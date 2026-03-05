@extends('adminlte::page')

@section('title', 'Listado de Delegaciones')

@section('content_header')
    <h1>Listado de Delegaciones</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Delegaciones Registradas</h3>

                    <div class="card-tools">
                        @can('crear delegaciones')
                            <a href="{{ route('delegaciones.create') }}" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i> Crear Nueva Delegación
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="card-body">
                    <table id="delegaciones" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>Número</center></th>
                                <th><center>Clave</center></th>
                                <th><center>Nombre</center></th>
                                <th><center>Municipio</center></th>
                                <th><center>Padre</center></th>
                                <th><center>Hijas</center></th>
                                <th><center>Estado</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($delegaciones as $index => $delegacion)
                                <tr>
                                    <td style="text-align:center">{{ $index + 1 }}</td>

                                    <td>{{ $delegacion->clave }}</td>

                                    <td>{{ $delegacion->nombre }}</td>

                                    <td>{{ $delegacion->municipio }}</td>

                                    <td>
                                        {{ $delegacion->padre ? $delegacion->padre->nombre : '—' }}
                                    </td>

                                    <td style="text-align:center">
                                        @if (isset($delegacion->hijas_count))
                                            {{ (int) $delegacion->hijas_count }}
                                        @elseif ($delegacion->relationLoaded('hijas'))
                                            {{ $delegacion->hijas->count() }}
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td style="text-align:center">
                                        @if ($delegacion->activa)
                                            <span class="badge badge-success">ACTIVA</span>
                                        @else
                                            <span class="badge badge-secondary">INACTIVA</span>
                                        @endif
                                    </td>

                                    <td style="text-align:center">
                                        <div class="btn-group" role="group">

                                            @can('ver delegaciones')
                                                <a href="{{ route('delegaciones.show', $delegacion) }}" class="btn btn-info btn-sm">
                                                    <i class="fa-regular fa-eye"></i>
                                                </a>
                                            @endcan

                                            @can('editar delegaciones')
                                                <a href="{{ route('delegaciones.edit', $delegacion) }}" class="btn btn-success btn-sm">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                            @endcan

                                            @can('eliminar delegaciones')
                                                <form action="{{ route('delegaciones.destroy', $delegacion) }}"
                                                      method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-danger btn-sm delete-btn">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            @endcan

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
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
            $('#delegaciones').DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Delegaciones",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Delegaciones",
                    "infoFiltered": "(Filtrado de _MAX_ total Delegaciones)",
                    "lengthMenu": "Mostrar _MENU_ Delegaciones",
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
                "buttons": [
                    {
                        extend: 'collection',
                        text: 'Opciones',
                        buttons: [
                            { extend: 'copy', text: 'Copiar' },
                            { extend: 'pdf', text: 'PDF' },
                            { extend: 'csv', text: 'CSV' },
                            { extend: 'excel', text: 'Excel' },
                            { extend: 'print', text: 'Imprimir' }
                        ]
                    },
                    { extend: 'colvis', text: 'Visor de columnas' }
                ],
            }).buttons().container().appendTo('#delegaciones_wrapper .col-md-6:eq(0)');
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

        @if (session('error'))
            Swal.fire({
                position: 'center',
                icon: 'error',
                title: '{{ session('error') }}',
                showConfirmButton: true
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
