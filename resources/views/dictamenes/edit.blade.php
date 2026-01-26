@extends('adminlte::page')

@section('title', 'Editar Dictamen')

@section('content_header')
    <h1 class="mb-0">Editar Dictamen</h1>
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
        <form method="POST" action="{{ route('dictamenes.update', $dictamen->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Número de dictamen</label>
                    <input
                        type="text"
                        name="numero_dictamen"
                        class="form-control"
                        value="{{ old('numero_dictamen', $dictamen->numero_dictamen) }}"
                        readonly
                    >
                </div>

                <div class="form-group col-md-3">
                    <label>Año</label>
                    <input
                        type="text"
                        name="anio"
                        class="form-control"
                        value="{{ old('anio', $dictamen->anio) }}"
                        readonly
                    >
                </div>

                <div class="form-group col-md-6">
                    <label>Área</label>
                    <input
                        type="text"
                        name="area"
                        class="form-control"
                        value="{{ old('area', $dictamen->area) }}"
                        readonly
                    >
                </div>
            </div>

            <div class="form-group">
                <label>Nombre del policía</label>
                <input
                    type="text"
                    name="nombre_policia"
                    class="form-control"
                    value="{{ old('nombre_policia', $dictamen->nombre_policia) }}"
                    required
                >
            </div>

            <div class="form-group">
                <label>Nombre del Ministerio Público</label>
                <input
                    type="text"
                    name="nombre_mp"
                    class="form-control"
                    value="{{ old('nombre_mp', $dictamen->nombre_mp) }}"
                    required
                >
            </div>

            <div class="form-group">
                <label>Archivo de dictamen (PDF)</label>

                @if($dictamen->archivo_dictamen)
                    <div class="mb-2">
                        <a
                            href="{{ asset('storage/'.$dictamen->archivo_dictamen) }}"
                            target="_blank"
                            class="btn btn-sm btn-outline-danger"
                        >
                            <i class="fas fa-file-pdf"></i> Ver archivo actual
                        </a>
                    </div>
                @endif

                <input
                    type="file"
                    name="archivo_dictamen"
                    class="form-control-file"
                    accept="application/pdf"
                >
                <small class="form-text text-muted">
                    Si subes un archivo nuevo, reemplazará el anterior.
                </small>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('dictamenes.index') }}" class="btn btn-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    Guardar cambios
                </button>
            </div>
        </form>

    </div>
</div>
@stop
