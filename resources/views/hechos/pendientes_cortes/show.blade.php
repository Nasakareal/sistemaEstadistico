@extends('adminlte::page')

@php
    $titulo = $titulo ?? 'Detalle del Corte de Pendientes';
    $routeIndex = $routeIndex ?? 'hechos.pendientes.cortes.index';
    $routeShow = $routeShow ?? 'hechos.pendientes.cortes.show';
@endphp

@section('title', $titulo)

@section('content_header')
    <div class="d-flex align-items-center justify-content-between flex-wrap">
        <h1 class="mb-0">{{ $titulo }}</h1>

        <div class="d-flex" style="gap:8px; flex-wrap:wrap;">
            <a href="{{ route($routeIndex) }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>

            @if($prev)
                <a href="{{ route($routeShow, $prev->id) }}" class="btn btn-outline-info btn-sm">
                    <i class="fa-solid fa-backward-step"></i> Corte anterior
                </a>
            @endif
        </div>
    </div>
@stop

@section('content')
    @php
        $fmt = function ($v) {
            if (is_null($v) || $v === '') return 'No especificado';
            return (string) $v;
        };

        $corteSinPendientesUnidad = collect($totales ?? [])->sum() === 0;
    @endphp

    @if($corteSinPendientesUnidad)
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            Corte generado sin pendientes para esta unidad.
        </div>
    @endif

    {{-- TARJETAS --}}
    <div class="row">
        <div class="col-12 col-md-6 col-lg-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totales['previos'] }}</h3>
                    <p>Pendientes del corte previo</p>
                </div>
                <div class="icon">
                    <i class="fa-solid fa-list-check"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totales['resueltos'] }}</h3>
                    <p>Resueltos</p>
                </div>
                <div class="icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $totales['turnados'] }}</h3>
                    <p>Turnados</p>
                </div>
                <div class="icon">
                    <i class="fa-solid fa-share"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $totales['siguen_pendiente'] }}</h3>
                    <p>Siguen PENDIENTE</p>
                </div>
                <div class="icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-6 col-lg-3">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $totales['otros'] }}</h3>
                    <p>Otros estados</p>
                </div>
                <div class="icon">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $totales['nuevos_pendientes'] }}</h3>
                    <p>Nuevos pendientes</p>
                </div>
                <div class="icon">
                    <i class="fa-solid fa-plus"></i>
                </div>
            </div>
        </div>

        {{-- INFO --}}
        <div class="col-12 col-lg-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Información del Corte</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label>Corte actual</label>
                            <p>{{ $corte->corte_fecha }}</p>
                        </div>

                        <div class="col-md-6">
                            <label>Corte previo</label>
                            <p>{{ $prev ? $prev->corte_fecha : 'No disponible' }}</p>
                        </div>

                        <div class="col-md-6">
                            <label>Generado</label>
                            <p>{{ $corte->created_at ? $corte->created_at->format('Y-m-d H:i') : '' }}</p>
                        </div>

                        <div class="col-md-6">
                            <label>Observaciones</label>
                            <p>{{ $fmt($corte->observaciones ?? null) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== --}}
    {{-- TABLA RESUELTOS --}}
    {{-- ===================== --}}
    <div class="row">
        <div class="col-12">
            <div class="tabla-header bg-success">
                <i class="fa-solid fa-circle-check"></i> Resueltos
            </div>

            <div class="card">
                <div class="card-body">
                    @if(count($resueltos) === 0)
                        <div class="alert alert-info mb-0">No hay registros.</div>
                    @else
                        <table id="tbl_resueltos" class="table table-striped table-bordered table-hover table-sm">
                            <thead class="thead-success">
                                <tr>
                                    <th>ID</th><th>Fecha</th>
                                    <th>Unidad</th><th>Situación</th><th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resueltos as $h)
                                    <tr>
                                        <td>{{ $h->id }}</td>
                                        <td>{{ $h->fecha }}</td>
                                        <td>{{ $h->unidad }}</td>
                                        <td>{{ $h->situacion }}</td>
                                        <td>
                                            <a href="{{ route('hechos.show',$h->id) }}" class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== --}}
    {{-- TABLA TURNADOS --}}
    {{-- ===================== --}}
    <div class="row">
        <div class="col-12">
            <div class="tabla-header bg-warning text-dark">
                <i class="fa-solid fa-share"></i> Turnados
            </div>

            <div class="card">
                <div class="card-body">
                    @if(count($turnados) === 0)
                        <div class="alert alert-info mb-0">No hay registros.</div>
                    @else
                        <table id="tbl_turnados" class="table table-striped table-bordered table-hover table-sm">
                            <thead class="thead-warning">
                                <tr>
                                    <th>ID</th><th>Fecha</th>
                                    <th>Unidad</th><th>Situación</th><th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($turnados as $h)
                                    <tr>
                                        <td>{{ $h->id }}</td>
                                        <td>{{ $h->fecha }}</td>
                                        <td>{{ $h->unidad }}</td>
                                        <td>{{ $h->situacion }}</td>
                                        <td>
                                            <a href="{{ route('hechos.show',$h->id) }}" class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== --}}
    {{-- TABLA SIGUEN --}}
    {{-- ===================== --}}
    <div class="row">
        <div class="col-12">
            <div class="tabla-header bg-danger">
                <i class="fa-solid fa-triangle-exclamation"></i> Siguen PENDIENTE
            </div>

            <div class="card">
                <div class="card-body">
                    @if(count($siguen) === 0)
                        <div class="alert alert-info mb-0">No hay registros.</div>
                    @else
                        <table id="tbl_siguen" class="table table-striped table-bordered table-hover table-sm">
                            <thead class="thead-danger">
                                <tr>
                                    <th>ID</th><th>Fecha</th>
                                    <th>Unidad</th><th>Situación</th><th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($siguen as $h)
                                    <tr>
                                        <td>{{ $h->id }}</td>
                                        <td>{{ $h->fecha }}</td>
                                        <td>{{ $h->unidad }}</td>
                                        <td>{{ $h->situacion }}</td>
                                        <td>
                                            <a href="{{ route('hechos.show',$h->id) }}" class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== --}}
    {{-- TABLA OTROS --}}
    {{-- ===================== --}}
    <div class="row">
        <div class="col-12">
            <div class="tabla-header bg-secondary">
                <i class="fa-solid fa-layer-group"></i> Otros estados
            </div>

            <div class="card">
                <div class="card-body">
                    @if(count($otros) === 0)
                        <div class="alert alert-info mb-0">No hay registros.</div>
                    @else
                        <table id="tbl_otros" class="table table-striped table-bordered table-hover table-sm">
                            <thead class="thead-secondary">
                                <tr>
                                    <th>ID</th><th>Fecha</th>
                                    <th>Unidad</th><th>Situación</th><th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($otros as $h)
                                    <tr>
                                        <td>{{ $h->id }}</td>
                                        <td>{{ $h->fecha }}</td>
                                        <td>{{ $h->unidad }}</td>
                                        <td>{{ $h->situacion }}</td>
                                        <td>
                                            <a href="{{ route('hechos.show',$h->id) }}" class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== --}}
    {{-- TABLA NUEVOS --}}
    {{-- ===================== --}}
    <div class="row">
        <div class="col-12">
            <div class="tabla-header bg-primary">
                <i class="fa-solid fa-plus"></i> Nuevos pendientes
            </div>

            <div class="card">
                <div class="card-body">
                    @if(count($nuevos) === 0)
                        <div class="alert alert-info mb-0">No hay registros.</div>
                    @else
                        <table id="tbl_nuevos" class="table table-striped table-bordered table-hover table-sm">
                            <thead class="thead-primary">
                                <tr>
                                    <th>ID</th><th>Fecha</th>
                                    <th>Unidad</th><th>Situación</th><th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($nuevos as $h)
                                    <tr>
                                        <td>{{ $h->id }}</td>
                                        <td>{{ $h->fecha }}</td>
                                        <td>{{ $h->unidad }}</td>
                                        <td>{{ $h->situacion }}</td>
                                        <td>
                                            <a href="{{ route('hechos.show',$h->id) }}" class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop

{{-- CSS --}}
@section('css')
<style>
    .tabla-header{
        padding: 14px 18px;
        font-size: 20px;
        font-weight: bold;
        color: white;
        border-radius: 10px 10px 0 0;
        display:flex;
        align-items:center;
        gap:10px;
        margin-top:25px;
    }

    .thead-success th{background:#28a745!important;color:white!important;}
    .thead-warning th{background:#ffc107!important;color:black!important;}
    .thead-danger th{background:#dc3545!important;color:white!important;}
    .thead-primary th{background:#007bff!important;color:white!important;}
    .thead-secondary th{background:#6c757d!important;color:white!important;}

    .card{
        border-radius: 0 0 12px 12px;
        overflow:hidden;
    }
</style>
@stop



@section('js')
    <script>
        $(function () {
            const makeDT = (id, orderCol) => {
                if (!document.getElementById(id)) return;
                $('#' + id).DataTable({
                    paging: false,
                    info: false,
                    order: [[orderCol, "desc"]],
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
            };

            makeDT('tbl_resueltos', 2);
            makeDT('tbl_turnados', 2);
            makeDT('tbl_siguen', 2);
            makeDT('tbl_otros', 2);
            makeDT('tbl_nuevos', 2);
        });
    </script>
@stop
