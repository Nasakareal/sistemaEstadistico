@extends('adminlte::page')

@section('title', 'Excel Diario de Siniestros')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Siniestros · Excel Diario · Cortes</span>
            </div>

            <div class="sv-hero__title">
                Excel Diario de Siniestros
            </div>

            <div class="sv-hero__subtitle">
                Concentrados diarios generados automáticamente
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
                        <div class="sv-panel__title">Excel diarios generados</div>
                        <div class="sv-panel__desc">
                            Selecciona una fecha para descargar el concentrado diario correspondiente.
                        </div>
                    </div>
                </div>

                @if(isset($cortes) && count($cortes))
                    <div class="table-responsive">
                        <table class="table sv-table mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha de corte</th>
                                    <th>Archivo</th>
                                    <th width="180">Acción</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($cortes as $corte)
                                    <tr>
                                        <td>
                                            <div class="sv-date">
                                                <i class="fa-regular fa-calendar"></i>

                                                @if(!empty($corte['fecha']))
                                                    {{ \Carbon\Carbon::parse($corte['fecha'])->format('d/m/Y') }}
                                                @endif
                                            </div>
                                        </td>

                                        <td>
                                            <div class="sv-file">
                                                <div class="sv-file__icon">
                                                    <i class="fa-solid fa-file-excel"></i>
                                                </div>

                                                <div>
                                                    <div class="sv-file__name">
                                                        {{ $corte['archivo'] ?? '' }}
                                                    </div>

                                                    <div class="sv-file__type">
                                                        Microsoft Excel
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <a href="{{ $corte['url_descarga'] }}"
                                               class="btn sv-btn">
                                                <i class="fas fa-download"></i>
                                                Descargar
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="sv-empty">
                        <i class="fa-solid fa-file-excel"></i>

                        <div class="sv-empty__title">
                            No hay Excel diarios disponibles
                        </div>

                        <div class="sv-empty__desc">
                            Los concentrados diarios de Siniestros aparecerán aquí después de ser generados automáticamente.
                        </div>
                    </div>
                @endif
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
        display: inline-flex;
        align-items: center;
        gap: 10px;
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
        display: inline-block;
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
        border-radius: var(--sv-radius);
        border: 1px solid var(--sv-stroke);
        background: linear-gradient(180deg, var(--sv-card), var(--sv-card2));
        box-shadow: 0 10px 35px rgba(0,0,0,.22);
        overflow: hidden;
    }

    .sv-panel__header{
        padding: 18px 18px 14px 18px;
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
        vertical-align: middle;
    }

    .sv-table td{
        border-top: 1px solid rgba(255,255,255,.08) !important;
        vertical-align: middle;
        color: var(--sv-text);
        font-weight: 650;
    }

    .sv-table tbody tr{
        transition: .15s ease;
    }

    .sv-table tbody tr:hover{
        background: rgba(255,255,255,.04);
    }

    .sv-date{
        display: inline-flex;
        align-items: center;
        gap: 9px;
        font-weight: 800;
    }

    .sv-date i{
        color: rgba(45,168,255,.95);
        font-size: 15px;
    }

    .sv-file{
        display: flex;
        align-items: center;
        gap: 11px;
    }

    .sv-file__icon{
        width: 40px;
        height: 40px;
        flex: 0 0 auto;
        display: grid;
        place-items: center;
        border-radius: 12px;
        border: 1px solid rgba(25,211,140,.25);
        background: rgba(25,211,140,.10);
    }

    .sv-file__icon i{
        font-size: 19px;
        color: #19D38C;
    }

    .sv-file__name{
        color: var(--sv-text);
        font-size: 13px;
        font-weight: 850;
        word-break: break-word;
    }

    .sv-file__type{
        margin-top: 2px;
        color: var(--sv-muted);
        font-size: 11.5px;
        font-weight: 650;
    }

    .sv-btn{
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 14px;
        font-weight: 900;
        border: 1px solid rgba(25,211,140,.35) !important;
        background: linear-gradient(
            135deg,
            rgba(25,211,140,.20),
            rgba(45,168,255,.20)
        ) !important;
        color: rgba(234,240,255,.95) !important;
        padding: 8px 12px;
    }

    .sv-btn:hover{
        transform: translateY(-1px);
        border-color: rgba(25,211,140,.55) !important;
        background: linear-gradient(
            135deg,
            rgba(25,211,140,.30),
            rgba(45,168,255,.28)
        ) !important;
        color: rgba(234,240,255,.98) !important;
    }

    .sv-empty{
        padding: 48px 20px;
        text-align: center;
        color: var(--sv-muted);
    }

    .sv-empty i{
        font-size: 42px;
        margin-bottom: 14px;
        color: #19D38C;
    }

    .sv-empty__title{
        font-size: 16px;
        font-weight: 900;
        color: var(--sv-text);
    }

    .sv-empty__desc{
        margin: 6px auto 0;
        max-width: 520px;
        font-size: 13px;
        color: var(--sv-muted);
    }

    @media (max-width: 767.98px){
        .sv-panel__header{
            padding: 15px;
        }

        .sv-table th,
        .sv-table td{
            white-space: nowrap;
        }

        .sv-file__name{
            max-width: 240px;
        }
    }
</style>
@stop

@section('js')
    <script>
        console.log('Excel Diario de Siniestros');
    </script>
@stop
