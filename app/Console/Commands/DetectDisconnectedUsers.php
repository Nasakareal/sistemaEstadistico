<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Console\Command;

class DetectDisconnectedUsers extends Command
{
    protected $signature = 'users:detect-disconnected {--minutes=5 : Minutos sin reportar para considerar desconectado}';

    protected $description = 'Detecta usuarios que dejaron de reportar (last_seen_at) y genera alertas por unidad+turno (Jefe de Grupo) y por unidad (Subdirector).';

    public function handle()
    {
        $minutes = (int) $this->option('minutes');
        if ($minutes <= 0) { $minutes = 5; }

        $threshold = now()->subMinutes($minutes);

        $subdirectores = User::query()
            ->whereHas('roles', fn($q) => $q->where('name', 'Subdirector'))
            ->get(['id', 'name', 'unidad_id', 'turno_id']);

        $jefes = User::query()
            ->whereHas('roles', fn($q) => $q->where('name', 'Jefe de Grupo'))
            ->get(['id', 'name', 'unidad_id', 'turno_id']);

        $subdirectoresPorUnidad = $subdirectores->groupBy(fn(User $u) => (string)($u->unidad_id ?? 'null'));

        $jefesPorUnidadTurno = $jefes->groupBy(function (User $u) {
            return (string)($u->unidad_id ?? 'null') . ':' . (string)($u->turno_id ?? 'null');
        });

        $disconnectedUsers = User::query()
            ->where('compartir_ubicacion', 1)
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '<=', $threshold)
            ->whereNull('disconnected_alert_sent_at')
            ->get(['id', 'name', 'email', 'unidad_id', 'turno_id', 'last_seen_at', 'connection_status']);

        $marked = 0;
        $alertsCreated = 0;

        foreach ($disconnectedUsers as $u) {

            $unidadKey = (string)($u->unidad_id ?? 'null');
            $unidadTurnoKey = $unidadKey . ':' . (string)($u->turno_id ?? 'null');

            $recipients = collect();

            if ($subdirectoresPorUnidad->has($unidadKey)) {
                $recipients = $recipients->merge($subdirectoresPorUnidad->get($unidadKey));
            }

            if ($jefesPorUnidadTurno->has($unidadTurnoKey)) {
                $recipients = $recipients->merge($jefesPorUnidadTurno->get($unidadTurnoKey));
            }

            $recipients = $recipients->unique('id')->values();

            $u->connection_status = 'offline';
            $u->disconnected_alert_sent_at = now();
            $u->save();
            $marked++;

            if ($recipients->isEmpty()) {
                continue;
            }

            $lastSeenStr = optional($u->last_seen_at)->format('Y-m-d H:i:s');

            foreach ($recipients as $r) {
                Alert::create([
                    'to_user_id'   => $r->id,
                    'from_user_id' => null,
                    'type'         => 'ELEMENTO_DESCONECTADO',
                    'title'        => 'Elemento desconectado',
                    'message'      => $u->name . ' dejó de reportar hace más de ' . $minutes . ' min. Último reporte: ' . $lastSeenStr,
                    'data'         => [
                        'user_id'      => $u->id,
                        'name'         => $u->name,
                        'email'        => $u->email,
                        'unidad_id'    => $u->unidad_id,
                        'turno_id'     => $u->turno_id,
                        'minutes'      => $minutes,
                        'last_seen_at' => optional($u->last_seen_at)->toISOString(),
                    ],
                ]);

                $alertsCreated++;
            }
        }

        $this->info("OK. Usuarios marcados offline: {$marked} | Alertas creadas: {$alertsCreated}");

        return Command::SUCCESS;
    }
}
