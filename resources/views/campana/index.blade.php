<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <link rel="icon" href="{{ asset('Favicons.ico') }}" type="image/x-icon">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Campañas - Seguridad Vial</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

        <!-- Styles -->
        <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            display: flex;
            flex-direction: column;
            font-family: 'Nunito', sans-serif;
            background-color: #f7fafc;
            color: #333;
        }

        .header {
            background-color: #004a87;
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .navbar-fixed {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid #ddd;
            padding: 10px 20px;
            display: flex;
            justify-content: space-around;
            align-items: center;
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
            flex: 1;
        }

        .content h1 {
            font-size: 2rem;
            text-align: center;
            color: #004a87;
        }

        .content p {
            font-size: 1rem;
            line-height: 1.5;
            margin-top: 20px;
            text-align: justify;
        }

        .row {
            display: flex;
            align-items: center;
            justify-content: space-around;
            margin: 40px 0;
        }

        .row img {
            width: 300px;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .row .description {
            max-width: 400px;
            padding: 20px;
        }

        .description h2 {
            font-size: 1.5rem;
            color: #004a87;
            margin-bottom: 10px;
        }

        .description p {
            font-size: 1rem;
            line-height: 1.5;
            text-align: justify;
        }

        .links {
            margin-top: 30px;
            text-align: center;
        }

        .links a {
            margin: 0 15px;
            color: #004a87;
            font-weight: bold;
            text-decoration: none;
        }

        .links a:hover {
            text-decoration: underline;
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
            <a href="{{ route('campanas.index') }}">Campañas</a>
            <a href="{{ url('/contacto') }}">Contáctanos</a>
            <a href="{{ route('login') }}">Iniciar Sesión</a>
        </div>

        <div class="content">
            <h1>Bienvenido a la sección de Campañas</h1>
            <p>
                Conoce nuestras campañas enfocadas en promover la seguridad vial. Estamos comprometidos con el bienestar de todos los ciudadanos.
            </p>

            <!-- Fila con imagen y descripción de la campaña -->
            <div class="row">
                <img src="{{ asset('img/vac1.jpg') }}" alt="Campaña Vacaciones Seguras">
                <div class="description">
                    <h2>Campaña Vacaciones Seguras</h2>
                    <p>
                        La campaña "Vacaciones Seguras" tiene como objetivo concienciar a los conductores sobre la importancia de manejar con precaución durante los periodos vacacionales. 
                        A través de esta iniciativa, promovemos medidas de prevención para reducir accidentes en carreteras.
                    </p>
                </div>
            </div>

            <!-- Botón opcional para regresar al inicio -->
            <div class="links">
                <a href="{{ url('/') }}">Regresar al Inicio</a>
            </div>
        </div>

        <div class="footer">
            &copy; 2025 Coordinación del Agrupamiento de Seguridad Vial. Todos los derechos reservados.
        </div>
    </body>
</html>
