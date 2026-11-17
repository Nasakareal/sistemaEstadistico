@extends('adminlte::page')

@section('title', 'Anaqueles')

@section('content_header')
    <h1>Anaqueles del archivo</h1>
@stop

@section('content')
    @include('admin.settings.archivo_siniestros._nav')
    @include('admin.settings.archivo_siniestros._alerts')
    <div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title">Distribución de anaqueles</h3><div class="card-tools">@can('gestionar archivo siniestros')<a class="btn btn-primary btn-sm" href="{{ route('archivo_siniestros.anaqueles.create') }}"><i class="fa-solid fa-plus"></i> Nuevo anaquel</a>@endcan</div></div>
    <div class="card-body">
        <form class="form-inline mb-3" method="GET" action="{{ route('archivo_siniestros.anaqueles.index') }}"><label class="mr-2" for="area_id">Área</label><select id="area_id" name="area_id" class="form-control mr-2"><option value="">Todas</option>@foreach($areas as $area)<option value="{{ $area->id }}" {{ (string) request('area_id') === (string) $area->id ? 'selected' : '' }}>{{ $area->nombre }}</option>@endforeach</select><button class="btn btn-outline-primary">Filtrar</button></form>
        <div class="table-responsive"><table class="table table-striped table-bordered table-hover"><thead><tr><th>Código</th><th>Nombre</th><th>Área</th><th>Filas</th><th>Columnas</th><th>Bloques creados</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
        @forelse($anaqueles as $anaquel)
            <tr><td>{{ $anaquel->codigo }}</td><td>{{ $anaquel->nombre ?: '—' }}</td><td>{{ $anaquel->area->nombre ?? '—' }}</td><td>{{ $anaquel->total_filas }}</td><td>{{ $anaquel->total_columnas }}</td><td>{{ $anaquel->bloques_count }}</td><td><span class="badge badge-{{ $anaquel->activo ? 'success' : 'secondary' }}">{{ $anaquel->activo ? 'Activo' : 'Inactivo' }}</span></td><td class="text-nowrap">@can('gestionar archivo siniestros')<a href="{{ route('archivo_siniestros.anaqueles.edit', $anaquel) }}" class="btn btn-success btn-sm"><i class="fa-solid fa-pen-to-square"></i></a>@endcan <a href="{{ route('archivo_siniestros.bloques.index', ['anaquel_id' => $anaquel->id]) }}" class="btn btn-info btn-sm"><i class="fa-solid fa-table-cells"></i> Bloques</a></td></tr>
        @empty<tr><td colspan="8" class="text-center py-4">No hay anaqueles registrados.</td></tr>@endforelse
        </tbody></table></div>
    </div>
    <div class="card-footer">{{ $anaqueles->links('pagination::bootstrap-4') }}</div>
</div>
@stop
