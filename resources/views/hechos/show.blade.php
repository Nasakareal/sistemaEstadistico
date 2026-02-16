@extends('adminlte::page')

@section('title', 'Detalles del Hecho')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
        <div class="d-flex align-items-center" style="gap:10px;">
            <h1 class="mb-0">Detalles del Hecho</h1>
            <span class="badge badge-light" style="font-size:.9rem; padding:.4rem .6rem;">
                Folio: {{ $hecho->folio_c5i ?? 'No especificado' }}
            </span>
        </div>

        <div class="d-flex align-items-center" style="gap:8px; flex-wrap:wrap;">
            {{-- NUEVO: botón ver dictamen (solo si existe) --}}
            @if($hecho->dictamen)
                <a href="{{ route('dictamenes.show', $hecho->dictamen->id) }}"
                   class="btn btn-primary btn-sm rounded-circle d-inline-flex align-items-center justify-content-center"
                   style="width:36px;height:36px;padding:0;"
                   title="Ver dictamen">
                    <i class="fa-solid fa-file-lines"></i>
                </a>
            @endif

            @can('editar hechos')
                <a href="{{ route('hechos.edit', $hecho->id) }}"
                   class="btn btn-success btn-sm rounded-circle d-inline-flex align-items-center justify-content-center"
                   style="width:36px;height:36px;padding:0;"
                   title="Editar hecho">
                    <i class="fa-solid fa-pen-to-square"></i>
                </a>
            @endcan

            <a href="{{ route('hechos.descargar', $hecho->id) }}"
               class="btn btn-warning btn-sm rounded-circle d-inline-flex align-items-center justify-content-center"
               style="width:36px;height:36px;padding:0;"
               title="Descargar informe">
                <i class="fas fa-download"></i>
            </a>

            @can('borrar hechos')
                <form action="{{ route('hechos.destroy', $hecho->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="btn btn-danger btn-sm rounded-circle d-inline-flex align-items-center justify-content-center"
                            style="width:36px;height:36px;padding:0;"
                            title="Eliminar hecho"
                            onclick="return confirm('¿Estás seguro de eliminar este hecho?');">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </form>
            @endcan
        </div>
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

        'situacion' => 'Estatus',
    ];

    $fmt = function ($v) {
        if (is_null($v) || $v === '') return 'No especificado';
        if (is_bool($v)) return $v ? 'Sí' : 'No';
        return (string) $v;
    };

    $lat = old('lat', $hecho->lat ?? null);
    $lng = old('lng', $hecho->lng ?? null);
    $precision = old('precision_m', $hecho->precision_m ?? null);

    $dictamen = $hecho->dictamen ?? null;

    $sitRaw = (string) data_get($hecho, 'situacion', '');
    $sit = strtoupper(trim($sitRaw));
    $soloTurnado = ($sit === 'TURNADO'); // <- Regla exacta: solo TURNADO, nada más
    $camposMP = ['oficio_mp', 'vehiculos_mp', 'personas_mp'];
@endphp


