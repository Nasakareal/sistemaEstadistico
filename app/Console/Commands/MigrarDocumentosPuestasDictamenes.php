<?php

namespace App\Console\Commands;

use App\Models\Dictamen;
use App\Models\PuestaDisposicion;
use App\Services\Documentos\DocumentoArchivoStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrarDocumentosPuestasDictamenes extends Command
{
    protected $signature = 'documentos:migrar-puestas-dictamenes
        {--dry-run : Solo muestra conteos, sin subir ni borrar archivos}
        {--conservar-local : No borra el archivo local despues de subirlo}
        {--solo=todo : Opciones: todo, dictamenes, puestas}
        {--limit=0 : Maximo de registros por tipo; 0 procesa todos}';

    protected $description = 'Migra PDFs de dictamenes y puestas a disposicion al contenedor documentos.';

    public function handle(DocumentoArchivoStorage $storage): int
    {
        @set_time_limit(0);

        $dryRun = (bool) $this->option('dry-run');
        $deleteSource = !(bool) $this->option('conservar-local');
        $solo = strtolower((string) $this->option('solo'));
        $limit = max(0, (int) $this->option('limit'));
        $azureActivo = (bool) config('services.azure_storage.documentos_enabled', false);

        if (!in_array($solo, ['todo', 'dictamenes', 'puestas'], true)) {
            $this->error('La opcion --solo debe ser: todo, dictamenes o puestas.');
            return self::FAILURE;
        }

        if (!$azureActivo) {
            $this->warn('Azure documentos no esta activo. Configura AZURE_STORAGE_DOCUMENTOS_ENABLED=true para subir al contenedor documentos.');
        }

        $stats = $this->emptyStats($dryRun);

        if ($solo === 'todo' || $solo === 'dictamenes') {
            $this->migrarDictamenes($storage, $dryRun, $deleteSource, $limit, $stats);
        }

        if ($solo === 'todo' || $solo === 'puestas') {
            $this->migrarPuestas($storage, $dryRun, $deleteSource, $limit, $stats);
        }

        $this->info('Migracion de documentos');
        $this->line('Dry-run: ' . ($stats['dry_run'] ? 'SI' : 'NO'));
        $this->line('Registros revisados: ' . $stats['revisados']);
        $this->line('Archivos migrados: ' . $stats['migrados']);
        $this->line('Ya migrados: ' . $stats['ya_migrados']);
        $this->line('Sin cambios: ' . $stats['sin_cambios']);
        $this->line('Referencias actualizadas: ' . $stats['referencias_actualizadas']);
        $this->line('Archivos faltantes: ' . $stats['faltantes']);
        $this->line('Errores: ' . count($stats['errores']));

        foreach ($stats['errores'] as $error) {
            $this->error($error);
        }

        return empty($stats['errores']) ? self::SUCCESS : self::FAILURE;
    }

    private function migrarDictamenes(DocumentoArchivoStorage $storage, bool $dryRun, bool $deleteSource, int $limit, array &$stats): void
    {
        $procesados = 0;

        Dictamen::query()
            ->whereNotNull('archivo_dictamen')
            ->where('archivo_dictamen', '<>', '')
            ->orderBy('id')
            ->chunkById(100, function ($dictamenes) use ($storage, $dryRun, $deleteSource, $limit, &$procesados, &$stats) {
                foreach ($dictamenes as $dictamen) {
                    if ($limit > 0 && $procesados >= $limit) {
                        return false;
                    }

                    $procesados++;
                    $source = $this->normalizePath($dictamen->archivo_dictamen);
                    $target = $this->targetPath($source, 'dictamenes', $dictamen->created_at);

                    $this->migrarUno($storage, $dryRun, $deleteSource, $source, $target, function ($path) use ($dictamen) {
                        $dictamen->archivo_dictamen = $path;
                        $dictamen->save();
                    }, $stats);
                }

                return true;
            });
    }

    private function migrarPuestas(DocumentoArchivoStorage $storage, bool $dryRun, bool $deleteSource, int $limit, array &$stats): void
    {
        $procesados = 0;

        PuestaDisposicion::query()
            ->whereNotNull('archivo_puesta')
            ->where('archivo_puesta', '<>', '')
            ->orderBy('id')
            ->chunkById(100, function ($puestas) use ($storage, $dryRun, $deleteSource, $limit, &$procesados, &$stats) {
                foreach ($puestas as $puesta) {
                    if ($limit > 0 && $procesados >= $limit) {
                        return false;
                    }

                    $procesados++;
                    $source = $this->normalizePath($puesta->archivo_puesta);
                    $target = $this->targetPath($source, 'puestas_disposicion', $puesta->created_at);

                    $this->migrarUno($storage, $dryRun, $deleteSource, $source, $target, function ($path) use ($puesta) {
                        $puesta->archivo_puesta = $path;
                        $puesta->save();
                    }, $stats);
                }

                return true;
            });
    }

    private function migrarUno(DocumentoArchivoStorage $storage, bool $dryRun, bool $deleteSource, string $source, string $target, callable $actualizar, array &$stats): void
    {
        $stats['revisados']++;

        if ($source === '' || $target === '') {
            $stats['faltantes']++;
            return;
        }

        try {
            if ($dryRun) {
                if (Storage::disk('public')->exists($source)) {
                    $stats['sin_cambios']++;
                } elseif ($storage->exists($target)) {
                    $stats['ya_migrados']++;
                } else {
                    $stats['faltantes']++;
                }

                return;
            }

            $resultado = $storage->migratePublicFile($source, $target, $deleteSource);

            if ($resultado['status'] === 'missing_source') {
                $stats['faltantes']++;
                return;
            }

            if ($resultado['status'] === 'already_migrated') {
                $stats['ya_migrados']++;
            } elseif (in_array($resultado['status'], ['migrated', 'migrated_local'], true)) {
                $stats['migrados']++;
            } else {
                $stats['sin_cambios']++;
            }

            $finalPath = (string) ($resultado['path'] ?? $target);

            if ($finalPath !== $source) {
                $actualizar($finalPath);
                $stats['referencias_actualizadas']++;
            }
        } catch (\Throwable $e) {
            $stats['errores'][] = $source . ': ' . $e->getMessage();
        }
    }

    private function targetPath(string $source, string $directory, $createdAt): string
    {
        $source = $this->normalizePath($source);
        $directory = trim($directory, '/');

        if (strpos($source, 'documentos/' . $directory . '/') === 0) {
            return substr($source, strlen('documentos/'));
        }

        if (strpos($source, $directory . '/') === 0) {
            return $source;
        }

        $yearMonth = $createdAt ? $createdAt->format('Y/m') : now()->format('Y/m');

        return $directory . '/' . $yearMonth . '/' . $this->sanitizeFileName(basename($source));
    }

    private function normalizePath(?string $path): string
    {
        $path = str_replace('\\', '/', trim((string) $path));
        $path = preg_replace('#^https?://[^/]+/storage/#i', '', $path);
        $path = preg_replace('#^/?storage/#', '', (string) $path);
        $path = preg_replace('#^/?public/#', '', (string) $path);

        return ltrim((string) $path, '/');
    }

    private function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
        $name = trim((string) $name, "._-\t\n\r\0\x0B");

        return $name !== '' ? substr($name, 0, 120) : 'documento.pdf';
    }

    private function emptyStats(bool $dryRun): array
    {
        return [
            'dry_run' => $dryRun,
            'revisados' => 0,
            'migrados' => 0,
            'ya_migrados' => 0,
            'sin_cambios' => 0,
            'referencias_actualizadas' => 0,
            'faltantes' => 0,
            'errores' => [],
        ];
    }
}
