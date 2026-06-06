<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\MapaPatrullasAccess;
use Illuminate\Console\Command;

class DetectDisconnectedUsers extends Command
{
    protected $signature = 'users:detect-disconnected {--minutes=5 : Minutos sin reportar para considerar desconectado}';

    protected $description = 'Marca peritos desconectados sin generar alertas.';

    public function handle()
    {
        $minutes = (int) $this->option('minutes');
        if ($minutes <= 0) { $minutes = 5; }

        $threshold = now()->subMinutes($minutes);

        $disconnectedUsersQuery = User::query()
            ->where('compartir_ubicacion', 1)
            ->where('unidad_id', MapaPatrullasAccess::UNIDAD_SINIESTROS_ID)
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '<=', $threshold)
            ->whereNull('disconnected_alert_sent_at');

        MapaPatrullasAccess::applyPeritoScope($disconnectedUsersQuery);

        $disconnectedUsers = $disconnectedUsersQuery
            ->get(['id', 'name', 'email', 'unidad_id', 'turno_id', 'last_seen_at', 'connection_status']);

        $marked = 0;

        foreach ($disconnectedUsers as $u) {
            $u->connection_status = 'offline';
            $u->disconnected_alert_sent_at = now();
            $u->save();
            $marked++;
        }

        $this->info("OK. Usuarios marcados offline: {$marked} | Alertas creadas: 0");

        return Command::SUCCESS;
    }
}
