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
                    <label>Número de dictamen</label>
                    <input type="text" class="form-control" value="{{ $numeroSiguiente }}" disabled>
                </div>

                <div class="form-group col-md-3">
                    <label>Año</label>
                    <input type="text" class="form-control" value="{{ now()->year }}" readonly>
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
