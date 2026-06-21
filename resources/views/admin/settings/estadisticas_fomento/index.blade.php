@extends('adminlte::page')

@section('title', 'Estadísticas Fomento')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Fomento · Reportes · Exportaciones</span>
            </div>

            <div class="sv-hero__title">
                Estadísticas de Fomento
            </div>

            <div class="sv-hero__subtitle">
                Estado de fuerza y actividades de la Unidad de Fomento a la Cultura Vial
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-card">
                <div class="sv-card__icon bg-success">
                    <i class="fa-solid fa-file-excel"></i>
                </div>
                <div class="sv-card__body">
                    <div class="sv-card__title">Excel Diario</div>
                    <div class="sv-card__desc">Consulta y descarga el corte diario de Fomento.</div>
                    <a href="{{ route('settings.estadisticas_fomento.excel_diario') }}" class="btn sv-btn">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-card">
                <div class="sv-card__icon bg-info">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <div class="sv-card__body">
                    <div class="sv-card__title">Municipios atendidos</div>
                    <div class="sv-card__desc">Resumen mensual por municipio con eventos y población atendida.</div>
                    <a href="{{ route('settings.estadisticas_fomento.municipios_atendidos') }}" class="btn sv-btn">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-card">
                <div class="sv-card__icon bg-warning">
                    <i class="fa-solid fa-ranking-star"></i>
                </div>
                <div class="sv-card__body">
                    <div class="sv-card__title">Servicios por personal</div>
                    <div class="sv-card__desc">Listado de personal con conteo de servicios registrados.</div>
                    <a href="{{ route('settings.estadisticas_fomento.servicios_personal') }}" class="btn sv-btn">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-card">
                <div class="sv-card__icon bg-primary">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <div class="sv-card__body">
                    <div class="sv-card__title">Aseguramientos</div>
                    <div class="sv-card__desc">Vista común de aseguramientos con tarjeta nativa para compartir.</div>
                    <a href="{{ route('estadisticas_aseguramientos.index', ['unidad_slug' => 'fomento-cultura-vial']) }}" class="btn sv-btn">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/sv-dashboard.css') }}">
<style>
    .sv-card{
        display:flex;
        gap:14px;
        padding:14px;
        margin-bottom:16px;
        border-radius:var(--sv-radius);
        border:1px solid var(--sv-stroke);
        background:linear-gradient(180deg,var(--sv-card),var(--sv-card2));
        box-shadow:0 10px 35px rgba(0,0,0,.22);
        min-height:108px;
    }

    .sv-card__icon{
        width:52px;
        height:52px;
        border-radius:18px;
        display:grid;
        place-items:center;
        border:1px solid rgba(255,255,255,.14);
        box-shadow:0 12px 25px rgba(0,0,0,.22);
        flex:0 0 auto;
    }

    .sv-card__icon i{
        font-size:20px;
        color:rgba(255,255,255,.95);
    }

    .sv-card__body{
        flex:1;
        min-width:0;
    }

    .sv-card__title{
        font-weight:900;
        font-size:14px;
        color:var(--sv-text);
        line-height:1.15;
    }

    .sv-card__desc{
        margin-top:6px;
        font-weight:650;
        font-size:12.5px;
        color:var(--sv-muted);
    }
</style>
@stop
