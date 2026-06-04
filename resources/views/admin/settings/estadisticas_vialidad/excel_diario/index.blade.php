@extends('adminlte::page')

@section('title', 'Excel Diario Vialidades Urbanas')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Vialidades Urbanas · Excel Diario · Cortes</span>
            </div>

            <div class="sv-hero__title">
                Excel Diario Vialidades Urbanas
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
            <div class="sv-panel sv-generator mb-3">
                <div class="sv-generator__body">
                    <div>
                        <div class="sv-panel__heading">Generar corte diario</div>
                        <div class="sv-panel__desc">
                            Corte {{ $horaCorte ?? '18:00:00' }} ·
                            {{ isset($inicioSugerido) ? $inicioSugerido->format('d/m/Y H:i') : '' }}
                            a
                            {{ isset($finSugerido) ? $finSugerido->format('d/m/Y H:i') : '' }}
                        </div>
                    </div>

                    <form method="POST" action="{{ route('settings.estadisticas_vialidad.excel_diario.generar') }}" class="sv-generator__form" autocomplete="off">
                        @csrf
                        <input
                            type="date"
                            name="fecha"
                            class="form-control"
                            value="{{ old('fecha', $fechaSugerida ?? now('America/Mexico_City')->toDateString()) }}"
                            required
                        >
                        <button type="submit" class="btn sv-btn">
                            <i class="fa-solid fa-file-circle-plus"></i> Generar Excel
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="sv-panel">
                <div class="sv-panel__body sv-panel__body--header">
                    <div>
                        <div class="sv-panel__heading">Archivos generados</div>
                        <div class="sv-panel__desc">El corte se genera diariamente a las 18:00 horas.</div>
                    </div>

                    <a href="{{ route('settings.estadisticas_vialidad.index') }}" class="btn sv-btn">
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
                        <div class="sv-empty__desc">Cuando existan Excel diarios de Vialidades Urbanas aparecerán aquí.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/sv-dashboard.css') }}">
<style>
    .sv-panel__body--header,
    .sv-generator__body{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:12px;
        flex-wrap:wrap;
        border-bottom:1px solid rgba(255,255,255,.10);
    }

    .sv-generator{
        border-color:rgba(25,211,140,.26);
    }

    .sv-generator__body{
        padding:18px;
    }

    .sv-generator__form{
        display:flex;
        gap:8px;
        align-items:center;
        flex-wrap:wrap;
    }

    .sv-generator__form input[type="date"]{
        min-width:180px;
        max-width:220px;
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
</style>
@stop
