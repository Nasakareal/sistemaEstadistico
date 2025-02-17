@extends('adminlte::page')

@section('title', 'Gráfico de Servicios por Grúa')

@section('content_header')
    <h1>Gráfico de Servicios por Grúa</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Filtros</h3>
                </div>
                <div class="card-body">
                    <form id="filtro-form">
                        <div class="row">
                            <!-- Filtro por grúa -->
                            <div class="col-md-12 col-lg-4">
                                <label for="filtro-grua">Filtrar por Grúa:</label>
                                <select id="filtro-grua" class="form-control select2" multiple>
                                    @foreach($gruasServicios as $grua)
                                        <option value="{{ $grua['nombre'] }}">{{ $grua['nombre'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filtro por rango de fechas -->
                            <div class="col-md-6 col-lg-4 mt-3 mt-lg-0">
                                <label for="filtro-fecha">Filtrar por Fecha:</label>
                                <select id="filtro-fecha" class="form-control">
                                    <option value="todas">Todas</option>
                                    <option value="semana">Última Semana</option>
                                    <option value="mes">Último Mes</option>
                                    <option value="rango">Rango Personalizado</option>
                                </select>
                            </div>

                            <!-- Fechas personalizadas -->
                            <div class="col-md-6 col-lg-4 mt-3 mt-lg-0 rango-fechas d-none">
                                <label>Selecciona un rango de fechas:</label>
                                <input type="date" id="fecha-inicio" class="form-control">
                                <input type="date" id="fecha-fin" class="form-control mt-2">
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary mt-3 w-100" id="btn-filtrar">
                            <i class="fa-solid fa-filter"></i> Aplicar Filtros
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Contenedor del gráfico -->
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Cantidad de Servicios por Grúa</h3>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="grafico-servicios"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .card {
            margin: 20px;
        }

        /* Ajustes para pantallas pequeñas */
        @media (max-width: 768px) {
            .chart-container {
                width: 100%;
                height: 60vh; /* Ajusta el alto en móviles */
            }
            .select2 {
                width: 100% !important;
            }
        }

        /* Asegurar que el gráfico se adapta */
        .chart-container {
            position: relative;
            width: 100%;
            height: 400px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.select2').select2({ placeholder: 'Selecciona una o más grúas', allowClear: true });

            $('#filtro-fecha').change(function () {
                if ($(this).val() === 'rango') {
                    $('.rango-fechas').removeClass('d-none');
                } else {
                    $('.rango-fechas').addClass('d-none');
                }
            });
        });

        const serviciosData = @json($gruasServicios);
        let etiquetas = serviciosData.map(grua => grua.nombre);
        let datos = serviciosData.map(grua => grua.servicios_count);

        const ctx = document.getElementById('grafico-servicios').getContext('2d');
        let chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: etiquetas,
                datasets: [{
                    label: 'Cantidad de Servicios',
                    data: datos,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                },
                scales: {
                    x: {
                        title: { display: true, text: 'Grúas' },
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 0
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Servicios Registrados' }
                    }
                }
            }
        });

        $('#btn-filtrar').click(function () {
            let gruasSeleccionadas = $('#filtro-grua').val();
            let filtroFecha = $('#filtro-fecha').val();
            let fechaInicio = $('#fecha-inicio').val();
            let fechaFin = $('#fecha-fin').val();

            let datosFiltrados = serviciosData.filter(grua => {
                let incluir = true;

                // Filtrar por grúa
                if (gruasSeleccionadas && gruasSeleccionadas.length > 0) {
                    incluir = gruasSeleccionadas.includes(grua.nombre);
                }

                // Filtrar por fecha
                let fechaServicio = new Date(grua.fecha_ultimo_servicio);
                let hoy = new Date();
                let semanaAtras = new Date();
                semanaAtras.setDate(hoy.getDate() - 7);
                let mesAtras = new Date();
                mesAtras.setMonth(hoy.getMonth() - 1);

                if (filtroFecha === 'semana' && fechaServicio < semanaAtras) {
                    incluir = false;
                }
                if (filtroFecha === 'mes' && fechaServicio < mesAtras) {
                    incluir = false;
                }
                if (filtroFecha === 'rango') {
                    // Verificar que ambas fechas estén seleccionadas
                    if (!fechaInicio || !fechaFin) {
                        incluir = false;
                    } else {
                        // Convertir las fechas incluyendo la hora para abarcar todo el día
                        let inicio = new Date(fechaInicio + 'T00:00:00');
                        let fin = new Date(fechaFin + 'T23:59:59');
                        if (fechaServicio < inicio || fechaServicio > fin) {
                            incluir = false;
                        }
                    }
                }

                return incluir;
            });

            let etiquetasFiltradas = datosFiltrados.map(grua => grua.nombre);
            let datosFiltradosCount = datosFiltrados.map(grua => grua.servicios_count);

            chart.data.labels = etiquetasFiltradas;
            chart.data.datasets[0].data = datosFiltradosCount;
            chart.update();
        });
    </script>
@stop
