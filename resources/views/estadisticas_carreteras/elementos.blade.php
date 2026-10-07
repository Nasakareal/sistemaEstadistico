@extends('adminlte::page')

@section('title', 'Puestas por elemento')

@section('content_header')
<div class="sv-report-hero elements">
    <div><div class="sv-report-kicker"><i class="fa-solid fa-ranking-star"></i> Desempeño operativo</div><h1>Puestas por elemento</h1><p>Primeros respondientes y personal participante · {{ $totalPuestas }} puestas analizadas</p></div>
    <div class="sv-report-actions">
        <a href="{{ route('estadisticas_carreteras.concentrado', request()->query()) }}" class="btn"><i class="fa-solid fa-table-cells-large"></i> Concentrado</a>
        <a href="{{ route('estadisticas_carreteras.index') }}" class="btn"><i class="fa-solid fa-chart-line"></i> Panel</a>
        <a href="{{ route('estadisticas_carreteras.elementos', request()->query()) }}" class="btn active"><i class="fa-solid fa-ranking-star"></i> Elementos</a>
        <button type="button" class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir</button>
    </div>
</div>
@stop

@section('content')
<div class="sv-report-panel sv-report-filters">
    <form method="GET" action="{{ route('estadisticas_carreteras.elementos') }}">
        <div class="row align-items-end">
            <div class="col-lg-2 col-md-6 mb-2"><label>Desde</label><input type="date" name="desde" value="{{ $filtros['desde'] }}" class="form-control"></div>
            <div class="col-lg-2 col-md-6 mb-2"><label>Hasta</label><input type="date" name="hasta" value="{{ $filtros['hasta'] }}" class="form-control"></div>
            <div class="col-lg-3 col-md-6 mb-2"><label>Destacamento</label><select name="destacamento_id" class="form-control"><option value="">Todos</option>@foreach($destacamentos as $destacamento)<option value="{{ $destacamento->id }}" {{ $filtros['destacamento_id'] === (int)$destacamento->id ? 'selected' : '' }}>{{ $destacamento->nombre }}</option>@endforeach</select></div>
            <div class="col-lg-3 col-md-6 mb-2"><label>Buscar elemento</label><input type="search" name="q" value="{{ $filtros['q'] }}" class="form-control" placeholder="Nombre o apellidos"></div>
            <div class="col-lg-2 col-md-12 mb-2 d-flex"><button class="btn sv-report-primary flex-fill"><i class="fa-solid fa-filter"></i> Actualizar</button><a href="{{ route('estadisticas_carreteras.elementos') }}" class="btn sv-report-clear ml-2"><i class="fa-solid fa-rotate-left"></i></a></div>
        </div>
    </form>
</div>

@if($podio->isNotEmpty())
<div class="sv-podium">
    @foreach($podio as $persona)
    <div class="sv-podium-card place-{{ $persona['posicion'] }}">
        <div class="sv-podium-place">{{ $persona['posicion'] }}</div>
        <div class="sv-podium-medal"><i class="fa-solid fa-medal"></i></div>
        <small>{{ $persona['destacamento'] }}</small><h3>{{ $persona['nombre'] }}</h3>
        <strong>{{ $persona['total'] }}</strong><span>puestas</span>
        <div class="sv-podium-breakdown"><b>{{ $persona['primer_respondiente'] }}</b> primer respondiente <i></i> <b>{{ $persona['participaciones'] }}</b> participaciones</div>
    </div>
    @endforeach
</div>
@endif

<div class="sv-report-panel">
    <div class="sv-report-panel-title"><div><span>Ranking operativo</span><small>{{ $ranking->count() }} elementos con participación registrada</small></div><span class="sv-report-cut">Corte: {{ \Carbon\Carbon::parse($filtros['hasta'])->format('d/m/Y') }}</span></div>
    <div class="table-responsive">
        <table class="table sv-report-table sv-elements-table mb-0">
            <thead><tr><th class="text-center">Pos.</th><th>Destacamento</th><th>Elemento</th><th class="text-center">Puestas del destacamento</th><th class="text-center">Primer respondiente</th><th class="text-center">Participaciones</th><th class="text-center">Total operativo</th><th>Actividad</th><th>Motivos frecuentes</th></tr></thead>
            <tbody>
                @forelse($ranking as $persona)
                <tr class="{{ $persona['posicion'] <= 3 ? 'top-row' : '' }}">
                    <td class="text-center"><span class="sv-rank rank-{{ $persona['posicion'] }}">{{ $persona['posicion'] }}</span></td>
                    <td><span class="sv-detachment">{{ $persona['destacamento'] }}</span></td><td><strong>{{ $persona['nombre'] }}</strong></td>
                    <td class="metric">{{ $persona['total_destacamento'] }}</td><td class="metric">{{ $persona['primer_respondiente'] }}</td><td class="metric">{{ $persona['participaciones'] }}</td><td class="metric emph">{{ $persona['total'] }}</td>
                    <td><div class="sv-report-bar green"><span style="width: {{ ($persona['total'] / $maxTotal) * 100 }}%"></span></div></td>
                    <td><small>{{ $persona['motivos_relevantes'] ?: 'Sin motivo especificado' }}</small></td>
                </tr>
                @empty<tr><td colspan="9" class="text-center py-5 text-muted">No hay elementos para los filtros seleccionados.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</div>
<p class="sv-report-note"><i class="fa-solid fa-circle-info"></i> El total operativo cuenta cada puesta una sola vez por elemento, aunque aparezca en más de un campo del mismo registro.</p>
@stop

@section('css')<link rel="stylesheet" href="{{ asset('css/sv-carreteras-reportes.css') }}">@stop
