@extends('adminlte::page')

@section('title', 'Disciplina del personal')

@section('content_header')
<div class="sv-report-hero discipline">
    <div>
        <div class="sv-report-kicker"><i class="fa-solid fa-scale-balanced"></i> Protección a Carreteras</div>
        <h1>Disciplina del personal</h1>
        <p>Amonestaciones y arrestos registrados en el expediente institucional</p>
    </div>
    <div class="sv-report-actions">
        <a href="{{ route('estadisticas_carreteras.concentrado', request()->query()) }}" class="btn"><i class="fa-solid fa-table-cells-large"></i> Concentrado</a>
        <a href="{{ route('estadisticas_carreteras.index') }}" class="btn"><i class="fa-solid fa-chart-line"></i> Panel</a>
        <a href="{{ route('estadisticas_carreteras.elementos', request()->query()) }}" class="btn"><i class="fa-solid fa-ranking-star"></i> Elementos</a>
        <a href="{{ route('estadisticas_carreteras.rendimiento', request()->query()) }}" class="btn"><i class="fa-solid fa-award"></i> Rendimiento</a>
        <a href="{{ route('estadisticas_carreteras.incapacidades', request()->query()) }}" class="btn"><i class="fa-solid fa-notes-medical"></i> Incapacidades</a>
        <a href="{{ route('estadisticas_carreteras.disciplina', request()->query()) }}" class="btn active"><i class="fa-solid fa-scale-balanced"></i> Disciplina</a>
        @can('editar personal')
            <button type="button" class="btn" data-toggle="modal" data-target="#modalDisciplina"><i class="fa-solid fa-file-circle-plus"></i> Registrar</button>
        @endcan
        <button type="button" class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir</button>
    </div>
</div>
@stop

