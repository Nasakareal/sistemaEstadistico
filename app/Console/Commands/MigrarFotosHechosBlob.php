<?php

namespace App\Console\Commands;

use App\Models\Hechos;
use App\Models\Vehiculo;
use App\Services\Fotos\HechoFotoStorage;
use Illuminate\Console\Command;

class MigrarFotosHechosBlob extends Command
{
    protected $signature = 'hechos:fotos-migrar-blob
        {--dry-run : Solo muestra conteos, sin subir archivos}
        {--limit=0 : Maximo de referencias a revisar; 0 revisa todas}';

    protected $description = 'Copia fotos de hechos, vehiculos e inventarios al contenedor hechos-fotos de Azure sin borrar archivos locales.';

    public function handle(HechoFotoStorage $storage): int
    {
        @set_time_limit(0);

        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $stats = $this->emptyStats($dryRun);
        $procesados = 0;

        if (!$storage->usesAzure()) {
            $this->warn('Azure hechos-fotos no esta activo. Configura AZURE_STORAGE_HECHOS_FOTOS_ENABLED=true para subir al contenedor hechos-fotos.');
        }

        Hechos::query()
            ->where(function ($query) {
                $query->whereNotNull('foto_lugar')
                    ->orWhereNotNull('foto_situacion');
            })
            ->orderBy('id')
            ->chunkById(100, function ($hechos) use ($storage, $dryRun, $limit, &$procesados, &$stats) {
                foreach ($hechos as $hecho) {
                    foreach (['foto_lugar', 'foto_situacion'] as $campo) {
                        foreach ($this->pathsFromValue($hecho->{$campo}) as $path) {
                            if ($limit > 0 && $procesados >= $limit) {
                                return false;
                            }

                            $procesados++;
                            $this->copiarReferencia($storage, $path, $dryRun, $stats);
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
                    foreach (['fotos', 'foto_inventario_grua'] as $campo) {
                        foreach ($this->pathsFromValue($vehiculo->{$campo}) as $path) {
                            if ($limit > 0 && $procesados >= $limit) {
                                return false;
                            }

                            $procesados++;
                            $this->copiarReferencia($storage, $path, $dryRun, $stats);
                        }
                    }
                }

                return true;
            });

        $this->info('Migracion de fotos de hechos a Blob');
        $this->line('Dry-run: ' . ($stats['dry_run'] ? 'SI' : 'NO'));
        $this->line('Referencias revisadas: ' . $stats['revisadas']);
        $this->line('Por subir: ' . $stats['por_subir']);
        $this->line('Subidas: ' . $stats['subidas']);
        $this->line('Ya en Blob: ' . $stats['ya_en_blob']);
        $this->line('Faltantes locales: ' . $stats['faltantes']);
        $this->line('Errores: ' . count($stats['errores']));

        foreach ($stats['errores'] as $error) {
            $this->error($error);
        }

        return empty($stats['errores']) ? self::SUCCESS : self::FAILURE;
    }

    private function copiarReferencia(HechoFotoStorage $storage, string $path, bool $dryRun, array &$stats): void
    {
        $path = $storage->normalizePath($path);

        if ($path === '') {
            return;
        }

        $stats['revisadas']++;

        try {
            $exists = $storage->usesAzure() && $storage->exists($path);

            if ($exists) {
                $stats['ya_en_blob']++;
                return;
            }

            if ($dryRun) {
                if ($storage->sourceExists($path)) {
                    $stats['por_subir']++;
                } else {
                    $stats['faltantes']++;
                }
                return;
            }

            $result = $storage->putPublicFile($path, $path);
            $status = (string) ($result['status'] ?? '');

            if ($status === 'copied') {
                $stats['subidas']++;
            } elseif ($status === 'already_exists') {
                $stats['ya_en_blob']++;
            } elseif ($status === 'missing_source') {
                $stats['faltantes']++;
            }
        } catch (\Throwable $e) {
            $stats['errores'][] = $path . ': ' . $e->getMessage();
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

    private function emptyStats(bool $dryRun): array
    {
        return [
            'dry_run' => $dryRun,
            'revisadas' => 0,
            'por_subir' => 0,
            'subidas' => 0,
            'ya_en_blob' => 0,
            'faltantes' => 0,
            'errores' => [],
        ];
    }
}
