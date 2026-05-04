@extends('adminlte::page')

@section('title', 'Respaldos SQL')

@section('content_header')
    <h1>Respaldos SQL</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Archivos disponibles en storage/app/backups_sql</h3>
                <div class="card-tools">
                    <a href="{{ route('settings.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap">
                    <div class="mb-2 mb-md-0">
                        <b>Respaldo al momento de Delegaciones.</b>
                        Genera y descarga un SQL con actividades y hechos de la unidad org 2, junto con sus registros relacionados.
                        No se guarda en el servidor.
                    </div>
                    <a href="{{ route('backups_sql.delegaciones') }}" class="btn btn-success">
                        <i class="fa-solid fa-database"></i> Obtener respaldo
                    </a>
                </div>

                @if ($files->isEmpty())
                    <div class="alert alert-warning mb-0">
                        No hay respaldos aún. Coloca archivos .sql o .sql.gz en <b>storage/app/backups_sql</b>.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Archivo</th>
                                    <th class="text-right">Tamaño</th>
                                    <th>Última modificación</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($files as $f)
                                    <tr>
                                        <td>{{ $f['name'] }}</td>
                                        <td class="text-right">
                                            {{ number_format($f['size'] / 1024 / 1024, 2) }} MB
                                        </td>
                                        <td>
                                            {{ \Carbon\Carbon::createFromTimestamp($f['last_modified'])->format('Y-m-d H:i:s') }}
                                        </td>
                                        <td class="text-right">
                                            <a class="btn btn-primary btn-sm"
                                               href="{{ route('backups_sql.download', $f['name']) }}">
                                                <i class="fa-solid fa-download"></i> Descargar
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>
        </div>

    </div>
</div>
@stop
