@extends('adminlte::page')

@section('title', 'Áreas del archivo')

@section('content_header')
    <h1>Áreas del archivo de siniestros</h1>
@stop

@section('content')
    @include('admin.settings.archivo_siniestros._nav')
    @include('admin.settings.archivo_siniestros._alerts')
    <div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Salas y puntos de consulta</h3>
        <div class="card-tools">@can('gestionar archivo siniestros')<a href="{{ route('archivo_siniestros.areas.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Nueva área</a>@endcan</div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-striped table-hover table-bordered mb-0">
            <thead><tr><th>Código</th><th>Nombre</th><th>Tipo</th><th>Anaqueles</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            @forelse($areas as $area)
                <tr>
                    <td>{{ $area->codigo }}</td><td>{{ $area->nombre }}</td><td>{{ ucfirst($area->tipo) }}</td>
                    <td>{{ $area->anaqueles_count }}</td>
                    <td><span class="badge badge-{{ $area->activo ? 'success' : 'secondary' }}">{{ $area->activo ? 'Activa' : 'Inactiva' }}</span></td>
                    <td>@can('gestionar archivo siniestros')<a class="btn btn-success btn-sm" href="{{ route('archivo_siniestros.areas.edit', $area) }}"><i class="fa-solid fa-pen-to-square"></i> Editar</a>@endcan</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-4">Todavía no hay áreas. Registra la sala histórica y la mesa de liberaciones.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $areas->links('pagination::bootstrap-4') }}</div>
</div>
@stop
