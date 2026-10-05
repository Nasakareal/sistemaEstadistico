<?php

namespace App\Console\Commands;

use App\Models\ConduceLegalidadCaptura;
use App\Services\ConduceLegalidadActividadSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncConduceLegalidadActividades extends Command
{
    protected $signature = 'conduce-legalidad:sync-actividades
        {--all : Recalcula tambien las capturas que ya tienen actividad vinculada}
        {--dry-run : Solo cuenta las capturas pendientes, sin modificar datos}
        {--chunk=100 : Cantidad de capturas procesadas por lote}';

    protected $description = 'Sincroniza las alimentaciones de Conduce con Legalidad con Estadisticas de Actividades';

    public function handle(ConduceLegalidadActividadSyncService $sync): int
    {
        $query = ConduceLegalidadCaptura::query()
            ->whereHas('operativo', fn ($q) => $q->where('tipo_operativo', 'conduce_legalidad'))
            ->when(!$this->option('all'), fn ($q) => $q->whereNull('actividad_id'));

        $total = (clone $query)->count();
        if ($this->option('dry-run')) {
            $this->info("Capturas por sincronizar: {$total}");
            return self::SUCCESS;
        }

        if ($total === 0) {
            $this->info('No hay capturas pendientes de sincronizacion.');
            return self::SUCCESS;
        }

        $chunk = max(1, min(1000, (int) $this->option('chunk')));
        $sincronizadas = 0;
        $fallidas = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('id')->chunkById($chunk, function ($capturas) use (
            $sync,
            &$sincronizadas,
            &$fallidas,
            $bar
        ): void {
            foreach ($capturas as $captura) {
                try {
                    DB::transaction(function () use ($captura, $sync): void {
                        $bloqueada = ConduceLegalidadCaptura::query()
                            ->lockForUpdate()
                            ->findOrFail($captura->id);
                        $sync->sync($bloqueada);
                    });
                    $sincronizadas++;
                } catch (Throwable $e) {
                    try {
                        report($e);
                    } catch (Throwable $loggingError) {
                        // La reparacion debe continuar aunque el canal de logs
                        // tenga un problema independiente de esta captura.
                    }
                    $fallidas++;
                    $this->newLine();
                    $this->error("Captura {$captura->id}: {$e->getMessage()}");
                } finally {
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Sincronizadas: {$sincronizadas}; fallidas: {$fallidas}.");

        return $fallidas === 0 ? self::SUCCESS : self::FAILURE;
    }
}
