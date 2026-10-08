@extends('adminlte::page')

@section('title', 'Concentrado de Carreteras')

@section('content_header')
<div class="sv-report-hero">
    <div>
        <div class="sv-report-kicker"><i class="fa-solid fa-road"></i> Protección a Carreteras</div>
        <h1>Concentrado ejecutivo</h1>
        <p>Resultados por destacamento del {{ \Carbon\Carbon::parse($filtros['desde'])->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($filtros['hasta'])->format('d/m/Y') }}</p>
    </div>
    <div class="sv-report-actions">
        <a href="{{ route('estadisticas_carreteras.concentrado', request()->query()) }}" class="btn active"><i class="fa-solid fa-table-cells-large"></i> Concentrado</a>
        <a href="{{ route('estadisticas_carreteras.index') }}" class="btn"><i class="fa-solid fa-chart-line"></i> Panel</a>
        <a href="{{ route('estadisticas_carreteras.elementos', request()->query()) }}" class="btn"><i class="fa-solid fa-ranking-star"></i> Elementos</a>
        <a href="{{ route('estadisticas_carreteras.rendimiento', request()->query()) }}" class="btn"><i class="fa-solid fa-award"></i> Rendimiento</a>
        <a href="{{ route('estadisticas_carreteras.incapacidades', request()->query()) }}" class="btn"><i class="fa-solid fa-notes-medical"></i> Incapacidades</a>
        <button type="button" class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir</button>
    </div>
</div>
@stop

@section('content')
<div class="sv-report-panel sv-report-filters">
    <form method="GET" action="{{ route('estadisticas_carreteras.concentrado') }}">
        <div class="row align-items-end">
            <div class="col-lg-3 col-md-6 mb-2"><label>Desde</label><input type="date" name="desde" value="{{ $filtros['desde'] }}" class="form-control"></div>
            <div class="col-lg-3 col-md-6 mb-2"><label>Hasta</label><input type="date" name="hasta" value="{{ $filtros['hasta'] }}" class="form-control"></div>
            <div class="col-lg-3 col-md-6 mb-2">
                <label>Destacamento</label>
                <select name="destacamento_id" class="form-control">
                    <option value="">Todos los destacamentos</option>
                    @foreach($destacamentos as $destacamento)
                        <option value="{{ $destacamento->id }}" {{ $filtros['destacamento_id'] === (int)$destacamento->id ? 'selected' : '' }}>{{ $destacamento->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 col-md-6 mb-2 d-flex">
                <button class="btn sv-report-primary flex-fill"><i class="fa-solid fa-filter"></i> Actualizar</button>
                <a href="{{ route('estadisticas_carreteras.concentrado') }}" class="btn sv-report-clear ml-2" title="Restablecer"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </div>
    </form>
</div>

<div class="row sv-report-kpis">
    <div class="col-xl-3 col-md-6"><div class="sv-report-kpi cyan"><span><i class="fa-solid fa-file-shield"></i></span><div><small>Puestas a disposición</small><strong>{{ number_format($totales['puestas']) }}</strong></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="sv-report-kpi violet"><span><i class="fa-solid fa-boxes-stacked"></i></span><div><small>Aseguramientos reportados</small><strong>{{ number_format($totales['aseguramientos']) }}</strong></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="sv-report-kpi amber"><span><i class="fa-solid fa-handcuffs"></i></span><div><small>Detenciones y faltas</small><strong>{{ number_format($totales['detenciones']) }}</strong></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="sv-report-kpi green"><span><i class="fa-solid fa-location-dot"></i></span><div><small>Destacamentos con actividad</small><strong>{{ number_format($filas->count()) }}</strong></div></div></div>
</div>

@if($lider)
<div class="sv-report-spotlight">
    <div class="sv-report-spotlight-icon"><i class="fa-solid fa-trophy"></i></div>
    <div><small>Mayor actividad en el periodo</small><h2>{{ $lider->destacamento }}</h2><p>{{ $lider->puestas }} puestas · {{ $lider->aseguramientos }} aseguramientos · {{ $lider->detenciones }} detenciones y faltas</p></div>
    <div class="sv-report-spotlight-number">{{ $totales['puestas'] ? number_format(($lider->puestas / $totales['puestas']) * 100, 1) : 0 }}<small>% del total</small></div>
</div>
@endif

<div class="sv-report-panel">
    <div class="sv-report-panel-title"><div><span>Detalle territorial</span><small>Comparativo por destacamento</small></div><span class="sv-report-cut">Corte: {{ \Carbon\Carbon::parse($filtros['hasta'])->format('d/m/Y') }}</span></div>
    <div class="table-responsive">
        <table class="table sv-report-table mb-0">
            <thead>
                <tr>
                    <th rowspan="2">Destacamento</th><th rowspan="2" class="text-center">Puestas</th><th rowspan="2" class="text-center">Aseguramientos</th>
                    <th colspan="3" class="text-center group amber">Detenciones</th><th colspan="3" class="text-center group cyan">Distribución por sexo</th><th rowspan="2">Participación</th>
                </tr>
                <tr><th class="text-center">Falta adm.</th><th class="text-center">Pres. delito M.P.</th><th class="text-center">Total</th><th class="text-center">Hombres</th><th class="text-center">Mujeres</th><th class="text-center">N/E</th></tr>
            </thead>
            <tbody>
                @forelse($filas as $fila)
                <tr>
                    <td><strong>{{ $fila->destacamento }}</strong></td><td class="metric">{{ $fila->puestas }}</td><td class="metric">{{ $fila->aseguramientos }}</td>
                    <td class="metric">{{ $fila->faltas }}</td><td class="metric">{{ $fila->delitos }}</td><td class="metric emph">{{ $fila->detenciones }}</td>
                    <td class="metric">{{ $fila->hombres }}</td><td class="metric">{{ $fila->mujeres }}</td><td class="metric">{{ $fila->no_especificado }}</td>
                    <td><div class="sv-report-bar"><span style="width: {{ ($fila->puestas / $maxPuestas) * 100 }}%"></span></div><small>{{ $totales['puestas'] ? number_format(($fila->puestas / $totales['puestas']) * 100, 1) : 0 }}%</small></td>
                </tr>
                @empty<tr><td colspan="10" class="text-center py-5 text-muted">No hay registros para el periodo seleccionado.</td></tr>@endforelse
            </tbody>
            @if($filas->isNotEmpty())
            <tfoot><tr><th>Total</th><th>{{ $totales['puestas'] }}</th><th>{{ $totales['aseguramientos'] }}</th><th>{{ $totales['faltas'] }}</th><th>{{ $totales['delitos'] }}</th><th>{{ $totales['detenciones'] }}</th><th>{{ $totales['hombres'] }}</th><th>{{ $totales['mujeres'] }}</th><th>{{ $totales['no_especificado'] }}</th><th>100%</th></tr></tfoot>
            @endif
        </table>
    </div>
</div>
<p class="sv-report-note"><i class="fa-solid fa-circle-info"></i> “N/E” identifica detenciones cuyo sexo no fue especificado en el registro de origen. Los aseguramientos corresponden al total reportado en cada puesta.</p>
@stop

@section('css')<link rel="stylesheet" href="{{ asset('css/sv-carreteras-reportes.css') }}">@stop
