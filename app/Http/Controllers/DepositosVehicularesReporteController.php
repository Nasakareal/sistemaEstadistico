<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

class DepositosVehicularesReporteController extends Controller
{
    private array $columnas = [];

    public function index(Request $request)
    {
        $this->autorizar($request);
        $this->validar($request);

        $perPage = (int) $request->query('per_page', 25);
        $vehiculos = $this->detalle($request)
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('v.id')
            ->paginate($perPage)
            ->appends($request->query());

        
        return view('admin.settings.estadisticas.depositos_vehiculares', [
            'vehiculos' => $vehiculos,
            'resumen' => $this->resumen($request),
            'opciones' => $this->opciones(),
            'filtros' => $request->query(),
        ]);
    }

    public function apiIndex(Request $request)
    {
        $this->autorizar($request);
        $this->validar($request);

        $perPage = (int) $request->query('per_page', 25);
        $vehiculos = $this->detalle($request)
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('v.id')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'data' => $vehiculos->items(),
            'summary' => $this->resumen($request),
            'options' => $this->opciones(),
            'pagination' => [
                'current_page' => $vehiculos->currentPage(),
                'last_page' => $vehiculos->lastPage(),
                'per_page' => $vehiculos->perPage(),
                'total' => $vehiculos->total(),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $this->autorizar($request);
        $this->validar($request, false);

        $nombre = 'depositos_vehiculares_' . now(config('app.timezone', 'America/Mexico_City'))->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($request) {
            $spreadsheet = $this->crearExcel($request);
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        ]);
    }

    private function base(Request $request): Builder
    {
        $ultimosHechos = DB::table('hecho_vehiculo')
            ->select('vehiculo_id')
            ->selectRaw('MAX(hecho_id) as hecho_id')
            ->groupBy('vehiculo_id');

        $ultimasActividades = DB::table('actividad_vehiculo')
            ->select('vehiculo_id')
            ->selectRaw('MAX(actividad_id) as actividad_id')
            ->groupBy('vehiculo_id');

        $ultimasLiberaciones = DB::table('liberaciones')
            ->select('vehiculo_id')
            ->selectRaw('MAX(id) as liberacion_id')
            ->groupBy('vehiculo_id');

        $q = DB::table('vehiculos as v')
            ->leftJoinSub($ultimosHechos, 'hv', 'hv.vehiculo_id', '=', 'v.id')
            ->leftJoinSub($ultimasActividades, 'av', 'av.vehiculo_id', '=', 'v.id')
            ->leftJoinSub($ultimasLiberaciones, 'ul', 'ul.vehiculo_id', '=', 'v.id')
            ->leftJoin('liberaciones as l', 'l.id', '=', 'ul.liberacion_id')
            ->leftJoin('hechos as h', function ($join) {
                $join->on('h.id', '=', DB::raw('COALESCE(l.hecho_id, hv.hecho_id)'));
            })
            ->leftJoin('actividades as a', 'a.id', '=', 'av.actividad_id')
            ->leftJoin('gruas as g', 'g.id', '=', 'v.grua_id')
            ->where(function ($w) {
                $w->whereNotNull('l.id')
                    ->orWhereNotNull('v.fecha_inventario_grua')
                    ->orWhereRaw("TRIM(COALESCE(v.numero_inventario_grua, '')) <> ''")
                    ->orWhereRaw("UPPER(TRIM(COALESCE(v.corralon, ''))) NOT IN ('', 'N/A', 'NA', 'SIN ESPECIFICAR', 'NINGUNO', 'NO APLICA', 'NULL')");
            });

        $fecha = $request->query('fecha_campo', 'ingreso') === 'egreso'
            ? 'l.fecha_liberacion'
            : $this->fechaIngreso();

        if ($request->filled('desde')) {
            $q->whereDate(DB::raw($fecha), '>=', $request->query('desde'));
        }

        if ($request->filled('hasta')) {
            $q->whereDate(DB::raw($fecha), '<=', $request->query('hasta'));
        }

        if ($request->filled('unidad_id')) {
            $q->whereRaw($this->unidad() . ' = ?', [(int) $request->query('unidad_id')]);
        }

        if ($request->filled('origen')) {
            $q->whereRaw($this->origen() . ' = ?', [$request->query('origen')]);
        }

        if ($request->query('estado') === 'liberado') {
            $q->whereNotNull('l.fecha_liberacion');
        } elseif ($request->query('estado') === 'sin_liberacion') {
            $q->whereNull('l.fecha_liberacion');
        }

        foreach (['tipo', 'marca', 'color', 'linea', 'modelo'] as $campo) {
            if ($request->filled($campo)) {
                $q->where("v.{$campo}", $request->query($campo));
            }
        }

        if ($request->filled('municipio')) {
            $q->whereRaw($this->municipio() . ' LIKE ?', ['%' . $request->query('municipio') . '%']);
        }

        if ($request->filled('corralon')) {
            $q->whereRaw($this->corralon() . ' LIKE ?', ['%' . $request->query('corralon') . '%']);
        }

        if ($request->filled('q')) {
            $valor = '%' . trim((string) $request->query('q')) . '%';
            $q->where(function ($w) use ($valor) {
                $w->where('v.placas', 'LIKE', $valor)
                    ->orWhere('v.serie', 'LIKE', $valor)
                    ->orWhere('v.numero_inventario_grua', 'LIKE', $valor)
                    ->orWhere('v.marca', 'LIKE', $valor)
                    ->orWhere('v.linea', 'LIKE', $valor)
                    ->orWhere('l.folio_anual', 'LIKE', $valor)
                    ->orWhere('h.folio_c5i', 'LIKE', $valor);
            });
        }

        return $q;
    }

