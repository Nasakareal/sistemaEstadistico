@extends('adminlte::page')

@section('title', 'Listado de Grúas')

@section('content_header')
    <h1>Listado de Grúas</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Grúas Registradas</h3>
                    <div class="card-tools">
                        <a href="{{ url('/gruas/create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Registrar Nueva Grúa
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="gruas" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>Número</center></th>
                                <th><center>Nombre</center></th>
                                <th><center>Dirección</center></th>
                                <th><center>Teléfono</center></th>
                                <th><center>Email</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($gruas as $index => $grua)
                                <tr>
                                    <td style="text-align: center">{{ $index + 1 }}</td>
                                    <td>{{ $grua->nombre }}</td>
                                    <td>{{ $grua->direccion }}</td>
                                    <td>{{ $grua->telefono }}</td>
                                    <td>{{ $grua->email }}</td>
                                    <td style="text-align: center">
                                        <div class="btn-group" role="group">
                                            <!-- Botón para ver servicios -->
                                            <a href="{{ route('servicios.index', $grua->id) }}" class="btn btn-warning btn-sm">
                                                <i class="fa-solid fa-wrench"></i>
                                            </a>

                                            <!-- Botón para ver detalles de la grúa -->
                                            <a href="{{ url('/gruas/' . $grua->id) }}" class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>

                                            <!-- Botón para editar la grúa -->
                                            <a href="{{ url('/gruas/' . $grua->id . '/edit') }}" class="btn btn-success btn-sm">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>

                                            <!-- Botón para eliminar la grúa -->
                                            <form action="{{ url('/gruas/' . $grua->id) }}" 
                                                  method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-sm delete-btn">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </form>
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
            $('#gruas').DataTable({
                "pageLength": 5,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Grúas",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Grúas",
                    "infoFiltered": "(Filtrado de _MAX_ total Grúas)",
                    "lengthMenu": "Mostrar _MENU_ Grúas",
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
            }).buttons().container().appendTo('#gruas_wrapper .col-md-6:eq(0)');
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
                title: '¿Estás seguro de eliminar esta grúa?',
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
