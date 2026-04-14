@extends('adminlte::page')

@section('title', 'Relación de Armamento')

@section('content_header')
    <h1>Relación de Armamento</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Armamento Asignado al Personal</h3>

                    <div class="card-tools">
                        <a href="{{ route('settings.estadisticas_siniestros.relacion_armamento.descargar') }}" class="btn btn-success">
                            <i class="fa-solid fa-file-excel"></i> Descargar
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <table id="armamento" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>Número</center></th>
                                <th><center>Elemento</center></th>
                                <th><center>Tipo</center></th>
                                <th><center>Clase</center></th>
                                <th><center>Marca</center></th>
                                <th><center>Modelo</center></th>
                                <th><center>Matrícula</center></th>
                                <th><center>Calibre</center></th>
                                <th><center>Cargadores</center></th>
                                <th><center>Cartuchos</center></th>
                            </tr>
                        </thead>
                        <tbody>
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
            $('#armamento').DataTable({
                "ajax": {
                    "url": "{{ route('settings.estadisticas_siniestros.relacion_armamento.data') }}",
                    "type": "GET",
                    "dataSrc": "data"
                },
                "columns": [
                    { "data": "index" },
                    { "data": "elemento" },
                    { "data": "tipo" },
                    { "data": "clase" },
                    { "data": "marca" },
                    { "data": "modelo" },
                    { "data": "matricula" },
                    { "data": "calibre" },
                    { "data": "cargadores" },
                    { "data": "cartuchos" }
                ],
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": "(Filtrado de _MAX_ total registros)",
                    "lengthMenu": "Mostrar _MENU_ registros",
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
                ]
            }).buttons().container().appendTo('#armamento_wrapper .col-md-6:eq(0)');
        });
    </script>
@stop
