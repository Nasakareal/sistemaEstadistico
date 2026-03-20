@extends('adminlte::page')

@section('title', 'Resumen Ejecutivo')

@section('content')
<div id="ppt-shell">
    <div id="ppt-horizontal">

        <section class="ppt-slide ppt-slide--cover">
            @include('resumen_ejecutivo.partials.portada')
        </section>

        <section class="ppt-slide">
            <div class="ppt-card ppt-card--resumen">
                <div class="resumen-slide">

                    <div class="resumen-slide__header">
                        <div class="resumen-slide__brand">
                            <img src="{{ asset('img/tele.png') }}" alt="Identidad institucional">
                        </div>

                        <div class="resumen-slide__title-wrap">
                            <div class="resumen-slide__eyebrow">Panel ejecutivo</div>
                            <h2 class="resumen-slide__title">Agenda de Proximidad Social</h2>
                            <div class="resumen-slide__date">
                                {{ \Carbon\Carbon::parse($fecha)->translatedFormat('d \d\e F \d\e Y') }}
                                <span>(corte de las 20:00 hrs.)</span>
                            </div>
                        </div>
                    </div>

                    <div class="resumen-slide__body">
                        <div class="resumen-metricas">

                            <div class="resumen-metrica">
                                <div class="resumen-metrica__icon">
                                    <i class="fa-solid fa-car-burst"></i>
                                </div>
                                <div class="resumen-metrica__numero" id="kpi_total_hechos">0</div>
                                <div class="resumen-metrica__titulo">Siniestros registrados</div>
                                <div class="resumen-metrica__desc">Eventos capturados en el corte diario</div>
                            </div>

                            <div class="resumen-metrica resumen-metrica--divider"></div>

                            <div class="resumen-metrica">
                                <div class="resumen-metrica__icon">
                                    <i class="fa-solid fa-user-injured"></i>
                                </div>
                                <div class="resumen-metrica__numero" id="kpi_total_lesionados">0</div>
                                <div class="resumen-metrica__titulo">Lesionados</div>
                                <div class="resumen-metrica__desc">Personas reportadas con lesión</div>
                            </div>

                            <div class="resumen-metrica resumen-metrica--divider"></div>

                            <div class="resumen-metrica">
                                <div class="resumen-metrica__icon">
                                    <i class="fa-solid fa-car-side"></i>
                                </div>
                                <div class="resumen-metrica__numero" id="kpi_total_vehiculos">0</div>
                                <div class="resumen-metrica__titulo">Vehículos involucrados</div>
                                <div class="resumen-metrica__desc">Unidades registradas en los siniestros</div>
                            </div>

                            <div class="resumen-metrica resumen-metrica--divider"></div>

                            <div class="resumen-metrica">
                                <div class="resumen-metrica__icon">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                </div>
                                <div class="resumen-metrica__numero" id="kpi_total_relevantes">0</div>
                                <div class="resumen-metrica__titulo">Siniestros relevantes</div>
                                <div class="resumen-metrica__desc">Casos priorizados para seguimiento</div>
                            </div>

                        </div>
                    </div>

                    <div class="resumen-slide__footer-line"></div>

                </div>
            </div>
        </section>

        <section class="ppt-slide">
            <div class="ppt-card">
                <div class="ppt-card__header">
                    <div>
                        <div class="ppt-eyebrow">Análisis</div>
                        <h2 class="ppt-title">Siniestros por tipo</h2>
                    </div>
                </div>

                <div id="chart_por_tipo" class="ppt-chart"></div>
            </div>
        </section>

        <section class="ppt-slide">
            <div class="ppt-card">
                <div class="ppt-card__header">
                    <div>
                        <div class="ppt-eyebrow">Comportamiento</div>
                        <h2 class="ppt-title">Siniestros por hora</h2>
                    </div>
                </div>

                <div id="chart_por_hora" class="ppt-chart"></div>
            </div>
        </section>

        <section class="ppt-slide ppt-slide--relevantes">
            <div class="ppt-card">
                <div class="ppt-card__header">
                    <div>
                        <div class="ppt-eyebrow">Seguimiento</div>
                        <h2 class="ppt-title">Siniestros relevantes</h2>
                    </div>
                </div>

                <div id="contenedor_relevantes"></div>
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
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
const fecha = @json($fecha);
const dataUrl = @json(route('resumen_ejecutivo.data', $fecha));
const ppt = document.getElementById('ppt-horizontal');
const slides = Array.from(document.querySelectorAll('.ppt-slide'));
const btnPrev = document.getElementById('pptPrev');
const btnNext = document.getElementById('pptNext');
const indicatorsWrap = document.getElementById('pptIndicators');

let chartTipo = null;
let chartHora = null;
let currentIndex = 0;

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
    const dots = indicatorsWrap.querySelectorAll('.ppt-indicator');
    dots.forEach((dot, i) => {
        dot.classList.toggle('is-active', i === index);
    });
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
    window.clearTimeout(window.__pptScrollTimer);
    window.__pptScrollTimer = window.setTimeout(detectCurrentSlide, 60);
});

