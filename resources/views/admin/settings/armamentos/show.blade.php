@extends('adminlte::page')

@section('title', 'Detalle de Armamento')

@section('content_header')
    <h1>Detalle de Armamento</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Información del Armamento</h3>
                    <div class="card-tools">
                        <a href="{{ route('armamentos.edit', $armamento->id) }}" class="btn btn-success btn-sm">
                            <i class="fa-regular fa-pen-to-square"></i> Editar
                        </a>
                        <a href="{{ route('armamentos.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">

                    <h5 class="mb-3"><strong>Datos Generales</strong></h5>

                    <table class="table table-bordered table-striped mb-4">
                        <tbody>
                            <tr>
                                <th>Unidad</th>
                                <td>{{ $armamento->unidad?->nombre ?? 'Sin unidad' }}</td>
                            </tr>

                            <tr>
                                <th>Estatus</th>
                                <td>
                                    @if ($armamento->estatus === 'ACTIVO')
                                        <span class="badge badge-success">ACTIVO</span>
                                    @elseif ($armamento->estatus === 'BAJA')
                                        <span class="badge badge-danger">BAJA</span>
                                    @elseif ($armamento->estatus === 'RESGUARDO')
                                        <span class="badge badge-warning">RESGUARDO</span>
                                    @elseif ($armamento->estatus === 'MANTENIMIENTO')
                                        <span class="badge badge-info">MANTENIMIENTO</span>
                                    @else
                                        <span class="badge badge-secondary">{{ $armamento->estatus }}</span>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <th>Fecha de Registro</th>
                                <td>{{ $armamento->created_at?->format('d-m-Y H:i') }}</td>
                            </tr>

                            <tr>
                                <th>Última Actualización</th>
                                <td>{{ $armamento->updated_at?->format('d-m-Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <h5 class="mb-3"><strong>Datos del Armamento</strong></h5>

                    <table class="table table-bordered table-striped mb-4">
                        <tbody>
                            <tr>
                                <th>Tipo</th>
                                <td>{{ $armamento->tipo }}</td>
                            </tr>

                            <tr>
                                <th>Clase</th>
                                <td>{{ $armamento->clase ?? 'No especificada' }}</td>
                            </tr>

                            <tr>
                                <th>Marca</th>
                                <td>{{ $armamento->marca ?? 'No especificada' }}</td>
                            </tr>

                            <tr>
                                <th>Modelo</th>
                                <td>{{ $armamento->modelo ?? 'No especificado' }}</td>
                            </tr>

                            <tr>
                                <th>Calibre</th>
                                <td>{{ $armamento->calibre ?? 'No especificado' }}</td>
                            </tr>

                            <tr>
                                <th>Matrícula</th>
                                <td>{{ $armamento->matricula ?? 'No registrada' }}</td>
                            </tr>

                            <tr>
                                <th>Serie</th>
                                <td>{{ $armamento->serie ?? 'No registrada' }}</td>
                            </tr>

                            <tr>
                                <th>Observaciones</th>
                                <td>
                                    {!! $armamento->observaciones
                                        ? nl2br(e($armamento->observaciones))
                                        : '<span class="text-muted">Sin observaciones</span>' !!}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <h5 class="mb-3"><strong>Accesorios entregados</strong></h5>

                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <th>Cargadores</th>
                                <td>{{ $armamento->cargadores_cantidad ?? 0 }}</td>
                            </tr>

                            <tr>
                                <th>Cartuchos</th>
                                <td>{{ $armamento->cartuchos_cantidad ?? 0 }}</td>
                            </tr>
                        </tbody>
                    </table>

                </div>

                <div class="card-footer text-muted">
                    <small>ID interno: {{ $armamento->id }}</small>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .table.table-bordered th,
    .table.table-bordered td {
        color: #e9ecef !important;
        vertical-align: middle;
    }

    .table.table-bordered th {
        background: rgba(255,255,255,.06) !important;
        border-color: rgba(255,255,255,.12) !important;
        font-weight: 700;
        width: 30%;
    }

    .table.table-bordered td {
        background: rgba(0,0,0,.15) !important;
        border-color: rgba(255,255,255,.08) !important;
    }

    .card.card-outline.card-info {
        border-color: rgba(255,255,255,.12) !important;
    }

    .card.card-outline.card-info .card-header {
        border-bottom-color: rgba(255,255,255,.10) !important;
    }

    .card .card-footer {
        border-top-color: rgba(255,255,255,.10) !important;
        color: rgba(233,236,239,.75) !important;
    }

    .badge {
        padding: .35rem .6rem;
        font-size: .85rem;
        border-radius: .35rem;
    }
</style>
@stop
