<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" href="{{ asset('Favicons.ico') }}" type="image/x-icon">
  <title>Seguridad Vial - Michoacán</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --bg0:#070B14;
      --bg1:#0B1224;
      --card: rgba(255,255,255,.08);
      --card2: rgba(255,255,255,.06);
      --stroke: rgba(255,255,255,.14);
      --text:#EAF0FF;
      --muted: rgba(234,240,255,.75);
      --muted2: rgba(234,240,255,.55);
      --brand:#2DA8FF;
      --brand2:#7C5CFF;
      --ok:#19D38C;
      --warn:#FFCC66;
      --shadow: 0 18px 55px rgba(0,0,0,.45);
      --shadow2: 0 12px 35px rgba(0,0,0,.35);
      --radius: 18px;
      --radius2: 22px;
      --container: 1180px;
    }

    *{ box-sizing: border-box; }
    html, body { height: 100%; }
    body{
      margin:0;
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      color: var(--text);
      background:
        radial-gradient(1200px 900px at 20% 10%, rgba(45,168,255,.25), transparent 60%),
        radial-gradient(1000px 800px at 80% 20%, rgba(124,92,255,.22), transparent 55%),
        radial-gradient(900px 650px at 60% 85%, rgba(25,211,140,.12), transparent 60%),
        linear-gradient(180deg, var(--bg0), var(--bg1) 60%, #050813);
      overflow-x: hidden;
    }

    a{ color: inherit; text-decoration: none; }

    .container{
      width: min(var(--container), calc(100% - 44px));
      margin: 0 auto;
    }

    /* Topbar */
    .topbar{
      position: sticky;
      top: 0;
      z-index: 50;
      background: linear-gradient(180deg, rgba(7,11,20,.88), rgba(7,11,20,.55));
      backdrop-filter: blur(14px);
      border-bottom: 1px solid rgba(255,255,255,.08);
    }

    .topbar-inner{
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding: 14px 0;
      gap: 16px;
    }

    .brand{
      display:flex;
      align-items:center;
      gap: 12px;
      min-width: 260px;
    }

    .brand-badge{
      width: 42px;
      height: 42px;
      border-radius: 12px;
      background: radial-gradient(circle at 30% 20%, rgba(45,168,255,.9), rgba(124,92,255,.75));
      display:grid;
      place-items:center;
      box-shadow: 0 10px 30px rgba(45,168,255,.18);
      border: 1px solid rgba(255,255,255,.18);
      overflow: hidden;
    }

    .brand-badge img{
      width: 30px;
      height: 30px;
      object-fit: contain;
      filter: drop-shadow(0 6px 10px rgba(0,0,0,.35));
    }

    .brand-text{
      display:flex;
      flex-direction:column;
      line-height: 1.05;
    }

    .brand-text .title{
      font-weight: 800;
      letter-spacing: .2px;
      font-size: 14.5px;
      color: rgba(234,240,255,.92);
    }

    .brand-text .subtitle{
      font-weight: 500;
      font-size: 12.5px;
      color: var(--muted2);
    }

    .nav{
      display:flex;
      align-items:center;
      gap: 10px;
      flex-wrap: wrap;
      justify-content: flex-end;
    }

    .nav a{
      padding: 10px 12px;
      border-radius: 12px;
      color: rgba(234,240,255,.85);
      border: 1px solid transparent;
      transition: .18s ease;
      font-weight: 600;
      font-size: 13.5px;
      letter-spacing: .2px;
    }

    .nav a:hover{
      border-color: rgba(255,255,255,.14);
      background: rgba(255,255,255,.06);
      transform: translateY(-1px);
    }

    .cta{
      display:flex;
      align-items:center;
      gap: 10px;
      margin-left: 6px;
    }

    .btn{
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.06);
      color: rgba(234,240,255,.92);
      padding: 10px 14px;
      border-radius: 12px;
      font-weight: 700;
      font-size: 13.5px;
      transition: .18s ease;
      display:inline-flex;
      align-items:center;
      gap: 10px;
      white-space: nowrap;
    }

    .btn:hover{
      transform: translateY(-1px);
      background: rgba(255,255,255,.10);
      border-color: rgba(255,255,255,.18);
    }

    .btn-primary{
      border: 1px solid rgba(45,168,255,.35);
      background: linear-gradient(135deg, rgba(45,168,255,.25), rgba(124,92,255,.22));
      box-shadow: 0 18px 55px rgba(0,0,0,.30);
    }
    .btn-primary:hover{
      border-color: rgba(45,168,255,.55);
      background: linear-gradient(135deg, rgba(45,168,255,.34), rgba(124,92,255,.30));
    }

    /* Hero */
    .hero{
      padding: 34px 0 10px;
    }

    .hero-grid{
      display:grid;
      grid-template-columns: 1.15fr .85fr;
      gap: 18px;
      align-items: stretch;
    }

    .hero-card{
      background: linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.05));
      border: 1px solid rgba(255,255,255,.12);
      border-radius: var(--radius2);
      box-shadow: var(--shadow);
      overflow: hidden;
      position: relative;
    }

    .hero-card::before{
      content:"";
      position:absolute;
      inset:-2px;
      background:
        radial-gradient(900px 350px at 20% 10%, rgba(45,168,255,.22), transparent 60%),
        radial-gradient(900px 350px at 80% 20%, rgba(124,92,255,.20), transparent 60%);
      pointer-events:none;
      filter: blur(10px);
      opacity: .9;
    }

    .hero-inner{
      position: relative;
      padding: 28px;
    }

    .kicker{
      display:inline-flex;
      align-items:center;
      gap: 10px;
      padding: 8px 12px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(0,0,0,.18);
      color: rgba(234,240,255,.88);
      font-weight: 700;
      font-size: 12.5px;
      letter-spacing: .3px;
    }

    .dot{
      width: 8px;
      height: 8px;
      border-radius: 999px;
      background: var(--ok);
      box-shadow: 0 0 0 5px rgba(25,211,140,.14);
    }

    h1{
      margin: 14px 0 10px;
      font-size: 42px;
      line-height: 1.06;
      letter-spacing: -1px;
    }

    .lead{
      color: var(--muted);
      font-size: 15.5px;
      line-height: 1.55;
      max-width: 60ch;
      margin: 0 0 18px;
    }

    .hero-actions{
      display:flex;
      gap: 12px;
      flex-wrap: wrap;
      margin-top: 12px;
    }

    .metrics{
      display:grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-top: 18px;
    }

    .metric{
      padding: 14px 14px;
      border-radius: 16px;
      border: 1px solid rgba(255,255,255,.10);
      background: rgba(255,255,255,.05);
      box-shadow: var(--shadow2);
    }

    .metric .n{
      font-size: 18px;
      font-weight: 900;
      letter-spacing: .2px;
    }
    .metric .d{
      font-size: 12.5px;
      color: var(--muted2);
      margin-top: 4px;
      line-height: 1.25;
    }

    /* Right panel */
    .side{
      display:flex;
      flex-direction: column;
      gap: 14px;
    }

    .panel{
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.12);
      border-radius: var(--radius2);
      box-shadow: var(--shadow2);
      padding: 18px;
    }

    .panel h3{
      margin: 0 0 10px;
      font-size: 14px;
      letter-spacing: .2px;
      color: rgba(234,240,255,.92);
    }

    .panel p{
      margin: 0;
      color: var(--muted);
      font-size: 13.5px;
      line-height: 1.5;
    }

    .pill-row{
      display:flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 12px;
    }

    .pill{
      font-size: 12.5px;
      font-weight: 700;
      padding: 8px 10px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,.12);
      background: rgba(0,0,0,.18);
      color: rgba(234,240,255,.80);
    }

    .tiktok{
      padding: 0;
      overflow: hidden;
    }

    .tiktok-head{
      padding: 16px 18px;
      display:flex;
      align-items:center;
      justify-content: space-between;
      gap: 12px;
      border-bottom: 1px solid rgba(255,255,255,.10);
      background: rgba(0,0,0,.12);
    }

    .tiktok-head strong{
      font-size: 13.5px;
      letter-spacing: .2px;
    }

    .tiktok-body{
      padding: 16px 14px 18px;
      display:flex;
      justify-content:center;
    }

    /* Sections */
    .section{
      padding: 18px 0 38px;
    }

    .section-head{
      display:flex;
      align-items:flex-end;
      justify-content: space-between;
      gap: 14px;
      margin: 10px 0 14px;
    }

    .section-head h2{
      margin:0;
      font-size: 18px;
      letter-spacing: -.2px;
    }

    .section-head span{
      color: var(--muted2);
      font-size: 13px;
    }

    .cards{
      display:grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
    }

    .card{
      border-radius: var(--radius2);
      border: 1px solid rgba(255,255,255,.12);
      background: rgba(255,255,255,.06);
      box-shadow: var(--shadow2);
      padding: 16px;
      transition: .18s ease;
    }

    .card:hover{
      transform: translateY(-2px);
      background: rgba(255,255,255,.08);
      border-color: rgba(255,255,255,.18);
    }

    .card .icon{
      width: 44px;
      height: 44px;
      border-radius: 14px;
      display:grid;
      place-items:center;
      border: 1px solid rgba(255,255,255,.14);
      background: linear-gradient(135deg, rgba(45,168,255,.18), rgba(124,92,255,.14));
      margin-bottom: 10px;
    }

    .card h4{
      margin: 0 0 6px;
      font-size: 14.5px;
      letter-spacing: .2px;
    }

    .card p{
      margin: 0 0 12px;
      color: var(--muted);
      font-size: 13.5px;
      line-height: 1.5;
    }

    .card a{
      display:inline-flex;
      align-items:center;
      gap: 8px;
      font-weight: 800;
      font-size: 13px;
      color: rgba(234,240,255,.90);
      padding: 10px 12px;
      border-radius: 12px;
      background: rgba(0,0,0,.18);
      border: 1px solid rgba(255,255,255,.12);
      transition: .18s ease;
    }
    .card a:hover{
      background: rgba(0,0,0,.25);
      border-color: rgba(255,255,255,.18);
    }

    /* Footer */
    .footer{
      border-top: 1px solid rgba(255,255,255,.10);
      padding: 18px 0 26px;
      color: rgba(234,240,255,.62);
      font-size: 12.5px;
    }

    .footer-inner{
      display:flex;
      align-items:center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }

    .footer a{
      color: rgba(234,240,255,.78);
      font-weight: 700;
    }

    /* Responsive */
    @media (max-width: 980px){
      h1{ font-size: 36px; }
      .hero-grid{ grid-template-columns: 1fr; }
      .metrics{ grid-template-columns: 1fr; }
      .cards{ grid-template-columns: 1fr; }
      .brand{ min-width: unset; }
      .nav{ justify-content: flex-start; }
    }

    @media (max-width: 520px){
      h1{ font-size: 30px; }
      .topbar-inner{ align-items:flex-start; }
      .nav a{ padding: 10px 10px; }
      .btn{ width: 100%; justify-content:center; }
      .hero-actions{ flex-direction: column; }
    }
  </style>
