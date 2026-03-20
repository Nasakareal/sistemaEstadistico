@extends('adminlte::page')

@section('title', 'Resumen Ejecutivo')

@section('content_header')
    <h1>Resumen Ejecutivo Diario</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Listado de días disponibles</h3>
            </div>

            <div class="card-body">

                @if ($fechas->count() === 0)
                    <div class="alert alert-info">
                        No hay datos disponibles.
                    </div>
                @endif

                <table id="fechas" class="table table-striped table-bordered table-hover table-sm">
                    <thead>
                        <tr>
                            <th><center>#</center></th>
                            <th><center>Fecha</center></th>
                            <th><center>Día</center></th>
                            <th><center>Acciones</center></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($fechas as $index => $fecha)
                            @php
                                $carbon = \Carbon\Carbon::parse($fecha)->locale('es');
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>

                                <td>
                                    {{ $carbon->format('Y-m-d') }}
                                </td>

                                <td>
                                    {{ ucfirst($carbon->translatedFormat('l d \d\e F')) }}
                                </td>

                                <td>
                                    <a href="{{ route('resumen_ejecutivo.show', $fecha) }}"
                                       class="btn btn-info btn-sm"
                                       title="Ver resumen">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>
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

    #fechas.table-hover tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.08) !important;
    }

    #fechas.table-hover tbody tr:hover td,
    #fechas.table-hover tbody tr:hover th {
        color: #ffffff !important;
    }

    #fechas.table-hover tbody tr:hover a {
        color: #ffffff !important;
    }
</style>
@stop

@section('js')
<script>
    $(function () {
        $('#fechas').DataTable({
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
</script>
@stop
