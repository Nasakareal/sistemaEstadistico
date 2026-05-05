@extends('adminlte::page')

@section('title', 'Control de Corte Delegaciones')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between flex-wrap">
        <div>
            <h1 class="mb-1">Control de Corte Delegaciones</h1>
            <div class="text-muted">
                Corte {{ $fechaCorte }} · {{ $inicio->format('Y-m-d H:i') }} a {{ $fin->format('Y-m-d H:i') }}
            </div>
        </div>
        <a href="{{ route('settings.estadisticas_delegaciones.index') }}" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card card-outline card-primary control-card">
        <div class="card-body">
            <form method="GET" action="{{ route('settings.estadisticas_delegaciones.control_hechos') }}" class="control-filters" autocomplete="off">
                <div>
                    <label for="fecha_corte">Corte</label>
                    <input type="date" name="fecha_corte" id="fecha_corte" class="form-control" value="{{ $fechaCorte }}">
                </div>

                <div>
                    <label for="estado">Vista</label>
                    <select name="estado" id="estado" class="form-control">
                        <option value="todos" {{ $estado === 'todos' ? 'selected' : '' }}>Todos</option>
                        <option value="contados" {{ $estado === 'contados' ? 'selected' : '' }}>Ya se contemplan</option>
                        <option value="falta_completar" {{ $estado === 'falta_completar' ? 'selected' : '' }}>Falta completar</option>
                        <option value="sin_estadistica" {{ $estado === 'sin_estadistica' ? 'selected' : '' }}>Sin estadística</option>
                    </select>
                </div>

                <div>
                    <label for="buscar">Buscar</label>
                    <input type="text" name="buscar" id="buscar" class="form-control" value="{{ $buscar }}" placeholder="ID, folio, municipio">
                </div>

                <div class="control-filters__actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    <a href="{{ route('settings.estadisticas_delegaciones.control_hechos') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-rotate-left"></i> Limpiar
                    </a>
                </div>
            </form>

            <div class="control-summary">
                <div class="control-summary__item">
                    <span>Total visible</span>
                    <strong>{{ $resumen['total'] }}</strong>
                </div>
                <div class="control-summary__item is-ok">
                    <span>Se contemplan</span>
                    <strong>{{ $resumen['contados'] }}</strong>
                </div>
                <div class="control-summary__item is-warn">
                    <span>Falta completar</span>
                    <strong>{{ $resumen['falta_completar'] }}</strong>
                </div>
                <div class="control-summary__item is-muted">
                    <span>Sin estadística</span>
                    <strong>{{ $resumen['sin_estadistica'] }}</strong>
                </div>
            </div>

            <div class="control-excel {{ $excel['existe'] ? 'is-ready' : 'is-missing' }}">
                <div>
                    <span class="control-excel__label">Excel del corte</span>
                    <strong>{{ $excel['archivo'] }}</strong>
                    @if ($excel['existe'])
                        <small>Generado {{ $excel['modificado']->format('Y-m-d H:i') }}</small>
                    @else
                        <small>Aún no hay archivo generado para esta fecha.</small>
                    @endif
                </div>

                @if ($excel['existe'])
                    <a href="{{ $excel['url_descarga'] }}" class="btn btn-success">
                        <i class="fa-solid fa-download"></i> Descargar Excel
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="card card-outline card-info">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 control-table">
                    <thead>
                        <tr>
                            <th>Hecho</th>
                            <th>Fecha del hecho</th>
                            <th>Delegación</th>
                            <th>Captura</th>
                            <th>Excel</th>
                            <th>Estadísticas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($hechos as $hecho)
                            @php
                                $meta = $hecho->control_delegaciones;
                                $hoy = now('America/Mexico_City')->toDateString();
                                $manana = now('America/Mexico_City')->addDay()->toDateString();
                                $fechaHecho = $meta['evento_at'] ? $meta['evento_at']->format('Y-m-d H:i') : 'Sin fecha';
                                $corteActual = $meta['corte_actual'] ?: 'Sin corte';
                            @endphp
                            <tr>
                                <td class="control-main">
                                    <div class="font-weight-bold">#{{ $hecho->id }}</div>
                                    <div>{{ $hecho->tipo_hecho ?: 'Sin tipo' }}</div>
                                    <small>{{ $hecho->folio_c5i ?: 'Sin folio C5i' }}</small>
                                </td>
                                <td>
                                    <div>{{ $fechaHecho }}</div>
                                    <small>{{ trim(($hecho->calle ?: '') . ' ' . ($hecho->colonia ?: '')) }}</small>
                                </td>
                                <td>
                                    {{ optional($hecho->delegacion)->nombre_con_clave ?: optional($hecho->delegacion)->nombre ?: 'Sin delegación' }}
                                </td>
                                <td>
                                    @if ($meta['captura_completa'])
                                        <span class="badge badge-success">Completa</span>
                                    @else
                                        <span class="badge badge-danger">Falta completar</span>
                                        <div class="mt-1">
                                            @forelse ($meta['faltantes'] as $faltante)
                                                <span class="control-chip control-chip--danger">{{ $faltante }}</span>
                                            @empty
                                                <span class="control-chip control-chip--danger">Revisar captura</span>
                                            @endforelse
                                        </div>
                                    @endif
                                    <div class="control-small">Corte actual: {{ $corteActual }}</div>
                                </td>
                                <td>
                                    @if ($meta['se_contempla'])
                                        <span class="badge badge-success">Se contempla</span>
                                    @else
                                        <span class="badge badge-secondary">No entra</span>
                                    @endif
                                    @if ($meta['vehiculos_corralon'] > 0)
                                        <div class="control-small">{{ $meta['vehiculos_corralon'] }} en corralón</div>
                                    @endif
                                </td>
                                <td class="control-stats">
                                    @forelse ($meta['estadisticas'] as $estadistica)
                                        <span class="control-chip">{{ $estadistica }}</span>
                                    @empty
                                        <span class="text-muted">Sin estadística para este corte</span>
                                    @endforelse
                                </td>
                                <td class="control-actions">
                                    <a href="{{ route('hechos.show', $hecho) }}" class="btn btn-info btn-sm">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>
                                    <a href="{{ route('hechos.edit', $hecho) }}" class="btn btn-success btn-sm">
                                        <i class="fa-solid fa-pencil"></i>
                                    </a>

                                    <form method="POST" action="{{ route('settings.estadisticas_delegaciones.control_hechos.mover_corte', $hecho) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="fecha_corte" value="{{ $hoy }}">
                                        <button type="submit" class="btn btn-primary btn-sm" {{ $meta['captura_completa'] ? '' : 'disabled' }}>
                                            Hoy
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('settings.estadisticas_delegaciones.control_hechos.mover_corte', $hecho) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="fecha_corte" value="{{ $manana }}">
                                        <button type="submit" class="btn btn-warning btn-sm" {{ $meta['captura_completa'] ? '' : 'disabled' }}>
                                            Mañana
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No hay hechos para el corte seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $hechos->onEachSide(1)->links('pagination::bootstrap-4') }}
        </div>
    </div>
