<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <link rel="icon" href="{{ asset('Favicons.ico') }}" type="image/x-icon">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Seguridad Vial - Michoacán</title>

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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header img {
            height: 60px;
            width: 60px;
            margin-right: 15px;
        }

        .header h1 {
            margin: 0;
            font-size: 1.8rem;
            line-height: 1.2;
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

        .tiktok-container {
            margin-top: 30px;
            text-align: center;
        }

        .tiktok-container h2 {
            font-size: 1.5rem;
            color: #004a87;
            margin-bottom: 20px;
        }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="{{ asset('Favicons.ico') }}" alt="Logo">
            <h1>Coordinación del Agrupamiento de Seguridad Vial</h1>
            <img src="{{ asset('Favicons.ico') }}" alt="Logo">
        </div>

        <!-- Barra fija de navegación -->
        <div class="navbar-fixed">
            <a href="{{ url('/apoyo') }}">Servicios</a>
            <a href="{{ url('/campanas') }}">Campañas</a>
            <a href="{{ url('/contacto') }}">Contáctanos</a>
            <a href="{{ route('login') }}">Iniciar Sesión</a>
        </div>

        <div class="content">
            <h1>Bienvenidos a Seguridad Vial - Michoacán</h1>
            <p>
                La seguridad vial es una prioridad para nuestro estado. Nuestra misión es reducir accidentes de tránsito, 
                promover conductas responsables en la vía pública y garantizar que las reglas de tránsito sean respetadas 
                por todos los ciudadanos.
            </p>
            <p>
                Aquí encontrarás información sobre nuestras campañas, servicios, reglamentos y medidas para mejorar la seguridad 
                en las carreteras y calles de Michoacán.
            </p>

            <div class="links">
                <a href="https://www.tiktok.com/@sseguridadmich" target="_blank">Perfil de TikTok</a>
            </div>

            <!-- Videos de TikTok -->
            <div class="tiktok-container">
                <h2>Último video en TikTok</h2>
                <blockquote class="tiktok-embed" cite="https://www.tiktok.com/@sseguridadmich/video/7471296574182771974" data-video-id="7471296574182771974" style="max-width: 605px;min-width: 325px;">
                    <section>Ver video en <a href="https://www.tiktok.com/@sseguridadmich">TikTok</a></section>
                </blockquote>
            </div>
        </div>

        <div class="footer">
            &copy; 2025 Coordinación del Agrupamiento de Seguridad Vial. Todos los derechos reservados.
        </div>

        <!-- Script de TikTok para cargar los videos -->
        <script async src="https://www.tiktok.com/embed.js"></script>
    </body>
</html>
