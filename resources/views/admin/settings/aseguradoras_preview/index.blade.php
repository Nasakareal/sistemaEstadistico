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
        <div class="vi-topbar__actions">
            <span class="vi-private"><i class="fas fa-lock"></i> Vista privada</span>
            <button type="button" class="vi-print" onclick="window.print()">
                <i class="fas fa-file-export"></i> Exportar resumen
            </button>
        </div>
    </div>
@stop

@section('content')
    <main class="vi-shell">
        <section class="vi-hero">
            <div class="vi-hero__copy">
                <div class="vi-eyebrow"><span></span> Inteligencia territorial para decisiones de seguros</div>
                <h1>Convertimos hechos viales en <em>decisiones rentables.</em></h1>
                <p>Una lectura agregada y anonimizada del riesgo, la severidad y la asistencia vial para anticipar pérdidas, auditar costos y proteger mejor cada portafolio.</p>
                <div class="vi-hero__meta">
                    <span><i class="fas fa-shield-alt"></i> Sin datos personales</span>
                    <span><i class="fas fa-database"></i> Fuentes operativas verificables</span>
                    <span><i class="fas fa-map-marked-alt"></i> Cobertura territorial</span>
                </div>
            </div>
            <div class="vi-hero__score">
                <span>Índice general de riesgo</span>
                <strong>67</strong><small>/100</small>
                <div class="vi-scorebar"><i style="width:67%"></i></div>
                <b><i class="fas fa-arrow-up"></i> 4.2% vs. periodo anterior</b>
            </div>
        </section>

        <div class="vi-demo-note">
            <div><i class="fas fa-flask"></i></div>
            <p><strong>Demostración comercial.</strong> Las cifras de esta pantalla son ilustrativas y no representan resultados reales. La versión para clientes utilizará únicamente información agregada, reglas documentadas y umbrales de privacidad.</p>
        </div>

        <section class="vi-toolbar">
            <div>
                <span class="vi-toolbar__label">Portafolio</span>
                <button type="button" class="vi-select">Vehículos particulares <i class="fas fa-chevron-down"></i></button>
            </div>
            <div>
                <span class="vi-toolbar__label">Periodo</span>
                <button type="button" class="vi-select">Últimos 12 meses <i class="fas fa-chevron-down"></i></button>
            </div>
            <div class="vi-toolbar__stamp">
                <span>Actualización</span>
                <strong>Hoy · 05:40 h</strong>
            </div>
        </section>

        <section class="vi-kpis" aria-label="Indicadores principales">
            <article class="vi-kpi">
                <div class="vi-kpi__icon vi-cyan"><i class="fas fa-car-crash"></i></div>
                <span>Siniestros analizados</span>
                <strong>12,480</strong>
                <small><b>+8.1%</b> volumen interanual</small>
            </article>
            <article class="vi-kpi">
                <div class="vi-kpi__icon vi-violet"><i class="fas fa-coins"></i></div>
                <span>Exposición estimada</span>
                <strong>$18.6 M</strong>
                <small>Modelo ilustrativo de severidad</small>
            </article>
            <article class="vi-kpi">
                <div class="vi-kpi__icon vi-green"><i class="fas fa-piggy-bank"></i></div>
                <span>Ahorro identificable</span>
                <strong>14.8%</strong>
                <small><b>$3.4 M</b> oportunidad anual</small>
            </article>
            <article class="vi-kpi">
                <div class="vi-kpi__icon vi-orange"><i class="fas fa-check-double"></i></div>
                <span>Cobertura de datos</span>
                <strong>82%</strong>
                <small>Campos críticos completos</small>
            </article>
        </section>

        <section class="vi-grid vi-grid--main">
            <article class="vi-panel vi-tow">
                <header class="vi-panel__header">
                    <div>
                        <span class="vi-panel__kicker">Control de costos</span>
                        <h2>Eficiencia de asistencia vial</h2>
                    </div>
                    <span class="vi-status vi-status--green"><i></i> Oportunidad alta</span>
                </header>
                <div class="vi-tow__summary">
                    <div><span>Servicios de grúa</span><strong>1,942</strong></div>
                    <div><span>Para revisión</span><strong>286</strong><small>14.7%</small></div>
                    <div><span>Impacto estimado</span><strong>$3.4 M</strong></div>
                </div>
                <div class="vi-bar-chart" aria-label="Servicios de grúa por mes">
                    @foreach([44, 58, 50, 66, 62, 76, 71, 86, 69, 82, 91, 78] as $index => $height)
                        <div class="vi-bar-chart__month">
                            <div class="vi-bar-chart__bar">
                                <i style="height:{{ $height }}%"></i>
                                <b style="height:{{ max(9, round($height * .15)) }}%"></b>
                            </div>
                            <span>{{ ['OCT','NOV','DIC','ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP'][$index] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="vi-legend">
                    <span><i class="vi-legend__all"></i> Total de servicios</span>
                    <span><i class="vi-legend__review"></i> Señales para revisión</span>
                </div>
                <div class="vi-insight">
                    <i class="fas fa-lightbulb"></i>
                    <p><strong>Hallazgo demostrativo:</strong> la mayor oportunidad se concentra en arrastres de corta distancia, vehículos aparentemente circulables y servicios repetidos dentro de una misma ventana temporal.</p>
                </div>
            </article>

            <article class="vi-panel vi-risk">
                <header class="vi-panel__header">
                    <div>
                        <span class="vi-panel__kicker">Suscripción y prevención</span>
                        <h2>Corredores con mayor riesgo</h2>
                    </div>
                    <button class="vi-icon-button" type="button" title="Vista de mapa"><i class="fas fa-map"></i></button>
                </header>
                <div class="vi-risk__map" aria-hidden="true">
                    <div class="vi-road vi-road--one"></div><div class="vi-road vi-road--two"></div>
                    <div class="vi-road vi-road--three"></div><div class="vi-road vi-road--four"></div>
                    <span class="vi-hotspot vi-hotspot--1"></span><span class="vi-hotspot vi-hotspot--2"></span>
                    <span class="vi-hotspot vi-hotspot--3"></span><span class="vi-hotspot vi-hotspot--4"></span>
                    <div class="vi-map-label">MORELIA</div>
                </div>
                <ol class="vi-ranking">
                    <li><b>01</b><div><strong>Periférico Paseo de la República</strong><span>Frecuencia alta · severidad media</span></div><em>89</em></li>
                    <li><b>02</b><div><strong>Av. Madero Poniente</strong><span>Motocicletas · horario nocturno</span></div><em>83</em></li>
                    <li><b>03</b><div><strong>Calz. La Huerta</strong><span>Alcances · lluvia</span></div><em>78</em></li>
                    <li><b>04</b><div><strong>Salida a Salamanca</strong><span>Severidad alta · fin de semana</span></div><em>74</em></li>
                </ol>
            </article>
        </section>

        <section class="vi-grid vi-grid--secondary">
            <article class="vi-panel vi-causes">
                <header class="vi-panel__header">
                    <div><span class="vi-panel__kicker">Composición</span><h2>Factores de severidad</h2></div>
                </header>
                <div class="vi-causes__body">
                    <div class="vi-donut"><div><strong>38%</strong><span>principal factor</span></div></div>
                    <div class="vi-causes__legend">
                        <p><i style="background:#35d6c3"></i><span>Tipo de impacto</span><b>38%</b></p>
                        <p><i style="background:#7e6bf2"></i><span>Horario / día</span><b>24%</b></p>
                        <p><i style="background:#ffb454"></i><span>Condición vial</span><b>21%</b></p>
                        <p><i style="background:#52627e"></i><span>Otros factores</span><b>17%</b></p>
                    </div>
                </div>
            </article>
            <article class="vi-panel vi-alerts">
                <header class="vi-panel__header">
                    <div><span class="vi-panel__kicker">Detección temprana</span><h2>Señales que ameritan revisión</h2></div>
                    <span class="vi-status vi-status--amber">12 nuevas</span>
                </header>
                <div class="vi-alert-list">
                    <div><i class="fas fa-route"></i><p><strong>Distancia atípica de arrastre</strong><span>Por encima del patrón territorial</span></p><b>Alta</b></div>
                    <div><i class="fas fa-redo-alt"></i><p><strong>Servicio repetido</strong><span>Mismo vehículo y ventana menor a 24 h</span></p><b>Alta</b></div>
                    <div><i class="fas fa-network-wired"></i><p><strong>Concentración por proveedor</strong><span>Desviación respecto del promedio mensual</span></p><b>Media</b></div>
                </div>
                <p class="vi-human-review"><i class="fas fa-user-check"></i> Cada señal requiere validación humana. No constituye por sí sola fraude, abuso ni servicio innecesario.</p>
            </article>
        </section>

        <section class="vi-value">
            <div class="vi-section-title">
                <span>Producto modular</span>
                <h2>Una sola fuente, múltiples decisiones</h2>
                <p>La aseguradora activa únicamente los módulos que producen valor para su operación.</p>
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

        <section class="vi-commercial">
            <div class="vi-commercial__copy">
                <span class="vi-panel__kicker">Ruta comercial sugerida</span>
                <h2>Empezar pequeño, demostrar ahorro y escalar.</h2>
                <p>El producto puede venderse en tres etapas, con métricas de éxito acordadas desde el inicio y sin comprometer información personal.</p>
            </div>
            <div class="vi-steps">
                <article><b>01</b><div><strong>Diagnóstico</strong><span>Muestra histórica, calidad de datos y línea base.</span></div><em>Proyecto</em></article>
                <article><b>02</b><div><strong>Piloto de 90 días</strong><span>Un territorio, reglas de grúa y tablero ejecutivo.</span></div><em>Validación</em></article>
                <article><b>03</b><div><strong>Suscripción</strong><span>Módulos, usuarios, alertas e integraciones.</span></div><em>Recurrente</em></article>
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
</style>
@stop
