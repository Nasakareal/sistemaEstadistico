@extends('adminlte::page')

@section('title', 'Detalle Examen Diario')

@section('content_header')
    <h1>Detalle Examen Diario</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">

        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Información del Registro</h3>

                <div class="card-tools">
                    @can('editar modulo examenes diarios')
                        <a href="{{ route('modulo_examenes_diarios.edit', $registro->id) }}" class="btn btn-success btn-sm">
                            <i class="fa-regular fa-pen-to-square"></i> Editar
                        </a>
                    @endcan

                    <a href="{{ route('modulo_examenes_diarios.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>

            <div class="card-body">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="small text-muted">Fecha</div>
                        <div class="h5 mb-0">
                            {{ optional($registro->fecha)->format('d-m-Y') ?? '—' }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="small text-muted">Módulo</div>
                        <div class="h5 mb-0">
                            {{ $registro->modulo_nombre ?? '—' }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="small text-muted">Informado por</div>
                        <div class="h5 mb-0">
                            {{ $registro->informado_por ?? '—' }}
                        </div>
                    </div>
                </div>

                <hr>

                <h5 class="mb-3"><strong>Tipos de Examen</strong></h5>

                <div class="row">
                    <div class="col-md-2 col-6 mb-3">
                        <div class="info-box bg-info">
                            <span class="info-box-icon"><i class="fa-solid fa-bus"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Serv. Público</span>
                                <span class="info-box-number">{{ number_format((int)($registro->servicio_publico ?? 0)) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-2 col-6 mb-3">
                        <div class="info-box bg-primary">
                            <span class="info-box-icon"><i class="fa-solid fa-car"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Automovilista</span>
                                <span class="info-box-number">{{ number_format((int)($registro->automovilista ?? 0)) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-2 col-6 mb-3">
                        <div class="info-box bg-warning">
                            <span class="info-box-icon"><i class="fa-solid fa-id-card"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Chofer</span>
                                <span class="info-box-number">{{ number_format((int)($registro->chofer ?? 0)) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-2 col-6 mb-3">
                        <div class="info-box bg-danger">
                            <span class="info-box-icon"><i class="fa-solid fa-motorcycle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Motociclista</span>
                                <span class="info-box-number">{{ number_format((int)($registro->motociclista ?? 0)) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-2 col-6 mb-3">
                        <div class="info-box bg-secondary">
                            <span class="info-box-icon"><i class="fa-solid fa-file-signature"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Permiso</span>
                                <span class="info-box-number">{{ number_format((int)($registro->permiso ?? 0)) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-2 col-6 mb-3">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fa-solid fa-sigma"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total</span>
                                <span class="info-box-number">{{ number_format((int)($registro->total ?? 0)) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <h5 class="mb-3"><strong>Resultados</strong></h5>

                <table class="table table-bordered table-striped table-sm">
                    <tbody>
                        <tr>
                            <th style="width:30%;">Hombres</th>
                            <td>{{ number_format((int)($registro->hombres ?? 0)) }}</td>
                        </tr>
                        <tr>
                            <th>Mujeres</th>
                            <td>{{ number_format((int)($registro->mujeres ?? 0)) }}</td>
                        </tr>
                        <tr>
                            <th>Aprobados</th>
                            <td>{{ number_format((int)($registro->aprobados ?? 0)) }}</td>
                        </tr>
                        <tr>
                            <th>Reprobados</th>
                            <td>{{ number_format((int)($registro->reprobados ?? 0)) }}</td>
                        </tr>
                        <tr>
                            <th>Folios</th>
                            <td>{{ $registro->folios ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <div class="small text-muted">Creado</div>
                        <div>{{ $registro->created_at?->format('d-m-Y H:i') ?? '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Actualizado</div>
                        <div>{{ $registro->updated_at?->format('d-m-Y H:i') ?? '—' }}</div>
                    </div>
                </div>

            </div>

            <div class="card-footer text-muted">
                <small>ID interno: {{ $registro->id }}</small>
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

    .info-box {
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,.20);
    }

    .info-box .info-box-number {
        font-size: 18px;
        font-weight: 900;
    }

    .info-box .info-box-text {
        font-weight: 700;
    }
</style>
@stop
