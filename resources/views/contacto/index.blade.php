<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <link rel="icon" href="{{ asset('Favicons.ico') }}" type="image/x-icon">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Contacto - Seguridad Vial</title>

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

        .contact-info {
            margin-top: 30px;
            text-align: center;
        }

        .contact-info p {
            font-size: 1rem;
            font-weight: bold;
        }

        .contact-form {
            margin-top: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: bold;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-group textarea {
            height: 120px;
            resize: none;
        }

        .form-group button {
            background-color: #004a87;
            color: white;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        .form-group button:hover {
            background-color: #003366;
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

        <!-- Barra de navegación -->
        <div class="navbar-fixed">
            <a href="{{ route('apoyo.index') }}">Servicios</a>
            <a href="{{ url('/campanas') }}">Campañas</a>
            <a href="{{ url('/contacto') }}">Contáctanos</a>
            <a href="{{ route('login') }}">Iniciar Sesión</a>
        </div>

        <div class="content">
            <h1>Contacto</h1>
            <p>Si tienes dudas, sugerencias o necesitas asistencia, no dudes en comunicarte con nosotros.</p>

            <!-- Información de contacto -->
            <div class="contact-info">
                <h2>Información de Contacto</h2>
                <p><strong>Teléfono:</strong> (443) 123-4567</p>
                <p><strong>Email:</strong> contacto@seguridadvial-mich.com</p>
                <p><strong>Dirección:</strong> Periférico Independencia #5000, col. Sentimientos de la Nación, Morelia, Michoacán</p>
            </div>

            <!-- Formulario de contacto -->
            <div class="contact-form">
                <h2>Envíanos un Mensaje</h2>
                <form action="{{ route('contacto.enviar') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" name="nombre" id="nombre" placeholder="Tu nombre" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <input type="email" name="email" id="email" placeholder="Tu correo electrónico" required>
                    </div>

                    <div class="form-group">
                        <label for="mensaje">Mensaje</label>
                        <textarea name="mensaje" id="mensaje" placeholder="Escribe tu mensaje aquí..." required></textarea>
                    </div>

                    <div class="form-group text-center">
                        <button type="submit">Enviar Mensaje</button>
                    </div>
                </form>
            </div>

            <!-- Botón de regreso -->
            <div class="links">
                <a href="{{ url('/') }}">Regresar al Inicio</a>
            </div>
        </div>

        <div class="footer">
            &copy; 2025 Coordinación del Agrupamiento de Seguridad Vial. Todos los derechos reservados.
        </div>
    </body>
</html>
