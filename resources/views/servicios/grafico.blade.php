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
                    <h3 class="card-title">Cantidad de Servicios por Grúa</h3>
                </div>
                <div class="card-body">
                    <!-- Contenedor del gráfico -->
                    <canvas id="grafico-servicios"></canvas>
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
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const serviciosData = @json($gruasServicios);

        const etiquetas = serviciosData.map(grua => grua.nombre);
        const datos = serviciosData.map(grua => grua.servicios_count);

        const ctx = document.getElementById('grafico-servicios').getContext('2d');
        new Chart(ctx, {
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
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                },
                scales: {
                    x: {
                        title: { display: true, text: 'Grúas' }
                    },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Servicios Registrados' }
                    }
                }
            }
        });
    </script>
@stop
