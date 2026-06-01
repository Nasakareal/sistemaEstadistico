<?php

namespace App\Console\Commands;

use App\Models\Personal;
use App\Models\PersonalFoto;
use App\Services\Fotos\PersonalFotoStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrarFotosPersonalBlob extends Command
{
    protected $signature = 'personal:fotos-migrar-blob
        {--dry-run : Solo muestra conteos, sin subir ni borrar archivos}
        {--conservar-local : No borra la copia local despues de subirla}
        {--limpiar-huerfanos : Revisa fotos locales no referenciadas en BD y borra solo las que ya existen en Azure}
        {--limit=0 : Maximo de registros a procesar; 0 procesa todos}';

    protected $description = 'Migra fotos de personal al contenedor fotos/personal de Azure.';

    public function handle(PersonalFotoStorage $storage): int
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
            $this->warn('Azure fotos no esta activo. Configura AZURE_STORAGE_FOTOS_ENABLED=true para subir al contenedor fotos.');
        }

        $procesados = 0;

        PersonalFoto::query()
            ->whereNotNull('ruta')
            ->where('ruta', '<>', '')
            ->orderBy('id')
            ->chunkById(100, function ($fotos) use ($storage, $dryRun, $deleteSource, $limit, $azureActivo, &$procesados, &$stats, &$referenciados) {
                foreach ($fotos as $foto) {
                    if ($limit > 0 && $procesados >= $limit) {
                        return false;
                    }

                    $procesados++;
                    $this->migrarReferencia($storage, $foto->ruta, function (string $target) use ($foto) {
                        $source = $foto->ruta;
                        $foto->ruta = $target;
                        $foto->save();

                        Personal::query()
                            ->where('foto', $source)
                            ->update(['foto' => $target]);
                    }, $dryRun, $deleteSource, $azureActivo, $stats, $referenciados);
                }

                return true;
            });

        Personal::query()
            ->whereNotNull('foto')
            ->where('foto', '<>', '')
            ->orderBy('id')
            ->chunkById(100, function ($personales) use ($storage, $dryRun, $deleteSource, $limit, $azureActivo, &$procesados, &$stats, &$referenciados) {
                foreach ($personales as $personal) {
                    if ($limit > 0 && $procesados >= $limit) {
                        return false;
                    }

                    $source = (string) $personal->foto;
                    $target = $this->targetPath($storage, $source);

                    if (isset($referenciados[$target])) {
                        continue;
                    }

                    $procesados++;
                    $this->migrarReferencia($storage, $source, function (string $target) use ($personal) {
                        $personal->foto = $target;
                        $personal->save();
                    }, $dryRun, $deleteSource, $azureActivo, $stats, $referenciados);
                }

                return true;
            });

        if ($cleanOrphans) {
            $this->limpiarHuerfanos($storage, $referenciados, $dryRun, $deleteSource, $azureActivo, $stats);
        }

        $this->info('Migracion de fotos de personal');
        $this->line('Dry-run: ' . ($stats['dry_run'] ? 'SI' : 'NO'));
        $this->line('Referencias revisadas: ' . $stats['revisadas']);
        $this->line('Por migrar: ' . $stats['por_migrar']);
        $this->line('Migradas: ' . $stats['migradas']);
        $this->line('Ya migradas: ' . $stats['ya_migradas']);
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

    private function migrarReferencia(PersonalFotoStorage $storage, ?string $source, callable $actualizar, bool $dryRun, bool $deleteSource, bool $azureActivo, array &$stats, array &$referenciados): void
    {
        $source = $storage->normalizeSourcePath($source);
        $target = $this->targetPath($storage, $source);

        if ($source === '' || $target === '') {
            $stats['faltantes']++;
            return;
        }

        $referenciados[$target] = true;
        $stats['revisadas']++;

        try {
            $localCount = $this->localCount($storage, $source);
            $remoteExists = $azureActivo && $storage->exists($target);

            if ($localCount > 0) {
                $stats['copias_locales_detectadas'] += $localCount;
            }

            if ($dryRun) {
                if ($localCount > 0 && !$remoteExists) {
                    $stats['por_migrar']++;
                } elseif ($remoteExists) {
                    $stats['ya_migradas']++;
                } else {
                    $stats['faltantes']++;
                }

                if ($source !== $target) {
                    $stats['referencias_por_actualizar']++;
                }

                return;
            }

            if ($localCount > 0 && !$remoteExists) {
                $resultado = $storage->migrateLocalFile($source, $target, false);

                if (($resultado['status'] ?? '') === 'migrated') {
                    $stats['migradas']++;
                    $remoteExists = true;
                } elseif (($resultado['status'] ?? '') === 'already_migrated') {
                    $stats['ya_migradas']++;
                    $remoteExists = true;
                } else {
                    $stats['faltantes']++;
                    return;
                }
            } elseif ($remoteExists) {
                $stats['ya_migradas']++;
            } else {
                $stats['faltantes']++;
                return;
            }

            if ($source !== $target) {
                $actualizar($target);
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

    private function limpiarHuerfanos(PersonalFotoStorage $storage, array $referenciados, bool $dryRun, bool $deleteSource, bool $azureActivo, array &$stats): void
    {
        foreach (['local', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);

            if (!$disk->exists('personals/fotos')) {
                continue;
            }

            foreach ($disk->allFiles('personals/fotos') as $path) {
                $target = $this->targetPath($storage, $path);

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
                    $disk->delete($path);
                    $stats['copias_locales_borradas']++;
                }
            }
        }
    }

    private function targetPath(PersonalFotoStorage $storage, string $source): string
    {
        $normalized = $storage->normalizePath($source);

        if (str_starts_with($normalized, 'personal/')) {
            return $normalized;
        }

        $name = $this->sanitizeFileName(basename($normalized));

        return 'personal/' . ($name !== '' ? $name : 'foto.jpg');
    }

    private function localCount(PersonalFotoStorage $storage, string $source): int
    {
        $count = 0;

        foreach ($storage->localCandidates($source) as $candidate) {
            if (Storage::disk($candidate['disk'])->exists($candidate['path'])) {
                $count++;
            }
        }

        return $count;
    }

    private function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
        $name = trim((string) $name, "._-\t\n\r\0\x0B");

        return $name !== '' ? substr($name, 0, 140) : '';
    }

    private function emptyStats(bool $dryRun): array
    {
        return [
            'dry_run' => $dryRun,
            'revisadas' => 0,
            'por_migrar' => 0,
            'migradas' => 0,
            'ya_migradas' => 0,
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
