<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ResumenTodasUnidadesWhatsAppService
{
    private const TZ = 'America/Mexico_City';

    public function generar(?Carbon $corte = null): array
    {
        [$inicio, $fin] = $this->rango($corte);
        $totales = $this->totales($inicio, $fin);
        $mensaje = $this->mensaje($fin, $totales);

        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'totales' => $totales,
            'mensaje' => $mensaje,
            'template_params' => $this->templateParams($fin, $totales),
            'template_chunks' => $this->whatsAppTemplateChunks($mensaje),
        ];
    }

    public function rango(?Carbon $corte = null): array
    {
        $base = $corte ? $corte->copy()->timezone(self::TZ) : Carbon::now(self::TZ);
        $corteHoy = $base->copy()->setTime(19, 0, 0);

        $fin = $base->greaterThanOrEqualTo($corteHoy)
            ? $corteHoy
            : $corteHoy->copy()->subDay();

        return [$fin->copy()->subDay(), $fin];
    }

    public function totales(Carbon $inicio, Carbon $fin): array
    {
        $siniestros = $this->siniestros($inicio, $fin);
        $corralon = $this->vehiculosCorralonSiniestros($inicio, $fin);
        $operativos = $this->operativos($inicio, $fin);

        return [
            'vehiculos_mp' => $this->vehiculosMp($inicio, $fin),
            'siniestros' => $siniestros,
            'corralon_vehiculos' => $corralon['vehiculos'],
            'corralon_motocicletas' => $corralon['motocicletas'],

            'apoyo_evento_deportivo' => $this->actividadCount('INSTITUCIONES', 'APOYO A EVENTOS DEPORTIVOS', $inicio, $fin),
            'apoyo_evento_cultural' => $this->actividadCount('INSTITUCIONES', 'APOYO A EVENTOS CULTURALES', $inicio, $fin),
            'apoyo_evento_religioso' => $this->actividadCount('INSTITUCIONES', 'APOYO A EVENTOS RELIGIOSOS', $inicio, $fin),

            'c5i_siniestros' => $siniestros['total'],
            'c5i_concentracion_personas' => $this->actividadCount('REPORTES C5i', 'CONSENTRACION PERSONAS', $inicio, $fin),

            'abanderamientos_cortes' => $this->actividadCount('ABANDERAMIENTOS', 'CORTES DE CIRCULACION', $inicio, $fin),
            'abanderamientos_accidentes' => $this->actividadCount('ABANDERAMIENTOS', 'ACCIDENTES', $inicio, $fin),
            'abanderamientos_obras' => $this->actividadCount('ABANDERAMIENTOS', 'OBRAS PUBLICAS', $inicio, $fin),
            'abanderamientos_otros' => $this->actividadCountExcept(
                'ABANDERAMIENTOS',
                ['CORTES DE CIRCULACION', 'ACCIDENTES', 'OBRAS PUBLICAS'],
                $inicio,
                $fin
            ),

            'monitoreos_vias_ferreas' => $this->actividadCount('MONITOREOS', 'VIAS FERREAS', $inicio, $fin),
            'monitoreos_perifericos' => $this->actividadCount('MONITOREOS', 'PERIFERICOS', $inicio, $fin),
            'monitoreos_avenidas' => $this->actividadCount('MONITOREOS', 'AVENIDAS', $inicio, $fin),
            'monitoreos_tiendas' => $this->actividadCount('MONITOREOS', 'TIENDAS DEPARTAMENTALES', $inicio, $fin),
            'monitoreos_bancos' => $this->actividadCount('MONITOREOS', 'BANCOS', $inicio, $fin),
            'monitoreos_gasolineras' => $this->actividadCount('MONITOREOS', 'GASOLINERAS', $inicio, $fin),
            'monitoreos_oficinas' => $this->actividadCount('MONITOREOS', 'OFICINAS GUBERNAMENTALES', $inicio, $fin),
            'monitoreos_manifestaciones' => $this->actividadCount('MONITOREOS', 'MANIFESTACIONES', $inicio, $fin),
            'monitoreos_otros' => $this->actividadCountExcept(
                'MONITOREOS',
                ['VIAS FERREAS', 'PERIFERICOS', 'AVENIDAS', 'TIENDAS DEPARTAMENTALES', 'BANCOS', 'GASOLINERAS', 'OFICINAS GUBERNAMENTALES', 'MANIFESTACIONES'],
                $inicio,
                $fin
            ),

            'auxilio_fallas_mecanicas' => $this->actividadCount('AUXILIO VIAL A CONDUCTORES', 'FALLAS MECANICAS', $inicio, $fin),

            'dsv_apoyos_vialidad' => $this->actividadCount('DISPOSITIVOS DE SEGURIDAD VIAL', 'APOYO A LA VIALIDAD', $inicio, $fin),
            'dsv_zonas_transeuntes' => $this->actividadCount('DISPOSITIVOS DE SEGURIDAD VIAL', 'ZONAS DE MAYOR PASE DE TRANSEUNTES', $inicio, $fin),
            'dsv_pasos_peatonales' => $this->actividadCount('DISPOSITIVOS DE SEGURIDAD VIAL', 'PASOS PEATONALES', $inicio, $fin),
            'dsv_patrullajes' => $this->actividadCount('DISPOSITIVOS DE SEGURIDAD VIAL', 'PATRULLAJES', $inicio, $fin),
            'dsv_otros' => $this->actividadCountExcept(
                'DISPOSITIVOS DE SEGURIDAD VIAL',
                ['APOYO A LA VIALIDAD', 'ZONAS DE MAYOR PASE DE TRANSEUNTES', 'PASOS PEATONALES', 'PATRULLAJES'],
                $inicio,
                $fin
            ),

            'concientizacion_talleres' => $this->actividadCount('CAPACITACIONES', 'TALLER EDUCACION SEGURIDAD VIAL', $inicio, $fin),
            'concientizacion_campanas' => $this->actividadCount('CAPACITACIONES', 'CAMPAÑA EDUCACION SEGURIDAD VIAL', $inicio, $fin),
            'concientizacion_capacitaciones' => $this->actividadCount('CAPACITACIONES', 'CAPACITACIONES EDUCACION SEGURIDAD VIAL', $inicio, $fin),
            'concientizacion_modulos' => $this->actividadCount('CAPACITACIONES', 'MODULOS EDUCACION SEGURIDAD VIAL', $inicio, $fin),
            'concientizacion_personas' => $this->actividadPeople('CAPACITACIONES', null, $inicio, $fin),

            'campanas_concientizacion' => $this->actividadCount('CAMPAÑAS', 'CONCIENTIZACION Y PREVENCION', $inicio, $fin),
            'campanas_tripticos' => $this->actividadCount('CAMPAÑAS', 'REPARTICION DE TRIPTICOS', $inicio, $fin),
            'campanas_recomendaciones' => $this->actividadCountLike('CAMPAÑAS', 'RECOMEND', $inicio, $fin),
            'campanas_personas' => $this->actividadPeople('CAMPAÑAS', null, $inicio, $fin),

            'proximidad_recorridos' => $this->actividadCount('PROXIMIDAD SOCIAL', 'RECORRIDOS DE PROXIMIDAD', $inicio, $fin),
            'proximidad_adulto_mayor' => $this->actividadCount('PROXIMIDAD SOCIAL', 'APOYO A PERSONAS DE LA TERCERA EDAD', $inicio, $fin),

            'operativos' => $operativos['items'],
            'inspecciones_personas' => $operativos['inspecciones_personas'],
            'inspecciones_vehiculos' => $operativos['inspecciones_vehiculos'],
            'inspecciones_motocicletas' => $operativos['inspecciones_motocicletas'],
        ];
    }

    public function whatsAppTemplateChunks(string $mensaje, int $maxBodyChars = 30000): array
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
                    $chunk,
                ],
            ];
        }

        return $out;
    }

    public function whatsAppTextChunks(string $mensaje, int $maxBodyChars = 3900): array
    {
        $chunks = $this->splitMessage($mensaje, $maxBodyChars);
        $total = count($chunks);

        if ($total <= 1) {
            return $chunks;
        }

        return array_map(function (string $chunk, int $index) use ($total) {
            return 'Parte ' . ($index + 1) . ' de ' . $total . "\n\n" . $chunk;
        }, $chunks, array_keys($chunks));
    }

    protected function mensaje(Carbon $fin, array $t): string
    {
        $params = $this->templateParams($fin, $t);

        $lineas = [];
        $lineas[] = $params[0] . '.';
        $lineas[] = '';
        $lineas[] = 'COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL';
        $lineas[] = '';
        $lineas[] = 'ASEGURAMIENTOS PUESTOS A DISPOSICIÓN DE LA FISCALÍA GENERAL DEL ESTADO:';
        $lineas[] = $params[1];
        $lineas[] = '';
        $lineas[] = 'SINIESTROS DE TRÁNSITO:';
        $lineas[] = $params[2];
        $lineas[] = '';
        $lineas[] = 'APOYO A INSTITUCIONES:';
        $lineas[] = $params[3];
        $lineas[] = '';
        $lineas[] = 'ATENCIÓN DE REPORTES DE C5i:';
        $lineas[] = $params[4];
        $lineas[] = '';
        $lineas[] = 'ABANDERAMIENTOS VIALES:';
        $lineas[] = $params[5];
        $lineas[] = '';
        $lineas[] = 'MONITOREOS:';
        $lineas[] = $params[6];
        $lineas[] = '';
        $lineas[] = 'AUXILIO VIAL A CONDUCTORES:';
        $lineas[] = $params[7];
        $lineas[] = '';
        $lineas[] = 'DISPOSITIVOS DE SEGURIDAD VIAL:';
        $lineas[] = $params[8];
        $lineas[] = '';
        $lineas[] = 'ACCIONES DE CONCIENTIZACIÓN VIAL:';
        $lineas[] = $params[9];
        $lineas[] = '';
        $lineas[] = 'CAMPAÑAS:';
        $lineas[] = $params[10];
        $lineas[] = '';
        $lineas[] = 'PROXIMIDAD SOCIAL:';
        $lineas[] = $params[11];
        $lineas[] = '';
        $lineas[] = 'SEGUNDO APARTADO DE TABLA G1';
        $lineas[] = 'OPERATIVOS:';
        $lineas[] = $params[12];
        $lineas[] = '';
        $lineas[] = 'INSPECCIONES:';
        $lineas[] = $params[13];
        $lineas[] = '';
        $lineas[] = 'Para conocimiento de la superioridad.';

        return implode("\n", $lineas);
    }

    protected function templateParams(Carbon $fin, array $t): array
    {
        $s = $t['siniestros'];
        $operativos = '(00) sin operativos.';

        if (!empty($t['operativos'])) {
            $lineasOperativos = [];

            foreach ($t['operativos'] as $operativo) {
                $lineasOperativos[] = '(' . $this->pad($operativo['cantidad']) . ') ' . $operativo['nombre']
                    . ', (' . $this->pad($operativo['guardias']) . ') Guardias Civiles y ('
                    . $this->pad($operativo['crp']) . ') CRP.';
            }

            $operativos = implode("\n", $lineasOperativos);
        }

        return [
            mb_strtoupper($fin->copy()->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y'), 'UTF-8'),
            '(' . $this->pad($t['vehiculos_mp']) . ') ' . $this->plural($t['vehiculos_mp'], 'vehículo', 'vehículos') . ' por siniestro de tránsito.',
            '(' . $this->pad($s['total']) . ') en total; '
                . '(' . $this->pad($s['resueltos']) . ') ' . $this->plural($s['resueltos'], 'siniestro resuelto', 'siniestros resueltos') . ', '
                . '(' . $this->pad($s['pendientes']) . ') ' . $this->plural($s['pendientes'], 'siniestro pendiente', 'siniestros pendientes') . ', '
                . '(' . $this->pad($s['turnados']) . ') ' . $this->plural($s['turnados'], 'siniestro turnado', 'siniestros turnados') . '; '
                . '(' . $this->pad($s['lesionados']) . ') ' . $this->plural($s['lesionados'], 'persona lesionada', 'personas lesionadas') . ', '
                . '(' . $this->pad($t['corralon_vehiculos']) . ') ' . $this->plural($t['corralon_vehiculos'], 'vehículo', 'vehículos') . ' y '
                . '(' . $this->pad($t['corralon_motocicletas']) . ') ' . $this->plural($t['corralon_motocicletas'], 'motocicleta', 'motocicletas') . ' al corralón.',
            $this->formatItems([
                [$t['apoyo_evento_deportivo'], 'apoyo a evento deportivo', 'apoyos a eventos deportivos'],
                [$t['apoyo_evento_cultural'], 'apoyo a evento cultural', 'apoyos a eventos culturales'],
                [$t['apoyo_evento_religioso'], 'apoyo a evento religioso', 'apoyos a eventos religiosos'],
            ]),
            $this->formatItems([
                [$t['c5i_siniestros'], 'siniestro de tránsito', 'siniestros de tránsito'],
                [$t['c5i_concentracion_personas'], 'concentración de personas', 'concentraciones de personas'],
            ]),
            $this->formatItems([
                [$t['abanderamientos_cortes'], 'corte de circulación', 'cortes de circulación'],
                [$t['abanderamientos_accidentes'], 'accidente', 'accidentes'],
                [$t['abanderamientos_obras'], 'obra pública', 'obras públicas'],
                [$t['abanderamientos_otros'], 'otro abanderamiento', 'otros abanderamientos'],
            ]),
            $this->formatItems([
                [$t['monitoreos_vias_ferreas'], 'vía férrea', 'vías férreas'],
                [$t['monitoreos_perifericos'], 'periférico', 'periféricos'],
                [$t['monitoreos_avenidas'], 'avenida', 'avenidas'],
                [$t['monitoreos_tiendas'], 'tienda departamental', 'tiendas departamentales'],
                [$t['monitoreos_bancos'], 'institución bancaria', 'instituciones bancarias'],
                [$t['monitoreos_gasolineras'], 'gasolinera', 'gasolineras'],
                [$t['monitoreos_oficinas'], 'oficina gubernamental', 'oficinas gubernamentales'],
                [$t['monitoreos_manifestaciones'], 'manifestación', 'manifestaciones'],
                [$t['monitoreos_otros'], 'otro monitoreo', 'otros monitoreos'],
            ]),
            $this->formatItems([
                [$t['auxilio_fallas_mecanicas'], 'falla mecánica', 'fallas mecánicas'],
            ]),
            $this->formatItems([
                [$t['dsv_apoyos_vialidad'], 'apoyo a la vialidad', 'apoyos a la vialidad'],
                [$t['dsv_zonas_transeuntes'], 'zona de mayor pase de transeúntes', 'zonas de mayor pase de transeúntes'],
                [$t['dsv_pasos_peatonales'], 'paso y/o cruce peatonal', 'pasos y/o cruces peatonales'],
                [$t['dsv_patrullajes'], 'patrullaje', 'patrullajes'],
                [$t['dsv_otros'], 'otro dispositivo', 'otros dispositivos'],
            ]),
            $this->formatItems([
                [$t['concientizacion_talleres'], 'taller de educación en seguridad vial', 'talleres de educación en seguridad vial'],
                [$t['concientizacion_campanas'], 'campaña de educación en seguridad vial', 'campañas de educación en seguridad vial'],
                [$t['concientizacion_capacitaciones'], 'capacitación de educación en seguridad vial', 'capacitaciones de educación en seguridad vial'],
                [$t['concientizacion_modulos'], 'módulo de educación en seguridad vial', 'módulos de educación en seguridad vial'],
                [$t['concientizacion_personas'], 'persona sensibilizada', 'personas sensibilizadas'],
            ]),
            $this->formatItems([
                [$t['campanas_concientizacion'], 'concientización y prevención', 'concientización y prevención'],
                [$t['campanas_tripticos'], 'repartición de trípticos', 'repartición de trípticos'],
                [$t['campanas_recomendaciones'], 'recomendación a persona en siniestro de tránsito', 'recomendaciones a personas en siniestros de tránsito'],
                [$t['campanas_personas'], 'persona sensibilizada', 'personas sensibilizadas'],
            ]),
            $this->formatItems([
                [$t['proximidad_recorridos'], 'recorrido de proximidad', 'recorridos de proximidad'],
                [$t['proximidad_adulto_mayor'], 'apoyo a adulto mayor', 'apoyos a adultos mayores'],
            ]),
            $operativos,
            $this->formatItems([
                [$t['inspecciones_personas'], 'a persona', 'a personas'],
                [$t['inspecciones_vehiculos'], 'de vehículo', 'de vehículos'],
                [$t['inspecciones_motocicletas'], 'de motocicleta', 'de motocicletas'],
            ]),
        ];
    }

    protected function actividadCount(string $categoria, string $subcategoria, Carbon $inicio, Carbon $fin): int
    {
        if (!$this->tablaExiste('actividades') || !$this->tablaExiste('actividad_categorias') || !$this->tablaExiste('actividad_subcategorias')) {
            return 0;
        }

        $query = DB::table('actividades')
            ->join('actividad_categorias', 'actividad_categorias.id', '=', 'actividades.actividad_categoria_id')
            ->join('actividad_subcategorias', 'actividad_subcategorias.id', '=', 'actividades.actividad_subcategoria_id')
            ->where('actividad_categorias.slug', $this->slug($categoria))
            ->where('actividad_subcategorias.slug', $this->slug($subcategoria));

        $this->aplicarRango($query, 'actividades', 'actividades', $inicio, $fin);

        return (int) $query->sum(DB::raw('COALESCE(actividades.cantidad, 0)'));
    }

    protected function actividadCountExcept(
        string $categoria,
        array $subcategoriasVisibles,
        Carbon $inicio,
        Carbon $fin
    ): int {
        if (!$this->tablaExiste('actividades') || !$this->tablaExiste('actividad_categorias') || !$this->tablaExiste('actividad_subcategorias')) {
            return 0;
        }

        $query = DB::table('actividades')
            ->join('actividad_categorias', 'actividad_categorias.id', '=', 'actividades.actividad_categoria_id')
            ->join('actividad_subcategorias', 'actividad_subcategorias.id', '=', 'actividades.actividad_subcategoria_id')
            ->where('actividad_categorias.slug', $this->slug($categoria))
            ->whereNotIn('actividad_subcategorias.slug', array_map(
                fn (string $subcategoria) => $this->slug($subcategoria),
                $subcategoriasVisibles
            ));

        $this->aplicarRango($query, 'actividades', 'actividades', $inicio, $fin);

        return (int) $query->sum(DB::raw('COALESCE(actividades.cantidad, 0)'));
    }

    protected function actividadCountLike(string $categoria, string $needle, Carbon $inicio, Carbon $fin): int
    {
        if (!$this->tablaExiste('actividades') || !$this->tablaExiste('actividad_categorias')) {
            return 0;
        }

        $like = '%' . mb_strtoupper($needle, 'UTF-8') . '%';

        $query = DB::table('actividades')
            ->join('actividad_categorias', 'actividad_categorias.id', '=', 'actividades.actividad_categoria_id')
            ->leftJoin('actividad_subcategorias', 'actividad_subcategorias.id', '=', 'actividades.actividad_subcategoria_id')
            ->where('actividad_categorias.slug', $this->slug($categoria))
            ->where(function ($q) use ($like) {
                $q->whereRaw('UPPER(COALESCE(actividad_subcategorias.nombre, "")) LIKE ?', [$like])
                    ->orWhereRaw('UPPER(COALESCE(actividades.motivo, "")) LIKE ?', [$like])
                    ->orWhereRaw('UPPER(COALESCE(actividades.narrativa, "")) LIKE ?', [$like])
                    ->orWhereRaw('UPPER(COALESCE(actividades.acciones_realizadas, "")) LIKE ?', [$like])
                    ->orWhereRaw('UPPER(COALESCE(actividades.observaciones, "")) LIKE ?', [$like]);
            });

        $this->aplicarRango($query, 'actividades', 'actividades', $inicio, $fin);

        return (int) $query->sum(DB::raw('COALESCE(actividades.cantidad, 0)'));
    }

    protected function actividadPeople(string $categoria, ?string $subcategoria, Carbon $inicio, Carbon $fin): int
    {
        if (!$this->tablaExiste('actividades') || !$this->tablaExiste('actividad_categorias')) {
            return 0;
        }

        $query = DB::table('actividades')
            ->join('actividad_categorias', 'actividad_categorias.id', '=', 'actividades.actividad_categoria_id')
            ->where('actividad_categorias.slug', $this->slug($categoria));

        if ($this->tablaExiste('fomento_cultura_vial_detalles')) {
            $query->leftJoin('fomento_cultura_vial_detalles as fomento', 'fomento.actividad_id', '=', 'actividades.id');
        }

        if ($subcategoria !== null && $this->tablaExiste('actividad_subcategorias')) {
            $query->join('actividad_subcategorias', 'actividad_subcategorias.id', '=', 'actividades.actividad_subcategoria_id')
                ->where('actividad_subcategorias.slug', $this->slug($subcategoria));
        }

        $this->aplicarRango($query, 'actividades', 'actividades', $inicio, $fin);

        $personasExpr = $this->tablaExiste('fomento_cultura_vial_detalles')
            ? 'COALESCE(NULLIF(fomento.total_poblacion_atendida, 0), actividades.personas_alcanzadas, 0)'
            : 'COALESCE(actividades.personas_alcanzadas, 0)';

        return (int) $query->sum(DB::raw($personasExpr));
    }

    protected function siniestros(Carbon $inicio, Carbon $fin): array
    {
        if (!$this->tablaExiste('hechos')) {
            return ['total' => 0, 'resueltos' => 0, 'pendientes' => 0, 'turnados' => 0, 'lesionados' => 0];
        }

        $base = DB::table('hechos');
        $this->aplicarRango($base, 'hechos', 'hechos', $inicio, $fin);
        $hechos = $base->get(['id', 'situacion']);

        $out = ['total' => $hechos->count(), 'resueltos' => 0, 'pendientes' => 0, 'turnados' => 0];

        foreach ($hechos as $hecho) {
            $situacion = $this->norm((string) ($hecho->situacion ?? ''));

            if ($situacion === 'RESUELTO' || $situacion === 'RESUELTA') {
                $out['resueltos']++;
            } elseif ($situacion === 'PENDIENTE' || $situacion === 'PENDIENTES') {
                $out['pendientes']++;
            } elseif ($situacion === 'TURNADO' || $situacion === 'TURNADA') {
                $out['turnados']++;
            }
        }

        $out['lesionados'] = $this->lesionados($inicio, $fin);

        return $out;
    }

    protected function lesionados(Carbon $inicio, Carbon $fin): int
    {
        if (!$this->tablaExiste('lesionados') || !$this->tablaExiste('hechos')) {
            return 0;
        }

        $query = DB::table('lesionados')
            ->join('hechos', 'hechos.id', '=', 'lesionados.hecho_id')
            ->whereRaw("UPPER(TRIM(COALESCE(lesionados.tipo_lesion, ''))) <> 'FALLECIDO'");

        $this->aplicarRango($query, 'hechos', 'hechos', $inicio, $fin);

        return (int) $query->count('lesionados.id');
    }

    protected function vehiculosMp(Carbon $inicio, Carbon $fin): int
    {
        if (!$this->tablaExiste('hechos')) {
            return 0;
        }

        $query = DB::table('hechos');
        $this->aplicarRango($query, 'hechos', 'hechos', $inicio, $fin);

        if (Schema::hasColumn('hechos', 'vehiculos_mp')) {
            return (int) $query->sum(DB::raw('COALESCE(vehiculos_mp, 0)'));
        }

        return 0;
    }

    protected function vehiculosCorralonSiniestros(Carbon $inicio, Carbon $fin): array
    {
        $out = ['vehiculos' => 0, 'motocicletas' => 0];

        if (!$this->tablaExiste('hechos') || !$this->tablaExiste('hecho_vehiculo') || !$this->tablaExiste('vehiculos')) {
            return $out;
        }

        $subHechos = DB::table('hechos')->select('hechos.id');
        $this->aplicarRango($subHechos, 'hechos', 'hechos', $inicio, $fin);

        $vehiculos = DB::table('hecho_vehiculo')
            ->join('vehiculos', 'vehiculos.id', '=', 'hecho_vehiculo.vehiculo_id')
            ->whereIn('hecho_vehiculo.hecho_id', $subHechos)
            ->select('vehiculos.id', 'vehiculos.tipo', 'vehiculos.corralon')
            ->distinct()
            ->get();

        foreach ($vehiculos as $vehiculo) {
            if (!$this->isCorralonResguardado((string) ($vehiculo->corralon ?? ''))) {
                continue;
            }

            if ($this->tipoGeneralFromTipo((string) ($vehiculo->tipo ?? '')) === 'motocicleta') {
                $out['motocicletas']++;
            } else {
                $out['vehiculos']++;
            }
        }

        return $out;
    }

    protected function operativos(Carbon $inicio, Carbon $fin): array
    {
        $items = [];
        $inspecciones = ['personas' => 0, 'vehiculos' => 0, 'motocicletas' => 0];

        foreach ([
            $this->operativosTabla($inicio, $fin),
            $this->operativosDispositivosTabla($inicio, $fin),
            $this->operativosActividadesTabla($inicio, $fin),
        ] as $rows) {
            foreach ($rows as $row) {
                $nombre = $this->nombreOperativo((string) ($row->nombre ?? 'OPERATIVO'));
                $key = $this->norm($nombre);

                if (!isset($items[$key])) {
                    $items[$key] = ['nombre' => $nombre, 'cantidad' => 0, 'guardias' => 0, 'crp' => 0];
                }

                $items[$key]['cantidad'] += (int) ($row->cantidad ?? 0);
                $items[$key]['guardias'] += (int) ($row->guardias ?? 0);
                $items[$key]['crp'] += $this->contarCrpTexto((string) ($row->crps ?? ''));

                $inspecciones['personas'] += (int) ($row->personas_inspeccionadas ?? 0);
                $inspecciones['vehiculos'] += (int) ($row->vehiculos_inspeccionados ?? 0);
                $inspecciones['motocicletas'] += (int) ($row->motocicletas_inspeccionadas ?? 0);
            }
        }

        return [
            'items' => array_values(array_filter($items, function ($item) {
                return (int) $item['cantidad'] > 0 || (int) $item['guardias'] > 0 || (int) $item['crp'] > 0;
            })),
            'inspecciones_personas' => $inspecciones['personas'],
            'inspecciones_vehiculos' => $inspecciones['vehiculos'],
            'inspecciones_motocicletas' => $inspecciones['motocicletas'],
        ];
    }

    protected function operativosTabla(Carbon $inicio, Carbon $fin)
    {
        if (!$this->tablaExiste('operativos') || !$this->tablaExiste('operativo_catalogos')) {
            return collect();
        }

        $query = DB::table('operativos as o')
            ->leftJoin('operativo_catalogos as c', 'c.id', '=', 'o.operativo_catalogo_id')
            ->selectRaw('COALESCE(c.nombre, "OPERATIVO") as nombre')
            ->selectRaw('SUM(CASE WHEN COALESCE(o.dispositivos_realizados, 0) > 0 THEN o.dispositivos_realizados ELSE 1 END) as cantidad')
            ->selectRaw('SUM(COALESCE(o.estado_fuerza_participante, 0)) as guardias')
            ->selectRaw('GROUP_CONCAT(NULLIF(TRIM(COALESCE(o.crps_participantes, "")), "") SEPARATOR "|") as crps')
            ->selectRaw('SUM(COALESCE(o.personas_inspeccionadas, 0)) as personas_inspeccionadas')
            ->selectRaw('SUM(COALESCE(o.vehiculos_inspeccionados, 0)) as vehiculos_inspeccionados')
            ->selectRaw('SUM(COALESCE(o.antecedentes_motos, 0)) as motocicletas_inspeccionadas')
            ->groupByRaw('COALESCE(c.nombre, "OPERATIVO")')
            ->orderBy('nombre');

        $this->aplicarRango($query, 'operativos', 'o', $inicio, $fin);

        return $query->get();
    }

    protected function operativosDispositivosTabla(Carbon $inicio, Carbon $fin)
    {
        if (!$this->tablaExiste('operativo_dispositivos') || !$this->tablaExiste('operativo_dispositivo_catalogos')) {
            return collect();
        }

        $query = DB::table('operativo_dispositivos as od')
            ->leftJoin('operativo_dispositivo_catalogos as c', 'c.id', '=', 'od.operativo_dispositivo_catalogo_id')
            ->selectRaw('COALESCE(c.nombre, od.asunto, "OPERATIVO") as nombre')
            ->selectRaw('SUM(CASE WHEN COALESCE(od.cantidad, 0) > 0 THEN od.cantidad ELSE 1 END) as cantidad')
            ->selectRaw('SUM(COALESCE(od.estado_fuerza_participante, 0)) as guardias')
            ->selectRaw('GROUP_CONCAT(NULLIF(TRIM(COALESCE(od.crps_participantes, "")), "") SEPARATOR "|") as crps')
            ->selectRaw('SUM(COALESCE(od.personas_inspeccionadas, 0)) as personas_inspeccionadas')
            ->selectRaw('SUM(COALESCE(od.vehiculos_inspeccionados, 0)) as vehiculos_inspeccionados')
            ->selectRaw('SUM(COALESCE(od.antecedentes_motos, 0)) as motocicletas_inspeccionadas')
            ->groupByRaw('COALESCE(c.nombre, od.asunto, "OPERATIVO")')
            ->orderBy('nombre');

        if (Schema::hasColumn('operativo_dispositivos', 'estado_revision')) {
            $query->where('od.estado_revision', 'aprobado');
        }

        $this->aplicarRango($query, 'operativo_dispositivos', 'od', $inicio, $fin);

        return $query->get();
    }

    protected function operativosActividadesTabla(Carbon $inicio, Carbon $fin)
    {
        if (!$this->tablaExiste('actividades') || !$this->tablaExiste('actividad_categorias')) {
            return collect();
        }

        $query = DB::table('actividades as a')
            ->join('actividad_categorias as c', 'c.id', '=', 'a.actividad_categoria_id')
            ->selectRaw('"OTROS OPERATIVOS" as nombre')
            ->selectRaw('SUM(COALESCE(a.cantidad, 0)) as cantidad')
            ->selectRaw('SUM(COALESCE(a.personas_participantes, 0)) as guardias')
            ->selectRaw('GROUP_CONCAT(NULLIF(TRIM(COALESCE(a.patrullas_participantes_texto, "")), "") SEPARATOR "|") as crps')
            ->selectRaw('0 as personas_inspeccionadas')
            ->selectRaw('0 as vehiculos_inspeccionados')
            ->selectRaw('0 as motocicletas_inspeccionadas')
            ->where('c.slug', $this->slug('OPERATIVOS'));

        $this->aplicarRango($query, 'actividades', 'a', $inicio, $fin);

        return $query->get();
    }

    protected function aplicarRango($query, string $tabla, string $alias, Carbon $inicio, Carbon $fin): void
    {
        $inicioStr = $inicio->format('Y-m-d H:i:s');
        $finStr = $fin->format('Y-m-d H:i:s');

        if (Schema::hasColumn($tabla, 'fecha') && Schema::hasColumn($tabla, 'hora')) {
            $query->whereRaw(
                "TIMESTAMP({$alias}.fecha, COALESCE(NULLIF({$alias}.hora, ''), '00:00:00')) >= ?",
                [$inicioStr]
            )->whereRaw(
                "TIMESTAMP({$alias}.fecha, COALESCE(NULLIF({$alias}.hora, ''), '00:00:00')) < ?",
                [$finStr]
            );

            return;
        }

        if (Schema::hasColumn($tabla, 'fecha')) {
            $query->whereBetween($alias . '.fecha', [
                $inicio->toDateString(),
                $fin->copy()->subSecond()->toDateString(),
            ]);

            return;
        }

        if (Schema::hasColumn($tabla, 'created_at')) {
            $query->whereBetween($alias . '.created_at', [$inicioStr, $finStr]);
        }
    }

    protected function formatItems(array $items): string
    {
        $parts = [];

        foreach ($items as $item) {
            [$count, $singular, $plural] = $item;
            $count = (int) $count;

            if ($count <= 0) {
                continue;
            }

            $parts[] = '(' . $this->pad($count) . ') ' . $this->plural($count, $singular, $plural);
        }

        if (empty($parts)) {
            return '(00) sin novedades.';
        }

        if (count($parts) === 1) {
            return $parts[0] . '.';
        }

        $last = array_pop($parts);

        return implode(', ', $parts) . ' y ' . $last . '.';
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

    protected function nombreOperativo(string $nombre): string
    {
        $n = $this->norm($nombre);

        if (str_contains($n, 'CASCO') || str_contains($n, 'MOTOCICLISTA')) {
            return 'Concientización uso de casco';
        }

        if (str_contains($n, 'CABALLERO') || str_contains($n, 'GUARDIAN')) {
            return 'Guardianes del camino';
        }

        if (str_contains($n, 'PSV') || str_contains($n, 'RSV') || str_contains($n, 'CARRETER')) {
            return 'Seguridad en carreteras';
        }

        return $this->title($nombre);
    }

    protected function contarCrpTexto(string $texto): int
    {
        $texto = trim($texto);

        if ($texto === '') {
            return 0;
        }

        if (preg_match('/^\d+$/', $texto)) {
            return (int) $texto;
        }

        $partes = preg_split('/[|,;\/]+/', $texto, -1, PREG_SPLIT_NO_EMPTY);

        if (count($partes ?: []) > 1) {
            return count($partes);
        }

        return 1;
    }

    protected function tipoGeneralFromTipo(string $tipo): string
    {
        $t = $this->norm($tipo);

        if ($t === '') {
            return 'otros';
        }

        if (str_contains($t, 'MOTO') || str_contains($t, 'SCOOTER') || str_contains($t, 'MOTONETA')) {
            return 'motocicleta';
        }

        if (str_contains($t, 'CAMION') || str_contains($t, 'TRACTO')) {
            return 'camion';
        }

        if (str_contains($t, 'REMOLQUE')) {
            return 'remolque';
        }

        if (str_contains($t, 'CAMIONETA') || str_contains($t, 'PICK')) {
            return 'camioneta';
        }

        if (str_contains($t, 'AUTO') || str_contains($t, 'SEDAN') || str_contains($t, 'COUPE')) {
            return 'automovil';
        }

        return 'otros';
    }

    protected function isCorralonResguardado(string $corralon): bool
    {
        $c = $this->norm($corralon);

        if ($c === '') {
            return false;
        }

        return !in_array($c, ['N/A', 'NA', 'NO', 'NO SE UTILIZA', 'NOSEUTILIZA', 'N.A', 'N.A.'], true);
    }

    protected function tablaExiste(string $tabla): bool
    {
        try {
            return Schema::hasTable($tabla);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function slug(string $value): string
    {
        return Str::slug($this->sinAcentos($value));
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

    protected function title(string $value): string
    {
        return mb_convert_case(mb_strtolower(trim($value), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    protected function plural(int $count, string $singular, string $plural): string
    {
        return $count === 1 ? $singular : $plural;
    }

    protected function pad(int $value): string
    {
        return str_pad((string) $value, 2, '0', STR_PAD_LEFT);
    }
}
