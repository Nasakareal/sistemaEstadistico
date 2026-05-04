<?php

namespace App\Console\Commands;

use App\Models\Actividad;
use App\Models\ActividadFoto;
use App\Services\ImageThumbnailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerarThumbnailsActividades extends Command
{
    protected $signature = 'actividades:generar-thumbnails {--limit=500 : Maximo de fotos a procesar}';

    protected $description = 'Genera miniaturas faltantes para fotos de actividades.';

    public function handle(ImageThumbnailService $thumbnails): int
    {
        @set_time_limit(0);

        $limit = max(1, (int) $this->option('limit'));
        $disk = Storage::disk('public');

        $procesadas = 0;
        $creadas = 0;
        $faltantes = 0;
        $fallidas = 0;

        ActividadFoto::query()
            ->whereNotNull('foto_path')
            ->whereNull('foto_thumbnail_path')
            ->whereNull('foto_eliminada_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->with('actividad')
            ->get()
            ->each(function (ActividadFoto $foto) use ($thumbnails, $disk, &$procesadas, &$creadas, &$faltantes, &$fallidas) {
                $procesadas++;
                $path = trim((string) $foto->foto_path);

                if ($path === '' || !$disk->exists($path)) {
                    $faltantes++;
                    return;
                }

                $actividad = $foto->actividad;
                $unidadId = (int) ($actividad->unidad_org_id ?? 0);
                $fecha = $actividad && $actividad->fecha
                    ? $actividad->fecha->format('Y-m-d')
                    : now('America/Mexico_City')->toDateString();
                $year = substr($fecha, 0, 4) ?: now('America/Mexico_City')->format('Y');
                $directory = 'actividades_thumbnails/unidad_' . $unidadId . '/' . $year;

                $thumbnailPath = $thumbnails->createPublicThumbnail(
                    $path,
                    $directory,
                    'actividad_' . (int) $foto->actividad_id . '_foto_' . (int) $foto->id
                );

                if (!$thumbnailPath) {
                    $fallidas++;
                    return;
                }

                $foto->foto_thumbnail_path = $thumbnailPath;
                $foto->save();

                if ($actividad && (string) $actividad->foto_path === $path) {
                    Actividad::query()
                        ->whereKey($actividad->id)
                        ->update(['foto_thumbnail_path' => $thumbnailPath]);
                }

                $creadas++;
            });

        $this->info('Fotos revisadas: ' . $procesadas);
        $this->info('Thumbnails creados: ' . $creadas);
        $this->info('Archivos no encontrados: ' . $faltantes);
        $this->info('Fallidas: ' . $fallidas);

        return self::SUCCESS;
    }
}
