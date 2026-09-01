<?php

namespace App\Services\Exports;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EstadisticaMensualExporter
{
    private const UNIDAD_SINIESTROS_ID = 1;
    private const UNIDAD_DELEGACIONES_ID = 2;

    public function download(Request $request, int $anio, int $mes)
    {
        $reqDesde = trim((string)$request->query('desde', ''));
        $reqHasta = trim((string)$request->query('hasta', ''));

        if ($reqDesde !== '' && $reqHasta !== '') {
            $desde = $reqDesde;
            $hasta = $reqHasta;
        } elseif ($reqDesde !== '') {
            $desde = $reqDesde;
            $hasta = $reqDesde;
        } elseif ($reqHasta !== '') {
            $desde = $reqHasta;
            $hasta = $reqHasta;
        } else {
            $desde = sprintf('%04d-%02d-01', $anio, $mes);
            $hasta = date('Y-m-t', strtotime($desde));
        }

        $filename = "estadistica_{$desde}_{$hasta}.xlsx";

        $hechosQuery = DB::table('hechos')
            ->leftJoin('users as export_creator', 'export_creator.id', '=', 'hechos.created_by')
            ->leftJoin('delegaciones as export_delegacion', 'export_delegacion.id', '=', 'hechos.delegacion_id')
            ->leftJoin('delegaciones as export_creator_delegacion', 'export_creator_delegacion.id', '=', 'export_creator.delegacion_id')
            ->select([
                'hechos.*',
                DB::raw("CASE
                    WHEN COALESCE(hechos.unidad_org_id, export_creator.unidad_id) = " . self::UNIDAD_DELEGACIONES_ID . "
                    THEN COALESCE(export_delegacion.nombre, export_creator_delegacion.nombre)
                    ELSE NULL
                END as delegacion"),
            ])
            ->whereBetween('hechos.fecha', [$desde, $hasta]);

        $this->applyScopeByUser($hechosQuery, $request);
        $this->applyOrigenHechosFilter($hechosQuery, $request);

        $delegacionId = trim((string)$request->query('delegacion_id', ''));
        if ($delegacionId !== '') {
            $hechosQuery->where('hechos.delegacion_id', $delegacionId);
        }

        $hechosQuery
            ->orderBy('hechos.fecha')
            ->orderBy('hechos.id');

        return new StreamedResponse(function () use ($hechosQuery) {

            $hechos = $hechosQuery->get();
            $hechoIds = $hechos->pluck('id')->values()->all();

            $hechoVehiculos = collect();
            $vehiculos = collect();

            if (!empty($hechoIds)) {
                $hechoVehiculos = DB::table('hecho_vehiculo')
                    ->select('hecho_id', 'vehiculo_id')
                    ->whereIn('hecho_id', $hechoIds)
                    ->get();

                $vehiculoIds = $hechoVehiculos->pluck('vehiculo_id')->unique()->values()->all();

                if (!empty($vehiculoIds)) {
                    $vehiculos = DB::table('vehiculos')
                        ->whereIn('id', $vehiculoIds)
                        ->get()
                        ->keyBy('id');
                }
            }

            $vehiculoConductores = collect();
            $conductores = collect();

            $vehiculoIdsAll = $vehiculos->keys()->values()->all();
            if (!empty($vehiculoIdsAll)) {
                $vehiculoConductores = DB::table('vehiculo_conductor')
                    ->select('vehiculo_id', 'conductor_id')
                    ->whereIn('vehiculo_id', $vehiculoIdsAll)
                    ->get()
                    ->groupBy('vehiculo_id');

                $conductorIds = $vehiculoConductores->flatten(1)->pluck('conductor_id')->unique()->values()->all();

                if (!empty($conductorIds)) {
                    $conductores = DB::table('conductores')
                        ->whereIn('id', $conductorIds)
                        ->get()
                        ->keyBy('id');
                }
            }

            $lesionados = collect();
            if (!empty($hechoIds)) {
                $lesionados = DB::table('lesionados')
                    ->whereIn('hecho_id', $hechoIds)
                    ->orderBy('hecho_id')
                    ->orderBy('id')
                    ->get();
            }

            $spreadsheet = new Spreadsheet();

            $sheet1 = $spreadsheet->getActiveSheet();
            $sheet1->setTitle('incidentes');

            $headersIncidentes = [
                'id_accidentes','fecha','hora','telefono','fecha_capt','tipo_incidente_id_incidente',
                'callea','calleb','georef','id_mpio','id_ciudad','ciudad','colonia','domicilio','numero',
                'coordenadas','sector','resuelto','pendiente','superficie_via','tiempo','clima','condiciones',
                'cont_transito','circunstancias','colision_sob_cam','tipo_vehiculo','otras_causas','otros',
                'perito','unidad','situacion','oficio_mp','personasmp','vehiculosmp','reporte',
                'id_usuario','calidad_geo','nota_geo','delegacion'
            ];
            $sheet1->fromArray($headersIncidentes, null, 'A1');

            $row = 2;
            foreach ($hechos as $h) {
                $fecha = $h->fecha ? date('d/m/Y', strtotime($h->fecha)) : '';
                $hora  = $h->hora ? substr((string)$h->hora, 0, 5) : '';

                $coords = '\N';
                if ($h->lat !== null && $h->lng !== null && $h->lat !== '' && $h->lng !== '') {
                    $lat = rtrim(rtrim((string)$h->lat, '0'), '.');
                    $lng = rtrim(rtrim((string)$h->lng, '0'), '.');
                    $coords = $lat . ',' . $lng;
                }

                $situacion = $h->situacion ?? 'PENDIENTE';

                $sheet1->fromArray([
                    $h->id ?? '',
                    $fecha,
                    $hora,
                    'sin uso',
                    ($h->created_at ?? 'reservado'),
                    $h->tipo_hecho ?? '\N',
                    $h->calle ?? '\N',
                    $h->entre_calles ?? '\N',
                    0,
                    '\N',
                    '\N',
                    $h->municipio ?? '\N',
                    $h->colonia ?? '\N',
                    ($h->ubicacion_formateada ?? ($h->calle ?? '\N')),
                    '\N',
                    $coords,
                    $h->sector ?? 0,
                    'sin uso',
                    'sin uso',
                    $h->superficie_via ?? '\N',
                    $h->tiempo ?? '\N',
                    $h->clima ?? '\N',
                    $h->condiciones ?? '\N',
                    $h->control_transito ?? '\N',
                    $h->causas ?? '\N',
                    $h->colision_camino ?? '\N',
                    '\N',
                    '\N',
                    '\N',
                    $h->perito ?? 'reservado',
                    $h->unidad ?? 'reservado',
                    $situacion,
                    $h->oficio_mp ?? '\N',
                    $h->personas_mp ?? '\N',
                    $h->vehiculos_mp ?? '\N',
                    '',
                    $h->created_by ?? '\N',
                    $h->calidad_geo ?? '\N',
                    $h->nota_geo ?? 'sin uso',
                    $h->delegacion ?? '',
                ], null, "A{$row}");

                $row++;
            }

            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('vehiculos');

            $headersVehiculos = [
                'id_vehiculo','id_accidentes','marca','modelo','tipo','color','capacidad','clasificacion',
                'placas','servicio','entidad','noserie','propiedad','domiciliop','conductor','cinturon',
                'edad','sexo','tel','domicilio','colonia','municipio','entidadc','vigencia','tipolic_no',
                'ocupacion','partes_daniadas','monto','statusc','aliento_etil','cert_med','grua','corralon',
                'tipod','propiedadd','cantidadd'
            ];
            $sheet2->fromArray($headersVehiculos, null, 'A1');

            $row = 2;
            foreach ($hechoVehiculos as $hv) {
                $v = $vehiculos->get($hv->vehiculo_id);
                if (!$v) continue;

                $c = null;
                $links = $vehiculoConductores->get($v->id, collect());
                if ($links->count() > 0) {
                    $first = $links->first();
                    if ($first && isset($first->conductor_id)) {
                        $c = $conductores->get($first->conductor_id);
                    }
                }

                $sheet2->fromArray([
                    $v->id ?? '',
                    $hv->hecho_id ?? '',
                    $v->marca ?? '\N',
                    $v->modelo ?? '\N',
                    $v->tipo ?? '\N',
                    $v->color ?? '\N',
                    ($v->capacidad_personas ?? '\N'),
                    '\N',
                    $v->placas ?? '\N',
                    $v->tipo_servicio ?? '\N',
                    $v->estado_placas ?? '\N',
                    $v->serie ?? '\N',
                    '\N',
                    '\N',
                    $c->nombre ?? 'reservado',
                    ($c ? (int)($c->cinturon ?? 0) : 0),
                    $c->edad ?? '\N',
                    $c->sexo ?? '\N',
                    $c->telefono ?? '\N',
                    $c->domicilio ?? '\N',
                    '\N',
                    '\N',
                    '\N',
                    $c->vigencia_licencia ?? '\N',
                    $c->tipo_licencia ?? '\N',
                    $c->ocupacion ?? '\N',
                    $v->partes_danadas ?? '\N',
                    $v->monto_danos ?? '\N',
                    '\N',
                    ($c ? (int)($c->aliento_etilico ?? 0) : 0),
                    ($c ? (int)($c->certificado_lesiones ?? 0) : 0),
                    $v->grua ?? '\N',
                    $v->corralon ?? '\N',
                    '\N',
                    '\N',
                    '\N',
                ], null, "A{$row}");

                $row++;
            }

            $sheet3 = $spreadsheet->createSheet();
            $sheet3->setTitle('personas');

            $headersPersonas = [
                'id_accidentes','id_vehiculo','nombre','sexo','edad','domicilio','colonia','id_mpio',
                'entidad','peatonopasajero','status','trasladado_a','auxiliardo_por'
            ];
            $sheet3->fromArray($headersPersonas, null, 'A1');

            $row = 2;
            foreach ($lesionados as $l) {
                $sheet3->fromArray([
                    $l->hecho_id ?? '',
                    '\N',
                    $l->nombre ?? 'reservado',
                    $l->sexo ?? '\N',
                    $l->edad ?? '\N',
                    'reservado',
                    '\N',
                    '\N',
                    '\N',
                    '\N',
                    $l->hospital ?? '\N',
                    $l->hospital ?? '\N',
                    $l->paramedico ?? 'reservado',
                ], null, "A{$row}");

                $row++;
            }

            foreach ([$sheet1, $sheet2, $sheet3] as $sh) {
                $sh->freezePane('A2');
                $sh->getStyle('A1:ZZ1')->getFont()->setBold(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');

        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="estadistica.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function applyScopeByUser($q, Request $request): void
    {
        $user = $request->user();

        if (!$user) {
            $q->whereRaw('1 = 0');
            return;
        }

        if ($user->hasRole('Superadmin')) {
            return;
        }

        $unidadId = (int)($user->unidad_id ?? 0);

        if ($unidadId === 3) {
            return;
        }

        if ($unidadId > 0) {
            $this->applyUnidadScope($q, $unidadId);
            return;
        }

        $q->whereRaw('1 = 0');
    }

    private function applyUnidadScope($q, int $unidadId): void
    {
        $this->applyUnidadesScope($q, [$unidadId]);
    }

    private function applyOrigenHechosFilter($q, Request $request): void
    {
        $origen = strtolower(trim((string) $request->query('origen_hechos', '')));

        if ($origen === 'ambas' || $origen === 'ambos') {
            $this->applyUnidadesScope($q, [
                self::UNIDAD_SINIESTROS_ID,
                self::UNIDAD_DELEGACIONES_ID,
            ]);
            return;
        }

        if ($origen === 'siniestros') {
            $this->applyUnidadScope($q, self::UNIDAD_SINIESTROS_ID);
            return;
        }

        if ($origen === 'delegaciones') {
            $this->applyUnidadScope($q, self::UNIDAD_DELEGACIONES_ID);
        }
    }

    private function applyUnidadesScope($q, array $unidadIds): void
    {
        $unidadIds = array_values(array_unique(array_map('intval', $unidadIds)));
        $unidadIds = array_values(array_filter($unidadIds, fn ($id) => $id > 0));

        if (empty($unidadIds)) {
            $q->whereRaw('1 = 0');
            return;
        }

        $q->where(function ($scope) use ($unidadIds) {
            $scope->whereIn('hechos.unidad_org_id', $unidadIds)
                ->orWhere(function ($legacy) use ($unidadIds) {
                    $legacy->whereNull('hechos.unidad_org_id')
                        ->whereExists(function ($sub) use ($unidadIds) {
                            $sub->selectRaw('1')
                                ->from('users')
                                ->whereColumn('users.id', 'hechos.created_by')
                                ->whereIn('users.unidad_id', $unidadIds);
                        });
                });
        });
    }
}
