@extends('adminlte::page')

@section('title', 'Resumen de Vialidades Urbanas')

@section('content_header')
    <h1>Resumen de Vialidades Urbanas</h1>
@stop

@section('content')
<div class="container-fluid">
    <div class="card shadow-lg border-0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="card-title mb-0">Consolidado diario</h3>
                <small class="text-muted">
                    Fecha: {{ \Carbon\Carbon::parse($fechaSeleccionada)->format('d/m/Y') }}
                </small>
            </div>

            <div>
                <a href="{{ route('vialidades_urbanas.index', ['fecha' => $fechaSeleccionada]) }}" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> Volver
                </a>
                <button onclick="window.print()" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-print"></i> Imprimir
                </button>
            </div>
        </div>

        <div class="card-body">

            <div class="text-center mb-4">
                <h4 class="mb-1" style="font-weight:700;">GUARDIA CIVIL</h4>
                <h5 class="mb-1">COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL</h5>
                <h6 class="mb-0">UNIDAD DE PROTECCIÓN EN VIALIDADES URBANAS</h6>
            </div>

            <div class="row mb-4 text-center">
                <div class="col-md-3 col-6 mb-3">
                    <div class="resumen-box">
                        <div class="resumen-numero">{{ $totales['dispositivos'] ?? 0 }}</div>
                        <div class="resumen-label">Dispositivos</div>
                    </div>
                </div>

                <div class="col-md-3 col-6 mb-3">
                    <div class="resumen-box">
                        <div class="resumen-numero">{{ $totales['elementos'] ?? 0 }}</div>
                        <div class="resumen-label">Elementos</div>
                    </div>
                </div>

                <div class="col-md-3 col-6 mb-3">
                    <div class="resumen-box">
                        <div class="resumen-numero">{{ $totales['crp'] ?? 0 }}</div>
                        <div class="resumen-label">CRP</div>
                    </div>
                </div>

                <div class="col-md-3 col-6 mb-3">
                    <div class="resumen-box">
                        <div class="resumen-numero">{{ ($totales['motopatrullas'] ?? 0) + ($totales['unidades_motorizadas'] ?? 0) + ($totales['patrullas'] ?? 0) }}</div>
                        <div class="resumen-label">Unidades</div>
                    </div>
                </div>

                <div class="col-md-3 col-6 mb-3">
                    <div class="resumen-box">
                        <div class="resumen-numero">{{ $totales['fenix'] ?? 0 }}</div>
                        <div class="resumen-label">Fénix</div>
                    </div>
                </div>

                <div class="col-md-3 col-6 mb-3">
                    <div class="resumen-box">
                        <div class="resumen-numero">{{ $totales['motopatrullas'] ?? 0 }}</div>
                        <div class="resumen-label">Motopatrullas</div>
                    </div>
                </div>

                <div class="col-md-3 col-6 mb-3">
                    <div class="resumen-box">
                        <div class="resumen-numero">{{ $totales['patrullas'] ?? 0 }}</div>
                        <div class="resumen-label">Patrullas</div>
                    </div>
                </div>

                <div class="col-md-3 col-6 mb-3">
                    <div class="resumen-box">
                        <div class="resumen-numero">{{ $totales['gruas'] ?? 0 }}</div>
                        <div class="resumen-label">Grúas</div>
                    </div>
                </div>
            </div>

            <hr>

            <h4 class="mb-3">Dispositivos del día</h4>

            @forelse($dispositivos as $dispositivo)
                <div class="dispositivo-card mb-4">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="mb-2">
                                {{ $dispositivo->asunto ?: 'SIN ASUNTO' }}
                            </h5>

                            <div class="mb-2 text-muted">
                                <strong>Hora:</strong> {{ substr((string) $dispositivo->hora, 0, 5) }}
                                &nbsp;&nbsp;|&nbsp;&nbsp;
                                <strong>Catálogo:</strong> {{ optional($dispositivo->catalogo)->nombre ?? 'SIN CATÁLOGO' }}
                            </div>

                            <div class="mb-2">
                                <strong>Municipio:</strong> {{ $dispositivo->municipio ?: 'SIN MUNICIPIO' }}
                            </div>

                            <div class="mb-2">
                                <strong>Lugar:</strong> {{ $dispositivo->lugar ?: 'SIN LUGAR' }}
                            </div>

                            @if(!empty($dispositivo->evento))
                                <div class="mb-2">
                                    <strong>Evento:</strong> {{ $dispositivo->evento }}
                                </div>
                            @endif

                            @if(!empty($dispositivo->supervision))
                                <div class="mb-2">
                                    <strong>Supervisión:</strong> {{ $dispositivo->supervision }}
                                </div>
                            @endif

                            @if(!empty($dispositivo->descripcion))
                                <div class="mb-2">
                                    <strong>Descripción:</strong><br>
                                    {{ $dispositivo->descripcion }}
                                </div>
                            @endif

                            @if(!empty($dispositivo->objetivo))
                                <div class="mb-2">
                                    <strong>Objetivo:</strong><br>
                                    {{ $dispositivo->objetivo }}
                                </div>
                            @endif
                        </div>

                        <div class="col-md-4">
                            @if(optional($dispositivo->fotoPortada)->ruta)
                                <img
                                    src="{{ asset('storage/' . ltrim($dispositivo->fotoPortada->ruta, '/')) }}"
                                    alt="Portada"
                                    class="img-fluid rounded shadow-sm resumen-foto"
                                >
                            @else
                                <div class="sin-foto">
                                    Sin foto
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="row mt-3 text-center">
                        <div class="col-md-2 col-6 mb-2">
                            <div class="estado-box">
                                <strong>{{ (int) $dispositivo->elementos }}</strong><br>Elementos
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-2">
                            <div class="estado-box">
                                <strong>{{ (int) $dispositivo->crp }}</strong><br>CRP
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-2">
                            <div class="estado-box">
                                <strong>{{ (int) $dispositivo->motopatrullas }}</strong><br>Motopatrullas
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-2">
                            <div class="estado-box">
                                <strong>{{ (int) $dispositivo->fenix }}</strong><br>Fénix
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-2">
                            <div class="estado-box">
                                <strong>{{ (int) $dispositivo->unidades_motorizadas }}</strong><br>Unid. mot.
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-2">
                            <div class="estado-box">
                                <strong>{{ (int) $dispositivo->patrullas }}</strong><br>Patrullas
                            </div>
                        </div>
                    </div>

                    @if($dispositivo->detalles->count())
                        <div class="mt-4">
                            <h6 class="mb-3">Detalles capturados</h6>

                            @foreach($dispositivo->detalles as $detalle)
                                <div class="detalle-item mb-2">
                                    @if(!empty($detalle->hora))
                                        <span class="badge badge-secondary mr-2">{{ substr((string) $detalle->hora, 0, 5) }}</span>
                                    @endif

                                    @if(!empty($detalle->titulo))
                                        <strong>{{ $detalle->titulo }}</strong><br>
                                    @endif

                                    <span>{{ $detalle->contenido }}</span>

                                    @if(!empty($detalle->ubicacion))
                                        <br><small class="text-muted">{{ $detalle->ubicacion }}</small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="alert alert-secondary mb-0">
                    No hay dispositivos registrados para la fecha seleccionada.
                </div>
            @endforelse

        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .resumen-box {
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 16px;
        padding: 16px 10px;
        height: 100%;
    }

    .resumen-numero {
        font-size: 28px;
        font-weight: 700;
        line-height: 1;
        color: #fff;
        margin-bottom: 6px;
    }

    .resumen-label {
        font-size: 13px;
        color: #d1d5db;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .dispositivo-card {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.10);
        border-radius: 18px;
        padding: 18px;
    }

    .estado-box {
        background: rgba(255,255,255,0.05);
        border-radius: 12px;
        padding: 12px 8px;
        border: 1px solid rgba(255,255,255,0.08);
    }

    .resumen-foto {
        width: 100%;
        height: 220px;
        object-fit: cover;
    }

    .sin-foto {
        width: 100%;
        height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: rgba(255,255,255,0.05);
        border: 1px dashed rgba(255,255,255,0.15);
        color: #9ca3af;
    }

    .detalle-item {
        background: rgba(255,255,255,0.04);
        border-left: 4px solid rgba(255,255,255,0.20);
        padding: 12px 14px;
        border-radius: 10px;
    }

    @media print {
        .btn,
        .main-header,
        .main-sidebar,
        .content-header,
        .no-print {
            display: none !important;
        }

        .content-wrapper,
        .card,
        .card-body {
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            border: none !important;
        }
    }
</style>
@stop
