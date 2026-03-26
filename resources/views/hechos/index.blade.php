@extends('adminlte::page')

@section('title', 'Listado de Hechos')

@section('content_header')
    <h1>Listado de Hechos de Tránsito</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Hechos</h3>
                    <div class="card-tools">
                        <a href="{{ url('/hechos/create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Añadir nuevo accidente
                        </a>
                    </div>
                </div>

                <div class="card-body">

                    <form method="GET" action="{{ route('hechos.index') }}" class="row mb-3" autocomplete="off">
                        <div class="col-md-4">
                            <label for="fecha_filtro">Filtrar por fecha:</label>
                            <input
                                type="date"
                                id="fecha_filtro"
                                name="fecha"
                                class="form-control"
                                value="{{ $fechaSeleccionada ?? now('America/Mexico_City')->format('Y-m-d') }}"
                            >
                        </div>

                        <div class="col-md-8 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fa-solid fa-filter"></i> Filtrar
                            </button>

                            <a href="{{ route('hechos.index') }}" class="btn btn-secondary">
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
                                    <th class="text-center">Relevante</th>
                                    <th class="text-center">Revisado por</th>
                                    <th class="text-center">Creado por</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($hechos as $hecho)
                                    @php
                                        $foto = $hecho->foto_lugar;
                                        $urlFoto = $foto ? asset('storage/' . ltrim($foto, '/')) : null;

                                        $fechaMostrar = !empty($hecho->fecha)
                                            ? \Carbon\Carbon::parse($hecho->fecha)->format('Y-m-d')
                                            : '';

                                        $horaMostrar = !empty($hecho->hora)
                                            ? substr((string) $hecho->hora, 0, 5)
                                            : '';
                                    @endphp

                                    <tr>
                                        <td>{{ $hecho->id }}</td>

                                        <td>
                                            {{ trim($fechaMostrar . ' ' . $horaMostrar) }}
                                        </td>

                                        <td>
                                            {{ $hecho->calle }}, {{ $hecho->colonia }}, {{ $hecho->municipio }}
                                        </td>

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
                                            @if ($hecho->es_relevante)
                                                <span class="badge badge-warning">SÍ</span>
                                            @else
                                                <span class="badge badge-secondary">NO</span>
                                            @endif
                                        </td>

                                        <td>{{ $hecho->revisadoPor ? $hecho->revisadoPor->name : 'SIN REVISIÓN' }}</td>

                                        <td>{{ $hecho->creator ? $hecho->creator->name : 'Desconocido' }}</td>

                                        <td class="text-center">
                                            @if(auth()->user()->hasRole('Superadmin') || auth()->user()->hasRole('Administrador') || auth()->user()->hasRole('Subdirector'))
                                                @if($hecho->es_relevante)
                                                    <form action="{{ route('hechos.desmarcarRelevante', $hecho->id) }}" method="POST" style="display:inline-block;">
                                                        @csrf
                                                        <button
                                                            type="submit"
                                                            class="btn btn-outline-danger btn-sm"
                                                            onclick="return confirm('¿Quitar este hecho como relevante?');"
                                                            title="Quitar relevante"
                                                        >
                                                            <i class="fa-solid fa-star-half-stroke"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('hechos.marcarRelevante', $hecho->id) }}" method="POST" style="display:inline-block;">
                                                        @csrf
                                                        <button
                                                            type="submit"
                                                            class="btn btn-outline-warning btn-sm"
                                                            onclick="return confirm('¿Marcar este hecho como relevante?');"
                                                            title="Marcar relevante"
                                                        >
                                                            <i class="fa-solid fa-star"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif

                                            <a href="{{ route('hechos.show', $hecho->id) }}" class="btn btn-info btn-sm" title="Ver">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>

                                            <a href="{{ route('hechos.edit', $hecho->id) }}" class="btn btn-success btn-sm" title="Editar">
                                                <i class="fa-solid fa-pencil"></i>
                                            </a>

                                            <a href="{{ route('hechos.descargar', $hecho->id) }}" class="btn btn-warning btn-sm" title="Descargar">
                                                <i class="fas fa-download"></i>
                                            </a>

                                            <a
                                                href="{{ route('hechos.compartir', $hecho->id) }}"
                                                class="btn btn-success btn-sm"
                                                title="Compartir nativo por WhatsApp"
                                            >
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </a>

                                            <form action="{{ route('hechos.destroy', $hecho->id) }}" method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('¿Estás seguro de eliminar este hecho?');"
                                                    title="Eliminar"
                                                >
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">
                                            No hay hechos para la fecha seleccionada.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $hechos->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
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

        #hechos.table-hover tbody tr:hover a {
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
    </style>
@stop

@section('js')
    <script>
        $(function () {
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
    </script>
@stop
