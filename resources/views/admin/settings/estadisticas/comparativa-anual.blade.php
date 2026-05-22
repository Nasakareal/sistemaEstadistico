@extends('adminlte::page')

@section('title', 'Comparativa Anual de Hechos')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h1 class="m-0">Comparativa Anual de Hechos</h1>
            <small class="text-muted">Hechos, lesionados y defunciones · Siniestros + Delegaciones</small>
        </div>
        <a href="{{ route('estadisticas.index') }}" class="btn btn-secondary btn-sm mt-2 mt-sm-0">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Filtros</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('estadisticas.comparativaAnual') }}" class="row align-items-end">
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group mb-md-0">
                                <label for="desde">Desde</label>
                                <input type="number" min="1900" max="2100" id="desde" name="desde" class="form-control" value="{{ $anioInicio }}">
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group mb-md-0">
                                <label for="hasta">Hasta</label>
                                <input type="number" min="1900" max="2100" id="hasta" name="hasta" class="form-control" value="{{ $anioFin }}">
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-sync-alt"></i> Aplicar
                            </button>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('estadisticas.comparativaAnual') }}" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-undo"></i> Reiniciar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-primary"><i class="fas fa-car-crash"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Hechos</span>
                    <span class="info-box-number">{{ number_format($totales['hechos']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-user-injured"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Lesionados</span>
                    <span class="info-box-number">{{ number_format($totales['lesionados']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-danger"><i class="fas fa-cross"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Defunciones</span>
                    <span class="info-box-number">{{ number_format($totales['defunciones']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-calendar-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Mayor registro</span>
                    <span class="info-box-number">
                        {{ $anioMayorHechos ? $anioMayorHechos->anio . ' · ' . number_format($anioMayorHechos->hechos) : 'Sin datos' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    @if($proyeccionAnual)
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line"></i>
                            Proyección {{ $proyeccionAnual->anio }} al cierre de año
                        </h3>
                    </div>
                    <div class="card-body py-3">
                        <div class="projection-grid">
                            <div>
                                <span class="projection-label">Hechos estimados</span>
                                <strong>{{ number_format($proyeccionAnual->proyeccion_hechos) }}</strong>
                            </div>
                            <div>
                                <span class="projection-label">Lesionados estimados</span>
                                <strong>{{ number_format($proyeccionAnual->proyeccion_lesionados) }}</strong>
                            </div>
                            <div>
                                <span class="projection-label">Defunciones estimadas</span>
                                <strong>{{ number_format($proyeccionAnual->proyeccion_defunciones) }}</strong>
                            </div>
                            <div>
                                <span class="projection-label">Corte</span>
                                <strong>{{ $fechaCorteProyeccion->format('d/m/Y') }}</strong>
                            </div>
                        </div>
                        <div class="projection-note">
                            Método: acumulado {{ $proyeccionAnual->anio }} hasta el último dato capturado + promedio histórico restante
                            ({{ $proyeccionRestanteHistorica['inicio_restante'] }} al 31/12 de {{ implode(', ', $proyeccionRestanteHistorica['anios']) }}).
                            Promedio restante: {{ number_format($proyeccionRestanteHistorica['hechos']) }} hechos,
                            {{ number_format($proyeccionRestanteHistorica['lesionados']) }} lesionados y
                            {{ number_format($proyeccionRestanteHistorica['defunciones']) }} defunciones.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($resumenMismoCorte)
        @php
            $actualCorte = $resumenMismoCorte['actual'];
            $anioAnteriorCorte = $resumenMismoCorte['anterior'];
            $diferenciaAnterior = $resumenMismoCorte['diferencia_anterior'];
            $porcentajeAnterior = $resumenMismoCorte['porcentaje_anterior'];
            $diferenciaPromedio = $resumenMismoCorte['diferencia_promedio'];
            $porcentajePromedio = $resumenMismoCorte['porcentaje_promedio'];
        @endphp
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-balance-scale"></i>
                            {{ $actualCorte->anio }} contra el mismo corte histórico
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="same-cut-grid">
                            <div class="same-cut-kpi">
                                <span>Hechos al corte</span>
                                <strong>{{ number_format($actualCorte->hechos) }}</strong>
                                <small>Del 01/01 al {{ \Carbon\Carbon::parse($actualCorte->corte)->format('d/m/Y') }}</small>
                            </div>
                            <div class="same-cut-kpi">
                                <span>Contra {{ $anioAnteriorCorte ? $anioAnteriorCorte->anio : 'año anterior' }}</span>
                                <strong class="{{ $diferenciaAnterior !== null && $diferenciaAnterior >= 0 ? 'same-cut-up' : 'same-cut-down' }}">
                                    {{ $diferenciaAnterior !== null ? ($diferenciaAnterior >= 0 ? '+' : '') . number_format($diferenciaAnterior) : '-' }}
                                </strong>
                                <small>
                                    {{ $porcentajeAnterior !== null ? ($porcentajeAnterior >= 0 ? '+' : '') . number_format($porcentajeAnterior, 1) . '%' : 'Sin referencia' }}
                                </small>
                            </div>
                            <div class="same-cut-kpi">
                                <span>Contra promedio histórico</span>
                                <strong class="{{ $diferenciaPromedio !== null && $diferenciaPromedio >= 0 ? 'same-cut-up' : 'same-cut-down' }}">
                                    {{ $diferenciaPromedio !== null ? ($diferenciaPromedio >= 0 ? '+' : '') . number_format($diferenciaPromedio) : '-' }}
                                </strong>
                                <small>
                                    Promedio: {{ $resumenMismoCorte['promedio_historico'] !== null ? number_format($resumenMismoCorte['promedio_historico']) : '-' }}
                                    @if($porcentajePromedio !== null)
                                        ({{ $porcentajePromedio >= 0 ? '+' : '' }}{{ number_format($porcentajePromedio, 1) }}%)
                                    @endif
                                </small>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-lg-7 col-12">
                                <div class="same-cut-chart">
                                    <canvas id="mismoCorteChart"></canvas>
                                </div>
                            </div>
                            <div class="col-lg-5 col-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Año</th>
                                                <th>Corte</th>
                                                <th>Hechos</th>
                                                <th>Lesionados</th>
                                                <th>Defunciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($comparativaMismoCorte as $registro)
                                                <tr class="{{ $registro->anio === $actualCorte->anio ? 'same-cut-current-row' : '' }}">
                                                    <td>{{ $registro->anio }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($registro->corte)->format('d/m') }}</td>
                                                    <td>{{ number_format($registro->hechos) }}</td>
                                                    <td>{{ number_format($registro->lesionados) }}</td>
                                                    <td>{{ number_format($registro->defunciones) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="projection-note">
                            Lectura: esta comparación usa el mismo periodo de cada año, del 01/01 al {{ $fechaCorteProyeccion->format('d/m') }},
                            para no comparar {{ $actualCorte->anio }} incompleto contra años completos.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8 col-12">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Tendencia por Año</h3>
                </div>
                <div class="card-body">
                    <div class="chart-series-controls mb-3">
                        <label class="chart-toggle chart-toggle--choques">
                            <input type="checkbox" class="chart-series-toggle" data-dataset="0" checked>
                            <span>Hechos</span>
                        </label>
                        <label class="chart-toggle chart-toggle--lesionados">
                            <input type="checkbox" class="chart-series-toggle" data-dataset="1" checked>
                            <span>Lesionados</span>
                        </label>
                        <label class="chart-toggle chart-toggle--defunciones">
                            <input type="checkbox" class="chart-series-toggle" data-dataset="2" checked>
                            <span>Defunciones</span>
                        </label>
                        <label class="chart-toggle chart-toggle--proyeccion">
                            <input type="checkbox" class="chart-series-toggle" data-dataset="3" checked>
                            <span>Proy. hechos</span>
                        </label>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="comparativaAnualChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Detalle</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="tabla-comparativa-anual" class="table table-bordered table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Año</th>
                                    <th>Hechos</th>
                                    <th>Lesionados</th>
                                    <th>Defunciones</th>
                                    <th>Proy. Hechos</th>
                                    <th>Proy. Lesionados</th>
                                    <th>Proy. Defunciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($comparativa as $registro)
                                    <tr>
                                        <td>{{ $registro->anio }}</td>
                                        <td>{{ number_format($registro->hechos) }}</td>
                                        <td>{{ number_format($registro->lesionados) }}</td>
                                        <td>{{ number_format($registro->defunciones) }}</td>
                                        <td>{{ $registro->proyeccion_hechos !== null ? number_format($registro->proyeccion_hechos) : '-' }}</td>
                                        <td>{{ $registro->proyeccion_lesionados !== null ? number_format($registro->proyeccion_lesionados) : '-' }}</td>
                                        <td>{{ $registro->proyeccion_defunciones !== null ? number_format($registro->proyeccion_defunciones) : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">Sin datos</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th>{{ number_format($totales['hechos']) }}</th>
                                    <th>{{ number_format($totales['lesionados']) }}</th>
                                    <th>{{ number_format($totales['defunciones']) }}</th>
                                    <th>{{ $proyeccionAnual ? number_format($proyeccionAnual->proyeccion_hechos) : '-' }}</th>
                                    <th>{{ $proyeccionAnual ? number_format($proyeccionAnual->proyeccion_lesionados) : '-' }}</th>
                                    <th>{{ $proyeccionAnual ? number_format($proyeccionAnual->proyeccion_defunciones) : '-' }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .chart-wrap {
            min-height: 420px;
            position: relative;
        }

        #comparativaAnualChart {
            min-height: 420px;
        }

        .same-cut-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .same-cut-kpi {
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 8px;
            padding: 14px;
            background: rgba(255, 255, 255, .04);
        }

        .same-cut-kpi span,
        .same-cut-kpi small {
            display: block;
            color: rgba(255, 255, 255, .68);
            font-size: 12px;
            font-weight: 800;
        }

        .same-cut-kpi strong {
            display: block;
            margin: 6px 0;
            font-size: 26px;
            line-height: 1;
        }

        .same-cut-up {
            color: #ffc107;
        }

        .same-cut-down {
            color: #17a2b8;
        }

        .same-cut-chart {
            min-height: 260px;
            position: relative;
        }

        #mismoCorteChart {
            min-height: 260px;
        }

        .same-cut-current-row {
            background: rgba(40, 167, 69, .12);
            font-weight: 900;
        }

        .projection-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .projection-grid > div {
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 8px;
            padding: 12px;
            background: rgba(255, 255, 255, .04);
        }

        .projection-label {
            display: block;
            color: rgba(255, 255, 255, .68);
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .projection-grid strong {
            font-size: 22px;
            line-height: 1;
        }

        .projection-note {
            margin-top: 10px;
            color: rgba(255, 255, 255, .68);
            font-size: 12px;
            font-weight: 700;
        }

        .chart-series-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .chart-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 8px 10px;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 8px;
            background: rgba(255, 255, 255, .04);
            color: rgba(255, 255, 255, .78);
            cursor: pointer;
            font-size: 12px;
            font-weight: 800;
            user-select: none;
        }

        .chart-toggle input {
            margin: 0;
        }

        .chart-toggle--choques {
            border-color: rgba(0, 123, 255, .6);
        }

        .chart-toggle--lesionados {
            border-color: rgba(255, 193, 7, .75);
        }

        .chart-toggle--defunciones {
            border-color: rgba(220, 53, 69, .75);
        }

        .chart-toggle--proyeccion {
            border-color: rgba(23, 162, 184, .75);
        }

        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
        }

        @media (max-width: 767.98px) {
            .same-cut-grid,
            .projection-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        $(function () {
            const registros = @json($comparativa);
            const labels = registros.map((registro) => registro.anio);
            const hechos = registros.map((registro) => registro.hechos);
            const lesionados = registros.map((registro) => registro.lesionados);
            const defunciones = registros.map((registro) => registro.defunciones);
            const proyeccionHechos = registros.map((registro) => registro.proyeccion_hechos);
            const maxLesionados = Math.max(...lesionados, 0);
            const maxDefunciones = Math.max(...defunciones, 0);
            const canvas = document.getElementById('comparativaAnualChart');
            let comparativaChart = null;
            const registrosMismoCorte = @json($comparativaMismoCorte);
            const canvasMismoCorte = document.getElementById('mismoCorteChart');

            if (canvas && window.Chart) {
                comparativaChart = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [
                            {
                                type: 'bar',
                                label: 'Hechos',
                                data: hechos,
                                yAxisID: 'yChoques',
                                backgroundColor: 'rgba(0, 123, 255, 0.72)',
                                borderColor: 'rgba(0, 123, 255, 1)',
                                borderWidth: 1,
                            },
                            {
                                type: 'line',
                                label: 'Lesionados',
                                data: lesionados,
                                yAxisID: 'yLesionados',
                                borderColor: 'rgba(255, 193, 7, 1)',
                                backgroundColor: 'rgba(255, 193, 7, 0.18)',
                                borderWidth: 3,
                                tension: 0.28,
                                pointRadius: 4,
                                fill: false,
                            },
                            {
                                type: 'line',
                                label: 'Defunciones',
                                data: defunciones,
                                yAxisID: 'yDefunciones',
                                borderColor: 'rgba(220, 53, 69, 1)',
                                backgroundColor: 'rgba(220, 53, 69, 0.18)',
                                borderWidth: 3,
                                tension: 0.28,
                                pointRadius: 4,
                                fill: false,
                            },
                            {
                                type: 'line',
                                label: 'Proyección hechos',
                                data: proyeccionHechos,
                                yAxisID: 'yChoques',
                                borderColor: 'rgba(23, 162, 184, 1)',
                                backgroundColor: 'rgba(23, 162, 184, 0.16)',
                                borderWidth: 3,
                                borderDash: [8, 6],
                                tension: 0.28,
                                pointRadius: 5,
                                fill: false,
                                spanGaps: false,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            yChoques: {
                                beginAtZero: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Hechos',
                                },
                                ticks: {
                                    precision: 0,
                                },
                            },
                            yLesionados: {
                                beginAtZero: true,
                                position: 'right',
                                suggestedMax: Math.max(10, Math.ceil(maxLesionados * 1.18)),
                                grid: {
                                    drawOnChartArea: false,
                                },
                                title: {
                                    display: true,
                                    text: 'Lesionados',
                                },
                                ticks: {
                                    precision: 0,
                                },
                            },
                            yDefunciones: {
                                beginAtZero: true,
                                position: 'right',
                                suggestedMax: Math.max(10, Math.ceil(maxDefunciones * 1.25)),
                                grid: {
                                    drawOnChartArea: false,
                                },
                                title: {
                                    display: true,
                                    text: 'Defunciones',
                                },
                                ticks: {
                                    precision: 0,
                                },
                            },
                        },
                    },
                });

                const updateAxisVisibility = () => {
                    const showChoques = !comparativaChart.getDatasetMeta(0).hidden || !comparativaChart.getDatasetMeta(3).hidden;
                    const showLesionados = !comparativaChart.getDatasetMeta(1).hidden;
                    const showDefunciones = !comparativaChart.getDatasetMeta(2).hidden;

                    comparativaChart.options.scales.yChoques.display = showChoques;
                    comparativaChart.options.scales.yLesionados.display = showLesionados;
                    comparativaChart.options.scales.yDefunciones.display = showDefunciones;
                    comparativaChart.update();
                };

                $('.chart-series-toggle').on('change', function () {
                    const datasetIndex = Number($(this).data('dataset'));
                    comparativaChart.setDatasetVisibility(datasetIndex, this.checked);
                    updateAxisVisibility();
                });
            }

            if (canvasMismoCorte && window.Chart) {
                new Chart(canvasMismoCorte, {
                    type: 'bar',
                    data: {
                        labels: registrosMismoCorte.map((registro) => registro.anio),
                        datasets: [
                            {
                                label: 'Hechos al mismo corte',
                                data: registrosMismoCorte.map((registro) => registro.hechos),
                                backgroundColor: registrosMismoCorte.map((registro) => registro.es_actual ? 'rgba(40, 167, 69, 0.78)' : 'rgba(0, 123, 255, 0.62)'),
                                borderColor: registrosMismoCorte.map((registro) => registro.es_actual ? 'rgba(40, 167, 69, 1)' : 'rgba(0, 123, 255, 1)'),
                                borderWidth: 1,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false,
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                },
                            },
                        },
                    },
                });
            }

            $('#tabla-comparativa-anual').DataTable({
                pageLength: 25,
                order: [[0, 'desc']],
                language: {
                    emptyTable: 'No hay datos',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                    infoFiltered: '(filtrado de _MAX_ registros totales)',
                    lengthMenu: 'Mostrar _MENU_ registros',
                    loadingRecords: 'Cargando...',
                    processing: 'Procesando...',
                    search: 'Buscar:',
                    zeroRecords: 'No se encontraron resultados',
                    paginate: {
                        first: 'Primero',
                        last: 'Último',
                        next: 'Siguiente',
                        previous: 'Anterior',
                    },
                },
                responsive: true,
                lengthChange: true,
                autoWidth: false,
            });
        });
    </script>
@stop
