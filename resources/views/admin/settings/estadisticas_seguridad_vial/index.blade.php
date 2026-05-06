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

        <button type="button" class="svial-action svial-action--screen" id="svialFullscreen" aria-label="Pantalla completa">
            <i class="fa-solid fa-expand"></i>
            <span>Pantalla</span>
        </button>

        <a class="svial-action svial-action--ppt" id="svialDownloadPpt" href="{{ route('estadisticas_seguridad_vial.powerpoint', ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]) }}">
            <i class="fa-solid fa-file-powerpoint"></i>
            <span>PowerPoint</span>
        </a>
    </form>

    <div id="ppt-horizontal">
        <section class="ppt-slide ppt-slide--cover">
            @include('admin.settings.estadisticas_seguridad_vial.partials.caratula')
        </section>

        <section class="ppt-slide">
            @include('admin.settings.estadisticas_seguridad_vial.partials.comparativa_ciudades')
        </section>

        <section class="ppt-slide">
            @include('admin.settings.estadisticas_seguridad_vial.partials.mapa_calor_morelia')
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
            <div class="ppt-card svial-temporal-card">
                <div class="ppt-card__header svial-card-header">
                    <div>
                        <div class="ppt-eyebrow">Lectura temporal</div>
                        <h2 class="ppt-title">Distribución semanal y horaria</h2>
                        <div class="svial-period" id="svialTemporalPeriod">Periodo seleccionado</div>
                    </div>
                </div>

                <div class="svial-insight-strip">
                    <div class="svial-insight">
                        <span>Día de la semana con mayor incidencia</span>
                        <strong id="kpi_dia_pico">SIN DATOS</strong>
                        <b id="kpi_dia_pico_total">0</b>
                    </div>

                    <div class="svial-insight">
                        <span>Hora crítica</span>
                        <strong id="kpi_hora_pico">SIN HORA</strong>
                        <b id="kpi_hora_pico_total">0</b>
                    </div>

                    <div class="svial-insight">
                        <span>Promedio diario</span>
                        <strong id="kpi_promedio_diario_temporal">0</strong>
                        <b>HECHOS</b>
                    </div>
                </div>

                <div class="svial-temporal-grid">
                    <div class="svial-panel">
                        <div class="svial-panel-kicker">Día de la semana</div>
                        <h3>Siniestros por día</h3>
                        <div id="chart_dia" class="svial-temporal-chart"></div>
                    </div>

                    <div class="svial-panel">
                        <div class="svial-panel-kicker">24 horas</div>
                        <h3>Siniestros por hora</h3>
                        <div id="chart_hora" class="svial-temporal-chart"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="ppt-slide">
            <div class="ppt-card svial-analysis-card">
                <div class="ppt-card__header svial-card-header">
                    <div>
                        <div class="ppt-eyebrow">Lectura operativa</div>
                        <h2 class="ppt-title">Tipo de siniestro y estatus</h2>
                        <div class="svial-period" id="svialAnalysisPeriod">Periodo seleccionado</div>
                    </div>
                </div>

                <div class="svial-operational-grid">
                    <div class="svial-panel svial-panel--type">
                        <div class="svial-panel-kicker">Top 10</div>
                        <h3>Siniestros por tipo</h3>
                        <div class="svial-panel-metric">
                            <span id="kpi_tipo_principal">SIN DATOS</span>
                            <strong id="kpi_tipo_principal_total">0</strong>
                        </div>
                        <div id="chart_tipo" class="svial-type-chart"></div>
                    </div>

                    <div class="svial-panel">
                        <div class="svial-panel-kicker">Estatus</div>
                        <h3>Situación reportada</h3>
                        <div id="chart_situacion" class="svial-donut-chart"></div>
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
@php
    $resumenCss = public_path('css/resumen-ejecutivo-show.css');
    $svialCss = public_path('css/estadisticas-seguridad-vial.css');
@endphp
<link rel="stylesheet" href="{{ asset('css/resumen-ejecutivo-show.css') }}?v={{ file_exists($resumenCss) ? filemtime($resumenCss) : time() }}">
<link rel="stylesheet" href="{{ asset('css/estadisticas-seguridad-vial.css') }}?v={{ file_exists($svialCss) ? filemtime($svialCss) : time() }}">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@stop

