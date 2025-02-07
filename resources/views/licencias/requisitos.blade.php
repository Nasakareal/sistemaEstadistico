<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <link rel="icon" href="{{ asset('Favicons.ico') }}" type="image/x-icon">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Requisitos de Licencia</title>
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

            ul {
                list-style: none;
                padding: 0;
            }

            li {
                padding: 15px;
                background-color: #e2e8f0;
                margin-bottom: 10px;
                border-radius: 8px;
                font-size: 1rem;
                color: #333;
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
            <a href="{{ route('licencias.costos') }}">Ver Costos</a>
            <a href="{{ route('licencias.ubicaciones') }}">Ver Ubicaciones</a>
            <a href="{{ route('login') }}">Iniciar Sesión</a>
        </div>

        <!-- Contenido principal -->
        <div class="content">
            <h1>Requisitos para Tramitar la Licencia</h1>
            <ul>
                @foreach ($requisitos as $requisito)
                    <li>{{ $requisito }}</li>
                @endforeach
            </ul>

            <!-- Botón para regresar -->
            <div class="links">
                <a href="{{ url('/') }}">Regresar al Inicio</a>
            </div>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            &copy; 2025 Coordinación del Agrupamiento de Seguridad Vial. Todos los derechos reservados.
        </div>
    </body>
</html>
