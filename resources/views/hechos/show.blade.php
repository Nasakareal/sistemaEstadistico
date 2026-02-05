@extends('adminlte::page')

@section('title', 'Detalles del Hecho')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between flex-wrap">
        <h1 class="mb-0">Detalles del Hecho</h1>

        @can('editar hechos')
            <a href="{{ route('hechos.edit', $hecho->id) }}" class="btn btn-warning btn-sm">
                <i class="fa-solid fa-pen-to-square"></i> Editar hecho
            </a>
        @endcan
    </div>
@stop

@section('content')
@php
    $campos = [
        'id' => 'ID',
        'folio_c5i' => 'Folio C5i',
        'perito' => 'Perito',
        'unidad' => 'Unidad (número económico)',
        'unidad_org_id' => 'Unidad organizacional (ID)',
        'hora' => 'Hora',
        'fecha' => 'Fecha',
        'sector' => 'Sector',
        'calle' => 'Calle',
        'colonia' => 'Colonia',
        'entre_calles' => 'Entre calles',
        'municipio' => 'Municipio',
        'tipo_hecho' => 'Tipo de hecho',
        'superficie_via' => 'Superficie de vía',
        'condiciones' => 'Condiciones',
        'control_transito' => 'Control de tránsito',
        'checaron_antecedentes' => 'Checaron antecedentes',
        'causas' => 'Causas',
        'colision_camino' => 'Colisión / Camino',
        'danos_patrimoniales' => 'Daños patrimoniales',
        'propiedades_afectadas' => 'Propiedades afectadas',
        'monto_danos_patrimoniales' => 'Monto daños patrimoniales',
        'oficio_mp' => 'Oficio MP',
        'vehiculos_mp' => 'Vehículos MP',
        'personas_mp' => 'Personas MP',
        'situacion' => 'Situación',
    ];

    $fmt = function ($v) {
        if (is_null($v) || $v === '') return 'No especificado';
        if (is_bool($v)) return $v ? 'Sí' : 'No';
        return (string) $v;
    };

    $lat = old('lat', $hecho->lat ?? null);
    $lng = old('lng', $hecho->lng ?? null);
    $precision = old('precision_m', $hecho->precision_m ?? null);
@endphp