@section('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
const dataUrl = @json(route('estadisticas_seguridad_vial.data.comparativa_municipios'));
const heatmapUrl = @json(route('estadisticas_seguridad_vial.data.mapa_calor_morelia'));
const pptShell = document.getElementById('ppt-shell');
const ppt = document.getElementById('ppt-horizontal');
const slides = Array.from(document.querySelectorAll('.ppt-slide'));
const btnPrev = document.getElementById('pptPrev');
const btnNext = document.getElementById('pptNext');
const indicatorsWrap = document.getElementById('pptIndicators');
const form = document.getElementById('svialFilters');
const inputInicio = document.getElementById('fecha_inicio');
const inputFin = document.getElementById('fecha_fin');
const downloadPpt = document.getElementById('svialDownloadPpt');
const btnFullscreen = document.getElementById('svialFullscreen');
const pptDownloadUrl = @json(route('estadisticas_seguridad_vial.powerpoint'));
const numberFormat = new Intl.NumberFormat('es-MX');

let currentIndex = 0;
let chartMunicipios = null;
let chartTipo = null;
let chartSituacion = null;
let chartDia = null;
let chartHora = null;
let svialHeatMap = null;
let svialHeatMarkers = null;
let svialHeatLayers = {};

const heatLayerConfig = {
    fallecidos: {
        color: '#dc2626',
        gradient: { '0.25': '#fecaca', '0.58': '#ef4444', '1': '#7f1d1d' }
    },
    lesionados: {
        color: '#f59e0b',
        gradient: { '0.25': '#fde68a', '0.58': '#f59e0b', '1': '#b45309' }
    },
    choques: {
        color: '#2563eb',
        gradient: { '0.25': '#bfdbfe', '0.58': '#2563eb', '1': '#1e3a8a' }
    }
};

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
    setTimeout(() => svialHeatMap?.invalidateSize(), 160);
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

function shortChartLabel(value, max = 28) {
    const text = String(value ?? '').replace(/\s+/g, ' ').trim();

    if (text.length <= max) {
        return text;
    }

    return `${text.slice(0, Math.max(1, max - 1))}…`;
}

function setText(id, value) {
    const node = document.getElementById(id);
    if (node) node.textContent = value;
}

function initHeatMap() {
    const node = document.getElementById('svialHeatMap');
    const status = document.getElementById('svialHeatStatus');

    if (!node) return false;

    if (typeof L === 'undefined' || typeof L.heatLayer === 'undefined') {
        if (status) status.textContent = 'No se pudo cargar Leaflet para el mapa.';
        return false;
    }

    if (svialHeatMap) return true;

    svialHeatMap = L.map(node, {
        zoomControl: true,
        preferCanvas: true
    }).setView([19.703, -101.186], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(svialHeatMap);

    svialHeatMarkers = L.layerGroup().addTo(svialHeatMap);
    setTimeout(() => svialHeatMap.invalidateSize(), 180);

    return true;
}

function clearHeatMap() {
    if (!svialHeatMap) return;

    Object.values(svialHeatLayers).forEach((layer) => {
        if (svialHeatMap.hasLayer(layer)) {
            svialHeatMap.removeLayer(layer);
        }
    });

    svialHeatLayers = {};
    svialHeatMarkers?.clearLayers();
}

function syncHeatLayerVisibility() {
    if (!svialHeatMap) return;

    document.querySelectorAll('.svial-layer-toggle').forEach((toggle) => {
        const layer = svialHeatLayers[toggle.dataset.layer];
        if (!layer) return;

        if (toggle.checked && !svialHeatMap.hasLayer(layer)) {
            layer.addTo(svialHeatMap);
        }

        if (!toggle.checked && svialHeatMap.hasLayer(layer)) {
            svialHeatMap.removeLayer(layer);
        }
    });
}

function heatCategoryColor(category) {
    return heatLayerConfig[category]?.color || '#334155';
}

function buildHeatPopup(punto) {
    const hechos = Array.isArray(punto.hechos) ? punto.hechos : [];
    const cards = hechos.map((hecho) => `
        <a class="svial-heat-popup-row" href="${escapeHtml(hecho.show_url)}" target="_blank" rel="noopener noreferrer">
            <strong>#${escapeHtml(hecho.id)} · ${escapeHtml(hecho.fecha || 'SIN FECHA')}</strong>
            <span>${escapeHtml(hecho.tipo_hecho || 'SIN TIPO')}${hecho.situacion ? ' · ' + escapeHtml(hecho.situacion) : ''}</span>
            <small>${escapeHtml(hecho.hora || 'SIN HORA')}${hecho.ubicacion ? ' · ' + escapeHtml(hecho.ubicacion) : ''}</small>
        </a>
    `).join('');

    return `
        <div class="svial-heat-popup">
            <div class="svial-heat-popup-title">${numberFormat.format(punto.total || 0)} siniestro(s) en esta zona</div>
            <div class="svial-heat-popup-meta">
                ${numberFormat.format(punto.fallecidos || 0)} personas fallecidas ·
                ${numberFormat.format(punto.lesionados || 0)} personas lesionadas ·
                ${numberFormat.format(punto.choques || 0)} choques sin víctimas
            </div>
            <div class="svial-heat-popup-list">${cards}</div>
        </div>
    `;
}

async function cargarMapaCalor() {
    if (!initHeatMap()) return;

    clearHeatMap();
    setText('svialHeatStatus', 'Cargando mapa...');

    const params = new URLSearchParams({
        fecha_inicio: inputInicio.value,
        fecha_fin: inputFin.value,
        precision: document.getElementById('svialHeatPrecision')?.value || '4'
    });

    try {
        const response = await fetch(`${heatmapUrl}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        const layers = data.layers || {};
        const maximos = data.maximos || {};
        const puntos = Array.isArray(data.puntos) ? data.puntos : [];

        Object.keys(heatLayerConfig).forEach((key) => {
            const points = Array.isArray(layers[key]) ? layers[key] : [];
            svialHeatLayers[key] = L.heatLayer(points, {
                radius: 30,
                blur: 22,
                maxZoom: 17,
                max: Math.max(1, Number(maximos[key] || 1)),
                gradient: heatLayerConfig[key].gradient
            });
        });

        puntos.forEach((punto) => {
            const total = Number(punto.total || 0);
            const marker = L.circleMarker([punto.lat, punto.lng], {
                radius: Math.min(24, 8 + (Math.sqrt(total) * 3.4)),
                color: '#ffffff',
                weight: 2,
                fillColor: heatCategoryColor(punto.categoria),
                fillOpacity: .9
            });

            marker.bindPopup(buildHeatPopup(punto));
            marker.addTo(svialHeatMarkers);
        });

        syncHeatLayerVisibility();

        setText('svialHeatPeriod', data.periodo?.texto || 'Periodo seleccionado');
        setText('svialHeatFatal', numberFormat.format(data.totales?.fallecidos || 0));
        setText('svialHeatInjured', numberFormat.format(data.totales?.lesionados || 0));
        setText('svialHeatCrashes', numberFormat.format(data.totales?.choques || 0));
        setText('svialHeatPoints', numberFormat.format(data.totales?.puntos || 0));
        setText('svialHeatTotal', numberFormat.format(data.totales?.hechos_conflictivos || 0));

        const zonasTexto = numberFormat.format(data.totales?.puntos || 0);
        const hechosZonaTexto = numberFormat.format(data.totales?.hechos_conflictivos || 0);
        const hechosTotalTexto = numberFormat.format(data.totales?.hechos || 0);
        setText(
            'svialHeatStatus',
            puntos.length
                ? `${zonasTexto} zonas conflictivas · ${hechosZonaTexto} de ${hechosTotalTexto} siniestros con coordenadas.`
                : 'Sin zonas conflictivas con coordenadas en el periodo.'
        );

        const bounds = puntos.map((punto) => [punto.lat, punto.lng]);

        if (bounds.length === 1) {
            svialHeatMap.setView(bounds[0], 15);
        } else if (bounds.length > 1) {
            svialHeatMap.fitBounds(bounds, { padding: [34, 34], maxZoom: 15 });
        } else {
            svialHeatMap.setView([19.703, -101.186], 12);
        }

        setTimeout(() => svialHeatMap.invalidateSize(), 120);
    } catch (error) {
        console.error('Error cargando mapa de calor de seguridad vial:', error);
        setText('svialHeatStatus', 'No se pudo cargar el mapa.');
    }
}

function updatePptDownloadLink() {
    if (!downloadPpt) return;

    const params = new URLSearchParams({
        fecha_inicio: inputInicio.value,
        fecha_fin: inputFin.value
    });

    downloadPpt.href = `${pptDownloadUrl}?${params.toString()}`;
}

function setFullscreenButton(active) {
    if (!btnFullscreen) return;

    btnFullscreen.innerHTML = active
        ? '<i class="fa-solid fa-compress"></i><span>Salir</span>'
        : '<i class="fa-solid fa-expand"></i><span>Pantalla</span>';
}

async function toggleFullscreen() {
    if (!pptShell) return;

    try {
        if (!document.fullscreenElement && pptShell.requestFullscreen) {
            await pptShell.requestFullscreen();
        } else if (document.fullscreenElement && document.exitFullscreen) {
            await document.exitFullscreen();
        } else {
            pptShell.classList.toggle('is-fullscreen');
            setFullscreenButton(pptShell.classList.contains('is-fullscreen'));
            setTimeout(() => goToSlide(currentIndex), 120);
        }
    } catch (error) {
        pptShell.classList.toggle('is-fullscreen');
        setFullscreenButton(pptShell.classList.contains('is-fullscreen'));
        setTimeout(() => goToSlide(currentIndex), 120);
    }
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
    chartDia = destroyChart(chartDia);
    chartHora = destroyChart(chartHora);

    if (typeof ApexCharts === 'undefined') {
        chartUnavailable('#chart_municipios');
        chartUnavailable('#chart_tipo');
        chartUnavailable('#chart_situacion');
        chartUnavailable('#chart_dia');
        chartUnavailable('#chart_hora');
        return;
    }

    const palette = ['#1d4ed8', '#dc2626', '#d97706', '#059669', '#7c3aed', '#0f766e', '#334155', '#be123c', '#2563eb', '#ca8a04', '#16a34a', '#9333ea'];
    const municipiosLabels = data.graficas?.municipios?.labels ?? [];
    const municipiosSeries = data.graficas?.municipios?.series ?? [];
    const diaLabels = data.graficas?.por_dia?.labels ?? [];
    const horaLabels = data.graficas?.por_hora?.labels ?? [];
    const horaSeries = data.graficas?.por_hora?.series ?? [];
    const tipoLabels = data.graficas?.por_tipo?.labels ?? [];
    const tipoDisplayLabels = tipoLabels.map((label) => shortChartLabel(label, 32));

    chartMunicipios = new ApexCharts(document.querySelector('#chart_municipios'), {
        chart: { type: 'bar', height: 470, toolbar: { show: false }, animations: { easing: 'easeinout', speed: 450 } },
        series: [{ name: 'Siniestros', data: municipiosSeries }],
        xaxis: { categories: municipiosLabels, labels: { style: { colors: '#334155', fontWeight: 700 } } },
        yaxis: { labels: { style: { colors: '#334155', fontWeight: 700 } } },
        plotOptions: { bar: { horizontal: true, borderRadius: 7, barHeight: '68%', distributed: true } },
        colors: palette,
        dataLabels: {
            enabled: true,
            formatter: (value) => Number(value) > 0 ? numberFormat.format(value) : '',
            style: { fontWeight: 950, colors: ['#0f172a'] },
            background: { enabled: true, foreColor: '#0f172a', borderRadius: 5, borderWidth: 0, opacity: .94 }
        },
        grid: { borderColor: 'rgba(148,163,184,.25)' },
        legend: { show: false }
    });
    chartMunicipios.render();

    chartDia = new ApexCharts(document.querySelector('#chart_dia'), {
        chart: { type: 'bar', height: 315, toolbar: { show: false }, animations: { easing: 'easeinout', speed: 450 } },
        series: [{ name: 'Siniestros', data: data.graficas?.por_dia?.series ?? [] }],
        xaxis: {
            categories: diaLabels,
            labels: { rotate: -20, trim: true, style: { colors: '#334155', fontSize: '11px', fontWeight: 850 } }
        },
        yaxis: { forceNiceScale: true, labels: { style: { colors: '#334155', fontWeight: 800 } } },
        plotOptions: { bar: { borderRadius: 7, columnWidth: '54%', distributed: true } },
        colors: palette.slice(0, 7),
        dataLabels: {
            enabled: true,
            formatter: (value) => Number(value) > 0 ? numberFormat.format(value) : '',
            style: { fontWeight: 950, colors: ['#0f172a'] },
            background: { enabled: true, foreColor: '#0f172a', borderRadius: 6, borderWidth: 0, opacity: .96 }
        },
        grid: { borderColor: 'rgba(148,163,184,.22)' },
        legend: { show: false }
    });
    chartDia.render();

    chartHora = new ApexCharts(document.querySelector('#chart_hora'), {
        chart: { type: 'bar', height: 315, toolbar: { show: false }, animations: { easing: 'easeinout', speed: 450 } },
        series: [{ name: 'Siniestros', data: horaSeries }],
        xaxis: {
            categories: horaLabels,
            labels: { rotate: -45, style: { colors: '#334155', fontSize: '11px', fontWeight: 800 } }
        },
        yaxis: { forceNiceScale: true, labels: { style: { colors: '#334155', fontWeight: 800 } } },
        plotOptions: { bar: { borderRadius: 5, columnWidth: '58%', distributed: false, dataLabels: { position: 'top' } } },
        colors: ['#0f766e'],
        dataLabels: {
            enabled: true,
            offsetY: -18,
            formatter: (value) => Number(value) > 0 ? numberFormat.format(value) : '',
            style: { fontSize: '11px', fontWeight: 950, colors: ['#0f172a'] },
            background: { enabled: true, foreColor: '#0f172a', borderRadius: 5, borderWidth: 0, opacity: .96 }
        },
        grid: { borderColor: 'rgba(148,163,184,.22)', padding: { top: 28 } },
        legend: { show: false }
    });
    chartHora.render();

    chartTipo = new ApexCharts(document.querySelector('#chart_tipo'), {
        chart: { type: 'bar', height: 395, toolbar: { show: false }, animations: { easing: 'easeinout', speed: 450 } },
        series: [{ name: 'Siniestros', data: data.graficas?.por_tipo?.series ?? [] }],
        xaxis: {
            categories: tipoDisplayLabels,
            labels: { style: { colors: '#334155', fontWeight: 800 } }
        },
        yaxis: {
            labels: {
                maxWidth: 270,
                style: { colors: '#334155', fontSize: '11px', fontWeight: 900 }
            }
        },
        plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: '62%', distributed: true } },
        colors: palette,
        dataLabels: {
            enabled: true,
            textAnchor: 'start',
            offsetX: 8,
            style: { fontWeight: 950, colors: ['#0f172a'] },
            background: { enabled: true, foreColor: '#0f172a', borderRadius: 5, borderWidth: 0, opacity: .96 }
        },
        grid: { borderColor: 'rgba(148,163,184,.22)', padding: { left: 8 } },
        legend: { show: false },
        tooltip: {
            y: {
                title: {
                    formatter: function (_, opts) {
                        return tipoLabels[opts.dataPointIndex] ?? 'Tipo';
                    }
                }
            }
        }
    });
    chartTipo.render();

    chartSituacion = new ApexCharts(document.querySelector('#chart_situacion'), {
        chart: { type: 'donut', height: 420, toolbar: { show: false }, animations: { easing: 'easeinout', speed: 450 } },
        series: data.graficas?.por_situacion?.series ?? [],
        labels: data.graficas?.por_situacion?.labels ?? [],
        colors: ['#059669', '#d97706', '#dc2626', '#1d4ed8', '#64748b'],
        legend: {
            position: 'bottom',
            fontWeight: 850,
            labels: { colors: '#334155' },
            formatter: function (seriesName, opts) {
                const value = opts.w.globals.series[opts.seriesIndex] ?? 0;
                return `${seriesName}: ${numberFormat.format(value)}`;
            }
        },
        plotOptions: { pie: { donut: { size: '62%', labels: { show: true, total: { show: true, label: 'TOTAL' } } } } },
        dataLabels: { enabled: true, style: { fontWeight: 950 } },
        stroke: { colors: ['#ffffff'], width: 4 }
    });
    chartSituacion.render();
}

function updateReport(data) {
    const kpis = data.kpis ?? {};
    const periodo = data.periodo ?? {};

    inputInicio.value = periodo.fecha_inicio ?? inputInicio.value;
    inputFin.value = periodo.fecha_fin ?? inputFin.value;
    updatePptDownloadLink();

    setText('svialCoverPeriod', periodo.texto ?? 'Periodo seleccionado');
    setText('svialComparePeriod', periodo.texto ?? 'Periodo seleccionado');
    setText('svialHeatPeriod', periodo.texto ?? 'Periodo seleccionado');
    setText('svialSummaryPeriod', periodo.texto ?? 'Periodo seleccionado');
    setText('svialTemporalPeriod', periodo.texto ?? 'Periodo seleccionado');
    setText('svialAnalysisPeriod', periodo.texto ?? 'Periodo seleccionado');
    setText('svialMunicipiosCount', numberFormat.format(kpis.municipios_con_hechos ?? 0));
    setText('svialTotalHechosCompare', numberFormat.format(kpis.total_hechos ?? 0));

    setText('kpi_total_hechos', numberFormat.format(kpis.total_hechos ?? 0));
    setText('kpi_total_lesionados', numberFormat.format(kpis.total_lesionados ?? 0));
    setText('kpi_total_fallecidos', numberFormat.format(kpis.total_fallecidos ?? 0));
    setText('kpi_total_vehiculos', numberFormat.format(kpis.total_vehiculos ?? 0));
    setText('kpi_municipios', numberFormat.format(kpis.municipios_con_hechos ?? 0));
    setText('kpi_promedio_diario', numberFormat.format(kpis.promedio_diario ?? 0));
    setText('kpi_promedio_diario_temporal', numberFormat.format(kpis.promedio_diario ?? 0));
    setText('kpi_municipio_principal', kpis.municipio_principal ?? 'SIN DATOS');
    setText('kpi_municipio_principal_total', numberFormat.format(kpis.municipio_principal_total ?? 0));
    setText('kpi_hora_pico', kpis.hora_pico ?? 'SIN HORA');
    setText('kpi_hora_pico_total', numberFormat.format(kpis.hora_pico_total ?? 0));
    setText('kpi_dia_pico', kpis.dia_pico ?? 'SIN DATOS');
    setText('kpi_dia_pico_total', numberFormat.format(kpis.dia_pico_total ?? 0));
    setText('kpi_tipo_principal', kpis.tipo_principal ?? 'SIN DATOS');
    setText('kpi_tipo_principal_total', numberFormat.format(kpis.tipo_principal_total ?? 0));

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
btnFullscreen?.addEventListener('click', toggleFullscreen);

document.addEventListener('fullscreenchange', () => {
    const active = document.fullscreenElement === pptShell;
    pptShell?.classList.toggle('is-fullscreen', active);
    setFullscreenButton(active);
    setTimeout(() => goToSlide(currentIndex), 120);
});

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

function scrollActiveSlide(deltaY) {
    const activeSlide = slides[currentIndex];
    const card = activeSlide?.querySelector('.ppt-card');
    const target = card && card.scrollHeight > card.clientHeight ? card : activeSlide;

    if (!target || target.scrollHeight <= target.clientHeight) {
        return false;
    }

    const before = target.scrollTop;
    target.scrollTop += deltaY;

    return target.scrollTop !== before;
}

ppt?.addEventListener('wheel', (event) => {
    if (Math.abs(event.deltaY) <= Math.abs(event.deltaX)) {
        return;
    }

    if (scrollActiveSlide(event.deltaY)) {
        event.preventDefault();
    }
}, { passive: false });

window.addEventListener('resize', () => goToSlide(currentIndex));

form?.addEventListener('submit', async (event) => {
    event.preventDefault();

    try {
        await cargarReporte();
        await cargarMapaCalor();
        goToSlide(1);
    } catch (error) {
        console.error('Error cargando informe de seguridad vial:', error);
        document.getElementById('svialRankingMunicipios').innerHTML = '<div class="svial-empty svial-empty--danger">No se pudo cargar el informe.</div>';
    }
});

inputInicio?.addEventListener('change', updatePptDownloadLink);
inputFin?.addEventListener('change', updatePptDownloadLink);
document.getElementById('svialHeatRefresh')?.addEventListener('click', cargarMapaCalor);
document.getElementById('svialHeatPrecision')?.addEventListener('change', cargarMapaCalor);
document.querySelectorAll('.svial-layer-toggle').forEach((toggle) => {
    toggle.addEventListener('change', syncHeatLayerVisibility);
});

document.addEventListener('DOMContentLoaded', async () => {
    buildIndicators();
    updatePptDownloadLink();

    try {
        await cargarReporte();
        await cargarMapaCalor();
    } catch (error) {
        console.error('Error cargando informe de seguridad vial:', error);
        document.getElementById('svialRankingMunicipios').innerHTML = '<div class="svial-empty svial-empty--danger">No se pudo cargar el informe.</div>';
    }
});
</script>
@stop
