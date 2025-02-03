@extends('adminlte::page')

@section('title', 'Gráfico de Servicios por Grúa')

@section('content_header')
    <h1>Gráfico de Servicios por Grúa</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Gráfico Comparativo</h3>
                </div>
                <div class="card-body">
                    <canvas id="graficoServicios"></canvas>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .chart-container {
            position: relative;
            height: 60vh;
            width: 80vw;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('graficoServicios').getContext('2d');

            // Datos dinámicos enviados desde el controlador
            const gruas = {!! json_encode($gruasServicios->pluck('nombre')) !!};
            const servicios = {!! json_encode($gruasServicios->pluck('servicios_count')) !!};

            // Crear el gráfico
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: gruas,
                    datasets: [{
                        label: 'Número de Servicios',
                        data: servicios,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderWidth: 2,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: 'Servicios por Grúa'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
@stop
