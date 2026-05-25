@extends('adminlte::page')

@section('title', 'Municipios atendidos Fomento')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Fomento · Municipios · {{ $anio }}</span>
            </div>

            <div class="sv-hero__title">
                Municipios atendidos
            </div>

            <div class="sv-hero__subtitle">
                Eventos y población atendida por municipio, mes y total anual
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="sv-panel">
        <div class="sv-panel__body sv-panel__body--header">
            <form method="GET" action="{{ route('settings.estadisticas_fomento.municipios_atendidos') }}" class="sv-inline-form">
                <div class="sv-inline-field">
                    <label for="anio">Año</label>
                    <select name="anio" id="anio" class="form-control">
                        @foreach($aniosDisponibles as $anioDisponible)
                            <option value="{{ $anioDisponible }}" {{ (int) $anioDisponible === (int) $anio ? 'selected' : '' }}>
                                {{ $anioDisponible }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn sv-btn">
                    <i class="fa-solid fa-filter"></i> Consultar
                </button>
            </form>

            <div class="sv-toolbar">
                <a href="{{ route('settings.estadisticas_fomento.municipios_atendidos.exportar', ['anio' => $anio]) }}" class="btn sv-btn sv-btn-success">
                    <i class="fa-solid fa-file-excel"></i> Exportar Excel
                </a>

                <a href="{{ route('settings.estadisticas_fomento.index') }}" class="btn sv-btn">
                    <i class="fas fa-arrow-left"></i> Regresar
                </a>
            </div>
        </div>
    </div>

    <div class="sv-panel">
        <div class="sv-panel__title">
            <i class="fa-solid fa-table"></i>
            <span>Resumen {{ $anio }}</span>
        </div>

        <div class="municipios-table-wrap">
            <table class="table table-sm municipios-table mb-0">
                <thead>
                    <tr>
                        <th rowspan="2" class="municipio-head">Municipio</th>
                        @foreach($meses as $mes)
                            <th colspan="2" class="text-center">{{ $mes['nombre'] }}</th>
                        @endforeach
                        <th colspan="2" class="text-center total-head">Total anual</th>
                    </tr>
                    <tr>
                        @foreach($meses as $mes)
                            <th class="text-center">Eventos</th>
                            <th class="text-center">Pob. atendida</th>
                        @endforeach
                        <th class="text-center total-head">Eventos</th>
                        <th class="text-center total-head">Pob. atendida</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="municipio-cell">{{ $row['municipio'] }}</td>
                            @foreach($meses as $mesNumero => $mes)
                                <td class="text-right">{{ number_format($row['meses'][$mesNumero]['eventos']) }}</td>
                                <td class="text-right">{{ number_format($row['meses'][$mesNumero]['poblacion']) }}</td>
                            @endforeach
                            <td class="text-right total-cell">{{ number_format($row['total_eventos']) }}</td>
                            <td class="text-right total-cell">{{ number_format($row['total_poblacion']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 1 + (count($meses) * 2) + 2 }}" class="text-center py-4">
                                No hay actividades de Fomento con municipio para {{ $anio }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th class="municipio-cell total-row">TOTAL</th>
                        @foreach($meses as $mesNumero => $mes)
                            <th class="text-right total-row">{{ number_format($totales['meses'][$mesNumero]['eventos']) }}</th>
                            <th class="text-right total-row">{{ number_format($totales['meses'][$mesNumero]['poblacion']) }}</th>
                        @endforeach
                        <th class="text-right total-row">{{ number_format($totales['total_eventos']) }}</th>
                        <th class="text-right total-row">{{ number_format($totales['total_poblacion']) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/sv-dashboard.css') }}">
<style>
    .sv-panel__body--header{
        display:flex;
        justify-content:space-between;
        align-items:end;
        gap:12px;
        flex-wrap:wrap;
    }

    .sv-inline-form,
    .sv-toolbar{
        display:flex;
        align-items:end;
        gap:10px;
        flex-wrap:wrap;
    }

    .sv-inline-field label{
        display:block;
        margin-bottom:5px;
        font-weight:900;
        font-size:12px;
        color:var(--sv-muted);
    }

    .sv-inline-field .form-control{
        min-width:130px;
        color:var(--sv-text);
        background:rgba(12,16,28,.65);
        border:1px solid rgba(255,255,255,.16);
        border-radius:12px;
    }

    .sv-btn-success{
        background:rgba(25,211,140,.18);
        border-color:rgba(25,211,140,.34);
    }

    .municipios-table-wrap{
        width:100%;
        overflow:auto;
        max-height:calc(100vh - 265px);
    }

    .municipios-table{
        min-width:2240px;
        table-layout:fixed;
    }

    .municipios-table th,
    .municipios-table td{
        width:82px;
        font-size:11px;
        white-space:nowrap;
        border-color:rgba(255,255,255,.16) !important;
    }

    .municipios-table thead th{
        position:sticky;
        top:0;
        z-index:3;
        background:#1f2a3f;
        color:rgba(234,240,255,.94);
        vertical-align:middle;
    }

    .municipios-table thead tr:nth-child(2) th{
        top:31px;
        background:#25324a;
    }

    .municipios-table .municipio-head,
    .municipios-table .municipio-cell{
        width:210px;
        min-width:210px;
        max-width:210px;
        position:sticky;
        left:0;
        z-index:4;
    }

    .municipios-table .municipio-head{
        background:#1f2a3f;
    }

    .municipios-table .municipio-cell{
        background:#ffe699;
        color:#152033;
        font-weight:850;
        overflow:hidden;
        text-overflow:ellipsis;
    }

    .municipios-table .total-head{
        background:#32343d;
    }

    .municipios-table .total-cell{
        font-weight:900;
        color:rgba(234,240,255,.94);
        background:rgba(255,255,255,.06);
    }

    .municipios-table .total-row{
        background:#7f7f7f !important;
        color:#fff !important;
        font-weight:950;
    }

    @media (max-width: 768px){
        .sv-panel__body--header{
            align-items:stretch;
        }

        .sv-inline-form,
        .sv-toolbar{
            width:100%;
        }

        .sv-inline-field,
        .sv-inline-field .form-control,
        .sv-toolbar .btn{
            width:100%;
        }
    }
</style>
@stop
