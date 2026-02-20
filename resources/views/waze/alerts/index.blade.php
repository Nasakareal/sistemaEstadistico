@extends('adminlte::page')

@section('title', 'Alertas Waze')

@section('content_header')
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="mb-0">Alertas Waze</h1>

            <div class="d-flex" style="gap:8px;">

                {{-- BOTÓN NUEVA VISTA RIESGO --}}
                <a href="{{ route('waze.riesgo.index') }}" class="btn btn-warning">
                    <i class="fas fa-exclamation-triangle"></i> Dashboard Riesgo
                </a>

                <form method="GET" action="{{ route('waze.alerts.index') }}" class="d-flex" style="gap:8px;">
                    <select name="solo" class="form-control">
                        <option value="" {{ request('solo')=='' ? 'selected' : '' }}>Todas</option>
                        <option value="accidentes" {{ request('solo')=='accidentes' ? 'selected' : '' }}>Solo accidentes</option>
                    </select>

                    <select name="tipo" class="form-control">
                        <option value="" {{ request('tipo')=='' ? 'selected' : '' }}>Tipo (todos)</option>
                        <option value="ACCIDENT" {{ request('tipo')=='ACCIDENT' ? 'selected' : '' }}>ACCIDENT</option>
                        <option value="HAZARD" {{ request('tipo')=='HAZARD' ? 'selected' : '' }}>HAZARD</option>
                        <option value="JAM" {{ request('tipo')=='JAM' ? 'selected' : '' }}>JAM</option>
                        <option value="ROAD_CLOSED" {{ request('tipo')=='ROAD_CLOSED' ? 'selected' : '' }}>ROAD_CLOSED</option>
                    </select>

                    <button class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </form>

                <form method="POST" action="{{ route('waze.alerts.read_all') }}">
                    @csrf
                    <button class="btn btn-secondary">
                        <i class="fas fa-check-double"></i> Marcar todo como leído
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>
@stop


@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">

            <div class="card-header">
                <h3 class="card-title">Listado de Alertas</h3>
            </div>

            <div class="card-body table-responsive">

                <table id="wazeAlerts" class="table table-striped table-bordered table-hover table-sm">
                    <thead>
                        <tr>
                            <th style="width:80px;">Estado</th>
                            <th>Tipo</th>
                            <th>Subtipo</th>
                            <th>Lugar</th>
                            <th>Fecha</th>
                            <th style="width:180px;">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($alerts as $a)
                            @php
                                $street = $a->street ?? 'Sin calle';
                                $city = $a->city ?? '';
                                $place = trim($street . ($city ? ', ' . $city : ''));

                                $mapsUrl = ($a->lat && $a->lng)
                                    ? 'https://www.google.com/maps/search/?api=1&query=' . $a->lat . ',' . $a->lng
                                    : null;

                                $isNew = ((int)($a->is_read ?? 0) === 0);
                            @endphp

                            <tr>
                                <td>
                                    @if($isNew)
                                        <span class="badge badge-danger">NUEVA</span>
                                    @else
                                        <span class="badge badge-secondary">LEÍDA</span>
                                    @endif
                                </td>

                                <td>{{ e($a->type) }}</td>

                                <td>{{ e($a->subtype) }}</td>

                                <td>{{ e($place) }}</td>

                                <td>
                                    {{ optional($a->published_at)->format('Y-m-d H:i') }}
                                </td>

                                <td>
                                    <div class="btn-group">

                                        @if($mapsUrl)
                                            <a class="btn btn-info btn-sm"
                                               target="_blank"
                                               href="{{ $mapsUrl }}">
                                                <i class="fas fa-map-marker-alt"></i>
                                            </a>
                                        @endif

                                        @if($isNew)
                                            <form method="POST"
                                                  action="{{ route('waze.alerts.read', $a->id) }}"
                                                  style="display:inline-block;">
                                                @csrf
                                                <button class="btn btn-success btn-sm" type="submit">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif

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


@section('js')
<script>
$(function () {

    $('#wazeAlerts tbody tr').each(function(i){
        const tds = $(this).find('td').length;
        if(tds !== 6){
            console.warn('Fila con columnas incorrectas:', i, tds);
        }
    });

    $('#wazeAlerts').DataTable({
        pageLength: 10,
        order: [[4, "desc"]],
        responsive: false,
        autoWidth: false,
        language: {
            emptyTable: "No hay información",
            info: "Mostrando _START_ a _END_ de _TOTAL_ alertas",
            infoEmpty: "Mostrando 0 a 0 de 0 alertas",
            infoFiltered: "(Filtrado de _MAX_ total alertas)",
            lengthMenu: "Mostrar _MENU_ alertas",
            loadingRecords: "Cargando...",
            processing: "Procesando...",
            search: "Buscador:",
            zeroRecords: "Sin resultados encontrados",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        }
    });

});
</script>
@stop
