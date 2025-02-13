<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <link rel="icon" href="{{ asset('Favicons.ico') }}" type="image/x-icon">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Apoyo - Seguridad Vial</title>

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

        /* Barra fija superior debajo de la cabecera */
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
            <a href="{{ url('/campanas') }}">Campañas</a>
            <a href="{{ url('/contacto') }}">Contáctanos</a>
            <a href="{{ route('login') }}">Iniciar Sesión</a>
        </div>

        <div class="content">
            <h1>Bienvenido a la sección de Apoyo</h1>
            <p>
                Aquí encontrarás información detallada sobre los servicios que ofrece nuestra organización. 
                Si tienes dudas o necesitas más asistencia, no dudes en explorar otras secciones.
            </p>

            <!-- Apoyo a personas perdidas -->
            <div class="row">
                <img src="{{ asset('img/seg1.jpg') }}" alt="Servicio de Apoyo">
                <div class="description">
                    <h2>Asistencia a Personas en Situación de Pérdida</h2>
                    <p>
                        Este servicio está diseñado para brindar apoyo a personas que se encuentren en una situación de vulnerabilidad o pérdida. 
                        Contamos con personal capacitado para orientar y asistir en todo momento, garantizando la seguridad y tranquilidad de los ciudadanos.
                    </p>
                </div>
            </div>

            <!-- Examenes de Manejo -->
            <div class="row">
                <div class="col-md-12 text-center">
                    <img src="{{ asset('img/lic2.jpg') }}" alt="Servicio de Apoyo" class="img-fluid mb-3">
                </div>
                <div class="description text-center">
                    <h2>Exámenes de manejo</h2>
                    <p>
                        Este servicio está diseñado para facilitar la realización del examen de manejo, requisito fundamental para tramitar una licencia de conducir.
                        Contamos con personal especializado y equipos adecuados para evaluar los conocimientos teóricos y habilidades prácticas de conducción, garantizando que los solicitantes cumplan con las normativas de seguridad vial establecidas.
                        Nuestro compromiso es ofrecer una experiencia eficiente, segura y transparente, enfocada en preparar conductores responsables para la prevención de accidentes y el bienestar de la comunidad.
                    </p>

                    <!-- Botones de acción -->
                    <div class="mt-4">
                    <hr>
                        <a href="{{ route('licencias.requisitos') }}" class="btn btn-primary mr-2">
                            <i class="fa-solid fa-list"></i> Ver Requisitos de la Licencia
                        </a>
                        <hr>
                        <a href="{{ route('licencias.costos') }}" class="btn btn-info">
                            <i class="fa-solid fa-dollar-sign"></i> Ver Costos
                        </a>
                        <hr>
                        <a href="{{ route('licencias.ubicaciones') }}" class="btn btn-info">
                            <i class="fa-solid fa-dollar-sign"></i> Ver Ubicaciones
                        </a>
                    </div>
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
