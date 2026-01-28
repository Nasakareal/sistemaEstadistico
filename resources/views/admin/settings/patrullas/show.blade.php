@extends('adminlte::page')

@section('title', 'Detalle de Patrulla')

@section('content_header')
    <h1>Detalle de Patrulla</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Información de la Patrulla</h3>
                    <div class="card-tools">
                        <a href="{{ route('patrullas.edit', $patrulla->id) }}" class="btn btn-success btn-sm">
                            <i class="fa-regular fa-pen-to-square"></i> Editar
                        </a>
                        <a href="{{ route('patrullas.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <th style="width: 30%">Número Económico</th>
                                <td>{{ $patrulla->numero_economico }}</td>
                            </tr>

                            <tr>
                                <th>Unidad</th>
                                <td>
                                    {{ $patrulla->unidad?->nombre ?? 'Sin unidad' }}
                                </td>
                            </tr>

                            <tr>
                                <th>Turno</th>
                                <td>
                                    {{ $patrulla->turno?->nombre ?? 'Sin turno' }}
                                </td>
                            </tr>

                            <tr>
                                <th>Estado</th>
                                <td>
                                    @if ($patrulla->activa)
                                        <span class="badge badge-success">Activa</span>
                                    @else
                                        <span class="badge badge-danger">Inactiva</span>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <th>Fecha de Creación</th>
                                <td>{{ $patrulla->created_at?->format('d-m-Y H:i') }}</td>
                            </tr>

                            <tr>
                                <th>Última Actualización</th>
                                <td>{{ $patrulla->updated_at?->format('d-m-Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer text-muted">
                    <small>
                        ID interno: {{ $patrulla->id }}
                    </small>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    /* Respeta el estilo oscuro del tema y no “lava” el TH */
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

    /* Suaviza el cuadro completo */
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

    /* Badges más finos */
    .badge {
        padding: .35rem .6rem;
        font-size: .85rem;
        border-radius: .35rem;
    }
</style>
@stop
