@extends('adminlte::page')

@section('title', 'Alertas Waze')

@section('content_header')
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="mb-0">Alertas Waze</h1>

            <div class="d-flex" style="gap:8px;">
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
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Listado de Alertas</h3>
                <div class="card-tools">
                    {{-- Puedes poner aquí algo extra si quieres --}}
                </div>
            </div>

            <div class="card-body table-responsive">
                <table id="wazeAlerts" class="table table-striped table-bordered table-hover table-sm">
                    <thead>
                        <tr>
                            <th style="width:80px;"><center>Estado</center></th>
                            <th><center>Tipo</center></th>
                            <th><center>Subtipo</center></th>
                            <th><center>Lugar</center></th>
                            <th><center>Fecha</center></th>
                            <th style="width:180px;"><center>Acciones</center></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alerts as $a)
                            @php
                                $street = $a->street ?: 'Sin calle';
                                $city = $a->city ?: '';
                                $place = trim($street . ($city ? ', ' . $city : ''));

                                $mapsUrl = ($a->lat && $a->lng)
                                    ? ('https://www.google.com/maps/search/?api=1&query=' . $a->lat . ',' . $a->lng)
                                    : null;

                                $isNew = ((int)($a->is_read ?? 0) === 0);
                            @endphp

                            <tr class="{{ $isNew ? 'font-weight-bold' : '' }}">
                                <td style="text-align:center; vertical-align:middle;">
                                    @if($isNew)
                                        <span class="badge badge-danger">NUEVA</span>
                                    @else
                                        <span class="badge badge-secondary">LEÍDA</span>
                                    @endif
                                </td>

                                <td style="text-align:center; vertical-align:middle;">
                                    {{ $a->type }}
                                </td>

                                <td style="text-align:center; vertical-align:middle;">
                                    {{ $a->subtype }}
                                </td>

                                <td style="text-align:center; vertical-align:middle;">
                                    {{ $place }}
                                </td>

                                <td style="text-align:center; vertical-align:middle;">
                                    {{ optional($a->published_at)->format('Y-m-d H:i') }}
                                </td>

                                <td style="text-align:center; vertical-align:middle;">
                                    <div class="btn-group" role="group" style="gap:6px;">
                                        @if($mapsUrl)
                                            <a class="btn btn-info btn-sm" target="_blank" href="{{ $mapsUrl }}">
                                                <i class="fas fa-map-marker-alt"></i>
                                            </a>
                                        @endif

                                        @if($isNew)
                                            <form method="POST" action="{{ route('waze.alerts.read', $a->id) }}" style="display:inline-block;">
                                                @csrf
                                                <button class="btn btn-success btn-sm" type="submit">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted" style="text-align:center;">
                                    No hay alertas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- IMPORTANTE: quitamos $alerts->links() porque DataTables pagina --}}
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
        $('#wazeAlerts').DataTable({
            "pageLength": 10,
            "order": [[4, "desc"]],
            "language": {
                "emptyTable": "No hay información",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ alertas",
                "infoEmpty": "Mostrando 0 a 0 de 0 alertas",
                "infoFiltered": "(Filtrado de _MAX_ total alertas)",
                "lengthMenu": "Mostrar _MENU_ alertas",
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
            timer: 6000
        });
    @endif
</script>
@stop
