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

                        @can('ver kilometrajes patrullas')
                            <a href="{{ route('patrullas.kilometrajes.index', $patrulla->id) }}" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-gauge-high"></i> Kilometrajes
                            </a>
                        @endcan

                        <a href="{{ route('patrullas.edit', $patrulla->id) }}" class="btn btn-success btn-sm">
                            <i class="fa-regular fa-pen-to-square"></i> Editar
                        </a>
                        <a href="{{ route('patrullas.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">

                    <h5 class="mb-3"><strong>Fotografía</strong></h5>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="foto-patrulla-box text-center">
                                @if ($patrulla->foto)
                                    <img
                                        src="{{ asset('storage/' . $patrulla->foto) }}"
                                        alt="Foto de la patrulla {{ $patrulla->numero_economico }}"
                                        class="img-fluid foto-patrulla"
                                    >
                                @else
                                    <div class="text-muted py-5">
                                        <i class="fa-regular fa-image fa-3x mb-3"></i>
                                        <div>Esta patrulla no tiene foto registrada</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <h5 class="mb-3"><strong>Datos Generales</strong></h5>

                    <table class="table table-bordered table-striped mb-4">
                        <tbody>
                            <tr>
                                <th>Número Económico</th>
                                <td>{{ $patrulla->numero_economico }}</td>
                            </tr>

                            <tr>
                                <th>Unidad</th>
                                <td>{{ $patrulla->unidad?->nombre ?? 'Sin unidad' }}</td>
                            </tr>

                            <tr>
                                <th>Turno</th>
                                <td>{{ $patrulla->turno?->nombre ?? 'Sin turno' }}</td>
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
                        </tbody>
                    </table>

                    <h5 class="mb-3"><strong>Datos del Vehículo</strong></h5>

                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <th>Tipo</th>
                                <td>{{ $patrulla->tipo ?? 'No especificado' }}</td>
                            </tr>

                            <tr>
                                <th>Marca</th>
                                <td>{{ $patrulla->marca ?? 'No especificado' }}</td>
                            </tr>

                            <tr>
                                <th>Línea</th>
                                <td>{{ $patrulla->linea ?? 'No especificado' }}</td>
                            </tr>

                            <tr>
                                <th>Modelo (Año)</th>
                                <td>{{ $patrulla->modelo ?? 'No especificado' }}</td>
                            </tr>

                            <tr>
                                <th>Placas</th>
                                <td>{{ $patrulla->placas ?? 'No registradas' }}</td>
                            </tr>

                            <tr>
                                <th>Serie (VIN)</th>
                                <td>{{ $patrulla->serie ?? 'No registrada' }}</td>
                            </tr>

                            <tr>
                                <th>No. Motor</th>
                                <td>{{ $patrulla->no_motor ?? 'No registrado' }}</td>
                            </tr>

                            <tr>
                                <th>Color</th>
                                <td>{{ $patrulla->color ?? 'No especificado' }}</td>
                            </tr>

                            <tr>
                                <th>Observaciones</th>
                                <td>
                                    {!! $patrulla->observaciones
                                        ? nl2br(e($patrulla->observaciones))
                                        : '<span class="text-muted">Sin observaciones</span>' !!}
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
                    <small>ID interno: {{ $patrulla->id }}</small>
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

    .foto-patrulla-box {
        background: rgba(0,0,0,.15);
        border: 1px solid rgba(255,255,255,.10);
        border-radius: .5rem;
        padding: 15px;
    }

    .foto-patrulla {
        max-width: 100%;
        max-height: 420px;
        border-radius: .4rem;
        box-shadow: 0 4px 18px rgba(0,0,0,.25);
    }
</style>
@stop
