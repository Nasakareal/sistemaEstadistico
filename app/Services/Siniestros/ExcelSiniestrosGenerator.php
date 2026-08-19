<?php

namespace App\Services\Siniestros;

use App\Models\Personal;
use App\Services\Siniestros\Hojas\EstadoFuerzaSheetService;
use App\Services\Siniestros\Hojas\NovRelSheetService;
use App\Services\Siniestros\Hojas\TotalSheetService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelSiniestrosGenerator
{
    private const UNIDAD_SINIESTROS_ID = 1;

    public function generar(string $fecha): string
    {
        $fecha = Carbon::parse($fecha, 'America/Mexico_City')->format('Y-m-d');

        [$inicio, $fin] = $this->rangoCorte($fecha);

        $unidadId = $this->unidadSiniestrosId();

        $personal = $this->personalSiniestros(
            $unidadId,
            $fin
        );

        $actividades = $this->actividadesSiniestros(
            $unidadId,
            $inicio,
            $fin
        );

        $spreadsheet = new Spreadsheet();

        $estadoFuerza = $spreadsheet->getActiveSheet();
        $estadoFuerza->setTitle('EST FUERZA');

        app(EstadoFuerzaSheetService::class)->generar(
            $estadoFuerza,
            $personal,
            $fecha,
            $inicio,
            $fin
        );

        $total = $spreadsheet->createSheet();
        $total->setTitle('TOTAL');

        app(TotalSheetService::class)->generar(
            $total,
            $personal,
            $actividades,
            $fecha,
            $inicio,
            $fin
        );

        $novRel = $spreadsheet->createSheet();
        $novRel->setTitle('NOV. REL');

        app(NovRelSheetService::class)->generar(
            $novRel,
            $actividades,
            $fecha,
            $inicio,
            $fin
        );

        $spreadsheet->setActiveSheetIndex(0);

        $tempPath = storage_path(
            'app/temp_excel_siniestros_' . $fecha . '.xlsx'
        );

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        return $tempPath;
    }

    private function personalSiniestros(
        int $unidadId,
        Carbon $fin
    ): Collection {
        return Personal::query()
            ->with([
                'unidad',
                'turno',
                'patrulla',
                'incidencias.tipo',
            ])
            ->where('unidad_id', $unidadId)
            ->whereRaw(
                "UPPER(TRIM(COALESCE(estatus, ''))) = ?",
                ['ACTIVO']
            )
            ->where(function ($query) use ($fin) {
                $query
                    ->whereNull('fecha_baja')
                    ->orWhereDate(
                        'fecha_baja',
                        '>',
                        $fin->toDateString()
                    );
            })
            ->orderBy('grado')
            ->orderBy('ap_paterno')
            ->orderBy('ap_materno')
            ->orderBy('nombre')
            ->get();
    }

    private function actividadesSiniestros(
        int $unidadId,
        Carbon $inicio,
        Carbon $fin
    ): Collection {
        return DB::table('actividades')
            ->leftJoin(
                'actividad_categorias',
                'actividad_categorias.id',
                '=',
                'actividades.actividad_categoria_id'
            )
            ->leftJoin(
                'actividad_subcategorias',
                'actividad_subcategorias.id',
                '=',
                'actividades.actividad_subcategoria_id'
            )
            ->leftJoin(
                'users',
                'users.id',
                '=',
                'actividades.created_by'
            )
            ->where(function ($query) use ($unidadId) {
                $query
                    ->where(
                        'actividades.unidad_org_id',
                        $unidadId
                    )
                    ->orWhere(function ($legacy) use ($unidadId) {
                        $legacy
                            ->whereNull(
                                'actividades.unidad_org_id'
                            )
                            ->whereExists(
                                function ($sub) use ($unidadId) {
                                    $sub
                                        ->selectRaw('1')
                                        ->from(
                                            'users as actividad_creadores'
                                        )
                                        ->whereColumn(
                                            'actividad_creadores.id',
                                            'actividades.created_by'
                                        )
                                        ->where(
                                            'actividad_creadores.unidad_id',
                                            $unidadId
                                        );
                                }
                            );
                    });
            })
            ->whereRaw(
                "TIMESTAMP(
                    DATE(actividades.fecha),
                    COALESCE(actividades.hora, '00:00:00')
                ) >= ?
                AND
                TIMESTAMP(
                    DATE(actividades.fecha),
                    COALESCE(actividades.hora, '00:00:00')
                ) < ?",
                [
                    $inicio->toDateTimeString(),
                    $fin->toDateTimeString(),
                ]
            )
            ->select([
                'actividades.*',
                'actividad_categorias.nombre as categoria_nombre',
                'actividad_subcategorias.nombre as subcategoria_nombre',
                'users.name as capturo',
            ])
            ->orderBy('actividades.fecha')
            ->orderBy('actividades.hora')
            ->orderBy('actividades.id')
            ->get();
    }

    private function unidadSiniestrosId(): int
    {
        $id = DB::table('unidades')
            ->where('id', self::UNIDAD_SINIESTROS_ID)
            ->value('id');

        if ($id) {
            return (int) $id;
        }

        return (int) DB::table('unidades')
            ->whereIn('slug', [
                'siniestros',
                'atencion-siniestros',
                'unidad-atencion-siniestros',
            ])
            ->orWhere(
                'nombre',
                'like',
                '%SINIESTROS%'
            )
            ->value('id');
    }

    private function rangoCorte(string $fecha): array
    {
        $horaCorte = config(
            'cortes.hora_corte_siniestros',
            '18:00:00'
        );

        $fin = Carbon::parse(
            $fecha . ' ' . $horaCorte,
            'America/Mexico_City'
        );

        return [
            $fin->copy()->subDay(),
            $fin,
        ];
    }
}
