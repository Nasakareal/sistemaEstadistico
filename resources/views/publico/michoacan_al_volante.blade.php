<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#102d47">
    <meta name="description" content="Academia vial, simulador de examen de manejo y juegos interactivos de Seguridad Vial Michoacán.">
    <title>Michoacán al Volante | Seguridad Vial</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/michoacan-al-volante.css') }}">
</head>
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>

    <header class="site-header">
        <div class="institutional-identity">
            <img class="ssp-logo" src="{{ asset('img/SSP_horizontal.png') }}" alt="Secretaría de Seguridad Pública, Gobierno de Michoacán">
            <span class="identity-divider" aria-hidden="true"></span>
            <a class="brand" href="{{ route('michoacan_al_volante') }}" data-home>
                <img class="guardia-logo" src="{{ asset('img/guardiacivil.png') }}" alt="Guardia Civil Michoacán">
                <span><strong>Michoacán al Volante</strong><small>Plataforma pública de formación vial</small></span>
            </a>
        </div>
        <nav aria-label="Navegación principal">
            <button type="button" class="nav-button" data-home>Inicio</button>
            <button type="button" class="nav-button" data-open-academy="learn">Academia</button>
            <button type="button" class="nav-button nav-primary" data-open-academy="exam">Simular examen</button>
            <a class="nav-button" href="{{ route('welcome') }}">Portal</a>
        </nav>
    </header>

    <main id="contenido"
          data-app
          data-questions-url="{{ asset('michoacan-al-volante-assets/questions.json') }}"
          data-assets-url="{{ asset('michoacan-al-volante-assets') }}">
        <section class="app-view is-active" data-view="home" aria-labelledby="hero-title">
            <div class="hero-shell">
                <div class="hero-copy">
                    <span class="eyebrow">Aprende · practica · decide</span>
                    <h1 id="hero-title">Las mejores decisiones comienzan <em>antes</em> de arrancar.</h1>
                    <p>Prepárate para conducir con seguridad mediante un simulador de examen, lecciones breves y retos viales. Es gratuito, anónimo y para todas las personas.</p>
                    <div class="hero-actions">
                        <button class="button button-primary button-large" type="button" data-open-academy="exam">Iniciar simulacro</button>
                        <button class="button button-light button-large" type="button" data-open-academy="learn">Entrar a la academia</button>
                    </div>
                    <p class="hero-note">30 preguntas · 25 minutos · meta de entrenamiento: 80%</p>
                </div>
                <div class="road-hero" aria-hidden="true">
                    <div class="sun"></div>
                    <div class="mountain mountain-one"></div>
                    <div class="mountain mountain-two"></div>
                    <div class="road">
                        <span class="road-line line-one"></span><span class="road-line line-two"></span>
                        <span class="road-line line-three"></span><span class="road-line line-four"></span>
                    </div>
                    <div class="hero-car">🚙</div>
                    <div class="road-sign">80</div>
                </div>
            </div>

            <section class="home-section" aria-labelledby="academy-title">
                <div class="section-heading">
                    <div><span class="eyebrow">Academia y simulador</span><h2 id="academy-title">Prepárate a tu ritmo</h2></div>
                    <p>Un banco de 120 situaciones reales para estudiar y poner a prueba tus decisiones.</p>
                </div>
                <div class="mode-grid">
                    <button class="mode-card learn" type="button" data-open-academy="learn">
                        <span class="mode-icon">📖</span><span><strong>Aprender</strong><small>Recorre temas y recibe la explicación al responder.</small></span><b>Explorar →</b>
                    </button>
                    <button class="mode-card practice" type="button" data-open-academy="practice">
                        <span class="mode-icon">🎯</span><span><strong>Practicar</strong><small>10 preguntas aleatorias con retroalimentación inmediata.</small></span><b>Practicar →</b>
                    </button>
                    <button class="mode-card exam" type="button" data-open-academy="exam">
                        <span class="mode-icon">⏱️</span><span><strong>Simulacro</strong><small>30 preguntas en 25 minutos, como entrenamiento.</small></span><b>Comenzar →</b>
                    </button>
                </div>
            </section>

            <section class="home-section games-section" aria-labelledby="games-title">
                <div class="section-heading">
                    <div><span class="eyebrow">Juegos de decisión</span><h2 id="games-title">Entrena la mirada, no solo la memoria</h2></div>
                    <p>Tres experiencias cortas tomadas de la app móvil.</p>
                </div>
                <div class="game-grid">
                    <button class="game-card hazard" type="button" data-open-game="hazard">
                        <span class="game-art">⚠️</span><span class="game-number">01</span><strong>Detecta el peligro</strong><small>Reflejos y observación</small><b>Jugar ahora</b>
                    </button>
                    <button class="game-card priority" type="button" data-open-game="priority">
                        <span class="game-art">🚦</span><span class="game-number">02</span><strong>¿Quién pasa primero?</strong><small>Rompecabezas de cruces</small><b>Resolver cruces</b>
                    </button>
                    <button class="game-card decision" type="button" data-open-game="decision">
                        <span class="game-art">↩️</span><span class="game-number">03</span><strong>Un segundo antes</strong><small>Cambia el desenlace</small><b>Tomar decisiones</b>
                    </button>
                </div>
            </section>

            <aside class="disclaimer">
                <span aria-hidden="true">ℹ️</span>
                <p><strong>Herramienta educativa.</strong> El resultado es únicamente de práctica: no sustituye, acredita ni modifica un examen oficial o trámite de licencia.</p>
            </aside>
        </section>

        <section class="app-view workspace" data-view="academy" aria-live="polite">
            <div class="workspace-bar">
                <button class="back-button" type="button" data-home>← Volver</button>
                <div><span class="eyebrow">Academia vial</span><h1 data-academy-title>Simulacro</h1></div>
                <div class="timer" data-timer hidden>25:00</div>
            </div>
            <div data-academy-content></div>
        </section>

        <section class="app-view workspace" data-view="game" aria-live="polite">
            <div class="workspace-bar">
                <button class="back-button" type="button" data-home>← Volver</button>
                <div><span class="eyebrow">Juego vial</span><h1 data-game-title></h1></div>
                <div class="score-pill" data-game-score>0 puntos</div>
            </div>
            <div data-game-content></div>
        </section>
    </main>

    <footer class="institutional-footer">
        <div class="footer-identity">
            <img src="{{ asset('img/logo-blanco.png') }}" alt="Secretaría de Seguridad Pública, Gobierno de Michoacán">
            <span></span>
            <img class="footer-guardia" src="{{ asset('img/guardiacivil.png') }}" alt="Guardia Civil Michoacán">
        </div>
        <div class="footer-copy"><strong>Michoacán al Volante</strong><small>Herramienta pública y gratuita de educación vial.</small></div>
        <div class="footer-motto"><strong>Tu seguridad también se aprende.</strong><small>Emergencias 911 · Denuncia anónima 089</small></div>
    </footer>

    <noscript>Activa JavaScript para utilizar el simulador y los juegos.</noscript>
    <script src="{{ asset('js/michoacan-al-volante-games.js') }}" defer></script>
    <script src="{{ asset('js/michoacan-al-volante.js') }}" defer></script>
</body>
</html>
