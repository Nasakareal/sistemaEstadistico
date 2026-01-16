@extends('adminlte::page')

@section('title', 'Sistema Estadistico')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Operativo · Prevención · Respuesta</span>
            </div>

            <div class="sv-hero__title">
                Sistema Estadístico
            </div>

            <div class="sv-hero__subtitle">
                Coordinación del Agrupamiento de Seguridad Vial · Michoacán
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">

        {{-- HECHOS --}}
        @can('ver hechos')
        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-card">
                <div class="sv-card__icon bg-navy">
                    <i class="fa-solid fa-car-side"></i>
                </div>
                <div class="sv-card__body">
                    <div class="sv-card__title">Listado de Accidentes</div>
                    <div class="sv-card__desc">Consulta, crea y administra hechos.</div>
                    <a href="{{ url('hechos') }}" class="btn sv-btn">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
        </div>
        @endcan

        {{-- LISTAS --}}
        @can('ver listas')
        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-card">
                <div class="sv-card__icon bg-teal">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="sv-card__body">
                    <div class="sv-card__title">Pase de Lista</div>
                    <div class="sv-card__desc">Control de asistencias y registros.</div>
                    <a href="{{ url('listas') }}" class="btn sv-btn">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
        </div>
        @endcan

        {{-- ACTIVIDADES (EN DESARROLLO) --}}
        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-card sv-card--disabled" title="En desarrollo">
                <div class="sv-card__icon bg-lime">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="sv-card__body">
                    <div class="sv-card__title">Actividades</div>
                    <div class="sv-card__desc">Módulo en desarrollo.</div>
                    <a href="{{ url('#') }}" class="btn sv-btn sv-btn--ghost" onclick="return false;">
                        <i class="fas fa-clock"></i> Próximamente
                    </a>
                </div>
            </div>
        </div>

        {{-- GRAFICO SERVICIOS --}}
        @can('ver gruas')
        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-card">
                <div class="sv-card__icon bg-teal">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div class="sv-card__body">
                    <div class="sv-card__title">Gráfico de Servicios</div>
                    <div class="sv-card__desc">Estadísticas de servicios por grúa.</div>
                    <a href="{{ url('servicios/grafico') }}" class="btn sv-btn">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
        </div>
        @endcan

        {{-- GRUAS --}}
        @can('ver gruas')
        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-card">
                <div class="sv-card__icon bg-maroon">
                    <i class="fa-solid fa-truck-moving"></i>
                </div>
                <div class="sv-card__body">
                    <div class="sv-card__title">Grúas</div>
                    <div class="sv-card__desc">Catálogo y control de grúas.</div>
                    <a href="{{ url('gruas') }}" class="btn sv-btn">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
        </div>
        @endcan

        {{-- FORMATOS --}}
        @can('ver formatos')
        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-card">
                <div class="sv-card__icon bg-info">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="sv-card__body">
                    <div class="sv-card__title">Formatos</div>
                    <div class="sv-card__desc">Plantillas y documentos oficiales.</div>
                    <a href="{{ url('formatos') }}" class="btn sv-btn">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
        </div>
        @endcan

        {{-- DICTAMENES (crear) --}}
        @can('crear dictamenes')
        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-card">
                <div class="sv-card__icon bg-warning">
                    <i class="fas fa-gavel"></i>
                </div>
                <div class="sv-card__body">
                    <div class="sv-card__title">Solicitar Dictamen</div>
                    <div class="sv-card__desc">Genera un folio / número de dictamen.</div>
                    <a href="{{ url('dictamenes/create') }}" class="btn sv-btn">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
        </div>
        @endcan

        {{-- OFICIOS --}}
        @can('ver oficios')
        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-card">
                <div class="sv-card__icon bg-fuchsia">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <div class="sv-card__body">
                    <div class="sv-card__title">Oficios</div>
                    <div class="sv-card__desc">Sube y consulta oficios generados.</div>
                    <a href="{{ url('oficios') }}" class="btn sv-btn">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
        </div>
        @endcan

        {{-- ESTADISTICAS --}}
        @can('ver estadisticas')
        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-card">
                <div class="sv-card__icon bg-success">
                    <i class="fa-solid fa-table-cells"></i>
                </div>
                <div class="sv-card__body">
                    <div class="sv-card__title">Estadísticas</div>
                    <div class="sv-card__desc">Reportes y exportaciones.</div>
                    <a href="{{ url('admin/settings/estadisticas') }}" class="btn sv-btn">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
        </div>
        @endcan

        {{-- BUSQUEDA --}}
        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-card">
                <div class="sv-card__icon bg-indigo">
                    <i class="fas fa-search"></i>
                </div>
                <div class="sv-card__body">
                    <div class="sv-card__title">Búsqueda</div>
                    <div class="sv-card__desc">Localiza registros por filtros.</div>
                    <a href="{{ url('busqueda') }}" class="btn sv-btn">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
        </div>

        {{-- MAPA --}}
        @can('ver mapa')
        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-card">
                <div class="sv-card__icon bg-primary">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <div class="sv-card__body">
                    <div class="sv-card__title">Mapa Patrullas</div>
                    <div class="sv-card__desc">Ubicación operativa en tiempo real.</div>
                    <a href="{{ url('mapa') }}" class="btn sv-btn">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
        </div>
        @endcan

    </div>
