@extends('adminlte::page')

@section('title', 'Listado de Operativos Guardianes del Camino')

@section('content_header')
    <h1>Listado de Operativos Guardianes del Camino</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Operativos</h3>
                    <div class="card-tools">
                        @can('crear operativos carreteras')
                            <a href="{{ route('guardianes_camino.create') }}" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i> Añadir nuevo operativo
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="card-body">

                    <form method="GET" action="{{ route('guardianes_camino.index') }}" class="row mb-3" autocomplete="off">
                        <div class="col-md-3">
                            <label for="fecha_inicio">Fecha inicio:</label>
                            <input
                                type="date"
                                id="fecha_inicio"
                                name="fecha_inicio"
                                class="form-control"
                                value="{{ request('fecha_inicio') }}"
                            >
                        </div>

                        <div class="col-md-3">
                            <label for="fecha_fin">Fecha fin:</label>
                            <input
                                type="date"
                                id="fecha_fin"
                                name="fecha_fin"
                                class="form-control"
                                value="{{ request('fecha_fin') }}"
                            >
                        </div>

                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fa-solid fa-filter"></i> Filtrar
                            </button>

                            <a href="{{ route('guardianes_camino.index') }}" class="btn btn-secondary">
                                <i class="fa-solid fa-rotate-left"></i> Limpiar
                            </a>
                        </div>
                    </form>

                    @if ($operativos->count() === 0)
                        <div class="alert alert-info">
                            No hay operativos registrados para los filtros seleccionados.
                        </div>
                    @endif

                    <table id="guardianes_camino" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>ID</center></th>
                                <th><center>Fecha y Hora</center></th>
                                <th><center>Operativo</center></th>
                                <th><center>Lugar</center></th>
                                <th><center>Delegación</center></th>
                                <th><center>Destacamento</center></th>
                                <th><center>Creado por</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($operativos as $operativo)
                                <tr>
                                    <td>{{ $operativo->id }}</td>
                                    <td>
                                        {{ optional($operativo->fecha)->format('d-m-Y') }}
                                        {{ $operativo->hora }}
                                    </td>
                                    <td>{{ $operativo->catalogo->nombre ?? 'Guardianes del Camino' }}</td>
                                    <td>{{ $operativo->lugar ?? 'Sin lugar' }}</td>
                                    <td>{{ $operativo->delegacion->nombre ?? 'Sin delegación' }}</td>
                                    <td>{{ $operativo->destacamento->nombre ?? 'Sin destacamento' }}</td>
                                    <td>{{ $operativo->creador->name ?? 'Desconocido' }}</td>

                                    <td style="text-align: center">
                                        <a href="{{ route('guardianes_camino.show', $operativo->id) }}" class="btn btn-info btn-sm" title="Ver">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>

                                        @can('editar operativos carreteras')
                                            <a href="{{ route('guardianes_camino.edit', $operativo->id) }}" class="btn btn-success btn-sm" title="Editar">
                                                <i class="fa-solid fa-pencil"></i>
                                            </a>
                                        @endcan

                                        <a href="{{ route('guardianes_camino.resumen', $operativo->id) }}" class="btn btn-warning btn-sm" title="Resumen">
                                            <i class="fa-solid fa-chart-column"></i>
                                        </a>

                                        @can('crear operativos carreteras')
                                            <a href="{{ route('guardianes_camino.dispositivos.create', $operativo->id) }}" class="btn btn-primary btn-sm" title="Agregar dispositivo">
                                                <i class="fa-solid fa-plus"></i>
                                            </a>
                                        @endcan

                                        <a href="{{ route('guardianes_camino.whatsapp', $operativo->id) }}" class="btn btn-success btn-sm" title="WhatsApp">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>

                                        @can('eliminar operativos carreteras')
                                            <form action="{{ route('guardianes_camino.destroy', $operativo->id) }}" method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="button"
                                                    class="btn btn-danger btn-sm delete-btn"
                                                    data-name="{{ $operativo->catalogo->nombre ?? 'Guardianes del Camino' }}"
                                                >
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-3">
                        {{ $operativos->links() }}
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

        #guardianes_camino.table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.08) !important;
        }

        #guardianes_camino.table-hover tbody tr:hover td,
        #guardianes_camino.table-hover tbody tr:hover th {
            color: #ffffff !important;
        }

        #guardianes_camino.table-hover tbody tr:hover a {
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
    </style>
@stop

@section('js')
    <script>
        $(function () {
            $('#guardianes_camino').DataTable({
                paging: false,
                info: false,
                order: [[1, "desc"]],
                language: {
                    emptyTable: "No hay información disponible",
                    loadingRecords: "Cargando...",
                    processing: "Procesando...",
                    search: "Buscar:",
                    zeroRecords: "No se encontraron resultados",
                },
                responsive: true,
                lengthChange: false,
                autoWidth: false,
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

        $(document).on('click', '.delete-btn', function (e) {
            e.preventDefault();

            let form = $(this).closest('form');
            let nombre = $(this).data('name');

            Swal.fire({
                title: '¿Estás seguro?',
                text: 'Se eliminará el operativo ' + nombre + ' y sus datos relacionados.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
@stop
