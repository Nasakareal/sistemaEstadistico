@extends('adminlte::page')

@section('title', 'Conduce con Legalidad')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:12px;">
        <div>
            <h1 class="mb-1">Conduce con Legalidad</h1>
            <div class="text-muted">Operativos visibles con el mismo alcance de la aplicación móvil.</div>
        </div>
        <div class="d-flex" style="gap:8px;">
            <span class="badge badge-primary p-2">{{ (int) ($resumen['operativos'] ?? 0) }} operativos</span>
            <span class="badge badge-info p-2">{{ (int) ($resumen['registros'] ?? 0) }} registros</span>
        </div>
    </div>
@stop

@section('content')
    <div class="card card-outline card-primary mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('conduce_legalidad.index') }}">
                <div class="row align-items-end">
                    <div class="col-md-6 mb-2">
                        <label for="buscar">Buscar</label>
                        <input id="buscar" name="buscar" class="form-control" value="{{ request('buscar') }}" placeholder="Lugar, municipio, colonia o número">
                    </div>
                    @if(!empty($abilities['can_create_operativo']))
                        <div class="col-md-4 mb-2">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado" class="form-control">
                                <option value="">Activos</option>
                                @foreach(['activo' => 'Activo', 'cerrado' => 'Cerrado', 'cancelado' => 'Cancelado', 'standby' => 'Standby'] as $value => $label)
                                    <option value="{{ $value }}" @selected(request('estado') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-2 mb-2 d-flex" style="gap:6px;">
                        <button class="btn btn-primary flex-fill"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
                        <a href="{{ route('conduce_legalidad.index') }}" class="btn btn-secondary" title="Limpiar filtros"><i class="fa-solid fa-rotate-left"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        @forelse($operativos as $operativo)
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card h-100 operativo-card card-outline {{ ($operativo['estado'] ?? '') === 'activo' ? 'card-success' : 'card-secondary' }}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>{{ $operativo['nombre'] ?? 'Conduce con Legalidad' }}</strong>
                        <span class="badge {{ ($operativo['estado'] ?? '') === 'activo' ? 'badge-success' : 'badge-secondary' }}">
                            {{ strtoupper($operativo['estado'] ?? 'N/D') }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="folio mb-2">Operativo #{{ $operativo['id'] }}</div>
                        <p class="mb-1"><i class="fa-regular fa-calendar"></i> {{ !empty($operativo['fecha']) ? \Carbon\Carbon::parse($operativo['fecha'])->format('d/m/Y') : 'Sin fecha' }} · {{ substr((string) ($operativo['hora_inicio'] ?? ''), 0, 5) }}</p>
                        <p class="mb-1"><i class="fa-solid fa-location-dot"></i> {{ collect([$operativo['lugar'] ?? null, $operativo['colonia'] ?? null, $operativo['municipio'] ?? null])->filter()->implode(', ') ?: 'Ubicación no especificada' }}</p>
                        <p class="mb-0"><i class="fa-solid fa-clipboard-list"></i> {{ (int) ($operativo['total_capturas'] ?? 0) }} registros visibles</p>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('conduce_legalidad.show', $operativo['id']) }}" class="btn btn-primary btn-block">
                            <i class="fa-solid fa-eye"></i> Ver operativo
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info">No hay operativos de Conduce con Legalidad que coincidan con los filtros.</div></div>
        @endforelse
    </div>

    @if(($pagination['last_page'] ?? 1) > 1)
        <nav aria-label="Paginación">
            <ul class="pagination justify-content-center">
                @for($page = 1; $page <= (int) $pagination['last_page']; $page++)
                    <li class="page-item {{ $page === (int) ($pagination['current_page'] ?? 1) ? 'active' : '' }}">
                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $page]) }}">{{ $page }}</a>
                    </li>
                @endfor
            </ul>
        </nav>
    @endif
@stop

@section('css')
<style>
    .operativo-card { border-radius: 12px; overflow: hidden; }
    .operativo-card .folio { font-size: 1.1rem; font-weight: 800; }
    .operativo-card p { color: rgba(234,240,255,.78); }
    .operativo-card p i { width: 20px; color: #60a5fa; }
</style>
@stop
