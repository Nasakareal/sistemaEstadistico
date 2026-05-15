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
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        Revisa el archivo seleccionado. Solo se aceptan respaldos .sql o .sql.gz.
                    </div>
                @endif

                <form action="{{ route('backups_sql.upload') }}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="mb-4">
                    @csrf
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-8 mb-md-0">
                            <label for="backup">Subir respaldo de producción</label>
                            <input type="file"
                                   name="backup"
                                   id="backup"
                                   class="form-control-file @error('backup') is-invalid @enderror"
                                   accept=".sql,.gz"
                                   required>
                            @error('backup')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">
                                Se guardará en storage/app/backups_sql. El archivo debe terminar en .sql o .sql.gz.
                            </small>
                        </div>
                        <div class="form-group col-md-4 mb-md-0 text-md-right">
                            <button type="submit" class="btn btn-success">
                                <i class="fa-solid fa-upload"></i> Subir respaldo
                            </button>
                        </div>
                    </div>
                </form>

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
