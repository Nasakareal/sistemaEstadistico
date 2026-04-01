@extends('adminlte::page')

@section('title', 'Mapa de Destacamentos')

@section('content_header')
    <h1>Mapa de Destacamentos</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-info mapa-destacamentos-card">
                <div class="card-header">
                    <h3 class="card-title">Ubicación de todos los destacamentos registrados</h3>

                    <div class="card-tools">
                        <a href="{{ route('destacamentos.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if($destacamentos->isEmpty())
                        <div class="alert alert-warning mb-0">
                            No hay destacamentos con coordenadas registradas.
                        </div>
                    @else
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="small-box bg-info">
                                    <div class="inner">
                                        <h3>{{ $destacamentos->count() }}</h3>
                                        <p>Destacamentos con ubicación</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa-solid fa-map-location-dot"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="mapa_destacamentos"></div>
                    @endif
                </div>
            </div>

        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

    <style>
        .mapa-destacamentos-card {
            border-top: 3px solid #17a2b8 !important;
            background: rgba(7, 18, 40, 0.78) !important;
            backdrop-filter: blur(6px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, .28);
        }

        .mapa-destacamentos-card > .card-header {
            background: rgba(255, 255, 255, 0.04) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #f8fafc !important;
        }

        .mapa-destacamentos-card > .card-header .card-title {
            color: #f8fafc !important;
            font-weight: 700 !important;
            font-size: 1.05rem !important;
        }

        .mapa-destacamentos-card > .card-body {
            background: transparent !important;
            color: #e5e7eb !important;
        }

        #mapa_destacamentos {
            width: 100%;
            height: 75vh;
            min-height: 520px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.10);
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .22);
        }

        .leaflet-container {
            background: #0f172a !important;
        }

        .destacamento-popup {
            min-width: 220px;
            color: #111827;
            line-height: 1.45;
        }

        .destacamento-popup .titulo {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: .35rem;
            color: #0f172a;
        }

        .destacamento-popup .linea {
            margin-bottom: .2rem;
            font-size: .92rem;
        }

        .destacamento-popup .acciones {
            margin-top: .65rem;
        }

        .destacamento-popup .btn {
            font-size: .85rem;
            padding: .3rem .6rem;
        }

        .small-box.bg-info {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,.18);
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

    @php
        $destacamentosMapa = $destacamentos->map(function ($d) {
            return [
                'id' => $d->id,
                'clave' => $d->clave,
                'nombre' => $d->nombre,
                'municipio' => $d->municipio,
                'direccion' => $d->direccion,
                'telefono' => $d->telefono,
                'lat' => $d->lat,
                'lng' => $d->lng,
                'show_url' => route('destacamentos.show', $d->id),
            ];
        })->values();
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if($destacamentos->isNotEmpty())
                const destacamentos = @json($destacamentosMapa);

                const mapa = L.map('mapa_destacamentos');

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(mapa);

                const castleIcon = L.icon({
                    iconUrl: "{{ asset('img/castle.png') }}",
                    iconSize: [42, 42],
                    iconAnchor: [21, 42],
                    popupAnchor: [0, -38]
                });

                const bounds = [];

                destacamentos.forEach(function (d) {
                    if (d.lat === null || d.lng === null || d.lat === '' || d.lng === '') {
                        return;
                    }

                    const popupHtml =
                        '<div class="destacamento-popup">' +
                            '<div class="titulo">' + (d.nombre ?? 'DESTACAMENTO') + '</div>' +
                            '<div class="linea"><strong>Clave:</strong> ' + (d.clave ?? 'Sin clave') + '</div>' +
                            '<div class="linea"><strong>Municipio:</strong> ' + (d.municipio ?? 'No especificado') + '</div>' +
                            '<div class="linea"><strong>Dirección:</strong> ' + (d.direccion ?? 'No especificada') + '</div>' +
                            '<div class="acciones">' +
                                '<a href="' + d.show_url + '" class="btn btn-info btn-sm">' +
                                    '<i class="fa-regular fa-eye"></i> Ver destacamento' +
                                '</a>' +
                            '</div>' +
                        '</div>';

                    const lat = parseFloat(d.lat);
                    const lng = parseFloat(d.lng);

                    const marker = L.marker([lat, lng], {
                        icon: castleIcon
                    }).addTo(mapa);

                    marker.bindPopup(popupHtml);

                    bounds.push([lat, lng]);
                });

                if (bounds.length === 1) {
                    mapa.setView(bounds[0], 15);
                } else if (bounds.length > 1) {
                    mapa.fitBounds(bounds, { padding: [40, 40] });
                } else {
                    mapa.setView([19.7008, -101.1844], 7);
                }

                setTimeout(function () {
                    mapa.invalidateSize();
                }, 350);
            @endif
        });
    </script>
@stop
