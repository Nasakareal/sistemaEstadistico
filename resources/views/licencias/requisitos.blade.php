<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <link rel="icon" href="{{ asset('Favicons.ico') }}" type="image/x-icon">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Requisitos de Licencia</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root{
            --bg0:#070B14;
            --bg1:#0B1224;
            --text: rgba(234,240,255,.92);
            --muted: rgba(234,240,255,.70);
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

        /* MAIN */
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
        .pill{
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

        /* LIST */
        .list{
            margin-top: 16px;
            display:grid;
            gap: 14px;
        }

        .tramite{
            border-radius: var(--radius);
            border: 1px solid var(--stroke);
            background: linear-gradient(180deg, var(--cardA), var(--cardB));
            box-shadow: var(--shadow2);
            padding: 16px;
        }

        .tramite-head{
            display:flex;
            align-items:flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .tramite h2{
            margin: 0;
            font-size: 16px;
            font-weight: 950;
            color: rgba(234,240,255,.95);
            letter-spacing: -.2px;
            display:flex;
            align-items:center;
            gap: 10px;
        }

        .count{
            padding: 8px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(0,0,0,.14);
            font-weight: 900;
            font-size: 12px;
            color: rgba(234,240,255,.82);
            white-space: nowrap;
        }

        ul{
            list-style: none;
            padding: 0;
            margin: 0;
            display:grid;
            gap: 10px;
        }

        li{
            padding: 11px 12px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,.10);
            background: rgba(0,0,0,.14);
            color: rgba(234,240,255,.82);
            font-weight: 650;
            line-height: 1.45;
            display:flex;
            gap: 10px;
            align-items:flex-start;
        }

        li i{
            margin-top: 2px;
            opacity: .9;
            color: rgba(234,240,255,.85);
        }

        /* LINKS */
        .links{
            margin-top: 16px;
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

        /* FOOTER */
        .footer{
            text-align:center;
            padding: 18px 10px;
            color: rgba(234,240,255,.70);
            border-top: 1px solid rgba(255,255,255,.10);
            background: rgba(0,0,0,.16);
        }

        @media (max-width: 950px){
            .nav-inner{ flex-direction: column; align-items: stretch; }
            .nav-links{ justify-content: center; }
        }
    </style>
</head>

<body>

    {{-- NAVBAR --}}
    <div class="navbar-fixed">
        <div class="nav-inner">
            <a class="brand" href="{{ url('/') }}">
                <span class="badge"><i class="fa-solid fa-id-card"></i></span>
                <span>Licencias · Seguridad Vial</span>
            </a>

            <div class="nav-links">
                <a href="{{ route('apoyo.index') }}"><i class="fa-solid fa-handshake-angle"></i> Servicios</a>
                <a href="{{ route('licencias.costos') }}"><i class="fa-solid fa-dollar-sign"></i> Ver Costos</a>
                <a href="{{ route('licencias.ubicaciones') }}"><i class="fa-solid fa-location-dot"></i> Ver Ubicaciones</a>
                <a href="{{ route('login') }}"><i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión</a>
            </div>
        </div>
    </div>

    <div class="wrap">

        {{-- HERO --}}
        <div class="hero">
            <div class="pill">
                <span class="dot"></span>
                <span>Requisitos</span>
            </div>
            <h1>Requisitos para Tramitar la Licencia</h1>
            <p>Consulta la lista de documentos y condiciones por tipo de trámite.</p>
        </div>

        {{-- LISTADO --}}
        <div class="list">
            @foreach ($tramites as $nombreTramite => $requisitos)
                <div class="tramite">
                    <div class="tramite-head">
                        <h2>
                            <i class="fa-solid fa-clipboard-check"></i>
                            {{ $nombreTramite }}
                        </h2>
                        <div class="count">{{ count($requisitos) }} requisitos</div>
                    </div>

                    <ul>
                        @foreach ($requisitos as $requisito)
                            <li>
                                <i class="fa-solid fa-check"></i>
                                <span>{{ $requisito }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="links">
            <a href="{{ url('/') }}"><i class="fa-solid fa-house"></i> Regresar al Inicio</a>
        </div>

    </div>

    <div class="footer">
        &copy; 2025 Coordinación del Agrupamiento de Seguridad Vial. Todos los derechos reservados.
    </div>

</body>
</html>
