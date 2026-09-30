@extends('adminlte::page')

@section('title', 'Vialytics | Inteligencia para Aseguradoras')

@section('content_header')
    <div class="vi-topbar">
        <a href="{{ route('settings.index') }}" class="vi-back" aria-label="Volver a configuraciones">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="vi-brand">
            <span class="vi-brand__mark"><i class="fas fa-wave-square"></i></span>
            <span>VIALYTICS</span>
            <span class="vi-brand__divider"></span>
            <small>Insurance Intelligence</small>
        </div>
        <nav class="vi-topnav" aria-label="Secciones de la presentación">
            <a href="#vi-command">Command center</a>
            <a href="#vi-history">Histórico</a>
            <a href="#vi-business-case">Caso de negocio</a>
            <a href="#vi-moat">Ventaja</a>
            <a href="#vi-pilot">Piloto</a>
        </nav>
        <div class="vi-topbar__actions">
            <span class="vi-private"><i class="fas fa-lock"></i> Vista privada</span>
            <button type="button" class="vi-print vi-pitch-toggle" id="viPitchToggle">
                <i class="fas fa-expand"></i> Modo presentación
            </button>
            <button type="button" class="vi-print" onclick="window.print()">
                <i class="fas fa-file-export"></i> Exportar resumen
            </button>
        </div>
    </div>
@stop

@section('js')
<script>
(() => {
    const tow = document.getElementById('viTowRange');
    const cost = document.getElementById('viCostRange');
    const rate = document.getElementById('viRateRange');
    const integer = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 0 });
    const money = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });
    const compactMoney = (value) => value >= 1000000
        ? `$${(value / 1000000).toFixed(2)} M`
        : money.format(value);

    const updateBusinessCase = () => {
        const services = Number(tow.value);
        const averageCost = Number(cost.value);
        const reviewRate = Number(rate.value) / 100;
        const reviewServices = Math.round(services * reviewRate);
        const annualSpend = services * averageCost;
        const opportunity = reviewServices * averageCost;

        document.getElementById('viTowOutput').textContent = integer.format(services);
        document.getElementById('viCostOutput').textContent = money.format(averageCost);
        document.getElementById('viRateOutput').textContent = `${Number(rate.value).toFixed(1)}%`;
        document.getElementById('viOpportunity').textContent = compactMoney(opportunity);
        document.getElementById('viOpportunityDetail').textContent = `${integer.format(reviewServices)} servicios entrarían a una revisión priorizada.`;
        document.getElementById('viAnnualSpend').textContent = compactMoney(annualSpend);
        document.getElementById('viSignalCost').textContent = money.format(averageCost);
    };

    [tow, cost, rate].forEach((control) => control.addEventListener('input', updateBusinessCase));
    updateBusinessCase();

    const pitchButton = document.getElementById('viPitchToggle');
    const syncPitchButton = () => {
        const active = document.body.classList.contains('vi-pitch-mode');
        pitchButton.innerHTML = active
            ? '<i class="fas fa-compress"></i> Salir de presentación'
            : '<i class="fas fa-expand"></i> Modo presentación';
    };

    pitchButton.addEventListener('click', async () => {
        document.body.classList.toggle('vi-pitch-mode');
        if (document.body.classList.contains('vi-pitch-mode') && !document.fullscreenElement) {
            try { await document.documentElement.requestFullscreen(); } catch (error) { /* El modo visual sigue activo. */ }
        } else if (document.fullscreenElement) {
            try { await document.exitFullscreen(); } catch (error) { /* Sin impacto en la vista. */ }
        }
        syncPitchButton();
    });

    document.addEventListener('fullscreenchange', () => {
        if (!document.fullscreenElement) document.body.classList.remove('vi-pitch-mode');
        syncPitchButton();
    });
})();
</script>
@stop

