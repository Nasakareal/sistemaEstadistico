<?php

namespace App\Http\Controllers;

use App\Services\Fomento\ExcelFomentoGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EstadisticasFomentoSettingsController extends Controller
{
    private const UNIDAD_FOMENTO_ID = 6;

    public function index(Request $request)
    {
        $this->ensureCanViewFomentoStats($request);

        return view('admin.settings.estadisticas_fomento.index');
    }

    public function excelDiario(Request $request)
    {
        $this->ensureCanViewFomentoStats($request);

        $disk = Storage::disk('local');
        $directorio = 'cortes/excel_fomento';

        if (!$disk->exists($directorio)) {
            $disk->makeDirectory($directorio);
        }

        $cortes = collect($disk->files($directorio))
            ->filter(function ($file) {
                return preg_match('/excel_fomento_\d{4}-\d{2}-\d{2}\.xlsx$/', basename($file));
            })
            ->map(function ($file) {
                $nombre = basename($file);

                preg_match('/excel_fomento_(\d{4}-\d{2}-\d{2})\.xlsx$/', $nombre, $matches);

                return [
                    'archivo' => $nombre,
                    'ruta' => $file,
                    'fecha' => $matches[1] ?? null,
                    'url_descarga' => route('settings.estadisticas_fomento.excel_diario.descargar', $matches[1] ?? null),
                ];
            })
            ->filter(fn ($item) => !empty($item['fecha']))
            ->sortByDesc('fecha')
            ->values();

        return view('admin.settings.estadisticas_fomento.excel_diario.index', compact('cortes'));
    }

    public function generarExcelDiario(Request $request)
    {
        $this->ensureCanViewFomentoStats($request);

        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
        ]);

        $tz = 'America/Mexico_City';
        $fechaCorte = Carbon::parse($data['fecha'], $tz)->format('Y-m-d');
        $tempPath = app(ExcelFomentoGenerator::class)->generar($fechaCorte);

        $directorioDestino = storage_path('app/cortes/excel_fomento');

        if (!File::exists($directorioDestino)) {
            File::makeDirectory($directorioDestino, 0775, true);
        }

        $nombreArchivo = 'excel_fomento_' . $fechaCorte . '.xlsx';
        $rutaDestino = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;

        File::copy($tempPath, $rutaDestino);
        @chmod($rutaDestino, 0664);

        return redirect()
            ->route('settings.estadisticas_fomento.excel_diario')
            ->with('success', 'Excel de Fomento generado para ' . Carbon::parse($fechaCorte)->format('d/m/Y') . '.');
    }

    public function descargarExcelDiario(Request $request, string $fecha)
    {
        $this->ensureCanViewFomentoStats($request);

        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha), 404);

        $nombreArchivo = 'excel_fomento_' . $fecha . '.xlsx';
        $ruta = storage_path('app/cortes/excel_fomento/' . $nombreArchivo);

        abort_unless(file_exists($ruta), 404);

        return response()->download(
            $ruta,
            $nombreArchivo,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function municipiosAtendidos(Request $request)
    {
        $this->ensureCanViewFomentoStats($request);

        $aniosDisponibles = $this->aniosDisponiblesMunicipios();
        $anio = $this->anioSeleccionado($request, $aniosDisponibles);
        $reporte = $this->municipiosAtendidosData($anio);

        return view('admin.settings.estadisticas_fomento.municipios_atendidos.index', [
            'anio' => $anio,
            'aniosDisponibles' => $aniosDisponibles,
            'meses' => $reporte['meses'],
            'rows' => $reporte['rows'],
            'totales' => $reporte['totales'],
        ]);
    }

    public function exportarMunicipiosAtendidos(Request $request): StreamedResponse
    {
        $this->ensureCanViewFomentoStats($request);

        $anio = $this->anioSeleccionado($request, $this->aniosDisponiblesMunicipios());
        $reporte = $this->municipiosAtendidosData($anio);
        $filename = 'fomento_municipios_atendidos_' . $anio . '.xlsx';

        return new StreamedResponse(function () use ($reporte, $anio) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Municipios atendidos');

            $meses = $reporte['meses'];
            $lastColumn = Coordinate::stringFromColumnIndex(1 + (count($meses) * 2) + 2);

            $sheet->mergeCells('A1:' . $lastColumn . '1');
            $sheet->setCellValue('A1', 'UNIDAD DE FOMENTO A LA CULTURA VIAL');
            $sheet->mergeCells('A2:' . $lastColumn . '2');
            $sheet->setCellValue('A2', 'Municipios atendidos ' . $anio);

            $rowHeaderTop = 4;
            $rowHeaderSub = 5;
            $sheet->mergeCells('A' . $rowHeaderTop . ':A' . $rowHeaderSub);
            $sheet->setCellValue('A' . $rowHeaderTop, 'MUNICIPIO');

            $columnIndex = 2;

            foreach ($meses as $mes) {
                $start = Coordinate::stringFromColumnIndex($columnIndex);
                $end = Coordinate::stringFromColumnIndex($columnIndex + 1);
                $sheet->mergeCells($start . $rowHeaderTop . ':' . $end . $rowHeaderTop);
                $sheet->setCellValue($start . $rowHeaderTop, $mes['nombre']);
                $sheet->setCellValue($start . $rowHeaderSub, 'EVENTOS');
                $sheet->setCellValue($end . $rowHeaderSub, 'POB. ATENDIDA');
                $columnIndex += 2;
            }

            $startTotal = Coordinate::stringFromColumnIndex($columnIndex);
            $endTotal = Coordinate::stringFromColumnIndex($columnIndex + 1);
            $sheet->mergeCells($startTotal . $rowHeaderTop . ':' . $endTotal . $rowHeaderTop);
            $sheet->setCellValue($startTotal . $rowHeaderTop, 'TOTAL ANUAL');
            $sheet->setCellValue($startTotal . $rowHeaderSub, 'EVENTOS');
            $sheet->setCellValue($endTotal . $rowHeaderSub, 'POB. ATENDIDA');

            $rowNumber = 6;

            foreach ($reporte['rows'] as $row) {
                $sheet->setCellValue('A' . $rowNumber, $row['municipio']);
                $columnIndex = 2;

                foreach ($meses as $mesNumero => $mes) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex) . $rowNumber, $row['meses'][$mesNumero]['eventos']);
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1) . $rowNumber, $row['meses'][$mesNumero]['poblacion']);
                    $columnIndex += 2;
                }

                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex) . $rowNumber, $row['total_eventos']);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1) . $rowNumber, $row['total_poblacion']);
                $rowNumber++;
            }

            $totalRow = $rowNumber;
            $sheet->setCellValue('A' . $totalRow, 'TOTAL');
            $columnIndex = 2;

            foreach ($meses as $mesNumero => $mes) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex) . $totalRow, $reporte['totales']['meses'][$mesNumero]['eventos']);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1) . $totalRow, $reporte['totales']['meses'][$mesNumero]['poblacion']);
                $columnIndex += 2;
            }

            $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex) . $totalRow, $reporte['totales']['total_eventos']);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1) . $totalRow, $reporte['totales']['total_poblacion']);

            $sheet->getStyle('A1:' . $lastColumn . '2')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $sheet->getStyle('A' . $rowHeaderTop . ':' . $lastColumn . $rowHeaderSub)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9EAF7'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '888888'],
                    ],
                ],
            ]);

            if ($totalRow >= 6) {
                $sheet->getStyle('A6:A' . $totalRow)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFE699'],
                    ],
                ]);

                $sheet->getStyle('A6:' . $lastColumn . $totalRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'B7B7B7'],
                        ],
                    ],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getStyle('B6:' . $lastColumn . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('B6:' . $lastColumn . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('A' . $totalRow . ':' . $lastColumn . $totalRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $totalRow . ':' . $lastColumn . $totalRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('BFBFBF');
            }

            $sheet->freezePane('B6');
            $sheet->getColumnDimension('A')->setWidth(28);

            for ($column = 2; $column <= Coordinate::columnIndexFromString($lastColumn); $column++) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setWidth(13);
            }

            (new Xlsx($spreadsheet))->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function ensureCanViewFomentoStats(Request $request): void
    {
        abort_unless($request->user() && $request->user()->can('menu-estadisticas-actividades-fomento'), 403);
    }

    private function anioSeleccionado(Request $request, array $aniosDisponibles): int
    {
        $anio = (int) $request->query('anio', $aniosDisponibles[0] ?? now('America/Mexico_City')->year);

        if ($anio < 2000 || $anio > 2100) {
            return now('America/Mexico_City')->year;
        }

        return $anio;
    }

    private function aniosDisponiblesMunicipios(): array
    {
        if (!$this->tablaExiste('fomento_cultura_vial_detalles')) {
            return [now('America/Mexico_City')->year];
        }

        $anios = $this->baseMunicipiosAtendidosQuery()
            ->whereNotNull('actividades.fecha')
            ->selectRaw('YEAR(actividades.fecha) as anio')
            ->groupBy('anio')
            ->orderByDesc('anio')
            ->pluck('anio')
            ->map(fn ($anio) => (int) $anio)
            ->filter()
            ->values()
            ->all();

        return $anios ?: [now('America/Mexico_City')->year];
    }

    private function municipiosAtendidosData(int $anio): array
    {
        $meses = $this->mesesReporte();
        $rows = [];
        $totales = [
            'meses' => [],
            'total_eventos' => 0,
            'total_poblacion' => 0,
        ];

        foreach ($meses as $mesNumero => $mes) {
            $totales['meses'][$mesNumero] = [
                'eventos' => 0,
                'poblacion' => 0,
            ];
        }

        if (!$this->tablaExiste('fomento_cultura_vial_detalles')) {
            return compact('meses', 'rows', 'totales');
        }

        $data = $this->baseMunicipiosAtendidosQuery()
            ->whereYear('actividades.fecha', $anio)
            ->selectRaw("
                MONTH(actividades.fecha) as mes,
                COALESCE(NULLIF(TRIM(actividades.municipio), ''), 'NO ESPECIFICADO') as municipio,
                COUNT(DISTINCT actividades.id) as eventos,
                SUM(COALESCE(fomento.total_poblacion_atendida, 0)) as poblacion
            ")
            ->groupBy('mes', 'municipio')
            ->orderBy('municipio')
            ->get();

        foreach ($data as $item) {
            $municipio = $item->municipio ?: 'NO ESPECIFICADO';
            $mes = (int) $item->mes;

            if (!isset($rows[$municipio])) {
                $rows[$municipio] = [
                    'municipio' => $municipio,
                    'meses' => [],
                    'total_eventos' => 0,
                    'total_poblacion' => 0,
                ];

                foreach ($meses as $mesNumero => $mesData) {
                    $rows[$municipio]['meses'][$mesNumero] = [
                        'eventos' => 0,
                        'poblacion' => 0,
                    ];
                }
            }

            $eventos = (int) $item->eventos;
            $poblacion = (int) $item->poblacion;

            $rows[$municipio]['meses'][$mes]['eventos'] += $eventos;
            $rows[$municipio]['meses'][$mes]['poblacion'] += $poblacion;
            $rows[$municipio]['total_eventos'] += $eventos;
            $rows[$municipio]['total_poblacion'] += $poblacion;

            $totales['meses'][$mes]['eventos'] += $eventos;
            $totales['meses'][$mes]['poblacion'] += $poblacion;
            $totales['total_eventos'] += $eventos;
            $totales['total_poblacion'] += $poblacion;
        }

        $rows = collect($rows)
            ->sort(function ($a, $b) {
                if ($a['municipio'] === 'NO ESPECIFICADO') {
                    return 1;
                }

                if ($b['municipio'] === 'NO ESPECIFICADO') {
                    return -1;
                }

                if ($a['total_eventos'] === $b['total_eventos']) {
                    return strnatcasecmp($a['municipio'], $b['municipio']);
                }

                return $b['total_eventos'] <=> $a['total_eventos'];
            })
            ->values()
            ->all();

        return compact('meses', 'rows', 'totales');
    }

    private function baseMunicipiosAtendidosQuery()
    {
        return DB::table('actividades')
            ->join('fomento_cultura_vial_detalles as fomento', 'fomento.actividad_id', '=', 'actividades.id')
            ->leftJoin('users as actividad_creadores', 'actividad_creadores.id', '=', 'actividades.created_by')
            ->where(function ($query) {
                $query->where('actividades.unidad_org_id', self::UNIDAD_FOMENTO_ID)
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('actividades.unidad_org_id')
                            ->where('actividad_creadores.unidad_id', self::UNIDAD_FOMENTO_ID);
                    });
            });
    }

    private function mesesReporte(): array
    {
        return [
            1 => ['nombre' => 'ENERO'],
            2 => ['nombre' => 'FEBRERO'],
            3 => ['nombre' => 'MARZO'],
            4 => ['nombre' => 'ABRIL'],
            5 => ['nombre' => 'MAYO'],
            6 => ['nombre' => 'JUNIO'],
            7 => ['nombre' => 'JULIO'],
            8 => ['nombre' => 'AGOSTO'],
            9 => ['nombre' => 'SEPTIEMBRE'],
            10 => ['nombre' => 'OCTUBRE'],
            11 => ['nombre' => 'NOVIEMBRE'],
            12 => ['nombre' => 'DICIEMBRE'],
        ];
    }

    private function tablaExiste(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
}