@stop

@section('css')
<style>
    :root{
        --sv-text: rgba(234,240,255,.92);
        --sv-muted: rgba(234,240,255,.65);
        --sv-stroke: rgba(255,255,255,.12);
        --sv-card: rgba(255,255,255,.08);
        --sv-card2: rgba(255,255,255,.05);
        --sv-shadow: 0 18px 55px rgba(0,0,0,.35);
        --sv-radius: 22px;
    }

    /* Hero */
    .sv-hero{
        margin: 10px 0 12px;
        border-radius: 26px;
        border: 1px solid rgba(255,255,255,.12);
        background:
            radial-gradient(700px 280px at 20% 30%, rgba(45,168,255,.20), transparent 60%),
            radial-gradient(700px 280px at 80% 30%, rgba(124,92,255,.18), transparent 60%),
            linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.04));
        box-shadow: var(--sv-shadow);
        overflow: hidden;
    }
    .sv-hero__inner{ padding: 18px 18px 16px; text-align: center; }
    .sv-hero__badge{
        display:inline-flex; align-items:center; gap:10px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(0,0,0,.18);
        border: 1px solid rgba(255,255,255,.10);
        color: rgba(234,240,255,.85);
        font-weight: 800;
        font-size: 12px;
        letter-spacing: .35px;
    }
    .sv-dot{
        width: 8px; height: 8px; border-radius: 999px;
        background: #19D38C;
        box-shadow: 0 0 0 5px rgba(25,211,140,.14);
        display:inline-block;
    }
    .sv-hero__title{
        margin-top: 10px;
        font-weight: 950;
        letter-spacing: -.6px;
        font-size: clamp(22px, 2.3vw, 30px);
        color: var(--sv-text);
    }
    .sv-hero__subtitle{
        margin-top: 6px;
        font-weight: 650;
        font-size: 13px;
        color: var(--sv-muted);
    }

    /* Cards */
    .sv-card{
        display:flex;
        gap: 14px;
        padding: 14px;
        margin-bottom: 16px;
        border-radius: var(--sv-radius);
        border: 1px solid var(--sv-stroke);
        background: linear-gradient(180deg, var(--sv-card), var(--sv-card2));
        box-shadow: 0 10px 35px rgba(0,0,0,.22);
        transition: .18s ease;
        min-height: 108px;
    }
    .sv-card:hover{
        transform: translateY(-2px);
        border-color: rgba(45,168,255,.28);
        box-shadow: 0 18px 55px rgba(0,0,0,.30);
    }

    .sv-card__icon{
        width: 52px; height: 52px;
        border-radius: 18px;
        display:grid; place-items:center;
        border: 1px solid rgba(255,255,255,.14);
        box-shadow: 0 12px 25px rgba(0,0,0,.22);
        flex: 0 0 auto;
    }
    .sv-card__icon i{
        font-size: 20px;
        color: rgba(255,255,255,.95);
    }

    .sv-card__body{ flex: 1; min-width: 0; }
    .sv-card__title{
        font-weight: 900;
        font-size: 14px;
        color: var(--sv-text);
        line-height: 1.15;
    }
    .sv-card__desc{
        margin-top: 6px;
        font-weight: 650;
        font-size: 12.5px;
        color: var(--sv-muted);
    }

    .sv-btn{
        margin-top: 10px;
        display:inline-flex;
        align-items:center;
        gap: 8px;
        border-radius: 14px;
        font-weight: 900;
        border: 1px solid rgba(45,168,255,.35) !important;
        background: linear-gradient(135deg, rgba(45,168,255,.25), rgba(124,92,255,.22)) !important;
        color: rgba(234,240,255,.95) !important;
        padding: 8px 12px;
    }
    .sv-btn:hover{
        transform: translateY(-1px);
        border-color: rgba(45,168,255,.55) !important;
        background: linear-gradient(135deg, rgba(45,168,255,.34), rgba(124,92,255,.30)) !important;
        color: rgba(234,240,255,.98) !important;
    }

    .sv-btn--ghost{
        background: rgba(0,0,0,.18) !important;
        border: 1px solid rgba(255,255,255,.12) !important;
        color: rgba(234,240,255,.88) !important;
    }
    .sv-btn--ghost:hover{
        background: rgba(0,0,0,.22) !important;
        border-color: rgba(255,255,255,.16) !important;
        transform: none;
    }

    .sv-card--disabled{
        opacity: .78;
    }
</style>
@stop

@section('js')
    <script> console.log("¿Alguien Lee esto?"); </script>
@stop