@stop

@section('css')
<style>
    .control-card,
    .control-table {
        color: rgba(255, 255, 255, .88);
    }

    .control-filters {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 12px;
        align-items: end;
    }

    .control-filters label {
        margin-bottom: 4px;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        color: rgba(255, 255, 255, .7);
    }

    .control-filters__actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .control-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        margin-top: 14px;
    }

    .control-summary__item {
        padding: 12px;
        border: 1px solid rgba(148, 163, 184, .2);
        border-radius: 8px;
        background: rgba(15, 23, 42, .4);
    }

    .control-summary__item span {
        display: block;
        color: rgba(255, 255, 255, .62);
        font-size: 12px;
        font-weight: 800;
    }

    .control-summary__item strong {
        display: block;
        margin-top: 4px;
        font-size: 24px;
    }

    .control-summary__item.is-ok strong { color: #4ade80; }
    .control-summary__item.is-warn strong { color: #facc15; }
    .control-summary__item.is-muted strong { color: #cbd5e1; }

    .control-excel {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 14px;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, .24);
        background: rgba(15, 23, 42, .42);
    }

    .control-excel.is-ready {
        border-color: rgba(74, 222, 128, .34);
    }

    .control-excel.is-missing {
        border-color: rgba(250, 204, 21, .34);
    }

    .control-excel__label,
    .control-excel small {
        display: block;
        color: rgba(255, 255, 255, .62);
        font-size: 12px;
        font-weight: 800;
    }

    .control-excel strong {
        display: block;
        color: rgba(255, 255, 255, .92);
        font-size: 16px;
    }

    .control-table th,
    .control-table td {
        vertical-align: middle !important;
        border-color: rgba(148, 163, 184, .18) !important;
    }

    .control-table thead th {
        color: #f8fafc;
        background: rgba(15, 23, 42, .9);
        text-transform: uppercase;
        font-size: 12px;
    }

    .control-table tbody td {
        background: rgba(15, 23, 42, .35);
    }

    .control-main {
        min-width: 150px;
    }

    .control-main small,
    .control-small {
        display: block;
        margin-top: 4px;
        color: rgba(255, 255, 255, .58);
        font-size: 12px;
    }

    .control-stats {
        min-width: 260px;
        max-width: 420px;
    }

    .control-chip {
        display: inline-flex;
        align-items: center;
        margin: 2px;
        padding: 4px 7px;
        border-radius: 6px;
        color: #dbeafe;
        background: rgba(37, 99, 235, .22);
        border: 1px solid rgba(96, 165, 250, .26);
        font-size: 12px;
        font-weight: 700;
    }

    .control-chip--danger {
        color: #fee2e2;
        background: rgba(220, 38, 38, .22);
        border-color: rgba(248, 113, 113, .28);
    }

    .control-actions {
        min-width: 210px;
        white-space: nowrap;
    }
</style>
@stop
