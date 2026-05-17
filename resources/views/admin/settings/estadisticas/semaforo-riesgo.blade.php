@extends('adminlte::page')

@section('title', 'Semáforo de Riesgo')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h1 class="m-0">Semáforo de Riesgo por Zona</h1>
            <small class="text-muted">Índice de severidad, tendencia y prioridades operativas</small>
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
                    <form method="GET" action="{{ route('estadisticas.semaforoRiesgo') }}" class="row align-items-end">
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group mb-md-0">
                                <label for="desde">Desde</label>
                                <input type="date" id="desde" name="desde" class="form-control" value="{{ $fechaInicio->toDateString() }}">
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group mb-md-0">
                                <label for="hasta">Hasta</label>
                                <input type="date" id="hasta" name="hasta" class="form-control" value="{{ $fechaFin->toDateString() }}">
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group mb-md-0">
                                <label for="agrupar">Agrupar por</label>
                                <select id="agrupar" name="agrupar" class="form-control">
                                    <option value="colonia" {{ $agrupar === 'colonia' ? 'selected' : '' }}>Colonia</option>
                                    <option value="sector" {{ $agrupar === 'sector' ? 'selected' : '' }}>Sector</option>
                                    <option value="calle" {{ $agrupar === 'calle' ? 'selected' : '' }}>Calle</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-sync-alt"></i> Aplicar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-6 col-12">
            <div class="risk-kpi risk-kpi--critical">
                <span>Zona prioritaria</span>
                <strong>{{ $topRiesgo ? $topRiesgo->zona : 'Sin datos' }}</strong>
                <small>{{ $topRiesgo ? number_format($topRiesgo->indice) . ' pts de severidad' : 'Sin registros' }}</small>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12">
            <div class="risk-kpi risk-kpi--warning">
                <span>Mayor incremento</span>
                <strong>{{ $topIncremento ? $topIncremento->zona : 'Sin datos' }}</strong>
                <small>
                    {{ $topIncremento ? ($topIncremento->diferencia >= 0 ? '+' : '') . number_format($topIncremento->diferencia) . ' choques vs periodo anterior' : 'Sin referencia' }}
                </small>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12">
            <div class="risk-kpi risk-kpi--info">
                <span>Zonas críticas</span>
                <strong>{{ number_format($zonasCriticas) }}</strong>
                <small>Primer 12% por severidad</small>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12">
            <div class="risk-kpi risk-kpi--calm">
                <span>Índice promedio</span>
                <strong>{{ number_format($promedioIndice, 1) }}</strong>
                <small>Choques + lesionados x3 + defunciones x10</small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 col-12">
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar"></i> Top por severidad
                    </h3>
                </div>
                <div class="card-body">
                    <div class="risk-chart">
                        <canvas id="riskChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5 col-12">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-arrow-trend-up"></i> Zonas que más subieron
                    </h3>
                </div>
                <div class="card-body">
                    <div class="risk-chart">
                        <canvas id="growthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Detalle operativo</h3>
                    <div class="card-tools">
                        <span class="badge badge-light">
                            Periodo anterior: {{ $periodoAnteriorInicio->format('d/m/Y') }} - {{ $periodoAnteriorFin->format('d/m/Y') }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="tabla-semaforo-riesgo" class="table table-bordered table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Semáforo</th>
                                    <th>Zona</th>
                                    <th>Índice</th>
                                    <th>Choques</th>
                                    <th>Lesionados</th>
                                    <th>Defunciones</th>
                                    <th>Impacto</th>
                                    <th>Periodo anterior</th>
                                    <th>Diferencia</th>
                                    <th>Variación</th>
                                    <th>Actuales</th>
                                    <th>Legacy</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($zonas as $zona)
                                    @php
                                        if ($zona->nivel === 'CRITICO') {
                                            $badge = 'danger';
                                        } elseif ($zona->nivel === 'ALTO') {
                                            $badge = 'warning';
                                        } elseif ($zona->nivel === 'MEDIO') {
                                            $badge = 'info';
                                        } else {
                                            $badge = 'success';
                                        }
                                    @endphp
                                    <tr>
                                        <td><span class="badge badge-{{ $badge }}">{{ $zona->nivel === 'CRITICO' ? 'CRÍTICO' : $zona->nivel }}</span></td>
                                        <td class="text-left">{{ $zona->zona }}</td>
                                        <td>{{ number_format($zona->indice) }}</td>
                                        <td>{{ number_format($zona->hechos) }}</td>
                                        <td>{{ number_format($zona->lesionados) }}</td>
                                        <td>{{ number_format($zona->defunciones) }}</td>
                                        <td>{{ number_format($zona->impacto, 1) }}</td>
                                        <td>{{ number_format($zona->hechos_previos) }}</td>
                                        <td class="{{ $zona->diferencia >= 0 ? 'risk-up' : 'risk-down' }}">
                                            {{ $zona->diferencia >= 0 ? '+' : '' }}{{ number_format($zona->diferencia) }}
                                        </td>
                                        <td>
                                            {{ $zona->variacion !== null ? ($zona->variacion >= 0 ? '+' : '') . number_format($zona->variacion, 1) . '%' : '-' }}
                                        </td>
                                        <td>{{ number_format($zona->hechos_actuales) }}</td>
                                        <td>{{ number_format($zona->hechos_legacy) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .risk-kpi {
            min-height: 118px;
            margin-bottom: 16px;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, .12);
            background: rgba(255, 255, 255, .05);
            box-shadow: 0 10px 30px rgba(0, 0, 0, .22);
        }

        .risk-kpi span,
        .risk-kpi small {
            display: block;
            color: rgba(255, 255, 255, .68);
            font-size: 12px;
            font-weight: 800;
        }

        .risk-kpi strong {
            display: block;
            margin: 8px 0;
            font-size: 22px;
            line-height: 1.08;
            color: rgba(255, 255, 255, .94);
        }

        .risk-kpi--critical {
            border-color: rgba(220, 53, 69, .7);
        }

        .risk-kpi--warning {
            border-color: rgba(255, 193, 7, .7);
        }

        .risk-kpi--info {
            border-color: rgba(23, 162, 184, .7);
        }

        .risk-kpi--calm {
            border-color: rgba(40, 167, 69, .7);
        }

        .risk-chart {
            min-height: 320px;
            position: relative;
        }

        #riskChart,
        #growthChart {
            min-height: 320px;
        }

        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
        }

        .risk-up {
            color: #ffc107;
            font-weight: 900;
        }

        .risk-down {
            color: #17a2b8;
            font-weight: 900;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        $(function () {
            const topRiesgo = @json($topGrafica);
            const topIncrementos = @json($topIncrementos);
            const riskCanvas = document.getElementById('riskChart');
            const growthCanvas = document.getElementById('growthChart');

            if (riskCanvas && window.Chart) {
                new Chart(riskCanvas, {
                    type: 'bar',
                    data: {
                        labels: topRiesgo.map((zona) => zona.zona),
                        datasets: [
                            {
                                label: 'Índice de severidad',
                                data: topRiesgo.map((zona) => zona.indice),
                                backgroundColor: topRiesgo.map((zona) => {
                                    if (zona.nivel === 'CRITICO') return 'rgba(220, 53, 69, .78)';
                                    if (zona.nivel === 'ALTO') return 'rgba(255, 193, 7, .78)';
                                    if (zona.nivel === 'MEDIO') return 'rgba(23, 162, 184, .72)';
                                    return 'rgba(40, 167, 69, .68)';
                                }),
                                borderWidth: 1,
                            },
                        ],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: { precision: 0 },
                            },
                        },
                    },
                });
            }

            if (growthCanvas && window.Chart) {
                new Chart(growthCanvas, {
                    type: 'bar',
                    data: {
                        labels: topIncrementos.map((zona) => zona.zona),
                        datasets: [
                            {
                                label: 'Incremento de choques',
                                data: topIncrementos.map((zona) => zona.diferencia),
                                backgroundColor: 'rgba(255, 193, 7, .76)',
                                borderColor: 'rgba(255, 193, 7, 1)',
                                borderWidth: 1,
                            },
                        ],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: { precision: 0 },
                            },
                        },
                    },
                });
            }

            $('#tabla-semaforo-riesgo').DataTable({
                pageLength: 25,
                order: [[2, 'desc']],
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
