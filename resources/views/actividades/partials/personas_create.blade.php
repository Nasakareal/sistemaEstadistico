<div class="card card-outline card-warning mb-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
        <div>
            <h3 class="card-title mb-0"><i class="fa-solid fa-users"></i> Conductores y personas</h3>
            <div class="help-muted mt-1">Un conductor por vehículo. Pasajeros, peatones y otras personas se registran por separado.</div>
        </div>
        <div class="d-flex align-items-center" style="gap:8px;">
            <span class="badge badge-light vehiculo-total-badge" id="personasActividadTotal">Total: {{ count(old('personas', [])) }}</span>
            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalAgregarPersonaActividad">
                <i class="fa-solid fa-user-plus"></i> Agregar persona
            </button>
        </div>
    </div>
    <div class="card-body">
        @error('personas')<div class="alert alert-danger">{{ $message }}</div>@enderror
        <div id="personasActividadEmpty" class="alert alert-info mb-0" style="{{ count(old('personas', [])) ? 'display:none;' : '' }}">
            No hay conductores ni personas adicionales registradas.
        </div>
        <div id="personasActividadList" class="vehiculos-grid"></div>
        <div id="personasActividadInputs"></div>
    </div>
</div>
