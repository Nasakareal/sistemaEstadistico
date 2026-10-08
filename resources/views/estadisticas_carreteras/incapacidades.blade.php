@extends('adminlte::page')

@section('title', 'Incapacidades del personal')

@section('content_header')
<div class="sv-report-hero incapacity">
    <div>
        <div class="sv-report-kicker"><i class="fa-solid fa-notes-medical"></i> Seguimiento de incidencias</div>
        <h1>Incapacidades del personal</h1>
        <p>Frecuencia y días acumulados del personal adscrito a Protección a Carreteras</p>
    </div>
    <div class="sv-report-actions">
        <a href="{{ route('estadisticas_carreteras.concentrado', request()->query()) }}" class="btn"><i class="fa-solid fa-table-cells-large"></i> Concentrado</a>
        <a href="{{ route('estadisticas_carreteras.index') }}" class="btn"><i class="fa-solid fa-chart-line"></i> Panel</a>
        <a href="{{ route('estadisticas_carreteras.elementos', request()->query()) }}" class="btn"><i class="fa-solid fa-ranking-star"></i> Elementos</a>
        <a href="{{ route('estadisticas_carreteras.rendimiento', request()->query()) }}" class="btn"><i class="fa-solid fa-award"></i> Rendimiento</a>
        <a href="{{ route('estadisticas_carreteras.incapacidades', request()->query()) }}" class="btn active"><i class="fa-solid fa-notes-medical"></i> Incapacidades</a>
        <button type="button" class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir</button>
    </div>
</div>
@stop

@section('content')
<div class="sv-report-panel sv-report-filters">
    <form method="GET" action="{{ route('estadisticas_carreteras.incapacidades') }}">
        <div class="row align-items-end">
            <div class="col-lg-2 col-md-6 mb-2"><label>Desde</label><input type="date" name="desde" value="{{ $filtros['desde'] }}" class="form-control" required></div>
            <div class="col-lg-2 col-md-6 mb-2"><label>Hasta</label><input type="date" name="hasta" value="{{ $filtros['hasta'] }}" class="form-control" required></div>
            <div class="col-lg-3 col-md-6 mb-2"><label>Destacamento</label><select name="destacamento_id" class="form-control"><option value="">Todos</option>@foreach($destacamentos as $destacamento)<option value="{{ $destacamento->id }}" {{ $filtros['destacamento_id'] === (int) $destacamento->id ? 'selected' : '' }}>{{ $destacamento->nombre }}</option>@endforeach</select></div>
            <div class="col-lg-3 col-md-6 mb-2"><label>Buscar elemento</label><input type="search" name="q" value="{{ $filtros['q'] }}" class="form-control" placeholder="Nombre, empleado o placa"></div>
            <div class="col-lg-2 col-md-12 mb-2 d-flex"><button class="btn sv-report-primary flex-fill"><i class="fa-solid fa-filter"></i> Actualizar</button><a href="{{ route('estadisticas_carreteras.incapacidades') }}" class="btn sv-report-clear ml-2" title="Restablecer"><i class="fa-solid fa-rotate-left"></i></a></div>
        </div>
    </form>
</div>

<div class="row sv-report-kpis">
    <div class="col-xl-3 col-sm-6"><div class="sv-report-kpi cyan"><span><i class="fa-solid fa-users"></i></span><div><small>Personal con incapacidad</small><strong>{{ number_format($totales['personal']) }}</strong></div></div></div>
    <div class="col-xl-3 col-sm-6"><div class="sv-report-kpi violet"><span><i class="fa-solid fa-file-medical"></i></span><div><small>Periodos registrados</small><strong>{{ number_format($totales['incapacidades']) }}</strong></div></div></div>
    <div class="col-xl-3 col-sm-6"><div class="sv-report-kpi amber"><span><i class="fa-solid fa-calendar-days"></i></span><div><small>Días acumulados</small><strong>{{ number_format($totales['dias']) }}</strong></div></div></div>
    <div class="col-xl-3 col-sm-6"><div class="sv-report-kpi green"><span><i class="fa-solid fa-hourglass-half"></i></span><div><small>Sin fecha de fin</small><strong>{{ number_format($totales['sin_fecha_fin']) }}</strong></div></div></div>
</div>

<div class="sv-report-panel">
    <div class="sv-report-panel-title">
        <div><span>Ranking por días acumulados</span><small>{{ $ranking->count() }} elementos con incapacidad dentro del periodo consultado</small></div>
        <span class="sv-report-cut">{{ \Carbon\Carbon::parse($filtros['desde'])->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($filtros['hasta'])->format('d/m/Y') }}</span>
    </div>
    <div class="table-responsive">
        <table class="table sv-report-table sv-incapacity-table mb-0">
            <thead><tr><th class="text-center">Pos.</th><th>Destacamento</th><th>Elemento</th><th class="text-center">Periodos</th><th class="text-center">Días acumulados</th><th class="text-center">Promedio</th><th class="text-center">Periodo más largo</th><th>Último periodo</th><th>Proporción</th><th class="text-center">Expediente</th></tr></thead>
            <tbody>
                @forelse($ranking as $persona)
                <tr class="{{ $persona['posicion'] <= 3 ? 'top-row' : '' }}">
                    <td class="text-center"><span class="sv-rank rank-{{ $persona['posicion'] }}">{{ $persona['posicion'] }}</span></td>
                    <td><span class="sv-detachment">{{ $persona['destacamento'] }}</span></td>
                    <td><strong>{{ $persona['nombre'] }}</strong><small class="d-block text-muted">Empleado: {{ $persona['numero_empleado'] ?: 'N/D' }} · Placa: {{ $persona['numero_placa'] ?: 'N/D' }} · {{ $persona['estatus'] }}</small></td>
                    <td class="metric">{{ number_format($persona['incapacidades']) }}@if($persona['sin_fecha_fin'] > 0)<span class="sv-open-period" title="Periodos sin fecha de fin">{{ $persona['sin_fecha_fin'] }} abierto(s)</span>@endif</td>
                    <td class="metric emph">{{ number_format($persona['dias']) }}</td>
                    <td class="metric">{{ number_format($persona['promedio_dias'], 1) }} días</td>
                    <td class="metric">{{ number_format($persona['periodo_maximo']) }} días</td>
                    <td><strong>{{ optional($persona['ultima_inicio'])->format('d/m/Y') }}</strong><small class="d-block text-muted">a {{ $persona['ultima_fin'] ? $persona['ultima_fin']->format('d/m/Y') : 'sin fecha de fin' }}</small></td>
                    <td><div class="sv-report-bar amber"><span style="width: {{ ($persona['dias'] / $maxDias) * 100 }}%"></span></div></td>
                    <td class="text-center">@can('ver personal')<a href="{{ route('personal.show', $persona['personal_id']) }}" class="btn btn-sm sv-record-link" title="Ver expediente"><i class="fa-solid fa-address-card"></i></a>@else<span class="text-muted">—</span>@endcan</td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center py-5 text-muted">No hay incapacidades registradas para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<p class="sv-report-note"><i class="fa-solid fa-circle-info"></i> Los días se cuentan de forma inclusiva y solo dentro del periodo seleccionado. Una incapacidad sin fecha de fin se calcula hasta hoy o hasta el corte consultado, lo que ocurra primero. Este reporte identifica recurrencias para revisión administrativa; por sí solo no determina irregularidades ni sustituye la valoración médica o el debido proceso.</p>
@stop

@section('css')<link rel="stylesheet" href="{{ asset('css/sv-carreteras-reportes.css') }}">@stop
