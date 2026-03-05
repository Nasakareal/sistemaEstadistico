@extends('adminlte::page')

@section('title', 'Detalle de Delegación')

@section('content_header')
    <h1>Detalle de Delegación</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ $delegacion->nombre }}
                        @if (!empty($delegacion->clave))
                            <span class="text-muted">({{ $delegacion->clave }})</span>
                        @endif
                    </h3>

                    <div class="card-tools">
                        <a href="{{ route('delegaciones.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>

                        @can('editar delegaciones')
                            <a href="{{ route('delegaciones.edit', $delegacion) }}" class="btn btn-success btn-sm">
                                <i class="fa-regular fa-pen-to-square"></i> Editar
                            </a>
                        @endcan

                        @can('eliminar delegaciones')
                            <form action="{{ route('delegaciones.destroy', $delegacion) }}"
                                  method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-danger btn-sm delete-btn">
                                    <i class="fa-regular fa-trash-can"></i> Eliminar
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>

                <div class="card-body">

                    {{-- INFO PRINCIPAL --}}
                    <div class="row">

                        <div class="col-md-3">
                            <div class="small text-muted">Clave</div>
                            <div class="font-weight-bold">
                                {{ $delegacion->clave ?: '—' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Nombre</div>
                            <div class="font-weight-bold">
                                {{ $delegacion->nombre }}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Municipio</div>
                            <div class="font-weight-bold">
                                {{ $delegacion->municipio ?: '—' }}
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="small text-muted">Estado</div>
                            <div class="font-weight-bold">
                                @if ($delegacion->activa)
                                    <span class="badge badge-success">ACTIVA</span>
                                @else
                                    <span class="badge badge-secondary">INACTIVA</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6 mt-3">
                            <div class="small text-muted">Delegación padre</div>
                            <div class="font-weight-bold">
                                {{ $delegacion->padre ? ($delegacion->padre->nombre_con_clave ?? $delegacion->padre->nombre) : '—' }}
                            </div>
                        </div>

                        <div class="col-md-6 mt-3">
                            <div class="small text-muted">Fecha de creación</div>
                            <div class="font-weight-bold">
                                {{ $delegacion->created_at ? $delegacion->created_at->format('d-m-Y H:i') : '—' }}
                            </div>
                        </div>

                    </div>

                    <hr>

                    {{-- HIJAS --}}
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="mb-0">Delegaciones hijas</h5>

                        @can('editar delegaciones')
                            <a href="{{ route('delegaciones.edit', $delegacion) }}" class="btn btn-outline-primary btn-sm">
                                <i class="fa-regular fa-pen-to-square"></i> Gestionar hijas
                            </a>
                        @endcan
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover table-sm" id="hijas">
                            <thead>
                                <tr>
                                    <th><center>Número</center></th>
                                    <th><center>Clave</center></th>
                                    <th><center>Nombre</center></th>
                                    <th><center>Municipio</center></th>
                                    <th><center>Estado</center></th>
                                    <th><center>Acciones</center></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($delegacion->hijas as $i => $hija)
                                    <tr>
                                        <td style="text-align:center">{{ $i + 1 }}</td>
                                        <td>{{ $hija->clave }}</td>
                                        <td>{{ $hija->nombre }}</td>
                                        <td>{{ $hija->municipio }}</td>
                                        <td style="text-align:center">
                                            @if ($hija->activa)
                                                <span class="badge badge-success">ACTIVA</span>
                                            @else
                                                <span class="badge badge-secondary">INACTIVA</span>
                                            @endif
                                        </td>
                                        <td style="text-align:center">
                                            <div class="btn-group" role="group">
                                                @can('ver delegaciones')
                                                    <a href="{{ route('delegaciones.show', $hija) }}" class="btn btn-info btn-sm">
                                                        <i class="fa-regular fa-eye"></i>
                                                    </a>
                                                @endcan

                                                @can('editar delegaciones')
                                                    <a href="{{ route('delegaciones.edit', $hija) }}" class="btn btn-success btn-sm">
                                                        <i class="fa-regular fa-pen-to-square"></i>
                                                    </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No hay delegaciones hijas registradas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>

        </div>
    </div>
@stop

@section('css')
    <style>
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            $('#hijas').DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Hijas",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Hijas",
                    "infoFiltered": "(Filtrado de _MAX_ total Hijas)",
                    "lengthMenu": "Mostrar _MENU_ Hijas",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscador:",
                    "zeroRecords": "Sin resultados encontrados",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
            });
        });

        @if (session('success'))
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 15000
            });
        @endif

        $(document).on('click', '.delete-btn', function (e) {
            e.preventDefault();
            let form = $(this).closest('form');

            Swal.fire({
                title: '¿Estás seguro de eliminar esta delegación?',
                text: "¡No podrás revertir esta acción!",
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
