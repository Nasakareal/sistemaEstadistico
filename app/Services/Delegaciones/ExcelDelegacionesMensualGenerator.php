<?php

namespace App\Services\Delegaciones;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelDelegacionesMensualGenerator
{
    public function generar(string $fechaCorte): string
    {
        $tz = 'America/Mexico_City';

        $fecha = Carbon::parse($fechaCorte . '-01', $tz);
        $inicio = $fecha->copy()->startOfMonth()->toDateString();
        $fin = $fecha->copy()->endOfMonth()->toDateString();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('INEGI');

        $datos = DB::table('hechos')
            ->leftJoin('delegaciones as d', 'd.id', '=', 'hechos.delegacion_id')
            ->leftJoin('delegaciones as r', 'r.id', '=', 'd.delegacion_padre_id')
            ->selectRaw("
                COALESCE(r.nombre, d.nombre, 'SIN REGIONAL') as regional,
                COALESCE(d.nombre, 'SIN DELEGACION') as delegacion,
                COUNT(*) as siniestros,
                COUNT(*) as total
            ")
            ->whereBetween('hechos.fecha', [$inicio, $fin])
            ->where('hechos.unidad_org_id', 2)
            ->whereNotNull('hechos.delegacion_id')
            ->groupBy('regional', 'delegacion')
            ->orderBy('regional')
            ->orderBy('delegacion')
            ->get();

        $sheet->setCellValue('A1', 'Cuenta de SINIESTRO');
        $sheet->setCellValue('B1', 'Etiquetas de columna');
        $sheet->setCellValue('A2', 'Etiquetas de fila');
        $sheet->setCellValue('B2', 'SINIESTRO');
        $sheet->setCellValue('C2', 'Total general');

        $fila = 3;

        foreach ($datos as $item) {
            $nombreFila = mb_strtoupper($item->regional, 'UTF-8') . ' - ' . mb_strtoupper($item->delegacion, 'UTF-8');

            $sheet->setCellValue('A' . $fila, $nombreFila);
            $sheet->setCellValue('B' . $fila, (int) $item->siniestros);
            $sheet->setCellValue('C' . $fila, (int) $item->total);

            $fila++;
        }

        $sheet->setCellValue('A' . $fila, 'Total general');
        $sheet->setCellValue('B' . $fila, '=SUM(B3:B' . ($fila - 1) . ')');
        $sheet->setCellValue('C' . $fila, '=SUM(C3:C' . ($fila - 1) . ')');

        $sheet->getStyle('A1:C2')->getFont()->setBold(true);
        $sheet->getStyle('A' . $fila . ':C' . $fila)->getFont()->setBold(true);

        $sheet->getStyle('A1:C2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('D9EAF7');
        $sheet->getStyle('A' . $fila . ':C' . $fila)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('D9EAF7');

        $sheet->getStyle('A1:C' . $fila)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('B3:C' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach (range('A', 'C') as $columna) {
            $sheet->getColumnDimension($columna)->setAutoSize(true);
        }

        $tempPath = storage_path('app/temp_excel_delegaciones_mensual_' . $fechaCorte . '.xlsx');

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        return $tempPath;
    }
}
