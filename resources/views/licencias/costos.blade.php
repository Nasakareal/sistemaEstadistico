<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <link rel="icon" href="{{ asset('Favicons.ico') }}" type="image/x-icon">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Costos de Licencia</title>
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

        <style>
            html, body {
                height: 100%;
                margin: 0;
                padding: 0;
                font-family: 'Nunito', sans-serif;
                background-color: #f7fafc;
                color: #333;
            }

            .navbar-fixed {
                position: sticky;
                top: 0;
                background-color: #fff;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                padding: 10px 20px;
                display: flex;
                justify-content: space-around;
                z-index: 1000;
            }

            .navbar-fixed a {
                color: #004a87;
                font-weight: bold;
                text-decoration: none;
                padding: 10px;
                border-radius: 4px;
            }

            .navbar-fixed a:hover {
                background-color: #004a87;
                color: white;
            }

            .content {
                max-width: 800px;
                margin: 40px auto;
                padding: 20px;
                background: white;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                border-radius: 8px;
            }

            h1 {
                font-size: 2rem;
                text-align: center;
                color: #004a87;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }

            th, td {
                padding: 12px;
                border: 1px solid #ddd;
                text-align: center;
            }

            th {
                background-color: #004a87;
                color: white;
            }

            .footer {
                text-align: center;
                padding: 20px;
                background-color: #004a87;
                color: white;
            }
        </style>
    </head>
    <body>
        <!-- Barra fija de navegación -->
        <div class="navbar-fixed">
            <a href="{{ route('apoyo.index') }}">Servicios</a>
            <a href="{{ route('licencias.requisitos') }}">Ver Requisitos</a>
            <a href="{{ route('licencias.ubicaciones') }}">Ver Ubicaciones</a>
            <a href="{{ route('login') }}">Iniciar Sesión</a>
        </div>

        <!-- Contenido principal -->
        <div class="content">
            <h1>Costos para Tramitar la Licencia</h1>
            <table>
                <thead>
                    <tr>
                        <th>Tipo de Licencia</th>
                        <th>Costo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($costos as $tipo => $costo)
                        <tr>
                            <td>{{ $tipo }}</td>
                            <td>{{ $costo }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Botón para regresar -->
            <div class="links" style="margin-top: 20px; text-align: center;">
                <a href="{{ url('/') }}">Regresar al Inicio</a>
            </div>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            &copy; 2025 Coordinación del Agrupamiento de Seguridad Vial. Todos los derechos reservados.
        </div>
    </body>
</html>
