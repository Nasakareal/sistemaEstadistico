@extends('adminlte::page')

@section('title', 'Detalle Guardia SCT')

@section('content_header')
    <h1>Detalle de la Guardia SCT</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Información de la Guardia</h3>
                </div>

                <div class="card-body">
                    <div class="row">

                        <!-- Grúa -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Grúa</label>
                                <input type="text" class="form-control" 
                                       value="{{ $guardia->grua->nombre ?? '-' }}" readonly>
                            </div>
                        </div>

                        <!-- Tramo -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tramo</label>
                                <input type="text" class="form-control"
                                       value="{{ $guardia->tramo 
                                            ? $guardia->tramo->carretera.' - '.$guardia->tramo->nombre 
                                            : 'GENERAL' }}" readonly>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Estado</label>
                                <input type="text" class="form-control"
                                       value="{{ $guardia->activo ? 'ACTIVO' : 'INACTIVO' }}" readonly>
                            </div>
                        </div>

                    </div>

                    <div class="row">

                        <!-- Día Inicio -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Día Inicio</label>
                                <input type="text" class="form-control"
                                       value="{{ $guardia->dia_inicio }}" readonly>
                            </div>
                        </div>

                        <!-- Día Fin -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Día Fin</label>
                                <input type="text" class="form-control"
                                       value="{{ $guardia->dia_fin == 31 
                                            ? '31 (ÚLTIMO DÍA)' 
                                            : $guardia->dia_fin }}" readonly>
                            </div>
                        </div>

                        <!-- Fecha Registro -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Registrado</label>
                                <input type="text" class="form-control"
                                       value="{{ $guardia->created_at->format('d/m/Y H:i') }}" readonly>
                            </div>
                        </div>

                        <!-- Última Actualización -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Última Actualización</label>
                                <input type="text" class="form-control"
                                       value="{{ $guardia->updated_at->format('d/m/Y H:i') }}" readonly>
                            </div>
                        </div>

                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-12">
                            <a href="{{ route('grua-guardias-sct.edit', $guardia->id) }}" 
                               class="btn btn-warning">
                                <i class="fa-solid fa-pen-to-square"></i> Editar
                            </a>

                            <a href="{{ route('grua-guardias-sct.index') }}" 
                               class="btn btn-secondary">
                                <i class="fa-solid fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .form-group label {
        font-weight: bold;
    }
</style>
@stop