</head>

<body>
  <!-- TOPBAR -->
  <div class="topbar">
    <div class="container">
      <div class="topbar-inner">
        <div class="brand">
          <div class="brand-badge" aria-hidden="true">
            <img src="{{ asset('Favicons.ico') }}" alt="">
          </div>
          <div class="brand-text">
            <div class="title">Coordinación del Agrupamiento de Seguridad Vial</div>
            <div class="subtitle">Secretaría de Seguridad Pública · Michoacán</div>
          </div>
        </div>

        <div class="nav">
          <a href="{{ url('/apoyo') }}">Servicios</a>
          <a href="{{ url('/campanas') }}">Campañas</a>
          <a href="{{ url('/contacto') }}">Contáctanos</a>
          <div class="cta">
            <a class="btn btn-primary" href="{{ route('login') }}">Iniciar Sesión</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- HERO -->
  <section class="hero">
    <div class="container">
      <div class="hero-grid">
        <div class="hero-card">
          <div class="hero-inner">
            <div class="kicker"><span class="dot"></span> Operativo · Prevención · Respuesta</div>

            <h1>Seguridad vial con enfoque operativo y datos confiables.</h1>

            <p class="lead">
              Un portal institucional para informar, orientar y mejorar la seguridad vial en Michoacán:
              campañas, servicios, y acceso a sistemas internos para registro y seguimiento.
            </p>

            <div class="hero-actions">
              <a class="btn btn-primary" href="{{ route('login') }}">Entrar al sistema</a>
              <a class="btn" href="{{ url('/apoyo') }}">Ver servicios</a>
              <a class="btn" href="https://www.tiktok.com/@sseguridadmich" target="_blank" rel="noopener">TikTok oficial</a>
            </div>

            <div class="metrics">
              <div class="metric">
                <div class="n">Prevención</div>
                <div class="d">Campañas y recomendaciones actualizadas para reducir siniestros.</div>
              </div>
              <div class="metric">
                <div class="n">Respuesta</div>
                <div class="d">Orientación y canales para atención, trámites y seguimiento.</div>
              </div>
              <div class="metric">
                <div class="n">Transparencia</div>
                <div class="d">Información clara, accesible y trazable para la ciudadanía.</div>
              </div>
            </div>
          </div>
        </div>

        <div class="side">
          <div class="panel">
            <h3>Qué encontrarás aquí</h3>
            <p>
              Información oficial sobre campañas, servicios y materiales de orientación.
              Si cuentas con acceso, podrás iniciar sesión para operar módulos internos.
            </p>
            <div class="pill-row">
              <span class="pill">Campañas</span>
              <span class="pill">Servicios</span>
              <span class="pill">Orientación</span>
              <span class="pill">Acceso interno</span>
            </div>
          </div>

          <div class="panel tiktok">
            <div class="tiktok-head">
              <strong>Último video</strong>
              <a class="btn" style="padding:8px 10px; font-size:12.5px;" href="https://www.tiktok.com/@sseguridadmich" target="_blank" rel="noopener">Ver perfil</a>
            </div>
            <div class="tiktok-body">
              <blockquote class="tiktok-embed"
                cite="https://www.tiktok.com/@sseguridadmich/video/7471296574182771974"
                data-video-id="7471296574182771974"
                style="max-width: 605px;min-width: 325px;">
                <section>Ver video en <a href="https://www.tiktok.com/@sseguridadmich">TikTok</a></section>
              </blockquote>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- QUICK LINKS -->
  <section class="section">
    <div class="container">
      <div class="section-head">
        <h2>Accesos rápidos</h2>
        <span>Información institucional y canales de apoyo</span>
      </div>

      <div class="cards">
        <div class="card">
          <div class="icon">🛟</div>
          <h4>Servicios</h4>
          <p>Conoce los servicios disponibles, orientación y recursos para la ciudadanía.</p>
          <a href="{{ url('/apoyo') }}">Abrir servicios →</a>
        </div>

        <div class="card">
          <div class="icon">📣</div>
          <h4>Campañas</h4>
          <p>Materiales, recomendaciones y acciones preventivas en el estado.</p>
          <a href="{{ url('/campanas') }}">Ver campañas →</a>
        </div>

        <div class="card">
          <div class="icon">✉️</div>
          <h4>Contacto</h4>
          <p>Canales de comunicación para dudas, orientación y seguimiento.</p>
          <a href="{{ url('/contacto') }}">Ir a contacto →</a>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <div class="footer">
    <div class="container">
      <div class="footer-inner">
        <div>
          &copy; 2025 Coordinación del Agrupamiento de Seguridad Vial. Todos los derechos reservados.
        </div>
        <div>
          <a href="{{ route('login') }}">Iniciar Sesión</a>
          <span style="opacity:.45;"> · </span>
          <a href="https://www.tiktok.com/@sseguridadmich" target="_blank" rel="noopener">TikTok</a>
        </div>
      </div>
    </div>
  </div>

  <script async src="https://www.tiktok.com/embed.js"></script>
</body>
</html>