@section('content')
@if(session('success'))
    <div class="alert alert-success"><i class="fa-solid fa-circle-check mr-1"></i>{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger"><strong>No se pudo guardar:</strong> {{ $errors->first() }}</div>
@endif

<div class="sv-report-panel sv-report-filters">
    <form method="GET" action="{{ route('estadisticas_carreteras.disciplina') }}">
        <div class="row align-items-end">
            <div class="col-lg-2 col-md-6 mb-2"><label>Desde</label><input type="date" name="desde" value="{{ $filtros['desde'] }}" class="form-control" required></div>
            <div class="col-lg-2 col-md-6 mb-2"><label>Hasta</label><input type="date" name="hasta" value="{{ $filtros['hasta'] }}" class="form-control" required></div>
            <div class="col-lg-3 col-md-6 mb-2"><label>Destacamento</label><select name="destacamento_id" class="form-control"><option value="">Todos</option>@foreach($destacamentos as $destacamento)<option value="{{ $destacamento->id }}" {{ $filtros['destacamento_id'] === (int) $destacamento->id ? 'selected' : '' }}>{{ $destacamento->nombre }}</option>@endforeach</select></div>
            <div class="col-lg-3 col-md-6 mb-2"><label>Buscar elemento</label><input type="search" name="q" value="{{ $filtros['q'] }}" class="form-control" placeholder="Nombre, empleado o placa"></div>
            <div class="col-lg-2 col-md-12 mb-2 d-flex"><button class="btn sv-report-primary flex-fill"><i class="fa-solid fa-filter"></i> Actualizar</button><a href="{{ route('estadisticas_carreteras.disciplina') }}" class="btn sv-report-clear ml-2" title="Restablecer"><i class="fa-solid fa-rotate-left"></i></a></div>
        </div>
    </form>
</div>

<div class="row sv-report-kpis">
    <div class="col-xl-3 col-sm-6"><div class="sv-report-kpi cyan"><span><i class="fa-solid fa-users"></i></span><div><small>Personal visible</small><strong>{{ number_format($totales['personal']) }}</strong></div></div></div>
    <div class="col-xl-3 col-sm-6"><div class="sv-report-kpi violet"><span><i class="fa-solid fa-user-shield"></i></span><div><small>Con incidencias</small><strong>{{ number_format($totales['con_incidencias']) }}</strong></div></div></div>
    <div class="col-xl-3 col-sm-6"><div class="sv-report-kpi amber"><span><i class="fa-solid fa-triangle-exclamation"></i></span><div><small>Amonestaciones</small><strong>{{ number_format($totales['amonestaciones']) }}</strong></div></div></div>
    <div class="col-xl-3 col-sm-6"><div class="sv-report-kpi red"><span><i class="fa-solid fa-gavel"></i></span><div><small>Arrestos</small><strong>{{ number_format($totales['arrestos']) }}</strong></div></div></div>
</div>

<div class="sv-report-panel">
    <div class="sv-report-panel-title">
        <div><span>Ranking por recurrencia disciplinaria</span><small>Se muestran todos los elementos; primero aparecen quienes acumulan más registros en el periodo</small></div>
        <span class="sv-report-cut">{{ \Carbon\Carbon::parse($filtros['desde'])->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($filtros['hasta'])->format('d/m/Y') }}</span>
    </div>
    <div class="table-responsive">
        <table class="table sv-report-table mb-0">
            <thead><tr><th class="text-center">Pos.</th><th>Destacamento</th><th>Elemento</th><th class="text-center">Amonestaciones</th><th class="text-center">Arrestos</th><th class="text-center">Total</th><th>Última incidencia</th><th class="text-center no-print">Acciones</th></tr></thead>
            <tbody>
                @forelse($ranking as $persona)
                    <tr class="{{ $persona['total'] > 0 && $persona['posicion'] <= 3 ? 'top-row' : '' }}">
                        <td class="text-center"><span class="sv-rank {{ $persona['total'] > 0 ? 'rank-'.$persona['posicion'] : '' }}">{{ $persona['posicion'] }}</span></td>
                        <td><span class="sv-detachment">{{ $persona['destacamento'] }}</span></td>
                        <td><strong>{{ $persona['nombre'] }}</strong><small class="d-block text-muted">Empleado: {{ $persona['numero_empleado'] ?: 'N/D' }} · Placa: {{ $persona['numero_placa'] ?: 'N/D' }} · {{ $persona['estatus'] ?: 'SIN ESTATUS' }}</small></td>
                        <td class="metric">{{ number_format($persona['amonestaciones']) }}</td>
                        <td class="metric {{ $persona['arrestos'] > 0 ? 'text-danger font-weight-bold' : '' }}">{{ number_format($persona['arrestos']) }}</td>
                        <td class="metric emph">{{ number_format($persona['total']) }}</td>
                        <td>@if($persona['ultima_fecha'])<strong>{{ $persona['ultima_fecha']->format('d/m/Y') }}</strong><small class="d-block text-muted">{{ $persona['ultimo_tipo'] }}</small>@else<span class="text-muted">Sin incidencias</span>@endif</td>
                        <td class="text-center no-print">
                            @can('editar personal')<button type="button" class="btn btn-sm btn-outline-danger js-registrar-disciplina" data-personal-id="{{ $persona['personal_id'] }}" data-personal-nombre="{{ $persona['nombre'] }}" title="Registrar amonestación o arresto"><i class="fa-solid fa-plus"></i></button>@endcan
                            @can('ver personal')<a href="{{ route('personal.show', $persona['personal_id']) }}" class="btn btn-sm sv-record-link" title="Ver expediente"><i class="fa-solid fa-address-card"></i></a>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-5 text-muted">No hay personal disponible para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="sv-report-panel mt-3">
    <div class="sv-report-panel-title"><div><span>Registros recientes</span><small>Últimas 50 amonestaciones o arrestos del periodo</small></div></div>
    <div class="table-responsive">
        <table class="table sv-report-table mb-0">
            <thead><tr><th>Fecha</th><th>Elemento</th><th>Tipo</th><th>Folio</th><th>Motivo</th><th class="text-center no-print">Documento</th></tr></thead>
            <tbody>
                @forelse($recientes as $incidencia)
                    <tr><td>{{ \Carbon\Carbon::parse($incidencia->fecha_inicio)->format('d/m/Y') }}@if($incidencia->fecha_fin)<small class="d-block text-muted">al {{ \Carbon\Carbon::parse($incidencia->fecha_fin)->format('d/m/Y') }}</small>@endif</td><td><strong>{{ $incidencia->nombre_personal }}</strong></td><td><span class="badge {{ strtoupper($incidencia->tipo_clave) === 'ARRESTO' ? 'badge-danger' : 'badge-warning' }}">{{ $incidencia->tipo_nombre }}</span></td><td>{{ $incidencia->folio ?: '—' }}</td><td>{{ $incidencia->motivo ?: '—' }}@if($incidencia->observaciones)<small class="d-block text-muted">{{ $incidencia->observaciones }}</small>@endif</td><td class="text-center no-print">@if($incidencia->documento_id)<a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('personal.documentos.signed', now()->addMinutes(30), ['documento' => $incidencia->documento_id, 'archivo' => 'general']) }}" target="_blank" class="btn btn-sm btn-info"><i class="fa-solid fa-file"></i></a>@else<span class="text-muted">—</span>@endif</td></tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">No hay amonestaciones ni arrestos en este periodo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<p class="sv-report-note"><i class="fa-solid fa-circle-info"></i> El orden muestra recurrencia documental: total de registros, después arrestos y finalmente amonestaciones. No constituye por sí mismo una valoración definitiva del desempeño ni sustituye la revisión del expediente y el debido proceso.</p>

