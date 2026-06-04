<?php

namespace App\Console\Commands;

use App\Models\Actividad;
use App\Models\ActividadFoto;
use App\Services\Fotos\ActividadFotoBlobStorage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class MigrarFotosActividadesBlob extends Command
{
    protected $signature = 'actividades:fotos-migrar-blob
        {--dry-run : Solo reporta, sin subir ni actualizar base de datos}
        {--limit=0 : Maximo de registros de actividad_fotos a revisar; 0 revisa todos}
        {--mostrar=30 : Maximo de rutas a mostrar en pantalla}
        {--sin-zips : No intenta recuperar originales desde ZIPs archivados}';

    protected $description = 'Copia fotos de actividades al contenedor fotos/actividades de Azure sin borrar archivos locales.';

    private array $existsCache = [];
    private bool $zipWarningShown = false;

    public function handle(ActividadFotoBlobStorage $storage): int
    {
        @set_time_limit(0);

        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $mostrar = max(0, (int) $this->option('mostrar'));
        $incluirZips = !(bool) $this->option('sin-zips');
        $azureActivo = $storage->usesAzure();
        $blobColumnsReady = $this->blobColumnsReady();
        $ahora = Carbon::now(config('app.timezone', 'America/Mexico_City'));

        if (!$blobColumnsReady) {
            $this->warn('Faltan columnas Blob en actividades/actividad_fotos. Ejecuta php artisan migrate antes de subir.');

            if (!$dryRun) {
                return self::FAILURE;
            }
        }

        if (!$azureActivo) {
            $this->warn('Azure fotos no esta activo. Configura AZURE_STORAGE_FOTOS_ENABLED=true para subir al contenedor fotos.');

            if (!$dryRun) {
                return self::FAILURE;
            }
        }

        $stats = $this->emptyStats($dryRun, $azureActivo, $incluirZips);
        $mostradas = 0;
        $procesadas = 0;

        ActividadFoto::query()
            ->with('actividad')
            ->whereNotNull('foto_path')
            ->where('foto_path', '<>', '')
            ->whereNull('foto_eliminada_at')
            ->orderBy('id')
            ->chunkById(100, function ($fotos) use ($storage, $dryRun, $limit, $mostrar, $incluirZips, $ahora, &$stats, &$mostradas, &$procesadas) {
                foreach ($fotos as $foto) {
                    if ($limit > 0 && $procesadas >= $limit) {
                        return false;
                    }

                    $procesadas++;

                    if (!$foto->actividad) {
                        $stats['errores'][] = 'Foto #' . $foto->id . ' no tiene actividad relacionada.';
                        continue;
                    }

                    $this->procesarFoto(
                        $storage,
                        $foto->actividad,
                        $foto,
                        $dryRun,
                        $incluirZips,
                        $ahora,
                        $stats,
                        $mostradas,
                        $mostrar
                    );
                }

                return true;
            });

        if ($blobColumnsReady) {
            $this->procesarPrincipalesLegacy($storage, $dryRun, $ahora, $stats, $mostradas, $mostrar);
        }

        $this->imprimirStats($stats);

        return empty($stats['errores']) ? self::SUCCESS : self::FAILURE;
    }

    private function procesarFoto(
        ActividadFotoBlobStorage $storage,
        Actividad $actividad,
        ActividadFoto $foto,
        bool $dryRun,
        bool $incluirZips,
        Carbon $ahora,
        array &$stats,
        int &$mostradas,
        int $mostrar
    ): void {
        $dirty = false;
        $originalTarget = $foto->foto_blob_path
            ?: $storage->makeBlobPath($actividad, $foto, (string) $foto->foto_path, 'original');

        $original = $this->copiarReferencia(
            $storage,
            (string) $foto->foto_path,
            $originalTarget,
            'originales',
            $actividad,
            $foto,
            (string) $foto->foto_archivo_zip_path,
            $dryRun,
            $incluirZips,
            $stats,
            $mostradas,
            $mostrar
        );

        if ($this->resultadoTieneBlob($original) && !$dryRun) {
            $foto->foto_blob_path = $originalTarget;
            $foto->foto_blob_copiada_at = $ahora;
            $dirty = true;
        }

        $thumbnailTarget = null;
        $thumbnail = ['status' => 'empty'];

        if (!empty($foto->foto_thumbnail_path)) {
            $thumbnailTarget = $foto->foto_thumbnail_blob_path
                ?: $storage->makeBlobPath($actividad, $foto, (string) $foto->foto_thumbnail_path, 'thumbnail');

            $thumbnail = $this->copiarReferencia(
                $storage,
                (string) $foto->foto_thumbnail_path,
                $thumbnailTarget,
                'thumbnails',
                $actividad,
                $foto,
                null,
                $dryRun,
                false,
                $stats,
                $mostradas,
                $mostrar
            );

            if ($this->resultadoTieneBlob($thumbnail) && !$dryRun) {
                $foto->foto_thumbnail_blob_path = $thumbnailTarget;
                $dirty = true;
            }
        }

        if ($dirty) {
            $foto->save();
        }

        if (!$dryRun) {
            $this->actualizarActividadPrincipal($actividad, $foto, $original, $originalTarget, $thumbnail, $thumbnailTarget, $ahora);
        }
    }

    private function procesarPrincipalesLegacy(
        ActividadFotoBlobStorage $storage,
        bool $dryRun,
        Carbon $ahora,
        array &$stats,
        int &$mostradas,
        int $mostrar
    ): void {
        Actividad::query()
            ->whereNotNull('foto_path')
            ->where('foto_path', '<>', '')
            ->whereNull('foto_blob_path')
            ->whereNull('foto_eliminada_at')
            ->orderBy('id')
            ->chunkById(100, function ($actividades) use ($storage, $dryRun, $ahora, &$stats, &$mostradas, $mostrar) {
                foreach ($actividades as $actividad) {
                    $target = $storage->makeBlobPath($actividad, null, (string) $actividad->foto_path, 'original');
                    $resultado = $this->copiarReferencia(
                        $storage,
                        (string) $actividad->foto_path,
                        $target,
                        'principales_legacy',
                        $actividad,
                        null,
                        (string) $actividad->foto_archivo_zip_path,
                        $dryRun,
                        true,
                        $stats,
                        $mostradas,
                        $mostrar
                    );

                    if ($this->resultadoTieneBlob($resultado) && !$dryRun) {
                        $actividad->foto_blob_path = $target;
                        $actividad->foto_blob_copiada_at = $ahora;
                        $actividad->save();
                    }
                }

                return true;
            });
    }

    private function copiarReferencia(
        ActividadFotoBlobStorage $storage,
        string $sourcePath,
        string $targetPath,
        string $bucket,
        Actividad $actividad,
        ?ActividadFoto $foto,
        ?string $zipPath,
        bool $dryRun,
        bool $incluirZip,
        array &$stats,
        int &$mostradas,
        int $mostrar
    ): array {
        $sourcePath = $storage->normalizeLocalPath($sourcePath);
        $targetPath = $storage->normalizeBlobPath($targetPath);
        $stats[$bucket . '_revisadas']++;

        if ($sourcePath === '' || $targetPath === '') {
            $stats[$bucket . '_faltantes']++;
            return ['status' => 'empty'];
        }

        try {
            if ($storage->usesAzure() && $this->blobExists($storage, $targetPath)) {
                $stats[$bucket . '_ya_en_blob']++;
                return ['status' => 'already', 'target' => $targetPath];
            }

            $disk = Storage::disk('public');

            if ($disk->exists($sourcePath)) {
                if ($dryRun) {
                    $stats[$bucket . '_por_subir']++;
                    $this->mostrarRuta('Subiria local', $sourcePath, $targetPath, $mostradas, $mostrar);
                    return ['status' => 'would_upload_local', 'target' => $targetPath];
                }

                $storage->putPublicFile($sourcePath, $targetPath);
                $this->existsCache[$targetPath] = true;
                $stats[$bucket . '_subidas']++;
                $stats[$bucket . '_desde_local']++;
                return ['status' => 'uploaded_local', 'target' => $targetPath];
            }

            if ($incluirZip && $zipPath) {
                $zipResult = $this->subirDesdeZip($storage, $actividad, $foto, $zipPath, $targetPath, $dryRun);

                if ($zipResult['ok']) {
                    if ($dryRun) {
                        $stats[$bucket . '_por_subir']++;
                        $this->mostrarRuta('Subiria ZIP', $zipPath, $targetPath, $mostradas, $mostrar);
                        return ['status' => 'would_upload_zip', 'target' => $targetPath];
                    }

                    $stats[$bucket . '_subidas']++;
                    $stats[$bucket . '_desde_zip']++;
                    $this->existsCache[$targetPath] = true;
                    return ['status' => 'uploaded_zip', 'target' => $targetPath];
                }
            }

            $stats[$bucket . '_faltantes']++;
            $this->mostrarRuta('Falta', $sourcePath, $targetPath, $mostradas, $mostrar);
            return ['status' => 'missing'];
        } catch (\Throwable $e) {
            $stats['errores'][] = $sourcePath . ': ' . $e->getMessage();
            return ['status' => 'error'];
        }
    }

    private function subirDesdeZip(
        ActividadFotoBlobStorage $storage,
        Actividad $actividad,
        ?ActividadFoto $foto,
        string $zipPath,
        string $targetPath,
        bool $dryRun
    ): array {
        if (!class_exists(ZipArchive::class)) {
            if (!$this->zipWarningShown) {
                $this->zipWarningShown = true;
                $this->warn('ZipArchive no esta disponible; no se pueden recuperar originales desde ZIP.');
            }

            return ['ok' => false];
        }

        $zipPath = $storage->normalizeLocalPath($zipPath);
        $disk = Storage::disk('public');

        if ($zipPath === '' || !$disk->exists($zipPath)) {
            return ['ok' => false];
        }

        $zip = new ZipArchive();

        if ($zip->open($disk->path($zipPath)) !== true) {
            return ['ok' => false];
        }

        try {
            $entry = $this->buscarEntradaZip($zip, $actividad, $foto);

            if (!$entry) {
                return ['ok' => false];
            }

            if ($dryRun) {
                return ['ok' => true];
            }

            $stream = $zip->getStream($entry);

            if ($stream === false) {
                return ['ok' => false];
            }

            try {
                $storage->putStream($targetPath, $stream, $storage->mimeTypeForPath($entry));
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            return ['ok' => true];
        } finally {
            $zip->close();
        }
    }

    private function buscarEntradaZip(ZipArchive $zip, Actividad $actividad, ?ActividadFoto $foto): ?string
    {
        $prefix = 'actividad_' . $actividad->id . '/';

        if ($foto) {
            $prefix .= 'foto_' . $foto->id . '_';
        } else {
            $prefix .= 'principal_';
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if ($name && strpos($name, $prefix) === 0) {
                return $name;
            }
        }

        return null;
    }

    private function actualizarActividadPrincipal(
        Actividad $actividad,
        ActividadFoto $foto,
        array $original,
        string $originalTarget,
        array $thumbnail,
        ?string $thumbnailTarget,
        Carbon $ahora
    ): void {
        $dirty = false;

        if ((string) $actividad->foto_path === (string) $foto->foto_path && $this->resultadoTieneBlob($original)) {
            $actividad->foto_blob_path = $originalTarget;
            $actividad->foto_blob_copiada_at = $ahora;
            $dirty = true;
        }

        if ($thumbnailTarget
            && (string) $actividad->foto_thumbnail_path === (string) $foto->foto_thumbnail_path
            && $this->resultadoTieneBlob($thumbnail)) {
            $actividad->foto_thumbnail_blob_path = $thumbnailTarget;
            $dirty = true;
        }

        if ($dirty) {
            $actividad->save();
        }
    }

    private function blobExists(ActividadFotoBlobStorage $storage, string $targetPath): bool
    {
        if (!array_key_exists($targetPath, $this->existsCache)) {
            $this->existsCache[$targetPath] = $storage->exists($targetPath);
        }

        return $this->existsCache[$targetPath];
    }

    private function resultadoTieneBlob(array $resultado): bool
    {
        return in_array($resultado['status'] ?? '', ['already', 'uploaded_local', 'uploaded_zip'], true);
    }

    private function mostrarRuta(string $label, string $source, string $target, int &$mostradas, int $mostrar): void
    {
        if ($mostradas >= $mostrar) {
            return;
        }

        $this->line($label . ': ' . $source . ' -> fotos/' . ltrim($target, '/'));
        $mostradas++;
    }

    private function imprimirStats(array $stats): void
    {
        $this->info('Migracion de fotos de actividades a Blob');
        $this->line('Modo: ' . ($stats['dry_run'] ? 'DRY-RUN' : 'SUBIR'));
        $this->line('Azure fotos activo: ' . ($stats['azure_activo'] ? 'SI' : 'NO'));
        $this->line('Recuperar desde ZIPs: ' . ($stats['incluir_zips'] ? 'SI' : 'NO'));

        foreach (['originales', 'thumbnails', 'principales_legacy'] as $bucket) {
            $this->line('');
            $this->line(str_replace('_', ' ', ucfirst($bucket)) . ':');
            $this->line('  Revisadas: ' . $stats[$bucket . '_revisadas']);
            $this->line('  Ya en Blob: ' . $stats[$bucket . '_ya_en_blob']);
            $this->line('  Por subir: ' . $stats[$bucket . '_por_subir']);
            $this->line('  Subidas: ' . $stats[$bucket . '_subidas']);
            $this->line('  Desde local: ' . $stats[$bucket . '_desde_local']);
            $this->line('  Desde ZIP: ' . $stats[$bucket . '_desde_zip']);
            $this->line('  Faltantes: ' . $stats[$bucket . '_faltantes']);
        }

        $this->line('');
        $this->line('Errores: ' . count($stats['errores']));

        foreach ($stats['errores'] as $error) {
            $this->error($error);
        }

        if ($stats['dry_run']) {
            $this->warn('Dry-run activo: no se subio ni actualizo nada.');
        }
    }

    private function emptyStats(bool $dryRun, bool $azureActivo, bool $incluirZips): array
    {
        $stats = [
            'dry_run' => $dryRun,
            'azure_activo' => $azureActivo,
            'incluir_zips' => $incluirZips,
            'errores' => [],
        ];

        foreach (['originales', 'thumbnails', 'principales_legacy'] as $bucket) {
            foreach (['revisadas', 'ya_en_blob', 'por_subir', 'subidas', 'desde_local', 'desde_zip', 'faltantes'] as $metric) {
                $stats[$bucket . '_' . $metric] = 0;
            }
        }

        return $stats;
    }

    private function blobColumnsReady(): bool
    {
        return Schema::hasColumn('actividad_fotos', 'foto_blob_path')
            && Schema::hasColumn('actividad_fotos', 'foto_thumbnail_blob_path')
            && Schema::hasColumn('actividad_fotos', 'foto_blob_copiada_at')
            && Schema::hasColumn('actividades', 'foto_blob_path')
            && Schema::hasColumn('actividades', 'foto_thumbnail_blob_path')
            && Schema::hasColumn('actividades', 'foto_blob_copiada_at');
    }
}
