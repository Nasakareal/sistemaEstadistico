<?php

namespace App\Services;

use App\Models\Actividad;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ActividadInformeService
{
    private const UNIDAD_SINIESTROS_ID = 1;

    public function generarYGuardarEnCortes(string $fecha, Request $request): string
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $tz = 'America/Mexico_City';

        try {
            $fechaSeleccionada = Carbon::createFromFormat('Y-m-d', $fecha, $tz)->toDateString();
        } catch (\Throwable $e) {
            throw new RuntimeException('La fecha proporcionada no es válida.');
        }

        $inicioDia = Carbon::parse($fechaSeleccionada, $tz)->startOfDay();
        $finDia = Carbon::parse($fechaSeleccionada, $tz)->endOfDay();

        $actividades = $this->buildQuery($request, $inicioDia, $finDia)->get();

        $actividades->transform(function ($a) {
            $rel = $this->getOrCreatePdfImage($a->foto_path ?: $a->foto_thumbnail_path);
            $a->foto_pdf_path = $rel;
            $a->foto_pdf_abs = $rel ? public_path('storage/' . ltrim($rel, '/')) : null;
            return $a;
        });

        $pdf = Pdf::loadView('actividades.informe', [
            'actividades' => $actividades,
            'fechaSeleccionada' => $fechaSeleccionada,
            'tz' => $tz,
        ])->setPaper('letter', 'portrait')
          ->setOptions([
              'dpi' => 96,
              'defaultFont' => 'DejaVu Sans',
              'isRemoteEnabled' => true,
              'chroot' => base_path(),
          ]);

        $disk = Storage::disk('local');
        $directorio = 'cortes/actividades';

        if (!$disk->exists($directorio)) {
            $disk->makeDirectory($directorio);
        }

        $nombreArchivo = 'actividades_' . $fechaSeleccionada . '.pdf';
        $disk->put($directorio . '/' . $nombreArchivo, $pdf->output());

        return $nombreArchivo;
    }

    private function buildQuery(Request $request, Carbon $inicioDia, Carbon $finDia)
    {
        $query = Actividad::query()
            ->with(['categoria', 'subcategoria', 'unidad', 'delegacion', 'destacamento'])
            ->where('unidad_org_id', self::UNIDAD_SINIESTROS_ID)
            ->whereBetween('fecha', [$inicioDia->toDateString(), $finDia->toDateString()])
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->orderByDesc('id');

        if ($request->filled('actividad_categoria_id')) {
            $query->where('actividad_categoria_id', (int) $request->actividad_categoria_id);
        }

        if ($request->filled('actividad_subcategoria_id')) {
            $query->where('actividad_subcategoria_id', (int) $request->actividad_subcategoria_id);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', "%{$q}%")
                    ->orWhere('lugar', 'like', "%{$q}%")
                    ->orWhere('municipio', 'like', "%{$q}%")
                    ->orWhere('carretera', 'like', "%{$q}%")
                    ->orWhere('tramo', 'like', "%{$q}%")
                    ->orWhere('motivo', 'like', "%{$q}%")
                    ->orWhere('narrativa', 'like', "%{$q}%")
                    ->orWhere('elementos_participantes_texto', 'like', "%{$q}%")
                    ->orWhere('patrullas_participantes_texto', 'like', "%{$q}%");
            });
        }

        return $query;
    }

    private function getOrCreatePdfImage(?string $fotoPath): ?string
    {
        if (!$fotoPath) {
            return null;
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($fotoPath)) {
            return null;
        }

        $absOriginal = public_path('storage/' . ltrim($fotoPath, '/'));

        if (!is_file($absOriginal)) {
            return null;
        }

        $extension = strtolower((string) pathinfo($absOriginal, PATHINFO_EXTENSION));

        if ($extension === 'heic' || $extension === 'heif') {
            return $fotoPath;
        }

        $hash = @hash_file('sha1', $absOriginal);

        if (!$hash) {
            return $fotoPath;
        }

        $cacheRel = 'actividades/pdf_cache/' . $hash . '.jpg';

        if ($disk->exists($cacheRel)) {
            return $cacheRel;
        }

        $tmpDir = storage_path('app/tmp');

        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        $tmpOut = $tmpDir . DIRECTORY_SEPARATOR . $hash . '.jpg';

        $ok = $this->resizeToJpeg($absOriginal, $tmpOut, 1280, 75);

        if (!$ok || !is_file($tmpOut)) {
            return $fotoPath;
        }

        $contenido = @file_get_contents($tmpOut);

        if ($contenido === false) {
            @unlink($tmpOut);
            return $fotoPath;
        }

        $disk->put($cacheRel, $contenido);
        @unlink($tmpOut);

        return $cacheRel;
    }

    private function resizeToJpeg(string $src, string $dst, int $maxW, int $quality): bool
    {
        $info = @getimagesize($src);

        if (!$info || empty($info[0]) || empty($info[1])) {
            return false;
        }

        $w = (int) $info[0];
        $h = (int) $info[1];
        $mime = isset($info['mime']) ? (string) $info['mime'] : '';

        if ($w <= 0 || $h <= 0) {
            return false;
        }

        $create = null;

        if ($mime === 'image/jpeg') {
            $create = 'imagecreatefromjpeg';
        } elseif ($mime === 'image/png') {
            $create = 'imagecreatefrompng';
        } elseif ($mime === 'image/webp') {
            $create = 'imagecreatefromwebp';
        } elseif ($mime === 'image/gif') {
            $create = 'imagecreatefromgif';
        }

        if (!$create || !function_exists($create)) {
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

        if ($mime === 'image/png' || $mime === 'image/gif' || $mime === 'image/webp') {
            $white = imagecolorallocate($dstIm, 255, 255, 255);
            imagefilledrectangle($dstIm, 0, 0, $newW, $newH, $white);
        }

        imagecopyresampled($dstIm, $srcIm, 0, 0, 0, 0, $newW, $newH, $w, $h);

        $saved = imagejpeg($dstIm, $dst, $quality);

        imagedestroy($srcIm);
        imagedestroy($dstIm);

        return (bool) $saved;
    }
}