<div class="row justify-content-center">
    <div class="col-md-10">

        <div class="card card-outline card-info">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0">Información Registrada</h3>

                @can('editar hechos')
                    <a href="{{ route('hechos.edit', $hecho->id) }}" class="btn btn-success btn-sm" title="Editar hecho">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                @endcan
            </div>

            <div class="card-body">
                <div class="row">
                    @foreach($campos as $field => $label)
                        @php
                            $isGreen = in_array($field, ['id', 'situacion'], true);
                        @endphp

                        <div class="col-12 col-md-3">
                            <div class="form-group">
                                <label>{{ $label }}</label>
                                <p class="form-control-static {{ $isGreen ? 'sv-green' : '' }}">
                                    {{ $fmt(data_get($hecho, $field)) }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <hr>

                <div class="row">
                    <div class="col-12">
                        <div class="card card-outline card-primary mb-0">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h3 class="card-title mb-0">
                                    <i class="fa-solid fa-map-location-dot"></i> Ubicación
                                </h3>

                                @if(!empty($lat) && !empty($lng))
                                    <div class="d-flex" style="gap:8px; flex-wrap:wrap;">
                                        <a class="btn btn-outline-info btn-sm"
                                           href="https://www.google.com/maps?q={{ $lat }},{{ $lng }}"
                                           target="_blank" rel="noopener">
                                            <i class="fa-solid fa-up-right-from-square"></i> Abrir en Google Maps
                                        </a>

                                        <a class="btn btn-outline-secondary btn-sm"
                                           href="https://www.openstreetmap.org/?mlat={{ $lat }}&mlon={{ $lng }}#map=18/{{ $lat }}/{{ $lng }}"
                                           target="_blank" rel="noopener">
                                            <i class="fa-solid fa-up-right-from-square"></i> Abrir en OSM
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <div class="card-body">
                                @if(!empty($lat) && !empty($lng))
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Latitud</label>
                                                <p class="form-control-static">{{ $lat }}</p>
                                            </div>
                                            <div class="form-group">
                                                <label>Longitud</label>
                                                <p class="form-control-static">{{ $lng }}</p>
                                            </div>
                                            @if(!empty($precision))
                                                <div class="form-group">
                                                    <label>Precisión</label>
                                                    <p class="form-control-static">± {{ $precision }} m</p>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="col-md-8">
                                            <div id="map_hecho" style="width:100%; height:340px; border-radius:14px; overflow:hidden; border:1px solid rgba(255,255,255,.12);"></div>
                                            <small class="text-muted d-block mt-2">
                                                El marcador indica el punto recibido por coordenadas.
                                            </small>
                                        </div>
                                    </div>
                                @else
                                    <p class="mb-0">No hay coordenadas registradas para este hecho.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Responsable</label>
                            <p class="form-control-static">{{ $hecho->responsable->nombre ?? 'No asignado' }}</p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Evidencia Adjunta</label>
                            @if (!empty($hecho->evidencia))
                                <a href="{{ asset('storage/' . $hecho->evidencia) }}" class="btn btn-info btn-sm" target="_blank" rel="noopener">
                                    <i class="fa-solid fa-paperclip"></i> Ver evidencia
                                </a>
                            @else
                                <p class="form-control-static">No hay evidencia adjunta.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-12">
                        <h3 class="mb-3">Vehículos Asociados</h3>

                        @if($hecho->vehiculos->count())
                            <div class="row g-3">
                                @foreach($hecho->vehiculos as $vehiculo)
                                    <div class="col-sm-6 col-md-4">
                                        <div class="card h-100">
                                            <div class="card-header d-flex align-items-center justify-content-between">
                                                <strong class="text-truncate" style="max-width: 80%;">
                                                    {{ $vehiculo->marca ?? 'SIN MARCA' }} - {{ $vehiculo->modelo ?? 'SIN MODELO' }}
                                                </strong>

                                                @can('editar vehiculos')
                                                    <a href="{{ route('vehiculos.edit', ['hecho' => $hecho->id, 'vehiculo' => $vehiculo->id]) }}"
                                                       class="btn btn-success btn-sm"
                                                       title="Editar vehículo">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </a>
                                                @endcan
                                            </div>

                                            <div class="card-body d-flex flex-column justify-content-between text-center">
                                                @if(!empty($vehiculo->fotos))
                                                    <img src="{{ asset('storage/' . $vehiculo->fotos) }}"
                                                         class="img-thumbnail mb-2"
                                                         style="width:100%; height:auto;">
                                                @else
                                                    <p class="text-muted">No hay foto disponible.</p>
                                                @endif

                                                @if ($vehiculo->corralon !== null)
                                                    <a href="{{ route('liberacion.publica', $vehiculo->id) }}"
                                                       class="btn btn-outline-primary btn-block mt-2 btn-liberacion">
                                                        <i class="fa-solid fa-file-lines"></i> Ver Liberación
                                                    </a>
                                                @else
                                                    <p class="text-muted mt-2 mb-0">No está en corralón</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p>No hay vehículos asociados a este hecho.</p>
                        @endif
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-12 text-center">
                        <a href="{{ route('hechos.index') }}" class="btn btn-secondary">
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<style>
  .content-wrapper .form-control,
  .content-wrapper .custom-select,
  .content-wrapper select.form-control,
  .content-wrapper textarea.form-control {
      background-color: #ffffff !important;
      color: #111827 !important;
      border: 1px solid rgba(17,24,39,.25) !important;
      box-shadow: none !important;
  }

  .content-wrapper .form-control::placeholder,
  .content-wrapper textarea.form-control::placeholder {
      color: rgba(17,24,39,.55) !important;
      opacity: 1 !important;
  }

  .content-wrapper .form-control[readonly],
  .content-wrapper .form-control:disabled,
  .content-wrapper select.form-control:disabled,
  .content-wrapper textarea.form-control:disabled {
      background-color: #f3f4f6 !important;
      color: #111827 !important;
      -webkit-text-fill-color: #111827 !important;
      opacity: 1 !important;
  }

  .content-wrapper .form-control:focus,
  .content-wrapper .custom-select:focus {
      border-color: rgba(59,130,246,.65) !important;
      box-shadow: 0 0 0 .2rem rgba(59,130,246,.15) !important;
  }

  .content-wrapper .form-group label {
      color: #e5e7eb !important;
      font-weight: 700;
  }

  .content-wrapper .form-control-static {
      color: #e5e7eb !important;
  }

  .content-wrapper .form-control-static.sv-green {
      color: #22c55e !important;
      font-weight: 800 !important;
  }

  .leaflet-container {
      background: #0b1220;
      border-radius: 14px;
  }
</style>
@stop

@section('js')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lat = @json($lat);
        const lng = @json($lng);

        if (lat && lng) {
            const map = L.map('map_hecho', {
                zoomControl: true,
                scrollWheelZoom: true
            }).setView([parseFloat(lat), parseFloat(lng)], 17);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 20,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const marker = L.marker([parseFloat(lat), parseFloat(lng)]).addTo(map);

            marker.bindPopup(`
                <div style="min-width:180px;">
                    <strong>Punto recibido</strong><br>
                    Lat: ${lat}<br>
                    Lng: ${lng}
                </div>
            `);

            marker.openPopup();
        }
    });
</script>
@stop
