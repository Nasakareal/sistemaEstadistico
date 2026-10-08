@extends('adminlte::page')

@section('title', 'Rendimiento Operativo de Carreteras')

@section('content_header')
@php
    $liderPrincipal = $porElemento->where('primer_respondiente', '>', 0)->sortByDesc('primer_respondiente')->first();
    $liderConstancia = $porElemento->where('dias_activos', '>', 0)->sortByDesc('dias_activos')->first();
    $destacamentoLider = $porDestacamento->first();
@endphp
<div class="rc-hero">
    <div class="rc-hero__copy">
        <span class="rc-eyebrow"><i class="fa-solid fa-road-circle-check"></i> Protección a Carreteras</span>
        <h1>Rendimiento operativo</h1>
        <p>Lectura integral de las puestas a disposición, la responsabilidad principal y la colaboración entre elementos y destacamentos.</p>
    </div>
    <div class="rc-hero__period">
        <span>Periodo analizado</span>
        <strong>{{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</strong>
        <small>{{ \Carbon\Carbon::parse($desde)->diffInDays(\Carbon\Carbon::parse($hasta)) + 1 }} días calendario</small>
    </div>
</div>
@stop

@section('content')
<div class="rc-toolbar">
    <form method="GET" action="{{ route('estadisticas_carreteras.rendimiento') }}" class="rc-filter">
        <div><label for="desde">Desde</label><input id="desde" type="date" name="desde" value="{{ $desde }}" class="form-control" required></div>
        <div><label for="hasta">Hasta</label><input id="hasta" type="date" name="hasta" value="{{ $hasta }}" class="form-control" required></div>
        <div class="rc-filter__wide"><label for="destacamento_id">Destacamento</label><select id="destacamento_id" name="destacamento_id" class="form-control"><option value="">Todos los destacamentos</option>@foreach($destacamentos as $destacamento)<option value="{{ $destacamento->id }}" @selected($destacamentoSeleccionado === (int) $destacamento->id)>{{ $destacamento->nombre }}</option>@endforeach</select></div>
        <button class="btn rc-btn" type="submit"><i class="fa-solid fa-filter"></i> Aplicar</button>
        <a href="{{ route('estadisticas_carreteras.rendimiento') }}" class="btn rc-btn rc-btn--ghost"><i class="fa-solid fa-rotate-left"></i> Limpiar</a>
    </form>
    <div class="rc-nav">
        <a href="{{ route('estadisticas_carreteras.concentrado', request()->query()) }}"><i class="fa-solid fa-table-cells-large"></i> Concentrado</a>
        <a href="{{ route('estadisticas_carreteras.index') }}"><i class="fa-solid fa-chart-line"></i> Panel</a>
        <a href="{{ route('estadisticas_carreteras.elementos', request()->query()) }}"><i class="fa-solid fa-ranking-star"></i> Elementos</a>
        <button type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir</button>
    </div>
</div>

<div class="rc-note">
    <i class="fa-solid fa-scale-balanced"></i>
    <div><strong>Comparación con contexto operativo.</strong> Cada elemento se compara únicamente con personal de su mismo destacamento. El índice considera responsabilidad como primer respondiente, ritmo de participación, constancia y variedad de actuaciones. Los aseguramientos y detenciones se muestran como resultados documentados, pero no aumentan la calificación porque dependen de la naturaleza del servicio.</div>
</div>

<div class="rc-kpis">
    <article><span class="is-blue"><i class="fa-solid fa-file-shield"></i></span><div><small>Puestas a disposición</small><strong>{{ number_format($kpis['puestas']) }}</strong><em>{{ number_format($kpis['promedio_diario'], 1) }} por día calendario</em></div></article>
    <article><span class="is-violet"><i class="fa-solid fa-boxes-stacked"></i></span><div><small>Aseguramientos</small><strong>{{ number_format($kpis['aseguramientos']) }}</strong><em>Resultado documentado</em></div></article>
    <article><span class="is-amber"><i class="fa-solid fa-handcuffs"></i></span><div><small>Detenciones y faltas</small><strong>{{ number_format($kpis['detenciones']) }}</strong><em>Resultado documentado</em></div></article>
    <article><span class="is-green"><i class="fa-solid fa-user-shield"></i></span><div><small>Elementos con actividad</small><strong>{{ number_format($kpis['elementos_activos']) }}</strong><em>Participación identificada</em></div></article>
    <article><span class="is-cyan"><i class="fa-solid fa-building-shield"></i></span><div><small>Destacamentos activos</small><strong>{{ number_format($kpis['destacamentos_activos']) }}</strong><em>Con al menos una puesta</em></div></article>
    <article><span class="is-rose"><i class="fa-solid fa-fingerprint"></i></span><div><small>Identificación del personal</small><strong>{{ number_format($kpis['cobertura_identificacion'], 1) }}%</strong><em>Puestas asociadas al padrón</em></div></article>
</div>

<div class="rc-highlights">
    <article class="rc-highlight rc-highlight--score"><small>Índice más alto con muestra válida</small><strong>{{ $lider?->nombre ?? 'Sin muestra suficiente' }}</strong><span>{{ $lider ? number_format($lider->calificacion, 1) . ' puntos · ' . $lider->destacamento : 'Se requieren más actuaciones comparables' }}</span></article>
    <article><small>Mayor responsabilidad principal</small><strong>{{ $liderPrincipal?->nombre ?? 'Sin actividad' }}</strong><span>{{ $liderPrincipal ? number_format($liderPrincipal->primer_respondiente) . ' puestas como primer respondiente' : 'No hay registros en el periodo' }}</span></article>
    <article><small>Mayor constancia registrada</small><strong>{{ $liderConstancia?->nombre ?? 'Sin actividad' }}</strong><span>{{ $liderConstancia ? number_format($liderConstancia->dias_activos) . ' días con participación' : 'No hay registros en el periodo' }}</span></article>
    <article><small>Destacamento con mayor actividad</small><strong>{{ $destacamentoLider?->nombre ?? 'Sin actividad' }}</strong><span>{{ $destacamentoLider ? number_format($destacamentoLider->puestas) . ' puestas a disposición' : 'No hay registros en el periodo' }}</span></article>
</div>

<div class="row">
    <div class="col-xl-8 col-12"><section class="rc-panel"><header><div><h2>Ritmo operativo diario</h2><p>Puestas a disposición registradas por fecha operativa.</p></div><span class="rc-chip">{{ number_format($kpis['puestas']) }} actuaciones</span></header><div class="rc-chart rc-chart--wide"><canvas id="chartDiario"></canvas></div></section></div>
    <div class="col-xl-4 col-12"><section class="rc-panel"><header><div><h2>Motivos principales</h2><p>Distribución de las actuaciones del periodo.</p></div></header><div class="rc-chart"><canvas id="chartMotivos"></canvas></div></section></div>
</div>

<section class="rc-panel">
    <header><div><h2>Comparativo por destacamento</h2><p>Volumen institucional y personal identificado con actividad.</p></div><span class="rc-chip">{{ $porDestacamento->count() }} con actividad</span></header>
    <div class="row align-items-center"><div class="col-xl-7"><div class="rc-chart"><canvas id="chartDestacamentos"></canvas></div></div><div class="col-xl-5"><div class="table-responsive"><table class="table rc-table rc-table--compact"><thead><tr><th>Destacamento</th><th>Puestas</th><th>Elementos</th><th>Aseg.</th><th>Det.</th></tr></thead><tbody>@forelse($porDestacamento as $fila)<tr><td><b>{{ $fila->nombre }}</b></td><td>{{ $fila->puestas }}</td><td>{{ $fila->elementos_activos }}</td><td>{{ $fila->aseguramientos }}</td><td>{{ $fila->detenciones }}</td></tr>@empty<tr><td colspan="5" class="rc-empty">Sin registros</td></tr>@endforelse</tbody></table></div></div></div>
</section>

<div class="rc-context">
    <div><span>Comparación con el periodo inmediato anterior</span><strong>@if($comparacion['variacion'] === null)Sin base comparable @else{{ $comparacion['variacion'] >= 0 ? '+' : '' }}{{ number_format($comparacion['variacion'], 1) }}% @endif</strong><small>{{ \Carbon\Carbon::parse($comparacion['desde'])->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($comparacion['hasta'])->format('d/m/Y') }} · {{ number_format($comparacion['total']) }} puestas</small></div>
    <div><span>Alcance del indicador</span><strong>Participación operativa</strong><small>No mide asistencia, disponibilidad, comisiones, incapacidades ni complejidad individual del servicio.</small></div>
</div>

<section class="rc-method">
    <div class="rc-method__lead"><span><i class="fa-solid fa-compass-drafting"></i></span><div><h2>Índice contextual de participación</h2><p>La mediana del destacamento equivale a 80 puntos; alcanzar 125% de esa referencia equivale a 100. Así se reconoce el desempeño sin comparar realidades territoriales distintas.</p></div></div>
    <div class="rc-method__grid">
        <div><b>Responsabilidad principal · 40%</b><span>Puestas como primer respondiente por día activo.</span></div>
        <div><b>Participación total · 30%</b><span>Intervenciones totales por día activo, como responsable o apoyo.</span></div>
        <div><b>Constancia · 20%</b><span>Número de días diferentes con participación registrada.</span></div>
        <div><b>Amplitud operativa · 10%</b><span>Variedad de motivos atendidos durante el periodo.</span></div>
    </div>
    <p><i class="fa-solid fa-circle-info"></i> Para obtener índice se requieren al menos {{ $minimoPuestas }} {{ $minimoPuestas === 1 ? 'puesta' : 'puestas' }}, {{ $minimoDias }} {{ $minimoDias === 1 ? 'día activo' : 'días activos' }} y tres compañeros comparables del mismo destacamento. “Sin muestra” y “sin pares” indican falta de base estadística, no bajo rendimiento.</p>
</section>

<section class="rc-panel">
    <header class="rc-ranking-head"><div><h2>Detalle por elemento</h2><p>Ordenado por índice contextual y responsabilidad principal.</p></div><label class="rc-search"><i class="fa-solid fa-magnifying-glass"></i><input id="buscarElemento" type="search" placeholder="Buscar elemento o destacamento…"></label></header>
    <div class="table-responsive">
        <table class="table rc-table rc-table--ranking" id="tablaRendimiento">
            <thead><tr><th>#</th><th>Elemento</th><th>Destacamento</th><th>Índice</th><th>Desglose</th><th>Puestas</th><th>Primer respondiente</th><th>Apoyo</th><th>Días activos</th><th>Prom./día</th><th>Aseg.</th><th>Det.</th><th>Motivos frecuentes</th><th>Última puesta</th><th>Expediente</th></tr></thead>
            <tbody>
                @forelse($porElemento as $indice => $fila)
                <tr>
                    <td class="rc-rank">{{ $indice + 1 }}</td>
                    <td><b>{{ $fila->nombre }}</b><small>@if($fila->id){{ trim(($fila->grado ? $fila->grado . ' · ' : '') . 'Empleado ' . ($fila->numero_empleado ?: 'N/D') . ' · Placa ' . ($fila->numero_placa ?: 'N/D')) }}@else{{ $fila->origen_identidad }}@endif</small></td>
                    <td><span class="rc-detachment">{{ $fila->destacamento }}</span></td>
                    <td>@if($fila->calificacion !== null)<span class="rc-score {{ $fila->calificacion >= 90 ? 'is-top' : ($fila->calificacion >= 65 ? 'is-solid' : 'is-developing') }}" style="--score: {{ $fila->calificacion }}"><b>{{ number_format($fila->calificacion, 1) }}</b><small>{{ $fila->nivel_calificacion }}</small></span>@else<span class="rc-score is-empty"><b>—</b><small>{{ $fila->nivel_calificacion }}</small></span>@endif</td>
                    <td class="rc-breakdown">@if($fila->calificacion !== null)<span>Principal <b>{{ $fila->score_principal }}</b></span><span>Participación <b>{{ $fila->score_participacion }}</b></span><span>Constancia <b>{{ $fila->score_constancia }}</b></span><span>Amplitud <b>{{ $fila->score_amplitud }}</b></span><small>{{ $fila->pares_comparables }} pares</small>@else<span>{{ $fila->motivo_sin_calificacion }}</span>@endif</td>
                    <td><b>{{ $fila->total }}</b></td><td>{{ $fila->primer_respondiente }}</td><td>{{ $fila->participaciones }}</td><td>{{ $fila->dias_activos }}</td><td>{{ number_format($fila->promedio_dia, 2) }}</td><td>{{ $fila->aseguramientos }}</td><td>{{ $fila->detenciones }}</td>
                    <td><small>{{ $fila->motivos_relevantes ?: 'Sin actividad registrada' }}</small></td><td>{{ $fila->ultima_puesta ? \Carbon\Carbon::parse($fila->ultima_puesta)->format('d/m/Y') : '—' }}</td>
                    <td>@if($fila->id)@can('ver personal')<a class="rc-file" href="{{ route('personal.show', $fila->id) }}" title="Ver expediente"><i class="fa-solid fa-address-card"></i></a>@else—@endcan @else<span class="text-muted" title="Aún no existe un expediente de personal vinculado">—</span>@endif</td>
                </tr>
                @empty<tr><td colspan="15" class="rc-empty">No hay personal disponible para los filtros seleccionados.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</section>

<p class="rc-footnote"><i class="fa-solid fa-circle-info"></i> La asociación se realiza por el nombre asentado como primer respondiente o personal participante y su coincidencia inequívoca con el padrón de Carreteras. Cuando existen homónimos o nombres no reconocidos, la puesta permanece en los totales institucionales pero no se atribuye automáticamente a una persona. Los resultados acreditados a cada participante no deben sumarse entre filas.</p>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/sv-rendimiento-carreteras.css') }}">
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    const diario = @json($diario);
    const destacamentos = @json($porDestacamento);
    const motivos = @json($motivos);
    const grid = 'rgba(148,163,184,.11)';
    const text = 'rgba(226,232,240,.72)';
    const baseScales = { x: { grid: { display: false }, ticks: { color: text } }, y: { beginAtZero: true, grid: { color: grid }, ticks: { color: text, precision: 0 } } };
    if (window.Chart) {
        new Chart(document.getElementById('chartDiario'), { type: 'line', data: { labels: diario.map(x => x.fecha), datasets: [{ label: 'Puestas', data: diario.map(x => x.total), borderColor: '#38bdf8', backgroundColor: 'rgba(56,189,248,.16)', fill: true, tension: .35, pointRadius: diario.length > 60 ? 0 : 3, pointBackgroundColor: '#f8fafc' }] }, options: { responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' }, plugins: { legend: { labels: { color: text, usePointStyle: true } } }, scales: baseScales } });
        new Chart(document.getElementById('chartMotivos'), { type: 'doughnut', data: { labels: motivos.map(x => x.motivo), datasets: [{ data: motivos.map(x => x.total), backgroundColor: ['#38bdf8','#8b5cf6','#f59e0b','#10b981','#fb7185','#22d3ee','#6366f1','#a3e635'], borderWidth: 0, hoverOffset: 5 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '66%', plugins: { legend: { position: 'bottom', labels: { color: text, usePointStyle: true, boxWidth: 8 } } } } });
        new Chart(document.getElementById('chartDestacamentos'), { type: 'bar', data: { labels: destacamentos.map(x => x.nombre), datasets: [{ label: 'Puestas', data: destacamentos.map(x => x.puestas), backgroundColor: destacamentos.map((_, i) => i === 0 ? '#fbbf24' : '#22d3ee'), borderRadius: 7, maxBarThickness: 34 }] }, options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ...baseScales.y }, y: { ...baseScales.x } } } });
    }
    const search = document.getElementById('buscarElemento');
    const rows = [...document.querySelectorAll('#tablaRendimiento tbody tr')];
    search?.addEventListener('input', () => { const term = search.value.trim().toLocaleLowerCase('es'); rows.forEach(row => row.hidden = !row.textContent.toLocaleLowerCase('es').includes(term)); });
})();
</script>
@stop
