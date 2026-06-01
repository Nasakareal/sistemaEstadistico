@extends('adminlte::page')

@section('title', 'Detalles del Hecho')

@php
    $usuario = auth()->user();
    $unidadRealHecho = \App\Support\HechoAccess::effectiveUnidadIdForHecho($hecho);
    $esHechoDelegaciones = $unidadRealHecho === 2;
    $puedeVerTarjetaWhatsApp = $usuario->hasRole('Superadmin') || $esHechoDelegaciones;
    $puedeGenerarIphPuesta = $usuario->hasRole('Superadmin') || (int) ($usuario->unidad_id ?? 0) === 2;
    $puestaHecho = $hecho->puestaDisposicion ?? null;
    $puestaPdfPath = $puestaHecho ? $puestaHecho->archivo_puesta : null;
    $puestaPdfUrl = $puestaPdfPath && $puestaHecho ? route('puestas_disposicion.archivo', $puestaHecho->id) : null;
    $urlAnterior = url()->previous();
    $volverHechoUrl = $urlAnterior && $urlAnterior !== url()->current()
        ? $urlAnterior
        : route('hechos.index');
@endphp

@section('content_header')
    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
        <div class="d-flex align-items-center" style="gap:10px;">
            <h1 class="mb-0">Detalles del Hecho</h1>
            <span class="badge badge-light" style="font-size:.9rem; padding:.4rem .6rem;">
                Folio: {{ $hecho->folio_c5i ?? 'No especificado' }}
            </span>
        </div>

        <div class="d-flex align-items-center" style="gap:8px; flex-wrap:wrap;">
            @if($hecho->dictamen)
                <a href="{{ route('dictamenes.show', $hecho->dictamen->id) }}"
                   class="btn btn-primary btn-sm rounded-circle d-inline-flex align-items-center justify-content-center"
                   style="width:36px;height:36px;padding:0;"
                   title="Ver dictamen">
                    <i class="fa-solid fa-file-lines"></i>
                </a>
            @endif

            @if(!empty($puedeEditar) && $puedeEditar)
                <a href="{{ route('hechos.edit', $hecho->id) }}"
                   class="btn btn-success btn-sm rounded-circle d-inline-flex align-items-center justify-content-center"
                   style="width:36px;height:36px;padding:0;"
                   title="Editar hecho">
                    <i class="fa-solid fa-pen-to-square"></i>
                </a>
            @endif

            @if(!empty($puedeEditar) && $puedeEditar)
                <a href="{{ route('croquis.show', $hecho->id) }}"
                   class="btn btn-primary btn-sm rounded-circle d-inline-flex align-items-center justify-content-center"
                   style="width:36px;height:36px;padding:0;"
                   title="{{ $hecho->croquis ? 'Editar croquis' : 'Crear croquis' }}">
                    <i class="fa-solid fa-draw-polygon"></i>
                </a>
            @endif

            <a href="{{ route('hechos.descargar', $hecho->id) }}"
               class="btn btn-warning btn-sm rounded-circle d-inline-flex align-items-center justify-content-center"
               style="width:36px;height:36px;padding:0;"
               title="Descargar informe">
                <i class="fas fa-download"></i>
            </a>

            @if($puestaPdfUrl)
                <a href="{{ $puestaPdfUrl }}"
                   class="btn btn-outline-danger btn-sm rounded-circle d-inline-flex align-items-center justify-content-center"
                   style="width:36px;height:36px;padding:0;"
                   target="_blank"
                   rel="noopener"
                   title="Ver PDF de puesta a disposición">
                    <i class="fa-solid fa-file-pdf"></i>
                </a>
            @endif

            <button type="button"
                    class="btn btn-success btn-sm rounded-circle d-inline-flex align-items-center justify-content-center btn-compartir-hecho"
                    style="width:36px;height:36px;padding:0;"
                    title="Compartir por WhatsApp"
                    data-url="{{ route('hechos.compartir', $hecho->id) }}">
                <i class="fa-brands fa-whatsapp"></i>
            </button>

            @if($puedeVerTarjetaWhatsApp)
                <button type="button"
                        class="btn btn-outline-info btn-sm rounded-circle d-inline-flex align-items-center justify-content-center btn-preview-whatsapp-card"
                        style="width:36px;height:36px;padding:0;"
                        title="Ver tarjeta WhatsApp"
                        data-url="{{ route('hechos.compartir', $hecho->id) }}">
                    <i class="fa-regular fa-rectangle-list"></i>
                </button>
            @endif

            @if($puedeGenerarIphPuesta)
                <a href="{{ route('hechos.iph_puesta_disposicion.descargar', $hecho->id) }}"
                   class="btn btn-outline-primary btn-sm rounded-circle d-inline-flex align-items-center justify-content-center"
                   style="width:36px;height:36px;padding:0;"
                   title="Descargar IPH de puesta a disposición en Word">
                    <i class="fa-solid fa-file-word"></i>
                </a>
            @endif

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
    $iphDelegacionesPath = $hecho->iph_delegaciones_path;
    $mostrarAccionesTurnado = $esHechoDelegaciones && $soloTurnado && ($iphDelegacionesPath || $puestaHecho);
    $puedeCrearPuestaTurnado = $soloTurnado
        && $esHechoDelegaciones
        && !$puestaHecho
        && $usuario
        && $usuario->can('crear puestas a disposicion');
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

                        @if($mostrarAccionesTurnado)
                            <div class="sv-status-actions">
                                @if($iphDelegacionesPath)
                                    <a href="{{ asset('storage/' . ltrim($iphDelegacionesPath, '/')) }}"
                                       class="btn btn-outline-light btn-sm sv-status-action"
                                       target="_blank"
                                       rel="noopener"
                                       download
                                       title="Descargar IPH">
                                        <i class="fa-solid fa-file-pdf"></i>
                                        <span>Descargar IPH</span>
                                    </a>
                                @endif

                                @if($puestaPdfUrl)
                                    <a href="{{ $puestaPdfUrl }}"
                                       class="btn btn-outline-danger btn-sm sv-status-action"
                                       target="_blank"
                                       rel="noopener"
                                       title="Ver PDF de puesta a disposición">
                                        <i class="fa-solid fa-file-pdf"></i>
                                        <span>Ver PDF puesta</span>
                                    </a>
                                @endif

                                @if($puestaHecho)
                                    <a href="{{ route('puestas_disposicion.show', $puestaHecho->id) }}"
                                       class="btn btn-outline-info btn-sm sv-status-action"
                                       title="Ver puesta a disposición">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span>Ver puesta</span>
                                    </a>
                                @endif
                            </div>
                        @endif

                        @if($puedeCrearPuestaTurnado)
                            <div class="sv-status-actions">
                                <a href="{{ route('puestas_disposicion.create', ['hecho_id' => $hecho->id]) }}"
                                   class="btn btn-outline-info btn-sm sv-status-action"
                                   title="Crear puesta vinculada">
                                    <i class="fa-solid fa-plus"></i>
                                    <span>Crear puesta</span>
                                </a>
                            </div>
                        @endif
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

                                    @if($field === 'situacion' && ($mostrarAccionesTurnado || $puedeCrearPuestaTurnado))
                                        <div class="sv-status-actions sv-status-actions-card">
                                            @if($iphDelegacionesPath)
                                                <a href="{{ asset('storage/' . ltrim($iphDelegacionesPath, '/')) }}"
                                                   class="btn btn-outline-light btn-sm sv-status-action"
                                                   target="_blank"
                                                   rel="noopener"
                                                   download
                                                   title="Descargar IPH">
                                                    <i class="fa-solid fa-file-pdf"></i>
                                                    <span>Descargar IPH</span>
                                                </a>
                                            @endif

                                            @if($puestaPdfUrl)
                                                <a href="{{ $puestaPdfUrl }}"
                                                   class="btn btn-outline-danger btn-sm sv-status-action"
                                                   target="_blank"
                                                   rel="noopener"
                                                   title="Ver PDF de puesta a disposición">
                                                    <i class="fa-solid fa-file-pdf"></i>
                                                    <span>Ver PDF puesta</span>
                                                </a>
                                            @endif

                                            @if($puestaHecho)
                                                <a href="{{ route('puestas_disposicion.show', $puestaHecho->id) }}"
                                                   class="btn btn-outline-info btn-sm sv-status-action"
                                                   title="Ver puesta a disposición">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                                    <span>Ver puesta</span>
                                                </a>
                                            @elseif($puedeCrearPuestaTurnado)
                                                <a href="{{ route('puestas_disposicion.create', ['hecho_id' => $hecho->id]) }}"
                                                   class="btn btn-outline-info btn-sm sv-status-action"
                                                   title="Crear puesta vinculada">
                                                    <i class="fa-solid fa-plus"></i>
                                                    <span>Crear puesta</span>
                                                </a>
                                            @endif
                                        </div>
                                    @endif
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
                                            <a href="{{ asset('storage/' . $hecho->foto_lugar) }}" target="_blank" rel="noopener" class="sv-photo-link">
                                                <div class="sv-photo-box">
                                                    <img src="{{ asset('storage/' . $hecho->foto_lugar) }}"
                                                         class="sv-photo-img"
                                                         alt="Foto del lugar"
                                                         loading="lazy"
                                                         decoding="async">
                                                </div>
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
                                            <a href="{{ asset('storage/' . $hecho->foto_situacion) }}" target="_blank" rel="noopener" class="sv-photo-link">
                                                <div class="sv-photo-box">
                                                    <img src="{{ asset('storage/' . $hecho->foto_situacion) }}"
                                                         class="sv-photo-img"
                                                         alt="Foto de la situación"
                                                         loading="lazy"
                                                         decoding="async">
                                                </div>
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
                                    <div class="sv-subcard-body">
                                        @php
                                            $usuario = auth()->user();

                                            $puedeRevisar = false;

                                            if (
                                                $usuario->hasRole('Superadmin') ||
                                                (
                                                    (int) ($usuario->unidad_id ?? 0) !== 3
                                                    && (
                                                        $usuario->hasRole('Administrador') ||
                                                        $usuario->hasRole('Subdirector')
                                                    )
                                                )
                                            ) {
                                                $puedeRevisar = true;
                                            }

                                            if ((int) ($usuario->unidad_id ?? 0) !== 3 && $usuario->hasRole('Jefe de Grupo')) {
                                                $creador = $hecho->creator;

                                                if ($creador && (int) $creador->turno_id === (int) $usuario->turno_id) {
                                                    $puedeRevisar = true;
                                                }
                                            }
                                        @endphp

                                        <div class="d-flex flex-wrap" style="gap:10px;">
                                            @if($dictamen)
                                                <a href="{{ route('dictamenes.show', $dictamen->id) }}" class="btn btn-primary btn-sm">
                                                    <i class="fa-solid fa-file-lines"></i> Ver dictamen
                                                </a>
                                            @endif

                                            @if(!empty($puedeEditar) && $puedeEditar)
                                                <a href="{{ route('hechos.edit', $hecho->id) }}" class="btn btn-success btn-sm">
                                                    <i class="fa-solid fa-pen-to-square"></i> Editar hecho
                                                </a>
                                            @endif

                                            @if(!empty($puedeEditar) && $puedeEditar)
                                                <a href="{{ route('croquis.show', $hecho->id) }}" class="btn btn-primary btn-sm">
                                                    <i class="fa-solid fa-draw-polygon"></i>
                                                    {{ $hecho->croquis ? 'Editar croquis' : 'Crear croquis' }}
                                                </a>
                                            @endif

                                            <a href="{{ route('hechos.descargar', $hecho->id) }}" class="btn btn-warning btn-sm">
                                                <i class="fas fa-download"></i> Descargar informe
                                            </a>

                                            @if($puestaPdfUrl)
                                                <a href="{{ $puestaPdfUrl }}"
                                                   class="btn btn-outline-danger btn-sm"
                                                   target="_blank"
                                                   rel="noopener">
                                                    <i class="fa-solid fa-file-pdf"></i> Ver PDF puesta
                                                </a>
                                            @endif

                                            <button type="button"
                                                    class="btn btn-success btn-sm btn-compartir-hecho"
                                                    data-url="{{ route('hechos.compartir', $hecho->id) }}">
                                                <i class="fa-brands fa-whatsapp"></i> Compartir WhatsApp
                                            </button>

                                            @if($puedeVerTarjetaWhatsApp)
                                                <button type="button"
                                                        class="btn btn-outline-info btn-sm btn-preview-whatsapp-card"
                                                        data-url="{{ route('hechos.compartir', $hecho->id) }}">
                                                    <i class="fa-regular fa-rectangle-list"></i> Ver tarjeta
                                                </button>
                                            @endif

                                            @if($puedeGenerarIphPuesta)
                                                <a href="{{ route('hechos.iph_puesta_disposicion.descargar', $hecho->id) }}"
                                                   class="btn btn-outline-dark btn-sm">
                                                    <i class="fa-solid fa-file-word"></i> Descargar IPH puesta
                                                </a>
                                            @endif

                                            @if($puedeRevisar)
                                                @if($hecho->estado_revision === 'pendiente')
                                                    <form action="{{ route('hechos.revision.aprobar', $hecho->id) }}" method="POST" style="display:inline-block;">
                                                        @csrf
                                                        <button class="btn btn-primary btn-sm">
                                                            <i class="fa-solid fa-check"></i> Aprobar
                                                        </button>
                                                    </form>

                                                    <form action="{{ route('hechos.revision.rechazar', $hecho->id) }}" method="POST" style="display:inline-block;">
                                                        @csrf
                                                        <button class="btn btn-danger btn-sm">
                                                            <i class="fa-solid fa-xmark"></i> Rechazar
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="badge badge-success">
                                                        <i class="fa-solid fa-check"></i> YA REVISADO
                                                    </span>
                                                @endif
                                            @endif

                                            <a href="{{ $volverHechoUrl }}" class="btn btn-secondary btn-sm">
                                                <i class="fa-solid fa-arrow-left"></i> Volver
                                            </a>
                                        </div>

                                        @if(!$dictamen)
                                            <div class="sv-hint mt-2">Este hecho no tiene dictamen</div>
                                        @endif
                                    </div>

                                    @if(!$dictamen)
                                        <div class="sv-hint mt-2">Este hecho no tiene dictamen</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-md-6 col-lg-3 mb-3">
                            <div class="sv-kv">
                                <div class="sv-k">KM recorridos</div>
                                <div class="sv-v sv-green">
                                    @if(!is_null($hecho->km_recorridos))
                                        {{ number_format($hecho->km_recorridos, 2) }} km
                                    @else
                                        No especificado
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

                                        $corralonNombre = $vehiculo->nombreCorralonValido();
                                        $estaResguardado = $corralonNombre !== null;

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



                        <div class="sv-divider"></div>

                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                            <h3 class="mb-0">Lesionados</h3>
                            <span class="badge badge-light" style="font-size:.9rem; padding:.35rem .6rem;">
                                Total: {{ $hecho->lesionados->count() ?? 0 }}
                            </span>
                        </div>

                        <div class="mt-3">
                            @if($hecho->lesionados && $hecho->lesionados->count())
                                <div class="row">
                                    @foreach($hecho->lesionados as $lesionado)
                                        <div class="col-md-6 col-xl-4 mb-3">
                                            <div class="sv-veh-card h-100">
                                                <div class="p-3">
                                                    <strong>{{ $lesionado->nombre ?? 'Sin nombre' }}</strong>
                                                    <div>{{ $lesionado->edad ?? 'Sin edad' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="sv-empty">
                                    No hay lesionados registrados.
                                </div>
                            @endif
                        </div>

                        <div class="sv-divider"></div>

                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                            <h3 class="mb-0">Croquis</h3>
                        </div>

                        <div class="mt-3">
                        @if(!empty($croquisData))
                            <div class="sv-croquis-box">
                                <canvas id="croquisShowCanvas" width="1200" height="700"></canvas>
                            </div>
                        @else
                            <div class="sv-empty">
                                No hay croquis registrado.
                            </div>
                        @endif
                    </div>
                    </div>

                </div>
            </div>

            <div class="text-center mt-3">
                <a href="{{ $volverHechoUrl }}" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

    </div>
</div>

@if($puedeVerTarjetaWhatsApp)
    @include('hechos.partials.whatsapp_preview_modal')
@endif
@stop

@section('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

@if($puedeVerTarjetaWhatsApp)
    @include('hechos.partials.whatsapp_preview_styles')
@endif

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

    .sv-status-actions {
        display: inline-flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
    }

    .sv-status-action {
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 800;
        line-height: 1;
        padding: .28rem .58rem;
        white-space: nowrap;
    }

    .sv-status-actions-card {
        display: flex;
        margin-top: 10px;
    }

    .sv-status-actions-card .sv-status-action {
        justify-content: center;
        min-height: 30px;
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

    .sv-photo-link{ display: inline-block; }

    .sv-photo-box{
        width: 100%;
        max-width: 420px;
        height: 220px;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.10);
        background: rgba(255,255,255,.04);
    }

    .sv-photo-img{
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .sv-croquis-box {
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 14px;
        padding: 10px;
        background: rgba(255,255,255,.04);
        overflow: auto;
    }

    #croquisShowCanvas {
        display: block;
        width: 100%;
        max-width: 1200px;
        height: auto;
        margin: 0 auto;
        background: #ffffff;
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 10px;
    }

</style>
@stop

@section('js')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script src="{{ asset('js/croquis/croquis-models.js') }}"></script>
    <script src="{{ asset('js/croquis/croquis-renderer.js') }}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const croquisData = @json($croquisData);

        if (croquisData && croquisData.length) {
            const canvas = document.getElementById('croquisShowCanvas');

            if (canvas) {
                const ctx = canvas.getContext('2d');

                const elementos = window.CroquisModels.deserialize(croquisData);

                const assets = {
                    vehiculos: {},
                    iconos: {}
                };

                window.CroquisRenderer.render(ctx, canvas, elementos, assets);
            }
        }

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

        document.querySelectorAll('.btn-compartir-hecho').forEach(function (button) {
            button.addEventListener('click', async function () {
                const url = this.dataset.url;

                try {
                    const resp = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!resp.ok) {
                        throw new Error('No se pudo obtener la información para compartir.');
                    }

                    const data = await resp.json();
                    const texto = (data.texto || '').trim();
                    const fotos = Array.isArray(data.fotos) ? data.fotos.filter(Boolean) : [];
                    const archivos = [];

                    if (navigator.share && fotos.length) {
                        for (let i = 0; i < fotos.length; i++) {
                            try {
                                const imgResp = await fetch(fotos[i]);
                                if (!imgResp.ok) {
                                    continue;
                                }

                                const blob = await imgResp.blob();
                                const mime = blob.type || 'image/jpeg';
                                let ext = 'jpg';

                                if (mime === 'image/png') {
                                    ext = 'png';
                                } else if (mime === 'image/webp') {
                                    ext = 'webp';
                                }

                                archivos.push(new File([blob], 'hecho_' + (i + 1) + '.' + ext, { type: mime }));
                            } catch (e) {
                            }
                        }
                    }

                    if (navigator.share) {
                        if (archivos.length && navigator.canShare && navigator.canShare({ files: archivos })) {
                            await navigator.share({
                                text: texto,
                                files: archivos
                            });
                            return;
                        }

                        await navigator.share({
                            text: texto
                        });
                        return;
                    }

                    window.open('https://wa.me/?text=' + encodeURIComponent(texto), '_blank');
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'No se pudo compartir el hecho.'
                    });
                }
            });
        });
    });
</script>

@if($puedeVerTarjetaWhatsApp)
    @include('hechos.partials.whatsapp_preview_scripts')
@endif
@stop
