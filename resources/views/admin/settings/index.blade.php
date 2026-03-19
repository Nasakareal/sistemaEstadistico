@extends('adminlte::page')

@section('title', 'Configuraciones del Sistema')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Administración · Control · Configuración</span>
            </div>

            <div class="sv-hero__title">
                Configuraciones del Sistema
            </div>

            <div class="sv-hero__subtitle">
                Panel de administración · Seguridad Vial · Michoacán
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">

        {{-- USUARIOS --}}
        @can('ver usuarios')
            <div class="col-md-3 col-sm-6 col-12">
                <div class="sv-card">
                    <div class="sv-card__icon bg-orange">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="sv-card__body">
                        <div class="sv-card__title">Usuarios</div>
                        <div class="sv-card__desc">Alta, edición y control de accesos.</div>
                        <a href="{{ url('/admin/settings/users') }}" class="btn sv-btn">
                            <i class="fas fa-arrow-right"></i> Acceder
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        {{-- ROLES --}}
        @can('ver roles')
            @role('Superadmin')
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="sv-card">
                        <div class="sv-card__icon bg-navy">
                            <i class="fa-regular fa-flag"></i>
                        </div>
                        <div class="sv-card__body">
                            <div class="sv-card__title">Roles</div>
                            <div class="sv-card__desc">Permisos, roles y asignaciones.</div>
                            <a href="{{ url('/admin/settings/roles') }}" class="btn sv-btn">
                                <i class="fas fa-arrow-right"></i> Acceder
                            </a>
                        </div>
                    </div>
                </div>
            @endrole
        @endcan

        {{-- PERSONAL --}}
        @can('ver personal')
            <div class="col-md-3 col-sm-6 col-12">
                <div class="sv-card">
                    <div class="sv-card__icon bg-teal">
                        <i class="fa-solid fa-user-group"></i>
                    </div>
                    <div class="sv-card__body">
                        <div class="sv-card__title">Personal</div>
                        <div class="sv-card__desc">Altas, bajas, incidencias y expedientes.</div>
                        <a href="{{ url('/admin/settings/personal') }}" class="btn sv-btn">
                            <i class="fas fa-arrow-right"></i> Acceder
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        {{-- PATRULLAS --}}
        @can('ver patrullas')
            <div class="col-md-3 col-sm-6 col-12">
                <div class="sv-card">
                    <div class="sv-card__icon bg-success">
                        <i class="fa fa-car" aria-hidden="true"></i>
                    </div>
                    <div class="sv-card__body">
                        <div class="sv-card__title">Patrullas</div>
                        <div class="sv-card__desc">Listado, Creación y análisis.</div>
                        <a href="{{ url('/admin/settings/patrullas') }}" class="btn sv-btn">
                            <i class="fas fa-arrow-right"></i> Acceder
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        {{-- ARMAMENTO --}}
        @can('ver armamentos')
            <div class="col-md-3 col-sm-6 col-12">
                <div class="sv-card">
                    <div class="sv-card__icon bg-maroon">
                        <i class="fa-solid fa-gun"></i>
                    </div>
                    <div class="sv-card__body">
                        <div class="sv-card__title">Armamento</div>
                        <div class="sv-card__desc">Control, asignación y estatus del armamento institucional.</div>
                        <a href="{{ url('/admin/settings/armamentos') }}" class="btn sv-btn">
                            <i class="fas fa-arrow-right"></i> Acceder
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        {{-- EXÁMENES DIARIOS --}}
        @can('ver modulo examenes')
            @if(auth()->user()->perteneceAAlgunaUnidad(['siniestros','delegaciones']))
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="sv-card">
                        <div class="sv-card__icon bg-purple">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <div class="sv-card__body">
                            <div class="sv-card__title">Exámenes (Módulo)</div>
                            <div class="sv-card__desc">Captura diaria de exámenes realizados.</div>
                            <a href="{{ url('/admin/settings/modulo-examenes-diarios') }}" class="btn sv-btn">
                                <i class="fas fa-arrow-right"></i> Acceder
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        @endcan

        {{-- ESTADISTICAS --}}
        @can('ver estadisticas')
            <div class="col-md-3 col-sm-6 col-12">
                <div class="sv-card">
                    <div class="sv-card__icon bg-success">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div class="sv-card__body">
                        <div class="sv-card__title">Estadísticas</div>
                        <div class="sv-card__desc">Reportes, exportaciones y análisis.</div>
                        <a href="{{ url('/admin/settings/estadisticas') }}" class="btn sv-btn">
                            <i class="fas fa-arrow-right"></i> Acceder
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        {{-- DELEGACIONES --}}
        @can('ver delegaciones')
            @if(auth()->user()->perteneceAUnidad('delegaciones'))
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="sv-card">
                        <div class="sv-card__icon bg-indigo">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </div>
                        <div class="sv-card__body">
                            <div class="sv-card__title">Delegaciones</div>
                            <div class="sv-card__desc">Gestión de delegaciones y subdelegaciones.</div>
                            <a href="{{ url('/admin/settings/delegaciones') }}" class="btn sv-btn">
                                <i class="fas fa-arrow-right"></i> Acceder
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        @endcan

        @php $user = auth()->user(); @endphp

        @if($user && ($user->hasRole('Superadmin') || (int)$user->unidad_id === 4))
            @can('ver destacamentos')
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="sv-card">
                        <div class="sv-card__icon bg-warning">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div class="sv-card__body">
                            <div class="sv-card__title">Destacamentos</div>
                            <div class="sv-card__desc">Gestión de destacamentos para consolidado de operativos.</div>
                            <a href="{{ url('/admin/settings/destacamentos') }}" class="btn sv-btn">
                                <i class="fas fa-arrow-right"></i> Acceder
                            </a>
                        </div>
                    </div>
                </div>
            @endcan
        @endif

        @php $user = auth()->user(); @endphp

        @if($user && $user->hasRole('Superadmin'))

            {{-- RESPALDOS SQL --}}
            <div class="col-md-3 col-sm-6 col-12">
                <div class="sv-card">
                    <div class="sv-card__icon bg-dark">
                        <i class="fa-solid fa-database"></i>
                    </div>
                    <div class="sv-card__body">
                        <div class="sv-card__title">Respaldos SQL</div>
                        <div class="sv-card__desc">Lista y descarga respaldos .sql / .sql.gz.</div>
                        <a href="{{ route('backups_sql.index') }}" class="btn sv-btn">
                            <i class="fas fa-arrow-right"></i> Acceder
                        </a>
                    </div>
                </div>
            </div>

        @endif

        @php $user = auth()->user(); @endphp

        @if($user && $user->perteneceAUnidad('siniestros'))

            {{-- EXPORT ESTADO DE FUERZA (PRUEBA) --}}
            <div class="col-md-3 col-sm-6 col-12">
                <div class="sv-card">
                    <div class="sv-card__icon bg-primary">
                        <i class="fa-solid fa-file-excel"></i>
                    </div>
                    <div class="sv-card__body">
                        <div class="sv-card__title">Exportar 6PM (Prueba)</div>
                        <div class="sv-card__desc">Genera el Excel local para revisar.</div>
                        <a href="{{ route('settings.exports.estado_fuerza') }}" class="btn sv-btn">
                            <i class="fas fa-download"></i> Generar Excel
                        </a>
                    </div>
                </div>
            </div>

            {{-- EXPORT PARTE DE NOVEDADES (PRUEBA) --}}
            <div class="col-md-3 col-sm-6 col-12">
                <div class="sv-card">
                    <div class="sv-card__icon bg-warning">
                        <i class="fa-solid fa-file-word"></i>
                    </div>
                    <div class="sv-card__body">
                        <div class="sv-card__title">Parte de Novedades (Prueba)</div>
                        <div class="sv-card__desc">Genera el DOCX local para revisar.</div>
                        <a href="{{ route('settings.exports.parte_novedades') }}" class="btn sv-btn">
                            <i class="fas fa-download"></i> Generar Parte
                        </a>
                    </div>
                </div>
            </div>

            {{-- EXPORT BITÁCORA (PRUEBA) --}}
            <div class="col-md-3 col-sm-6 col-12">
                <div class="sv-card">
                    <div class="sv-card__icon bg-secondary">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <div class="sv-card__body">
                        <div class="sv-card__title">Bitácora (Prueba)</div>
                        <div class="sv-card__desc">Genera el DOCX local para revisar.</div>
                        <a href="{{ route('settings.exports.bitacora') }}" class="btn sv-btn">
                            <i class="fas fa-download"></i> Generar Bitácora
                        </a>
                    </div>
                </div>
            </div>

            {{-- EXPORT MINI PARTE (PRUEBA) --}}
            <div class="col-md-3 col-sm-6 col-12">
                <div class="sv-card">
                    <div class="sv-card__icon bg-indigo">
                        <i class="fa-solid fa-file-word"></i>
                    </div>
                    <div class="sv-card__body">
                        <div class="sv-card__title">Mini Parte (Prueba)</div>
                        <div class="sv-card__desc">Genera el DOCX local para revisar.</div>
                        <a href="{{ route('settings.exports.mini_parte') }}" class="btn sv-btn">
                            <i class="fas fa-download"></i> Generar Mini Parte
                        </a>
                    </div>
                </div>
            </div>

        @endif

        @php $user = auth()->user(); @endphp

        @if($user && $user->perteneceAUnidad('siniestros'))

            {{-- RADAR RIESGO --}}
            <div class="col-md-3 col-sm-6 col-12">
                <div class="sv-card">
                    <div class="sv-card__icon bg-info">
                        <i class="fa-solid fa-radar"></i>
                    </div>
                    <div class="sv-card__body">
                        <div class="sv-card__title">Radar de Riesgo</div>
                        <div class="sv-card__desc">Mapa/consulta de zonas con mayor incidencia.</div>
                        <a href="{{ route('radar.riesgo') }}" class="btn sv-btn">
                            <i class="fas fa-arrow-right"></i> Acceder
                        </a>
                    </div>
                </div>
            </div>

            {{-- EXPORT BITÁCORA POR TURNO --}}
            <div class="col-md-3 col-sm-6 col-12">
                <div class="sv-card">
                    <div class="sv-card__icon bg-secondary">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <div class="sv-card__body">
                        <div class="sv-card__title">Bitácora por Turno</div>
                        <div class="sv-card__desc">Genera el DOCX por turno (A/B).</div>

                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('settings.exports.bitacora_turno', ['turno' => 'A']) }}" class="btn sv-btn">
                                <i class="fas fa-download"></i> Turno A
                            </a>

                            <a href="{{ route('settings.exports.bitacora_turno', ['turno' => 'B']) }}" class="btn sv-btn">
                                <i class="fas fa-download"></i> Turno B
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        @endif

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
</style>
@stop

@section('js')
    <script> console.log("Configuraciones del Sistema con estilo SV."); </script>
@stop
