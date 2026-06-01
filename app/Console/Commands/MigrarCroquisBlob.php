<?php

namespace App\Console\Commands;

use App\Models\Croquis;
use App\Services\Croquis\CroquisArchivoStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrarCroquisBlob extends Command
{
    protected $signature = 'croquis:migrar-blob
        {--dry-run : Solo muestra conteos, sin subir ni borrar archivos}
        {--conservar-local : No borra la copia local despues de subirla}
        {--limpiar-huerfanos : Revisa previews locales no referenciados en BD y borra solo los que ya existen en Azure}
        {--limit=0 : Maximo de registros a procesar; 0 procesa todos}';

    protected $description = 'Migra previews de croquis al contenedor croquis de Azure.';

    public function handle(CroquisArchivoStorage $storage): int
    {
        @set_time_limit(0);

        $dryRun = (bool) $this->option('dry-run');
        $deleteSource = !(bool) $this->option('conservar-local');
        $cleanOrphans = (bool) $this->option('limpiar-huerfanos');
        $limit = max(0, (int) $this->option('limit'));
        $azureActivo = $storage->usesAzure();
        $stats = $this->emptyStats($dryRun);
        $referenciados = [];

        if (!$azureActivo) {
            $this->warn('Azure croquis no esta activo. Configura AZURE_STORAGE_CROQUIS_ENABLED=true para subir al contenedor croquis.');
        }

        $procesados = 0;

        Croquis::query()
            ->whereNotNull('imagen_preview')
            ->where('imagen_preview', '<>', '')
            ->orderBy('id')
            ->chunkById(100, function ($croquisRows) use ($storage, $dryRun, $deleteSource, $limit, $azureActivo, &$procesados, &$stats, &$referenciados) {
                foreach ($croquisRows as $croquis) {
                    if ($limit > 0 && $procesados >= $limit) {
                        return false;
                    }

                    $procesados++;
                    $source = trim((string) $croquis->imagen_preview);

                    if ($this->esDatoEmbebidoOUrl($source)) {
                        $stats['omitidos']++;
                        continue;
                    }

                    $target = $this->targetPath($storage, $source, (int) $croquis->hecho_id);
                    $referenciados[$target] = true;
                    $stats['revisados']++;

                    try {
                        $local = $storage->localPath($source);
                        $remoteExists = $azureActivo && $storage->exists($target);

                        if ($local) {
                            $stats['copias_locales_detectadas']++;
                        }

                        if ($dryRun) {
                            if ($local && !$remoteExists) {
                                $stats['por_migrar']++;
                            } elseif ($remoteExists) {
                                $stats['ya_migrados']++;
                            } else {
                                $stats['faltantes']++;
                            }

                            if ($target !== $source) {
                                $stats['referencias_por_actualizar']++;
                            }

                            continue;
                        }

                        if ($local && !$remoteExists) {
                            $resultado = $storage->migrateLocalFile($local, $target, false);

                            if (($resultado['status'] ?? '') === 'migrated') {
                                $stats['migrados']++;
                                $remoteExists = true;
                            }
                        } elseif ($remoteExists) {
                            $stats['ya_migrados']++;
                        } else {
                            $stats['faltantes']++;
                            continue;
                        }

                        if ($croquis->imagen_preview !== $target) {
                            $croquis->imagen_preview = $target;
                            $croquis->save();
                            $stats['referencias_actualizadas']++;
                        }

                        if ($deleteSource && $remoteExists) {
                            $stats['copias_locales_borradas'] += $storage->deleteLocal($source);
                            $stats['copias_locales_borradas'] += $storage->deleteLocal($target);
                        }
                    } catch (\Throwable $e) {
                        $stats['errores'][] = $source . ': ' . $e->getMessage();
                    }
                }

                return true;
            });

        if ($cleanOrphans) {
            $this->limpiarHuerfanos($storage, $referenciados, $dryRun, $deleteSource, $azureActivo, $stats);
        }

        $this->info('Migracion de croquis');
        $this->line('Dry-run: ' . ($stats['dry_run'] ? 'SI' : 'NO'));
        $this->line('Registros revisados: ' . $stats['revisados']);
        $this->line('Omitidos por URL/data: ' . $stats['omitidos']);
        $this->line('Por migrar: ' . $stats['por_migrar']);
        $this->line('Migrados: ' . $stats['migrados']);
        $this->line('Ya migrados: ' . $stats['ya_migrados']);
        $this->line('Referencias por actualizar: ' . $stats['referencias_por_actualizar']);
        $this->line('Referencias actualizadas: ' . $stats['referencias_actualizadas']);
        $this->line('Copias locales detectadas: ' . $stats['copias_locales_detectadas']);
        $this->line('Copias locales borradas: ' . $stats['copias_locales_borradas']);
        $this->line('Huerfanos locales revisados: ' . $stats['huerfanos_locales']);
        $this->line('Huerfanos con copia en Azure: ' . $stats['huerfanos_con_blob']);
        $this->line('Huerfanos sin copia en Azure: ' . $stats['huerfanos_sin_blob']);
        $this->line('Faltantes: ' . $stats['faltantes']);
        $this->line('Errores: ' . count($stats['errores']));

        foreach ($stats['errores'] as $error) {
            $this->error($error);
        }

        return empty($stats['errores']) ? self::SUCCESS : self::FAILURE;
    }

    private function limpiarHuerfanos(CroquisArchivoStorage $storage, array $referenciados, bool $dryRun, bool $deleteSource, bool $azureActivo, array &$stats): void
    {
        $dir = public_path('img/croquis/previews');

        if (!is_dir($dir)) {
            return;
        }

        foreach (File::files($dir) as $file) {
            $target = 'previews/' . $file->getFilename();

            if (isset($referenciados[$target])) {
                continue;
            }

            $stats['huerfanos_locales']++;

            if (!$azureActivo || !$storage->exists($target)) {
                $stats['huerfanos_sin_blob']++;
                continue;
            }

            $stats['huerfanos_con_blob']++;
            $stats['copias_locales_detectadas']++;

            if (!$dryRun && $deleteSource) {
                @unlink($file->getPathname());
                $stats['copias_locales_borradas']++;
            }
        }
    }

    private function targetPath(CroquisArchivoStorage $storage, string $source, int $hechoId): string
    {
        $normalized = $storage->normalizePath($source);

        if (str_starts_with($normalized, 'previews/')) {
            return $normalized;
        }

        $name = basename($normalized);

        if ($name === '' || $name === '.' || $name === '/') {
            $name = 'hecho_' . $hechoId . '_croquis.png';
        }

        return 'previews/' . $this->sanitizeFileName($name);
    }

    private function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
        $name = trim((string) $name, "._-\t\n\r\0\x0B");

        return $name !== '' ? substr($name, 0, 120) : 'croquis.png';
    }

    private function esDatoEmbebidoOUrl(string $source): bool
    {
        return preg_match('/^(data:image|https?:\/\/)/i', $source) === 1;
    }

    private function emptyStats(bool $dryRun): array
    {
        return [
            'dry_run' => $dryRun,
            'revisados' => 0,
            'omitidos' => 0,
            'por_migrar' => 0,
            'migrados' => 0,
            'ya_migrados' => 0,
            'referencias_por_actualizar' => 0,
            'referencias_actualizadas' => 0,
            'copias_locales_detectadas' => 0,
            'copias_locales_borradas' => 0,
            'huerfanos_locales' => 0,
            'huerfanos_con_blob' => 0,
            'huerfanos_sin_blob' => 0,
            'faltantes' => 0,
            'errores' => [],
        ];
    }
}
