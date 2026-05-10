@extends('adminlte::page')

@section('title', 'Reporte Delegaciones')

@section('content_header')
    <h1>Reporte Delegaciones</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8 col-lg-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Actividades y hechos</h3>
                    <div class="card-tools">
                        <a href="{{ route('settings.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <form action="{{ route('backups_sql.delegaciones.excel') }}" method="GET">
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                Revisa las fechas capturadas.
                            </div>
                        @endif

                        <div class="alert alert-info">
                            El Excel se genera solo con registros de Delegaciones, unidad org ID 2.
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="fecha_inicio">Fecha inicio</label>
                                <input type="date"
                                       name="fecha_inicio"
                                       id="fecha_inicio"
                                       class="form-control @error('fecha_inicio') is-invalid @enderror"
                                       value="{{ old('fecha_inicio', $fechaInicio) }}"
                                       required>
                                @error('fecha_inicio')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label for="fecha_fin">Fecha fin</label>
                                <input type="date"
                                       name="fecha_fin"
                                       id="fecha_fin"
                                       class="form-control @error('fecha_fin') is-invalid @enderror"
                                       value="{{ old('fecha_fin', $fechaFin) }}"
                                       required>
                                @error('fecha_fin')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-file-excel"></i> Descargar Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop
