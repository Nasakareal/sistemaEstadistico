@extends('adminlte::page')

@section('title', 'Bloques')

@section('content_header')
    <h1>Bloques y capacidades</h1>
@stop

@section('content')
    @include('admin.settings.archivo_siniestros._nav')
    @include('admin.settings.archivo_siniestros._alerts')
    <div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title">Espacios de almacenamiento</h3><div class="card-tools">@can('gestionar archivo siniestros')<a href="{{ route('archivo_siniestros.bloques.create', request()->only('anaquel_id')) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Nuevo bloque</a>@endcan</div></div>
    <div class="card-body">
        <form class="form-inline mb-3" method="GET" action="{{ route('archivo_siniestros.bloques.index') }}"><label class="mr-2" for="anaquel_id">Anaquel</label><select id="anaquel_id" name="anaquel_id" class="form-control mr-2"><option value="">Todos</option>@foreach($anaqueles as $anaquel)<option value="{{ $anaquel->id }}" {{ (string) request('anaquel_id') === (string) $anaquel->id ? 'selected' : '' }}>{{ $anaquel->area->nombre ?? 'Área' }} / {{ $anaquel->codigo }}</option>@endforeach</select><button class="btn btn-outline-primary">Filtrar</button></form>
        <div class="table-responsive"><table class="table table-striped table-hover table-bordered"><thead><tr><th>Anaquel</th><th>Bloque</th><th>Fila</th><th>Columna</th><th>Capacidad</th><th>Apilado</th><th>QR</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
        @forelse($bloques as $bloque)
            <tr><td>{{ $bloque->anaquel->codigo ?? '—' }}<br><small class="text-muted">{{ $bloque->anaquel->area->nombre ?? '' }}</small></td><td><strong>{{ $bloque->codigo }}</strong></td><td>{{ $bloque->fila }}</td><td>{{ $bloque->columna }}</td><td>{{ $bloque->capacidad_libros }} libros</td><td>{{ $bloque->orden_apilado === 'arriba_abajo' ? 'Arriba → abajo' : 'Abajo → arriba' }}</td><td><small class="text-break">{{ $bloque->qr_uuid ?? 'Sin QR' }}</small></td><td><span class="badge badge-{{ $bloque->activo ? 'success' : 'secondary' }}">{{ $bloque->activo ? 'Activo' : 'Inactivo' }}</span></td><td>@can('gestionar archivo siniestros')<a href="{{ route('archivo_siniestros.bloques.edit', $bloque) }}" class="btn btn-success btn-sm"><i class="fa-solid fa-pen-to-square"></i></a>@endcan</td></tr>
        @empty<tr><td colspan="9" class="text-center py-4">No hay bloques registrados. Cada bloque representa una posición dentro de la cuadrícula del anaquel.</td></tr>@endforelse
        </tbody></table></div>
    </div>
    <div class="card-footer">{{ $bloques->links('pagination::bootstrap-4') }}</div>
</div>
@stop
