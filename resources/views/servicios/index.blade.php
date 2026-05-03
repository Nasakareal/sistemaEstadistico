@extends('adminlte::page')

@section('title', 'Listado de Servicios')

@section('content_header')
    <h1>Listado de Servicios de la Grúa: {{ $grua->nombre }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Servicios Vinculados</h3>
                </div>
                <div class="card-body">
                    <table id="servicios" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>Número</center></th>
                                <th><center>Tipo de Vehículo</center></th>
                                <th><center>Aseguradora</center></th>
                                <th><center>Descripción</center></th>
                                <th><center>Fecha de Registro</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($servicios as $index => $servicio)
                                <tr>
                                    <td style="text-align: center">{{ $index + 1 }}</td>
                                    <td>{{ ucfirst($servicio->tipo_vehiculo) }}</td>
                                    <td>{{ $servicio->aseguradora ?? 'No especificada' }}</td>
                                    <td>{{ $servicio->descripcion ?? 'Sin descripción' }}</td>
                                    <td>{{ $servicio->created_at->format('d-m-Y') }}</td>
                                    <td style="text-align: center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('servicios.show', [$grua->id, $servicio->id]) }}" class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
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
            $('#servicios').DataTable({
                "pageLength": 10,
                "order": [[4, "desc"]],
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Servicios",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Servicios",
                    "infoFiltered": "(Filtrado de _MAX_ total Servicios)",
                    "lengthMenu": "Mostrar _MENU_ Servicios",
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
            }).buttons().container().appendTo('#servicios_wrapper .col-md-6:eq(0)');
        });

        @if (session('success'))
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 1500
            });
        @endif
    </script>
@stop
