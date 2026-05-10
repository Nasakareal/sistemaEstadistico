<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Hechos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BackupsSqlController extends Controller
{
    private const UNIDAD_DELEGACIONES_ID = 2;

    public function index()
    {
        $dir = 'backups_sql';

        if (!Storage::disk('local')->exists($dir)) {
            Storage::disk('local')->makeDirectory($dir);
        }

        $files = collect(Storage::disk('local')->files($dir))
            ->filter(function ($path) {
                $name = basename($path);
                return (bool) preg_match('/^[A-Za-z0-9._-]+\.sql(\.gz)?$/', $name);
            })
            ->map(function ($path) {
                $disk = Storage::disk('local');
                return [
                    'name' => basename($path),
                    'path' => $path,
                    'size' => $disk->size($path),
                    'last_modified' => $disk->lastModified($path),
                ];
            })
            ->sortByDesc('last_modified')
            ->values();

        return view('admin.settings.backups_sql.index', compact('files'));
    }

    public function download(string $file)
    {
        if (!preg_match('/^[A-Za-z0-9._-]+\.sql(\.gz)?$/', $file)) {
            abort(404);
        }

        $path = 'backups_sql/' . $file;

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $absolutePath = Storage::disk('local')->path($path);

        return response()->download($absolutePath, $file, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    public function downloadDelegaciones(Request $request)
    {
        $this->ensureCanDownloadDelegacionesBackup($request);

        [$fechaInicio, $fechaFin] = $this->defaultDelegacionesReportDates();

        return view('admin.settings.backups_sql.delegaciones_reporte', compact('fechaInicio', 'fechaFin'));
    }

    public function downloadDelegacionesExcel(Request $request)
    {
        $this->ensureCanDownloadDelegacionesBackup($request);

        $data = $request->validate([
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
        ]);

        $reporte = $this->buildDelegacionesReportData($data['fecha_inicio'], $data['fecha_fin']);

        return $this->downloadDelegacionesExcelFile($reporte);
    }

    private function defaultDelegacionesReportDates(): array
    {
        $today = Carbon::now('America/Mexico_City');

        return [
            $today->copy()->subDays(6)->toDateString(),
            $today->toDateString(),
        ];
    }

    private function buildDelegacionesReportData(string $fechaInicio, string $fechaFin): array
    {
        $tz = 'America/Mexico_City';
        $inicio = Carbon::createFromFormat('Y-m-d', $fechaInicio, $tz)->startOfDay();
        $fin = Carbon::createFromFormat('Y-m-d', $fechaFin, $tz)->endOfDay();

        $actividades = Actividad::query()
            ->with(['categoria', 'subcategoria', 'delegacion', 'creador'])
            ->withCount('vehiculos')
            ->where('unidad_org_id', self::UNIDAD_DELEGACIONES_ID)
            ->whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->orderBy('fecha')
            ->orderBy('hora')
            ->orderBy('id')
            ->get();

        $hechos = Hechos::query()
            ->with(['delegacion', 'creator'])
            ->withCount(['vehiculos', 'lesionados'])
            ->where('unidad_org_id', self::UNIDAD_DELEGACIONES_ID)
            ->whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->orderBy('fecha')
            ->orderBy('hora')
            ->orderBy('id')
            ->get();

        return [
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'inicio' => $inicio,
            'fin' => $fin,
            'generadoEn' => Carbon::now($tz),
            'actividades' => $actividades,
            'hechos' => $hechos,
            'resumen' => [
                'actividades_total' => $actividades->count(),
                'hechos_total' => $hechos->count(),
                'hechos_pendientes' => $hechos->where('situacion', 'PENDIENTE')->count(),
                'hechos_turnados' => $hechos->where('situacion', 'TURNADO')->count(),
                'hechos_resueltos' => $hechos->where('situacion', 'RESUELTO')->count(),
            ],
        ];
    }

    private function downloadDelegacionesExcelFile(array $reporte)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Sistema Estadistico')
            ->setTitle('Reporte Delegaciones');

        $actividadesSheet = $spreadsheet->getActiveSheet();
        $actividadesSheet->setTitle('Actividades');
        $this->fillDelegacionesActividadesSheet($actividadesSheet, $reporte);

        $hechosSheet = $spreadsheet->createSheet();
        $hechosSheet->setTitle('Hechos');
        $this->fillDelegacionesHechosSheet($hechosSheet, $reporte);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'reporte_delegaciones_' . $reporte['fechaInicio'] . '_' . $reporte['fechaFin'] . '.xlsx';
        $tempPath = storage_path('app/temp/' . uniqid('reporte_delegaciones_', true) . '.xlsx');

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        return response()
            ->download($tempPath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    private function fillDelegacionesActividadesSheet($sheet, array $reporte): void
    {
        $headers = [
            'ID',
            'Fecha',
            'Hora',
            'Delegacion',
            'Municipio',
            'Categoria',
            'Subcategoria',
            'Actividad',
            'Lugar',
            'Observaciones',
            'Creado por',
        ];

        $this->writeReportHeader($sheet, $reporte, 'Actividades', count($headers));
        $this->writeTableHeader($sheet, 6, $headers);

        $row = 7;
        foreach ($reporte['actividades'] as $actividad) {
            $sheet->fromArray([
                $actividad->id,
                optional($actividad->fecha)->format('Y-m-d'),
                $this->formatHora($actividad->hora),
                optional($actividad->delegacion)->nombre,
                $actividad->municipio,
                optional($actividad->categoria)->nombre,
                optional($actividad->subcategoria)->nombre,
                $actividad->nombre,
                $actividad->lugar,
                $actividad->observaciones ?: $actividad->motivo ?: $actividad->narrativa,
                optional($actividad->creador)->name,
            ], null, 'A' . $row);
            $row++;
        }

        $this->finishReportSheet($sheet, $row - 1, count($headers));
    }

    private function fillDelegacionesHechosSheet($sheet, array $reporte): void
    {
        $headers = [
            'ID',
            'Fecha',
            'Hora',
            'Folio C5i',
            'Delegacion',
            'Municipio',
            'Tipo de hecho',
            'Situacion',
            'Calle',
            'Colonia',
            'Entre calles',
            'Vehiculos',
            'Lesionados',
            'Estado revision',
            'Creado por',
        ];

        $this->writeReportHeader($sheet, $reporte, 'Hechos', count($headers));
        $this->writeTableHeader($sheet, 6, $headers);

        $row = 7;
        foreach ($reporte['hechos'] as $hecho) {
            $sheet->fromArray([
                $hecho->id,
                optional($hecho->fecha)->format('Y-m-d'),
                $this->formatHora($hecho->hora),
                $hecho->folio_c5i,
                optional($hecho->delegacion)->nombre,
                $hecho->municipio,
                $hecho->tipo_hecho,
                $hecho->situacion,
                $hecho->calle,
                $hecho->colonia,
                $hecho->entre_calles,
                (int) $hecho->vehiculos_count,
                (int) $hecho->lesionados_count,
                $hecho->estado_revision,
                optional($hecho->creator)->name,
            ], null, 'A' . $row);
            $row++;
        }

        $this->finishReportSheet($sheet, $row - 1, count($headers));
    }

    private function writeReportHeader($sheet, array $reporte, string $title, int $columnCount): void
    {
        $lastColumn = $this->columnLetter($columnCount);

        $sheet->mergeCells('A1:' . $lastColumn . '1');
        $sheet->setCellValue('A1', 'Reporte Delegaciones - ' . $title);
        $sheet->mergeCells('A2:' . $lastColumn . '2');
        $sheet->setCellValue('A2', 'Periodo: ' . $reporte['inicio']->format('d/m/Y') . ' al ' . $reporte['fin']->format('d/m/Y') . ' | Unidad org ID: 2 | Generado: ' . $reporte['generadoEn']->format('d/m/Y H:i'));
        $sheet->mergeCells('A3:' . $lastColumn . '3');
        $sheet->setCellValue('A3', 'Actividades: ' . $reporte['resumen']['actividades_total'] . ' | Hechos: ' . $reporte['resumen']['hechos_total']);

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2:A3')->getFont()->setBold(true);
        $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function writeTableHeader($sheet, int $row, array $headers): void
    {
        $sheet->fromArray($headers, null, 'A' . $row);

        $lastColumn = $this->columnLetter(count($headers));
        $range = 'A' . $row . ':' . $lastColumn . $row;

        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4F82');
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function finishReportSheet($sheet, int $lastRow, int $columnCount): void
    {
        $lastColumn = $this->columnLetter($columnCount);

        if ($lastRow < 7) {
            $sheet->setCellValue('A7', 'Sin informacion en el periodo.');
            $sheet->mergeCells('A7:' . $lastColumn . '7');
            $lastRow = 7;
        }

        $sheet->getStyle('A6:' . $lastColumn . $lastRow)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setRGB('D5DDE8');

        $sheet->getStyle('A7:' . $lastColumn . $lastRow)
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(true);

        $sheet->freezePane('A7');
        $sheet->setAutoFilter('A6:' . $lastColumn . $lastRow);

        for ($i = 1; $i <= $columnCount; $i++) {
            $sheet->getColumnDimension($this->columnLetter($i))->setAutoSize(true);
        }
    }

    private function columnLetter(int $index): string
    {
        $letter = '';

        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }

    private function formatHora($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }

        return substr((string) $value, 0, 5);
    }

    private function ensureCanDownloadDelegacionesBackup(Request $request): void
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        if ($user->hasRole('Superadmin')) {
            return;
        }

        $unidadId = (int) ($user->unidad_id ?? 0);

        if ($unidadId === 3) {
            return;
        }

        if ($unidadId === self::UNIDAD_DELEGACIONES_ID) {
            return;
        }

        abort(403);
    }

    private function streamDelegacionesSql($generatedAt): void
    {
        @set_time_limit(0);

        $database = DB::connection()->getDatabaseName();

        echo "-- Respaldo SQL de Delegaciones\n";
        echo "-- Base de datos: " . $database . "\n";
        echo "-- Generado: " . $generatedAt->format('Y-m-d H:i:s') . " America/Mexico_City\n";
        echo "-- Alcance: registros con unidad_org_id = " . self::UNIDAD_DELEGACIONES_ID . " en actividades y hechos, mas registros relacionados.\n";
        echo "-- Este archivo se genera al momento y no se almacena en el servidor.\n\n";
        echo "SET FOREIGN_KEY_CHECKS=0;\n";
        echo "SET NAMES utf8mb4;\n\n";

        $actividadIds = $this->idsByUnidad('actividades');
        $hechoIds = $this->idsByUnidad('hechos');

        $actividadCategoriaIds = $this->pluckColumnWhereIn(
            'actividades',
            'actividad_categoria_id',
            'id',
            $actividadIds
        );
        $actividadSubcategoriaIds = $this->pluckColumnWhereIn(
            'actividades',
            'actividad_subcategoria_id',
            'id',
            $actividadIds
        );

        $vehiculoIds = array_values(array_unique(array_merge(
            $this->pluckColumnWhereIn('actividad_vehiculo', 'vehiculo_id', 'actividad_id', $actividadIds),
            $this->pluckColumnWhereIn('hecho_vehiculo', 'vehiculo_id', 'hecho_id', $hechoIds)
        )));

        $conductorIds = $this->pluckColumnWhereIn(
            'vehiculo_conductor',
            'conductor_id',
            'vehiculo_id',
            $vehiculoIds
        );

        $sections = [
            ['actividad_categorias', 'id', $actividadCategoriaIds],
            ['actividad_subcategorias', 'id', $actividadSubcategoriaIds],
            ['actividades', 'id', $actividadIds],
            ['actividad_fotos', 'actividad_id', $actividadIds],
            ['vehiculos', 'id', $vehiculoIds],
            ['conductores', 'id', $conductorIds],
            ['actividad_vehiculo', 'actividad_id', $actividadIds],
            ['hechos', 'id', $hechoIds],
            ['lesionados', 'hecho_id', $hechoIds],
            ['hecho_vehiculo', 'hecho_id', $hechoIds],
            ['vehiculo_conductor', 'vehiculo_id', $vehiculoIds],
            ['croquis', 'hecho_id', $hechoIds],
            ['hecho_situacion_historial', 'hecho_id', $hechoIds],
        ];

        $total = 0;
        foreach ($sections as [$table, $filterColumn, $ids]) {
            $total += $this->streamTableWhereIn($table, $filterColumn, $ids);
        }

        echo "\nSET FOREIGN_KEY_CHECKS=1;\n";
        echo "-- Total de filas exportadas: " . $total . "\n";
    }

    private function idsByUnidad(string $table): array
    {
        if (!$this->canReadTable($table, ['id', 'unidad_org_id'])) {
            return [];
        }

        return DB::table($table)
            ->where('unidad_org_id', self::UNIDAD_DELEGACIONES_ID)
            ->pluck('id')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function pluckColumnWhereIn(string $table, string $targetColumn, string $filterColumn, array $ids): array
    {
        if (empty($ids) || !$this->canReadTable($table, [$targetColumn, $filterColumn])) {
            return [];
        }

        return DB::table($table)
            ->whereIn($filterColumn, $ids)
            ->pluck($targetColumn)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function streamTableWhereIn(string $table, string $filterColumn, array $ids): int
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $filterColumn)) {
            return 0;
        }

        echo "-- Tabla: " . $table . "\n";

        if (empty($ids)) {
            echo "-- Sin filas para exportar.\n\n";
            return 0;
        }

        $columns = Schema::getColumnListing($table);
        if (empty($columns)) {
            echo "-- Sin columnas legibles.\n\n";
            return 0;
        }

        $query = DB::table($table)->whereIn($filterColumn, $ids);
        $countQuery = clone $query;
        $count = (int) $countQuery->count();

        if ($count === 0) {
            echo "-- Sin filas para exportar.\n\n";
            return 0;
        }

        $orderColumn = in_array('id', $columns, true) ? 'id' : $columns[0];
        $columnSql = implode(', ', array_map([$this, 'quoteIdentifier'], $columns));

        $exported = 0;
        $dataQuery = clone $query;
        $dataQuery
            ->select($columns)
            ->orderBy($orderColumn)
            ->chunk(500, function ($rows) use ($table, $columns, $columnSql, &$exported) {
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($columns as $column) {
                        $values[] = $this->quoteValue($row->{$column} ?? null);
                    }

                    echo 'INSERT INTO ' . $this->quoteIdentifier($table)
                        . ' (' . $columnSql . ') VALUES ('
                        . implode(', ', $values) . ");\n";
                    $exported++;
                }

                flush();
            });

        echo "-- Filas exportadas: " . $exported . "\n\n";

        return $exported;
    }

    private function canReadTable(string $table, array $columns): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function quoteValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_object($value) || is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $quoted = DB::getPdo()->quote((string) $value);

        if ($quoted === false) {
            return "'" . str_replace("'", "''", (string) $value) . "'";
        }

        return $quoted;
    }
}
