<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\DeviceToken;
use App\Models\OperativoDispositivo;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class GuardianesCaminoRevisionService
{
    public const UNIDAD_CARRETERAS_ID = 4;

    private const ROLES_REVISORES = [
        'RT',
        'Encargado de Destacamento',
        'Encargado de destacamento',
    ];

    public function esRevisor(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isSuperadmin()) {
            return true;
        }

        if ((int) ($user->unidad_id ?? 0) !== self::UNIDAD_CARRETERAS_ID) {
            return false;
        }

        return $user->hasAnyRole(self::ROLES_REVISORES);
    }

    public function assertPuedeRevisar(?User $user, ?OperativoDispositivo $dispositivo = null): void
    {
        if (!$this->esRevisor($user)) {
            abort(403, 'Solo RT o Encargado de Destacamento pueden revisar dispositivos de Carreteras.');
        }

        if (!$user || $user->isSuperadmin() || !$dispositivo) {
            return;
        }

        if ((int) ($dispositivo->unidad_org_id ?? 0) !== self::UNIDAD_CARRETERAS_ID) {
            abort(403, 'Este dispositivo no pertenece a Carreteras.');
        }

        if ($user->destacamento_id && $dispositivo->destacamento_id) {
            if ((int) $user->destacamento_id !== (int) $dispositivo->destacamento_id) {
                abort(403, 'No puedes revisar dispositivos de otro destacamento.');
            }
        }
    }

    public function puedeVerDispositivo(?User $user, OperativoDispositivo $dispositivo): bool
    {
        if ($dispositivo->estaAprobado()) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->isSuperadmin()) {
            return true;
        }

        if ((int) ($dispositivo->user_id ?? 0) === (int) $user->id) {
            return true;
        }

        if (!$this->esRevisor($user)) {
            return false;
        }

        if ($user->destacamento_id && $dispositivo->destacamento_id) {
            return (int) $user->destacamento_id === (int) $dispositivo->destacamento_id;
        }

        return true;
    }

    public function aplicarScopePendientes($query, User $user): void
    {
        $this->assertPuedeRevisar($user);

        $query->pendientesRevision()
            ->where('unidad_org_id', self::UNIDAD_CARRETERAS_ID);

        if (!$user->isSuperadmin() && $user->destacamento_id) {
            $query->where('destacamento_id', $user->destacamento_id);
        }
    }

    public function aprobar(OperativoDispositivo $dispositivo, User $user, ?string $observacion = null): void
    {
        $this->assertPuedeRevisar($user, $dispositivo);

        $dispositivo->update([
            'estado_revision' => OperativoDispositivo::REVISION_APROBADO,
            'revisado_por' => $user->id,
            'revisado_at' => now(),
            'observacion_revision' => $observacion,
            'updated_by' => $user->id,
        ]);
    }

    public function rechazar(OperativoDispositivo $dispositivo, User $user, string $observacion): void
    {
        $this->assertPuedeRevisar($user, $dispositivo);

        $dispositivo->update([
            'estado_revision' => OperativoDispositivo::REVISION_RECHAZADO,
            'revisado_por' => $user->id,
            'revisado_at' => now(),
            'observacion_revision' => $observacion,
            'updated_by' => $user->id,
        ]);
    }

    public function notificarRevisionPendiente(OperativoDispositivo $dispositivo): void
    {
        if (!in_array($dispositivo->estado_revision, [null, OperativoDispositivo::REVISION_PENDIENTE], true)) {
            return;
        }

        $dispositivo->loadMissing(['catalogo', 'destacamento', 'usuario']);

        $usuarios = User::query()
            ->where('unidad_id', self::UNIDAD_CARRETERAS_ID)
            ->where('estado', 'Activo')
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', self::ROLES_REVISORES);
            })
            ->when($dispositivo->destacamento_id, function ($q) use ($dispositivo) {
                $q->where(function ($sub) use ($dispositivo) {
                    $sub->whereNull('destacamento_id')
                        ->orWhere('destacamento_id', $dispositivo->destacamento_id);
                });
            })
            ->get();

        if ($usuarios->isEmpty()) {
            Log::warning('Guardianes: no hay RT/Encargado para notificar revisión pendiente.', [
                'dispositivo_id' => $dispositivo->id,
                'destacamento_id' => $dispositivo->destacamento_id,
            ]);

            return;
        }

        $title = 'Dispositivo pendiente de revisión';
        $catalogo = $dispositivo->catalogo->nombre ?? 'Guardianes del Camino';
        $destacamento = $dispositivo->destacamento->nombre ?? $dispositivo->destacamento_nombre_snapshot ?? 'sin destacamento';
        $message = "Agente UPEC subió {$catalogo} en {$destacamento}. Revísalo antes de que cuente en estadísticas.";

        $payload = [
            'type' => 'GUARDIANES_REVISION',
            'dispositivo_id' => (string) $dispositivo->id,
            'route' => '/dispositivos/revision',
            'estado_revision' => OperativoDispositivo::REVISION_PENDIENTE,
        ];

        foreach ($usuarios as $usuario) {
            Alert::create([
                'to_user_id' => $usuario->id,
                'from_user_id' => $dispositivo->user_id,
                'type' => 'guardianes_revision_pendiente',
                'title' => $title,
                'message' => $message,
                'data' => $payload,
            ]);
        }

        $tokens = DeviceToken::query()
            ->whereIn('user_id', $usuarios->pluck('id')->all())
            ->pluck('token')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($tokens)) {
            return;
        }

        try {
            app(PushService::class)->sendToTokens($tokens, $title, $message, $payload);
        } catch (\Throwable $e) {
            Log::error('Guardianes: no se pudo enviar push de revisión pendiente.', [
                'dispositivo_id' => $dispositivo->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
