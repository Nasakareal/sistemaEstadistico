@extends('adminlte::page')

@section('title', 'Estadísticas de Carreteras')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Carreteras · Indicadores · Reportes</span>
            </div>

            <div class="sv-hero__title">
                Estadísticas de Carreteras
            </div>

            <div class="sv-hero__subtitle">
                Panel de análisis · Unidad de Protección en Carreteras
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">

        {{-- EXCEL DE NOVEDADES --}}
        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-card">
                <div class="sv-card__icon bg-success">
                    <i class="fa-solid fa-file-excel"></i>
                </div>
                <div class="sv-card__body">
                    <div class="sv-card__title">Excel de Novedades</div>
                    <div class="sv-card__desc">
                        Genera el reporte consolidado de actividades de carreteras en formato Excel.
                    </div>
                    <a href="{{ route('settings.estadisticas_carreteras.excel_novedades') }}" class="btn sv-btn">
                        <i class="fas fa-download"></i> Generar Excel
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
    .sv-hero__inner{ padding: 18px; text-align: center; }
    .sv-hero__badge{
        display:inline-flex; align-items:center; gap:10px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(0,0,0,.18);
        border: 1px solid rgba(255,255,255,.10);
        color: rgba(234,240,255,.85);
        font-weight: 800;
        font-size: 12px;
    }
    .sv-dot{
        width: 8px; height: 8px; border-radius: 999px;
        background: #19D38C;
        box-shadow: 0 0 0 5px rgba(25,211,140,.14);
    }
    .sv-hero__title{
        margin-top: 10px;
        font-weight: 950;
        font-size: 26px;
        color: var(--sv-text);
    }
    .sv-hero__subtitle{
        margin-top: 6px;
        font-size: 13px;
        color: var(--sv-muted);
    }

    .sv-card{
        display:flex;
        gap: 14px;
        padding: 14px;
        margin-bottom: 16px;
        border-radius: var(--sv-radius);
        border: 1px solid var(--sv-stroke);
        background: linear-gradient(180deg, var(--sv-card), var(--sv-card2));
        box-shadow: 0 10px 35px rgba(0,0,0,.22);
    }

    .sv-card__icon{
        width: 52px; height: 52px;
        border-radius: 18px;
        display:grid; place-items:center;
        border: 1px solid rgba(255,255,255,.14);
    }

    .sv-card__body{ flex: 1; }

    .sv-card__title{
        font-weight: 900;
        font-size: 14px;
        color: var(--sv-text);
    }

    .sv-card__desc{
        margin-top: 6px;
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
</style>
@stop

@section('js')
<script>
    console.log("Estadísticas de Carreteras");
</script>
@stop