<div class="row justify-content-center">
    <div class="col-lg-11 col-xl-10">

        <div class="sv-shell">
            <div class="card sv-card mb-3">
                <div class="card-header sv-card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center" style="gap:10px;">
                        <h3 class="card-title mb-0">Información Registrada</h3>
                        @php
                            $estatus = $fmt(data_get($hecho, 'situacion'));
                            $estatusClass = 'badge-secondary';
                            if (stripos($estatus, 'resuelto') !== false) $estatusClass = 'badge-success';
                            if (stripos($estatus, 'pendiente') !== false) $estatusClass = 'badge-warning';
                        @endphp
                        <span class="badge {{ $estatusClass }}" style="font-size:.9rem; padding:.35rem .6rem;">
                            {{ $estatus }}
                        </span>
                    </div>

                    <div class="d-none d-md-flex align-items-center" style="gap:8px;">
                        <span class="sv-meta">
                            <i class="fa-regular fa-calendar"></i> {{ $fmt($hecho->fecha ?? null) }}
                        </span>
                        <span class="sv-meta">
                            <i class="fa-regular fa-clock"></i> {{ $fmt($hecho->hora ?? null) }}
                        </span>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        @foreach($campos as $field => $label)
                            @php
                                if (in_array($field, $camposMP, true) && !$soloTurnado) {
                                    continue;
                                }

                                $isGreen = in_array($field, ['id', 'situacion'], true);
                            @endphp

                            <div class="col-12 col-md-6 col-lg-3 mb-3">
                                <div class="sv-kv">
                                    <div class="sv-k">{{ $label }}</div>
                                    <div class="sv-v {{ $isGreen ? 'sv-green' : '' }}">
                                        {{ $fmt(data_get($hecho, $field)) }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="sv-divider"></div>

                    <div class="row">
                        <div class="col-12 col-lg-7 mb-3 mb-lg-0">
                            <div class="sv-subcard">
                                <div class="sv-subcard-header">
                                    <div class="d-flex align-items-center" style="gap:8px;">
                                        <i class="fa-solid fa-map-location-dot"></i>
                                        <strong>Ubicación</strong>
                                    </div>

                                    @if(!empty($lat) && !empty($lng))
                                        <div class="d-flex" style="gap:8px; flex-wrap:wrap;">
                                            <a class="btn btn-outline-info btn-sm"
                                               href="https://www.google.com/maps?q={{ $lat }},{{ $lng }}"
                                               target="_blank" rel="noopener">
                                                <i class="fa-solid fa-up-right-from-square"></i> Google Maps
                                            </a>

                                            <a class="btn btn-outline-secondary btn-sm"
                                               href="https://www.openstreetmap.org/?mlat={{ $lat }}&mlon={{ $lng }}#map=18/{{ $lat }}/{{ $lng }}"
                                               target="_blank" rel="noopener">
                                                <i class="fa-solid fa-up-right-from-square"></i> OSM
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                <div class="sv-subcard-body">
                                    @if(!empty($lat) && !empty($lng))
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="sv-kv mb-2">
                                                    <div class="sv-k">Latitud</div>
                                                    <div class="sv-v">{{ $lat }}</div>
                                                </div>
                                                <div class="sv-kv mb-2">
                                                    <div class="sv-k">Longitud</div>
                                                    <div class="sv-v">{{ $lng }}</div>
                                                </div>
                                                @if(!empty($precision))
                                                    <div class="sv-kv">
                                                        <div class="sv-k">Precisión</div>
                                                        <div class="sv-v">± {{ $precision }} m</div>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="col-md-8">
                                                <div id="map_hecho" class="sv-map"></div>
                                                <small class="text-muted d-block mt-2">
                                                    El marcador indica el punto recibido por coordenadas.
                                                </small>
                                            </div>
                                        </div>
                                    @else
                                        <div class="sv-empty">
                                            No hay coordenadas registradas para este hecho.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="sv-kv mt-3">
                            <div class="sv-k">Fotos del hecho</div>
                            <div class="sv-v">
                                <div class="row">
                                    {{-- FOTO LUGAR --}}
                                    <div class="col-12 mb-3">
                                        <div class="sv-photo-title">
                                            <i class="fa-regular fa-image"></i> Foto del lugar
                                        </div>

                                        @if(!empty($hecho->foto_lugar))
                                            <a href="{{ asset('storage/' . $hecho->foto_lugar) }}" target="_blank" rel="noopener">
                                                <img src="{{ asset('storage/' . $hecho->foto_lugar) }}"
                                                     class="sv-photo-img"
                                                     alt="Foto del lugar">
                                            </a>
                                        @else
                                            <div class="sv-empty">No hay foto del lugar.</div>
                                        @endif
                                    </div>

                                    {{-- FOTO SITUACION --}}
                                    <div class="col-12">
                                        <div class="sv-photo-title">
                                            <i class="fa-regular fa-image"></i> Foto de la situación
                                        </div>

                                        @if(!empty($hecho->foto_situacion))
                                            <a href="{{ asset('storage/' . $hecho->foto_situacion) }}" target="_blank" rel="noopener">
                                                <img src="{{ asset('storage/' . $hecho->foto_situacion) }}"
                                                     class="sv-photo-img"
                                                     alt="Foto de la situación">
                                            </a>
                                        @else
                                            <div class="sv-empty">No hay foto de la situación.</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>


                            <div class="sv-subcard mt-3">
                                <div class="sv-subcard-header">
                                    <div class="d-flex align-items-center" style="gap:8px;">
                                        <i class="fa-solid fa-car-burst"></i>
                                        <strong>Acciones rápidas</strong>
                                    </div>
                                </div>
                                <div class="sv-subcard-body">
                                    <div class="d-flex flex-wrap" style="gap:10px;">
                                        {{-- NUEVO: botón dictamen --}}
                                        @if($dictamen)
                                            <a href="{{ route('dictamenes.show', $dictamen->id) }}" class="btn btn-primary btn-sm">
                                                <i class="fa-solid fa-file-lines"></i> Ver dictamen
                                            </a>
                                        @endif

                                        @can('editar hechos')
                                            <a href="{{ route('hechos.edit', $hecho->id) }}" class="btn btn-success btn-sm">
                                                <i class="fa-solid fa-pen-to-square"></i> Editar hecho
                                            </a>
                                        @endcan

                                        <a href="{{ route('hechos.descargar', $hecho->id) }}" class="btn btn-warning btn-sm">
                                            <i class="fas fa-download"></i> Descargar informe
                                        </a>

                                        <a href="{{ route('hechos.index') }}" class="btn btn-secondary btn-sm">
                                            <i class="fa-solid fa-arrow-left"></i> Volver
                                        </a>
                                    </div>

                                    {{-- si quieres mostrar un hint cuando NO hay dictamen --}}
                                    @if(!$dictamen)
                                        <div class="sv-hint mt-2">Este hecho no tiene dictamen</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sv-divider"></div>

                    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                        <h3 class="mb-0">Vehículos Asociados</h3>
                        <span class="badge badge-light" style="font-size:.9rem; padding:.35rem .6rem;">
                            Total: {{ $hecho->vehiculos->count() }}
                        </span>
                    </div>

                    <div class="mt-3">
                        @if($hecho->vehiculos->count())
                            <div class="row">
                                @foreach($hecho->vehiculos as $vehiculo)
                                    @php
                                        $tieneGrua = false;
                                        if (isset($vehiculo->servicios) && $vehiculo->servicios->count()) {
                                            $tieneGrua = $vehiculo->servicios->whereNotNull('grua_id')->count() > 0;
                                        }

                                        $estaResguardado = $vehiculo->corralon !== null;

                                        $corralonNombre = null;
                                        if (is_object($vehiculo->corralon)) {
                                            $corralonNombre = $vehiculo->corralon->nombre ?? (string)($vehiculo->corralon->id ?? null);
                                        } else {
                                            $corralonNombre = $vehiculo->corralon;
                                        }
                                        $corralonNombre = $corralonNombre ?: 'No especificado';

                                        $placas = $vehiculo->placas ?? null;
                                        $placasFmt = (!empty($placas) && trim((string)$placas) !== '') ? $placas : 'SIN PLACAS';
                                    @endphp

                                    <div class="col-md-6 col-xl-4 mb-3">
                                        <div class="sv-veh-card h-100">
                                            <div class="sv-veh-head">
                                                <div class="sv-veh-title">
                                                    <div class="sv-veh-name text-truncate">
                                                        {{ $vehiculo->marca ?? 'SIN MARCA' }} · {{ $vehiculo->modelo ?? 'SIN MODELO' }}
                                                    </div>
                                                    <div class="sv-veh-plates">
                                                        <span class="badge badge-light" style="font-size:.85rem; padding:.35rem .55rem;">
                                                            <i class="fa-solid fa-id-card"></i> {{ $placasFmt }}
                                                        </span>
                                                    </div>
                                                </div>

                                                @can('editar vehiculos')
                                                    <a href="{{ route('vehiculos.edit', ['hecho' => $hecho->id, 'vehiculo' => $vehiculo->id]) }}"
                                                       class="btn btn-success btn-sm rounded-circle d-inline-flex align-items-center justify-content-center"
                                                       style="width:34px;height:34px;padding:0;"
                                                       title="Editar vehículo">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </a>
                                                @endcan
                                            </div>

                                            <div class="sv-veh-body">
                                                @if(!empty($vehiculo->fotos))
                                                    <img src="{{ asset('storage/' . $vehiculo->fotos) }}" class="sv-veh-img" alt="Foto del vehículo">
                                                @else
                                                    <div class="sv-veh-noimg">
                                                        <i class="fa-regular fa-image"></i>
                                                        <span>No hay foto disponible</span>
                                                    </div>
                                                @endif

                                                <div class="sv-veh-badges">
                                                    <span class="badge {{ $tieneGrua ? 'badge-warning' : 'badge-secondary' }}" style="padding:.45rem .65rem; font-size:.85rem;">
                                                        <i class="fa-solid fa-truck-pickup"></i> GRÚA: {{ $tieneGrua ? 'SÍ' : 'NO' }}
                                                    </span>

                                                    @if($estaResguardado)
                                                        <span class="badge badge-danger" style="padding:.45rem .65rem; font-size:.85rem;">
                                                            <i class="fa-solid fa-warehouse"></i> RESGUARDADO
                                                        </span>
                                                    @else
                                                        <span class="badge badge-success" style="padding:.45rem .65rem; font-size:.85rem;">
                                                            <i class="fa-solid fa-warehouse"></i> NO RESGUARDADO
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="sv-veh-meta">
                                                    <div class="sv-veh-meta-row">
                                                        <span class="sv-veh-meta-k">Corralón</span>
                                                        <span class="sv-veh-meta-v">
                                                            @if($estaResguardado)
                                                                {{ $corralonNombre }}
                                                            @else
                                                                Sin corralón
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>

                                                @if ($estaResguardado)
                                                    <a href="{{ route('liberacion.publica', $vehiculo->id) }}"
                                                       class="btn btn-outline-primary btn-block mt-2">
                                                        <i class="fa-solid fa-file-lines"></i> Ver Liberación
                                                    </a>
                                                @else
                                                    <div class="sv-hint mt-2">No está en corralón</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="sv-empty">
                                No hay vehículos asociados a este hecho.
                            </div>
                        @endif
                    </div>

                </div>
            </div>

            <div class="text-center mt-3">
                <a href="{{ route('hechos.index') }}" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

    </div>
</div>
@stop

@section('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<style>
    .sv-shell { border-radius: 18px; padding: 0; }

    .sv-card {
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.12);
        background: radial-gradient(1200px 600px at 20% 0%, rgba(59,130,246,.20), transparent 60%),
                    radial-gradient(900px 500px at 90% 10%, rgba(168,85,247,.18), transparent 65%),
                    rgba(17,24,39,.65);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow: 0 18px 40px rgba(0,0,0,.30);
    }

    .sv-card-header {
        border-bottom: 1px solid rgba(255,255,255,.10);
        background: rgba(0,0,0,.18);
    }

    .sv-meta {
        color: rgba(229,231,235,.85);
        font-weight: 600;
        font-size: .95rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: .25rem .5rem;
        border-radius: 10px;
        background: rgba(255,255,255,.06);
        border: 1px solid rgba(255,255,255,.08);
    }

    .sv-divider { height: 1px; background: rgba(255,255,255,.10); margin: 18px 0; }

    .sv-kv {
        border-radius: 14px;
        padding: 10px 12px;
        background: rgba(255,255,255,.06);
        border: 1px solid rgba(255,255,255,.08);
        min-height: 72px;
    }

    .sv-k {
        color: rgba(229,231,235,.85);
        font-weight: 700;
        font-size: .88rem;
        margin-bottom: 6px;
        letter-spacing: .2px;
    }

    .sv-v {
        color: #f3f4f6;
        font-weight: 600;
        font-size: .98rem;
        line-height: 1.2;
        word-break: break-word;
    }

    .sv-green { color: #22c55e !important; font-weight: 900 !important; }

    .sv-subcard {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.10);
        background: rgba(0,0,0,.16);
    }

    .sv-subcard-header {
        padding: 12px 14px;
        border-bottom: 1px solid rgba(255,255,255,.08);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
        color: rgba(243,244,246,.95);
    }

    .sv-subcard-body { padding: 14px; color: rgba(243,244,246,.95); }

    .sv-map {
        width: 100%;
        height: 320px;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.12);
        background: #0b1220;
    }

    .sv-empty {
        padding: 14px;
        border-radius: 14px;
        background: rgba(255,255,255,.06);
        border: 1px dashed rgba(255,255,255,.16);
        color: rgba(243,244,246,.90);
        font-weight: 600;
    }

    .sv-hint { color: rgba(229,231,235,.75); font-weight: 600; font-size: .95rem; }

    .sv-veh-card {
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.10);
        background: rgba(0,0,0,.18);
        box-shadow: 0 12px 28px rgba(0,0,0,.25);
    }

    .sv-veh-head {
        padding: 12px 12px;
        display: flex;
        align-items: start;
        justify-content: space-between;
        gap: 10px;
        border-bottom: 1px solid rgba(255,255,255,.08);
        background: rgba(255,255,255,.04);
    }

    .sv-veh-title { min-width: 0; }

    .sv-veh-name {
        color: rgba(243,244,246,.95);
        font-weight: 800;
        font-size: 1.0rem;
        line-height: 1.15;
        margin-bottom: 8px;
    }

    .sv-veh-body { padding: 12px; text-align: center; }

    .sv-veh-img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 14px;
        border: 1px solid rgba(255,255,255,.10);
        margin-bottom: 10px;
    }

    .sv-veh-noimg {
        height: 150px;
        border-radius: 14px;
        border: 1px dashed rgba(255,255,255,.18);
        background: rgba(255,255,255,.05);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 8px;
        color: rgba(229,231,235,.75);
        margin-bottom: 10px;
        font-weight: 700;
    }

    .sv-veh-badges {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 10px;
    }

    .sv-veh-meta {
        border-radius: 14px;
        padding: 10px 12px;
        background: rgba(255,255,255,.06);
        border: 1px solid rgba(255,255,255,.08);
        text-align: left;
    }

    .sv-veh-meta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .sv-veh-meta-k { color: rgba(229,231,235,.82); font-weight: 800; font-size: .92rem; }

    .sv-veh-meta-v {
        color: rgba(243,244,246,.95);
        font-weight: 700;
        font-size: .92rem;
        text-align: right;
        word-break: break-word;
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
