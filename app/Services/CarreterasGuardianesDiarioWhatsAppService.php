<?php

namespace App\Services;

use App\Models\OperativoDispositivo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CarreterasGuardianesDiarioWhatsAppService
{
    private const DEVICE_FIELDS = [
        'cantidad',
        'vehiculos_inspeccionados',
        'personas_inspeccionadas',
        'vehiculos_impactados',
        'personas_impactadas',
        'estado_fuerza_participante',
        'kilometros_recorridos',
        'acompanamientos',
        'abanderamientos',
        'auxilios_viales',
        'prox_empresas',
        'prox_tiendas_conveniencia',
        'prox_escuelas',
        'prox_hospitales',
        'antecedentes_personas',
        'antecedentes_vehiculos',
        'antecedentes_motos',
        'antecedentes_camiones',
        'puestas_disposicion',
        'vehiculos_recuperados',
        'armas_aseguradas',
        'mercancia_recuperada',
        'decomiso_drogas',
    ];

    public function generar(?Carbon $corte = null): array
    {
        $emitido = $this->emitido($corte);
        [$inicio, $fin] = $this->rango($emitido);
        $rows = $this->resumenPorDispositivo($inicio, $fin);
        $totales = $this->totalesGenerales($rows);
        $mensaje = $this->mensaje($emitido, $rows, $totales);

        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'emitido' => $emitido,
            'totales' => $totales,
            'dispositivos' => $rows,
            'mensaje' => $mensaje,
            'template_chunks' => $this->templateChunks($mensaje),
            'template_parts' => $this->threePartTemplateMessages($emitido, $rows, $totales),
        ];
    }

    public function generarDemo(?Carbon $corte = null): array
    {
        $emitido = $this->emitido($corte);
        [$inicio, $fin] = $this->rango($emitido);
        $rows = collect();
        $totales = $this->blankTotals();
        $mensaje = $this->mensaje($emitido, $rows, $totales);

        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'emitido' => $emitido,
            'totales' => $totales,
            'dispositivos' => $rows,
            'mensaje' => $mensaje,
            'template_chunks' => $this->templateChunks($mensaje),
            'template_parts' => $this->threePartTemplateMessages($emitido, $rows, $totales),
        ];
    }

    public function rango(?Carbon $corte = null): array
    {
        $base = $this->emitido($corte);
        [$hora, $minuto, $segundo] = $this->horaCorte();
        $corteHoy = $base->copy()->setTime($hora, $minuto, $segundo);

        $fin = $base->greaterThanOrEqualTo($corteHoy)
            ? $corteHoy
            : $corteHoy->copy()->subDay();

        return [$fin->copy()->subDay(), $fin];
    }

    public function templateChunks(string $mensaje, int $maxBodyChars = 850): array
    {
        $chunks = $this->splitMessage($mensaje, $maxBodyChars);
        $total = count($chunks);
        $out = [];

        foreach ($chunks as $index => $chunk) {
            $part = $index + 1;

            $out[] = [
                'part' => $part,
                'total' => $total,
                'body' => $chunk,
                'parameters' => [
                    (string) $part,
                    (string) $total,
                    $this->templateParameterText($chunk),
                ],
            ];
        }

        return $out;
    }

    public function threePartTemplateMessages(Carbon $emitido, Collection $rows, array $totales): array
    {
        $params = $this->threePartTemplateParams($emitido, $rows, $totales);
        $bodies = $this->threePartTemplateBodies($params);

        return [
            [
                'part' => 1,
                'total' => 3,
                'body' => $bodies[0],
                'parameters' => $params[0],
            ],
            [
                'part' => 2,
                'total' => 3,
                'body' => $bodies[1],
                'parameters' => $params[1],
            ],
            [
                'part' => 3,
                'total' => 3,
                'body' => $bodies[2],
                'parameters' => $params[2],
            ],
        ];
    }

    protected function threePartTemplateParams(Carbon $emitido, Collection $rows, array $totales): array
    {
        $psv = $this->combine($rows, ['PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)', 'PSV']);
        $rsv = $this->combine($rows, ['RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)', 'RSV', 'PATRULLAJE']);
        $casco = $this->combine($rows, ['CASCO', 'DISPOSITIVO CASCO']);
        $cinturon = $this->combine($rows, ['CINTURON', 'CINTURÓN', 'DISPOSITIVO CINTURON', 'DISPOSITIVO CINTURÓN']);
        $carrusel = $this->combine($rows, ['CARRUSEL', 'DISPOSITIVO CARRUSEL']);
        $cordillera = $this->combine($rows, ['CORDILLERA']);
        $asiento = $this->combine($rows, ['ASIENTO SEGURO PASAJEROS MENORES', 'DISPOSITIVO ASIENTO SEGURO PASAJEROS MENORES', 'ASIENTO SEGURO']);
        $caballeros = $this->combine($rows, [
            'CABALLEROS DEL CAMINO',
            'CABALLERO DEL CAMINO',
            'CABALLERO DEL CAMINO (PROXIMIDAD SOCIAL)',
            'ACOMPAÑAMIENTOS',
            'ABANDERAMIENTOS',
            'AUXILIOS VIALES',
        ]);
        $proximidad = $this->combine($rows, [
            'PROXIMIDAD SOCIAL',
            'CABALLEROS DEL CAMINO',
            'CABALLERO DEL CAMINO',
            'CABALLERO DEL CAMINO (PROXIMIDAD SOCIAL)',
        ]);
        $caballerosAcciones = (int) max(
            $caballeros['cantidad'],
            $caballeros['acompanamientos'] + $caballeros['abanderamientos'] + $caballeros['auxilios_viales']
        );

        return [
            [
                $emitido->format('d/m/Y'),
                $emitido->format('H:i'),
                $this->pad($psv['cantidad']),
                $this->pad($psv['vehiculos_inspeccionados']),
                $this->pad($psv['personas_inspeccionadas']),
                $this->pad($psv['estado_fuerza_participante']),
                $this->crps($psv),
                $this->km($psv['kilometros_recorridos']),
                $this->pad($rsv['cantidad']),
                $this->pad($rsv['vehiculos_inspeccionados']),
                $this->pad($rsv['personas_inspeccionadas']),
                $this->pad($rsv['estado_fuerza_participante']),
                $this->crps($rsv),
                $this->km($rsv['kilometros_recorridos']),
            ],
            [
                $this->pad($casco['cantidad']),
                $this->pad($casco['vehiculos_impactados']),
                $this->pad($casco['personas_impactadas']),
                $this->pad($casco['estado_fuerza_participante']),
                $this->crps($casco),
                $this->km($casco['kilometros_recorridos']),
                $this->pad($cinturon['cantidad']),
                $this->pad($cinturon['vehiculos_impactados']),
                $this->pad($cinturon['personas_impactadas']),
                $this->pad($cinturon['estado_fuerza_participante']),
                $this->crps($cinturon),
                $this->km($cinturon['kilometros_recorridos']),
                $this->pad($carrusel['cantidad']),
                $this->pad($carrusel['vehiculos_impactados']),
                $this->pad($carrusel['estado_fuerza_participante']),
                $this->crps($carrusel),
                $this->km($carrusel['kilometros_recorridos']),
                $this->pad($cordillera['cantidad']),
                $this->pad($cordillera['vehiculos_impactados']),
                $this->pad($cordillera['personas_impactadas']),
                $this->pad($cordillera['estado_fuerza_participante']),
                $this->crps($cordillera),
                $this->km($cordillera['kilometros_recorridos']),
                $this->pad($asiento['cantidad']),
                $this->pad($asiento['vehiculos_impactados']),
                $this->pad($asiento['personas_impactadas']),
                $this->pad($asiento['estado_fuerza_participante']),
                $this->crps($asiento),
                $this->km($asiento['kilometros_recorridos']),
            ],
            [
                $this->pad($caballerosAcciones),
                $this->pad($caballeros['acompanamientos']),
                $this->pad($caballeros['abanderamientos']),
                $this->pad($caballeros['auxilios_viales']),
                $this->pad($caballeros['estado_fuerza_participante']),
                $this->crps($caballeros),
                $this->km($caballeros['kilometros_recorridos']),
                $this->pad($proximidad['prox_empresas']),
                $this->pad($proximidad['prox_tiendas_conveniencia']),
                $this->pad($proximidad['prox_escuelas']),
                $this->pad($proximidad['prox_hospitales']),
                $this->pad($totales['vehiculos_inspeccionados'] + $totales['personas_inspeccionadas']),
                $this->pad($totales['antecedentes_personas']),
                $this->pad($totales['antecedentes_vehiculos']),
                $this->pad($totales['antecedentes_motos']),
                $this->pad($totales['antecedentes_camiones']),
                $this->pad($totales['puestas_disposicion']),
                $this->pad($totales['vehiculos_recuperados']),
                $this->pad($totales['armas_aseguradas']),
                $this->pad($totales['mercancia_recuperada']),
                $this->pad($totales['decomiso_drogas']),
                $this->pad($totales['estado_fuerza_participante']),
                $this->crps($totales),
                $this->km($totales['kilometros_recorridos']),
            ],
        ];
    }

    protected function threePartTemplateBodies(array $params): array
    {
        $p1 = array_values(array_map('strval', $params[0] ?? []));
        $p2 = array_values(array_map('strval', $params[1] ?? []));
        $p3 = array_values(array_map('strval', $params[2] ?? []));

        for ($i = count($p1); $i < 14; $i++) {
            $p1[] = '';
        }

        for ($i = count($p2); $i < 29; $i++) {
            $p2[] = '';
        }

        for ($i = count($p3); $i < 24; $i++) {
            $p3[] = '';
        }

        return [
            "GUARDIA CIVIL\n\n"
                . "COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL\n\n"
                . "UNIDAD DE PROTECCIÓN EN CARRETERAS\n\n"
                . "DESTACAMENTO " . $this->destacamento() . "\n\n"
                . "ASUNTO: CONSOLIDADO DE NOVEDADES DE ACTIVIDADES DIARIAS.\n\n"
                . "{$p1[0]}         {$p1[1]} hs.\n\n"
                . "DESCRIPCIÓN GENERAL:\n"
                . "OPERATIVO GUARDIANES DEL CAMINO\n"
                . $this->descripcionGeneral() . "\n\n"
                . "DISPOSITIVOS:\n\n"
                . "PSV (PUESTO DE SEGURIDAD Y VIGILANCIA): {$p1[2]}\n"
                . "VEHÍCULOS INSPECCIONADOS: {$p1[3]}\n"
                . "PERSONAS INSPECCIONADAS: {$p1[4]}\n"
                . "ESTADO DE FUERZA PARTICIPANTE: {$p1[5]} elementos.\n"
                . "CRP´s. PARTICIPANTES: {$p1[6]}\n"
                . "KILÓMETROS RECORRIDOS: {$p1[7]}\n\n"
                . "RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE): {$p1[8]}\n"
                . "VEHÍCULOS INSPECCIONADOS: {$p1[9]}\n"
                . "PERSONAS INSPECCIONADAS: {$p1[10]}\n"
                . "ESTADO DE FUERZA PARTICIPANTE: {$p1[11]} elementos.\n"
                . "CRP´s. PARTICIPANTES: {$p1[12]}\n"
                . "KILÓMETROS RECORRIDOS: {$p1[13]}",

            "DISPOSITIVO CASCO: {$p2[0]}\n"
                . "VEHÍCULOS IMPACTADOS: {$p2[1]}\n"
                . "PERSONAS IMPACTADAS: {$p2[2]}\n"
                . "ESTADO DE FUERZA PARTICIPANTE: {$p2[3]} elementos.\n"
                . "CRP´s. PARTICIPANTES: {$p2[4]}\n"
                . "KILÓMETROS RECORRIDOS: {$p2[5]}\n\n"
                . "DISPOSITIVO CINTURÓN: {$p2[6]}\n"
                . "VEHÍCULOS IMPACTADOS: {$p2[7]}\n"
                . "PERSONAS IMPACTADAS: {$p2[8]}\n"
                . "ESTADO DE FUERZA PARTICIPANTE: {$p2[9]} elementos.\n"
                . "CRP´s. PARTICIPANTES: {$p2[10]}\n"
                . "KILÓMETROS RECORRIDOS: {$p2[11]}\n\n"
                . "DISPOSITIVO CARRUSEL: {$p2[12]}\n"
                . "VEHÍCULOS IMPACTADOS: {$p2[13]}\n"
                . "ESTADO DE FUERZA PARTICIPANTE: {$p2[14]} elementos.\n"
                . "CRP´s. PARTICIPANTES: {$p2[15]}\n"
                . "KILÓMETROS RECORRIDOS: {$p2[16]}\n\n"
                . "CORDILLERA: {$p2[17]}\n"
                . "VEHÍCULOS IMPACTADOS: {$p2[18]}\n"
                . "PERSONAS IMPACTADAS: {$p2[19]}\n"
                . "ESTADO DE FUERZA PARTICIPANTE: {$p2[20]} elementos.\n"
                . "CRP´s. PARTICIPANTES: {$p2[21]}\n"
                . "KILÓMETROS RECORRIDOS: {$p2[22]}\n\n"
                . "DISPOSITIVO ASIENTO SEGURO PASAJEROS MENORES: {$p2[23]}\n"
                . "VEHÍCULOS IMPACTADOS: {$p2[24]}\n"
                . "PERSONAS IMPACTADAS: {$p2[25]}\n"
                . "ESTADO DE FUERZA PARTICIPANTE: {$p2[26]} elementos.\n"
                . "CRP´s. PARTICIPANTES: {$p2[27]}\n"
                . "KILÓMETROS RECORRIDOS: {$p2[28]}",

            "CABALLEROS DEL CAMINO: {$p3[0]}\n"
                . "• ACOMPAÑAMIENTOS (ESCOLTAS, CARAVANAS, EMERGENCIAS, OTROS): {$p3[1]}\n"
                . "• ABANDERAMIENTOS (HECHOS DE TRÁNSITO, EVENTOS, OTROS): {$p3[2]}\n"
                . "• AUXILIOS VIALES (FALLAS MECÁNICAS, PEATÓN, OTROS): {$p3[3]}\n"
                . "ESTADO DE FUERZA PARTICIPANTE: {$p3[4]} elementos.\n"
                . "CRP´s. PARTICIPANTES: {$p3[5]}\n"
                . "KILÓMETROS RECORRIDOS: {$p3[6]}\n\n"
                . "PROXIMIDAD SOCIAL\n"
                . "- EMPRESAS: {$p3[7]}\n"
                . "- TIENDAS DE CONVENIENCIA: {$p3[8]}\n"
                . "- ESCUELAS: {$p3[9]}\n"
                . "- HOSPITALES: {$p3[10]}\n\n"
                . "TOTALES:\n\n"
                . "INSPECCIONES DE PERSONAS Y/O VEHÍCULOS: {$p3[11]}\n"
                . "ANTECEDENTES DE PERSONAS: {$p3[12]}\n"
                . "ANTECEDENTES DE VEHÍCULOS: {$p3[13]}\n"
                . "ANTECEDENTES DE MOTOS: {$p3[14]}\n"
                . "ANTECEDENTES DE CAMIONES: {$p3[15]}\n\n"
                . "PUESTAS A DISPOSICIÓN: {$p3[16]}\n"
                . "• VEHÍCULOS RECUPERADOS: {$p3[17]}\n"
                . "• ARMAS ASEGURADAS: {$p3[18]}\n"
                . "• MERCANCÍA RECUPERADA: {$p3[19]}\n"
                . "• DECOMISO DE DROGAS: {$p3[20]}\n\n"
                . "ESTADO DE FUERZA PARTICIPANTE: {$p3[21]} elementos.\n"
                . "CRP´s. PARTICIPANTES: {$p3[22]}\n"
                . "KILÓMETROS RECORRIDOS: {$p3[23]}\n\n"
                . "SE ANEXAN GRÁFICAS.\n\n"
                . "RESPETUOSAMENTE.",
        ];
    }

    public function textChunks(string $mensaje, int $maxBodyChars = 3900): array
    {
        $chunks = $this->splitMessage($mensaje, $maxBodyChars);
        $total = count($chunks);

        if ($total <= 1) {
            return $chunks;
        }

        return array_map(function (string $chunk, int $index) use ($total) {
            return 'PARTE ' . ($index + 1) . ' DE ' . $total . "\n\n" . $chunk;
        }, $chunks, array_keys($chunks));
    }

    protected function resumenPorDispositivo(Carbon $inicio, Carbon $fin): Collection
    {
        if (!$this->tablasDisponibles()) {
            return collect();
        }

        $query = DB::table('operativo_dispositivos as od')
            ->leftJoin('operativo_dispositivo_catalogos as c', 'c.id', '=', 'od.operativo_dispositivo_catalogo_id')
            ->leftJoin('operativos as o', 'o.id', '=', 'od.operativo_id')
            ->leftJoin('operativo_catalogos as oc', 'oc.id', '=', 'o.operativo_catalogo_id')
            ->selectRaw('COALESCE(c.nombre, od.asunto, "SIN CATALOGO") as nombre')
            ->selectRaw('SUM(COALESCE(od.cantidad, 0)) as cantidad')
            ->selectRaw('SUM(COALESCE(od.vehiculos_inspeccionados, 0)) as vehiculos_inspeccionados')
            ->selectRaw('SUM(COALESCE(od.personas_inspeccionadas, 0)) as personas_inspeccionadas')
            ->selectRaw('SUM(COALESCE(od.vehiculos_impactados, 0)) as vehiculos_impactados')
            ->selectRaw('SUM(COALESCE(od.personas_impactadas, 0)) as personas_impactadas')
            ->selectRaw('SUM(COALESCE(od.estado_fuerza_participante, 0)) as estado_fuerza_participante')
            ->selectRaw('SUM(COALESCE(od.kilometros_recorridos, 0)) as kilometros_recorridos')
            ->selectRaw('SUM(COALESCE(od.acompanamientos, 0)) as acompanamientos')
            ->selectRaw('SUM(COALESCE(od.abanderamientos, 0)) as abanderamientos')
            ->selectRaw('SUM(COALESCE(od.auxilios_viales, 0)) as auxilios_viales')
            ->selectRaw('SUM(COALESCE(od.prox_empresas, 0)) as prox_empresas')
            ->selectRaw('SUM(COALESCE(od.prox_tiendas_conveniencia, 0)) as prox_tiendas_conveniencia')
            ->selectRaw('SUM(COALESCE(od.prox_escuelas, 0)) as prox_escuelas')
            ->selectRaw('SUM(COALESCE(od.prox_hospitales, 0)) as prox_hospitales')
            ->selectRaw('SUM(COALESCE(od.antecedentes_personas, 0)) as antecedentes_personas')
            ->selectRaw('SUM(COALESCE(od.antecedentes_vehiculos, 0)) as antecedentes_vehiculos')
            ->selectRaw('SUM(COALESCE(od.antecedentes_motos, 0)) as antecedentes_motos')
            ->selectRaw('SUM(COALESCE(od.antecedentes_camiones, 0)) as antecedentes_camiones')
            ->selectRaw('SUM(COALESCE(od.puestas_disposicion, 0)) as puestas_disposicion')
            ->selectRaw('SUM(COALESCE(od.vehiculos_recuperados, 0)) as vehiculos_recuperados')
            ->selectRaw('SUM(COALESCE(od.armas_aseguradas, 0)) as armas_aseguradas')
            ->selectRaw('SUM(COALESCE(od.mercancia_recuperada, 0)) as mercancia_recuperada')
            ->selectRaw('SUM(COALESCE(od.decomiso_drogas, 0)) as decomiso_drogas')
            ->selectRaw('GROUP_CONCAT(DISTINCT NULLIF(TRIM(COALESCE(od.crps_participantes, "")), "") SEPARATOR " | ") as crps_participantes')
            ->where(function ($q) {
                $slug = (string) config('guardianes_camino.operativo_slug', 'guardianes-del-camino');
                $unidadId = $this->unidadCarreterasId();

                $q->where('oc.slug', $slug);

                if ($unidadId > 0) {
                    $q->orWhere('od.unidad_org_id', $unidadId);
                }
            })
            ->groupByRaw('COALESCE(c.nombre, od.asunto, "SIN CATALOGO")')
            ->orderBy('nombre');

        if (Schema::hasColumn('operativo_dispositivos', 'estado_revision')) {
            $query->where('od.estado_revision', OperativoDispositivo::REVISION_APROBADO);
        }

        $this->aplicarRango($query, $inicio, $fin);

        return $query->get()
            ->map(fn ($row) => $this->rowToArray($row))
            ->keyBy(fn (array $row) => $this->norm($row['nombre']));
    }

    protected function aplicarRango($query, Carbon $inicio, Carbon $fin): void
    {
        $inicioStr = $inicio->format('Y-m-d H:i:s');
        $finStr = $fin->format('Y-m-d H:i:s');
        $campo = mb_strtolower(trim((string) config('services.whatsapp.carreteras_guardianes.rango_campo', 'created_at')), 'UTF-8');

        if ($campo === 'created_at' && Schema::hasColumn('operativo_dispositivos', 'created_at')) {
            $query->where('od.created_at', '>=', $inicioStr)
                ->where('od.created_at', '<', $finStr);

            return;
        }

        if (Schema::hasColumn('operativo_dispositivos', 'fecha') && Schema::hasColumn('operativo_dispositivos', 'hora')) {
            $query->whereRaw(
                "TIMESTAMP(od.fecha, COALESCE(NULLIF(od.hora, ''), '00:00:00')) >= ?",
                [$inicioStr]
            )->whereRaw(
                "TIMESTAMP(od.fecha, COALESCE(NULLIF(od.hora, ''), '00:00:00')) < ?",
                [$finStr]
            );

            return;
        }

        if (Schema::hasColumn('operativo_dispositivos', 'fecha')) {
            $query->whereBetween('od.fecha', [
                $inicio->toDateString(),
                $fin->copy()->subSecond()->toDateString(),
            ]);

            return;
        }

        if (Schema::hasColumn('operativo_dispositivos', 'created_at')) {
            $query->where('od.created_at', '>=', $inicioStr)
                ->where('od.created_at', '<', $finStr);
        }
    }

    protected function mensaje(Carbon $emitido, Collection $rows, array $totales): string
    {
        $psv = $this->combine($rows, ['PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)', 'PSV']);
        $rsv = $this->combine($rows, ['RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)', 'RSV', 'PATRULLAJE']);
        $casco = $this->combine($rows, ['CASCO', 'DISPOSITIVO CASCO']);
        $cinturon = $this->combine($rows, ['CINTURON', 'CINTURÓN', 'DISPOSITIVO CINTURON', 'DISPOSITIVO CINTURÓN']);
        $carrusel = $this->combine($rows, ['CARRUSEL', 'DISPOSITIVO CARRUSEL']);
        $cordillera = $this->combine($rows, ['CORDILLERA']);
        $asiento = $this->combine($rows, ['ASIENTO SEGURO PASAJEROS MENORES', 'DISPOSITIVO ASIENTO SEGURO PASAJEROS MENORES', 'ASIENTO SEGURO']);
        $caballeros = $this->combine($rows, [
            'CABALLEROS DEL CAMINO',
            'CABALLERO DEL CAMINO',
            'CABALLERO DEL CAMINO (PROXIMIDAD SOCIAL)',
            'ACOMPAÑAMIENTOS',
            'ABANDERAMIENTOS',
            'AUXILIOS VIALES',
        ]);
        $proximidad = $this->combine($rows, [
            'PROXIMIDAD SOCIAL',
            'CABALLEROS DEL CAMINO',
            'CABALLERO DEL CAMINO',
            'CABALLERO DEL CAMINO (PROXIMIDAD SOCIAL)',
        ]);

        $caballerosAcciones = (int) max(
            $caballeros['cantidad'],
            $caballeros['acompanamientos'] + $caballeros['abanderamientos'] + $caballeros['auxilios_viales']
        );

        $lineas = [];
        $lineas[] = 'GUARDIA CIVIL';
        $lineas[] = '';
        $lineas[] = 'COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL';
        $lineas[] = '';
        $lineas[] = 'UNIDAD DE PROTECCIÓN EN CARRETERAS';
        $lineas[] = '';
        $lineas[] = 'DESTACAMENTO ' . $this->destacamento();
        $lineas[] = '';
        $lineas[] = 'ASUNTO: CONSOLIDADO DE NOVEDADES DE ACTIVIDADES DIARIAS.';
        $lineas[] = '';
        $lineas[] = $emitido->format('d/m/Y') . '         ' . $emitido->format('H:i') . ' hs.';
        $lineas[] = '';
        $lineas[] = 'DESCRIPCIÓN GENERAL:';
        $lineas[] = 'OPERATIVO GUARDIANES DEL CAMINO';
        $lineas[] = $this->descripcionGeneral();
        $lineas[] = '';
        $lineas[] = '';
        $lineas[] = 'DISPOSITIVOS:';
        $lineas[] = '';

        $this->lineasInspeccion($lineas, 'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)', $psv);
        $this->lineasInspeccion($lineas, 'RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)', $rsv);
        $this->lineasImpacto($lineas, 'DISPOSITIVO CASCO', $casco, true);
        $this->lineasImpacto($lineas, 'DISPOSITIVO CINTURÓN', $cinturon, true);
        $this->lineasImpacto($lineas, 'DISPOSITIVO CARRUSEL', $carrusel, false);
        $this->lineasImpacto($lineas, 'CORDILLERA', $cordillera, true);
        $this->lineasImpacto($lineas, 'DISPOSITIVO ASIENTO SEGURO PASAJEROS MENORES', $asiento, true);

        $lineas[] = 'CABALLEROS DEL CAMINO: ' . $this->pad($caballerosAcciones);
        $lineas[] = '• ACOMPAÑAMIENTOS (ESCOLTAS, CARAVANAS, EMERGENCIAS, OTROS): ' . $this->pad($caballeros['acompanamientos']);
        $lineas[] = '• ABANDERAMIENTOS (HECHOS DE TRÁNSITO, EVENTOS, OTROS): ' . $this->pad($caballeros['abanderamientos']);
        $lineas[] = '• AUXILIOS VIALES (FALLAS MECÁNICAS, PEATÓN, OTROS): ' . $this->pad($caballeros['auxilios_viales']);
        $lineas[] = 'ESTADO DE FUERZA PARTICIPANTE: ' . $this->pad($caballeros['estado_fuerza_participante']) . ' elementos.';
        $lineas[] = 'CRP´s. PARTICIPANTES: ' . $this->crps($caballeros);
        $lineas[] = 'KILÓMETROS RECORRIDOS: ' . $this->km($caballeros['kilometros_recorridos']);
        $lineas[] = '';

        $lineas[] = 'PROXIMIDAD SOCIAL';
        $lineas[] = '- EMPRESAS: ' . $this->pad($proximidad['prox_empresas']);
        $lineas[] = '- TIENDAS DE CONVENIENCIA: ' . $this->pad($proximidad['prox_tiendas_conveniencia']);
        $lineas[] = '- ESCUELAS: ' . $this->pad($proximidad['prox_escuelas']);
        $lineas[] = '- HOSPITALES: ' . $this->pad($proximidad['prox_hospitales']);
        $lineas[] = '';

        $lineas[] = 'TOTALES:';
        $lineas[] = '';
        $lineas[] = 'INSPECCIONES DE PERSONAS Y/O VEHÍCULOS: ' . $this->pad($totales['vehiculos_inspeccionados'] + $totales['personas_inspeccionadas']);
        $lineas[] = 'ANTECEDENTES DE PERSONAS: ' . $this->pad($totales['antecedentes_personas']);
        $lineas[] = 'ANTECEDENTES DE VEHÍCULOS: ' . $this->pad($totales['antecedentes_vehiculos']);
        $lineas[] = 'ANTECEDENTES DE MOTOS: ' . $this->pad($totales['antecedentes_motos']);
        $lineas[] = 'ANTECEDENTES DE CAMIONES: ' . $this->pad($totales['antecedentes_camiones']);
        $lineas[] = '';
        $lineas[] = 'PUESTAS A DISPOSICIÓN: ' . $this->pad($totales['puestas_disposicion']);
        $lineas[] = '• VEHÍCULOS RECUPERADOS: ' . $this->pad($totales['vehiculos_recuperados']);
        $lineas[] = '• ARMAS ASEGURADAS: ' . $this->pad($totales['armas_aseguradas']);
        $lineas[] = '• MERCANCÍA RECUPERADA: ' . $this->pad($totales['mercancia_recuperada']);
        $lineas[] = '• DECOMISO DE DROGAS: ' . $this->pad($totales['decomiso_drogas']);
        $lineas[] = '';
        $lineas[] = 'ESTADO DE FUERZA PARTICIPANTE: ' . $this->pad($totales['estado_fuerza_participante']) . ' elementos.';
        $lineas[] = 'CRP´s. PARTICIPANTES: ' . $this->crps($totales);
        $lineas[] = 'KILÓMETROS RECORRIDOS: ' . $this->km($totales['kilometros_recorridos']);
        $lineas[] = '';
        $lineas[] = 'SE ANEXAN GRÁFICAS.';
        $lineas[] = '';
        $lineas[] = '';
        $lineas[] = 'RESPETUOSAMENTE.';

        return implode("\n", $lineas);
    }

    protected function lineasInspeccion(array &$lineas, string $titulo, array $row): void
    {
        $lineas[] = $titulo . ': ' . $this->pad($row['cantidad']);
        $lineas[] = 'VEHÍCULOS INSPECCIONADOS: ' . $this->pad($row['vehiculos_inspeccionados']);
        $lineas[] = 'PERSONAS INSPECCIONADAS: ' . $this->pad($row['personas_inspeccionadas']);
        $lineas[] = 'ESTADO DE FUERZA PARTICIPANTE: ' . $this->pad($row['estado_fuerza_participante']) . ' elementos.';
        $lineas[] = 'CRP´s. PARTICIPANTES: ' . $this->crps($row);
        $lineas[] = 'KILÓMETROS RECORRIDOS: ' . $this->km($row['kilometros_recorridos']);
        $lineas[] = '';
    }

    protected function lineasImpacto(array &$lineas, string $titulo, array $row, bool $incluyePersonas): void
    {
        $lineas[] = $titulo . ': ' . $this->pad($row['cantidad']);
        $lineas[] = 'VEHÍCULOS IMPACTADOS: ' . $this->pad($row['vehiculos_impactados']);

        if ($incluyePersonas) {
            $lineas[] = 'PERSONAS IMPACTADAS: ' . $this->pad($row['personas_impactadas']);
        }

        $lineas[] = 'ESTADO DE FUERZA PARTICIPANTE: ' . $this->pad($row['estado_fuerza_participante']) . ' elementos.';
        $lineas[] = 'CRP´s. PARTICIPANTES: ' . $this->crps($row);
        $lineas[] = 'KILÓMETROS RECORRIDOS: ' . $this->km($row['kilometros_recorridos']);
        $lineas[] = '';
    }

    protected function combine(Collection $rows, array $aliases): array
    {
        $aliases = array_flip(array_map(fn ($alias) => $this->norm($alias), $aliases));
        $combined = $this->blankTotals();

        foreach ($rows as $key => $row) {
            if (!isset($aliases[$this->norm((string) $key)]) && !isset($aliases[$this->norm($row['nombre'] ?? '')])) {
                continue;
            }

            $combined = $this->addRows($combined, $row);
        }

        return $combined;
    }

    protected function totalesGenerales(Collection $rows): array
    {
        $totales = $this->blankTotals();

        foreach ($rows as $row) {
            $totales = $this->addRows($totales, $row);
        }

        return $totales;
    }

    protected function addRows(array $base, array $row): array
    {
        foreach (self::DEVICE_FIELDS as $field) {
            $base[$field] = ($base[$field] ?? 0) + ($row[$field] ?? 0);
        }

        $base['crps_participantes'] = $this->mergeCrps(
            (string) ($base['crps_participantes'] ?? ''),
            (string) ($row['crps_participantes'] ?? '')
        );

        return $base;
    }

    protected function blankTotals(): array
    {
        $totals = ['nombre' => '', 'crps_participantes' => ''];

        foreach (self::DEVICE_FIELDS as $field) {
            $totals[$field] = $field === 'kilometros_recorridos' ? 0.0 : 0;
        }

        return $totals;
    }

    protected function rowToArray($row): array
    {
        $out = $this->blankTotals();
        $out['nombre'] = trim((string) ($row->nombre ?? ''));
        $out['crps_participantes'] = trim((string) ($row->crps_participantes ?? ''));

        foreach (self::DEVICE_FIELDS as $field) {
            $out[$field] = $field === 'kilometros_recorridos'
                ? (float) ($row->{$field} ?? 0)
                : (int) ($row->{$field} ?? 0);
        }

        return $out;
    }

    protected function mergeCrps(string ...$values): string
    {
        $items = [];

        foreach ($values as $value) {
            foreach (preg_split('/[\|,;\n]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
                $part = preg_replace('/\s+/', ' ', trim($part)) ?? trim($part);

                if ($part === '') {
                    continue;
                }

                $items[$this->norm($part)] = $part;
            }
        }

        return implode(', ', array_values($items));
    }

    protected function splitMessage(string $mensaje, int $maxBodyChars): array
    {
        $maxBodyChars = max(500, $maxBodyChars);
        $mensaje = trim(str_replace(["\r\n", "\r"], "\n", $mensaje));

        if ($mensaje === '') {
            return [''];
        }

        if (mb_strlen($mensaje, 'UTF-8') <= $maxBodyChars) {
            return [$mensaje];
        }

        $chunks = [];
        $current = '';
        $blocks = preg_split("/\n{2,}/", $mensaje) ?: [$mensaje];

        foreach ($blocks as $block) {
            $block = trim($block);

            if ($block === '') {
                continue;
            }

            foreach ($this->splitBlock($block, $maxBodyChars) as $piece) {
                $candidate = $current === ''
                    ? $piece
                    : $current . "\n\n" . $piece;

                if (mb_strlen($candidate, 'UTF-8') <= $maxBodyChars) {
                    $current = $candidate;
                    continue;
                }

                if ($current !== '') {
                    $chunks[] = $current;
                }

                $current = $piece;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks ?: [''];
    }

    protected function splitBlock(string $block, int $maxBodyChars): array
    {
        if (mb_strlen($block, 'UTF-8') <= $maxBodyChars) {
            return [$block];
        }

        $pieces = [];
        $current = '';
        $lines = preg_split("/\n/", $block) ?: [$block];

        foreach ($lines as $line) {
            foreach ($this->splitLine($line, $maxBodyChars) as $linePiece) {
                $candidate = $current === ''
                    ? $linePiece
                    : $current . "\n" . $linePiece;

                if (mb_strlen($candidate, 'UTF-8') <= $maxBodyChars) {
                    $current = $candidate;
                    continue;
                }

                if ($current !== '') {
                    $pieces[] = $current;
                }

                $current = $linePiece;
            }
        }

        if ($current !== '') {
            $pieces[] = $current;
        }

        return $pieces;
    }

    protected function splitLine(string $line, int $maxBodyChars): array
    {
        if (mb_strlen($line, 'UTF-8') <= $maxBodyChars) {
            return [$line];
        }

        $pieces = [];
        $remaining = trim($line);

        while (mb_strlen($remaining, 'UTF-8') > $maxBodyChars) {
            $slice = mb_substr($remaining, 0, $maxBodyChars, 'UTF-8');
            $breakAt = mb_strrpos($slice, ' ', 0, 'UTF-8');

            if ($breakAt === false || $breakAt < (int) floor($maxBodyChars * 0.6)) {
                $breakAt = $maxBodyChars;
            }

            $pieces[] = trim(mb_substr($remaining, 0, $breakAt, 'UTF-8'));
            $remaining = trim(mb_substr($remaining, $breakAt, null, 'UTF-8'));
        }

        if ($remaining !== '') {
            $pieces[] = $remaining;
        }

        return $pieces;
    }

    protected function templateParameterText(string $text): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $text));
    }

    protected function pad($value): string
    {
        return str_pad((string) (int) $value, 2, '0', STR_PAD_LEFT);
    }

    protected function km($value): string
    {
        $value = (float) $value;

        if (abs($value - round($value)) < 0.005) {
            return $this->pad((int) round($value));
        }

        return number_format($value, 2, '.', '');
    }

    protected function crps(array $row): string
    {
        $crps = trim((string) ($row['crps_participantes'] ?? ''));

        if ($crps === '') {
            return '00';
        }

        if (preg_match('/^\d{1,2}\s*\(/', $crps)) {
            return $crps;
        }

        return $this->pad($this->contarCrps($crps)) . ' (' . $crps . ')';
    }

    protected function contarCrps(string $crps): int
    {
        $parts = preg_split('/[\|,;\/]+|\s+y\s+/iu', $crps, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts)));

        return max(1, count($parts));
    }

    protected function descripcionGeneral(): string
    {
        $default = 'EN TRAMOS CARRETEROS DE LOS MUNICIPIOS: (Aeropuerto, Zinapécuaro, Queréndaro, Indaparapeo, Charo y Morelia La Cinta Texticuitzeo).';

        return trim((string) config('services.whatsapp.carreteras_guardianes.descripcion_general', $default)) ?: $default;
    }

    protected function destacamento(): string
    {
        $destacamento = trim((string) config('services.whatsapp.carreteras_guardianes.destacamento', 'MORELIA'));

        return mb_strtoupper($destacamento !== '' ? $destacamento : 'MORELIA', 'UTF-8');
    }

    protected function unidadCarreterasId(): int
    {
        return (int) config('services.whatsapp.carreteras_guardianes.unidad_id', 4);
    }

    protected function horaCorte(): array
    {
        $configured = (string) config('cortes.hora_corte_carreteras', '17:00:00');

        if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', trim($configured), $matches)) {
            return [17, 0, 0];
        }

        $hora = (int) $matches[1];
        $minuto = (int) $matches[2];
        $segundo = isset($matches[3]) ? (int) $matches[3] : 0;

        if ($hora < 0 || $hora > 23 || $minuto < 0 || $minuto > 59 || $segundo < 0 || $segundo > 59) {
            return [17, 0, 0];
        }

        return [$hora, $minuto, $segundo];
    }

    protected function tablasDisponibles(): bool
    {
        try {
            return Schema::hasTable('operativo_dispositivos')
                && Schema::hasTable('operativo_dispositivo_catalogos')
                && Schema::hasTable('operativos')
                && Schema::hasTable('operativo_catalogos');
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function emitido(?Carbon $corte = null): Carbon
    {
        $timezone = $this->timezone();

        return $corte
            ? $corte->copy()->timezone($timezone)
            : Carbon::now($timezone);
    }

    protected function timezone(): string
    {
        return (string) config('app.schedule_timezone', config('app.timezone', 'America/Mexico_City'));
    }

    protected function norm(string $value): string
    {
        $value = mb_strtoupper(trim($value), 'UTF-8');
        $value = $this->sinAcentos($value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return $value;
    }

    protected function sinAcentos(string $value): string
    {
        return strtr($value, [
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A', 'Â' => 'A',
            'É' => 'E', 'È' => 'E', 'Ë' => 'E', 'Ê' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Ï' => 'I', 'Î' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'O', 'Ô' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'U', 'Û' => 'U',
            'Ñ' => 'N',
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
        ]);
    }
}
