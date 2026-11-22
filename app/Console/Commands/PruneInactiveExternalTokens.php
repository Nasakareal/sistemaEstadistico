<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class PruneInactiveExternalTokens extends Command
{
    protected $signature = 'security:prune-inactive-tokens {--days=60} {--dry-run}';

    protected $description = 'Elimina tokens de Sanctum sin actividad durante el periodo indicado';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 30) {
            $this->error('El periodo mínimo permitido es de 30 días.');
            return 1;
        }

        $cutoff = now()->subDays($days);

        $query = PersonalAccessToken::query()
            ->where(function ($q) use ($cutoff) {
                $q->where('last_used_at', '<', $cutoff)
                    ->orWhere(function ($q) use ($cutoff) {
                        $q->whereNull('last_used_at')
                            ->where('created_at', '<', $cutoff);
                    });
            });

        $total = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("Tokens inactivos por más de {$days} días: {$total}");
            $this->info('No se eliminó ningún token.');
            return 0;
        }

        $deleted = $query->delete();

        $this->info("Tokens eliminados: {$deleted}");

        return 0;
    }
}
