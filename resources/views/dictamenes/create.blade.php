@extends('adminlte::page')

@section('title', 'Nuevo Dictamen')

@section('content_header')
    <h1 class="mb-0">Nuevo Dictamen</h1>
@stop

@section('content')
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('dictamenes.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="numero_dictamen">Número de dictamen</label>
                    <input type="number" name="numero_dictamen" id="numero_dictamen"
                           class="form-control @error('numero_dictamen') is-invalid @enderror"
                           min="1" step="1" value="{{ old('numero_dictamen', $numeroSiguiente) }}" required>
                    @error('numero_dictamen')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                    <small class="form-text text-muted">Para años anteriores, capture el número que aparece en el documento.</small>
                </div>

                <div class="form-group col-md-3">
                    <label for="anio">Año</label>
                    <input type="number" name="anio" id="anio"
                           class="form-control @error('anio') is-invalid @enderror"
                           min="{{ $anioMinimo }}" max="{{ $anioActual }}" step="1"
                           value="{{ old('anio', $anioActual) }}" required>
                    @error('anio')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                    <small class="form-text text-muted">Se permiten dictámenes de {{ $anioMinimo }} a {{ $anioActual }}.</small>
                </div>

                <div class="form-group col-md-6">
                    <label>Área</label>
                    <input type="text" class="form-control" value="{{ $unidadNombre ?? 'SIN ASIGNAR' }}" readonly>
                </div>
            </div>

            <div class="form-group">
                <label>Nombre del policía</label>
                <input type="text" name="nombre_policia" class="form-control" value="{{ old('nombre_policia') }}" required>
            </div>

            <div class="form-group">
                <label>Nombre del Ministerio Público</label>
                <input type="text" name="nombre_mp" class="form-control" value="{{ old('nombre_mp') }}" required>
            </div>

            <div class="form-group">
                <label>Archivo de dictamen (PDF)</label>
                <input type="file" name="archivo_dictamen" class="form-control-file" accept="application/pdf">
                <small class="form-text text-muted">Opcional. Solo PDF, máximo 10 MB.</small>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('dictamenes.index') }}" class="btn btn-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    Guardar dictamen
                </button>
            </div>
        </form>
    </div>
</div>
@stop
