<?php

namespace App\Console\Commands;

use App\Models\Actividad;
use App\Models\ActividadFoto;
use App\Services\Fotos\ActividadFotoBlobStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class LimpiarFotosActividadesLocalesBlob extends Command
{
    protected $signature = 'actividades:fotos-limpiar-locales-blob
        {--force : Borra los archivos locales confirmados en Blob; sin esta opcion solo reporta}
        {--limpiar-zips : Borra ZIPs archivados solo si todas sus fotos referenciadas ya tienen Blob}
        {--limpiar-cache-pdf : Borra storage/app/public/actividades/pdf_cache}
        {--mostrar=30 : Maximo de rutas a mostrar en pantalla}';

    protected $description = 'Libera espacio local de fotos de actividades ya copiadas a Blob sin tocar fotos de hechos.';

    private array $blobExistsCache = [];
    private array $pathsTouched = [];

    public function handle(ActividadFotoBlobStorage $blobStorage): int
    {
        @set_time_limit(0);

        $force = (bool) $this->option('force');
        $limpiarZips = (bool) $this->option('limpiar-zips');
        $limpiarCachePdf = (bool) $this->option('limpiar-cache-pdf');
        $mostrar = max(0, (int) $this->option('mostrar'));
        $mostradas = 0;

        if (!$this->blobColumnsReady()) {
            $this->warn('Faltan columnas Blob en actividades/actividad_fotos. Ejecuta php artisan migrate antes de limpiar locales.');

            return self::FAILURE;
        }

        $stats = [
            'modo_borrar' => $force,
            'originales_revisados' => 0,
            'originales_confirmados' => 0,
            'originales_borrados' => 0,
            'thumbnails_revisados' => 0,
            'thumbnails_confirmados' => 0,
            'thumbnails_borrados' => 0,
            'zips_revisados' => 0,
            'zips_confirmados' => 0,
            'zips_borrados' => 0,
            'cache_pdf_archivos' => 0,
            'cache_pdf_borrados' => 0,
            'bytes_liberables' => 0,
            'bytes_liberados' => 0,
            'errores' => [],
        ];

        $disk = Storage::disk('public');

        ActividadFoto::query()
            ->where(function ($q) {
                $q->whereNotNull('foto_path')
                    ->orWhereNotNull('foto_thumbnail_path');
            })
            ->orderBy('id')
            ->chunkById(100, function ($fotos) use ($blobStorage, $disk, $force, $mostrar, &$mostradas, &$stats) {
                foreach ($fotos as $foto) {
                    $this->limpiarArchivo(
                        $blobStorage,
                        $disk,
                        $foto->foto_path,
                        $foto->foto_blob_path,
                        'originales',
                        $force,
                        $stats,
                        $mostradas,
                        $mostrar
                    );

                    $this->limpiarArchivo(
                        $blobStorage,
                        $disk,
                        $foto->foto_thumbnail_path,
                        $foto->foto_thumbnail_blob_path,
                        'thumbnails',
                        $force,
                        $stats,
                        $mostradas,
                        $mostrar
                    );
                }

                return true;
            });

        Actividad::query()
            ->where(function ($q) {
                $q->whereNotNull('foto_path')
                    ->orWhereNotNull('foto_thumbnail_path');
            })
            ->orderBy('id')
            ->chunkById(100, function ($actividades) use ($blobStorage, $disk, $force, $mostrar, &$mostradas, &$stats) {
                foreach ($actividades as $actividad) {
                    $this->limpiarArchivo(
                        $blobStorage,
                        $disk,
                        $actividad->foto_path,
                        $actividad->foto_blob_path,
                        'originales',
                        $force,
                        $stats,
                        $mostradas,
                        $mostrar
                    );

                    $this->limpiarArchivo(
                        $blobStorage,
                        $disk,
                        $actividad->foto_thumbnail_path,
                        $actividad->foto_thumbnail_blob_path,
                        'thumbnails',
                        $force,
                        $stats,
                        $mostradas,
                        $mostrar
                    );
                }

                return true;
            });

        if ($limpiarZips) {
            $this->limpiarZips($blobStorage, $disk, $force, $stats, $mostradas, $mostrar);
        }

        if ($limpiarCachePdf) {
            $this->limpiarCachePdf($disk, $force, $stats, $mostradas, $mostrar);
        }

        $this->imprimirStats($stats);

        return empty($stats['errores']) ? self::SUCCESS : self::FAILURE;
    }

    private function limpiarArchivo(
        ActividadFotoBlobStorage $blobStorage,
        $disk,
        ?string $localPath,
        ?string $blobPath,
        string $tipo,
        bool $force,
        array &$stats,
        int &$mostradas,
        int $mostrar
    ): void {
        $localPath = $blobStorage->normalizeLocalPath($localPath);
        $blobPath = $blobStorage->normalizeBlobPath($blobPath);

        if ($localPath === '' || isset($this->pathsTouched[$localPath])) {
            return;
        }

        $stats[$tipo . '_revisados']++;

        if ($blobPath === '' || !$disk->exists($localPath)) {
            return;
        }

        try {
            if (!$this->blobExists($blobStorage, $blobPath)) {
                return;
            }

            $stats[$tipo . '_confirmados']++;
            $bytes = (int) $disk->size($localPath);
            $stats['bytes_liberables'] += $bytes;
            $this->pathsTouched[$localPath] = true;
            $this->mostrarRuta($force ? 'Borrando' : 'Borraria', $localPath, $mostradas, $mostrar);

            if ($force) {
                if ($disk->delete($localPath)) {
                    $stats[$tipo . '_borrados']++;
                    $stats['bytes_liberados'] += $bytes;
                } else {
                    $stats['errores'][] = 'No se pudo borrar ' . $localPath;
                }
            }
        } catch (\Throwable $e) {
            $stats['errores'][] = $localPath . ': ' . $e->getMessage();
        }
    }

    private function limpiarZips(ActividadFotoBlobStorage $blobStorage, $disk, bool $force, array &$stats, int &$mostradas, int $mostrar): void
    {
        $zipPaths = ActividadFoto::query()
            ->whereNotNull('foto_archivo_zip_path')
            ->select('foto_archivo_zip_path')
            ->distinct()
            ->pluck('foto_archivo_zip_path')
            ->merge(
                Actividad::query()
                    ->whereNotNull('foto_archivo_zip_path')
                    ->select('foto_archivo_zip_path')
                    ->distinct()
                    ->pluck('foto_archivo_zip_path')
            )
            ->map(fn ($path) => $blobStorage->normalizeLocalPath($path))
            ->filter()
            ->unique()
            ->values();

        foreach ($zipPaths as $zipPath) {
            $stats['zips_revisados']++;

            if (!$disk->exists($zipPath) || !$this->zipCompletamenteRespaldado($blobStorage, $zipPath)) {
                continue;
            }

            $stats['zips_confirmados']++;
            $bytes = (int) $disk->size($zipPath);
            $stats['bytes_liberables'] += $bytes;
            $this->mostrarRuta($force ? 'Borrando ZIP' : 'Borraria ZIP', $zipPath, $mostradas, $mostrar);

            if ($force) {
                if ($disk->delete($zipPath)) {
                    $stats['zips_borrados']++;
                    $stats['bytes_liberados'] += $bytes;
                } else {
                    $stats['errores'][] = 'No se pudo borrar ZIP ' . $zipPath;
                }
            }
        }
    }

    private function zipCompletamenteRespaldado(ActividadFotoBlobStorage $blobStorage, string $zipPath): bool
    {
        $referencias = ActividadFoto::query()
            ->where('foto_archivo_zip_path', $zipPath)
            ->whereNotNull('foto_path')
            ->count()
            + Actividad::query()
                ->where('foto_archivo_zip_path', $zipPath)
                ->whereNotNull('foto_path')
                ->count();

        $sinBlob = ActividadFoto::query()
            ->where('foto_archivo_zip_path', $zipPath)
            ->whereNotNull('foto_path')
            ->where(function ($q) {
                $q->whereNull('foto_blob_path')
                    ->orWhere('foto_blob_path', '');
            })
            ->exists()
            || Actividad::query()
                ->where('foto_archivo_zip_path', $zipPath)
                ->whereNotNull('foto_path')
                ->where(function ($q) {
                    $q->whereNull('foto_blob_path')
                        ->orWhere('foto_blob_path', '');
                })
                ->exists();

        if ($referencias <= 0 || $sinBlob) {
            return false;
        }

        $blobPaths = ActividadFoto::query()
            ->where('foto_archivo_zip_path', $zipPath)
            ->whereNotNull('foto_path')
            ->pluck('foto_blob_path')
            ->merge(
                Actividad::query()
                    ->where('foto_archivo_zip_path', $zipPath)
                    ->whereNotNull('foto_path')
                    ->pluck('foto_blob_path')
            )
            ->filter()
            ->unique()
            ->values();

        if ($blobPaths->isEmpty()) {
            return false;
        }

        foreach ($blobPaths as $blobPath) {
            if (!$this->blobExists($blobStorage, (string) $blobPath)) {
                return false;
            }
        }

        return true;
    }

    private function limpiarCachePdf($disk, bool $force, array &$stats, int &$mostradas, int $mostrar): void
    {
        $dir = 'actividades/pdf_cache';

        if (!$disk->exists($dir)) {
            return;
        }

        foreach ($disk->allFiles($dir) as $path) {
            $stats['cache_pdf_archivos']++;
            $bytes = (int) $disk->size($path);
            $stats['bytes_liberables'] += $bytes;
            $this->mostrarRuta($force ? 'Borrando cache PDF' : 'Borraria cache PDF', $path, $mostradas, $mostrar);

            if ($force) {
                if ($disk->delete($path)) {
                    $stats['cache_pdf_borrados']++;
                    $stats['bytes_liberados'] += $bytes;
                } else {
                    $stats['errores'][] = 'No se pudo borrar cache PDF ' . $path;
                }
            }
        }
    }

    private function blobExists(ActividadFotoBlobStorage $blobStorage, string $blobPath): bool
    {
        if (!array_key_exists($blobPath, $this->blobExistsCache)) {
            $this->blobExistsCache[$blobPath] = $blobStorage->exists($blobPath);
        }

        return $this->blobExistsCache[$blobPath];
    }

    private function mostrarRuta(string $label, string $path, int &$mostradas, int $mostrar): void
    {
        if ($mostradas >= $mostrar) {
            return;
        }

        $this->line($label . ': ' . $path);
        $mostradas++;
    }

    private function imprimirStats(array $stats): void
    {
        $this->info('Limpieza local de fotos de actividades respaldadas en Blob');
        $this->line('Modo: ' . ($stats['modo_borrar'] ? 'BORRAR' : 'REPORTE'));
        $this->line('Originales revisados: ' . $stats['originales_revisados']);
        $this->line('Originales confirmados en Blob: ' . $stats['originales_confirmados']);
        $this->line('Originales borrados: ' . $stats['originales_borrados']);
        $this->line('Thumbnails revisados: ' . $stats['thumbnails_revisados']);
        $this->line('Thumbnails confirmados en Blob: ' . $stats['thumbnails_confirmados']);
        $this->line('Thumbnails borrados: ' . $stats['thumbnails_borrados']);
        $this->line('ZIPs revisados: ' . $stats['zips_revisados']);
        $this->line('ZIPs confirmados en Blob: ' . $stats['zips_confirmados']);
        $this->line('ZIPs borrados: ' . $stats['zips_borrados']);
        $this->line('Cache PDF archivos: ' . $stats['cache_pdf_archivos']);
        $this->line('Cache PDF borrados: ' . $stats['cache_pdf_borrados']);
        $this->line('Espacio liberable: ' . $this->formatBytes((int) $stats['bytes_liberables']));
        $this->line('Espacio liberado: ' . $this->formatBytes((int) $stats['bytes_liberados']));
        $this->line('Errores: ' . count($stats['errores']));

        foreach ($stats['errores'] as $error) {
            $this->error($error);
        }

        if (!$stats['modo_borrar']) {
            $this->warn('No se borro nada. Agrega --force cuando el reporte sea correcto.');
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    private function blobColumnsReady(): bool
    {
        return Schema::hasColumn('actividad_fotos', 'foto_blob_path')
            && Schema::hasColumn('actividad_fotos', 'foto_thumbnail_blob_path')
            && Schema::hasColumn('actividades', 'foto_blob_path')
            && Schema::hasColumn('actividades', 'foto_thumbnail_blob_path');
    }
}
