<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <link rel="icon" href="{{ asset('icon.ico') }}" type="image/x-icon">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Apoyo - Seguridad Vial</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Icons (FontAwesome) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root{
            --bg0:#070B14;
            --bg1:#0B1224;
            --text: rgba(234,240,255,.92);
            --muted: rgba(234,240,255,.70);
            --muted2: rgba(234,240,255,.55);
            --stroke: rgba(255,255,255,.12);
            --cardA: rgba(255,255,255,.10);
            --cardB: rgba(255,255,255,.05);
            --shadow: 0 18px 55px rgba(0,0,0,.45);
            --shadow2: 0 12px 35px rgba(0,0,0,.35);
            --radius: 22px;
            --brand: rgba(45,168,255,.30);
            --brand2: rgba(124,92,255,.26);
        }

        *{ box-sizing: border-box; }
        html, body{ height: 100%; margin:0; padding:0; }
        body{
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(1200px 900px at 20% 10%, rgba(45,168,255,.25), transparent 60%),
                radial-gradient(1000px 800px at 80% 20%, rgba(124,92,255,.22), transparent 55%),
                radial-gradient(900px 650px at 60% 85%, rgba(25,211,140,.12), transparent 60%),
                linear-gradient(180deg, var(--bg0), var(--bg1) 60%, #050813);
            display:flex;
            flex-direction: column;
        }

        /* NAVBAR */
        .navbar-fixed{
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 12px 14px;
            display:flex;
            align-items:center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(180deg, rgba(7,11,20,.90), rgba(7,11,20,.58));
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(255,255,255,.10);
        }
        .nav-inner{
            width: min(1100px, 100%);
            display:flex;
            align-items:center;
            justify-content: space-between;
            gap: 14px;
        }
        .brand{
            display:flex;
            align-items:center;
            gap: 10px;
            text-decoration:none;
            color: rgba(234,240,255,.95);
            font-weight: 900;
            letter-spacing: .2px;
            white-space: nowrap;
        }
        .brand .badge{
            width: 36px;
            height: 36px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.14);
            background: linear-gradient(135deg, var(--brand), var(--brand2));
            display:grid;
            place-items:center;
            box-shadow: var(--shadow2);
        }
        .brand .badge i{ color: rgba(255,255,255,.95); }

        .nav-links{
            display:flex;
            align-items:center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .nav-links a{
            text-decoration:none;
            color: rgba(234,240,255,.82);
            font-weight: 800;
            font-size: 13px;
            padding: 10px 12px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.10);
            background: rgba(0,0,0,.14);
            transition: .18s ease;
        }
        .nav-links a:hover{
            transform: translateY(-1px);
            border-color: rgba(45,168,255,.35);
            background: linear-gradient(135deg, rgba(45,168,255,.18), rgba(124,92,255,.14));
            color: rgba(234,240,255,.95);
        }

        /* MAIN CONTAINER */
        .wrap{
            width: min(1100px, 100%);
            margin: 18px auto 28px;
            padding: 0 14px;
            flex: 1;
        }

        /* HERO */
        .hero{
            border-radius: 26px;
            border: 1px solid rgba(255,255,255,.12);
            background:
                radial-gradient(700px 280px at 20% 30%, rgba(45,168,255,.20), transparent 60%),
                radial-gradient(700px 280px at 80% 30%, rgba(124,92,255,.18), transparent 60%),
                linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.04));
            box-shadow: var(--shadow);
            overflow:hidden;
            padding: 18px 18px 16px;
            text-align:center;
        }
        .hero .pill{
            display:inline-flex;
            align-items:center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(0,0,0,.18);
            border: 1px solid rgba(255,255,255,.10);
            color: rgba(234,240,255,.85);
            font-weight: 900;
            font-size: 12px;
            letter-spacing: .35px;
        }
        .dot{
            width: 8px; height: 8px;
            border-radius: 999px;
            background: #19D38C;
            box-shadow: 0 0 0 5px rgba(25,211,140,.14);
        }
        .hero h1{
            margin: 10px 0 6px;
            font-size: clamp(22px, 2.2vw, 30px);
            font-weight: 950;
            letter-spacing: -.6px;
            color: rgba(234,240,255,.95);
        }
        .hero p{
            margin: 0;
            color: var(--muted);
            font-weight: 650;
            font-size: 13px;
            line-height: 1.55;
        }

        /* SECTION CARD */
        .section{
            margin-top: 16px;
            border-radius: var(--radius);
            border: 1px solid var(--stroke);
            background: linear-gradient(180deg, var(--cardA), var(--cardB));
            box-shadow: var(--shadow2);
            padding: 16px;
        }

        .rowx{
            display:grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 16px;
            align-items: center;
            margin-top: 14px;
        }
        @media (max-width: 900px){
            .rowx{ grid-template-columns: 1fr; }
            .nav-inner{ flex-direction: column; align-items: stretch; }
            .nav-links{ justify-content: center; }
        }

        .media{
            border-radius: 22px;
            overflow:hidden;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(0,0,0,.18);
            box-shadow: 0 10px 28px rgba(0,0,0,.25);
            position: relative;
        }
        .media img{
            width: 100%;
            height: 320px;
            object-fit: cover;
            display:block;
            filter: saturate(1.05) contrast(1.02);
        }
        @media (max-width: 900px){
            .media img{ height: 240px; }
        }

        .content{
            padding: 10px 10px 10px 2px;
        }
        .content h2{
            margin: 0 0 8px;
            font-size: 18px;
            font-weight: 950;
            letter-spacing: -.2px;
            color: rgba(234,240,255,.95);
        }
        .content p{
            margin: 0;
            color: var(--muted);
            font-weight: 650;
            line-height: 1.6;
            font-size: 13.5px;
            text-align: justify;
        }

        /* ACTIONS */
        .actions{
            margin-top: 14px;
            display:flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn{
            display:inline-flex;
            align-items:center;
            gap: 8px;
            border-radius: 14px;
            font-weight: 900;
            text-decoration:none;
            padding: 10px 12px;
            border: 1px solid rgba(255,255,255,.12);
            color: rgba(234,240,255,.95);
            background: rgba(0,0,0,.16);
            transition: .18s ease;
        }
        .btn:hover{
            transform: translateY(-1px);
            border-color: rgba(45,168,255,.45);
            background: linear-gradient(135deg, rgba(45,168,255,.22), rgba(124,92,255,.18));
        }
        .btn-primary{
            border-color: rgba(45,168,255,.35);
            background: linear-gradient(135deg, rgba(45,168,255,.25), rgba(124,92,255,.22));
        }

        /* FOOTER */
        .footer{
            text-align:center;
            padding: 18px 10px;
            color: rgba(234,240,255,.70);
            border-top: 1px solid rgba(255,255,255,.10);
            background: rgba(0,0,0,.16);
        }

        .links{
            margin-top: 14px;
            text-align:center;
        }
        .links a{
            color: rgba(234,240,255,.82);
            font-weight: 850;
            text-decoration:none;
            padding: 10px 12px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.10);
            background: rgba(0,0,0,.14);
            display:inline-block;
            transition: .18s ease;
        }
        .links a:hover{
            transform: translateY(-1px);
            border-color: rgba(45,168,255,.35);
            background: linear-gradient(135deg, rgba(45,168,255,.18), rgba(124,92,255,.14));
            color: rgba(234,240,255,.95);
        }
    </style>
