@extends('adminlte::page')

@section('title', 'Folios del libro')

@section('content_header')
    <h1>Folios indexados · {{ $libro->codigo }}</h1>
@stop

@section('content')
    @include('admin.settings.archivo_siniestros._nav')
    @include('admin.settings.archivo_siniestros._alerts')
    <div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title">Índice de folios</h3><div class="card-tools"><a href="{{ route('archivo_siniestros.libros.show', $libro) }}" class="btn btn-secondary btn-sm">Volver al libro</a></div></div>
    <div class="card-body table-responsive"><table class="table table-sm table-striped table-bordered"><thead><tr><th>Año</th><th>Folio</th><th>Fecha del siniestro</th><th>Página del libro</th><th>Documento</th><th>Página digital</th><th>Estado</th></tr></thead><tbody>
        @forelse($folios as $folio)<tr><td>{{ $folio->anio }}</td><td>{{ $folio->folio }}</td><td>{{ optional($folio->fecha_siniestro)->format('d/m/Y') ?: '—' }}</td><td>{{ $folio->numero_pagina ?: '—' }}</td><td>{{ $folio->documento->nombre ?? '—' }}</td><td>{{ $folio->pagina_documento ?: '—' }}</td><td>{{ str_replace('_',' ', $folio->estado_verificacion) }}</td></tr>
        @empty<tr><td colspan="7" class="text-center py-3">No hay folios registrados en este libro.</td></tr>@endforelse
    </tbody></table></div>
    <div class="card-footer">{{ $folios->links('pagination::bootstrap-4') }}</div>
</div>
@can('digitalizar archivo siniestros')
<div class="card card-outline card-info"><div class="card-header"><h3 class="card-title">Añadir folio al índice</h3></div><div class="card-body">
    <form action="{{ route('archivo_siniestros.libros.folios.store', $libro) }}" method="POST">@csrf
        <div class="row">
            <div class="col-md-3 form-group"><label for="anio">Año *</label><input type="number" name="anio" id="anio" min="2010" max="2100" class="form-control" required value="{{ old('anio', $libro->anio_inicio) }}"></div>
            <div class="col-md-3 form-group"><label for="folio">Folio *</label><input name="folio" id="folio" maxlength="80" required class="form-control" value="{{ old('folio') }}"></div>
            <div class="col-md-3 form-group"><label for="fecha_siniestro">Fecha del siniestro</label><input type="date" name="fecha_siniestro" id="fecha_siniestro" class="form-control" value="{{ old('fecha_siniestro') }}"></div>
            <div class="col-md-3 form-group"><label for="numero_pagina">Página del libro</label><input type="number" min="1" name="numero_pagina" id="numero_pagina" class="form-control" value="{{ old('numero_pagina') }}"></div>
            <div class="col-md-8 form-group"><label for="documento_id">Escaneo asociado</label><select name="documento_id" id="documento_id" class="form-control"><option value="">Sin documento vinculado</option>@foreach($libro->documentos as $documento)<option value="{{ $documento->id }}" {{ (string) old('documento_id') === (string) $documento->id ? 'selected' : '' }}>{{ $documento->nombre }} (páginas {{ $documento->pagina_inicio }}-{{ $documento->pagina_fin }})</option>@endforeach</select></div>
            <div class="col-md-4 form-group"><label for="pagina_documento">Página en archivo digital</label><input type="number" min="1" name="pagina_documento" id="pagina_documento" class="form-control" value="{{ old('pagina_documento') }}"></div>
        </div>
        <button class="btn btn-primary"><i class="fa-solid fa-plus"></i> Registrar folio</button>
    </form>
</div></div>
@endcan
@stop
