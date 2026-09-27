<?php

namespace App\Console\Commands;

use App\Models\SecurityEvent;
use Illuminate\Console\Command;

class PruneSecurityEvents extends Command
{
    protected $signature = 'security:prune-events {--days= : Días que se conservarán}';

    protected $description = 'Elimina eventos de seguridad anteriores al periodo de retención';

    public function handle(): int
    {
        $days = max(7, (int) ($this->option('days') ?: config('security_logging.retention_days', 90)));
        $deleted = SecurityEvent::query()
            ->where('last_seen_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Se eliminaron {$deleted} eventos de seguridad con más de {$days} días.");

        return 0;
    }
}
