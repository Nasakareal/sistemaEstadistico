<?php

namespace App\Http\Controllers;

use App\Services\ConduceLegalidadVehiculosReporteService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ConduceLegalidadVehiculosReporteController extends Controller
{
    public function index(Request $request, ConduceLegalidadVehiculosReporteService $reporte)
    {
        $this->autorizar($request);
        $this->validar($request);

        $perPage = (int) $request->query('per_page', 25);
        $vehiculos = $reporte->query($request)
            ->orderByDesc('fecha_efectiva')
            ->orderByDesc('v.id')
            ->paginate($perPage)
            ->appends($request->query());

        $vehiculos->setCollection($reporte->enriquecer($vehiculos->getCollection()));

        return view('reportes.conduce_legalidad_vehiculos', [
            'vehiculos' => $vehiculos,
            'resumen' => $reporte->resumen($request),
            'opciones' => $reporte->opciones(),
        ]);
    }

    public function export(Request $request, ConduceLegalidadVehiculosReporteService $reporte)
    {
        $this->autorizar($request);
        $this->validar($request, false);

        $vehiculos = $reporte->enriquecer(
            $reporte->query($request)
                ->orderBy('fecha_efectiva')
                ->orderBy('v.id')
                ->get()
        );

        $fecha = now(config('app.timezone', 'America/Mexico_City'))->format('Y-m-d');
        $nombre = "vehiculos_conduce_con_legalidad_{$fecha}.xlsx";

        return response()->streamDownload(function () use ($vehiculos) {
            $spreadsheet = $this->crearExcel($vehiculos);
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        ]);
    }

    private function crearExcel($vehiculos): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Sistema Estadístico')
            ->setTitle('Vehículos de Conduce con Legalidad')
            ->setSubject('Operativos de Conduce con Legalidad');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Vehículos');
        $sheet->setShowGridlines(false);
        $sheet->freezePane('A6');

        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', 'Vehículos de Conduce con Legalidad');
        $sheet->setCellValue('A3', 'Total de vehículos');
        $sheet->setCellValue('B3', $vehiculos->count());
        $sheet->fromArray(['Fecha', 'Lugar', 'Datos del vehículo', 'Motivo'], null, 'A5');

        $row = 6;
        foreach ($vehiculos as $vehiculo) {
            if (!empty($vehiculo->fecha_reporte)) {
                $sheet->setCellValue("A{$row}", ExcelDate::PHPToExcel(Carbon::parse($vehiculo->fecha_reporte)));
            }

            $sheet->setCellValueExplicit("B{$row}", (string) $vehiculo->lugar_reporte, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$row}", (string) $vehiculo->datos_vehiculo, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$row}", (string) $vehiculo->motivo_reporte, DataType::TYPE_STRING);
            $row++;
        }

        $lastRow = max(5, $row - 1);
        if ($lastRow >= 6) {
            $table = new Table("A5:D{$lastRow}", 'VehiculosConduceLegalidad');
            $style = new TableStyle(TableStyle::TABLE_STYLE_MEDIUM2);
            $style->setShowRowStripes(true);
            $table->setStyle($style);
            $sheet->addTable($table);
        } else {
            $sheet->setAutoFilter('A5:D5');
        }

        $sheet->getStyle('A2:D2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->getStyle('A3:B3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '0F766E']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECFDF5']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'A7F3D0']]],
        ]);
        $sheet->getStyle("A5:D{$lastRow}")->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(true);
        $sheet->getStyle('A5:D5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A5:D5')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A5:D5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F766E');
        $sheet->getStyle("A6:A{$lastRow}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');

        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(42);
        $sheet->getColumnDimension('C')->setWidth(74);
        $sheet->getColumnDimension('D')->setWidth(74);
        $sheet->getRowDimension(2)->setRowHeight(28);
        $sheet->getRowDimension(5)->setRowHeight(24);
        for ($dataRow = 6; $dataRow <= $lastRow; $dataRow++) {
            $sheet->getRowDimension($dataRow)->setRowHeight(42);
        }

        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.4)->setRight(0.3)->setBottom(0.4)->setLeft(0.3);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(5, 5);

        return $spreadsheet;
    }

    private function autorizar(Request $request): void
    {
        $user = $request->user();
        $permitido = $user && (
            $user->hasRole('Superadmin')
            || $user->can('ver estadisticas globales')
            || ($user->can('ver configuraciones') && $user->can('ver estadisticas'))
        );

        abort_unless($permitido, 403);
    }

    private function validar(Request $request, bool $conPaginacion = true): void
    {
        $rules = [
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'q' => ['nullable', 'string', 'max:150'],
            'municipio' => ['nullable', 'string', 'max:120'],
            'marca' => ['nullable', 'string', 'max:80'],
            'tipo' => ['nullable', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:50'],
            'linea' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:20'],
            'placas' => ['nullable', 'string', 'max:20'],
            'serie' => ['nullable', 'string', 'max:30'],
            'inventario' => ['nullable', 'string', 'max:100'],
            'motivo' => ['nullable', 'string', 'max:150'],
            'retenido' => ['nullable', Rule::in(['0', '1'])],
        ];

        if ($conPaginacion) {
            $rules['per_page'] = ['nullable', Rule::in(['25', '50', '100', 25, 50, 100])];
        }

        $request->validate($rules);
    }
}
