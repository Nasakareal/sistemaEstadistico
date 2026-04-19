@extends('adminlte::page')

@section('title', 'Listado de Personal')

@section('content_header')
    <h1>Listado de Personal</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Personal Registrado</h3>
                    <div class="card-tools">
                        @can('crear personal')
                            <a href="{{ url('/admin/settings/personal/create') }}" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i> Crear Nuevo Personal
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <table id="personal" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>No.</center></th>
                                <th><center>Número empleado</center></th>
                                <th><center>Nombre</center></th>
                                <th><center>Unidad</center></th>
                                <th><center>Turno</center></th>
                                <th><center>CUIP</center></th>
                                <th><center>CURP</center></th>
                                <th><center>Grado</center></th>
                                <th><center>Estatus</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($personals as $index => $personal)
                                <tr>
                                    <td style="text-align: center">{{ $index + 1 }}</td>
                                    <td>{{ $personal->numero_empleado ?? 'N/A' }}</td>
                                    <td>
                                        {{ trim(($personal->nombre ?? '') . ' ' . ($personal->ap_paterno ?? '') . ' ' . ($personal->ap_materno ?? '')) }}
                                    </td>
                                    <td>{{ $personal->unidad->nombre ?? 'N/A' }}</td>
                                    <td>{{ $personal->turno->nombre ?? 'N/A' }}</td>
                                    <td>{{ $personal->cuip ?? 'N/A' }}</td>
                                    <td>{{ $personal->curp ?? 'N/A' }}</td>
                                    <td>{{ $personal->grado ?? 'N/A' }}</td>
                                    <td>{{ $personal->estatus ?? 'N/A' }}</td>
                                    <td style="text-align: center">
                                        <div class="btn-group" role="group">
                                            @can('ver personal')
                                                <a href="{{ url('/admin/settings/personal/' . $personal->id) }}" class="btn btn-info btn-sm">
                                                    <i class="fa-regular fa-eye"></i>
                                                </a>
                                            @endcan

                                            @can('editar personal')
                                                <a href="{{ url('/admin/settings/personal/' . $personal->id . '/edit') }}" class="btn btn-success btn-sm">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                            @endcan

                                            @can('borrar personal')
                                                <form action="{{ url('/admin/settings/personal/' . $personal->id) }}"
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
            $('#personal').DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ elementos",
                    "infoEmpty": "Mostrando 0 a 0 de 0 elementos",
                    "infoFiltered": "(Filtrado de _MAX_ total elementos)",
                    "lengthMenu": "Mostrar _MENU_ elementos",
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
            }).buttons().container().appendTo('#personal_wrapper .col-md-6:eq(0)');
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
                title: '¿Estás seguro de eliminar este elemento?',
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
