@extends('adminlte::page')

@section('title', 'Archivo de siniestros')

@section('content_header')
    <h1>Libros del archivo de siniestros</h1>
@stop

@section('content')
    @include('admin.settings.archivo_siniestros._nav')
    @include('admin.settings.archivo_siniestros._alerts')
    <div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title">Inventario y búsqueda de libros</h3><div class="card-tools">@can('gestionar archivo siniestros')<a href="{{ route('archivo_siniestros.libros.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Registrar libro</a>@endcan</div></div>
    <div class="card-body">
        <form action="{{ route('archivo_siniestros.buscar') }}" method="GET" class="row mb-3">
            <div class="col-md-5 form-group"><label for="q">Código, título o folio exacto</label><input id="q" name="q" class="form-control" maxlength="150" value="{{ request('q') }}" placeholder="Buscar libro o folio..."></div>
            <div class="col-md-2 form-group"><label for="anio">Año</label><input type="number" min="2010" max="2100" id="anio" name="anio" class="form-control" value="{{ request('anio') }}"></div>
            <div class="col-md-3 form-group"><label for="estado_fisico">Ubicación física</label><select id="estado_fisico" name="estado_fisico" class="form-control"><option value="">Cualquiera</option>@foreach(['por_ubicar'=>'Pendiente de ubicación','almacenado'=>'Almacenado','en_consulta'=>'En consulta'] as $estado=>$etiqueta)<option value="{{ $estado }}" {{ request('estado_fisico') === $estado ? 'selected' : '' }}>{{ $etiqueta }}</option>@endforeach</select></div>
            <div class="col-md-2 form-group d-flex align-items-end"><button class="btn btn-primary btn-block"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button></div>
        </form>
        <div class="table-responsive"><table class="table table-striped table-bordered table-hover"><thead><tr><th>Código</th><th>Año / Folios</th><th>Estado físico</th><th>Ubicación actual</th><th>Destino previsto</th><th>Digitalización</th><th>Acciones</th></tr></thead><tbody>
        @forelse($libros as $libro)
            <tr>
                <td><strong>{{ $libro->codigo }}</strong><br><small class="text-muted">{{ $libro->titulo ?: 'Sin título' }}</small></td>
                <td>{{ $libro->anio_inicio ?: '—' }}@if($libro->anio_fin && $libro->anio_fin !== $libro->anio_inicio)–{{ $libro->anio_fin }}@endif<br><small>{{ $libro->folio_inicial ?: '?' }} a {{ $libro->folio_final ?: '?' }}</small></td>
                <td><span class="badge badge-{{ $libro->estado_fisico === 'almacenado' ? 'success' : ($libro->estado_fisico === 'en_consulta' ? 'warning' : 'secondary') }}">{{ str_replace('_',' ',ucfirst($libro->estado_fisico)) }}</span></td>
                <td>@if($libro->bloqueActual){{ $libro->bloqueActual->anaquel->area->nombre ?? '—' }}<br><strong>{{ $libro->bloqueActual->anaquel->codigo ?? '—' }} / {{ $libro->bloqueActual->codigo }} / P{{ $libro->posicion_actual }}</strong>@else{{ $libro->areaActual->nombre ?? 'Sin confirmar' }}@endif</td>
                <td>@if($libro->bloqueDestino){{ $libro->bloqueDestino->anaquel->codigo ?? '—' }} / {{ $libro->bloqueDestino->codigo }} / P{{ $libro->posicion_destino }}@else Sin asignar @endif</td>
                <td><span class="badge badge-{{ $libro->estado_digitalizacion === 'pendiente' ? 'secondary' : 'info' }}">{{ str_replace('_',' ',ucfirst($libro->estado_digitalizacion)) }}</span><br><small>{{ $libro->paginas_digitalizadas }} / {{ $libro->total_paginas ?: '?' }} páginas</small></td>
                <td class="text-nowrap"><a href="{{ route('archivo_siniestros.libros.show', $libro) }}" class="btn btn-info btn-sm"><i class="fa-regular fa-eye"></i> Ver</a>@can('gestionar archivo siniestros')<a href="{{ route('archivo_siniestros.libros.edit', $libro) }}" class="btn btn-success btn-sm"><i class="fa-solid fa-pen-to-square"></i></a>@endcan</td>
            </tr>
        @empty<tr><td colspan="7" class="text-center py-4">No se encontraron libros con esos filtros.</td></tr>@endforelse
        </tbody></table></div>
    </div>
    <div class="card-footer">{{ $libros->links('pagination::bootstrap-4') }}</div>
</div>
@stop
