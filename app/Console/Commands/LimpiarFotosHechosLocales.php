<?php

namespace App\Console\Commands;

use App\Models\Hechos;
use App\Models\Vehiculo;
use App\Services\Fotos\HechoFotoStorage;
use Illuminate\Console\Command;

class LimpiarFotosHechosLocales extends Command
{
    protected $signature = 'hechos:fotos-limpiar-locales
        {--dry-run : Solo muestra lo que se borraria, sin borrar archivos}
        {--limit=0 : Maximo de referencias a revisar; 0 revisa todas}';

    protected $description = 'Borra copias locales de fotos de hechos solo si ya existen en el contenedor hechos-fotos.';

    public function handle(HechoFotoStorage $storage): int
    {
        @set_time_limit(0);

        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $stats = [
            'dry_run' => $dryRun,
            'revisadas' => 0,
            'confirmadas_blob' => 0,
            'borradas_locales' => 0,
            'sin_local' => 0,
            'omitidas_sin_blob' => 0,
            'errores' => [],
            'rows' => [],
        ];

        if (!$storage->usesAzure()) {
            $this->warn('Azure hechos-fotos no esta activo. No se borrara nada.');
            return self::FAILURE;
        }

        $procesados = 0;

        Hechos::query()
            ->where(function ($query) {
                $query->whereNotNull('foto_lugar')
                    ->orWhereNotNull('foto_lugar_2')
                    ->orWhereNotNull('foto_situacion');
            })
            ->orderBy('id')
            ->chunkById(100, function ($hechos) use ($storage, $dryRun, $limit, &$procesados, &$stats) {
                foreach ($hechos as $hecho) {
                    foreach (['foto_lugar', 'foto_lugar_2', 'foto_situacion'] as $campo) {
                        foreach ($this->pathsFromValue($hecho->{$campo}) as $path) {
                            if ($limit > 0 && $procesados >= $limit) {
                                return false;
                            }

                            $procesados++;
                            $this->limpiarReferencia($storage, 'hecho', (int) $hecho->id, null, $campo, $path, $dryRun, $stats);
                        }
                    }
                }

                return true;
            });

        Vehiculo::query()
            ->where(function ($query) {
                $query->whereNotNull('fotos')
                    ->orWhereNotNull('foto_inventario_grua');
            })
            ->orderBy('id')
            ->chunkById(100, function ($vehiculos) use ($storage, $dryRun, $limit, &$procesados, &$stats) {
                foreach ($vehiculos as $vehiculo) {
                    $hechoId = optional($vehiculo->hechos()->orderBy('hechos.id')->first())->id;

                    foreach (['fotos', 'foto_inventario_grua'] as $campo) {
                        foreach ($this->pathsFromValue($vehiculo->{$campo}) as $path) {
                            if ($limit > 0 && $procesados >= $limit) {
                                return false;
                            }

                            $procesados++;
                            $this->limpiarReferencia($storage, 'vehiculo', (int) $vehiculo->id, $hechoId ? (int) $hechoId : null, $campo, $path, $dryRun, $stats);
                        }
                    }
                }

                return true;
            });

        $this->info('Limpieza local de fotos de hechos');
        $this->line('Dry-run: ' . ($stats['dry_run'] ? 'SI' : 'NO'));
        $this->line('Referencias revisadas: ' . $stats['revisadas']);
        $this->line('Confirmadas en hechos-fotos: ' . $stats['confirmadas_blob']);
        $this->line('Archivos locales borrados: ' . $stats['borradas_locales']);
        $this->line('Sin copia local: ' . $stats['sin_local']);
        $this->line('Omitidas sin Blob destino: ' . $stats['omitidas_sin_blob']);
        $this->line('Errores: ' . count($stats['errores']));

        if (!empty($stats['rows'])) {
            $this->table(
                ['tipo', 'id', 'hecho_id', 'campo', 'locales', 'path'],
                $stats['rows']
            );
        }

        foreach ($stats['errores'] as $error) {
            $this->error($error);
        }

        return empty($stats['errores']) ? self::SUCCESS : self::FAILURE;
    }

    private function limpiarReferencia(HechoFotoStorage $storage, string $tipo, int $id, ?int $hechoId, string $campo, string $path, bool $dryRun, array &$stats): void
    {
        $path = $storage->normalizePath($path);

        if ($path === '') {
            return;
        }

        $stats['revisadas']++;

        try {
            if (!$storage->targetBlobExists($path)) {
                $stats['omitidas_sin_blob']++;
                return;
            }

            $stats['confirmadas_blob']++;
            $locales = $storage->localExistingCount($path);

            if ($locales < 1) {
                $stats['sin_local']++;
                return;
            }

            if (!$dryRun) {
                $locales = $storage->deleteLocal($path);
            }

            $stats['borradas_locales'] += $locales;
            $stats['rows'][] = [
                $tipo,
                $id,
                $hechoId ?: '',
                $campo,
                $locales,
                $path,
            ];
        } catch (\Throwable $e) {
            $stats['errores'][] = $tipo . ' ' . $id . ' ' . $campo . ' ' . $path . ': ' . $e->getMessage();
        }
    }

    private function pathsFromValue($value): array
    {
        if (empty($value)) {
            return [];
        }

        if (is_array($value)) {
            return collect($value)
                ->flatMap(fn ($item) => $this->pathsFromValue($item))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $path = trim((string) $value);

        if ($path === '') {
            return [];
        }

        $json = json_decode($path, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->pathsFromValue($json);
        }

        if (str_contains($path, ',')) {
            return collect(explode(',', $path))
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return [$path];
    }
}