window.addEventListener('resize', () => {
    goToSlide(currentIndex);
});

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function renderRelevantes(relevantes) {
    const contenedor = document.getElementById('contenedor_relevantes');

    if (!contenedor) return;

    contenedor.innerHTML = '';
    contenedor.classList.remove('cols-1', 'cols-2');

    if (!Array.isArray(relevantes) || relevantes.length === 0) {
        contenedor.innerHTML = `
            <div class="alert alert-info mb-0">
                No hay siniestros relevantes marcados para esta fecha.
            </div>
        `;
        return;
    }

    if (relevantes.length === 1) {
        contenedor.classList.add('cols-1');
    } else if (relevantes.length === 2) {
        contenedor.classList.add('cols-2');
    }

    relevantes.forEach((hecho) => {
        const foto = hecho.foto_principal
            ? `
                <div class="relevante-media">
                    <img src="${hecho.foto_principal}" alt="Foto del siniestro">
                </div>
            `
            : `
                <div class="relevante-media d-flex align-items-center justify-content-center" style="color:#334155; font-weight:800;">
                    SIN FOTO
                </div>
            `;

        const card = `
            <div class="relevante-item">
                <div class="card">
                    <div class="card-body">
                        ${foto}

                        <div class="mb-3">
                            <span class="badge badge-warning">RELEVANTE</span>
                        </div>

                        <h5 class="font-weight-bold">${escapeHtml(hecho.tipo_hecho || 'SIN TIPO')}</h5>

                        <div class="relevante-texto">
                            <div class="relevante-linea">
                                <strong>Folio:</strong> ${escapeHtml(hecho.folio_c5i || hecho.id)}
                            </div>

                            <div class="relevante-linea">
                                <strong>Hora:</strong> ${escapeHtml(hecho.hora || 'SIN HORA')}
                            </div>

                            <div class="relevante-linea">
                                <strong>Ubicación:</strong> ${escapeHtml(hecho.ubicacion || 'Sin ubicación')}
                            </div>

                            <div class="relevante-linea">
                                <strong>Situación:</strong> ${escapeHtml(hecho.situacion || 'SIN DATO')}
                            </div>

                            <div class="relevante-linea">
                                <strong>Lesionados:</strong> ${escapeHtml(hecho.lesionados_count ?? 0)}
                            </div>

                            <div class="relevante-linea">
                                <strong>Vehículos:</strong> ${escapeHtml(hecho.vehiculos_count ?? 0)}
                            </div>
                        </div>

                        <div class="relevante-footer">
                            <a href="${hecho.url}" class="btn btn-primary btn-sm" target="_blank" rel="noopener">
                                <i class="fa-regular fa-eye"></i> Ver siniestro
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;

        contenedor.insertAdjacentHTML('beforeend', card);
    });
}

async function cargar() {
    buildIndicators();

    try {
        const res = await fetch(dataUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        const data = await res.json();

        console.log('Resumen ejecutivo data:', data);

        document.getElementById('kpi_total_hechos').textContent = data.kpis?.total_hechos ?? 0;
        document.getElementById('kpi_total_lesionados').textContent = data.kpis?.total_lesionados ?? 0;
        document.getElementById('kpi_total_vehiculos').textContent = data.kpis?.total_vehiculos ?? 0;
        document.getElementById('kpi_total_relevantes').textContent = data.kpis?.total_relevantes ?? 0;

        renderRelevantes(data.relevantes ?? []);

        if (chartTipo) {
            chartTipo.destroy();
            chartTipo = null;
        }

        if (chartHora) {
            chartHora.destroy();
            chartHora = null;
        }

        if (document.querySelector('#chart_por_tipo')) {
            chartTipo = new ApexCharts(document.querySelector('#chart_por_tipo'), {
                chart: {
                    type: 'bar',
                    height: 420,
                    toolbar: { show: false },
                    animations: { easing: 'easeinout', speed: 500 }
                },
                series: [{
                    name: 'Siniestros',
                    data: data.graficas?.por_tipo?.series ?? []
                }],
                xaxis: {
                    categories: data.graficas?.por_tipo?.labels ?? []
                },
                dataLabels: { enabled: false },
                stroke: { show: false },
                grid: { borderColor: 'rgba(148,163,184,.20)' },
                legend: { show: false }
            });

            chartTipo.render();
        }

        if (document.querySelector('#chart_por_hora')) {
            chartHora = new ApexCharts(document.querySelector('#chart_por_hora'), {
                chart: {
                    type: 'line',
                    height: 420,
                    toolbar: { show: false },
                    animations: { easing: 'easeinout', speed: 500 }
                },
                series: [{
                    name: 'Siniestros',
                    data: data.graficas?.por_hora?.series ?? []
                }],
                xaxis: {
                    categories: data.graficas?.por_hora?.labels ?? []
                },
                dataLabels: { enabled: false },
                stroke: {
                    curve: 'smooth',
                    width: 4
                },
                grid: { borderColor: 'rgba(148,163,184,.20)' },
                legend: { show: false }
            });

            chartHora.render();
        }

        goToSlide(0);
    } catch (error) {
        console.error('Error cargando resumen ejecutivo:', error);

        document.getElementById('kpi_total_hechos').textContent = 0;
        document.getElementById('kpi_total_lesionados').textContent = 0;
        document.getElementById('kpi_total_vehiculos').textContent = 0;
        document.getElementById('kpi_total_relevantes').textContent = 0;

        const contenedor = document.getElementById('contenedor_relevantes');
        if (contenedor) {
            contenedor.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger mb-0">
                        No se pudo cargar la información del resumen ejecutivo.
                    </div>
                </div>
            `;
        }
    }
}

document.addEventListener('DOMContentLoaded', cargar);
</script>
@stop
