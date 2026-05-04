@extends('adminlte::page')

@section('title', 'Informe de Seguridad Vial')

@section('content')
<div id="ppt-shell" class="svial-shell is-cover-active">
    <form class="svial-toolbar" id="svialFilters" autocomplete="off">
        <label class="svial-field">
            <span>Inicio</span>
            <input type="date" id="fecha_inicio" name="fecha_inicio" value="{{ $fechaInicio }}">
        </label>

        <label class="svial-field">
            <span>Fin</span>
            <input type="date" id="fecha_fin" name="fecha_fin" value="{{ $fechaFin }}">
        </label>

        <button type="submit" class="svial-action" aria-label="Actualizar informe">
            <i class="fa-solid fa-rotate-right"></i>
            <span>Actualizar</span>
        </button>
    </form>

    <div id="ppt-horizontal">
        <section class="ppt-slide ppt-slide--cover">
            @include('admin.settings.estadisticas_seguridad_vial.partials.caratula')
        </section>

        <section class="ppt-slide">
            @include('admin.settings.estadisticas_seguridad_vial.partials.comparativa_ciudades')
        </section>

        <section class="ppt-slide">
            <div class="ppt-card svial-summary-card">
                <div class="ppt-card__header svial-card-header">
                    <div>
                        <div class="ppt-eyebrow">Consolidado general</div>
                        <h2 class="ppt-title">Indicadores de seguridad vial</h2>
                        <div class="svial-period" id="svialSummaryPeriod">Periodo seleccionado</div>
                    </div>
                </div>

                <div class="svial-kpi-grid">
                    <div class="svial-kpi">
                        <div class="svial-kpi__icon svial-kpi__icon--blue">
                            <i class="fa-solid fa-car-burst"></i>
                        </div>
                        <div class="svial-kpi__value" id="kpi_total_hechos">0</div>
                        <div class="svial-kpi__label">Siniestros registrados</div>
                    </div>

                    <div class="svial-kpi">
                        <div class="svial-kpi__icon svial-kpi__icon--amber">
                            <i class="fa-solid fa-user-injured"></i>
                        </div>
                        <div class="svial-kpi__value" id="kpi_total_lesionados">0</div>
                        <div class="svial-kpi__label">Personas lesionadas</div>
                    </div>

                    <div class="svial-kpi">
                        <div class="svial-kpi__icon svial-kpi__icon--red">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div class="svial-kpi__value" id="kpi_total_fallecidos">0</div>
                        <div class="svial-kpi__label">Personas fallecidas</div>
                    </div>

                    <div class="svial-kpi">
                        <div class="svial-kpi__icon svial-kpi__icon--green">
                            <i class="fa-solid fa-car-side"></i>
                        </div>
                        <div class="svial-kpi__value" id="kpi_total_vehiculos">0</div>
                        <div class="svial-kpi__label">Vehículos involucrados</div>
                    </div>

                    <div class="svial-kpi">
                        <div class="svial-kpi__icon svial-kpi__icon--indigo">
                            <i class="fa-solid fa-map"></i>
                        </div>
                        <div class="svial-kpi__value" id="kpi_municipios">0</div>
                        <div class="svial-kpi__label">Municipios con hechos</div>
                    </div>

                    <div class="svial-kpi">
                        <div class="svial-kpi__icon svial-kpi__icon--slate">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div class="svial-kpi__value" id="kpi_promedio_diario">0</div>
                        <div class="svial-kpi__label">Promedio diario</div>
                    </div>
                </div>

                <div class="svial-highlight">
                    <div>
                        <span>Municipio con mayor incidencia</span>
                        <strong id="kpi_municipio_principal">SIN DATOS</strong>
                    </div>
                    <div class="svial-highlight__number" id="kpi_municipio_principal_total">0</div>
                </div>
            </div>
        </section>

        <section class="ppt-slide">
            <div class="ppt-card svial-analysis-card">
                <div class="ppt-card__header svial-card-header">
                    <div>
                        <div class="ppt-eyebrow">Lectura operativa</div>
                        <h2 class="ppt-title">Tipos, situación y horario</h2>
                        <div class="svial-period" id="svialAnalysisPeriod">Periodo seleccionado</div>
                    </div>
                </div>

                <div class="svial-analysis-grid">
                    <div class="svial-panel">
                        <div class="svial-panel-kicker">Top 10</div>
                        <h3>Siniestros por tipo</h3>
                        <div id="chart_tipo" class="svial-small-chart"></div>
                    </div>

                    <div class="svial-panel">
                        <div class="svial-panel-kicker">Estatus</div>
                        <h3>Situación reportada</h3>
                        <div id="chart_situacion" class="svial-small-chart"></div>
                    </div>

                    <div class="svial-panel svial-panel--wide">
                        <div class="svial-panel-kicker">Comportamiento diario</div>
                        <h3>Siniestros por hora</h3>
                        <div id="chart_hora" class="svial-wide-chart"></div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="ppt-nav">
        <button type="button" class="ppt-nav__btn" id="pptPrev" aria-label="Anterior">
            <i class="fas fa-chevron-left"></i>
        </button>

        <div class="ppt-indicators" id="pptIndicators"></div>

        <button type="button" class="ppt-nav__btn" id="pptNext" aria-label="Siguiente">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/resumen-ejecutivo-show.css') }}">
