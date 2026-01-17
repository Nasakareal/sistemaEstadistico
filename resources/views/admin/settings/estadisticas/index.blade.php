@extends('adminlte::page')

@section('title', 'Estadísticas del Sistema')

@section('content_header')
    <h1>Estadísticas Generales</h1>
@stop

@section('content')
    {{-- Tabla de estadísticas por día --}}
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Estadísticas por Día</h3>
                </div>
                <div class="card-body">
                    <table id="tabla-estadisticas" class="table table-bordered table-hover table-sm w-100">
                        <thead>
                            <tr>
                                <th>Estadística</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hechosPorFecha ?? [] as $registro)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($registro->fecha)->format('d/m/Y') }}</td>
                                    <td>{{ $registro->total }}</td>
                                </tr>
                            @endforeach

                            <tr>
                                <td><strong>Parte de Novedades</strong></td>
                                <td>
                                    <a href="{{ route('estadisticas.parteNovedades') }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-file-word"></i> Ver Parte de Novedades
                                    </a>
                                </td>
                            </tr>

                            <tr>
                                <td><strong>Bitácora</strong></td>
                                <td>
                                    <a href="{{ route('estadisticas.bitacora') }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-clipboard-list"></i> Ver Bitácora
                                    </a>
                                </td>
                            </tr>


                            <tr>
                                <td><strong>Mini Parte</strong></td>
                                <td>
                                    <a href="{{ route('estadisticas.miniParte') }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-file-word"></i> Ver Mini Parte
                                    </a>
                                </td>
                            </tr>

                            {{-- NUEVA ESTADÍSTICA: DICTAMEN --}}
                            <tr>
                                <td><strong>Dictamen (Buscador)</strong></td>
                                <td>
                                    <a href="{{ route('estadisticas.dictamen') }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-search"></i> Buscar por Placa / ID
                                    </a>
                                </td>
                            </tr>

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
            $('#tabla-estadisticas').DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay datos",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "No se encontraron resultados",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false
            });
        });

        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000
            });
        @endif
    </script>
@stop
