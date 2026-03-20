@extends('adminlte::page')

@section('title', 'Resumen Ejecutivo Diario')

@section('content_header')
    <h1>Resumen Ejecutivo Diario</h1>
@stop

@section('content')
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3 class="mb-1">Centro de Mando Diario</h3>
                    <p class="mb-0 text-muted">Resumen visual de los hechos más relevantes del día</p>
                </div>

                <div class="d-flex align-items-center">
                    <input type="date" id="filtro_fecha" class="form-control mr-2">
                    <button class="btn btn-primary" id="btn_recargar">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row" id="bloque_kpis">
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="kpi_total_hechos">0</h3>
                <p>Total de hechos</p>
            </div>
            <div class="icon"><i class="fas fa-car-crash"></i></div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3 id="kpi_total_lesionados">0</h3>
                <p>Lesionados</p>
            </div>
            <div class="icon"><i class="fas fa-user-injured"></i></div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="kpi_total_vehiculos">0</h3>
                <p>Vehículos involucrados</p>
            </div>
            <div class="icon"><i class="fas fa-car-side"></i></div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="kpi_total_relevantes">0</h3>
                <p>Hechos relevantes</p>
            </div>
            <div class="icon"><i class="fas fa-star"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Hechos por tipo</h3>
            </div>
            <div class="card-body">
                <div id="chart_por_tipo" style="min-height: 360px;"></div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Hechos por hora</h3>
            </div>
            <div class="card-body">
                <div id="chart_por_hora" style="min-height: 360px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title">Lo más relevante del día</h3>
            </div>
            <div class="card-body">
                <div class="row" id="contenedor_relevantes"></div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    #contenedor_relevantes .card {
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(0,0,0,.08);
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    let chartTipo = null;
    let chartHora = null;

    const fechaUrl = "{{ $fecha ?? now()->toDateString() }}";

    async function cargarResumen() {
        const fecha = document.getElementById('filtro_fecha').value;

        const response = await fetch(`/resumen-ejecutivo/data/${fecha}`);
        const data = await response.json();

        document.getElementById('kpi_total_hechos').textContent = data.kpis.total_hechos ?? 0;
        document.getElementById('kpi_total_lesionados').textContent = data.kpis.total_lesionados ?? 0;
        document.getElementById('kpi_total_vehiculos').textContent = data.kpis.total_vehiculos ?? 0;
        document.getElementById('kpi_total_relevantes').textContent = data.kpis.total_relevantes ?? 0;

        renderChartTipo(data.graficas.por_tipo.labels, data.graficas.por_tipo.series);
        renderChartHora(data.graficas.por_hora.labels, data.graficas.por_hora.series);
        renderRelevantes(data.relevantes);
    }

    function renderChartTipo(labels, series) {
        if (chartTipo) chartTipo.destroy();

        chartTipo = new ApexCharts(document.querySelector("#chart_por_tipo"), {
            chart: { type: 'bar', height: 350, toolbar: { show: false } },
            series: [{ name: 'Hechos', data: series }],
            xaxis: { categories: labels },
            dataLabels: { enabled: true }
        });

        chartTipo.render();
    }

    function renderChartHora(labels, series) {
        if (chartHora) chartHora.destroy();

        chartHora = new ApexCharts(document.querySelector("#chart_por_hora"), {
            chart: { type: 'line', height: 350, toolbar: { show: false } },
            series: [{ name: 'Hechos', data: series }],
            xaxis: { categories: labels },
            stroke: { curve: 'smooth', width: 4 },
            dataLabels: { enabled: true }
        });

        chartHora.render();
    }

    function renderRelevantes(items) {
        const contenedor = document.getElementById('contenedor_relevantes');
        contenedor.innerHTML = '';

        if (!items || items.length === 0) {
            contenedor.innerHTML = `
                <div class="col-md-12">
                    <div class="alert alert-info mb-0">
                        No se encontraron hechos relevantes.
                    </div>
                </div>
            `;
            return;
        }

        items.forEach(item => {
            contenedor.innerHTML += `
                <div class="col-md-4 mb-3">
                    <div class="card h-100 card-outline card-danger">
                        <div class="card-body">
                            <h5 class="card-title mb-2">${item.tipo_hecho ?? 'Sin tipo'}</h5>
                            <p class="mb-1"><strong>Folio:</strong> ${item.folio ?? '-'}</p>
                            <p class="mb-1"><strong>Hora:</strong> ${item.hora ?? '-'}</p>
                            <p class="mb-1"><strong>Ubicación:</strong> ${item.ubicacion ?? '-'}</p>
                            <p class="mb-1"><strong>Lesionados:</strong> ${item.lesionados ?? 0}</p>
                            <p class="mb-2"><strong>Score:</strong> ${item.score ?? 0}</p>

                            <a href="${item.url}" class="btn btn-outline-primary btn-sm">
                                Ver hecho
                            </a>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const fechaInput = document.getElementById('filtro_fecha');
        fechaInput.value = fechaUrl;

        document.getElementById('btn_recargar').addEventListener('click', cargarResumen);

        cargarResumen();
    });
</script>
@stop
