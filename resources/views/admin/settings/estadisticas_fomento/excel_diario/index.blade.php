@extends('adminlte::page')

@section('title', 'Excel Diario Fomento')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Fomento · Excel Diario · Cortes</span>
            </div>

            <div class="sv-hero__title">
                Excel Diario Fomento
            </div>

            <div class="sv-hero__subtitle">
                Listado de archivos Excel disponibles para descarga
            </div>
        </div>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="sv-panel">
                <div class="sv-panel__body sv-panel__body--header">
                    <div>
                        <div class="sv-panel__heading">Archivos generados</div>
                        <div class="sv-panel__desc">El corte se genera diariamente a las 18:00 horas.</div>
                    </div>

                    <a href="{{ route('settings.estadisticas_fomento.index') }}" class="btn sv-btn">
                        <i class="fas fa-arrow-left"></i> Regresar
                    </a>
                </div>

                @if(isset($cortes) && count($cortes))
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Archivo</th>
                                    <th width="180">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cortes as $corte)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($corte['fecha'])->format('d/m/Y') }}</td>
                                        <td>{{ $corte['archivo'] ?? '' }}</td>
                                        <td>
                                            <a href="{{ $corte['url_descarga'] }}" class="btn sv-btn">
                                                <i class="fas fa-download"></i> Descargar
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
                        <div class="sv-empty__title">No hay archivos disponibles</div>
                        <div class="sv-empty__desc">Cuando existan Excel diarios de Fomento aparecerán aquí.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/sv-dashboard.css') }}">
<style>
    .sv-panel__body--header{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:12px;
        flex-wrap:wrap;
        border-bottom:1px solid rgba(255,255,255,.10);
    }

    .sv-panel__heading{
        font-weight:900;
        font-size:16px;
        color:var(--sv-text);
    }

    .sv-panel__desc{
        margin-top:4px;
        font-size:12.5px;
        color:var(--sv-muted);
    }

    .sv-empty{
        padding:40px 20px;
        text-align:center;
        color:var(--sv-muted);
    }

    .sv-empty i{
        font-size:34px;
        margin-bottom:12px;
        color:rgba(255,255,255,.75);
    }

    .sv-empty__title{
        font-size:16px;
        font-weight:900;
        color:var(--sv-text);
    }

    .sv-empty__desc{
        margin-top:6px;
        font-size:13px;
        color:var(--sv-muted);
    }

    /* INICIO ESTILOS GENERADOR TEMPORAL FOMENTO: borrar junto con el bloque marcado en content. */
    .sv-panel--temp-generator{
        border-color:rgba(246,184,75,.42);
    }

    .sv-temp-generator{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:14px;
        flex-wrap:wrap;
        background:rgba(246,184,75,.08);
    }

    .sv-temp-generator__form{
        display:flex;
        gap:8px;
        align-items:center;
        flex-wrap:wrap;
    }

    .sv-temp-generator__form input[type="date"]{
        min-width:180px;
    }
    /* FIN ESTILOS GENERADOR TEMPORAL FOMENTO. */
</style>
@stop