</head>

<body>

    {{-- NAVBAR --}}
    <div class="navbar-fixed">
        <div class="nav-inner">
            <a class="brand" href="{{ url('/') }}">
                <span class="badge"><i class="fa-solid fa-shield-halved"></i></span>
                <span>Seguridad Vial · Michoacán</span>
            </a>

            <div class="nav-links">
                <a href="{{ route('apoyo.index') }}"><i class="fa-solid fa-handshake-angle"></i> Servicios</a>
                <a href="{{ url('/campanas') }}"><i class="fa-solid fa-bullhorn"></i> Campañas</a>
                <a href="{{ url('/contacto') }}"><i class="fa-solid fa-envelope"></i> Contáctanos</a>
                <a href="{{ route('login') }}"><i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión</a>
            </div>
        </div>
    </div>

    <div class="wrap">

        {{-- HERO --}}
        <div class="hero">
            <div class="pill">
                <span class="dot"></span>
                <span>Apoyo y Servicios</span>
            </div>
            <h1>Bienvenido a la sección de Apoyo</h1>
            <p>
                Aquí encontrarás información sobre servicios y orientación. Si necesitas asistencia,
                explora esta sección o navega a las demás áreas.
            </p>
        </div>

        {{-- SECCIÓN 1 --}}
        <div class="section">
            <div class="rowx">
                <div class="media">
                    <img src="{{ asset('img/seg1.jpg') }}" alt="Servicio de Apoyo">
                </div>
                <div class="content">
                    <h2>Asistencia a Personas en Situación de Pérdida</h2>
                    <p>
                        Este servicio está diseñado para brindar apoyo a personas que se encuentren en una situación de vulnerabilidad o pérdida.
                        Contamos con personal capacitado para orientar y asistir en todo momento, garantizando la seguridad y tranquilidad de los ciudadanos.
                    </p>
                </div>
            </div>
        </div>

        {{-- SECCIÓN 2 --}}
        <div class="section">
            <div class="rowx">
                <div class="media">
                    <img src="{{ asset('img/lic2.jpg') }}" alt="Exámenes de manejo">
                </div>
                <div class="content">
                    <h2>Exámenes de Manejo</h2>
                    <p>
                        Este servicio está diseñado para facilitar la realización del examen de manejo, requisito fundamental para tramitar una licencia de conducir.
                        Contamos con personal especializado y equipos adecuados para evaluar los conocimientos teóricos y habilidades prácticas de conducción,
                        garantizando que los solicitantes cumplan con las normativas de seguridad vial establecidas. Nuestro compromiso es ofrecer una experiencia
                        eficiente, segura y transparente, enfocada en preparar conductores responsables para la prevención de accidentes y el bienestar de la comunidad.
                    </p>

                    <div class="actions">
                        <a href="{{ route('licencias.requisitos') }}" class="btn btn-primary">
                            <i class="fa-solid fa-list-check"></i> Requisitos
                        </a>
                        <a href="{{ route('licencias.costos') }}" class="btn">
                            <i class="fa-solid fa-dollar-sign"></i> Costos
                        </a>
                        <a href="{{ route('licencias.ubicaciones') }}" class="btn">
                            <i class="fa-solid fa-location-dot"></i> Ubicaciones
                        </a>
                    </div>
                </div>
            </div>

            <div class="links">
                <a href="{{ url('/') }}"><i class="fa-solid fa-house"></i> Regresar al Inicio</a>
            </div>
        </div>

    </div>

    <div class="footer">
        &copy; 2025 Coordinación del Agrupamiento de Seguridad Vial. Todos los derechos reservados.
    </div>

</body>
</html>
