@extends('adminlte::page')

@section('title', 'Sectorizaciones')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Siniestros · Sectorizaciones · Cortes</span>
            </div>

            <div class="sv-hero__title">
                Sectorizaciones
            </div>

            <div class="sv-hero__subtitle">
                Listado de sectorizaciones disponibles para gestionar y descargar
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="sv-panel">
                <div class="sv-panel__header">
                    <div>
                        <div class="sv-panel__title">Sectorizaciones generadas</div>
                        <div class="sv-panel__desc">Selecciona una fecha para gestionar la sectorización o descargar su archivo.</div>
                    </div>
                </div>

                @if(isset($cortes) && count($cortes))
                    <div class="table-responsive">
                        <table class="table sv-table mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Archivo</th>
                                    <th width="260">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cortes as $corte)
                                    <tr>
                                        <td>{{ $corte['fecha'] ?? '' }}</td>
                                        <td>{{ $corte['archivo'] ?? '' }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap" style="gap:8px;">
                                                <a href="{{ route('settings.estadisticas_siniestros.sectorizaciones.gestionar', $corte['fecha']) }}" class="btn sv-btn">
                                                    <i class="fas fa-map-marked-alt"></i> Gestionar
                                                </a>

                                                <a href="{{ route('settings.estadisticas_siniestros.sectorizaciones.descargar', $corte['fecha']) }}" class="btn sv-btn">
                                                    <i class="fas fa-download"></i> Descargar
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="sv-empty">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <div class="sv-empty__title">No hay sectorizaciones disponibles</div>
                        <div class="sv-empty__desc">Cuando existan sectorizaciones guardadas aparecerán aquí.</div>
                    </div>
                @endif

                <div class="sv-panel__footer">
                    <a href="{{ route('settings.estadisticas_siniestros.sectorizaciones.gestionar', now('America/Mexico_City')->toDateString()) }}" class="btn sv-btn">
                        <i class="fas fa-plus-circle"></i> Gestionar sectorización de hoy
                    </a>

                    <a href="{{ route('settings.estadisticas_siniestros.sectorizaciones.gestionar', now('America/Mexico_City')->addDay()->toDateString()) }}" class="btn sv-btn">
                        <i class="fas fa-calendar-plus"></i> Preparar sectorización de mañana
                    </a>
                </div>
            </div>
        </div>
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

    .sv-hero__inner{
        padding: 18px 18px 16px;
        text-align: center;
    }

    .sv-hero__badge{
        display:inline-flex;
        align-items:center;
        gap:10px;
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
        width: 8px;
        height: 8px;
        border-radius: 999px;
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

    .sv-panel{
        border-radius: 22px;
        border: 1px solid rgba(255,255,255,.12);
        background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.05));
        box-shadow: 0 10px 35px rgba(0,0,0,.22);
        overflow: hidden;
    }

    .sv-panel__header{
        padding: 18px 18px 10px 18px;
        border-bottom: 1px solid rgba(255,255,255,.08);
    }

    .sv-panel__title{
        font-weight: 900;
        font-size: 16px;
        color: var(--sv-text);
    }

    .sv-panel__desc{
        margin-top: 4px;
        font-size: 12.5px;
        color: var(--sv-muted);
    }

    .sv-panel__footer{
        padding: 16px 18px 18px 18px;
        border-top: 1px solid rgba(255,255,255,.08);
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .sv-table{
        color: var(--sv-text);
        margin-bottom: 0;
    }

    .sv-table thead th{
        border-top: 0 !important;
        border-bottom: 1px solid rgba(255,255,255,.10) !important;
        color: rgba(234,240,255,.80);
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .4px;
        background: rgba(255,255,255,.04);
    }

    .sv-table td{
        border-top: 1px solid rgba(255,255,255,.08) !important;
        vertical-align: middle;
        color: var(--sv-text);
        font-weight: 650;
    }

    .sv-btn{
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

    .sv-empty{
        padding: 40px 20px;
        text-align: center;
        color: var(--sv-muted);
    }

    .sv-empty i{
        font-size: 34px;
        margin-bottom: 12px;
        color: rgba(255,255,255,.75);
    }

    .sv-empty__title{
        font-size: 16px;
        font-weight: 900;
        color: var(--sv-text);
    }

    .sv-empty__desc{
        margin-top: 6px;
        font-size: 13px;
        color: var(--sv-muted);
    }
</style>
@stop

@section('js')
    <script> console.log('Sectorizaciones'); </script>
@stop