@section('content')
    <main class="vi-shell">
        <section class="vi-hero">
            <div class="vi-hero__copy">
                <div class="vi-eyebrow"><span></span> La capa de inteligencia vial que hoy no existe en el seguro</div>
                <h1>La carretera ya sabe dónde ocurrirá el próximo riesgo. <em>Ahora su cartera también.</em></h1>
                <p>Convertimos evidencia operativa de movilidad, siniestros y asistencia vial en señales territoriales para suscripción, prevención, claims y control de proveedores.</p>
                <div class="vi-hero__meta">
                    <span><i class="fas fa-shield-alt"></i> Sin datos personales</span>
                    <span><i class="fas fa-database"></i> Fuentes operativas verificables</span>
                    <span><i class="fas fa-map-marked-alt"></i> Cobertura territorial</span>
                    <span><i class="fas fa-user-check"></i> Decisión humana</span>
                </div>
            </div>
            <div class="vi-hero__score">
                <span>Servicios con aseguradora identificada</span>
                <strong>{{ number_format($stats['asegurados']) }}</strong>
                <div class="vi-scorebar"><i style="width:{{ $stats['porcentaje_asegurados'] }}%"></i></div>
                <b><i class="fas fa-database"></i> {{ number_format($stats['porcentaje_asegurados'], 1) }}% del registro disponible</b>
            </div>
        </section>

        <div class="vi-demo-note">
            <div><i class="fas fa-database"></i></div>
            <p><strong>Datos del sistema.</strong> Los volúmenes de esta pantalla provienen del registro operativo de servicios de grúa. Los importes del simulador son supuestos editables porque el sistema no captura el costo facturado por la grúa.</p>
        </div>

        <section class="vi-toolbar" id="vi-command">
            <div>
                <span class="vi-toolbar__label">Portafolio</span>
                <button type="button" class="vi-select">Vehículos particulares <i class="fas fa-chevron-down"></i></button>
            </div>
            <div>
                <span class="vi-toolbar__label">Periodo</span>
                <button type="button" class="vi-select">
                    @if($stats['fecha_inicio'] && $stats['fecha_corte'])
                        {{ $stats['fecha_inicio']->locale('es')->translatedFormat('d M Y') }} — {{ $stats['fecha_corte']->locale('es')->translatedFormat('d M Y') }}
                    @else
                        Sin registros
                    @endif
                    <i class="fas fa-calendar-alt"></i>
                </button>
            </div>
            <div class="vi-toolbar__stamp">
                <span>Corte de la información</span>
                <strong>{{ $stats['fecha_corte'] ? $stats['fecha_corte']->locale('es')->translatedFormat('d \d\e F \d\e Y') : 'Sin registros' }}</strong>
            </div>
        </section>

        <section class="vi-kpis" aria-label="Indicadores principales">
            <article class="vi-kpi">
                <div class="vi-kpi__icon vi-cyan"><i class="fas fa-truck-pickup"></i></div>
                <span>Servicios registrados</span>
                <strong>{{ number_format($stats['total']) }}</strong>
                <small>Universo real disponible para análisis</small>
            </article>
            <article class="vi-kpi">
                <div class="vi-kpi__icon vi-violet"><i class="fas fa-shield-alt"></i></div>
                <span>Con aseguradora identificada</span>
                <strong>{{ number_format($stats['asegurados']) }}</strong>
                <small><b>{{ number_format($stats['porcentaje_asegurados'], 1) }}%</b> del total registrado</small>
            </article>
            <article class="vi-kpi">
                <div class="vi-kpi__icon vi-green"><i class="fas fa-building"></i></div>
                <span>Aseguradoras reconocidas</span>
                <strong>{{ number_format($stats['aseguradoras']) }}</strong>
                <small>Nombres normalizados en el registro</small>
            </article>
            <article class="vi-kpi">
                <div class="vi-kpi__icon vi-orange"><i class="fas fa-warehouse"></i></div>
                <span>Proveedores de grúa</span>
                <strong>{{ number_format($stats['gruas']) }}</strong>
                <small>Con al menos un servicio registrado</small>
            </article>
        </section>

        <section class="vi-grid vi-grid--main">
            <article class="vi-panel vi-tow">
                <header class="vi-panel__header">
                    <div>
                        <span class="vi-panel__kicker">Control de costos</span>
                        <h2>Eficiencia de asistencia vial</h2>
                    </div>
                    <span class="vi-status vi-status--green"><i></i> Dato operativo</span>
                </header>
                <div class="vi-tow__summary">
                    <div><span>Servicios de grúa</span><strong>{{ number_format($stats['total']) }}</strong></div>
                    <div><span>Con aseguradora</span><strong>{{ number_format($stats['asegurados']) }}</strong><small>{{ number_format($stats['porcentaje_asegurados'], 1) }}%</small></div>
                    <div><span>Sin aseguradora identificada</span><strong>{{ number_format($stats['sin_aseguradora']) }}</strong></div>
                </div>
                <div class="vi-bar-chart" aria-label="Servicios de grúa por mes">
                    @foreach($stats['meses'] as $mes)
                        <div class="vi-bar-chart__month">
                            <div class="vi-bar-chart__bar" title="{{ number_format($mes['total']) }} servicios; {{ number_format($mes['asegurados']) }} con aseguradora">
                                <i style="height:{{ $mes['altura_total'] }}%"></i>
                                <b style="height:{{ $mes['altura_asegurados'] }}%"></b>
                            </div>
                            <span>{{ $mes['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="vi-legend">
                    <span><i class="vi-legend__all"></i> Total de servicios</span>
                    <span><i class="vi-legend__review"></i> Con aseguradora identificada</span>
                </div>
                <div class="vi-insight">
                    <i class="fas fa-check-circle"></i>
                    <p><strong>Lo que ya existe:</strong> cada servicio está vinculado a un vehículo y a una grúa. La información de la aseguradora permitiría contrastar importe, recurrencia y proveedor durante el piloto.</p>
                </div>
            </article>

            <article class="vi-panel vi-risk">
                <header class="vi-panel__header">
                    <div>
                        <span class="vi-panel__kicker">Composición observada</span>
                        <h2>Aseguradoras en el registro</h2>
                    </div>
                    <span class="vi-status vi-status--green">Top {{ $stats['top_aseguradoras']->count() }}</span>
                </header>
                <ol class="vi-ranking">
                    @forelse($stats['top_aseguradoras'] as $index => $aseguradora)
                        <li>
                            <b>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</b>
                            <div><strong>{{ $aseguradora['nombre'] }}</strong><span>Servicios de grúa vinculados</span></div>
                            <em>{{ number_format($aseguradora['total']) }}</em>
                        </li>
                    @empty
                        <li><div><strong>Sin datos disponibles</strong><span>No hay aseguradoras identificadas en el periodo.</span></div></li>
                    @endforelse
                </ol>
            </article>
        </section>

        <section class="vi-grid vi-grid--secondary">
            <article class="vi-panel vi-causes">
                <header class="vi-panel__header">
                    <div><span class="vi-panel__kicker">Calidad del dato</span><h2>Estado del registro</h2></div>
                </header>
                <div class="vi-causes__body">
                    <div class="vi-donut" style="background:conic-gradient(#35d6c3 0 {{ $stats['porcentaje_asegurados'] }}%, #52627e {{ $stats['porcentaje_asegurados'] }}% 100%)"><div><strong>{{ number_format($stats['porcentaje_asegurados'], 1) }}%</strong><span>con aseguradora</span></div></div>
                    <div class="vi-causes__legend">
                        <p><i style="background:#35d6c3"></i><span>Vehículo vinculado</span><b>{{ number_format($stats['porcentaje_vinculados'], 1) }}%</b></p>
                        <p><i style="background:#7e6bf2"></i><span>Aseguradora identificada</span><b>{{ number_format($stats['asegurados']) }}</b></p>
                        <p><i style="background:#ffb454"></i><span>Proveedores observados</span><b>{{ number_format($stats['gruas']) }}</b></p>
                        <p><i style="background:#52627e"></i><span>Servicios por completar</span><b>{{ number_format($stats['sin_aseguradora']) }}</b></p>
                    </div>
                </div>
            </article>
            <article class="vi-panel vi-alerts">
                <header class="vi-panel__header">
                    <div><span class="vi-panel__kicker">Detección temprana</span><h2>Señales que ameritan revisión</h2></div>
                    <span class="vi-status vi-status--amber">A validar en piloto</span>
                </header>
                <div class="vi-alert-list">
                    <div><i class="fas fa-file-invoice-dollar"></i><p><strong>Costo fuera del rango acordado</strong><span>Requiere factura o detalle de la aseguradora</span></p><b>Piloto</b></div>
                    <div><i class="fas fa-redo-alt"></i><p><strong>Servicio repetido</strong><span>Mismo vehículo dentro de una ventana definida</span></p><b>Piloto</b></div>
                    <div><i class="fas fa-network-wired"></i><p><strong>Concentración por proveedor</strong><span>Comparación contra el volumen real observado</span></p><b>Piloto</b></div>
                </div>
                <p class="vi-human-review"><i class="fas fa-user-check"></i> Cada señal requiere validación humana. No constituye por sí sola fraude, abuso ni servicio innecesario.</p>
            </article>
        </section>

        @if($legacyStats)
            <section class="vi-history" id="vi-history">
                <div class="vi-history__header">
                    <div>
                        <span class="vi-panel__kicker">Archivo histórico recuperado</span>
                        <h2>Serie disponible para análisis: 2014–2026</h2>
                        <p>Fuente: base histórica de Peritos. Se presenta separada del registro operativo actual para conservar trazabilidad y evitar sumar universos distintos.</p>
                    </div>
                    <div class="vi-history__period">
                        <small>Periodo documentado</small>
                        <strong>{{ $legacyStats['fecha_inicio']->locale('es')->translatedFormat('M Y') }} — {{ $legacyStats['fecha_corte']->locale('es')->translatedFormat('M Y') }}</strong>
                    </div>
                </div>

                <div class="vi-history__body">
                    <div class="vi-history__focus">
                        <span>Vehículos asegurados con grúa utilizada</span>
                        <strong>{{ number_format($legacyStats['asegurados_con_grua']) }}</strong>
                        <p>Registros históricos donde coinciden ambos indicadores. Es el universo inicial para un estudio retrospectivo de asistencia vial.</p>
                        <div class="vi-history__support">
                            <div><b>{{ number_format($legacyStats['asegurados']) }}</b><span>marcados como asegurados</span></div>
                            <div><b>{{ number_format($legacyStats['con_grua']) }}</b><span>con grúa utilizada</span></div>
                        </div>
                    </div>

                    <div class="vi-history__evidence">
                        <div class="vi-history__cards">
                            <article><i class="fas fa-car-crash"></i><b>{{ number_format($legacyStats['hechos']) }}</b><span>hechos de tránsito</span></article>
                            <article><i class="fas fa-car"></i><b>{{ number_format($legacyStats['vehiculos']) }}</b><span>registros de vehículos</span></article>
                            <article><i class="fas fa-map-marker-alt"></i><b>{{ number_format($legacyStats['georreferenciados']) }}</b><span>hechos con coordenadas</span></article>
                        </div>
                        <div class="vi-history__chart" aria-label="Hechos históricos por año, de 2016 a 2025">
                            @foreach($legacyStats['por_anio'] as $anio)
                                <div title="{{ $anio['anio'] }}: {{ number_format($anio['total']) }} hechos">
                                    <i style="height:{{ $anio['altura'] }}%"></i>
                                    <b>{{ number_format($anio['total']) }}</b>
                                    <span>{{ $anio['anio'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="vi-history__note">
                    <i class="fas fa-clipboard-check"></i>
                    <p><strong>Lectura correcta:</strong> la fuente histórica identifica si el vehículo estaba asegurado, pero no conserva el nombre de la compañía. Los montos de daños requieren depuración de valores extremos antes de cualquier análisis económico.</p>
                </div>
            </section>
        @endif

        <section class="vi-business-case" id="vi-business-case">
            <div class="vi-section-title vi-section-title--wide">
                <span>Escenario para la reunión</span>
                <h2>Partimos del volumen real. El costo lo define la aseguradora.</h2>
                <p>El sistema aporta la cantidad observada de servicios. El costo y el porcentaje sujeto a revisión se ajustan durante la conversación y quedan identificados como supuestos.</p>
            </div>
            <div class="vi-lab">
                <div class="vi-lab__controls">
                    <div class="vi-lab__heading">
                        <span class="vi-panel__kicker">Modelo de alcance económico</span>
                        <h3>Servicios de grúa con vehículos asegurados</h3>
                        <p>La base inicia con los {{ number_format($stats['asegurados']) }} servicios que tienen aseguradora identificada. El importe no es un dato del sistema; se usa únicamente para dimensionar el piloto.</p>
                    </div>
                    <label class="vi-range">
                        <span><b>Servicios de grúa a analizar</b><output id="viTowOutput">{{ number_format($stats['asegurados']) }}</output></span>
                        <input id="viTowRange" type="range" min="1" max="{{ max(5000, $stats['asegurados'] * 5) }}" step="1" value="{{ max(1, $stats['asegurados']) }}">
                    </label>
                    <label class="vi-range">
                        <span><b>Costo promedio supuesto por servicio</b><output id="viCostOutput">$25,000</output></span>
                        <input id="viCostRange" type="range" min="20000" max="100000" step="1000" value="25000">
                    </label>
                    <label class="vi-range">
                        <span><b>Supuesto de servicios a revisar</b><output id="viRateOutput">10.0%</output></span>
                        <input id="viRateRange" type="range" min="1" max="30" step="0.5" value="10">
                    </label>
                </div>
                <div class="vi-lab__result">
                    <span>Valor de servicios sujeto a revisión</span>
                    <strong id="viOpportunity">—</strong>
                    <p id="viOpportunityDetail">—</p>
                    <div class="vi-result-grid">
                        <div><small>Gasto modelado</small><b id="viAnnualSpend">—</b></div>
                        <div><small>Costo supuesto</small><b id="viSignalCost">$25,000</b></div>
                    </div>
                    <div class="vi-result-foot"><i class="fas fa-info-circle"></i><span>No es ahorro prometido ni importe auditado. Es el tamaño económico del universo que se revisaría bajo los supuestos seleccionados.</span></div>
                </div>
            </div>
        </section>

        <section class="vi-moat" id="vi-moat">
            <div class="vi-moat__intro">
                <span class="vi-panel__kicker">Qué tenemos hoy</span>
                <h2>Un registro operativo.<br><em>Una base auditable.</em></h2>
                <p>La propuesta no depende de comprar expedientes ni de compartir datos personales. Parte de información que ya se genera durante la operación y conserva su vínculo con vehículo, proveedor y fecha.</p>
            </div>
            <div class="vi-moat__grid">
                <article><b>01</b><i class="fas fa-fingerprint"></i><h3>Origen verificable</h3><p>La señal nace del registro operativo, conserva su procedencia y puede auditarse.</p></article>
                <article><b>02</b><i class="fas fa-clock"></i><h3>Contexto temporal</h3><p>Corredor, horario y condiciones convierten un evento aislado en patrón.</p></article>
                <article><b>03</b><i class="fas fa-route"></i><h3>Red territorial</h3><p>La cobertura institucional produce una lectura difícil de replicar desde una sola cartera.</p></article>
                <article><b>04</b><i class="fas fa-sync-alt"></i><h3>Aprendizaje continuo</h3><p>Cada validación mejora reglas, umbrales y capacidad de priorización.</p></article>
            </div>
        </section>

        <section class="vi-value">
            <div class="vi-section-title">
                <span>Alcance propuesto</span>
                <h2>Lo que puede validar el piloto</h2>
                <p>Cada módulo se habilita sólo si existen los datos necesarios y una métrica de éxito acordada con la aseguradora.</p>
            </div>
            <div class="vi-modules">
                <article><i class="fas fa-calculator"></i><h3>Tarificación territorial</h3><p>Frecuencia y severidad por zona, corredor, horario y tipo de vehículo.</p><span>Suscripción</span></article>
                <article><i class="fas fa-bolt"></i><h3>Triaje de siniestros</h3><p>Priorización temprana por contexto vial y severidad probable.</p><span>Claims</span></article>
                <article><i class="fas fa-truck-pickup"></i><h3>Auditoría de grúas</h3><p>Señales de duplicidad, atipicidad y servicios potencialmente evitables.</p><span>Ahorro</span></article>
                <article><i class="fas fa-fingerprint"></i><h3>Anomalías operativas</h3><p>Patrones fuera de rango para orientar revisiones, sin decisiones automáticas.</p><span>Control</span></article>
                <article><i class="fas fa-traffic-light"></i><h3>Prevención focalizada</h3><p>Campañas y alertas por horario, clima, corredor y usuario vulnerable.</p><span>Prevención</span></article>
                <article><i class="fas fa-stopwatch"></i><h3>SLA de proveedores</h3><p>Comparativos de respuesta, recurrencia, cobertura y consistencia.</p><span>Red</span></article>
                <article><i class="fas fa-layer-group"></i><h3>Benchmark de cartera</h3><p>Comparación contra referencias territoriales agregadas y anónimas.</p><span>Estrategia</span></article>
                <article><i class="fas fa-plug"></i><h3>API y reportes</h3><p>Tableros ejecutivos, alertas y datos listos para integrar.</p><span>Integración</span></article>
            </div>
        </section>

        <section class="vi-commercial" id="vi-pilot">
            <div class="vi-commercial__copy">
                <span class="vi-panel__kicker">Propuesta para acordar</span>
                <h2>Una pregunta concreta. Una muestra definida. Un resultado medible.</h2>
                <p>El alcance y la duración se determinan con la aseguradora después de revisar disponibilidad, calidad y reglas de tratamiento de sus datos.</p>
            </div>
            <div class="vi-steps">
                <article><b>01</b><div><strong>Diagnóstico</strong><span>Muestra histórica, calidad de datos y línea base.</span></div><em>Proyecto</em></article>
                <article><b>02</b><div><strong>Piloto controlado</strong><span>Un territorio, reglas de grúa y tablero ejecutivo.</span></div><em>Validación</em></article>
                <article><b>03</b><div><strong>Suscripción</strong><span>Módulos, usuarios, alertas e integraciones.</span></div><em>Recurrente</em></article>
            </div>
            <div class="vi-commercial__close">
                <span>Siguiente paso: acordar una muestra y la métrica que decidirá si el piloto continúa.</span>
                <strong>Primero medir. Después escalar.</strong>
            </div>
        </section>

        <footer class="vi-footer">
            <div><span class="vi-brand__mark"><i class="fas fa-wave-square"></i></span><strong>VIALYTICS</strong></div>
            <p>Prototipo interno · Acceso exclusivo Superadmin · No distribuir</p>
        </footer>
    </main>
@stop

@section('css')
<style>
    :root{--vi-bg:#07101e;--vi-panel:#0e1a2d;--vi-panel2:#111f34;--vi-line:rgba(160,185,220,.13);--vi-text:#eef5ff;--vi-muted:#8fa3bf;--vi-green:#35d6a4;--vi-cyan:#35c8e6;--vi-violet:#7e6bf2;--vi-amber:#ffb454}
    .content-wrapper{background:var(--vi-bg)!important}.content-header{padding:0!important}.content{padding:0!important}.container-fluid{padding:0!important}.main-footer{display:none}.vi-shell{min-height:100vh;color:var(--vi-text);font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:radial-gradient(900px 420px at 88% 0,rgba(36,193,166,.09),transparent 70%),var(--vi-bg);padding-bottom:38px}.vi-topbar{height:72px;background:#091425;border-bottom:1px solid var(--vi-line);display:flex;align-items:center;padding:0 32px;gap:18px;color:var(--vi-text)}.vi-back,.vi-icon-button{width:38px;height:38px;border:1px solid var(--vi-line);background:rgba(255,255,255,.035);color:#b9cae0;border-radius:10px;display:grid;place-items:center}.vi-back:hover{color:#fff;border-color:var(--vi-green)}.vi-brand{display:flex;align-items:center;gap:10px;font-weight:900;letter-spacing:1.6px}.vi-brand__mark{width:30px;height:30px;border-radius:9px;background:linear-gradient(145deg,var(--vi-green),#1384a3);display:inline-grid;place-items:center;color:#05131d}.vi-brand__divider{width:1px;height:22px;background:var(--vi-line);margin-left:6px}.vi-brand small{font-weight:600;letter-spacing:.3px;color:var(--vi-muted)}.vi-topbar__actions{margin-left:auto;display:flex;align-items:center;gap:14px}.vi-private{font-size:12px;color:var(--vi-muted)}.vi-private i{color:var(--vi-green);margin-right:6px}.vi-print{border:1px solid rgba(53,214,164,.28);background:rgba(53,214,164,.09);color:#9bf2d5;padding:9px 14px;border-radius:9px;font-size:12px;font-weight:800}.vi-hero,.vi-demo-note,.vi-toolbar,.vi-kpis,.vi-grid,.vi-value,.vi-commercial,.vi-footer{max-width:1380px;margin-left:auto;margin-right:auto}.vi-hero{min-height:320px;display:flex;align-items:center;justify-content:space-between;padding:62px 36px 44px;gap:50px}.vi-hero__copy{max-width:800px}.vi-eyebrow,.vi-panel__kicker{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:1.4px;color:var(--vi-green)}.vi-eyebrow span{display:inline-block;width:25px;height:2px;background:var(--vi-green);vertical-align:middle;margin-right:8px}.vi-hero h1{font-size:clamp(36px,4.3vw,66px);line-height:1.02;letter-spacing:-2.8px;margin:18px 0;color:#f5f9ff;font-weight:850}.vi-hero h1 em{display:block;color:var(--vi-green);font-style:normal}.vi-hero__copy>p{max-width:720px;color:#9fb1c8;font-size:17px;line-height:1.65;margin:0}.vi-hero__meta{display:flex;gap:24px;flex-wrap:wrap;margin-top:24px;color:#aebed2;font-size:12px}.vi-hero__meta i{color:var(--vi-green);margin-right:6px}.vi-hero__score{width:230px;flex:0 0 230px;border:1px solid var(--vi-line);background:linear-gradient(145deg,rgba(20,38,61,.9),rgba(9,23,39,.88));padding:26px;border-radius:22px;box-shadow:0 25px 70px rgba(0,0,0,.28)}.vi-hero__score>span{display:block;color:var(--vi-muted);font-size:12px;font-weight:700}.vi-hero__score strong{font-size:68px;line-height:1.2;letter-spacing:-4px}.vi-hero__score small{color:var(--vi-muted);font-size:18px}.vi-scorebar{height:7px;border-radius:10px;background:#1d2d45;overflow:hidden}.vi-scorebar i{display:block;height:100%;background:linear-gradient(90deg,var(--vi-green),var(--vi-amber));border-radius:10px}.vi-hero__score b{display:block;font-size:11px;color:var(--vi-amber);margin-top:13px}.vi-demo-note{display:flex;gap:13px;align-items:center;background:rgba(53,200,230,.055);border:1px solid rgba(53,200,230,.16);border-radius:12px;padding:12px 18px;margin-bottom:22px}.vi-demo-note>div{color:var(--vi-cyan)}.vi-demo-note p{margin:0;color:#a8b9cd;font-size:12px;line-height:1.5}.vi-demo-note strong{color:#dce9f8}.vi-toolbar{display:flex;align-items:end;gap:13px;border-top:1px solid var(--vi-line);padding:22px 0}.vi-toolbar__label{display:block;color:#7187a4;font-size:9px;text-transform:uppercase;letter-spacing:1px;font-weight:900;margin:0 0 6px 3px}.vi-select{min-width:205px;display:flex;justify-content:space-between;align-items:center;background:#0d1a2c;border:1px solid var(--vi-line);color:#dbe7f6;padding:10px 13px;border-radius:9px;font-size:12px;text-align:left}.vi-select i{color:#687e9b;font-size:9px}.vi-toolbar__stamp{margin-left:auto;text-align:right}.vi-toolbar__stamp span{display:block;color:#7187a4;font-size:9px;text-transform:uppercase}.vi-toolbar__stamp strong{font-size:12px;color:#b7c7da}.vi-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:14px}.vi-kpi,.vi-panel{background:linear-gradient(155deg,var(--vi-panel2),var(--vi-panel));border:1px solid var(--vi-line);border-radius:15px}.vi-kpi{padding:20px;position:relative;min-height:145px}.vi-kpi__icon{position:absolute;right:18px;top:18px;width:36px;height:36px;border-radius:10px;display:grid;place-items:center}.vi-cyan{background:rgba(53,200,230,.13);color:var(--vi-cyan)}.vi-violet{background:rgba(126,107,242,.13);color:#9d8fff}.vi-green{background:rgba(53,214,164,.13);color:var(--vi-green)}.vi-orange{background:rgba(255,180,84,.13);color:var(--vi-amber)}.vi-kpi>span{display:block;color:#8499b5;font-size:11px;font-weight:750;text-transform:uppercase;letter-spacing:.5px;max-width:75%}.vi-kpi>strong{display:block;font-size:30px;margin:13px 0 7px;letter-spacing:-1px}.vi-kpi>small{color:#758aa6;font-size:11px}.vi-kpi>small b{color:var(--vi-green)}.vi-grid{display:grid;gap:14px;margin-bottom:14px}.vi-grid--main{grid-template-columns:1.65fr 1fr}.vi-grid--secondary{grid-template-columns:.9fr 1.25fr}.vi-panel{padding:22px}.vi-panel__header{display:flex;align-items:start;justify-content:space-between;gap:15px}.vi-panel h2{font-size:18px;font-weight:800;margin:5px 0 0}.vi-status{font-size:10px;font-weight:850;padding:6px 9px;border-radius:20px;white-space:nowrap}.vi-status--green{color:#80e9c5;background:rgba(53,214,164,.1)}.vi-status--green i{display:inline-block;width:6px;height:6px;border-radius:50%;background:var(--vi-green);margin-right:5px}.vi-status--amber{color:#ffd294;background:rgba(255,180,84,.11)}.vi-tow__summary{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:24px 0}.vi-tow__summary>div{padding:0 15px;border-left:1px solid var(--vi-line)}.vi-tow__summary>div:first-child{padding-left:0;border-left:0}.vi-tow__summary span{display:block;color:#8094ae;font-size:10px;text-transform:uppercase}.vi-tow__summary strong{font-size:21px;display:inline-block;margin-top:4px}.vi-tow__summary small{color:var(--vi-amber);font-size:10px;margin-left:6px}.vi-bar-chart{height:155px;display:flex;gap:10px;align-items:stretch;border-bottom:1px solid #283951;background:repeating-linear-gradient(to bottom,transparent 0,transparent 38px,rgba(145,167,198,.075) 39px);padding:0 6px}.vi-bar-chart__month{flex:1;display:flex;flex-direction:column;justify-content:flex-end;align-items:center}.vi-bar-chart__bar{position:relative;width:72%;height:126px;display:flex;align-items:end}.vi-bar-chart__bar i{display:block;width:100%;background:linear-gradient(180deg,rgba(53,200,230,.85),rgba(53,200,230,.20));border-radius:3px 3px 0 0}.vi-bar-chart__bar b{position:absolute;left:0;bottom:0;width:100%;background:var(--vi-amber);border-radius:2px 2px 0 0}.vi-bar-chart__month span{font-size:8px;color:#647994;margin:6px 0}.vi-legend{display:flex;gap:19px;justify-content:center;margin:12px 0 18px;font-size:10px;color:#8397b1}.vi-legend i{display:inline-block;width:8px;height:8px;border-radius:2px;margin-right:5px}.vi-legend__all{background:var(--vi-cyan)}.vi-legend__review{background:var(--vi-amber)}.vi-insight{display:flex;gap:11px;background:rgba(53,214,164,.055);border:1px solid rgba(53,214,164,.12);padding:12px;border-radius:10px}.vi-insight i{color:var(--vi-green);margin-top:2px}.vi-insight p{margin:0;font-size:11px;line-height:1.5;color:#92a8c2}.vi-insight strong{color:#c8d7e9}.vi-risk__map{height:166px;margin:20px 0 12px;border-radius:10px;background:linear-gradient(28deg,transparent 48%,rgba(53,214,164,.10) 49%,transparent 51%),#091525;position:relative;overflow:hidden;border:1px solid rgba(125,151,185,.12)}.vi-road{position:absolute;background:#213149;height:3px;width:120%;left:-10%;transform-origin:center}.vi-road--one{top:52%;transform:rotate(20deg)}.vi-road--two{top:40%;transform:rotate(-31deg)}.vi-road--three{top:75%;transform:rotate(-7deg)}.vi-road--four{top:20%;transform:rotate(7deg)}.vi-hotspot{position:absolute;width:12px;height:12px;border:3px solid #ff725e;background:#ffb454;border-radius:50%;box-shadow:0 0 0 9px rgba(255,114,94,.12),0 0 28px #ff725e}.vi-hotspot--1{top:34%;left:29%}.vi-hotspot--2{top:59%;left:55%;transform:scale(.75)}.vi-hotspot--3{top:24%;left:71%;transform:scale(.6)}.vi-hotspot--4{top:72%;left:80%;transform:scale(.5)}.vi-map-label{position:absolute;right:12px;bottom:9px;font-size:8px;letter-spacing:1px;color:#5f7693}.vi-ranking{padding:0;margin:0;list-style:none}.vi-ranking li{display:flex;align-items:center;gap:10px;padding:10px 0;border-top:1px solid rgba(151,175,207,.09)}.vi-ranking li>b{color:#506783;font-size:10px}.vi-ranking li>div{flex:1}.vi-ranking strong,.vi-ranking span{display:block}.vi-ranking strong{font-size:11px}.vi-ranking span{font-size:9px;color:#7186a1;margin-top:2px}.vi-ranking em{font-style:normal;color:var(--vi-amber);font-weight:900;font-size:13px}.vi-causes__body{display:flex;align-items:center;gap:30px;padding-top:27px}.vi-donut{width:160px;height:160px;flex:0 0 160px;border-radius:50%;background:conic-gradient(#35d6c3 0 38%,#7e6bf2 38% 62%,#ffb454 62% 83%,#52627e 83%);display:grid;place-items:center}.vi-donut:before{content:"";width:102px;height:102px;background:var(--vi-panel);border-radius:50%;grid-area:1/1}.vi-donut>div{position:relative;grid-area:1/1;text-align:center}.vi-donut strong,.vi-donut span{display:block}.vi-donut strong{font-size:28px}.vi-donut span{font-size:8px;color:#778ca8;text-transform:uppercase}.vi-causes__legend{flex:1}.vi-causes__legend p{display:flex;align-items:center;gap:8px;margin:0;padding:9px 0;border-bottom:1px solid rgba(150,175,208,.08);font-size:10px}.vi-causes__legend i{width:8px;height:8px;border-radius:2px}.vi-causes__legend span{flex:1;color:#91a4bc}.vi-alert-list{margin-top:15px}.vi-alert-list>div{display:flex;align-items:center;gap:12px;padding:12px 0;border-top:1px solid rgba(150,175,208,.09)}.vi-alert-list>div>i{width:34px;height:34px;background:rgba(255,180,84,.09);color:var(--vi-amber);border-radius:9px;display:grid;place-items:center;font-size:13px}.vi-alert-list p{flex:1;margin:0}.vi-alert-list strong,.vi-alert-list span{display:block}.vi-alert-list strong{font-size:11px}.vi-alert-list span{font-size:9px;color:#7186a1;margin-top:3px}.vi-alert-list>div>b{font-size:9px;color:#ffae8b;background:rgba(255,112,83,.09);padding:5px 7px;border-radius:10px}.vi-human-review{margin:6px 0 0;padding:10px;border-radius:8px;color:#788da8;background:rgba(255,255,255,.025);font-size:9.5px;line-height:1.45}.vi-human-review i{color:var(--vi-cyan);margin-right:5px}.vi-value{padding:62px 0 28px}.vi-section-title{text-align:center;max-width:650px;margin:0 auto 28px}.vi-section-title>span{color:var(--vi-green);text-transform:uppercase;letter-spacing:1.6px;font-size:10px;font-weight:900}.vi-section-title h2{font-size:30px;margin:8px 0}.vi-section-title p{color:#8296b1;font-size:13px}.vi-modules{display:grid;grid-template-columns:repeat(4,1fr);gap:11px}.vi-modules article{position:relative;min-height:175px;padding:20px;background:rgba(14,27,46,.72);border:1px solid var(--vi-line);border-radius:13px}.vi-modules article>i{color:var(--vi-green);font-size:20px}.vi-modules h3{font-size:13px;margin:14px 0 7px}.vi-modules p{color:#8295ae;font-size:10.5px;line-height:1.5}.vi-modules span{position:absolute;bottom:16px;left:20px;font-size:8px;text-transform:uppercase;letter-spacing:1px;color:#687e99}.vi-commercial{margin-top:35px;background:linear-gradient(120deg,rgba(53,214,164,.11),rgba(28,55,78,.35));border:1px solid rgba(53,214,164,.17);border-radius:17px;padding:31px;display:grid;grid-template-columns:.8fr 1.4fr;gap:40px;align-items:center}.vi-commercial__copy h2{font-size:25px;margin:8px 0}.vi-commercial__copy p{font-size:11px;color:#8ea2bc;line-height:1.6}.vi-steps{display:grid;gap:8px}.vi-steps article{display:flex;align-items:center;gap:13px;background:rgba(5,16,29,.44);border:1px solid rgba(144,177,203,.1);padding:12px;border-radius:10px}.vi-steps article>b{color:var(--vi-green);font-size:11px}.vi-steps article>div{flex:1}.vi-steps strong,.vi-steps span{display:block}.vi-steps strong{font-size:11px}.vi-steps span{font-size:9px;color:#7890ab;margin-top:3px}.vi-steps em{font-size:8px;font-style:normal;text-transform:uppercase;letter-spacing:.8px;color:#96e7cb}.vi-footer{border-top:1px solid var(--vi-line);margin-top:50px;padding-top:25px;display:flex;justify-content:space-between;align-items:center}.vi-footer>div{display:flex;align-items:center;gap:9px;font-size:11px;letter-spacing:1px}.vi-footer p{margin:0;color:#526985;font-size:9px}
    @media(max-width:1450px){.vi-hero,.vi-demo-note,.vi-toolbar,.vi-kpis,.vi-grid,.vi-value,.vi-commercial,.vi-footer{margin-left:24px;margin-right:24px}}
    @media(max-width:1000px){.vi-kpis{grid-template-columns:repeat(2,1fr)}.vi-grid--main,.vi-grid--secondary,.vi-commercial{grid-template-columns:1fr}.vi-modules{grid-template-columns:repeat(2,1fr)}.vi-hero{padding-left:24px;padding-right:24px}.vi-hero__score{display:none}}
    @media(max-width:620px){.vi-topbar{padding:0 14px}.vi-brand small,.vi-brand__divider,.vi-private,.vi-print{display:none}.vi-hero{padding:38px 18px 30px}.vi-hero h1{font-size:39px;letter-spacing:-1.8px}.vi-hero__meta{gap:10px;display:grid}.vi-demo-note,.vi-toolbar,.vi-kpis,.vi-grid,.vi-value,.vi-commercial,.vi-footer{margin-left:12px;margin-right:12px}.vi-toolbar{align-items:stretch;flex-direction:column}.vi-toolbar__stamp{margin-left:0;text-align:left}.vi-select{width:100%}.vi-kpis,.vi-modules{grid-template-columns:1fr}.vi-tow__summary{grid-template-columns:1fr}.vi-tow__summary>div{border-left:0;border-top:1px solid var(--vi-line);padding:9px 0}.vi-bar-chart{gap:4px}.vi-causes__body{flex-direction:column}.vi-commercial{padding:20px}.vi-footer{gap:15px;align-items:start}.vi-footer p{text-align:right}}
    @media print{.main-sidebar,.main-header,.vi-topbar,.vi-toolbar,.vi-demo-note{display:none!important}.content-wrapper{margin:0!important}.vi-shell{background:#fff;color:#111}.vi-panel,.vi-kpi,.vi-modules article,.vi-commercial{break-inside:avoid;background:#fff;color:#111;border-color:#ccd3da}.vi-hero{min-height:auto}.vi-hero h1,.vi-panel h2,.vi-kpi strong,.vi-modules h3{color:#111}.vi-hero__score{box-shadow:none}.vi-value{padding-top:30px}}

    /* Presentación privada: ocupa todo el lienzo sin alterar el resto del sistema. */
    .main-sidebar,.main-header,.preloader,#svMessenger{display:none!important}.content-wrapper{margin-left:0!important}.wrapper{background:var(--vi-bg)!important}
    .vi-topbar{position:sticky;top:0;z-index:50;backdrop-filter:blur(18px);background:rgba(9,20,37,.9)}
    .vi-topnav{display:flex;gap:18px;margin-left:24px}.vi-topnav a{color:#7f94af;font-size:10px;font-weight:800;letter-spacing:.2px}.vi-topnav a:hover{color:var(--vi-green)}
    .vi-pitch-mode .vi-topbar{height:58px}.vi-pitch-mode .vi-topnav,.vi-pitch-mode .vi-private{display:none}.vi-pitch-mode .vi-shell{padding-top:0}
    .vi-hero{min-height:420px}.vi-hero__copy{max-width:950px}.vi-hero__score{border-color:rgba(53,214,164,.21);box-shadow:0 30px 90px rgba(0,0,0,.34),inset 0 0 45px rgba(53,214,164,.035)}
    .vi-history,.vi-business-case,.vi-moat{max-width:1380px;margin-left:auto;margin-right:auto}
    .vi-history{margin-top:70px;padding:34px;border:1px solid rgba(53,200,230,.18);border-radius:20px;background:linear-gradient(145deg,rgba(16,36,58,.96),rgba(8,22,38,.98));box-shadow:0 30px 80px rgba(0,0,0,.22)}
    .vi-history__header{display:flex;justify-content:space-between;align-items:end;gap:30px;padding-bottom:25px;border-bottom:1px solid var(--vi-line)}.vi-history__header h2{font-size:30px;letter-spacing:-1px;margin:7px 0}.vi-history__header p{max-width:760px;margin:0;color:#8398b2;font-size:11px;line-height:1.6}.vi-history__period{text-align:right;white-space:nowrap}.vi-history__period small,.vi-history__period strong{display:block}.vi-history__period small{color:#667e9a;text-transform:uppercase;font-size:8px;letter-spacing:1px}.vi-history__period strong{color:#b7cce2;font-size:12px;margin-top:5px}
    .vi-history__body{display:grid;grid-template-columns:.72fr 1.28fr;gap:26px;padding-top:26px}.vi-history__focus{padding:28px;border:1px solid rgba(53,214,164,.18);border-radius:16px;background:radial-gradient(circle at 90% 0,rgba(53,214,164,.13),transparent 48%),rgba(5,18,31,.55)}.vi-history__focus>span{display:block;color:#88a0b9;font-size:10px;text-transform:uppercase;letter-spacing:.9px;font-weight:850}.vi-history__focus>strong{display:block;font-size:62px;line-height:1;margin:13px 0 12px;letter-spacing:-3px;color:#f4fbff}.vi-history__focus>p{color:#8298b2;font-size:10.5px;line-height:1.6}.vi-history__support{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-top:23px}.vi-history__support>div{padding:12px;border:1px solid var(--vi-line);border-radius:10px}.vi-history__support b,.vi-history__support span{display:block}.vi-history__support b{font-size:17px;color:var(--vi-green)}.vi-history__support span{font-size:8.5px;color:#748ba5;margin-top:3px}
    .vi-history__evidence{display:flex;flex-direction:column;gap:22px}.vi-history__cards{display:grid;grid-template-columns:repeat(3,1fr);gap:9px}.vi-history__cards article{position:relative;padding:16px;border:1px solid var(--vi-line);border-radius:12px;background:rgba(255,255,255,.025)}.vi-history__cards i{position:absolute;right:14px;top:14px;color:var(--vi-cyan);opacity:.7}.vi-history__cards b,.vi-history__cards span{display:block}.vi-history__cards b{font-size:22px}.vi-history__cards span{color:#778ea8;font-size:9px;margin-top:3px}
    .vi-history__chart{height:180px;display:flex;align-items:end;gap:8px;border-bottom:1px solid #2b3c54;padding:8px 4px 0;background:repeating-linear-gradient(to bottom,transparent 0,transparent 44px,rgba(145,167,198,.07) 45px)}.vi-history__chart>div{height:100%;flex:1;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;min-width:0}.vi-history__chart i{display:block;width:66%;min-height:3px;border-radius:4px 4px 0 0;background:linear-gradient(180deg,var(--vi-green),rgba(53,214,164,.18))}.vi-history__chart b{font-size:7px;color:#8198b1;margin-top:4px}.vi-history__chart span{font-size:8px;color:#647b96;margin:3px 0 7px}.vi-history__note{display:flex;gap:11px;align-items:start;margin-top:22px;padding:12px;border-radius:10px;background:rgba(255,180,84,.055);border:1px solid rgba(255,180,84,.12)}.vi-history__note i{color:var(--vi-amber);margin-top:2px}.vi-history__note p{margin:0;color:#8298b2;font-size:9.5px;line-height:1.55}.vi-history__note strong{color:#c9d7e7}
    .vi-business-case{padding:88px 0 34px}.vi-section-title--wide{max-width:780px}.vi-section-title--wide h2{font-size:42px;letter-spacing:-1.6px}.vi-section-title--wide p{font-size:14px;line-height:1.6}
    .vi-lab{display:grid;grid-template-columns:1.15fr .85fr;border:1px solid rgba(53,214,164,.2);border-radius:22px;overflow:hidden;background:linear-gradient(145deg,rgba(19,42,64,.96),rgba(8,25,42,.98));box-shadow:0 32px 90px rgba(0,0,0,.26)}
    .vi-lab__controls{padding:34px 38px}.vi-lab__heading{margin-bottom:28px}.vi-lab__heading h3{font-size:25px;margin:7px 0 8px;letter-spacing:-.7px}.vi-lab__heading p{margin:0;max-width:650px;color:#8298b2;font-size:11px;line-height:1.55}
    .vi-range{display:block;padding:15px 0;border-top:1px solid var(--vi-line)}.vi-range>span{display:flex;justify-content:space-between;align-items:center;margin-bottom:11px}.vi-range b{font-size:11px;color:#c5d3e3}.vi-range output{color:var(--vi-green);font-size:12px;font-weight:900}
    .vi-range input{width:100%;height:5px;appearance:none;border-radius:20px;background:linear-gradient(90deg,var(--vi-green),#24435c);outline:none}.vi-range input::-webkit-slider-thumb{appearance:none;width:17px;height:17px;border:4px solid #dffdf4;border-radius:50%;background:var(--vi-green);box-shadow:0 0 0 5px rgba(53,214,164,.1);cursor:pointer}
    .vi-lab__result{padding:42px;display:flex;flex-direction:column;justify-content:center;background:radial-gradient(circle at 80% 10%,rgba(53,214,164,.15),transparent 48%),rgba(4,18,30,.55);border-left:1px solid rgba(53,214,164,.14)}.vi-lab__result>span{color:#83a0b8;font-size:10px;text-transform:uppercase;letter-spacing:1.1px;font-weight:900}.vi-lab__result>strong{font-size:clamp(44px,5vw,70px);letter-spacing:-3px;line-height:1.08;margin:12px 0;color:#f6fbff}.vi-lab__result>p{color:#93a8bd;font-size:12px}.vi-result-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:20px}.vi-result-grid>div{padding:14px;border:1px solid var(--vi-line);border-radius:11px;background:rgba(255,255,255,.025)}.vi-result-grid small,.vi-result-grid b{display:block}.vi-result-grid small{color:#7187a3;font-size:8px;text-transform:uppercase}.vi-result-grid b{font-size:16px;margin-top:5px}.vi-result-foot{display:flex;gap:9px;align-items:start;margin-top:20px;padding:11px;border-radius:10px;background:rgba(53,200,230,.05);color:#708ba3;font-size:9px;line-height:1.45}.vi-result-foot i{color:var(--vi-cyan)}
    .vi-moat{padding:105px 0 45px;display:grid;grid-template-columns:.74fr 1.26fr;gap:55px;align-items:center}.vi-moat__intro h2{font-size:42px;line-height:1.06;letter-spacing:-1.8px;margin:10px 0 17px}.vi-moat__intro h2 em{display:block;color:var(--vi-green);font-style:normal}.vi-moat__intro p{color:#8499b3;font-size:13px;line-height:1.7}.vi-moat__grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.vi-moat__grid article{position:relative;min-height:205px;padding:24px;background:linear-gradient(155deg,var(--vi-panel2),var(--vi-panel));border:1px solid var(--vi-line);border-radius:15px}.vi-moat__grid article>b{position:absolute;right:18px;top:15px;color:#304862;font-size:24px}.vi-moat__grid article>i{color:var(--vi-green);font-size:19px}.vi-moat__grid h3{font-size:13px;margin:28px 0 8px}.vi-moat__grid p{color:#7f94af;font-size:10.5px;line-height:1.55}
    .vi-commercial{position:relative;overflow:hidden}.vi-commercial__close{grid-column:1/-1;border-top:1px solid rgba(53,214,164,.14);padding-top:20px;display:flex;align-items:end;justify-content:space-between;gap:20px}.vi-commercial__close span{color:#7f97af;font-size:10px}.vi-commercial__close strong{font-size:18px;color:#b9f5df}
    .vi-kpi,.vi-panel,.vi-modules article,.vi-moat__grid article{transition:transform .22s ease,border-color .22s ease,background .22s ease}.vi-kpi:hover,.vi-panel:hover,.vi-modules article:hover,.vi-moat__grid article:hover{transform:translateY(-3px);border-color:rgba(53,214,164,.23)}
    @media(max-width:1450px){.vi-history,.vi-business-case,.vi-moat{margin-left:24px;margin-right:24px}}
    @media(max-width:1180px){.vi-topnav{display:none}.vi-lab,.vi-moat,.vi-history__body{grid-template-columns:1fr}.vi-lab__result{border-left:0;border-top:1px solid rgba(53,214,164,.14)}.vi-moat{gap:28px}}
    @media(max-width:620px){.vi-history,.vi-business-case,.vi-moat{margin-left:12px;margin-right:12px}.vi-history{padding:22px 16px;margin-top:48px}.vi-history__header{align-items:start;flex-direction:column}.vi-history__period{text-align:left}.vi-history__focus>strong{font-size:48px}.vi-history__support,.vi-history__cards{grid-template-columns:1fr}.vi-history__chart{gap:3px;height:155px}.vi-history__chart b{display:none}.vi-business-case{padding-top:58px}.vi-section-title--wide h2{font-size:32px}.vi-lab__controls,.vi-lab__result{padding:24px 18px}.vi-result-grid,.vi-moat__grid{grid-template-columns:1fr}.vi-moat{padding-top:72px}.vi-moat__intro h2{font-size:34px}.vi-commercial__close{align-items:start;flex-direction:column}.vi-pitch-toggle{display:none}}
</style>
@stop
