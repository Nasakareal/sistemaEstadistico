<?php

namespace App\Http\Controllers;

use App\Models\Personal;
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

    public function serviciosPersonal(Request $request)
    {
        $this->ensureCanViewFomentoStats($request);

        $data = $request->validate([
            'fecha_inicio' => ['nullable', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
        ]);

        $tz = 'America/Mexico_City';
        $hoy = now($tz);
        $inicio = Carbon::parse($data['fecha_inicio'] ?? $hoy->copy()->startOfYear()->toDateString(), $tz)->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'] ?? $hoy->toDateString(), $tz)->endOfDay();
        $reporte = $this->serviciosPersonalData($inicio, $fin);

        return view('admin.settings.estadisticas_fomento.servicios_personal.index', [
            'fechaInicio' => $inicio->toDateString(),
            'fechaFin' => $fin->toDateString(),
            'rows' => $reporte['rows'],
            'totales' => $reporte['totales'],
            'servicios' => $reporte['servicios'],
        ]);
    }

    public function personal(Request $request)
    {
        return $this->reportePersonal($request, 'resumen');
    }

    public function rendimientoPersonal(Request $request)
    {
        return $this->reportePersonal($request, 'rendimiento');
    }

    public function incapacidadesPersonal(Request $request)
    {
        return $this->reportePersonal($request, 'incapacidades');
    }

    public function disciplinaPersonal(Request $request)
    {
        return $this->reportePersonal($request, 'disciplina');
    }

    private function reportePersonal(Request $request, string $modo)
    {
        $this->ensureCanViewFomentoStats($request);

        $data = $request->validate([
            'fecha_inicio' => ['nullable', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $tz = 'America/Mexico_City';
        $hoy = now($tz);
        $inicio = Carbon::parse($data['fecha_inicio'] ?? $hoy->copy()->startOfYear()->toDateString(), $tz)->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'] ?? $hoy->toDateString(), $tz)->endOfDay();
        $reporte = $this->estadisticasPersonalData($inicio, $fin);
        $busqueda = mb_strtoupper(trim((string) ($data['q'] ?? '')), 'UTF-8');
        $rows = collect($reporte['rows']);

        if ($busqueda !== '') {
            $rows = $rows->filter(function ($row) use ($busqueda) {
                return str_contains(mb_strtoupper((string) $row['nombre_completo'], 'UTF-8'), $busqueda)
                    || str_contains(mb_strtoupper((string) ($row['numero_empleado'] ?? ''), 'UTF-8'), $busqueda);
            });
        }

        if ($modo === 'incapacidades') {
            $rows = $rows->sort(function ($a, $b) {
                return $b['dias_incapacidad'] <=> $a['dias_incapacidad']
                    ?: $b['incapacidades'] <=> $a['incapacidades']
                    ?: strnatcasecmp($a['nombre_completo'], $b['nombre_completo']);
            });
        } elseif ($modo === 'disciplina') {
            $rows = $rows->sort(function ($a, $b) {
                return $b['incidencias_disciplina'] <=> $a['incidencias_disciplina']
                    ?: strnatcasecmp($a['nombre_completo'], $b['nombre_completo']);
            });
        } elseif ($modo === 'rendimiento') {
            $rows = $rows->sort(function ($a, $b) {
                return $a['total_servicios'] <=> $b['total_servicios']
                    ?: $a['dias_servicio'] <=> $b['dias_servicio']
                    ?: strnatcasecmp($a['nombre_completo'], $b['nombre_completo']);
            });
        }

        return view('admin.settings.estadisticas_fomento.personal.index', [
            'modo' => $modo,
            'fechaInicio' => $inicio->toDateString(),
            'fechaFin' => $fin->toDateString(),
            'busqueda' => $data['q'] ?? '',
            'rows' => $rows->values()->all(),
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

        if ($this->tablaExiste('fomento_municipios_atendidos_historicos')) {
            $aniosHistoricos = DB::table('fomento_municipios_atendidos_historicos')
                ->select('anio')
                ->groupBy('anio')
                ->pluck('anio')
                ->map(fn ($anio) => (int) $anio)
                ->filter()
                ->values()
                ->all();

            $anios = array_values(array_unique(array_merge($anios, $aniosHistoricos)));
            rsort($anios);
        }

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

        $activityMarkersHistoricos = $this->activityMarkersHistoricos($anio);
        $query = $this->baseMunicipiosAtendidosQuery();

        foreach ($activityMarkersHistoricos as $marker) {
            $query->where(function ($scope) use ($marker) {
                $scope->whereNull('actividades.observaciones')
                    ->orWhere('actividades.observaciones', 'not like', '%' . $marker . '%');
            });
        }

        $data = $query
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

        foreach ($this->municipiosHistoricosData($anio) as $item) {
            $municipio = $item->municipio ?: 'NO ESPECIFICADO';
            $mes = (int) $item->mes;
            $eventos = (int) $item->eventos;
            $poblacion = (int) $item->poblacion;

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

    private function estadisticasPersonalData(Carbon $inicio, Carbon $fin): array
    {
        $servicios = $this->serviciosPersonalData($inicio, $fin);
        $rows = collect($servicios['rows'])->keyBy('id');
        $personalIds = $rows->keys()->map(fn ($id) => (int) $id)->all();

        $rows = $rows->map(function ($row) {
            return $row + [
                'incapacidades' => 0,
                'dias_incapacidad' => 0,
                'amonestaciones' => 0,
                'arrestos' => 0,
                'incidencias_disciplina' => 0,
                'balance_servicios' => 'SIN SERVICIOS',
                'diferencia_promedio' => 0,
            ];
        });

        if (!empty($personalIds) && $this->tablaExiste('personal_incidencias') && $this->tablaExiste('incidencia_tipos')) {
            $incidencias = DB::table('personal_incidencias as pi')
                ->join('incidencia_tipos as it', 'it.id', '=', 'pi.incidencia_tipo_id')
                ->whereIn('pi.personal_id', $personalIds)
                ->where('pi.activo', 1)
                ->where(function ($query) {
                    $query->whereRaw("UPPER(TRIM(COALESCE(it.clave, ''))) IN ('INCAPACIDAD', 'AMONESTACION', 'ARRESTO')")
                        ->orWhereRaw("UPPER(TRIM(COALESCE(it.nombre, ''))) IN ('INCAPACIDAD', 'AMONESTACION', 'AMONESTACIÓN', 'ARRESTO')");
                })
                ->whereDate('pi.fecha_inicio', '<=', $fin->toDateString())
                ->where(function ($query) use ($inicio) {
                    $query->whereNull('pi.fecha_fin')
                        ->orWhereDate('pi.fecha_fin', '>=', $inicio->toDateString());
                })
                ->select([
                    'pi.personal_id',
                    'pi.fecha_inicio',
                    'pi.fecha_fin',
                    'it.clave as tipo_clave',
                    'it.nombre as tipo_nombre',
                ])
                ->get();

            foreach ($incidencias as $incidencia) {
                $personalId = (int) $incidencia->personal_id;

                if (!$rows->has($personalId)) {
                    continue;
                }

                $row = $rows->get($personalId);
                $tipo = $this->normalizarTexto((string) ($incidencia->tipo_clave ?: $incidencia->tipo_nombre));

                if ($tipo === 'INCAPACIDAD') {
                    [$dias] = $this->diasIncidenciaServicio($incidencia, $inicio, $fin);
                    $row['incapacidades']++;
                    $row['dias_incapacidad'] += $dias;
                } elseif ($tipo === 'ARRESTO') {
                    $row['arrestos']++;
                    $row['incidencias_disciplina']++;
                } elseif ($tipo === 'AMONESTACION') {
                    $row['amonestaciones']++;
                    $row['incidencias_disciplina']++;
                }

                $rows->put($personalId, $row);
            }
        }

        $activos = $rows->filter(fn ($row) => $this->normalizarTexto($row['estatus'] ?? '') === 'ACTIVO');
        $promedioServicios = $activos->count() > 0 ? round((float) $activos->avg('total_servicios'), 1) : 0.0;
        $minServicios = $activos->count() > 0 ? (int) $activos->min('total_servicios') : 0;
        $maxServicios = $activos->count() > 0 ? (int) $activos->max('total_servicios') : 0;

        $rows = $rows->map(function ($row) use ($promedioServicios, $minServicios, $maxServicios) {
            $servicios = (int) $row['total_servicios'];
            $activo = $this->normalizarTexto($row['estatus'] ?? '') === 'ACTIVO';

            if (!$activo) {
                $balance = 'INACTIVO';
            } elseif ($maxServicios === 0) {
                $balance = 'SIN SERVICIOS';
            } elseif ($minServicios < $maxServicios && $servicios === $minServicios) {
                $balance = 'PRIORIDAD SIGUIENTE';
            } elseif ($servicios > $promedioServicios) {
                $balance = 'POR ENCIMA DEL PROMEDIO';
            } else {
                $balance = 'EQUILIBRADO';
            }

            $row['balance_servicios'] = $balance;
            $row['diferencia_promedio'] = round($servicios - $promedioServicios, 1);

            return $row;
        })->values();

        $totales = $servicios['totales'] + [
            'activos' => $activos->count(),
            'inactivos' => max(0, $rows->count() - $activos->count()),
            'promedio_servicios' => $promedioServicios,
            'incapacidades' => (int) $rows->sum('incapacidades'),
            'dias_incapacidad' => (int) $rows->sum('dias_incapacidad'),
            'amonestaciones' => (int) $rows->sum('amonestaciones'),
            'arrestos' => (int) $rows->sum('arrestos'),
            'incidencias_disciplina' => (int) $rows->sum('incidencias_disciplina'),
        ];

        return ['rows' => $rows->all(), 'totales' => $totales];
    }

    private function serviciosPersonalData(Carbon $inicio, Carbon $fin): array
    {
        $personal = $this->basePersonalFomentoQuery()
            ->leftJoin('turnos', 'turnos.id', '=', 'personals.turno_id')
            ->select([
                'personals.id',
                'personals.numero_empleado',
                'personals.nombre',
                'personals.ap_paterno',
                'personals.ap_materno',
                'personals.grado',
                'personals.puesto',
                'personals.categoria',
                'personals.estatus',
                'turnos.nombre as turno_nombre',
                'turnos.tipo_rol as turno_tipo_rol',
            ])
            ->orderByRaw("CASE WHEN UPPER(TRIM(COALESCE(personals.estatus, ''))) = 'ACTIVO' THEN 0 ELSE 1 END")
            ->orderBy('personals.ap_paterno')
            ->orderBy('personals.ap_materno')
            ->orderBy('personals.nombre')
            ->get();

        $personalIds = $personal->pluck('id')->map(fn ($id) => (int) $id)->all();
        $tipoIds = $this->servicioIncidenciaTipoIds();
        $incidenciasPorPersonal = collect();

        if (!empty($personalIds) && !empty($tipoIds)) {
            $inicioFecha = $inicio->toDateString();
            $finFecha = $fin->toDateString();

            $incidenciasPorPersonal = DB::table('personal_incidencias')
                ->whereIn('personal_id', $personalIds)
                ->whereIn('incidencia_tipo_id', $tipoIds)
                ->where('activo', 1)
                ->where(function ($query) use ($inicioFecha, $finFecha) {
                    $query->where(function ($sinFin) use ($inicioFecha, $finFecha) {
                        $sinFin->whereNull('fecha_fin')
                            ->whereBetween('fecha_inicio', [$inicioFecha, $finFecha]);
                    })->orWhere(function ($conFin) use ($inicioFecha, $finFecha) {
                        $conFin->whereNotNull('fecha_fin')
                            ->whereDate('fecha_inicio', '<=', $finFecha)
                            ->whereDate('fecha_fin', '>=', $inicioFecha);
                    });
                })
                ->select([
                    'personal_id',
                    'fecha_inicio',
                    'fecha_fin',
                    'hora_inicio',
                    'hora_fin',
                    'folio',
                    'motivo',
                    'observaciones',
                ])
                ->orderBy('fecha_inicio')
                ->get()
                ->groupBy('personal_id');
        }

        $rows = $personal->map(function ($item) use ($incidenciasPorPersonal, $inicio, $fin) {
            $incidencias = $incidenciasPorPersonal->get($item->id, collect());
            $diasServicio = 0;
            $diasFinSemana = 0;
            $ultimaFecha = null;

            foreach ($incidencias as $incidencia) {
                [$dias, $finSemana] = $this->diasIncidenciaServicio($incidencia, $inicio, $fin);
                $diasServicio += $dias;
                $diasFinSemana += $finSemana;

                $fechaFin = $incidencia->fecha_fin ?: $incidencia->fecha_inicio;
                if (!$ultimaFecha || $fechaFin > $ultimaFecha) {
                    $ultimaFecha = $fechaFin;
                }
            }

            return [
                'id' => (int) $item->id,
                'numero_empleado' => $item->numero_empleado,
                'nombre_completo' => $this->nombreCompletoPersonal($item),
                'grado' => $item->grado,
                'puesto' => $item->puesto,
                'categoria' => $item->categoria,
                'estatus' => $item->estatus,
                'turno' => $item->turno_nombre,
                'turno_tipo_rol' => $item->turno_tipo_rol,
                'total_servicios' => $incidencias->count(),
                'dias_servicio' => $diasServicio,
                'dias_fin_semana' => $diasFinSemana,
                'ultimo_servicio' => $ultimaFecha,
            ];
        })
            ->sort(function ($a, $b) {
                if ($a['total_servicios'] === $b['total_servicios']) {
                    if ($a['dias_servicio'] === $b['dias_servicio']) {
                        return strnatcasecmp($a['nombre_completo'], $b['nombre_completo']);
                    }

                    return $b['dias_servicio'] <=> $a['dias_servicio'];
                }

                return $b['total_servicios'] <=> $a['total_servicios'];
            })
            ->values()
            ->all();

        $totales = [
            'personal' => count($rows),
            'servicios' => collect($rows)->sum('total_servicios'),
            'dias_servicio' => collect($rows)->sum('dias_servicio'),
            'dias_fin_semana' => collect($rows)->sum('dias_fin_semana'),
        ];

        $personalPorId = collect($rows)->keyBy('id');
        $servicios = $incidenciasPorPersonal
            ->flatten(1)
            ->map(function ($incidencia) use ($personalPorId) {
                $persona = $personalPorId->get((int) $incidencia->personal_id);

                return [
                    'personal_id' => (int) $incidencia->personal_id,
                    'nombre_completo' => $persona['nombre_completo'] ?? 'PERSONAL NO DISPONIBLE',
                    'numero_empleado' => $persona['numero_empleado'] ?? null,
                    'fecha_inicio' => $incidencia->fecha_inicio,
                    'fecha_fin' => $incidencia->fecha_fin,
                    'hora_inicio' => $incidencia->hora_inicio,
                    'hora_fin' => $incidencia->hora_fin,
                    'folio' => $incidencia->folio,
                    'motivo' => $incidencia->motivo,
                    'observaciones' => $incidencia->observaciones,
                ];
            })
            ->sortByDesc('fecha_inicio')
            ->values()
            ->all();

        return compact('rows', 'totales', 'servicios');
    }

    private function basePersonalFomentoQuery()
    {
        $query = DB::table('personals')
            ->where('personals.unidad_id', self::UNIDAD_FOMENTO_ID);

        if ($this->columnaExiste('personals', 'deleted_at')) {
            $query->whereNull('personals.deleted_at');
        }

        return $query;
    }

    private function servicioIncidenciaTipoIds(): array
    {
        if (!$this->tablaExiste('incidencia_tipos')) {
            return [];
        }

        return DB::table('incidencia_tipos')
            ->where(function ($query) {
                $query->whereRaw('UPPER(TRIM(clave)) = ?', ['SERVICIO'])
                    ->orWhereRaw('UPPER(TRIM(nombre)) = ?', ['SERVICIO']);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function diasIncidenciaServicio($incidencia, Carbon $inicio, Carbon $fin): array
    {
        $incInicio = Carbon::parse($incidencia->fecha_inicio, 'America/Mexico_City')->startOfDay();
        $incFin = $incidencia->fecha_fin
            ? Carbon::parse($incidencia->fecha_fin, 'America/Mexico_City')->startOfDay()
            : $incInicio->copy();

        $inicioDia = $inicio->copy()->startOfDay();
        $finDia = $fin->copy()->startOfDay();
        $clipInicio = $incInicio->greaterThan($inicioDia) ? $incInicio : $inicioDia;
        $clipFin = $incFin->lessThan($finDia) ? $incFin : $finDia;

        if ($clipFin->lessThan($clipInicio)) {
            return [0, 0];
        }

        $dias = 0;
        $finSemana = 0;

        for ($cursor = $clipInicio->copy(); $cursor->lte($clipFin); $cursor->addDay()) {
            $dias++;

            if (in_array((int) $cursor->dayOfWeekIso, [6, 7], true)) {
                $finSemana++;
            }
        }

        return [$dias, $finSemana];
    }

    private function nombreCompletoPersonal($personal): string
    {
        return trim(collect([
            $personal->grado,
            Personal::formarNombreCompleto($personal->nombre, $personal->ap_paterno, $personal->ap_materno),
        ])->filter()->implode(' '));
    }

    private function normalizarTexto(?string $texto): string
    {
        $texto = strtr((string) $texto, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U', 'ü' => 'U', 'ñ' => 'N',
        ]);

        return mb_strtoupper(trim($texto), 'UTF-8');
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

    private function activityMarkersHistoricos(int $anio): array
    {
        if (!$this->tablaExiste('fomento_municipios_atendidos_historicos')) {
            return [];
        }

        return DB::table('fomento_municipios_atendidos_historicos')
            ->where('anio', $anio)
            ->whereNotNull('activity_marker')
            ->select('activity_marker')
            ->groupBy('activity_marker')
            ->pluck('activity_marker')
            ->filter()
            ->values()
            ->all();
    }

    private function municipiosHistoricosData(int $anio)
    {
        if (!$this->tablaExiste('fomento_municipios_atendidos_historicos')) {
            return collect();
        }

        return DB::table('fomento_municipios_atendidos_historicos')
            ->where('anio', $anio)
            ->selectRaw('
                mes,
                municipio,
                SUM(eventos) as eventos,
                SUM(poblacion_atendida) as poblacion
            ')
            ->groupBy('mes', 'municipio')
            ->get();
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

    private function columnaExiste(string $table, string $column): bool
    {
        return DB::getSchemaBuilder()->hasColumn($table, $column);
    }
}
