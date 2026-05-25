<?php

namespace App\Services\Fomento;

use App\Models\Personal;
use App\Services\Fomento\Hojas\EstadoFuerzaSheetService;
use App\Services\Fomento\Hojas\NovRelSheetService;
use App\Services\Fomento\Hojas\TotalSheetService;
use App\Services\Fomento\Hojas\UcvSheetService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelFomentoGenerator
{
    private const UNIDAD_FOMENTO_ID = 6;

    public function generar(string $fecha): string
    {
        $fecha = Carbon::parse($fecha, 'America/Mexico_City')->format('Y-m-d');
        [$inicio, $fin] = $this->rangoCorte($fecha);

        $unidadId = $this->unidadFomentoId();
        $personal = $this->personalFomento($unidadId, $fin);
        $actividades = $this->actividadesFomento($unidadId, $inicio, $fin);

        $spreadsheet = new Spreadsheet();

        $estadoFuerza = $spreadsheet->getActiveSheet();
        $estadoFuerza->setTitle('EST FUERZA');
        app(EstadoFuerzaSheetService::class)->generar($estadoFuerza, $personal, $fecha, $inicio, $fin);

        $total = $spreadsheet->createSheet();
        $total->setTitle('TOTAL');
        app(TotalSheetService::class)->generar($total, $personal, $actividades, $fecha, $inicio, $fin);

        $ucv = $spreadsheet->createSheet();
        $ucv->setTitle('UCV');
        app(UcvSheetService::class)->generar($ucv, $actividades, $fecha, $inicio, $fin);

        $novRel = $spreadsheet->createSheet();
        $novRel->setTitle('NOV. REL');
        app(NovRelSheetService::class)->generar($novRel, $actividades, $fecha, $inicio, $fin);

        $spreadsheet->setActiveSheetIndex(0);

        $tempPath = storage_path('app/temp_excel_fomento_' . $fecha . '.xlsx');

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        return $tempPath;
    }

    private function personalFomento(int $unidadId, Carbon $fin): Collection
    {
        return Personal::query()
            ->with(['unidad', 'turno', 'patrulla', 'incidencias.tipo'])
            ->where('unidad_id', $unidadId)
            ->whereRaw("UPPER(TRIM(COALESCE(estatus, ''))) = ?", ['ACTIVO'])
            ->where(function ($query) use ($fin) {
                $query->whereNull('fecha_baja')
                    ->orWhereDate('fecha_baja', '>', $fin->toDateString());
            })
            ->orderBy('grado')
            ->orderBy('ap_paterno')
            ->orderBy('ap_materno')
            ->orderBy('nombre')
            ->get();
    }

    private function actividadesFomento(int $unidadId, Carbon $inicio, Carbon $fin): Collection
    {
        return DB::table('actividades')
            ->leftJoin('actividad_categorias', 'actividad_categorias.id', '=', 'actividades.actividad_categoria_id')
            ->leftJoin('actividad_subcategorias', 'actividad_subcategorias.id', '=', 'actividades.actividad_subcategoria_id')
            ->leftJoin('fomento_cultura_vial_detalles as fomento', 'fomento.actividad_id', '=', 'actividades.id')
            ->leftJoin('users', 'users.id', '=', 'actividades.created_by')
            ->where(function ($query) use ($unidadId) {
                $query->where('actividades.unidad_org_id', $unidadId)
                    ->orWhere(function ($legacy) use ($unidadId) {
                        $legacy->whereNull('actividades.unidad_org_id')
                            ->whereExists(function ($sub) use ($unidadId) {
                                $sub->selectRaw('1')
                                    ->from('users as actividad_creadores')
                                    ->whereColumn('actividad_creadores.id', 'actividades.created_by')
                                    ->where('actividad_creadores.unidad_id', $unidadId);
                            });
                    });
            })
            ->whereRaw(
                "TIMESTAMP(DATE(actividades.fecha), COALESCE(actividades.hora, '00:00:00')) >= ? AND TIMESTAMP(DATE(actividades.fecha), COALESCE(actividades.hora, '00:00:00')) < ?",
                [$inicio->toDateTimeString(), $fin->toDateTimeString()]
            )
            ->select([
                'actividades.id',
                'actividades.actividad_categoria_id',
                'actividades.actividad_subcategoria_id',
                'actividades.fecha',
                'actividades.hora',
                'actividades.nombre as actividad_nombre',
                'actividades.cantidad',
                'actividades.foto_path',
                'actividades.foto_thumbnail_path',
                'actividades.municipio',
                'actividades.lugar',
                'actividades.motivo',
                'actividades.narrativa',
                'actividades.acciones_realizadas',
                'actividades.observaciones',
                'actividades.personas_alcanzadas',
                'actividades.personas_participantes',
                'actividades.personas_detenidas',
                'actividades.patrullas_participantes_texto',
                'actividades.km_recorridos',
                'actividad_categorias.nombre as categoria_nombre',
                'actividad_subcategorias.nombre as subcategoria_nombre',
                'users.name as capturo',
                'fomento.fomento_cultura_vial_programa_id',
                'fomento.programa_nombre',
                'fomento.nombre_institucion',
                'fomento.domicilio',
                'fomento.nivel_educativo',
                'fomento.sector',
                'fomento.ninas',
                'fomento.ninos',
                'fomento.adolescentes_mujeres',
                'fomento.adolescentes_hombres',
                'fomento.docentes_hombres',
                'fomento.docentes_mujeres',
                'fomento.hombres',
                'fomento.mujeres',
                'fomento.total_poblacion_atendida',
            ])
            ->orderBy('actividades.fecha')
            ->orderBy('actividades.hora')
            ->orderBy('actividades.id')
            ->get();
    }

    private function unidadFomentoId(): int
    {
        $id = DB::table('unidades')
            ->where('id', self::UNIDAD_FOMENTO_ID)
            ->value('id');

        if ($id) {
            return (int) $id;
        }

        return (int) DB::table('unidades')
            ->whereIn('slug', ['cultura-vial', 'fomento-cultura-vial', 'fomento-a-la-cultura-vial'])
            ->orWhere('nombre', 'like', '%FOMENTO%CULTURA%VIAL%')
            ->value('id');
    }

    private function rangoCorte(string $fecha): array
    {
        $horaCorte = config('cortes.hora_corte_fomento', '18:00:00');
        $fin = Carbon::parse($fecha . ' ' . $horaCorte, 'America/Mexico_City');

        return [$fin->copy()->subDay(), $fin];
    }
}
