@extends('adminlte::page')

@section('title', 'Guardianes del Camino')

@section('content_header')
    <h1>Operativo Único Guardianes del Camino</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Dispositivos registrados</h3>
                    <div class="card-tools">
                            <a href="{{ route('guardianes_camino.dispositivos.create') }}" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i> Añadir dispositivo
                            </a>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" action="{{ route('guardianes_camino.index') }}" id="formFiltroFecha" class="row mb-3" autocomplete="off">
                        <div class="col-md-4">
                            <label for="fecha">Fecha:</label>
                            <input
                                type="date"
                                id="fecha"
                                name="fecha"
                                class="form-control"
                                value="{{ request('fecha', now()->format('Y-m-d')) }}"
                            >
                        </div>

                        <div class="col-md-8 d-flex align-items-end">
                            <a href="{{ route('guardianes_camino.index') }}" class="btn btn-secondary mr-2">
                                <i class="fa-solid fa-rotate-left"></i> Hoy
                            </a>

                            <a href="{{ route('guardianes_camino.resumen', ['fecha' => request('fecha', now()->format('Y-m-d'))]) }}" class="btn btn-warning mr-2">
                                <i class="fa-solid fa-chart-column"></i> Resumen
                            </a>

                            <a href="{{ route('guardianes_camino.whatsapp', ['fecha' => request('fecha', now()->format('Y-m-d'))]) }}" class="btn btn-success">
                                <i class="fa-brands fa-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                    </form>

                    @if ($dispositivos->count() === 0)
                        <div class="alert alert-info">
                            No hay dispositivos registrados para la fecha seleccionada.
                        </div>
                    @endif

                    <table id="guardianes_camino" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>ID</center></th>
                                <th><center>Fecha y Hora</center></th>
                                <th><center>Tipo de dispositivo</center></th>
                                <th><center>Lugar</center></th>
                                <th><center>Destacamento</center></th>
                                <th><center>Capturó</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($dispositivos as $dispositivo)
                                <tr>
                                    <td>{{ $dispositivo->id }}</td>
                                    <td>
                                        {{ optional($dispositivo->fecha)->format('d-m-Y') }}
                                        {{ $dispositivo->hora }}
                                    </td>
                                    <td>{{ $dispositivo->catalogo->nombre ?? 'Sin tipo' }}</td>
                                    <td>{{ $dispositivo->lugar ?? 'Sin lugar' }}</td>
                                    <td>{{ $dispositivo->destacamento->nombre ?? 'Sin destacamento' }}</td>
                                    <td>{{ $dispositivo->usuario->name ?? 'Desconocido' }}</td>
                                    <td style="text-align: center">
                                        <a href="{{ route('guardianes_camino.dispositivos.show', $dispositivo->id) }}" class="btn btn-info btn-sm" title="Ver">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>

                                        @can('editar operativos carreteras')
                                            <a href="{{ route('guardianes_camino.dispositivos.edit', $dispositivo->id) }}" class="btn btn-success btn-sm" title="Editar">
                                                <i class="fa-solid fa-pencil"></i>
                                            </a>
                                        @endcan

                                        @can('eliminar operativos carreteras')
                                            <form action="{{ route('guardianes_camino.dispositivos.destroy', $dispositivo->id) }}" method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="button"
                                                    class="btn btn-danger btn-sm delete-btn"
                                                    data-name="{{ $dispositivo->catalogo->nombre ?? 'Dispositivo' }}"
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
                        {{ $dispositivos->appends(request()->query())->links() }}
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

            $('#fecha').on('change', function () {
                $('#formFiltroFecha').submit();
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
                text: 'Se eliminará el dispositivo ' + nombre + '.',
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
