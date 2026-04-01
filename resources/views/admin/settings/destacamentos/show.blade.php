@extends('adminlte::page')

@section('title', 'Detalles del Destacamento')

@section('content_header')
    <h1>Detalles del Destacamento</h1>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10">

            <div class="card card-outline card-info">

                <div class="card-header">
                    <h3 class="card-title">Datos Registrados</h3>

                    <div class="card-tools">
                        <a href="{{ route('destacamentos.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>

                        @can('editar destacamentos')
                            <a href="{{ route('destacamentos.edit', $destacamento->id) }}" class="btn btn-success btn-sm">
                                <i class="fa-regular fa-pen-to-square"></i> Editar
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Clave</label>
                                <p class="form-control-static">{{ $destacamento->clave ?? 'Sin clave' }}</p>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Nombre</label>
                                <p class="form-control-static">{{ $destacamento->nombre }}</p>
                            </div>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Municipio</label>
                                <p class="form-control-static">{{ $destacamento->municipio ?? 'No especificado' }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Unidad</label>
                                <p class="form-control-static">{{ optional($destacamento->unidad)->nombre ?? 'No especificada' }}</p>
                            </div>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Latitud</label>
                                <p class="form-control-static">
                                    {{ $destacamento->lat !== null ? $destacamento->lat : 'No especificada' }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Longitud</label>
                                <p class="form-control-static">
                                    {{ $destacamento->lng !== null ? $destacamento->lng : 'No especificada' }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Estado</label>
                                <p class="form-control-static">
                                    @if($destacamento->activo)
                                        <span class="badge badge-success">ACTIVO</span>
                                    @else
                                        <span class="badge badge-secondary">INACTIVO</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Dirección</label>
                                <p class="form-control-static">{{ $destacamento->direccion ?? 'No especificada' }}</p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Teléfono</label>
                                <p class="form-control-static">{{ $destacamento->telefono ?? 'No especificado' }}</p>
                            </div>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Responsable</label>
                                <p class="form-control-static">{{ $destacamento->responsable ?? 'No especificado' }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Referencia</label>
                                <p class="form-control-static">{{ $destacamento->referencia ?? 'No especificada' }}</p>
                            </div>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha de Creación</label>
                                <p class="form-control-static">{{ optional($destacamento->created_at)->format('d-m-Y H:i') }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Última Actualización</label>
                                <p class="form-control-static">{{ optional($destacamento->updated_at)->format('d-m-Y H:i') }}</p>
                            </div>
                        </div>

                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="mb-3">Ubicación en el mapa</h5>

                            @if($destacamento->lat !== null && $destacamento->lng !== null)
                                <div id="mapa_destacamento"></div>

                                <div class="mt-3">
                                    <a href="https://www.google.com/maps?q={{ $destacamento->lat }},{{ $destacamento->lng }}"
                                       target="_blank"
                                       class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-location-dot"></i> Abrir en Google Maps
                                    </a>
                                </div>
                            @else
                                <div class="alert alert-warning mb-0">
                                    Este destacamento no tiene coordenadas registradas.
                                </div>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-12 text-center">
                            <a href="{{ route('destacamentos.index') }}" class="btn btn-secondary">
                                <i class="fa-solid fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

    <style>
        .card.card-outline.card-info {
            border-top: 3px solid #17a2b8 !important;
            background: rgba(7, 18, 40, 0.78) !important;
            backdrop-filter: blur(6px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, .28);
        }

        .card.card-outline.card-info > .card-header {
            background: rgba(255, 255, 255, 0.04) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #f8fafc !important;
        }

        .card.card-outline.card-info > .card-header .card-title {
            color: #f8fafc !important;
            font-weight: 700 !important;
            font-size: 1.15rem !important;
        }

        .card.card-outline.card-info > .card-body {
            background: transparent !important;
            color: #e5e7eb !important;
        }

        .form-group {
            margin-bottom: 1.15rem;
        }

        .form-group label {
            display: block;
            margin-bottom: .5rem;
            color: #f3f4f6 !important;
            font-weight: 700 !important;
            font-size: 1rem !important;
            text-shadow: 0 1px 1px rgba(0,0,0,.25);
        }

        .form-control-static {
            display: flex;
            align-items: center;
            width: 100%;
            min-height: 48px;
            padding: .7rem .9rem;
            margin-top: 0;
            font-size: 1rem;
            line-height: 1.5;
            color: #f8fafc !important;
            background: rgba(255, 255, 255, 0.08) !important;
            border: 1px solid rgba(255, 255, 255, 0.14) !important;
            border-radius: .45rem;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.03);
            word-break: break-word;
        }

        .form-control-static * {
            color: inherit !important;
        }

        .badge.badge-success {
            font-size: .88rem;
            padding: .45rem .8rem;
            box-shadow: none;
        }

        .badge.badge-secondary {
            font-size: .88rem;
            padding: .45rem .8rem;
            box-shadow: none;
        }

        hr {
            border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
        }

        h5.mb-3 {
            color: #f8fafc !important;
            font-weight: 700 !important;
        }

        #mapa_destacamento {
            width: 100%;
            height: 420px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .22);
        }

        .alert.alert-warning {
            background: rgba(255, 193, 7, 0.14) !important;
            border: 1px solid rgba(255, 193, 7, 0.28) !important;
            color: #ffe08a !important;
        }
    </style>
@stop

@section('js')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        @if($destacamento->lat !== null && $destacamento->lng !== null)
            document.addEventListener('DOMContentLoaded', function () {
                const lat = parseFloat(@json($destacamento->lat));
                const lng = parseFloat(@json($destacamento->lng));

                const mapa = L.map('mapa_destacamento').setView([lat, lng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(mapa);

                const popupHtml = `
                    <strong>{{ e($destacamento->nombre) }}</strong><br>
                    Clave: {{ e($destacamento->clave ?? 'Sin clave') }}<br>
                    Municipio: {{ e($destacamento->municipio ?? 'No especificado') }}<br>
                    Dirección: {{ e($destacamento->direccion ?? 'No especificada') }}
                `;

                L.marker([lat, lng]).addTo(mapa).bindPopup(popupHtml).openPopup();

                setTimeout(function () {
                    mapa.invalidateSize();
                }, 300);
            });
        @endif
    </script>
@stop
