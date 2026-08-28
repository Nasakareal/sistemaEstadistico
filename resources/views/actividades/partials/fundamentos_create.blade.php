@php
    $fundamentosSeleccionados = array_map('intval', old('fundamento_ids', []));
@endphp
<div class="card card-outline card-success mb-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
        <div>
            <h3 class="card-title mb-0"><i class="fa-solid fa-scale-balanced"></i> Fundamentos</h3>
            <div class="help-muted mt-1">Catálogo general; no está limitado a causales de corralón.</div>
        </div>
        <div class="d-flex align-items-center" style="gap:8px;">
            <span class="badge badge-light vehiculo-total-badge" id="fundamentosActividadTotal">Total: {{ count($fundamentosSeleccionados) }}</span>
            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalFundamentosActividad">
                <i class="fa-solid fa-magnifying-glass"></i> Buscar fundamentos
            </button>
        </div>
    </div>
    <div class="card-body">
        @error('fundamento_ids')<div class="alert alert-danger">{{ $message }}</div>@enderror
        @error('fundamento_ids.*')<div class="alert alert-danger">{{ $message }}</div>@enderror
        <div id="fundamentosActividadEmpty" class="alert alert-info mb-0" style="{{ count($fundamentosSeleccionados) ? 'display:none;' : '' }}">No hay fundamentos seleccionados.</div>
        <div id="fundamentosActividadList" class="fundamentos-seleccionados"></div>
        <div id="fundamentosActividadInputs"></div>
    </div>
</div>
