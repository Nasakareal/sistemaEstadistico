@extends('adminlte::page')

@section('title', 'Detalle del Corte de Pendientes')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between flex-wrap">
        <h1 class="mb-0">Detalle del Corte de Pendientes</h1>

        <div class="d-flex" style="gap:8px; flex-wrap:wrap;">
            <a href="{{ route('hechos.pendientes.cortes.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>

            @if($prev)
                <a href="{{ route('hechos.pendientes.cortes.show', $prev->id) }}" class="btn btn-outline-info btn-sm">
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
    @endphp

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

        <div class="col-12 col-lg-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Información del Corte</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Corte actual</label>
                                <p class="form-control-static">{{ $corte->corte_fecha }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Corte previo</label>
                                <p class="form-control-static">{{ $prev ? $prev->corte_fecha : 'No disponible' }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Generado</label>
                                <p class="form-control-static">
                                    {{ $corte->created_at ? $corte->created_at->format('Y-m-d H:i') : '' }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Observaciones</label>
                                <p class="form-control-static">{{ $fmt($corte->observaciones ?? null) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $renderTable = function ($id, $rows) {
            return $rows && count($rows) > 0;
        };
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title mb-0">Resueltos</h3>
                </div>
                <div class="card-body">
                    @if (count($resueltos) === 0)
                        <div class="alert alert-info mb-0">No hay registros.</div>
                    @else
                        <table id="tbl_resueltos" class="table table-striped table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th><center>ID</center></th>
                                    <th><center>Folio</center></th>
                                    <th><center>Fecha</center></th>
                                    <th><center>Sector</center></th>
                                    <th><center>Unidad</center></th>
                                    <th><center>Situación</center></th>
                                    <th><center>Acciones</center></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resueltos as $h)
                                    <tr>
                                        <td>{{ $h->id }}</td>
                                        <td>{{ $h->folio_c5i }}</td>
                                        <td>{{ $h->fecha }}</td>
                                        <td>{{ $h->sector }}</td>
                                        <td>{{ $h->unidad }}</td>
                                        <td>{{ $h->situacion }}</td>
                                        <td style="text-align:center;">
                                            <a href="{{ route('hechos.show', $h->id) }}" class="btn btn-info btn-sm">
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

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title mb-0">Turnados</h3>
                </div>
                <div class="card-body">
                    @if (count($turnados) === 0)
                        <div class="alert alert-info mb-0">No hay registros.</div>
                    @else
                        <table id="tbl_turnados" class="table table-striped table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th><center>ID</center></th>
                                    <th><center>Folio</center></th>
                                    <th><center>Fecha</center></th>
                                    <th><center>Sector</center></th>
                                    <th><center>Unidad</center></th>
                                    <th><center>Situación</center></th>
                                    <th><center>Acciones</center></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($turnados as $h)
                                    <tr>
                                        <td>{{ $h->id }}</td>
                                        <td>{{ $h->folio_c5i }}</td>
                                        <td>{{ $h->fecha }}</td>
                                        <td>{{ $h->sector }}</td>
                                        <td>{{ $h->unidad }}</td>
                                        <td>{{ $h->situacion }}</td>
                                        <td style="text-align:center;">
                                            <a href="{{ route('hechos.show', $h->id) }}" class="btn btn-info btn-sm">
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

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title mb-0">Siguen PENDIENTE</h3>
                </div>
                <div class="card-body">
                    @if (count($siguen) === 0)
                        <div class="alert alert-info mb-0">No hay registros.</div>
                    @else
                        <table id="tbl_siguen" class="table table-striped table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th><center>ID</center></th>
                                    <th><center>Folio</center></th>
                                    <th><center>Fecha</center></th>
                                    <th><center>Sector</center></th>
                                    <th><center>Unidad</center></th>
                                    <th><center>Situación</center></th>
                                    <th><center>Acciones</center></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($siguen as $h)
                                    <tr>
                                        <td>{{ $h->id }}</td>
                                        <td>{{ $h->folio_c5i }}</td>
                                        <td>{{ $h->fecha }}</td>
                                        <td>{{ $h->sector }}</td>
                                        <td>{{ $h->unidad }}</td>
                                        <td>{{ $h->situacion }}</td>
                                        <td style="text-align:center;">
                                            <a href="{{ route('hechos.show', $h->id) }}" class="btn btn-info btn-sm">
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

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Otros estados</h3>
                </div>
                <div class="card-body">
                    @if (count($otros) === 0)
                        <div class="alert alert-info mb-0">No hay registros.</div>
                    @else
                        <table id="tbl_otros" class="table table-striped table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th><center>ID</center></th>
                                    <th><center>Folio</center></th>
                                    <th><center>Fecha</center></th>
                                    <th><center>Sector</center></th>
                                    <th><center>Unidad</center></th>
                                    <th><center>Situación</center></th>
                                    <th><center>Acciones</center></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($otros as $h)
                                    <tr>
                                        <td>{{ $h->id }}</td>
                                        <td>{{ $h->folio_c5i }}</td>
                                        <td>{{ $h->fecha }}</td>
                                        <td>{{ $h->sector }}</td>
                                        <td>{{ $h->unidad }}</td>
                                        <td>{{ $h->situacion }}</td>
                                        <td style="text-align:center;">
                                            <a href="{{ route('hechos.show', $h->id) }}" class="btn btn-info btn-sm">
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

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Nuevos pendientes</h3>
                </div>
                <div class="card-body">
                    @if ($nuevos->count() === 0)
                        <div class="alert alert-info mb-0">No hay registros.</div>
                    @else
                        <table id="tbl_nuevos" class="table table-striped table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th><center>ID</center></th>
                                    <th><center>Folio</center></th>
                                    <th><center>Fecha</center></th>
                                    <th><center>Sector</center></th>
                                    <th><center>Unidad</center></th>
                                    <th><center>Situación</center></th>
                                    <th><center>Acciones</center></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($nuevos as $h)
                                    <tr>
                                        <td>{{ $h->id }}</td>
                                        <td>{{ $h->folio_c5i }}</td>
                                        <td>{{ $h->fecha }}</td>
                                        <td>{{ $h->sector }}</td>
                                        <td>{{ $h->unidad }}</td>
                                        <td>{{ $h->situacion }}</td>
                                        <td style="text-align:center;">
                                            <a href="{{ route('hechos.show', $h->id) }}" class="btn btn-info btn-sm">
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

@section('css')
    <style>
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }
        .small-box .icon i {
            font-size: 60px;
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
