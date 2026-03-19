@extends('adminlte::page')

@section('title', 'Resumen Operativo')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Resumen del Operativo Guardianes del Camino</h1>
        <div>
            <a href="{{ route('guardianes_camino.index', ['fecha' => $fecha ?? now()->format('Y-m-d')]) }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>

            <a href="{{ route('guardianes_camino.whatsapp', ['fecha' => $fecha ?? now()->format('Y-m-d')]) }}" class="btn btn-success btn-sm">
                <i class="fa-brands fa-whatsapp"></i> Compartir
            </a>
        </div>
    </div>
@stop

@section('content')
    @php
        $labelsCampos = [
            'total_cantidad' => 'Cantidad',
            'total_vehiculos_inspeccionados' => 'Vehículos inspeccionados',
            'total_personas_inspeccionadas' => 'Personas inspeccionadas',
            'total_vehiculos_impactados' => 'Vehículos impactados',
            'total_personas_impactadas' => 'Personas impactadas',
            'total_estado_fuerza_participante' => 'Estado de fuerza',
            'total_kilometros_recorridos' => 'Kilómetros recorridos',
            'total_acompanamientos' => 'Acompañamientos',
            'total_abanderamientos' => 'Abanderamientos',
            'total_auxilios_viales' => 'Auxilios viales',
            'total_prox_empresas' => 'Proximidad empresas',
            'total_prox_tiendas_conveniencia' => 'Proximidad tiendas',
            'total_prox_escuelas' => 'Proximidad escuelas',
            'total_prox_hospitales' => 'Proximidad hospitales',
            'total_antecedentes_personas' => 'Antecedentes personas',
            'total_antecedentes_vehiculos' => 'Antecedentes vehículos',
            'total_antecedentes_motos' => 'Antecedentes motos',
            'total_antecedentes_camiones' => 'Antecedentes camiones',
            'total_puestas_disposicion' => 'Puestas a disposición',
            'total_vehiculos_recuperados' => 'Vehículos recuperados',
            'total_armas_aseguradas' => 'Armas aseguradas',
            'total_mercancia_recuperada' => 'Mercancía recuperada',
            'total_decomiso_drogas' => 'Decomiso drogas',
        ];

        $camposPorCatalogo = [
            'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)' => [
                'total_cantidad',
                'total_vehiculos_inspeccionados',
                'total_personas_inspeccionadas',
                'total_estado_fuerza_participante',
                'total_kilometros_recorridos',
            ],
            'RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)' => [
                'total_cantidad',
                'total_vehiculos_inspeccionados',
                'total_personas_inspeccionadas',
                'total_estado_fuerza_participante',
                'total_kilometros_recorridos',
            ],
            'DISPOSITIVO CASCO' => [
                'total_cantidad',
                'total_vehiculos_impactados',
                'total_personas_impactadas',
                'total_estado_fuerza_participante',
                'total_kilometros_recorridos',
            ],
            'DISPOSITIVO CINTURON' => [
                'total_cantidad',
                'total_vehiculos_impactados',
                'total_personas_impactadas',
                'total_estado_fuerza_participante',
                'total_kilometros_recorridos',
            ],
            'DISPOSITIVO CINTURÓN' => [
                'total_cantidad',
                'total_vehiculos_impactados',
                'total_personas_impactadas',
                'total_estado_fuerza_participante',
                'total_kilometros_recorridos',
            ],
            'DISPOSITIVO CARRUSEL' => [
                'total_cantidad',
                'total_vehiculos_impactados',
                'total_estado_fuerza_participante',
                'total_kilometros_recorridos',
            ],
            'CORDILLERA' => [
                'total_cantidad',
                'total_vehiculos_impactados',
                'total_personas_impactadas',
                'total_estado_fuerza_participante',
                'total_kilometros_recorridos',
            ],
            'DISPOSITIVO ASIENTO SEGURO PASAJEROS MENORES' => [
                'total_cantidad',
                'total_vehiculos_impactados',
                'total_personas_impactadas',
                'total_estado_fuerza_participante',
                'total_kilometros_recorridos',
            ],
            'CABALLEROS DEL CAMINO' => [
                'total_cantidad',
                'total_acompanamientos',
                'total_abanderamientos',
                'total_auxilios_viales',
                'total_estado_fuerza_participante',
                'total_kilometros_recorridos',
            ],
            'PROXIMIDAD SOCIAL' => [
                'total_prox_empresas',
                'total_prox_tiendas_conveniencia',
                'total_prox_escuelas',
                'total_prox_hospitales',
            ],
        ];

        $normalizar = function ($texto) {
            $texto = mb_strtoupper(trim((string) $texto), 'UTF-8');
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
            return $ascii !== false ? $ascii : $texto;
        };
    @endphp

    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Resumen consolidado</h3>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <strong>Fecha:</strong><br>
                            {{ \Carbon\Carbon::parse($fecha ?? now()->format('Y-m-d'))->format('d-m-Y') }}
                        </div>

                        <div class="col-md-3">
                            <strong>Delegación:</strong><br>
                            {{ $operativo->delegacion->nombre ?? 'N/D' }}
                        </div>

                        <div class="col-md-3">
                            <strong>Destacamento:</strong><br>
                            {{ $operativo->destacamento->nombre ?? 'N/D' }}
                        </div>

                        <div class="col-md-3">
                            <strong>Operativo:</strong><br>
                            {{ $operativo->catalogo->nombre ?? 'Guardianes del Camino' }}
                        </div>
                    </div>

                    @if(filled($operativo->descripcion))
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="alert alert-info mb-0">
                                    <strong>Descripción general:</strong><br>
                                    {{ $operativo->descripcion }}
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($resumen->count() == 0)
                        <div class="alert alert-info">
                            No hay dispositivos registrados para la fecha seleccionada.
                        </div>
                    @endif

                    @foreach($resumen as $item)
                        @php
                            $nombreCatalogo = $item->catalogo->nombre ?? 'Dispositivo';
                            $nombreNormalizado = $normalizar($nombreCatalogo);
                            $camposMostrar = [];

                            foreach ($camposPorCatalogo as $clave => $campos) {
                                if ($normalizar($clave) === $nombreNormalizado) {
                                    $camposMostrar = $campos;
                                    break;
                                }
                            }
                        @endphp

                        <div class="card card-outline card-success mb-3">
                            <div class="card-header">
                                <h3 class="card-title">{{ $nombreCatalogo }}</h3>
                            </div>

                            <div class="card-body">
                                <div class="row">
                                    @foreach($camposMostrar as $campo)
                                        <div class="col-md-3 mb-3">
                                            <div class="small-box bg-info">
                                                <div class="inner">
                                                    <h4>
                                                        @if($campo === 'total_kilometros_recorridos')
                                                            {{ number_format((float) ($item->{$campo} ?? 0), 2) }}
                                                        @else
                                                            {{ $item->{$campo} ?? 0 }}
                                                        @endif
                                                    </h4>
                                                    <p>{{ $labelsCampos[$campo] ?? $campo }}</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="fa-solid fa-chart-column"></i>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if(!empty($item->total_crps_participantes))
                                    <div class="row">
                                        <div class="col-md-12">
                                            <strong>CRPS participantes:</strong><br>
                                            {{ $item->total_crps_participantes }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="card card-outline card-warning mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Totales generales</h3>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="small-box bg-primary">
                                        <div class="inner">
                                            <h4>{{ $totalesGenerales->vehiculos_inspeccionados ?? 0 }}</h4>
                                            <p>Vehículos inspeccionados</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fa-solid fa-car"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="small-box bg-primary">
                                        <div class="inner">
                                            <h4>{{ $totalesGenerales->personas_inspeccionadas ?? 0 }}</h4>
                                            <p>Personas inspeccionadas</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fa-solid fa-users"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="small-box bg-secondary">
                                        <div class="inner">
                                            <h4>{{ $totalesGenerales->vehiculos_impactados ?? 0 }}</h4>
                                            <p>Vehículos impactados</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fa-solid fa-car-burst"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="small-box bg-secondary">
                                        <div class="inner">
                                            <h4>{{ $totalesGenerales->personas_impactadas ?? 0 }}</h4>
                                            <p>Personas impactadas</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fa-solid fa-user-injured"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h4>{{ $totalesGenerales->antecedentes_personas ?? 0 }}</h4>
                                            <p>Antecedentes personas</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fa-solid fa-id-card"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h4>{{ $totalesGenerales->antecedentes_vehiculos ?? 0 }}</h4>
                                            <p>Antecedentes vehículos</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fa-solid fa-car-side"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h4>{{ $totalesGenerales->antecedentes_motos ?? 0 }}</h4>
                                            <p>Antecedentes motos</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fa-solid fa-motorcycle"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h4>{{ $totalesGenerales->antecedentes_camiones ?? 0 }}</h4>
                                            <p>Antecedentes camiones</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fa-solid fa-truck"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="small-box bg-danger">
                                        <div class="inner">
                                            <h4>{{ $totalesGenerales->puestas_disposicion ?? 0 }}</h4>
                                            <p>Puestas a disposición</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fa-solid fa-gavel"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="small-box bg-success">
                                        <div class="inner">
                                            <h4>{{ $totalesGenerales->vehiculos_recuperados ?? 0 }}</h4>
                                            <p>Vehículos recuperados</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fa-solid fa-car-rear"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="small-box bg-success">
                                        <div class="inner">
                                            <h4>{{ $totalesGenerales->armas_aseguradas ?? 0 }}</h4>
                                            <p>Armas aseguradas</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fa-solid fa-shield-halved"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="small-box bg-success">
                                        <div class="inner">
                                            <h4>{{ $totalesGenerales->mercancia_recuperada ?? 0 }}</h4>
                                            <p>Mercancía recuperada</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fa-solid fa-box-open"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="small-box bg-warning">
                                        <div class="inner">
                                            <h4>{{ $totalesGenerales->decomiso_drogas ?? 0 }}</h4>
                                            <p>Decomiso drogas</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="small-box bg-dark">
                                        <div class="inner">
                                            <h4>{{ $totalesGenerales->estado_fuerza_participante ?? 0 }}</h4>
                                            <p>Estado de fuerza</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fa-solid fa-user-shield"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="small-box bg-dark">
                                        <div class="inner">
                                            <h4>{{ number_format((float) ($totalesGenerales->kilometros_recorridos ?? 0), 2) }}</h4>
                                            <p>Kilómetros recorridos</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fa-solid fa-road"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if(!empty($totalesGenerales->crps_participantes))
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <strong>CRPS participantes (global):</strong><br>
                                        {{ $totalesGenerales->crps_participantes }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .card-title {
            font-weight: 600;
        }

        .small-box {
            border-radius: 14px;
        }
    </style>
@stop
