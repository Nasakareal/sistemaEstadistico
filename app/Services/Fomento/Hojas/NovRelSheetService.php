<?php

namespace App\Services\Fomento\Hojas;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NovRelSheetService extends BaseFomentoSheetService
{
    public function generar(Worksheet $sheet, Collection $actividades, string $fecha, Carbon $inicio, Carbon $fin): void
    {
        $sheet->getSheetView()->setZoomScale(90);
        $this->aplicarFormatoBase($sheet);

        $fotosPorActividad = $this->fotosPorActividad($actividades);
        $fila = 2;
        $numero = 1;

        foreach ($actividades as $actividad) {
            $fotos = $this->rutasFotosActividad($actividad, $fotosPorActividad);

            $this->aplicarEstiloFila($sheet, $fila);

            $sheet->setCellValue('A' . $fila, $numero);
            $sheet->setCellValue('B' . $fila, $this->mayusculas($this->lugarActividad($actividad)));
            $sheet->setCellValue('C' . $fila, $this->mayusculas($this->textoActividad($actividad)));

            foreach (array_slice($fotos, 0, 2) as $index => $foto) {
                $this->insertarImagen($sheet, $foto, $index === 0 ? 'D' : 'E', $fila);
            }

            $fila++;
            $numero++;
        }

        if ($numero === 1) {
            $this->aplicarEstiloFila($sheet, $fila);
            $sheet->mergeCells('B' . $fila . ':E' . $fila);
            $sheet->setCellValue('A' . $fila, '-');
            $sheet->setCellValue('B' . $fila, 'SIN NOVEDADES RELEVANTES EN EL PERIODO.');
        }

        $sheet->freezePane('A2');
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
    }

    private function aplicarFormatoBase(Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'N°.');
        $sheet->setCellValue('B1', 'LUGAR');
        $sheet->setCellValue('C1', 'ACTIVIDAD');
        $sheet->setCellValue('D1', 'GRAFICA 1');
        $sheet->setCellValue('E1', 'GRAFICA 2');

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(42);
        $sheet->getColumnDimension('D')->setWidth(38);
        $sheet->getColumnDimension('E')->setWidth(38);
        $sheet->getRowDimension(1)->setRowHeight(16);

        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '000000']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFFFF'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->setAutoFilter('A1:E1');
    }

    private function aplicarEstiloFila(Worksheet $sheet, int $fila): void
    {
        $sheet->getRowDimension($fila)->setRowHeight(195);
        $sheet->getStyle('A' . $fila . ':E' . $fila)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '000000']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }

    private function fotosPorActividad(Collection $actividades): Collection
    {
        $ids = $actividades
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return DB::table('actividad_fotos')
            ->whereIn('actividad_id', $ids->all())
            ->whereNull('foto_eliminada_at')
            ->where(function ($query) {
                $query->whereNull('foto_archivada_at')
                    ->orWhereNotNull('foto_thumbnail_path');
            })
            ->orderBy('orden')
            ->orderBy('id')
            ->get(['actividad_id', 'foto_path', 'foto_thumbnail_path'])
            ->groupBy('actividad_id');
    }

    private function rutasFotosActividad($actividad, Collection $fotosPorActividad): array
    {
        $rutas = [];

        foreach (($fotosPorActividad[(int) $actividad->id] ?? collect()) as $foto) {
            $ruta = $foto->foto_thumbnail_path ?: $foto->foto_path;

            if ($ruta) {
                $rutas[] = $ruta;
            }
        }

        foreach ([$actividad->foto_thumbnail_path ?? null, $actividad->foto_path ?? null] as $ruta) {
            if ($ruta) {
                $rutas[] = $ruta;
            }
        }

        return array_values(array_unique(array_filter($rutas)));
    }

    private function lugarActividad($actividad): string
    {
        foreach ([$actividad->municipio ?? null, $actividad->lugar ?? null] as $valor) {
            $valor = trim((string) $valor);

            if ($valor !== '') {
                return $valor;
            }
        }

        return 'NO ESPECIFICADO';
    }

    private function textoActividad($actividad): string
    {
        $texto = trim(implode(' ', array_filter([
            trim((string) ($actividad->acciones_realizadas ?? '')),
            trim((string) ($actividad->narrativa ?? '')),
            trim((string) ($actividad->motivo ?? '')),
        ])));

        if ($texto !== '') {
            return $texto;
        }

        foreach ([
            $actividad->programa_nombre ?? null,
            $actividad->subcategoria_nombre ?? null,
            $actividad->actividad_nombre ?? null,
        ] as $valor) {
            $valor = trim((string) $valor);

            if ($valor !== '') {
                return $valor;
            }
        }

        return 'ACTIVIDAD DE FOMENTO A LA CULTURA VIAL';
    }

    private function insertarImagen(Worksheet $sheet, string $ruta, string $columna, int $fila): void
    {
        $path = $this->resolverRutaImagen($ruta);

        if (!$path) {
            return;
        }

        $path = $this->rutaCompatibleExcel($path);

        if (!$path) {
            return;
        }

        $drawing = new Drawing();
        $drawing->setPath($path);
        $drawing->setCoordinates($columna . $fila);
        $drawing->setOffsetX(10);
        $drawing->setOffsetY(34);
        $drawing->setResizeProportional(true);
        $drawing->setWidthAndHeight(215, 128);
        $drawing->setWorksheet($sheet);
    }

    private function resolverRutaImagen(string $ruta): ?string
    {
        $ruta = ltrim($ruta, '/');

        foreach ([
            storage_path('app/public/' . $ruta),
            storage_path('app/' . $ruta),
            public_path('storage/' . $ruta),
            public_path($ruta),
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function rutaCompatibleExcel(string $path): ?string
    {
        $tipo = @exif_imagetype($path);

        $tiposCompatibles = array_filter([
            defined('IMAGETYPE_GIF') ? IMAGETYPE_GIF : null,
            defined('IMAGETYPE_JPEG') ? IMAGETYPE_JPEG : null,
            defined('IMAGETYPE_PNG') ? IMAGETYPE_PNG : null,
            defined('IMAGETYPE_BMP') ? IMAGETYPE_BMP : null,
        ]);

        if (in_array($tipo, $tiposCompatibles, true)) {
            return $path;
        }

        if (!defined('IMAGETYPE_WEBP') || $tipo !== IMAGETYPE_WEBP || !function_exists('imagecreatefromwebp')) {
            return null;
        }

        $directorio = storage_path('app/temp_fomento_nov_rel');

        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        $destino = $directorio . DIRECTORY_SEPARATOR . md5($path . filemtime($path)) . '.jpg';

        if (is_file($destino)) {
            return $destino;
        }

        $origen = @imagecreatefromwebp($path);

        if (!$origen) {
            return null;
        }

        $guardado = imagejpeg($origen, $destino, 88);
        imagedestroy($origen);

        return $guardado ? $destino : null;
    }

    private function mayusculas(string $texto): string
    {
        $texto = trim(preg_replace('/\s+/', ' ', $texto) ?? '');

        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($texto, 'UTF-8');
        }

        return strtoupper($texto);
    }
}