    private function detalle(Request $request): Builder
    {
        return $this->base($request)->select([
            'v.id as vehiculo_id',
            'v.tipo',
            'v.marca',
            'v.linea',
            'v.modelo',
            'v.color',
            'v.placas',
            'v.serie',
            'v.numero_inventario_grua as inventario',
            'l.id as liberacion_id',
            'l.folio_anual as folio_liberacion',
            'l.fecha_liberacion as fecha_egreso',
            DB::raw($this->fechaIngreso() . ' as fecha_ingreso'),
            DB::raw($this->unidad() . ' as unidad_id'),
            DB::raw($this->municipio() . ' as municipio'),
            DB::raw($this->ubicacion() . ' as ubicacion'),
            DB::raw($this->corralon() . ' as corralon'),
            DB::raw($this->origen() . ' as origen'),
            DB::raw("CASE WHEN l.fecha_liberacion IS NOT NULL THEN 'LIBERADO' ELSE 'SIN LIBERACIÓN REGISTRADA' END as estado"),
        ]);
    }

    private function resumen(Request $request): array
    {
        $row = $this->base($request)->selectRaw(
            'COUNT(*) as total, '
            . 'SUM(CASE WHEN l.fecha_liberacion IS NOT NULL THEN 1 ELSE 0 END) as liberados, '
            . 'SUM(CASE WHEN l.fecha_liberacion IS NULL THEN 1 ELSE 0 END) as sin_liberacion'
        )->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'liberados' => (int) ($row->liberados ?? 0),
            'sin_liberacion' => (int) ($row->sin_liberacion ?? 0),
        ];
    }

    private function opciones(): array
    {
        return [
            'unidades' => $this->opcionesUnidades(),
            'tipos' => DB::table('vehiculos')->whereNotNull('tipo')->distinct()->orderBy('tipo')->pluck('tipo'),
            'marcas' => DB::table('vehiculos')->whereNotNull('marca')->distinct()->orderBy('marca')->pluck('marca'),
            'corralones' => DB::table('vehiculos')->whereNotNull('corralon')->distinct()->orderBy('corralon')->pluck('corralon'),
        ];
    }

    private function opcionesUnidades()
    {
        $unidades = DB::table('hechos')->whereNotNull('unidad_org_id')->distinct()->pluck('unidad_org_id');
        $campo = $this->columna('actividades', 'unidad_org_id')
            ? 'unidad_org_id'
            : ($this->columna('actividades', 'unidad_id') ? 'unidad_id' : null);

        if ($campo !== null) {
            $unidades = $unidades->merge(
                DB::table('actividades')->whereNotNull($campo)->distinct()->pluck($campo)
            );
        }

        
        return DB::table('unidades')
            ->whereIn('id', $unidades->unique()->values()->all())
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    private function fechaIngreso(): string
    {
        $fechaActividad = $this->columna('actividades', 'fecha') ? 'a.fecha' : 'a.created_at';

        return "COALESCE(DATE(h.fecha), DATE({$fechaActividad}), DATE(v.fecha_inventario_grua))";
    }

    private function unidad(): string
    {
        $unidadActividad = $this->columna('actividades', 'unidad_org_id')
            ? 'a.unidad_org_id'
            : ($this->columna('actividades', 'unidad_id') ? 'a.unidad_id' : 'NULL');

        return "COALESCE(h.unidad_org_id, {$unidadActividad})";
    }

    private function municipio(): string
    {
        $municipioActividad = $this->columna('actividades', 'municipio') ? 'a.municipio' : 'NULL';

        return "COALESCE(NULLIF(TRIM(h.municipio), ''), NULLIF(TRIM({$municipioActividad}), ''))";
    }

    private function ubicacion(): string
    {
        $ubicacionActividad = $this->columna('actividades', 'ubicacion')
            ? 'a.ubicacion'
            : ($this->columna('actividades', 'lugar') ? 'a.lugar' : 'NULL');

        return "COALESCE(NULLIF(CONCAT_WS(', ', NULLIF(TRIM(h.calle), ''), NULLIF(TRIM(h.colonia), ''), NULLIF(TRIM(h.municipio), '')), ''), NULLIF(TRIM({$ubicacionActividad}), ''), 'SIN ESPECIFICAR')";
    }

    private function corralon(): string
    {
        return "COALESCE(NULLIF(TRIM(g.nombre), ''), NULLIF(TRIM(v.corralon), ''), 'SIN ESPECIFICAR')";
    }

    private function origen(): string
    {
        return "CASE WHEN l.hecho_id IS NOT NULL OR hv.hecho_id IS NOT NULL THEN 'SINIESTROS' WHEN av.actividad_id IS NOT NULL THEN 'ACTIVIDADES' ELSE 'OTROS' END";
    }

    private function columna(string $tabla, string $columna): bool
    {
        $clave = $tabla . '.' . $columna;

        if (!array_key_exists($clave, $this->columnas)) {
            $this->columnas[$clave] = Schema::hasColumn($tabla, $columna);
        }

        return $this->columnas[$clave];
    }

    private function crearExcel(Request $request): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Sistema Estadístico')
            ->setTitle('Depósitos vehiculares')
            ->setSubject('Ingresos y liberaciones de vehículos');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Depósitos');
        $sheet->setShowGridlines(false);
        $sheet->freezePane('A6');
        $sheet->mergeCells('A2:N2');
        $sheet->setCellValue('A2', 'Depósitos vehiculares - Reporte general');
        $sheet->setCellValue('A3', 'Total de registros');
        $sheet->setCellValue('B3', $this->base($request)->count());
        $sheet->fromArray([
            'Núm.', 'F. ingreso (referencia)', 'Tipo de vehículo', 'Marca', 'Línea', 'Modelo',
            'Placas', 'Serie', 'Ubicación', 'Corralón', 'F. egreso', 'Unidad ID', 'Origen', 'Estado',
        ], null, 'A5');

        $fila = 6;
        foreach ($this->detalle($request)->orderBy('fecha_ingreso')->orderBy('v.id')->cursor() as $v) {
            $sheet->setCellValue("A{$fila}", $fila - 5);
            $this->celdaFecha($sheet, "B{$fila}", $v->fecha_ingreso);
            $campos = [
                'C' => $v->tipo,
                'D' => $v->marca,
                'E' => $v->linea,
                'F' => $v->modelo,
                'G' => $v->placas,
                'H' => $v->serie,
                'I' => $v->ubicacion,
                'J' => $v->corralon,
                'L' => $v->unidad_id,
                'M' => $v->origen,
                'N' => $v->estado,
            ];

            foreach ($campos as $columna => $valor) {
                $sheet->setCellValueExplicit("{$columna}{$fila}", (string) ($valor ?? ''), DataType::TYPE_STRING);
            }

            $this->celdaFecha($sheet, "K{$fila}", $v->fecha_egreso);
            $fila++;
        }

        $ultima = max(5, $fila - 1);
        if ($ultima > 5) {
            $tabla = new Table("A5:N{$ultima}", 'TablaDepositosVehiculares');
            $estilo = new TableStyle(TableStyle::TABLE_STYLE_MEDIUM2);
            $estilo->setShowRowStripes(true);
            $tabla->setStyle($estilo);
            $sheet->addTable($tabla);
        } else {
            $sheet->setAutoFilter('A5:N5');
        }

        $sheet->getStyle('A2:N2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->getStyle('A3:B3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '0F766E']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECFDF5']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'A7F3D0']]],
        ]);
        $sheet->getStyle('A5:N5')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A5:N5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F766E');
        $sheet->getStyle("A5:N{$ultima}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        if ($ultima >= 6) {
            $sheet->getStyle("B6:B{$ultima}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            $sheet->getStyle("K6:K{$ultima}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        }

        foreach (['A' => 8, 'B' => 23, 'C' => 22, 'D' => 20, 'E' => 22, 'F' => 12,
                  'G' => 18, 'H' => 28, 'I' => 55, 'J' => 30, 'K' => 16,
                  'L' => 12, 'M' => 18, 'N' => 30] as $columna => $ancho) {
            $sheet->getColumnDimension($columna)->setWidth($ancho);
        }

        $sheet->getRowDimension(2)->setRowHeight(28);
        $sheet->getRowDimension(5)->setRowHeight(30);
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(5, 5);

        return $spreadsheet;
    }

    
    private function celdaFecha(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $celda, $valor): void
    {
        if ($valor !== null && $valor !== '') {
            $sheet->setCellValue($celda, ExcelDate::PHPToExcel(Carbon::parse($valor)));
        }
    }


    private function autorizar(Request $request): void
    {
        $usuario = $request->user();
        $permitido = $usuario && (
            $usuario->hasRole('Superadmin')
            || $usuario->can('ver estadisticas globales')
            || ($usuario->can('ver configuraciones') && $usuario->can('ver estadisticas'))
        );

        abort_unless($permitido, 403);
    }

    private function validar(Request $request, bool $conPaginacion = true): void
    {
        $reglas = [
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'fecha_campo' => ['nullable', Rule::in(['ingreso', 'egreso'])],
            'unidad_id' => ['nullable', 'integer', 'min:1'],
            'origen' => ['nullable', Rule::in(['SINIESTROS', 'ACTIVIDADES', 'OTROS'])],
            'estado' => ['nullable', Rule::in(['liberado', 'sin_liberacion'])],
            'q' => ['nullable', 'string', 'max:150'],
            'municipio' => ['nullable', 'string', 'max:120'],
            'corralon' => ['nullable', 'string', 'max:120'],
            'tipo' => ['nullable', 'string', 'max:80'],
            'marca' => ['nullable', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:50'],
            'linea' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:20'],
        ];

        if ($conPaginacion) {
            $reglas['per_page'] = ['nullable', Rule::in([25, 50, 100])];
        }

        $request->validate($reglas);
    }
}
