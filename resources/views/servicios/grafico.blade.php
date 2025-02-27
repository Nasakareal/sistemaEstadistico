@extends('adminlte::page')

@section('title', 'Gráfico de Servicios por Grúa')

@section('content_header')
    <h1>Gráfico de Servicios por Grúa</h1>
@stop

@section('content')
    <div class="row">
        <!-- Filtros -->
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Filtros</h3>
                </div>
                <div class="card-body">
                    <form id="filtro-form">
                        <div class="row">
                            <!-- Filtro por Grúa -->
                            <div class="col-md-12 col-lg-4">
                                <label for="filtro-grua">Filtrar por Grúa:</label>
                                <select id="filtro-grua" class="form-control select2" multiple>
                                    @foreach($gruasServicios as $grua)
                                        <option value="{{ $grua['nombre'] }}">{{ $grua['nombre'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Filtro por Fecha -->
                            <div class="col-md-6 col-lg-4 mt-3 mt-lg-0">
                                <label for="filtro-fecha">Filtrar por Fecha:</label>
                                <select id="filtro-fecha" class="form-control">
                                    <option value="todas">Todas</option>
                                    <option value="semana">Última Semana</option>
                                    <option value="mes">Último Mes</option>
                                    <option value="rango">Rango Personalizado</option>
                                </select>
                            </div>
                            <!-- Fechas Personalizadas -->
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

        <!-- Gráfico -->
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
        .card { margin: 20px; }
        /* Pantallas pequeñas */
        @media (max-width: 768px) {
            .chart-container { width: 100%; height: 60vh; }
            .select2 { width: 100% !important; }
        }
        /* Adaptar el gráfico */
        .chart-container { position: relative; width: 100%; height: 400px; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
@stop

@section('js')
    <!-- Incluir Chart.js y Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.select2').select2({ placeholder: 'Selecciona una o más grúas', allowClear: true });
            $('#filtro-fecha').change(function () {
                ($(this).val() === 'rango') 
                    ? $('.rango-fechas').removeClass('d-none') 
                    : $('.rango-fechas').addClass('d-none');
            });
        });

        // Recibimos los datos desde el controlador (asegúrate de que $gruasServicios tenga 'nombre', 'servicios_count' y 'fecha_ultimo_servicio')
        const serviciosData = @json($gruasServicios);
        console.log("Datos iniciales:", serviciosData);

        // Si hay entradas sin fecha, las excluimos (puedes ajustar según tu necesidad)
        const conFecha = serviciosData.filter(item => item.fecha_ultimo_servicio);

        // Ordenar por fecha_ultimo_servicio (ascendente)
        conFecha.sort((a, b) => {
            const dateA = new Date(a.fecha_ultimo_servicio.replace(' ', 'T'));
            const dateB = new Date(b.fecha_ultimo_servicio.replace(' ', 'T'));
            return dateA - dateB;
        });

        // Datos iniciales para el gráfico
        let etiquetas = conFecha.map(item => item.nombre);
        let datos = conFecha.map(item => item.servicios_count);

        const canvasElem = document.getElementById('grafico-servicios');
        if (!canvasElem) {
            console.error("No se encontró el elemento canvas");
        }
        const ctx = canvasElem.getContext('2d');
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
                plugins: { legend: { display: false }, tooltip: { enabled: true } },
                scales: {
                    x: {
                        title: { display: true, text: 'Grúas' },
                        ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 }
                    },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Servicios Registrados' }
                    }
                }
            }
        });

        // Función de filtrado y actualización del gráfico
        $('#btn-filtrar').click(function () {
            const gruasSeleccionadas = $('#filtro-grua').val();
            const filtroFecha = $('#filtro-fecha').val();
            const fechaInicio = $('#fecha-inicio').val();
            const fechaFin = $('#fecha-fin').val();

            let datosFiltrados = serviciosData.filter(item => {
                // Si se filtra por grúa
                if (gruasSeleccionadas && gruasSeleccionadas.length > 0 && !gruasSeleccionadas.includes(item.nombre)) {
                    return false;
                }
                // Si se elige "todas", se acepta sin filtrar por fecha
                if (filtroFecha === 'todas') return true;
                // Si no hay fecha, se descarta
                if (!item.fecha_ultimo_servicio) return false;
                // Convertir a formato ISO
                let fechaStr = (item.fecha_ultimo_servicio.indexOf(' ') > -1)
                    ? item.fecha_ultimo_servicio.replace(' ', 'T')
                    : item.fecha_ultimo_servicio;
                let fechaServicio = new Date(fechaStr);
                if (isNaN(fechaServicio.getTime())) return false;

                const hoy = new Date();
                const semanaAtras = new Date(hoy.getTime() - (7 * 24 * 60 * 60 * 1000));
                const mesAtras = new Date(hoy.getTime() - (30 * 24 * 60 * 60 * 1000));

                if (filtroFecha === 'semana' && fechaServicio < semanaAtras) return false;
                if (filtroFecha === 'mes' && fechaServicio < mesAtras) return false;
                if (filtroFecha === 'rango') {
                    if (!fechaInicio || !fechaFin) return false;
                    const inicio = new Date(fechaInicio + 'T00:00:00');
                    const fin = new Date(fechaFin + 'T23:59:59');
                    if (fechaServicio < inicio || fechaServicio > fin) return false;
                }
                return true;
            });

            // Ordenar los datos filtrados por fecha_ultimo_servicio (ascendente)
            datosFiltrados.sort((a, b) => {
                const dateA = new Date(a.fecha_ultimo_servicio.replace(' ', 'T'));
                const dateB = new Date(b.fecha_ultimo_servicio.replace(' ', 'T'));
                return dateA - dateB;
            });

            console.log("Datos filtrados y ordenados:", datosFiltrados);

            const etiquetasFiltradas = datosFiltrados.map(item => item.nombre);
            const datosFiltradosCount = datosFiltrados.map(item => item.servicios_count);

            chart.data.labels = etiquetasFiltradas;
            chart.data.datasets[0].data = datosFiltradosCount;
            chart.update();
        });
    </script>
@stop
