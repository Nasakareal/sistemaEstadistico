<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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

        $now = now('America/Mexico_City');
        $filename = 'respaldo_delegaciones_' . $now->format('Ymd_His') . '.sql';

        return response()->streamDownload(function () use ($now) {
            $this->streamDelegacionesSql($now);
        }, $filename, [
            'Content-Type' => 'application/sql; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
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
        $rolPermitido = $user->hasRole('Administrador') || $user->hasRole('Subdirector');

        if ($unidadId === self::UNIDAD_DELEGACIONES_ID && $rolPermitido) {
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
