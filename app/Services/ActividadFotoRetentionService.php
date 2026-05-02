<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\ActividadFoto;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ActividadFotoRetentionService
{
    public const UNIDAD_SINIESTROS_ID = 1;
    public const DIAS_BORRAR_SINIESTROS = 15;
    public const DIAS_ARCHIVAR_OTRAS_UNIDADES = 7;
    private const THUMB_MAX_WIDTH = 360;
    private const THUMB_JPEG_QUALITY = 35;

    public function procesar(array $opciones = []): array
    {
        @set_time_limit(0);

        $dryRun = (bool) ($opciones['dry_run'] ?? false);
        $unidadSiniestrosId = (int) ($opciones['unidad_siniestros_id'] ?? self::UNIDAD_SINIESTROS_ID);
        $diasBorrar = (int) ($opciones['dias_borrar'] ?? self::DIAS_BORRAR_SINIESTROS);
        $diasArchivar = (int) ($opciones['dias_archivar'] ?? self::DIAS_ARCHIVAR_OTRAS_UNIDADES);
        $marcarFaltantes = (bool) ($opciones['marcar_faltantes'] ?? false);
        $tz = (string) ($opciones['timezone'] ?? config('app.timezone', 'America/Mexico_City'));
        $ahora = Carbon::now($tz);

        $stats = [
            'dry_run' => $dryRun,
            'corte_borrar' => $ahora->copy()->subDays($diasBorrar)->toDateString(),
            'corte_archivar' => $ahora->copy()->subDays($diasArchivar)->toDateString(),
            'fotos_para_borrar' => 0,
            'fotos_borradas' => 0,
            'fotos_faltantes' => 0,
            'fotos_para_archivar' => 0,
            'fotos_archivadas' => 0,
            'zips_creados' => 0,
            'bytes_originales_archivados' => 0,
            'bytes_zip_creados' => 0,
            'thumbnails_creados' => 0,
            'thumbnails_fallidos' => 0,
            'bytes_thumbnails_creados' => 0,
            'errores' => [],
        ];

        $this->borrarFotosSiniestros(
            $unidadSiniestrosId,
            $ahora->copy()->subDays($diasBorrar),
            $ahora,
            $dryRun,
            $marcarFaltantes,
            $stats
        );

        $this->archivarFotosOtrasUnidades(
            $unidadSiniestrosId,
            $ahora->copy()->subDays($diasArchivar),
            $ahora,
            $dryRun,
            $marcarFaltantes,
            $stats
        );

        return $stats;
    }

    private function borrarFotosSiniestros(int $unidadSiniestrosId, Carbon $corte, Carbon $ahora, bool $dryRun, bool $marcarFaltantes, array &$stats): void
    {
        $disk = Storage::disk('public');
        $candidatas = $this->candidatas($unidadSiniestrosId, $corte, true);

        $stats['fotos_para_borrar'] = count($candidatas);

        foreach ($candidatas as $path => $item) {
            if (!$disk->exists($path)) {
                $stats['fotos_faltantes']++;

                if (!$dryRun && $marcarFaltantes) {
                    $this->marcarComoEliminada($path, $ahora);
                }

                continue;
            }

            if ($dryRun) {
                continue;
            }

            $this->deletePdfCacheForOriginal($path);

            if ($disk->delete($path)) {
                $stats['fotos_borradas']++;
                $this->marcarComoEliminada($path, $ahora);
            } else {
                $stats['errores'][] = 'No se pudo borrar ' . $path;
            }
        }
    }

    private function archivarFotosOtrasUnidades(int $unidadSiniestrosId, Carbon $corte, Carbon $ahora, bool $dryRun, bool $marcarFaltantes, array &$stats): void
    {
        if (!class_exists(ZipArchive::class)) {
            $stats['errores'][] = 'La extension ZipArchive no esta disponible en PHP.';
            return;
        }

        $disk = Storage::disk('public');
        $grupos = $this->gruposParaArchivar($unidadSiniestrosId, $corte);

        foreach ($grupos as $grupo) {
            $items = $grupo['items'];
            $stats['fotos_para_archivar'] += count($items);

            $itemsExistentes = [];

            foreach ($items as $path => $item) {
                if ($disk->exists($path)) {
                    $itemsExistentes[$path] = $item;
                    continue;
                }

                $stats['fotos_faltantes']++;

                if (!$dryRun && $marcarFaltantes) {
                    $this->marcarComoEliminada($path, $ahora);
                }
            }

            if (empty($itemsExistentes) || $dryRun) {
                continue;
            }

            $zipPath = $this->zipPath((int) $grupo['unidad_org_id'], (string) $grupo['fecha'], $ahora);
            $resultado = $this->crearZip($zipPath, $itemsExistentes, $disk);

            if (!$resultado['ok']) {
                $stats['errores'][] = $resultado['error'] ?? ('No se pudo crear ' . $zipPath);
                continue;
            }

            foreach (array_keys($itemsExistentes) as $path) {
                $thumbnailPath = $this->crearThumbnail($path, $itemsExistentes[$path], $ahora, $disk);

                if ($thumbnailPath) {
                    $stats['thumbnails_creados']++;

                    if ($disk->exists($thumbnailPath)) {
                        $stats['bytes_thumbnails_creados'] += (int) $disk->size($thumbnailPath);
                    }
                } else {
                    $stats['thumbnails_fallidos']++;
                }

                $this->deletePdfCacheForOriginal($path);

                if (!$disk->delete($path)) {
                    $stats['errores'][] = 'Se archivo, pero no se pudo borrar original ' . $path;
                    continue;
                }

                $this->marcarComoArchivada($path, $zipPath, $thumbnailPath, $ahora);
                $stats['fotos_archivadas']++;
            }

            $stats['zips_creados']++;
            $stats['bytes_originales_archivados'] += (int) $resultado['bytes_originales'];
            $stats['bytes_zip_creados'] += (int) $resultado['bytes_zip'];
        }
    }

    private function candidatas(int $unidadSiniestrosId, Carbon $corte, bool $soloSiniestros): array
    {
        $candidatas = [];

        $query = $this->actividadesConFotosPendientes($unidadSiniestrosId, $corte, $soloSiniestros);

        $query->with(['fotosTodas' => function ($q) {
            $q->whereNotNull('foto_path')
                ->whereNull('foto_archivada_at')
                ->whereNull('foto_eliminada_at')
                ->orderBy('orden')
                ->orderBy('id');
        }])->chunkById(100, function ($actividades) use (&$candidatas) {
            foreach ($actividades as $actividad) {
                $paths = [];

                foreach ($actividad->fotosTodas as $foto) {
                    $path = trim((string) $foto->foto_path);

                    if ($path === '') {
                        continue;
                    }

                    $paths[$path] = true;
                    $candidatas[$path] = $this->itemFoto($actividad, $foto, $path);
                }

                $principal = trim((string) $actividad->foto_path);

                if ($principal !== ''
                    && empty($paths[$principal])
                    && empty($actividad->foto_archivada_at)
                    && empty($actividad->foto_eliminada_at)) {
                    $candidatas[$principal] = $this->itemFoto($actividad, null, $principal);
                }
            }
        });

        return $candidatas;
    }

    private function gruposParaArchivar(int $unidadSiniestrosId, Carbon $corte): array
    {
        $grupos = [];
        $candidatas = $this->candidatas($unidadSiniestrosId, $corte, false);

        foreach ($candidatas as $path => $item) {
            $unidadId = (int) $item['unidad_org_id'];
            $fecha = (string) $item['fecha'];
            $key = $unidadId . '|' . $fecha;

            if (!isset($grupos[$key])) {
                $grupos[$key] = [
                    'unidad_org_id' => $unidadId,
                    'fecha' => $fecha,
                    'items' => [],
                ];
            }

            $grupos[$key]['items'][$path] = $item;
        }

        return $grupos;
    }

    private function actividadesConFotosPendientes(int $unidadSiniestrosId, Carbon $corte, bool $soloSiniestros)
    {
        $corteDate = $corte->toDateString();

        $query = Actividad::query()
            ->where(function ($q) use ($corteDate) {
                $q->whereDate('fecha', '<=', $corteDate)
                    ->orWhere(function ($fallback) use ($corteDate) {
                        $fallback->whereNull('fecha')
                            ->whereDate('created_at', '<=', $corteDate);
                    });
            })
            ->where(function ($q) {
                $q->where(function ($principal) {
                    $principal->whereNotNull('foto_path')
                        ->whereNull('foto_archivada_at')
                        ->whereNull('foto_eliminada_at');
                })->orWhereHas('fotosTodas', function ($fotos) {
                    $fotos->whereNotNull('foto_path')
                        ->whereNull('foto_archivada_at')
                        ->whereNull('foto_eliminada_at');
                });
            });

        if ($soloSiniestros) {
            $query->where('unidad_org_id', $unidadSiniestrosId);
        } else {
            $query->whereNotNull('unidad_org_id')
                ->where('unidad_org_id', '<>', $unidadSiniestrosId);
        }

        return $query;
    }

    private function itemFoto(Actividad $actividad, ?ActividadFoto $foto, string $path): array
    {
        $fecha = $actividad->fecha
            ? Carbon::parse($actividad->fecha)->toDateString()
            : Carbon::parse($actividad->created_at)->toDateString();

        return [
            'actividad_id' => (int) $actividad->id,
            'foto_id' => $foto ? (int) $foto->id : null,
            'unidad_org_id' => (int) $actividad->unidad_org_id,
            'fecha' => $fecha,
            'path' => $path,
            'nombre_original' => $foto
                ? (string) ($foto->foto_nombre_original ?: basename($path))
                : (string) ($actividad->foto_nombre_original ?: basename($path)),
        ];
    }

    private function crearZip(string $zipPath, array $items, $disk): array
    {
        $directorio = trim(str_replace('\\', '/', dirname($zipPath)), './');

        if ($directorio !== '' && !$disk->exists($directorio)) {
            $disk->makeDirectory($directorio);
        }

        $absoluteZip = $disk->path($zipPath);
        $zip = new ZipArchive();
        $openResult = $zip->open($absoluteZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($openResult !== true) {
            return [
                'ok' => false,
                'error' => 'No se pudo abrir el zip ' . $zipPath . ' (codigo ' . $openResult . ').',
            ];
        }

        $bytesOriginales = 0;
        $manifest = $this->manifestCsv($items);

        $zip->addFromString('manifest.csv', $manifest);

        foreach ($items as $path => $item) {
            $absolute = $disk->path($path);

            if (!is_file($absolute)) {
                continue;
            }

            $bytesOriginales += (int) filesize($absolute);
            $entryName = $this->zipEntryName($item, $path);
            $zip->addFile($absolute, $entryName);

            if (method_exists($zip, 'setCompressionName')) {
                $zip->setCompressionName($entryName, ZipArchive::CM_DEFLATE, 9);
            }
        }

        $zip->close();

        if (!is_file($absoluteZip) || filesize($absoluteZip) <= 0) {
            @unlink($absoluteZip);

            return [
                'ok' => false,
                'error' => 'El zip quedo vacio o no se pudo guardar: ' . $zipPath,
            ];
        }

        return [
            'ok' => true,
            'bytes_originales' => $bytesOriginales,
            'bytes_zip' => (int) filesize($absoluteZip),
        ];
    }

    private function crearThumbnail(string $path, array $item, Carbon $ahora, $disk): ?string
    {
        $absolute = $disk->path($path);

        if (!is_file($absolute)) {
            return null;
        }

        $info = @getimagesize($absolute);

        if (!$info || empty($info['mime'])) {
            return null;
        }

        $mime = (string) $info['mime'];

        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            return null;
        }

        $thumbnailPath = $this->thumbnailPath($item, $path, $ahora);
        $directorio = trim(str_replace('\\', '/', dirname($thumbnailPath)), './');

        if ($directorio !== '' && !$disk->exists($directorio)) {
            $disk->makeDirectory($directorio);
        }

        $tmpDir = storage_path('app/tmp');

        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        $tmpOut = $tmpDir . DIRECTORY_SEPARATOR . uniqid('actividad_thumb_', true) . '.jpg';
        $ok = $this->resizeToJpeg($absolute, $tmpOut, self::THUMB_MAX_WIDTH, self::THUMB_JPEG_QUALITY);

        if (!$ok || !is_file($tmpOut)) {
            @unlink($tmpOut);
            return null;
        }

        $contenido = @file_get_contents($tmpOut);
        @unlink($tmpOut);

        if ($contenido === false) {
            return null;
        }

        $disk->put($thumbnailPath, $contenido);

        return $thumbnailPath;
    }

    private function resizeToJpeg(string $src, string $dst, int $maxW, int $quality): bool
    {
        $info = @getimagesize($src);

        if (!$info || empty($info[0]) || empty($info[1]) || empty($info['mime'])) {
            return false;
        }

        $w = (int) $info[0];
        $h = (int) $info[1];
        $mime = (string) $info['mime'];

        if ($w <= 0 || $h <= 0) {
            return false;
        }

        if ($mime === 'image/jpeg') {
            $create = 'imagecreatefromjpeg';
        } elseif ($mime === 'image/png') {
            $create = 'imagecreatefrompng';
        } elseif ($mime === 'image/webp') {
            $create = 'imagecreatefromwebp';
        } elseif ($mime === 'image/gif') {
            $create = 'imagecreatefromgif';
        } else {
            return false;
        }

        if (!function_exists($create)) {
            return false;
        }

        $srcIm = @$create($src);

        if (!$srcIm) {
            return false;
        }

        $newW = $w > $maxW ? $maxW : $w;
        $newH = (int) round($h * ($newW / $w));

        if ($newW <= 0 || $newH <= 0) {
            imagedestroy($srcIm);
            return false;
        }

        $dstIm = imagecreatetruecolor($newW, $newH);

        if (!$dstIm) {
            imagedestroy($srcIm);
            return false;
        }

        $white = imagecolorallocate($dstIm, 255, 255, 255);
        imagefilledrectangle($dstIm, 0, 0, $newW, $newH, $white);
        imagecopyresampled($dstIm, $srcIm, 0, 0, 0, 0, $newW, $newH, $w, $h);

        $saved = imagejpeg($dstIm, $dst, $quality);

        imagedestroy($srcIm);
        imagedestroy($dstIm);

        return (bool) $saved;
    }

    private function manifestCsv(array $items): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'actividad_id',
            'foto_id',
            'unidad_org_id',
            'fecha',
            'foto_nombre_original',
            'foto_path_original',
        ]);

        foreach ($items as $path => $item) {
            fputcsv($handle, [
                $item['actividad_id'],
                $item['foto_id'],
                $item['unidad_org_id'],
                $item['fecha'],
                $item['nombre_original'],
                $path,
            ]);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    private function zipPath(int $unidadId, string $fecha, Carbon $ahora): string
    {
        $year = substr($fecha, 0, 4) ?: $ahora->format('Y');
        $timestamp = $ahora->format('Ymd_His');

        return 'actividades_archivadas/unidad_' . $unidadId . '/' . $year
            . '/actividades_' . $fecha . '_unidad_' . $unidadId . '_' . $timestamp . '.zip';
    }

    private function thumbnailPath(array $item, string $path, Carbon $ahora): string
    {
        $unidadId = (int) $item['unidad_org_id'];
        $fecha = (string) $item['fecha'];
        $year = substr($fecha, 0, 4) ?: $ahora->format('Y');
        $fotoId = $item['foto_id'] ? ('foto_' . $item['foto_id']) : 'principal';
        $baseName = pathinfo($this->sanitizeFileName((string) ($item['nombre_original'] ?: basename($path))), PATHINFO_FILENAME);
        $baseName = $baseName !== '' ? $baseName : 'foto';
        $baseName = substr($baseName, 0, 80);

        return 'actividades_thumbnails/unidad_' . $unidadId . '/' . $year
            . '/actividad_' . $item['actividad_id'] . '_' . $fotoId . '_' . $baseName . '_' . $ahora->format('Ymd_His') . '.jpg';
    }


    private function zipEntryName(array $item, string $path): string
    {
        $fotoId = $item['foto_id'] ? ('foto_' . $item['foto_id']) : 'principal';
        $nombre = $this->sanitizeFileName((string) ($item['nombre_original'] ?: basename($path)));

        return 'actividad_' . $item['actividad_id'] . '/' . $fotoId . '_' . $nombre;
    }

    private function sanitizeFileName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^A-Za-z0-9._ -]+/', '_', $name);
        $name = trim((string) $name, " .\t\n\r\0\x0B");

        return $name !== '' ? $name : 'foto';
    }

    private function marcarComoEliminada(string $path, Carbon $ahora): void
    {
        ActividadFoto::query()
            ->where('foto_path', $path)
            ->whereNull('foto_archivada_at')
            ->whereNull('foto_eliminada_at')
            ->update([
                'foto_eliminada_at' => $ahora,
                'updated_at' => $ahora,
            ]);

        Actividad::query()
            ->where('foto_path', $path)
            ->whereNull('foto_archivada_at')
            ->whereNull('foto_eliminada_at')
            ->update([
                'foto_path' => null,
                'foto_eliminada_at' => $ahora,
                'updated_at' => $ahora,
            ]);
    }

    private function marcarComoArchivada(string $path, string $zipPath, ?string $thumbnailPath, Carbon $ahora): void
    {
        ActividadFoto::query()
            ->where('foto_path', $path)
            ->whereNull('foto_archivada_at')
            ->whereNull('foto_eliminada_at')
            ->update([
                'foto_thumbnail_path' => $thumbnailPath,
                'foto_archivo_zip_path' => $zipPath,
                'foto_archivada_at' => $ahora,
                'updated_at' => $ahora,
            ]);

        Actividad::query()
            ->where('foto_path', $path)
            ->whereNull('foto_archivada_at')
            ->whereNull('foto_eliminada_at')
            ->update([
                'foto_path' => null,
                'foto_thumbnail_path' => $thumbnailPath,
                'foto_archivo_zip_path' => $zipPath,
                'foto_archivada_at' => $ahora,
                'updated_at' => $ahora,
            ]);
    }

    private function deletePdfCacheForOriginal(string $fotoPath): void
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($fotoPath)) {
            return;
        }

        $absolute = $disk->path($fotoPath);

        if (!is_file($absolute)) {
            return;
        }

        $hash = @hash_file('sha1', $absolute);

        if (!$hash) {
            return;
        }

        $cacheRel = 'actividades/pdf_cache/' . $hash . '.jpg';

        if ($disk->exists($cacheRel)) {
            $disk->delete($cacheRel);
        }
    }
}
