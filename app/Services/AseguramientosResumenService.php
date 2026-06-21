<?php

namespace App\Services;

use App\Models\Delegacion;
use App\Models\Destacamento;
use App\Models\Hechos;
use App\Models\PuestaDisposicion;
use App\Models\Unidad;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AseguramientosResumenService
{
    public function generar(array $filters = [], $usuario = null): array
    {
        [$inicio, $fin] = $this->rangoDesdeFiltros($filters);
        $unidadId = $this->unidadIdFiltro($filters, $usuario);
        $resumen = $this->resumenVacio($inicio, $fin, $unidadId, $this->nombreUnidad($unidadId));

        foreach ($this->puestasDisposicion($inicio, $fin, $filters, $usuario, $unidadId) as $puesta) {
            $resumen['fuentes']['puestas_disposicion']++;
            $this->sumarPersonas($resumen, $puesta);
            $this->sumarVehiculos($resumen, $puesta);
            $this->sumarObjetos($resumen, $puesta);
        }

        foreach ($this->siniestrosRelevantes($inicio, $fin, $filters, $usuario, $unidadId) as $hecho) {
            $linea = $this->lineaSiniestro($hecho);
            $resumen['siniestros'][] = $linea;
            $this->agregarDetalle($resumen, 'siniestros.relevantes', [
                'tipo' => 'siniestro',
                'hecho_id' => $hecho->id,
                'fecha' => (string) $hecho->fecha,
                'hora' => (string) ($hecho->hora ?? ''),
                'unidad' => $this->nombreUnidad($hecho->unidad_org_id ?? null),
                'delegacion' => optional($hecho->delegacion)->nombre,
                'destacamento' => null,
                'motivo' => $hecho->tipo_hecho,
                'clasificacion' => 'Siniestro relevante',
                'descripcion' => $linea,
                'cantidad' => 1,
                'unidad_medida' => 'hecho',
                'url' => route('hechos.show', $hecho->id),
            ]);
        }

        $params = $this->templateParams($resumen);
        $resumen['tarjeta'] = [
            'params' => $this->templateSafeParams($params),
            'texto' => $this->templateBody($resumen, $params),
        ];
        $resumen['kpis'] = $this->kpis($resumen);
        $resumen['charts'] = $this->charts($resumen);
        $resumen['detalle_grupos'] = $this->detalleGrupos($resumen);
        $resumen['definiciones'] = $this->definiciones();

        return $resumen;
    }

    public function catalogos($usuario = null): array
    {
        $puedeVerTodo = $this->puedeVerTodasLasUnidades($usuario);

        $unidades = Unidad::query()
            ->select('id', 'nombre', 'slug')
            ->when($this->hasColumn('unidades', 'activa'), fn ($q) => $q->where('activa', 1))
            ->when(!$puedeVerTodo, fn ($q) => $q->where('id', (int) ($usuario->unidad_id ?? 0)))
            ->orderBy('nombre')
            ->get();

        $delegaciones = Delegacion::query()
            ->select('id', 'clave', 'nombre', 'municipio', 'delegacion_padre_id')
            ->when($this->hasColumn('delegaciones', 'activa'), fn ($q) => $q->where('activa', 1))
            ->when(!$puedeVerTodo && $usuario && !is_null($usuario->delegacion_id), function ($q) use ($usuario) {
                $ids = $this->delegacionIdsVisibles($usuario);
                $q->whereIn('id', empty($ids) ? [-1] : $ids);
            })
            ->orderBy('clave')
            ->orderBy('nombre')
            ->get();

        $destacamentos = collect();
        $destacamentosTienenDelegacion = false;

        if ($this->hasTable('destacamentos')) {
            $destacamentosTienenUnidad = $this->hasColumn('destacamentos', 'unidad_id');
            $destacamentosTienenDelegacion = $this->hasColumn('destacamentos', 'delegacion_id');
            $destacamentosTienenClave = $this->hasColumn('destacamentos', 'clave');
            $destacamentosTienenNombre = $this->hasColumn('destacamentos', 'nombre');

            $select = ['id'];

            foreach (['nombre', 'clave', 'unidad_id', 'delegacion_id'] as $column) {
                if ($this->hasColumn('destacamentos', $column)) {
                    $select[] = $column;
                }
            }

            $query = Destacamento::query()->select($select);

            if (!$destacamentosTienenNombre) {
                $query->addSelect(DB::raw("'' as nombre"));
            }

            if (!$destacamentosTienenClave) {
                $query->addSelect(DB::raw("'' as clave"));
            }

            if (!$destacamentosTienenUnidad) {
                $query->addSelect(DB::raw('NULL as unidad_id'));
            }

            if (!$destacamentosTienenDelegacion) {
                $query->addSelect(DB::raw('NULL as delegacion_id'));
            }

            $query->when($this->hasColumn('destacamentos', 'activo'), fn ($q) => $q->where('activo', 1));

            if ($destacamentosTienenUnidad && !$puedeVerTodo && $usuario && $usuario->unidad_id) {
                $query->where('unidad_id', (int) $usuario->unidad_id);
            }

            if ($destacamentosTienenDelegacion && !$puedeVerTodo && $usuario && !is_null($usuario->delegacion_id)) {
                $ids = $this->delegacionIdsVisibles($usuario);
                $query->whereIn('delegacion_id', empty($ids) ? [-1] : $ids);
            }

            if (!$puedeVerTodo && $usuario && !is_null($usuario->destacamento_id)) {
                $query->where('id', (int) $usuario->destacamento_id);
            }

            if ($destacamentosTienenClave) {
                $query->orderBy('clave');
            }

            if ($destacamentosTienenNombre) {
                $query->orderBy('nombre');
            }

            $destacamentos = $query->get();
        }

        return [
            'unidades' => $unidades,
            'delegaciones' => $delegaciones,
            'destacamentos' => $destacamentos,
            'destacamentos_tienen_delegacion' => $destacamentosTienenDelegacion,
            'puede_ver_todas_las_unidades' => $puedeVerTodo,
        ];
    }

    public function templateParams(array $resumen): array
    {
        $fin = $resumen['fin'];
        $inicio = $resumen['inicio'];

        return [
            $fin->copy()->locale('es')->translatedFormat('d \d\e F \d\e Y'),
            $fin->format('H:i') . ' hrs',
            $this->periodoTexto($inicio, $fin),
            $this->personasTexto($resumen['personas']),
            $this->vehiculosTexto($resumen['vehiculos']),
            $this->armasTexto($resumen['armas']),
            $this->drogasTexto($resumen['drogas']),
            $this->dineroTexto($resumen['dinero']),
            $this->otrosTexto($resumen['otros']),
            $this->siniestrosTexto($resumen['siniestros']),
        ];
    }

    public function templateBody(array $resumen, array $params): string
    {
        $p = array_values(array_map('strval', $params));

        for ($i = count($p); $i < 10; $i++) {
            $p[] = '';
        }

        return trim(
            "{$resumen['scope']['nombre']}\n"
            . "{$p[0]}\n"
            . "Corte: {$p[1]}\n"
            . "Periodo reportado: {$p[2]}\n\n"
            . "Aseguramientos del periodo\n\n"
            . "Personas:\n{$p[3]}\n\n"
            . "Vehículos:\n{$p[4]}\n\n"
            . "Armas:\n{$p[5]}\n\n"
            . "Droga y alcohol:\n{$p[6]}\n\n"
            . "Dinero:\n{$p[7]}\n\n"
            . "Otros aseguramientos:\n{$p[8]}\n\n"
            . "Siniestros de tránsito relevantes:\n{$p[9]}\n\n"
            . "Criterio de hechos relevantes: solo se informa el siniestro si hay fallecidos, 3 o más lesionados, o intervención del tren. Las puestas a disposición se cuentan completas."
        );
    }

    public function debeIncluirSiniestroRelevante(Hechos $hecho): bool
    {
        if (!$this->esSiniestroTransito($hecho)) {
            return false;
        }

        $hecho->loadMissing('lesionados');

        $lesionados = $hecho->relationLoaded('lesionados') ? $hecho->lesionados : collect();
        $fallecidos = $lesionados->filter(fn ($lesionado) => $this->esLesionadoFallecido($lesionado))->count();
        $lesionadosNoFallecidos = $lesionados->reject(fn ($lesionado) => $this->esLesionadoFallecido($lesionado))->count();

        return $fallecidos > 0
            || $lesionadosNoFallecidos > 2
            || $this->contiene($this->textoHecho($hecho), [
                'TREN',
                'FERROCARRIL',
                'FERROVIARIO',
                'FERROVIARIA',
                'VIA FERREA',
                'VIAS FERREAS',
            ]);
    }

    private function resumenVacio(Carbon $inicio, Carbon $fin, ?int $unidadId, string $nombreUnidad): array
    {
        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'scope' => [
                'unidad_id' => $unidadId,
                'nombre' => $unidadId ? 'Unidad de ' . $nombreUnidad : 'Todas las unidades',
            ],
            'fuentes' => [
                'puestas_disposicion' => 0,
            ],
            'descartados' => [],
            'personas' => [
                'justicia_civica' => 0,
                'ministerio_publico' => 0,
            ],
            'vehiculos' => [
                'total' => 0,
                'tipos' => [],
                'reporte_robo' => 0,
                'alteracion_medios' => 0,
                'hechos_delictivos' => 0,
                'siniestro_transito' => 0,
                'abandono' => 0,
                'otros' => 0,
            ],
            'armas' => [
                'corta' => 0,
                'larga' => 0,
                'cargadores' => 0,
                'cartuchos' => 0,
                'otros' => 0,
            ],
            'drogas' => [
                'alcohol' => [],
                'metanfetaminas' => [],
                'marihuana' => [],
                'otras' => [],
            ],
            'dinero' => [
                'total' => 0.0,
                'detalles' => [],
            ],
            'otros' => [],
            'siniestros' => [],
            'detalles' => [],
        ];
    }

    private function puestasDisposicion(Carbon $inicio, Carbon $fin, array $filters, $usuario, ?int $unidadId): Collection
    {
        if (!$this->hasTable('puestas_disposicion')) {
            return collect();
        }

        $query = PuestaDisposicion::query()
            ->with(['hecho.lesionados', 'personas', 'vehiculos', 'objetos', 'unidad', 'delegacion', 'destacamento'])
            ->whereRaw(
                "TIMESTAMP(DATE(fecha_puesta), COALESCE(TIME(hora_puesta), '00:00:00')) >= ?",
                [$inicio->toDateTimeString()]
            )
            ->whereRaw(
                "TIMESTAMP(DATE(fecha_puesta), COALESCE(TIME(hora_puesta), '00:00:00')) <= ?",
                [$fin->toDateTimeString()]
            );

        $this->aplicarScopePuestas($query, $filters, $usuario, $unidadId);

        return $query
            ->orderBy('fecha_puesta')
            ->orderBy('hora_puesta')
            ->orderBy('id')
            ->get()
            ->filter(fn (PuestaDisposicion $puesta) => $this->coincideBusquedaPuesta($puesta, $filters['q'] ?? null))
            ->values();
    }

    private function siniestrosRelevantes(Carbon $inicio, Carbon $fin, array $filters, $usuario, ?int $unidadId): Collection
    {
        if (!$this->hasTable('hechos')) {
            return collect();
        }

        $query = Hechos::query()
            ->with(['lesionados', 'delegacion'])
            ->whereRaw(
                "TIMESTAMP(DATE(fecha), COALESCE(NULLIF(hora, ''), '00:00:00')) >= ?",
                [$inicio->toDateTimeString()]
            )
            ->whereRaw(
                "TIMESTAMP(DATE(fecha), COALESCE(NULLIF(hora, ''), '00:00:00')) <= ?",
                [$fin->toDateTimeString()]
            );

        $this->aplicarScopeHechos($query, $filters, $usuario, $unidadId);

        return $query
            ->orderBy('fecha')
            ->orderBy('hora')
            ->orderBy('id')
            ->get()
            ->filter(fn (Hechos $hecho) => $this->coincideBusquedaHecho($hecho, $filters['q'] ?? null))
            ->filter(fn (Hechos $hecho) => $this->debeIncluirSiniestroRelevante($hecho))
            ->values();
    }

    private function aplicarScopePuestas($query, array $filters, $usuario, ?int $unidadId): void
    {
        if ($unidadId) {
            $query->where('unidad_id', $unidadId);
        } elseif (!$this->puedeVerTodasLasUnidades($usuario)) {
            $query->where('unidad_id', (int) ($usuario->unidad_id ?? 0));
        }

        $delegacionFiltro = (int) ($filters['delegacion_id'] ?? 0);
        $destacamentoFiltro = (int) ($filters['destacamento_id'] ?? 0);

        if ($this->puedeVerTodasLasUnidades($usuario)) {
            if ($delegacionFiltro > 0) {
                $query->where('delegacion_id', $delegacionFiltro);
            }

            if ($destacamentoFiltro > 0) {
                $query->where('destacamento_id', $destacamentoFiltro);
            }

            return;
        }

        if ($usuario && !is_null($usuario->delegacion_id)) {
            $ids = $this->delegacionIdsVisibles($usuario);
            $query->whereIn('delegacion_id', empty($ids) ? [-1] : $ids);
        } elseif ($delegacionFiltro > 0) {
            $query->where('delegacion_id', $delegacionFiltro);
        }

        if ($usuario && !is_null($usuario->destacamento_id)) {
            $query->where('destacamento_id', (int) $usuario->destacamento_id);
        } elseif ($destacamentoFiltro > 0) {
            $query->where('destacamento_id', $destacamentoFiltro);
        }
    }

    private function aplicarScopeHechos($query, array $filters, $usuario, ?int $unidadId): void
    {
        if ($unidadId) {
            $query->where('unidad_org_id', $unidadId);
        } elseif (!$this->puedeVerTodasLasUnidades($usuario)) {
            $query->where('unidad_org_id', (int) ($usuario->unidad_id ?? 0));
        }

        $delegacionFiltro = (int) ($filters['delegacion_id'] ?? 0);

        if ($this->puedeVerTodasLasUnidades($usuario)) {
            if ($delegacionFiltro > 0) {
                $query->where('delegacion_id', $delegacionFiltro);
            }

            return;
        }

        if ($usuario && !is_null($usuario->delegacion_id)) {
            $ids = $this->delegacionIdsVisibles($usuario);
            $query->whereIn('delegacion_id', empty($ids) ? [-1] : $ids);
        } elseif ($delegacionFiltro > 0) {
            $query->where('delegacion_id', $delegacionFiltro);
        }
    }

    private function sumarPersonas(array &$resumen, PuestaDisposicion $puesta): void
    {
        $personas = $puesta->relationLoaded('personas') ? $puesta->personas : collect();

        foreach ($personas as $persona) {
            $texto = $this->normalizar(implode(' ', [
                $puesta->motivo ?? '',
                $puesta->tipo_puesta ?? '',
                $persona->calidad ?? '',
                $persona->delito_o_motivo ?? '',
                $persona->observaciones ?? '',
            ]));

            $key = $this->esJusticiaCivica($texto)
                ? 'personas.justicia_civica'
                : 'personas.ministerio_publico';

            if ($key === 'personas.justicia_civica') {
                $resumen['personas']['justicia_civica']++;
            } else {
                $resumen['personas']['ministerio_publico']++;
            }

            $this->agregarDetalle($resumen, $key, array_merge($this->detallePuestaBase($puesta), [
                'tipo' => 'persona',
                'persona_id' => $persona->id,
                'clasificacion' => $key === 'personas.justicia_civica' ? 'A justicia civica' : 'Al Ministerio Publico',
                'descripcion' => trim(implode(' · ', array_filter([
                    $persona->nombre_completo,
                    $persona->calidad,
                    $persona->delito_o_motivo,
                    $persona->observaciones,
                ]))),
                'cantidad' => 1,
                'unidad_medida' => 'persona',
            ]));
        }
    }

    private function sumarVehiculos(array &$resumen, PuestaDisposicion $puesta): void
    {
        $vehiculos = $puesta->relationLoaded('vehiculos') ? $puesta->vehiculos : collect();
        $esTransito = $this->esTextoTransito($this->normalizar($puesta->motivo ?? '') . ' ' . $this->normalizar($puesta->tipo_puesta ?? ''));

        foreach ($vehiculos as $vehiculo) {
            $texto = $this->normalizar(implode(' ', [
                $puesta->motivo ?? '',
                $puesta->tipo_puesta ?? '',
                $vehiculo->tipo ?? '',
                $vehiculo->calidad ?? '',
                $vehiculo->motivo_relacion ?? '',
                $vehiculo->observaciones ?? '',
                $vehiculo->numero_reporte_robo ?? '',
            ]));

            $resumen['vehiculos']['total']++;
            $tipo = $this->labelVehiculo($vehiculo->tipo ?? null);
            $resumen['vehiculos']['tipos'][$tipo] = ($resumen['vehiculos']['tipos'][$tipo] ?? 0) + 1;

            $categoria = 'otros';

            if (!empty($vehiculo->con_reporte_robo) || trim((string) ($vehiculo->numero_reporte_robo ?? '')) !== '' || $this->contiene($texto, ['REPORTE DE ROBO', 'RECUPERADO', 'ROBO DE VEHICULO', 'ROBO DE VEHÍCULO'])) {
                $categoria = 'reporte_robo';
            } elseif ($this->contiene($texto, ['ALTERAD', 'MEDIOS DE IDENTIFICACION', 'MEDIOS DE IDENTIFICACIÓN', 'SERIE', 'NIV'])) {
                $categoria = 'alteracion_medios';
            } elseif ($esTransito) {
                $categoria = 'siniestro_transito';
            } elseif ($this->contiene($texto, ['ABANDON'])) {
                $categoria = 'abandono';
            } elseif ($this->contiene($texto, ['DELITO', 'DELICT', 'ROBO', 'POSESION', 'POSESIÓN', 'DETENID'])) {
                $categoria = 'hechos_delictivos';
            }

            $resumen['vehiculos'][$categoria]++;

            $detalle = array_merge($this->detallePuestaBase($puesta), [
                'tipo' => 'vehiculo',
                'vehiculo_puesta_id' => $vehiculo->id,
                'clasificacion' => $this->vehiculoCategoriaLabel($categoria),
                'descripcion' => trim(implode(' · ', array_filter([
                    $vehiculo->tipo,
                    $vehiculo->marca,
                    $vehiculo->submarca,
                    $vehiculo->placas ? 'Placas ' . $vehiculo->placas : null,
                    $vehiculo->serie ? 'Serie ' . $vehiculo->serie : null,
                    $vehiculo->calidad,
                    $vehiculo->motivo_relacion,
                    $vehiculo->observaciones,
                ]))),
                'cantidad' => 1,
                'unidad_medida' => 'vehiculo',
            ]);

            $this->agregarDetalle($resumen, 'vehiculos.total', $detalle);
            $this->agregarDetalle($resumen, 'vehiculos.' . $categoria, $detalle);
        }
    }

    private function sumarObjetos(array &$resumen, PuestaDisposicion $puesta): void
    {
        $objetos = $puesta->relationLoaded('objetos') ? $puesta->objetos : collect();

        foreach ($objetos as $objeto) {
            $cantidad = $this->cantidadObjeto($objeto);
            $unidad = trim((string) ($objeto->unidad_medida ?? ''));
            $texto = $this->normalizar(implode(' ', [
                $objeto->tipo_objeto ?? '',
                $objeto->descripcion ?? '',
                $unidad,
                $objeto->observaciones ?? '',
            ]));

            $base = array_merge($this->detallePuestaBase($puesta), [
                'tipo' => 'objeto',
                'objeto_id' => $objeto->id,
                'descripcion' => trim(implode(' · ', array_filter([
                    $objeto->tipo_objeto,
                    $objeto->descripcion,
                    $objeto->cadena_custodia ? 'Cadena ' . $objeto->cadena_custodia : null,
                    $objeto->observaciones,
                ]))),
                'cantidad' => $cantidad,
                'unidad_medida' => $unidad !== '' ? $unidad : 'unidad(es)',
            ]);

            if ($this->sumarArma($resumen, $texto, $cantidad, $base)) {
                continue;
            }

            if ($this->sumarDroga($resumen, $texto, $cantidad, $unidad, $base)) {
                continue;
            }

            if ($this->esDinero($texto)) {
                $monto = $this->montoDinero($objeto, $texto);
                $resumen['dinero']['total'] += $monto;

                if ($monto <= 0) {
                    $resumen['dinero']['detalles'][] = $this->lineaObjeto($cantidad, $unidad, $objeto->descripcion ?? $objeto->tipo_objeto ?? 'Dinero');
                }

                $this->agregarDetalle($resumen, 'dinero.total', array_merge($base, [
                    'clasificacion' => 'Dinero',
                    'cantidad' => $monto > 0 ? $monto : $cantidad,
                    'unidad_medida' => $monto > 0 ? 'MXN' : ($unidad !== '' ? $unidad : 'sin monto'),
                ]));

                continue;
            }

            $resumen['otros'][] = $this->lineaObjeto($cantidad, $unidad, $objeto->descripcion ?? $objeto->tipo_objeto ?? 'Objeto asegurado');
            $this->agregarDetalle($resumen, 'otros.aseguramientos', array_merge($base, [
                'clasificacion' => 'Otros aseguramientos',
            ]));
        }
    }

    private function sumarArma(array &$resumen, string $texto, float $cantidad, array $detalle): bool
    {
        $key = null;

        if ($this->contiene($texto, ['CARGADOR'])) {
            $key = 'cargadores';
        } elseif ($this->contiene($texto, ['CARTUCHO', 'MUNICION', 'MUNICIÓN'])) {
            $key = 'cartuchos';
        } elseif ($this->contiene($texto, ['ARMA CORTA', 'CORTA', 'PISTOLA', 'REVOLVER'])) {
            $key = 'corta';
        } elseif ($this->contiene($texto, ['ARMA LARGA', 'LARGA', 'RIFLE', 'ESCOPETA'])) {
            $key = 'larga';
        } elseif ($this->contiene($texto, ['ARMA', 'GRANADA', 'LANZA', 'PUNZO', 'CUCHILLO', 'NAVAJA'])) {
            $key = 'otros';
        }

        if ($key === null) {
            return false;
        }

        $resumen['armas'][$key] += $cantidad;
        $this->agregarDetalle($resumen, 'armas.' . $key, array_merge($detalle, [
            'clasificacion' => $this->armaLabel($key),
        ]));

        return true;
    }

    private function sumarDroga(array &$resumen, string $texto, float $cantidad, string $unidad, array $detalle): bool
    {
        $key = null;

        if ($this->contiene($texto, ['ALCOHOL', 'CERVEZA', 'LICOR'])) {
            $key = 'alcohol';
        } elseif ($this->contiene($texto, ['METANFETAMINA', 'CRISTAL'])) {
            $key = 'metanfetaminas';
        } elseif ($this->contiene($texto, ['MARIHUANA', 'CANNABIS'])) {
            $key = 'marihuana';
        } elseif ($this->contiene($texto, ['DROGA', 'COCAINA', 'COCAÍNA', 'PASTILLA', 'FENTANILO', 'HEROINA', 'HEROÍNA', 'NARCOTICO', 'NARCÓTICO'])) {
            $key = 'otras';
        }

        if ($key === null) {
            return false;
        }

        $this->agregarCantidad($resumen['drogas'][$key], $cantidad, $unidad);
        $this->agregarDetalle($resumen, 'drogas.' . $key, array_merge($detalle, [
            'clasificacion' => $this->drogaLabel($key),
        ]));

        return true;
    }

    private function detallePuestaBase(PuestaDisposicion $puesta): array
    {
        return [
            'puesta_id' => $puesta->id,
            'numero_puesta' => $puesta->numero_puesta,
            'fecha' => optional($puesta->fecha_puesta)->toDateString() ?: (string) $puesta->fecha_puesta,
            'hora' => optional($puesta->hora_puesta)->format('H:i') ?: (string) $puesta->hora_puesta,
            'unidad' => optional($puesta->unidad)->nombre ?: $this->nombreUnidad($puesta->unidad_id),
            'delegacion' => optional($puesta->delegacion)->nombre,
            'destacamento' => optional($puesta->destacamento)->nombre,
            'motivo' => $puesta->motivo,
            'tipo_puesta' => $puesta->tipo_puesta,
            'url' => route('puestas_disposicion.show', $puesta->id),
        ];
    }

    private function agregarDetalle(array &$resumen, string $key, array $detalle): void
    {
        $detalle['detalle_key'] = $key;
        $resumen['detalles'][$key] ??= [];
        $resumen['detalles'][$key][] = $detalle;
    }

    private function kpis(array $resumen): array
    {
        $armasTotal = array_sum($resumen['armas']);
        $drogasItems = $this->detalleCount($resumen, ['drogas.alcohol', 'drogas.metanfetaminas', 'drogas.marihuana', 'drogas.otras']);

        return [
            'puestas' => [
                'label' => 'Puestas revisadas',
                'value' => (int) $resumen['fuentes']['puestas_disposicion'],
                'hint' => 'Puestas a disposición incluidas en el periodo.',
            ],
            'personas' => [
                'label' => 'Personas',
                'value' => (int) $resumen['personas']['justicia_civica'] + (int) $resumen['personas']['ministerio_publico'],
                'hint' => 'Personas vinculadas a puestas.',
            ],
            'vehiculos' => [
                'label' => 'Vehículos',
                'value' => (int) $resumen['vehiculos']['total'],
                'hint' => 'Vehículos vinculados a puestas.',
            ],
            'armas' => [
                'label' => 'Armas y municiones',
                'value' => (float) $armasTotal,
                'hint' => 'Suma de armas, cargadores, cartuchos y otros.',
            ],
            'drogas' => [
                'label' => 'Droga / alcohol',
                'value' => (int) $drogasItems,
                'hint' => 'Registros de objeto clasificados como droga o alcohol.',
            ],
            'dinero' => [
                'label' => 'Dinero',
                'value' => (float) $resumen['dinero']['total'],
                'hint' => 'Monto MXN detectado en objetos tipo dinero.',
                'money' => true,
            ],
            'siniestros' => [
                'label' => 'Siniestros relevantes',
                'value' => count($resumen['siniestros']),
                'hint' => 'Fallecidos, 3+ lesionados o tren.',
            ],
        ];
    }

    private function charts(array $resumen): array
    {
        return [
            'grupos' => [
                ['label' => 'Personas', 'total' => (int) $resumen['personas']['justicia_civica'] + (int) $resumen['personas']['ministerio_publico']],
                ['label' => 'Vehículos', 'total' => (int) $resumen['vehiculos']['total']],
                ['label' => 'Armas', 'total' => (float) array_sum($resumen['armas'])],
                ['label' => 'Droga/Alcohol', 'total' => $this->detalleCount($resumen, ['drogas.alcohol', 'drogas.metanfetaminas', 'drogas.marihuana', 'drogas.otras'])],
                ['label' => 'Otros', 'total' => count($resumen['otros'])],
                ['label' => 'Siniestros', 'total' => count($resumen['siniestros'])],
            ],
            'personas' => [
                ['label' => 'Justicia civica', 'total' => (int) $resumen['personas']['justicia_civica']],
                ['label' => 'Ministerio Publico', 'total' => (int) $resumen['personas']['ministerio_publico']],
            ],
            'vehiculos' => [
                ['label' => 'Reporte robo', 'total' => (int) $resumen['vehiculos']['reporte_robo']],
                ['label' => 'Alteracion medios', 'total' => (int) $resumen['vehiculos']['alteracion_medios']],
                ['label' => 'Hechos delictivos', 'total' => (int) $resumen['vehiculos']['hechos_delictivos']],
                ['label' => 'Siniestro transito', 'total' => (int) $resumen['vehiculos']['siniestro_transito']],
                ['label' => 'Abandono', 'total' => (int) $resumen['vehiculos']['abandono']],
                ['label' => 'Otros', 'total' => (int) $resumen['vehiculos']['otros']],
            ],
            'armas' => [
                ['label' => 'Corta', 'total' => (float) $resumen['armas']['corta']],
                ['label' => 'Larga', 'total' => (float) $resumen['armas']['larga']],
                ['label' => 'Cargadores', 'total' => (float) $resumen['armas']['cargadores']],
                ['label' => 'Cartuchos', 'total' => (float) $resumen['armas']['cartuchos']],
                ['label' => 'Otros', 'total' => (float) $resumen['armas']['otros']],
            ],
            'drogas' => [
                ['label' => 'Alcohol', 'total' => $this->detalleCount($resumen, ['drogas.alcohol'])],
                ['label' => 'Metanfetaminas', 'total' => $this->detalleCount($resumen, ['drogas.metanfetaminas'])],
                ['label' => 'Marihuana', 'total' => $this->detalleCount($resumen, ['drogas.marihuana'])],
                ['label' => 'Otras', 'total' => $this->detalleCount($resumen, ['drogas.otras'])],
            ],
        ];
    }

    private function detalleGrupos(array $resumen): array
    {
        $labels = [
            'personas.justicia_civica' => 'Personas · A justicia civica',
            'personas.ministerio_publico' => 'Personas · Al Ministerio Publico',
            'vehiculos.total' => 'Vehiculos · Total',
            'vehiculos.reporte_robo' => 'Vehiculos · Reporte de robo',
            'vehiculos.alteracion_medios' => 'Vehiculos · Alteracion en medios',
            'vehiculos.hechos_delictivos' => 'Vehiculos · Hechos delictivos',
            'vehiculos.siniestro_transito' => 'Vehiculos · Siniestro de transito',
            'vehiculos.abandono' => 'Vehiculos · Abandono',
            'vehiculos.otros' => 'Vehiculos · Otros motivos',
            'armas.corta' => 'Armas · Corta',
            'armas.larga' => 'Armas · Larga',
            'armas.cargadores' => 'Armas · Cargadores',
            'armas.cartuchos' => 'Armas · Cartuchos',
            'armas.otros' => 'Armas · Otros',
            'drogas.alcohol' => 'Droga/alcohol · Alcohol',
            'drogas.metanfetaminas' => 'Droga/alcohol · Metanfetaminas',
            'drogas.marihuana' => 'Droga/alcohol · Marihuana',
            'drogas.otras' => 'Droga/alcohol · Otras',
            'dinero.total' => 'Dinero · Cantidad',
            'otros.aseguramientos' => 'Otros aseguramientos',
            'siniestros.relevantes' => 'Siniestros de transito relevantes',
        ];

        return collect($labels)
            ->map(function ($label, $key) use ($resumen) {
                return [
                    'key' => $key,
                    'label' => $label,
                    'total' => count($resumen['detalles'][$key] ?? []),
                ];
            })
            ->values()
            ->all();
    }

    private function definiciones(): array
    {
        return [
            'personas.justicia_civica' => 'Cuenta cada persona de una puesta cuando el texto de la puesta o persona menciona justicia civica, juez civico, barandilla, falta administrativa, alcohol, ebrio o equivalente.',
            'personas.ministerio_publico' => 'Cuenta cada persona vinculada a una puesta que no cae en la regla de justicia civica.',
            'vehiculos.total' => 'Cuenta cada vehiculo vinculado a una puesta incluida en el periodo.',
            'vehiculos.reporte_robo' => 'Cuenta vehiculos con bandera o numero de reporte de robo, o texto como reporte de robo, recuperado o robo de vehiculo.',
            'vehiculos.alteracion_medios' => 'Cuenta vehiculos cuyo texto menciona alteracion, medios de identificacion, serie o NIV.',
            'vehiculos.hechos_delictivos' => 'Cuenta vehiculos cuyo texto menciona delito, hecho delictivo, robo, posesion o detenido, siempre que no haya entrado en categorias previas.',
            'vehiculos.siniestro_transito' => 'Cuenta vehiculos de puestas cuyo motivo o tipo corresponde a transito o siniestro. Las puestas se cuentan completas.',
            'vehiculos.abandono' => 'Cuenta vehiculos cuyo texto menciona abandono, siempre que no haya entrado en categorias previas.',
            'vehiculos.otros' => 'Cuenta vehiculos incluidos en puestas que no coinciden con las categorias anteriores.',
            'armas.corta' => 'Cuenta objetos con texto arma corta, corta, pistola o revolver.',
            'armas.larga' => 'Cuenta objetos con texto arma larga, larga, rifle o escopeta.',
            'armas.cargadores' => 'Cuenta objetos con texto cargador.',
            'armas.cartuchos' => 'Cuenta objetos con texto cartucho o municion.',
            'armas.otros' => 'Cuenta objetos con texto arma, granada, lanza, punzo, cuchillo o navaja que no entraron antes.',
            'drogas.alcohol' => 'Cuenta objetos con texto alcohol, cerveza o licor, conservando cantidad y unidad de medida.',
            'drogas.metanfetaminas' => 'Cuenta objetos con texto metanfetamina o cristal, conservando cantidad y unidad de medida.',
            'drogas.marihuana' => 'Cuenta objetos con texto marihuana o cannabis, conservando cantidad y unidad de medida.',
            'drogas.otras' => 'Cuenta objetos con texto droga, cocaina, pastilla, fentanilo, heroina o narcotico, conservando cantidad y unidad de medida.',
            'dinero.total' => 'Suma montos detectados en objetos tipo dinero, efectivo, pesos, MXN o con signo $.',
            'otros.aseguramientos' => 'Lista objetos asegurados que no fueron clasificados como arma, droga/alcohol o dinero.',
            'siniestros.relevantes' => 'Incluye hechos de transito con fallecido, muerto, deceso, occiso, sin vida, 3 o mas lesionados no fallecidos, o intervencion de tren.',
        ];
    }

    private function detalleCount(array $resumen, array $keys): int
    {
        $total = 0;

        foreach ($keys as $key) {
            $total += count($resumen['detalles'][$key] ?? []);
        }

        return $total;
    }

    private function personasTexto(array $personas): string
    {
        return 'A justicia cívica: ' . $this->pad((int) $personas['justicia_civica']) . "\n"
            . 'Al Ministerio Público: ' . $this->pad((int) $personas['ministerio_publico']);
    }

    private function vehiculosTexto(array $vehiculos): string
    {
        return 'Total: ' . $this->pad((int) $vehiculos['total']) . $this->tiposVehiculoTexto($vehiculos['tipos']) . "\n"
            . 'Recuperados con reporte de robo: ' . $this->pad((int) $vehiculos['reporte_robo']) . "\n"
            . 'A disposición por alteración en medios de identificación: ' . $this->pad((int) $vehiculos['alteracion_medios']) . "\n"
            . 'A disposición por hechos delictivos: ' . $this->pad((int) $vehiculos['hechos_delictivos']) . "\n"
            . 'A disposición por siniestro de tránsito: ' . $this->pad((int) $vehiculos['siniestro_transito']) . "\n"
            . 'Asegurados por abandono: ' . $this->pad((int) $vehiculos['abandono']) . "\n"
            . 'Asegurados por otros motivos: ' . $this->pad((int) $vehiculos['otros']);
    }

    private function armasTexto(array $armas): string
    {
        return 'Corta: ' . $this->cantidadTexto($armas['corta']) . "\n"
            . 'Larga: ' . $this->cantidadTexto($armas['larga']) . "\n"
            . 'Cargadores: ' . $this->cantidadTexto($armas['cargadores']) . "\n"
            . 'Cartuchos: ' . $this->cantidadTexto($armas['cartuchos']) . "\n"
            . 'Otros: ' . $this->cantidadTexto($armas['otros']);
    }

    private function drogasTexto(array $drogas): string
    {
        return 'Alcohol: ' . $this->cantidadesPorUnidadTexto($drogas['alcohol']) . "\n"
            . 'Metanfetaminas: ' . $this->cantidadesPorUnidadTexto($drogas['metanfetaminas']) . "\n"
            . 'Marihuana: ' . $this->cantidadesPorUnidadTexto($drogas['marihuana']) . "\n"
            . 'Otras: ' . $this->cantidadesPorUnidadTexto($drogas['otras']);
    }

    private function dineroTexto(array $dinero): string
    {
        $total = (float) ($dinero['total'] ?? 0);

        if ($total > 0) {
            return 'Cantidad: $' . number_format($total, 2, '.', ',') . ' MXN';
        }

        if (!empty($dinero['detalles'])) {
            return 'Cantidad no especificada: ' . implode('; ', array_slice($dinero['detalles'], 0, 4));
        }

        return 'Cantidad: $0.00 MXN';
    }

    private function otrosTexto(array $otros): string
    {
        $otros = array_values(array_filter(array_map('trim', $otros)));

        if (empty($otros)) {
            return 'Sin otros aseguramientos en el periodo.';
        }

        return implode("\n", array_slice($otros, 0, 8));
    }

    private function siniestrosTexto(array $siniestros): string
    {
        $siniestros = array_values(array_filter(array_map('trim', $siniestros)));

        if (empty($siniestros)) {
            return 'Sin siniestros de tránsito relevantes en el periodo.';
        }

        return implode("\n", array_slice($siniestros, 0, 6));
    }

    private function lineaSiniestro(Hechos $hecho): string
    {
        $hecho->loadMissing(['lesionados', 'delegacion']);
        $lesionados = $hecho->relationLoaded('lesionados') ? $hecho->lesionados : collect();
        $fallecidos = $lesionados->filter(fn ($lesionado) => $this->esLesionadoFallecido($lesionado))->count();
        $lesionadosNoFallecidos = $lesionados->reject(fn ($lesionado) => $this->esLesionadoFallecido($lesionado))->count();
        $partes = ['Hecho #' . $hecho->id];

        if (trim((string) ($hecho->tipo_hecho ?? '')) !== '') {
            $partes[] = trim((string) $hecho->tipo_hecho);
        }

        $partes[] = $lesionadosNoFallecidos . ' lesionado(s)';

        if ($fallecidos > 0) {
            $partes[] = $fallecidos . ' fallecido(s)';
        }

        if ($this->contiene($this->textoHecho($hecho), ['TREN', 'FERROCARRIL', 'VIA FERREA', 'VIAS FERREAS'])) {
            $partes[] = 'intervención de tren';
        }

        $ubicacion = $this->ubicacionHecho($hecho);

        if ($ubicacion !== '') {
            $partes[] = $ubicacion;
        }

        return implode(', ', $partes) . '.';
    }

    private function coincideBusquedaPuesta(PuestaDisposicion $puesta, $search): bool
    {
        $search = trim((string) $search);

        if ($search === '') {
            return true;
        }

        $textos = [
            $puesta->numero_puesta,
            $puesta->tipo_puesta,
            $puesta->motivo,
            $puesta->estatus,
            $puesta->nombre_policia,
            $puesta->nombre_mp,
            $puesta->autoridad_receptora,
            $puesta->carpeta_investigacion,
            $puesta->oficio,
            $puesta->lugar_puesta,
            optional($puesta->unidad)->nombre,
            optional($puesta->delegacion)->nombre,
            optional($puesta->destacamento)->nombre,
        ];

        foreach ($puesta->personas ?? [] as $persona) {
            $textos[] = $persona->nombre_completo;
            $textos[] = $persona->alias;
            $textos[] = $persona->calidad;
            $textos[] = $persona->delito_o_motivo;
        }

        foreach ($puesta->vehiculos ?? [] as $vehiculo) {
            $textos[] = $vehiculo->tipo;
            $textos[] = $vehiculo->marca;
            $textos[] = $vehiculo->submarca;
            $textos[] = $vehiculo->placas;
            $textos[] = $vehiculo->serie;
            $textos[] = $vehiculo->calidad;
        }

        foreach ($puesta->objetos ?? [] as $objeto) {
            $textos[] = $objeto->tipo_objeto;
            $textos[] = $objeto->descripcion;
            $textos[] = $objeto->cadena_custodia;
        }

        return strpos($this->normalizar(implode(' ', $textos)), $this->normalizar($search)) !== false;
    }

    private function coincideBusquedaHecho(Hechos $hecho, $search): bool
    {
        $search = trim((string) $search);

        if ($search === '') {
            return true;
        }

        return strpos($this->textoHecho($hecho), $this->normalizar($search)) !== false;
    }

    private function rangoDesdeFiltros(array $filters): array
    {
        $timezone = $this->timezone();
        $today = Carbon::now($timezone);
        $desde = trim((string) ($filters['desde'] ?? $today->copy()->subDays(30)->toDateString()));
        $hasta = trim((string) ($filters['hasta'] ?? $today->toDateString()));
        $horaDesde = $this->normalizeHour($filters['hora_desde'] ?? null) ?: '00:00:00';
        $horaHasta = $this->normalizeHour($filters['hora_hasta'] ?? null) ?: '23:59:59';

        try {
            $inicio = Carbon::createFromFormat('Y-m-d H:i:s', $desde . ' ' . $horaDesde, $timezone);
        } catch (\Throwable $e) {
            $inicio = $today->copy()->subDays(30)->startOfDay();
        }

        try {
            $fin = Carbon::createFromFormat('Y-m-d H:i:s', $hasta . ' ' . $horaHasta, $timezone);
        } catch (\Throwable $e) {
            $fin = $today->copy()->endOfDay();
        }

        if ($fin->lessThan($inicio)) {
            [$inicio, $fin] = [$fin->copy()->startOfDay(), $inicio->copy()->endOfDay()];
        }

        return [$inicio, $fin];
    }

    private function unidadIdFiltro(array $filters, $usuario): ?int
    {
        $requested = (int) ($filters['unidad_id'] ?? $filters['unidad_org_id'] ?? 0);

        if ($requested <= 0 && trim((string) ($filters['unidad_slug'] ?? '')) !== '') {
            $requested = (int) Unidad::query()
                ->where('slug', trim((string) $filters['unidad_slug']))
                ->value('id');
        }

        if ($this->puedeVerTodasLasUnidades($usuario)) {
            return $requested > 0 ? $requested : null;
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        return $unidadId > 0 ? $unidadId : -1;
    }

    private function puedeVerTodasLasUnidades($usuario): bool
    {
        return $usuario && ($usuario->hasRole('Superadmin') || (int) ($usuario->unidad_id ?? 0) === 3);
    }

    private function delegacionIdsVisibles($usuario): array
    {
        $delegacionId = (int) ($usuario->delegacion_id ?? 0);

        if ($delegacionId <= 0) {
            return [];
        }

        if (!$usuario->hasRole('Delegado')) {
            return [$delegacionId];
        }

        $esRegional = Delegacion::query()
            ->where('id', $delegacionId)
            ->whereNull('delegacion_padre_id')
            ->exists();

        if (!$esRegional) {
            return [$delegacionId];
        }

        return Delegacion::query()
            ->where('id', $delegacionId)
            ->orWhere('delegacion_padre_id', $delegacionId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    private function esSiniestroTransito(Hechos $hecho): bool
    {
        return $this->esTextoTransito($this->textoHecho($hecho));
    }

    private function esTextoTransito(string $texto): bool
    {
        return $this->contiene($texto, [
            'HECHO DE TRANSITO',
            'HECHOS DE TRANSITO',
            'TRANSITO',
            'SINIESTRO',
            'ACCIDENTE',
            'CHOQUE',
            'COLISION',
            'COLISIÓN',
            'VOLCADURA',
            'ATROPELL',
            'TREN',
            'FERROCARRIL',
            'FERROVIARIO',
            'FERROVIARIA',
        ]);
    }

    private function esJusticiaCivica(string $texto): bool
    {
        return $this->contiene($texto, [
            'JUSTICIA CIVICA',
            'JUSTICIA CÍVICA',
            'JUEZ CIVICO',
            'JUEZ CÍVICO',
            'CIVICA',
            'CÍVICA',
            'BARANDILLA',
            'FALTA ADMINISTRATIVA',
            'ALTERAR EL ORDEN PUBLICO',
            'ALTERAR EL ORDEN PÚBLICO',
            'ALCOHOL',
            'EBRIO',
            'ETILICO',
            'ETÍLICO',
        ]);
    }

    private function esDinero(string $texto): bool
    {
        return $this->contiene($texto, ['DINERO', 'EFECTIVO', 'PESO', 'PESOS', 'MXN', '$']);
    }

    private function montoDinero($objeto, string $texto): float
    {
        $cantidad = is_numeric($objeto->cantidad ?? null) ? (float) $objeto->cantidad : 0.0;
        $unidad = $this->normalizar($objeto->unidad_medida ?? '');

        if ($cantidad > 0 && ($unidad === '' || $this->contiene($unidad, ['PESO', 'MXN', '$']))) {
            return $cantidad;
        }

        if (preg_match_all('/\$?\s*([0-9]{1,3}(?:[, ][0-9]{3})*(?:\.[0-9]{1,2})?|[0-9]+(?:\.[0-9]{1,2})?)\s*(?:PESOS|MXN)?/u', $texto, $matches)) {
            $sum = 0.0;

            foreach ($matches[1] as $match) {
                $sum += (float) str_replace([',', ' '], '', $match);
            }

            return $sum;
        }

        return 0.0;
    }

    private function agregarCantidad(array &$bucket, float $cantidad, string $unidad): void
    {
        $unidad = trim($unidad) !== '' ? trim($unidad) : 'unidad(es)';
        $bucket[$unidad] = ($bucket[$unidad] ?? 0) + $cantidad;
    }

    private function cantidadesPorUnidadTexto(array $bucket): string
    {
        if (empty($bucket)) {
            return '00';
        }

        $partes = [];

        foreach ($bucket as $unidad => $cantidad) {
            if ((float) $cantidad <= 0) {
                continue;
            }

            $partes[] = $this->cantidadTexto($cantidad) . ' ' . trim((string) $unidad);
        }

        return $partes ? implode(', ', $partes) : '00';
    }

    private function tiposVehiculoTexto(array $tipos): string
    {
        if (empty($tipos)) {
            return '';
        }

        ksort($tipos);
        $partes = [];

        foreach ($tipos as $tipo => $total) {
            $partes[] = $this->pad((int) $total) . ' ' . mb_strtolower($tipo, 'UTF-8');
        }

        return ' (' . implode(', ', $partes) . ')';
    }

    private function labelVehiculo($tipo): string
    {
        $tipo = trim((string) $tipo);

        return $tipo !== '' ? mb_strtoupper($tipo, 'UTF-8') : 'NO ESPECIFICADO';
    }

    private function lineaObjeto(float $cantidad, string $unidad, $descripcion): string
    {
        $descripcion = trim((string) $descripcion);

        return $this->cantidadTexto($cantidad)
            . (trim($unidad) !== '' ? ' ' . trim($unidad) : '')
            . ($descripcion !== '' ? ' ' . $descripcion : '');
    }

    private function cantidadObjeto($objeto): float
    {
        if (is_numeric($objeto->cantidad ?? null) && (float) $objeto->cantidad > 0) {
            return (float) $objeto->cantidad;
        }

        return 1.0;
    }

    private function cantidadTexto($value): string
    {
        $value = (float) $value;

        if (abs($value - round($value)) < 0.00001) {
            return $this->pad((int) round($value));
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function periodoTexto(Carbon $inicio, Carbon $fin): string
    {
        return $inicio->format('d/m/Y H:i') . ' hrs a ' . $fin->format('d/m/Y H:i') . ' hrs';
    }

    private function textoHecho(Hechos $hecho): string
    {
        return $this->normalizar(implode(' ', [
            $hecho->tipo_hecho ?? '',
            $hecho->colision_camino ?? '',
            $hecho->causas ?? '',
            $hecho->responsable ?? '',
            $hecho->calle ?? '',
            $hecho->colonia ?? '',
            $hecho->municipio ?? '',
            $hecho->ubicacion_formateada ?? '',
        ]));
    }

    private function ubicacionHecho(Hechos $hecho): string
    {
        return trim(implode(', ', array_filter([
            $hecho->calle ?? null,
            $hecho->colonia ?? null,
            $hecho->municipio ?? null,
        ])));
    }

    private function esLesionadoFallecido($lesionado): bool
    {
        $texto = $this->normalizar(implode(' ', [
            $lesionado->tipo_lesion ?? '',
            $lesionado->tipo_victima ?? '',
            $lesionado->observaciones ?? '',
        ]));

        return $this->contiene($texto, [
            'FALLEC',
            'MUERTO',
            'MUERTA',
            'DECESO',
            'DEFUNCION',
            'DEFUNCIÓN',
            'OCCISO',
            'OCCISA',
            'CADAVER',
            'CADÁVER',
            'SIN VIDA',
        ]);
    }

    private function contiene(string $texto, array $needles): bool
    {
        $texto = $this->normalizar($texto);

        foreach ($needles as $needle) {
            if ($needle !== '' && strpos($texto, $this->normalizar($needle)) !== false) {
                return true;
            }
        }

        return false;
    }

    private function normalizar($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = strtr($value, [
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

        return mb_strtoupper(preg_replace('/\s+/', ' ', $value) ?? $value, 'UTF-8');
    }

    private function templateSafeParams(array $params): array
    {
        return array_map(function ($value) {
            return $this->templateParameterText((string) $value);
        }, $params);
    }

    private function templateParameterText(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $text);
        $text = preg_replace('/ {2,}/', ' ', $text) ?? $text;

        return trim($text);
    }

    private function pad(int $value): string
    {
        return str_pad((string) max(0, $value), 2, '0', STR_PAD_LEFT);
    }

    private function normalizeHour($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value . ':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        return null;
    }

    private function nombreUnidad($unidadId): string
    {
        if (!$unidadId || $unidadId < 0) {
            return 'SIN ASIGNAR';
        }

        return Unidad::query()->where('id', $unidadId)->value('nombre') ?: 'SIN ASIGNAR';
    }

    private function vehiculoCategoriaLabel(string $key): string
    {
        return [
            'reporte_robo' => 'Recuperado con reporte de robo',
            'alteracion_medios' => 'Alteracion en medios de identificacion',
            'hechos_delictivos' => 'Hechos delictivos',
            'siniestro_transito' => 'Siniestro de transito',
            'abandono' => 'Abandono',
            'otros' => 'Otros motivos',
        ][$key] ?? 'Otros motivos';
    }

    private function armaLabel(string $key): string
    {
        return [
            'corta' => 'Arma corta',
            'larga' => 'Arma larga',
            'cargadores' => 'Cargadores',
            'cartuchos' => 'Cartuchos',
            'otros' => 'Otras armas/objetos',
        ][$key] ?? 'Arma';
    }

    private function drogaLabel(string $key): string
    {
        return [
            'alcohol' => 'Alcohol',
            'metanfetaminas' => 'Metanfetaminas',
            'marihuana' => 'Marihuana',
            'otras' => 'Otras drogas',
        ][$key] ?? 'Droga';
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function timezone(): string
    {
        return (string) config('app.schedule_timezone', config('app.timezone', 'America/Mexico_City'));
    }
}