<link rel="stylesheet" href="{{ asset('css/estadisticas-seguridad-vial.css') }}">
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
const dataUrl = @json(route('estadisticas_seguridad_vial.data.comparativa_municipios'));
const pptShell = document.getElementById('ppt-shell');
const ppt = document.getElementById('ppt-horizontal');
const slides = Array.from(document.querySelectorAll('.ppt-slide'));
const btnPrev = document.getElementById('pptPrev');
const btnNext = document.getElementById('pptNext');
const indicatorsWrap = document.getElementById('pptIndicators');
const form = document.getElementById('svialFilters');
const inputInicio = document.getElementById('fecha_inicio');
const inputFin = document.getElementById('fecha_fin');
const numberFormat = new Intl.NumberFormat('es-MX');

let currentIndex = 0;
let chartMunicipios = null;
let chartTipo = null;
let chartSituacion = null;
let chartHora = null;

function buildIndicators() {
    indicatorsWrap.innerHTML = '';

    slides.forEach((_, index) => {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'ppt-indicator' + (index === 0 ? ' is-active' : '');
        dot.setAttribute('aria-label', `Ir a diapositiva ${index + 1}`);
        dot.addEventListener('click', () => goToSlide(index));
        indicatorsWrap.appendChild(dot);
    });
}

function updateIndicators(index) {
    indicatorsWrap.querySelectorAll('.ppt-indicator').forEach((dot, i) => {
        dot.classList.toggle('is-active', i === index);
    });

    pptShell?.classList.toggle('is-cover-active', index === 0);
}

function goToSlide(index) {
    if (!ppt || !slides.length) return;

    const safeIndex = Math.max(0, Math.min(index, slides.length - 1));
    currentIndex = safeIndex;

    ppt.scrollTo({
        left: safeIndex * ppt.clientWidth,
        behavior: 'smooth'
    });

    updateIndicators(safeIndex);
}

