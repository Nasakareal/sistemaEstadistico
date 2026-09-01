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

        $hechos = DB::table('hechos')
            ->leftJoin('delegaciones as d', 'd.id', '=', 'hechos.delegacion_id')
            ->leftJoin('delegaciones as r', 'r.id', '=', 'd.delegacion_padre_id')
            ->select([
                'hechos.id as hecho_id',
                'hechos.fecha',
                'hechos.hora',
                'hechos.folio_c5i',
                'hechos.municipio',
                'hechos.tipo_hecho',
                'hechos.situacion',
                DB::raw("COALESCE(r.nombre, d.nombre, 'SIN REGIONAL') as regional"),
                DB::raw("COALESCE(d.nombre, 'SIN DELEGACION') as delegacion"),
            ])
            ->whereBetween('hechos.fecha', [$inicio, $fin])
            ->where('hechos.unidad_org_id', 2)
            ->whereNotNull('hechos.delegacion_id')
            ->orderBy('hechos.fecha')
            ->orderBy('hechos.hora')
            ->orderBy('hechos.id')
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

        $detalle = $spreadsheet->createSheet();
        $detalle->setTitle('DETALLE_HECHOS');
        $detalle->fromArray([
            'ID_HECHO',
            'FECHA',
            'HORA',
            'REGIONAL',
            'DELEGACION',
            'MUNICIPIO',
            'TIPO_HECHO',
            'SITUACION',
            'FOLIO_C5I',
        ], null, 'A1');

        $filaDetalle = 2;
        foreach ($hechos as $hecho) {
            $detalle->fromArray([
                $hecho->hecho_id,
                $hecho->fecha,
                $hecho->hora ? substr((string) $hecho->hora, 0, 5) : '',
                $hecho->regional,
                $hecho->delegacion,
                $hecho->municipio,
                $hecho->tipo_hecho,
                $hecho->situacion,
                $hecho->folio_c5i,
            ], null, 'A' . $filaDetalle);

            $filaDetalle++;
        }

        $ultimaFilaDetalle = max(1, $filaDetalle - 1);
        $detalle->freezePane('A2');
        $detalle->setAutoFilter('A1:I' . $ultimaFilaDetalle);
        $detalle->getStyle('A1:I1')->getFont()->setBold(true);
        $detalle->getStyle('A1:I1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('D9EAF7');
        $detalle->getStyle('A1:I' . $ultimaFilaDetalle)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range('A', 'I') as $columna) {
            $detalle->getColumnDimension($columna)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $tempPath = storage_path('app/temp_excel_delegaciones_mensual_' . $fechaCorte . '.xlsx');

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        return $tempPath;
    }
}
