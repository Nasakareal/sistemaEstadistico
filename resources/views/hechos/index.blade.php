@extends('adminlte::page')

@section('title', 'Listado de Hechos')

@section('content_header')
    <h1>Listado de Hechos de Tránsito</h1>
@stop

@section('content')
    @php
        $usuario = auth()->user();
        $puedeFiltrarUnidad = $usuario->hasRole('Superadmin') || (int) ($usuario->unidad_id ?? 0) === 3;
        $puedeVerTarjetaWhatsApp = $usuario->hasRole('Superadmin')
            || $hechos->getCollection()->contains(fn ($hecho) => \App\Support\HechoAccess::effectiveUnidadIdForHecho($hecho) === 2);
        $puedeGenerarIphPuesta = $usuario->hasRole('Superadmin') || (int) ($usuario->unidad_id ?? 0) === 2;
        $origenFiltro = $origenFiltro ?? request('origen', 'todos');
        $sinFecha = (bool) ($sinFecha ?? request()->boolean('sin_fecha'));
        $fechaFiltro = $sinFecha ? '' : ($fechaSeleccionada ?? now('America/Mexico_City')->format('Y-m-d'));
    @endphp

    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Hechos</h3>
                    <div class="card-tools">
                        @can('crear hechos')
                            <a href="{{ url('/hechos/create') }}" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i> Añadir nuevo accidente
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="card-body">

                    <form method="GET" action="{{ route('hechos.index') }}" class="row mb-3" autocomplete="off">
                        <div class="col-md-3">
                            <label for="fecha_filtro">Fecha:</label>
                            <input
                                type="date"
                                id="fecha_filtro"
                                name="fecha"
                                class="form-control"
                                value="{{ $fechaFiltro }}"
                            >
                            <div class="form-check mt-2">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="sin_fecha"
                                    name="sin_fecha"
                                    value="1"
                                    {{ $sinFecha ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="sin_fecha">Todas las fechas</label>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label for="origen_filtro">Origen:</label>
                            <select id="origen_filtro" name="origen" class="form-control">
                                <option value="todos" {{ $origenFiltro === 'todos' ? 'selected' : '' }}>Todos</option>
                                <option value="actuales" {{ $origenFiltro === 'actuales' ? 'selected' : '' }}>Actuales</option>
                                <option value="historicos" {{ $origenFiltro === 'historicos' ? 'selected' : '' }}>Históricos Peritos</option>
                            </select>
                        </div>

                        @if($puedeFiltrarUnidad)
                        <div class="col-md-3">
                            <label>Unidad:</label>
                            <select name="unidad_filtro" class="form-control">
                                <option value="">Todas</option>
                                <option value="1" {{ request('unidad_filtro') == 1 ? 'selected' : '' }}>Siniestros</option>
                                <option value="2" {{ request('unidad_filtro') == 2 ? 'selected' : '' }}>Delegaciones</option>
                                <option value="4" {{ request('unidad_filtro') == 4 ? 'selected' : '' }}>Carreteras</option>
                            </select>
                        </div>
                        @endif

                        <div class="{{ $puedeFiltrarUnidad ? 'col-md-3' : 'col-md-6' }} d-flex align-items-end flex-wrap">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fa-solid fa-filter"></i> Filtrar
                            </button>

                            <a href="{{ route('hechos.index', array_filter(['origen' => 'historicos', 'sin_fecha' => 1, 'unidad_filtro' => $unidadFiltro ?: null])) }}" class="btn btn-outline-info mr-2">
                                <i class="fa-solid fa-clock-rotate-left"></i> Históricos
                            </a>

                            <a href="{{ route('hechos.index') }}" class="btn btn-secondary mt-2 mt-md-0">
                                <i class="fa-solid fa-rotate-left"></i> Hoy
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table id="hechos" class="table table-striped table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center">ID</th>
                                    <th class="text-center">Fecha y Hora</th>
                                    <th class="text-center">Ubicación</th>
                                    <th class="text-center">Foto Lugar</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Captura</th>
                                    <th class="text-center">Corralón</th>
                                    <th class="text-center">Revisado por</th>
                                    <th class="text-center">Creado por</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($hechos as $hecho)
                                    @php
                                        $foto = $hecho->foto_lugar;
                                        $urlFoto = $foto ? asset('storage/' . ltrim($foto, '/')) : null;

                                        $fechaMostrar = !empty($hecho->fecha)
                                            ? \Carbon\Carbon::parse($hecho->fecha)->format('Y-m-d')
                                            : '';

                                        $horaMostrar = !empty($hecho->hora)
                                            ? substr((string) $hecho->hora, 0, 5)
                                            : '';

                                        $unidadReal = \App\Support\HechoAccess::effectiveUnidadIdForHecho($hecho);
                                        $mostrarCaptura = $unidadReal === 2;
                                        $puedeVerTarjetaWhatsAppHecho = $usuario->hasRole('Superadmin') || $unidadReal === 2;
                                        $esIncompletoDelegaciones = $mostrarCaptura && !$hecho->captura_completa;

                                        $esUnidadSeguridadVial = (int) ($usuario->unidad_id ?? 0) === 3;
                                        $soloLecturaSeguridadVial = $esUnidadSeguridadVial;

                                        $puedeMarcarRelevante = !$soloLecturaSeguridadVial
                                            && ($usuario->hasRole('Superadmin') || $usuario->hasRole('Administrador') || $usuario->hasRole('Subdirector'));

                                        $puedeEditarHecho = !$soloLecturaSeguridadVial
                                            && !empty($hecho->puede_editar)
                                            && $hecho->puede_editar;

                                        $puedeEliminarHecho = !$soloLecturaSeguridadVial
                                            && ($usuario->hasRole('Superadmin') || $usuario->hasRole('Administrador'));

                                        $esHistoricoPeritos = (string) $hecho->fuente_ubicacion === 'legacy_peritos';
                                    @endphp

                                    <tr class="{{ $esIncompletoDelegaciones ? 'table-danger' : '' }}">
                                        <td>{{ $hecho->id }}</td>

                                        <td>{{ trim($fechaMostrar . ' ' . $horaMostrar) }}</td>

                                        <td>{{ $hecho->calle }}, {{ $hecho->colonia }}, {{ $hecho->municipio }}</td>

                                        <td>
                                            @if ($urlFoto)
                                                <a href="{{ $urlFoto }}" target="_blank" rel="noopener">
                                                    <img src="{{ $urlFoto }}" alt="foto_lugar" class="foto-thumb">
                                                </a>
                                            @else
                                                <span class="text-muted">Sin foto</span>
                                            @endif
                                        </td>

                                        <td>{{ $hecho->situacion }}</td>

                                        <td>
                                            @if ($mostrarCaptura)
                                                @if ($hecho->captura_completa)
                                                    <span class="badge badge-success">COMPLETO</span>
                                                @else
                                                    <span class="badge badge-danger">INCOMPLETO</span>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>

                                        <td>
                                            @php
                                                $vehiculosCorralon = (int) ($hecho->vehiculos_corralon_count ?? 0);
                                            @endphp

                                            <span class="badge {{ $vehiculosCorralon > 0 ? 'badge-info' : 'badge-secondary' }} corralon-count">
                                                <i class="fa-solid fa-warehouse"></i>
                                                {{ $vehiculosCorralon }}
                                                {{ $vehiculosCorralon === 1 ? 'vehículo' : 'vehículos' }}
                                            </span>
                                        </td>

                                        <td>{{ $hecho->revisadoPor ? $hecho->revisadoPor->name : 'SIN REVISIÓN' }}</td>

                                        <td>{{ $hecho->creator ? $hecho->creator->name : 'Desconocido' }}</td>

                                        <td class="text-center">
                                            @if($puedeMarcarRelevante)
                                                @if($hecho->es_relevante)
                                                    <form action="{{ route('hechos.desmarcarRelevante', $hecho->id) }}" method="POST" style="display:inline-block;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                            <i class="fa-solid fa-star-half-stroke"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('hechos.marcarRelevante', $hecho->id) }}" method="POST" style="display:inline-block;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-warning btn-sm">
                                                            <i class="fa-solid fa-star"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif

                                            <a href="{{ route('hechos.show', $hecho->id) }}" class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>

                                            @if($puedeEditarHecho)
                                                <a href="{{ route('hechos.edit', $hecho->id) }}" class="btn btn-success btn-sm">
                                                    <i class="fa-solid fa-pencil"></i>
                                                </a>
                                            @endif

                                            <a href="{{ route('croquis.show', $hecho->id) }}" class="btn btn-primary btn-sm">
                                                <i class="fa-solid fa-draw-polygon"></i>
                                            </a>

                                            <a href="{{ route('hechos.descargar', $hecho->id) }}" class="btn btn-warning btn-sm">
                                                <i class="fas fa-download"></i>
                                            </a>

                                            <button type="button" class="btn btn-success btn-sm btn-compartir-hecho"
                                                data-url="{{ route('hechos.compartir', $hecho->id) }}">
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </button>

                                            @if($puedeVerTarjetaWhatsAppHecho)
                                                <button type="button"
                                                    class="btn btn-outline-info btn-sm btn-preview-whatsapp-card"
                                                    title="Ver tarjeta WhatsApp"
                                                    data-url="{{ route('hechos.compartir', $hecho->id) }}">
                                                    <i class="fa-regular fa-rectangle-list"></i>
                                                </button>
                                            @endif

                                            @if($puedeGenerarIphPuesta)
                                                <a href="{{ route('hechos.iph_puesta_disposicion.descargar', $hecho->id) }}"
                                                   class="btn btn-outline-dark btn-sm"
                                                   title="Descargar IPH de puesta a disposición en Word">
                                                    <i class="fa-solid fa-file-word"></i>
                                                </a>
                                            @endif

                                            @if($puedeEliminarHecho)
                                                <form action="{{ route('hechos.destroy', $hecho->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{ $hechos->links() }}
                </div>
            </div>
        </div>
    </div>

    @if($puedeVerTarjetaWhatsApp)
        @include('hechos.partials.whatsapp_preview_modal')
    @endif
@stop

@section('css')
    @if($puedeVerTarjetaWhatsApp)
        @include('hechos.partials.whatsapp_preview_styles')
    @endif

    <style>
        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
        }

        #hechos.table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.08) !important;
        }

        #hechos.table-hover tbody tr:hover td,
        #hechos.table-hover tbody tr:hover th {
            color: #ffffff !important;
        }

        #hechos.table-hover tbody tr:hover a,
        #hechos.table-hover tbody tr:hover button {
            color: #ffffff !important;
        }

        input[type="date"].form-control {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.06) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }

        input[type="date"].form-control:focus {
            background-color: rgba(255, 255, 255, 0.10) !important;
            border-color: rgba(255, 255, 255, 0.30) !important;
            box-shadow: 0 0 0 .2rem rgba(255, 255, 255, 0.10) !important;
        }

        input[type="date"].form-control::-webkit-calendar-picker-indicator {
            filter: invert(1);
            opacity: 0.9;
            cursor: pointer;
        }

        .foto-thumb {
            width: 72px;
            height: 52px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid rgba(0,0,0,.12);
            background: #f8f9fa;
        }

        .corralon-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .25rem;
            min-width: 86px;
            padding: .32rem .48rem;
            border-radius: 6px;
            font-size: .78rem;
            line-height: 1.15;
            white-space: nowrap;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            if ($.fn.DataTable.isDataTable('#hechos')) {
                $('#hechos').DataTable().destroy();
            }

            $('#hechos').DataTable({
                paging: false,
                info: false,
                order: [[0, 'desc']],
                language: {
                    emptyTable: "No hay información disponible",
                    loadingRecords: "Cargando...",
                    processing: "Procesando...",
                    search: "Buscar:",
                    zeroRecords: "No se encontraron resultados"
                },
                responsive: true,
                lengthChange: false,
                autoWidth: false
            });

            $(document).on('click', '.btn-compartir-hecho', async function () {
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
                                } else if (mime === 'image/jpeg') {
                                    ext = 'jpg';
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

                    const waUrl = 'https://wa.me/?text=' + encodeURIComponent(texto);
                    window.open(waUrl, '_blank');
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'No se pudo compartir el hecho.'
                    });
                }
            });

            @if (session('success'))
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: '{{ session('success') }}',
                    showConfirmButton: false,
                    timer: 3000
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    position: 'center',
                    icon: 'error',
                    title: '{{ session('error') }}',
                    showConfirmButton: true
                });
            @endif

            @if (session('info'))
                Swal.fire({
                    position: 'center',
                    icon: 'info',
                    title: '{{ session('info') }}',
                    showConfirmButton: false,
                    timer: 2500
                });
            @endif
        });
    </script>

    @if($puedeVerTarjetaWhatsApp)
        @include('hechos.partials.whatsapp_preview_scripts')
    @endif
@stop
