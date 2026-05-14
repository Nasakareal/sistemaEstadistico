@extends('adminlte::page')

@section('title', 'Nuevo programa de Fomento')

@section('content_header')
    <h1>Nuevo programa de Fomento</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ $actividadSubcategoria->nombre }}</h3>
                </div>

                <form action="{{ route('catalogos_actividades.fomento_programas.store', $actividadSubcategoria->id) }}" method="POST">
                    @csrf

                    <div class="card-body">
                        <div class="form-group">
                            <label for="nombre">Nombre<span style="color:red">*</span></label>
                            <input type="text"
                                   name="nombre"
                                   id="nombre"
                                   class="form-control @error('nombre') is-invalid @enderror"
                                   value="{{ old('nombre') }}"
                                   required>
                            @error('nombre')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="orden">Orden</label>
                            <input type="number"
                                   min="0"
                                   name="orden"
                                   id="orden"
                                   class="form-control @error('orden') is-invalid @enderror"
                                   value="{{ old('orden', 0) }}">
                            @error('orden')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-check">
                            <input type="checkbox" name="activo" id="activo" value="1" class="form-check-input" {{ old('activo', 1) ? 'checked' : '' }}>
                            <label for="activo" class="form-check-label">Activo</label>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check"></i> Guardar
                        </button>
                        <a href="{{ route('catalogos_actividades.index') }}" class="btn btn-secondary">
                            <i class="fa-solid fa-ban"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop
