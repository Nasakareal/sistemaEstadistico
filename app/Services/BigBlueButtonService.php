<?php

namespace App\Services;

use App\Models\LicenciaPuntoCurso;
use App\Models\LicenciaPuntoCursoParticipante;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class BigBlueButtonService
{
    public function enabled(): bool
    {
        return (bool) config('services.bigbluebutton.enabled');
    }

    public function ensureMeetingCredentials(LicenciaPuntoCurso $curso): LicenciaPuntoCurso
    {
        $updates = [];

        if (!$curso->bbb_meeting_id) {
            $updates['bbb_meeting_id'] = 'curso-puntos-' . $curso->id . '-' . Str::lower(Str::random(10));
        }

        if (!$curso->bbb_moderator_password) {
            $updates['bbb_moderator_password'] = Str::random(18);
        }

        if (!$curso->bbb_attendee_password) {
            $updates['bbb_attendee_password'] = Str::random(18);
        }

        if ($updates) {
            $curso->forceFill($updates)->save();
            $curso->refresh();
        }

        return $curso;
    }

    public function createMeeting(LicenciaPuntoCurso $curso): LicenciaPuntoCurso
    {
        $this->assertConfigured();
        $curso = $this->ensureMeetingCredentials($curso);

        $params = [
            'name' => $curso->nombre,
            'meetingID' => $curso->bbb_meeting_id,
            'attendeePW' => $curso->bbb_attendee_password,
            'moderatorPW' => $curso->bbb_moderator_password,
            'record' => $curso->bbb_record ? 'true' : 'false',
            'autoStartRecording' => 'false',
            'allowStartStopRecording' => 'true',
            'muteOnStart' => ($curso->bbb_mute_on_start && !$curso->bbb_anyone_can_talk) ? 'true' : 'false',
            'endWhenNoModerator' => 'true',
            'endWhenNoModeratorDelayInMinutes' => '10',
            'allowModsToUnmuteUsers' => 'true',
            'lockSettingsLockOnJoin' => ($curso->bbb_lock_viewers_microphone && !$curso->bbb_anyone_can_talk) ? 'true' : 'false',
            'lockSettingsDisableMic' => ($curso->bbb_lock_viewers_microphone && !$curso->bbb_anyone_can_talk) ? 'true' : 'false',
            'meta_sistema' => 'sistemaEstadistico',
            'meta_curso_id' => (string) $curso->id,
            'meta_curso_folio' => $curso->folio,
        ];

        $xml = $this->call('create', $params);
        $createTime = isset($xml->createTime) ? (string) $xml->createTime : null;

        $curso->forceFill([
            'bbb_create_time' => $createTime ?: $curso->bbb_create_time,
            'bbb_last_started_at' => now('America/Mexico_City'),
        ])->save();

        return $curso->fresh();
    }

    public function moderatorJoinUrl(LicenciaPuntoCurso $curso, User $user): string
    {
        $curso = $this->ensureMeetingCredentials($curso);

        return $this->joinUrl([
            'fullName' => $user->name ?: 'Instructor',
            'meetingID' => $curso->bbb_meeting_id,
            'password' => $curso->bbb_moderator_password,
            'userID' => 'user-' . $user->id,
            'createTime' => $curso->bbb_create_time,
        ]);
    }

    public function attendeeJoinUrl(LicenciaPuntoCurso $curso, LicenciaPuntoCursoParticipante $participante): string
    {
        $curso = $this->ensureMeetingCredentials($curso);

        if (!$curso->bbb_create_time) {
            throw new RuntimeException('El instructor aun no ha iniciado la clase en vivo.');
        }

        return $this->joinUrl([
            'fullName' => $participante->titular_nombre,
            'meetingID' => $curso->bbb_meeting_id,
            'password' => $curso->bbb_attendee_password,
            'userID' => 'participante-' . $participante->id,
            'createTime' => $curso->bbb_create_time,
        ]);
    }

    private function joinUrl(array $params): string
    {
        $this->assertConfigured();

        $params = array_filter($params, fn ($value) => $value !== null && $value !== '');
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $checksum = sha1('join' . $query . $this->secret());

        return $this->endpoint('join') . '?' . $query . '&checksum=' . $checksum;
    }

    private function call(string $name, array $params)
    {
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $checksum = sha1($name . $query . $this->secret());
        $url = $this->endpoint($name) . '?' . $query . '&checksum=' . $checksum;

        $response = Http::timeout((int) config('services.bigbluebutton.timeout', 15))->get($url);

        if (!$response->ok()) {
            throw new RuntimeException('BigBlueButton no respondio correctamente: HTTP ' . $response->status());
        }

        $xml = @simplexml_load_string($response->body());

        if (!$xml) {
            throw new RuntimeException('BigBlueButton devolvio una respuesta invalida.');
        }

        if (isset($xml->returncode) && (string) $xml->returncode !== 'SUCCESS') {
            $message = isset($xml->message) ? (string) $xml->message : 'Error desconocido de BigBlueButton.';
            throw new RuntimeException($message);
        }

        return $xml;
    }

    private function endpoint(string $call): string
    {
        $base = rtrim((string) config('services.bigbluebutton.url'), '/');

        if (Str::endsWith($base, '/api')) {
            return $base . '/' . $call;
        }

        return $base . '/api/' . $call;
    }

    private function secret(): string
    {
        return (string) config('services.bigbluebutton.secret');
    }

    private function assertConfigured(): void
    {
        if (!$this->enabled()) {
            throw new RuntimeException('BigBlueButton no esta habilitado en la configuracion.');
        }

        if (!config('services.bigbluebutton.url') || !config('services.bigbluebutton.secret')) {
            throw new RuntimeException('Falta configurar BIGBLUEBUTTON_URL y BIGBLUEBUTTON_SECRET.');
        }
    }
}
