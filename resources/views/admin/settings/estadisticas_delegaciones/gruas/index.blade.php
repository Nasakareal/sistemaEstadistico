@extends('adminlte::page')

@section('title', 'Grúas por Delegación')

@section('content_header')
    <div class="gruas-header">
        <div>
            <div class="gruas-kicker">Delegaciones · Grúas · Directorio</div>
            <h1>Grúas por Delegación</h1>
            <p>Relación operativa de las grúas asignadas a cada delegación.</p>
        </div>
        <div class="gruas-actions">
            <a href="{{ route('settings.estadisticas_delegaciones.index') }}" class="btn btn-outline-light">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="{{ route('settings.estadisticas_delegaciones.gruas.exportar', array_merge(request()->query(), ['formato' => 'excel'])) }}" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Excel
            </a>
            <a href="{{ route('settings.estadisticas_delegaciones.gruas.exportar', array_merge(request()->query(), ['formato' => 'pdf'])) }}" class="btn btn-danger">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
        </div>
    </div>
@stop

@section('content')
    <form method="GET" action="{{ route('settings.estadisticas_delegaciones.gruas') }}" class="gruas-filters" autocomplete="off">
        <div class="filter-search">
            <label for="buscar">Buscar</label>
            <input type="text" name="buscar" id="buscar" class="form-control" value="{{ $buscar }}" placeholder="Delegación, regional, grúa, domicilio o teléfono">
        </div>
        <label class="filter-check">
            <input type="checkbox" name="incluir_inactivas" value="1" {{ $incluirInactivas ? 'checked' : '' }}>
            <span>Incluir inactivas</span>
        </label>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Filtrar
        </button>
        <a href="{{ route('settings.estadisticas_delegaciones.gruas') }}" class="btn btn-secondary">
            <i class="fas fa-eraser"></i> Limpiar
        </a>
    </form>

    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="metric">
                <span>Delegaciones</span>
                <strong>{{ number_format($resumen['delegaciones']) }}</strong>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="metric">
                <span>Grúas únicas</span>
                <strong>{{ number_format($resumen['gruas_asignadas']) }}</strong>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="metric">
                <span>Asignaciones</span>
                <strong>{{ number_format($resumen['relaciones']) }}</strong>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="metric metric-warning">
                <span>Sin grúa</span>
                <strong>{{ number_format($resumen['sin_gruas']) }}</strong>
            </div>
        </div>
    </div>

    <div class="gruas-panel">
        <div class="table-responsive">
            <table class="table table-hover gruas-table">
                <thead>
                    <tr>
                        <th>Regional</th>
                        <th>Delegación</th>
                        <th>Municipio</th>
                        <th>Grúas asignadas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($delegaciones as $delegacion)
                        <tr>
                            <td>
                                <div class="text-strong">{{ $delegacion['regional'] ?: 'Sin regional' }}</div>
                            </td>
                            <td>
                                <div class="text-strong">{{ $delegacion['delegacion'] }}</div>
                                <div class="text-muted small">{{ $delegacion['clave'] ?: 'Sin clave' }}</div>
                                @unless ($delegacion['activa'])
                                    <span class="badge badge-secondary">Inactiva</span>
                                @endunless
                            </td>
                            <td>{{ $delegacion['municipio'] ?: '—' }}</td>
                            <td>
                                @if ($delegacion['gruas']->isEmpty())
                                    <span class="empty-state">Sin grúa asignada</span>
                                @else
                                    <div class="grua-list">
                                        @foreach ($delegacion['gruas'] as $grua)
                                            <div class="grua-item">
                                                <div class="grua-name">{{ $grua['nombre'] }}</div>
                                                <div class="grua-meta">
                                                    @if (!empty($grua['direccion']))
                                                        <span><i class="fas fa-location-dot"></i> {{ $grua['direccion'] }}</span>
                                                    @endif
                                                    @if (!empty($grua['telefono']))
                                                        <span><i class="fas fa-phone"></i> {{ $grua['telefono'] }}</span>
                                                    @endif
                                                    @if (!empty($grua['ubicacion_corralon']))
                                                        <span><i class="fas fa-warehouse"></i> {{ $grua['ubicacion_corralon'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4">No se encontraron delegaciones con esos filtros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('css')
<style>
    .gruas-header{
        display:flex;
        justify-content:space-between;
        gap:16px;
        align-items:flex-end;
        padding:18px;
        margin:10px 0 14px;
        border:1px solid rgba(255,255,255,.12);
        border-radius:18px;
        background:linear-gradient(135deg, rgba(31,78,121,.36), rgba(19,34,64,.52));
        box-shadow:0 16px 42px rgba(0,0,0,.28);
    }
    .gruas-kicker{
        color:rgba(234,240,255,.68);
        font-weight:800;
        font-size:12px;
        text-transform:uppercase;
    }
    .gruas-header h1{
        margin:4px 0;
        color:rgba(255,255,255,.95);
        font-weight:900;
        font-size:28px;
    }
    .gruas-header p{
        margin:0;
        color:rgba(234,240,255,.72);
        font-weight:650;
    }
    .gruas-actions{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        justify-content:flex-end;
    }
    .gruas-filters{
        display:flex;
        gap:10px;
        align-items:flex-end;
        flex-wrap:wrap;
        padding:14px;
        margin-bottom:14px;
        border:1px solid rgba(255,255,255,.10);
        border-radius:12px;
        background:rgba(255,255,255,.06);
    }
    .filter-search{
        flex:1 1 360px;
    }
    .filter-search label{
        color:rgba(234,240,255,.78);
        font-weight:800;
        font-size:12px;
    }
    .filter-check{
        display:flex;
        align-items:center;
        gap:8px;
        margin:0 6px 7px;
        color:rgba(234,240,255,.82);
        font-weight:750;
    }
    .metric{
        margin-bottom:14px;
        padding:14px;
        border-radius:12px;
        border:1px solid rgba(255,255,255,.10);
        background:rgba(255,255,255,.07);
    }
    .metric span{
        display:block;
        color:rgba(234,240,255,.68);
        font-size:12px;
        font-weight:800;
        text-transform:uppercase;
    }
    .metric strong{
        color:rgba(255,255,255,.96);
        font-size:26px;
        line-height:1.1;
    }
    .metric-warning strong{
        color:#ffd166;
    }
    .gruas-panel{
        border:1px solid rgba(255,255,255,.10);
        border-radius:14px;
        overflow:hidden;
        background:rgba(255,255,255,.06);
    }
    .gruas-table{
        margin-bottom:0;
        color:rgba(234,240,255,.90);
    }
    .gruas-table thead th{
        border-bottom:1px solid rgba(255,255,255,.14);
        color:rgba(255,255,255,.95);
        background:rgba(15,23,42,.62);
        font-size:12px;
        text-transform:uppercase;
        letter-spacing:.02em;
    }
    .gruas-table td{
        border-top:1px solid rgba(255,255,255,.08);
        vertical-align:top;
    }
    .text-strong{
        font-weight:900;
    }
    .grua-list{
        display:grid;
        gap:8px;
    }
    .grua-item{
        padding:9px 10px;
        border-radius:8px;
        background:rgba(255,255,255,.07);
        border:1px solid rgba(255,255,255,.08);
    }
    .grua-name{
        font-weight:900;
        color:rgba(255,255,255,.96);
    }
    .grua-meta{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        margin-top:4px;
        color:rgba(234,240,255,.68);
        font-size:12px;
        line-height:1.35;
    }
    .empty-state{
        color:#ffd166;
        font-weight:850;
    }
    @media (max-width: 768px){
        .gruas-header{
            align-items:flex-start;
            flex-direction:column;
        }
        .gruas-actions{
            justify-content:flex-start;
        }
    }
</style>
@stop
