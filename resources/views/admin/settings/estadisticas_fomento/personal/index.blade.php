@extends('adminlte::page')

@php
    $titulos = [
        'resumen' => ['Estadísticas del personal', 'Concentrado de adscripción, servicios, incapacidades y disciplina'],
        'rendimiento' => ['Equidad en servicios', 'Comparativo para distribuir los servicios entre todo el personal'],
        'incapacidades' => ['Incapacidades del personal', 'Frecuencia y días acumulados dentro del periodo'],
        'disciplina' => ['Disciplina del personal', 'Amonestaciones y arrestos registrados dentro del periodo'],
    ];
    [$titulo, $subtitulo] = $titulos[$modo] ?? $titulos['resumen'];
@endphp

@section('title', $titulo . ' · Fomento')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge"><span class="sv-dot"></span><span>Fomento · Personal · {{ date('d/m/Y', strtotime($fechaInicio)) }} - {{ date('d/m/Y', strtotime($fechaFin)) }}</span></div>
            <div class="sv-hero__title">{{ $titulo }}</div>
            <div class="sv-hero__subtitle">{{ $subtitulo }}</div>
        </div>
    </div>
@stop

@section('content')
    <div class="sv-personal-nav">
        <a href="{{ route('settings.estadisticas_fomento.personal', request()->query()) }}" class="btn {{ $modo === 'resumen' ? 'active' : '' }}"><i class="fa-solid fa-table-cells-large"></i> Concentrado</a>
        <a href="{{ route('settings.estadisticas_fomento.personal.rendimiento', request()->query()) }}" class="btn {{ $modo === 'rendimiento' ? 'active' : '' }}"><i class="fa-solid fa-scale-balanced"></i> Equidad</a>
        <a href="{{ route('settings.estadisticas_fomento.personal.incapacidades', request()->query()) }}" class="btn {{ $modo === 'incapacidades' ? 'active' : '' }}"><i class="fa-solid fa-notes-medical"></i> Incapacidades</a>
        <a href="{{ route('settings.estadisticas_fomento.personal.disciplina', request()->query()) }}" class="btn {{ $modo === 'disciplina' ? 'active' : '' }}"><i class="fa-solid fa-gavel"></i> Disciplina</a>
        <a href="{{ route('settings.estadisticas_fomento.servicios_personal', ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]) }}" class="btn"><i class="fa-solid fa-list-check"></i> Servicios</a>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6"><div class="sv-kpi"><div class="sv-kpi__icon bg-info"><i class="fa-solid fa-users"></i></div><div><div class="sv-kpi__label">Personal</div><div class="sv-kpi__value">{{ number_format($totales['personal']) }}</div><small>{{ number_format($totales['activos']) }} activos</small></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="sv-kpi"><div class="sv-kpi__icon bg-success"><i class="fa-solid fa-clipboard-check"></i></div><div><div class="sv-kpi__label">Servicios</div><div class="sv-kpi__value">{{ number_format($totales['servicios']) }}</div><small>Promedio {{ number_format($totales['promedio_servicios'], 1) }}</small></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="sv-kpi"><div class="sv-kpi__icon bg-warning"><i class="fa-solid fa-notes-medical"></i></div><div><div class="sv-kpi__label">Incapacidades</div><div class="sv-kpi__value">{{ number_format($totales['incapacidades']) }}</div><small>{{ number_format($totales['dias_incapacidad']) }} días</small></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="sv-kpi"><div class="sv-kpi__icon bg-danger"><i class="fa-solid fa-gavel"></i></div><div><div class="sv-kpi__label">Disciplina</div><div class="sv-kpi__value">{{ number_format($totales['incidencias_disciplina']) }}</div><small>{{ number_format($totales['amonestaciones']) }} amon. · {{ number_format($totales['arrestos']) }} arrestos</small></div></div></div>
    </div>

    <div class="sv-panel">
        <div class="sv-panel__body">
            <form method="GET" class="sv-personal-filter">
                <div><label>Desde</label><input type="date" name="fecha_inicio" class="form-control" value="{{ $fechaInicio }}"></div>
                <div><label>Hasta</label><input type="date" name="fecha_fin" class="form-control" value="{{ $fechaFin }}"></div>
                <div class="grow"><label>Buscar personal</label><input type="search" name="q" class="form-control" value="{{ $busqueda }}" placeholder="Nombre o número de empleado"></div>
                <button class="btn sv-btn"><i class="fa-solid fa-filter"></i> Actualizar</button>
            </form>
        </div>
    </div>

    <div class="sv-panel">
        <div class="sv-panel__title"><i class="fa-solid fa-users-viewfinder"></i><span>{{ $titulo }}</span></div>
        <div class="table-responsive sv-personal-table">
            <table class="table table-sm table-hover mb-0">
                <thead><tr>
                    <th>#</th><th>Elemento</th><th>No. empleado</th><th>Puesto</th><th>Turno</th><th>Estatus</th>
                    @if(in_array($modo, ['resumen', 'rendimiento'], true))
                        <th>Servicios</th><th>Días servicio</th><th>Último servicio</th><th>Balance</th>
                    @endif
                    @if(in_array($modo, ['resumen', 'incapacidades'], true))
                        <th>Incapacidades</th><th>Días incapacidad</th>
                    @endif
                    @if(in_array($modo, ['resumen', 'disciplina'], true))
                        <th>Amonestaciones</th><th>Arrestos</th>
                    @endif
                    <th>Expediente</th>
                </tr></thead>
                <tbody>
                @forelse($rows as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="text-left"><strong>{{ $row['nombre_completo'] ?: 'N/A' }}</strong></td>
                        <td>{{ $row['numero_empleado'] ?: 'N/A' }}</td><td>{{ $row['puesto'] ?: 'N/A' }}</td><td>{{ $row['turno'] ?: 'Sin turno' }}</td>
                        <td><span class="badge badge-{{ strtoupper((string) $row['estatus']) === 'ACTIVO' ? 'success' : 'secondary' }}">{{ $row['estatus'] ?: 'N/A' }}</span></td>
                        @if(in_array($modo, ['resumen', 'rendimiento'], true))
                            <td><strong>{{ number_format($row['total_servicios']) }}</strong></td><td>{{ number_format($row['dias_servicio']) }}</td>
                            <td>{{ $row['ultimo_servicio'] ? \Carbon\Carbon::parse($row['ultimo_servicio'])->format('d/m/Y') : 'N/A' }}</td>
                            <td><span class="sv-balance sv-balance--{{ str_replace(' ', '-', strtolower($row['balance_servicios'])) }}">{{ $row['balance_servicios'] }}</span></td>
                        @endif
                        @if(in_array($modo, ['resumen', 'incapacidades'], true))
                            <td>{{ number_format($row['incapacidades']) }}</td><td>{{ number_format($row['dias_incapacidad']) }}</td>
                        @endif
                        @if(in_array($modo, ['resumen', 'disciplina'], true))
                            <td>{{ number_format($row['amonestaciones']) }}</td><td>{{ number_format($row['arrestos']) }}</td>
                        @endif
                        <td>@can('ver personal')<a href="{{ route('personal.show', $row['id']) }}" class="btn sv-btn sv-btn--mini"><i class="fa-regular fa-eye"></i></a>@else<span class="text-muted">N/A</span>@endcan</td>
                    </tr>
                @empty
                    <tr><td colspan="18" class="text-center py-5"><i class="fa-solid fa-users-slash fa-2x mb-3 d-block"></i>No hay personal adscrito a la Unidad de Fomento. El reporte quedará disponible automáticamente cuando se registre personal.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/sv-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/sv-carreteras-reportes.css') }}">
<style>
.sv-personal-nav{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}.sv-personal-nav .btn{background:#172033;color:#dbeafe;border:1px solid rgba(255,255,255,.12);border-radius:12px;font-weight:800}.sv-personal-nav .btn.active{background:#0ea5e9;color:#fff}.sv-personal-filter{display:flex;gap:12px;align-items:end;flex-wrap:wrap}.sv-personal-filter label{display:block;color:var(--sv-muted);font-size:12px;font-weight:900}.sv-personal-filter .grow{flex:1;min-width:230px}.sv-personal-filter .form-control{background:rgba(12,16,28,.65);color:var(--sv-text);border:1px solid rgba(255,255,255,.16)}.sv-personal-table{padding:0 12px 12px}.sv-personal-table th,.sv-personal-table td{text-align:center;vertical-align:middle;white-space:nowrap}.sv-balance{display:inline-block;padding:5px 8px;border-radius:999px;background:#334155;font-size:10px;font-weight:900}.sv-balance--prioridad-siguiente{background:#166534;color:#dcfce7}.sv-balance--por-encima-del-promedio{background:#92400e;color:#fef3c7}.sv-btn--mini{width:36px;height:34px;padding:0}@media(max-width:768px){.sv-personal-filter>*{width:100%}.sv-personal-filter .btn{width:100%}}
</style>
@stop