function detectCurrentSlide() {
    if (!ppt) return;

    const index = Math.round(ppt.scrollLeft / ppt.clientWidth);
    currentIndex = Math.max(0, Math.min(index, slides.length - 1));
    updateIndicators(currentIndex);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function setText(id, value) {
    const node = document.getElementById(id);
    if (node) node.textContent = value;
}

function destroyChart(chart) {
    if (chart) chart.destroy();
    return null;
}

function chartUnavailable(selector) {
    const node = document.querySelector(selector);
    if (node) {
        node.innerHTML = '<div class="svial-empty">No se pudo cargar la gráfica.</div>';
    }
}

function renderRanking(items) {
    const wrap = document.getElementById('svialRankingMunicipios');
    if (!wrap) return;

    if (!Array.isArray(items) || items.length === 0) {
        wrap.innerHTML = '<div class="svial-empty">No hay siniestros registrados para el periodo.</div>';
        return;
    }

    wrap.innerHTML = items.slice(0, 12).map((item, index) => `
        <div class="svial-rank-row">
            <div class="svial-rank-row__pos">${index + 1}</div>
            <div class="svial-rank-row__main">
                <strong>${escapeHtml(item.municipio)}</strong>
                <span>${numberFormat.format(item.lesionados ?? 0)} lesionados · ${numberFormat.format(item.vehiculos ?? 0)} vehículos</span>
            </div>
            <div class="svial-rank-row__metric">
                <strong>${numberFormat.format(item.hechos ?? 0)}</strong>
                <span>${Number(item.participacion ?? 0).toFixed(1)}%</span>
            </div>
        </div>
    `).join('');
}

function renderCharts(data) {
    chartMunicipios = destroyChart(chartMunicipios);
    chartTipo = destroyChart(chartTipo);
    chartSituacion = destroyChart(chartSituacion);
    chartHora = destroyChart(chartHora);

    if (typeof ApexCharts === 'undefined') {
        chartUnavailable('#chart_municipios');
        chartUnavailable('#chart_tipo');
        chartUnavailable('#chart_situacion');
        chartUnavailable('#chart_hora');
        return;
    }

    const palette = ['#1d4ed8', '#dc2626', '#d97706', '#059669', '#7c3aed', '#0f766e', '#334155', '#be123c', '#2563eb', '#ca8a04', '#16a34a', '#9333ea'];
    const municipiosLabels = data.graficas?.municipios?.labels ?? [];
    const municipiosSeries = data.graficas?.municipios?.series ?? [];

    chartMunicipios = new ApexCharts(document.querySelector('#chart_municipios'), {
        chart: { type: 'bar', height: 470, toolbar: { show: false }, animations: { easing: 'easeinout', speed: 450 } },
        series: [{ name: 'Siniestros', data: municipiosSeries }],
        xaxis: { categories: municipiosLabels, labels: { style: { colors: '#334155', fontWeight: 700 } } },
        yaxis: { labels: { style: { colors: '#334155', fontWeight: 700 } } },
        plotOptions: { bar: { horizontal: true, borderRadius: 7, barHeight: '68%', distributed: true } },
        colors: palette,
        dataLabels: { enabled: true, style: { fontWeight: 900 } },
        grid: { borderColor: 'rgba(148,163,184,.25)' },
        legend: { show: false }
    });
    chartMunicipios.render();

    chartTipo = new ApexCharts(document.querySelector('#chart_tipo'), {
        chart: { type: 'bar', height: 300, toolbar: { show: false }, animations: { easing: 'easeinout', speed: 450 } },
        series: [{ name: 'Siniestros', data: data.graficas?.por_tipo?.series ?? [] }],
        xaxis: { categories: data.graficas?.por_tipo?.labels ?? [], labels: { rotate: -20, trim: true } },
        plotOptions: { bar: { borderRadius: 6, columnWidth: '52%', distributed: true } },
        colors: palette,
        dataLabels: { enabled: false },
        grid: { borderColor: 'rgba(148,163,184,.22)' },
        legend: { show: false }
    });
    chartTipo.render();

    chartSituacion = new ApexCharts(document.querySelector('#chart_situacion'), {
        chart: { type: 'donut', height: 300, toolbar: { show: false }, animations: { easing: 'easeinout', speed: 450 } },
        series: data.graficas?.por_situacion?.series ?? [],
        labels: data.graficas?.por_situacion?.labels ?? [],
        colors: ['#059669', '#d97706', '#dc2626', '#1d4ed8', '#64748b'],
        legend: { position: 'bottom', fontWeight: 700 },
        dataLabels: { enabled: true },
        stroke: { colors: ['#ffffff'], width: 3 }
    });
    chartSituacion.render();

    chartHora = new ApexCharts(document.querySelector('#chart_hora'), {
        chart: { type: 'line', height: 250, toolbar: { show: false }, animations: { easing: 'easeinout', speed: 450 } },
        series: [{ name: 'Siniestros', data: data.graficas?.por_hora?.series ?? [] }],
        xaxis: { categories: data.graficas?.por_hora?.labels ?? [] },
        stroke: { curve: 'smooth', width: 4, colors: ['#1d4ed8'] },
        markers: { size: 4, colors: ['#dc2626'], strokeWidth: 2, strokeColors: '#ffffff' },
        dataLabels: { enabled: false },
        grid: { borderColor: 'rgba(148,163,184,.22)' },
        legend: { show: false }
    });
    chartHora.render();
}

function updateReport(data) {
    const kpis = data.kpis ?? {};
    const periodo = data.periodo ?? {};

    inputInicio.value = periodo.fecha_inicio ?? inputInicio.value;
    inputFin.value = periodo.fecha_fin ?? inputFin.value;

    setText('svialCoverPeriod', periodo.texto ?? 'Periodo seleccionado');
    setText('svialComparePeriod', periodo.texto ?? 'Periodo seleccionado');
    setText('svialSummaryPeriod', periodo.texto ?? 'Periodo seleccionado');
    setText('svialAnalysisPeriod', periodo.texto ?? 'Periodo seleccionado');
    setText('svialMunicipiosCount', numberFormat.format(kpis.municipios_con_hechos ?? 0));
    setText('svialTotalHechosCompare', numberFormat.format(kpis.total_hechos ?? 0));

    setText('kpi_total_hechos', numberFormat.format(kpis.total_hechos ?? 0));
    setText('kpi_total_lesionados', numberFormat.format(kpis.total_lesionados ?? 0));
    setText('kpi_total_fallecidos', numberFormat.format(kpis.total_fallecidos ?? 0));
    setText('kpi_total_vehiculos', numberFormat.format(kpis.total_vehiculos ?? 0));
    setText('kpi_municipios', numberFormat.format(kpis.municipios_con_hechos ?? 0));
    setText('kpi_promedio_diario', numberFormat.format(kpis.promedio_diario ?? 0));
    setText('kpi_municipio_principal', kpis.municipio_principal ?? 'SIN DATOS');
    setText('kpi_municipio_principal_total', numberFormat.format(kpis.municipio_principal_total ?? 0));

    renderRanking(data.ranking_municipios ?? []);
    renderCharts(data);
}

async function cargarReporte() {
    const params = new URLSearchParams({
        fecha_inicio: inputInicio.value,
        fecha_fin: inputFin.value
    });

    const res = await fetch(`${dataUrl}?${params.toString()}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
    }

    const data = await res.json();
    const url = new URL(window.location.href);
    url.search = params.toString();
    window.history.replaceState({}, '', url.toString());
    updateReport(data);
}

btnPrev?.addEventListener('click', () => goToSlide(currentIndex - 1));
btnNext?.addEventListener('click', () => goToSlide(currentIndex + 1));

document.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowRight') {
        e.preventDefault();
        goToSlide(currentIndex + 1);
    }

    if (e.key === 'ArrowLeft') {
        e.preventDefault();
        goToSlide(currentIndex - 1);
    }
});

ppt?.addEventListener('scroll', () => {
    window.clearTimeout(window.__svialScrollTimer);
    window.__svialScrollTimer = window.setTimeout(detectCurrentSlide, 60);
});

window.addEventListener('resize', () => goToSlide(currentIndex));

form?.addEventListener('submit', async (event) => {
    event.preventDefault();

    try {
        await cargarReporte();
        goToSlide(1);
    } catch (error) {
        console.error('Error cargando informe de seguridad vial:', error);
        document.getElementById('svialRankingMunicipios').innerHTML = '<div class="svial-empty svial-empty--danger">No se pudo cargar el informe.</div>';
    }
});

document.addEventListener('DOMContentLoaded', async () => {
    buildIndicators();

    try {
        await cargarReporte();
    } catch (error) {
        console.error('Error cargando informe de seguridad vial:', error);
        document.getElementById('svialRankingMunicipios').innerHTML = '<div class="svial-empty svial-empty--danger">No se pudo cargar el informe.</div>';
    }
});
</script>
@stop