@can('editar personal')
<div class="modal fade modal-opaque" id="modalDisciplina" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form id="formDisciplina" method="POST" action="#" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="return_to" value="estadisticas_carreteras.disciplina">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Registrar amonestación o arresto</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                <div class="modal-body">
                    <div class="form-group"><label>Elemento</label><select id="disciplinaPersonal" class="form-control" required><option value="">Selecciona un elemento</option>@foreach($personal as $persona)<option value="{{ $persona['personal_id'] }}">{{ $persona['nombre'] }} — {{ $persona['destacamento'] }}</option>@endforeach</select></div>
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Tipo</label><select name="tipo" class="form-control" required><option value="AMONESTACION">AMONESTACIÓN</option><option value="ARRESTO">ARRESTO</option></select></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Fecha</label><input type="date" name="fecha_inicio" value="{{ now()->toDateString() }}" class="form-control" required></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Fecha fin</label><input type="date" name="fecha_fin" class="form-control"><small class="text-muted">Útil para arrestos de varios días.</small></div></div>
                    </div>
                    <div class="form-group"><label>Folio u oficio</label><input type="text" name="folio" class="form-control" maxlength="60" placeholder="Número de oficio o referencia"></div>
                    <div class="form-group"><label>Motivo</label><textarea name="motivo" class="form-control" rows="3" maxlength="1000" required placeholder="Describe claramente la conducta o causa documentada"></textarea></div>
                    <div class="form-group"><label>Observaciones</label><textarea name="observaciones" class="form-control" rows="2" maxlength="1000"></textarea></div>
                    <div class="form-group mb-0"><label>Documento</label><input type="file" name="archivo_incidencia" class="form-control-file" accept=".pdf,image/jpeg,image/png,image/webp"><small class="text-muted">PDF o imagen, máximo 10 MB. Se guarda también en el expediente del elemento.</small></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger"><i class="fa-solid fa-floppy-disk"></i> Guardar registro</button></div>
            </div>
        </form>
    </div>
</div>
@endcan
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/sv-carreteras-reportes.css') }}">
<style>
    .sv-report-kpi.red span { background: rgba(220, 38, 38, .14); color: #dc2626; }
    .discipline { border-left: 4px solid #dc2626; }

    #modalDisciplina .modal-content {
        color: #e5e7eb !important;
        background: #1f2937 !important;
        border: 1px solid rgba(255, 255, 255, .14) !important;
        box-shadow: 0 24px 80px rgba(0, 0, 0, .8) !important;
        opacity: 1 !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        overflow: hidden;
    }

    #modalDisciplina .modal-header,
    #modalDisciplina .modal-footer {
        background: #111827 !important;
        border-color: rgba(255, 255, 255, .12) !important;
    }

    #modalDisciplina .modal-body { background: #1f2937 !important; }
    #modalDisciplina .modal-title,
    #modalDisciplina label { color: #f8fafc !important; }
    #modalDisciplina .close { color: #f8fafc !important; opacity: .9; text-shadow: none; }
    #modalDisciplina .text-muted { color: #aebed1 !important; }

    #modalDisciplina .form-control {
        color: #f8fafc !important;
        background: #0b1220 !important;
        border: 1px solid rgba(255, 255, 255, .16) !important;
    }

    #modalDisciplina .form-control:focus {
        color: #fff !important;
        background: #070d18 !important;
        border-color: rgba(239, 68, 68, .7) !important;
        box-shadow: 0 0 0 .2rem rgba(239, 68, 68, .2) !important;
    }

    #modalDisciplina select option { color: #111827; background: #fff; }
    .modal-backdrop.show { opacity: .86 !important; }

    @media print { .no-print, .modal { display: none !important; } }
</style>
@stop

@section('js')
@can('editar personal')
<script>
(() => {
    const form = document.getElementById('formDisciplina');
    const select = document.getElementById('disciplinaPersonal');
    const actionTemplate = @json(route('personal.incidencias.store', ['personal' => '__PERSONAL__']));

    function actualizarAccion() {
        if (!select.value) {
            form.action = '#';
            return;
        }
        form.action = actionTemplate.replace('__PERSONAL__', encodeURIComponent(select.value));
    }

    select.addEventListener('change', actualizarAccion);
    form.addEventListener('submit', event => {
        actualizarAccion();
        if (!select.value) event.preventDefault();
    });

    document.querySelectorAll('.js-registrar-disciplina').forEach(button => {
        button.addEventListener('click', () => {
            select.value = button.dataset.personalId || '';
            actualizarAccion();
            $('#modalDisciplina').modal('show');
        });
    });
})();
</script>
@endcan
@stop
