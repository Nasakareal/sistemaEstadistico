@extends('adminlte::page')

@section('title', 'Conduce con Legalidad')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:12px;">
        <div>
            <h1 class="mb-1">Conduce con Legalidad</h1>
            <div class="text-muted">Resumen de los operativos visibles para tu unidad.</div>
        </div>
    </div>
@stop

@section('content')
    <div class="row conduce-kpis">
        <div class="col-xl-3 col-sm-6 mb-3">
            <div class="info-box h-100 conduce-kpi conduce-kpi--operativos">
                <span class="info-box-icon"><i class="fa-solid fa-road-barrier"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Operativos</span>
                    <span class="info-box-number">{{ number_format((int) ($resumen['operativos'] ?? 0)) }}</span>
                    <small>Con los filtros actuales</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3">
            <div class="info-box h-100 conduce-kpi conduce-kpi--registros">
                <span class="info-box-icon"><i class="fa-solid fa-clipboard-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Alimentaciones</span>
                    <span class="info-box-number">{{ number_format((int) ($resumen['registros'] ?? 0)) }}</span>
                    <small>Registros capturados</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3">
            <div class="info-box h-100 conduce-kpi conduce-kpi--personas">
                <span class="info-box-icon"><i class="fa-solid fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Personas</span>
                    <span class="info-box-number">{{ number_format((int) ($resumen['personas'] ?? 0)) }}</span>
                    <small>Personas registradas</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3">
            <div class="info-box h-100 conduce-kpi conduce-kpi--vehiculos">
                <span class="info-box-icon"><i class="fa-solid fa-motorcycle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Vehículos</span>
                    <span class="info-box-number">{{ number_format((int) ($resumen['vehiculos'] ?? 0)) }}</span>
                    <small>Vehículos registrados</small>
                </div>
            </div>
        </div>
    </div>

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
    .conduce-kpi { border-radius: 14px; overflow: hidden; background: rgba(15, 23, 42, .72); border: 1px solid rgba(148, 163, 184, .18); box-shadow: 0 12px 28px rgba(2, 6, 23, .18); }
    .conduce-kpi .info-box-icon { width: 72px; color: #fff; font-size: 1.55rem; }
    .conduce-kpi .info-box-text { color: rgba(226, 232, 240, .72); font-weight: 700; letter-spacing: .04em; text-transform: uppercase; font-size: .76rem; }
    .conduce-kpi .info-box-number { color: #f8fafc; font-size: 1.75rem; line-height: 1.15; }
    .conduce-kpi small { color: rgba(203, 213, 225, .68); }
    .conduce-kpi--operativos .info-box-icon { background: linear-gradient(145deg, #2563eb, #1d4ed8); }
    .conduce-kpi--registros .info-box-icon { background: linear-gradient(145deg, #0891b2, #0e7490); }
    .conduce-kpi--personas .info-box-icon { background: linear-gradient(145deg, #7c3aed, #6d28d9); }
    .conduce-kpi--vehiculos .info-box-icon { background: linear-gradient(145deg, #059669, #047857); }
    .operativo-card { border-radius: 12px; overflow: hidden; }
    .operativo-card .folio { font-size: 1.1rem; font-weight: 800; }
    .operativo-card p { color: rgba(234,240,255,.78); }
    .operativo-card p i { width: 20px; color: #60a5fa; }
</style>
@stop
