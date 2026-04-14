<div class="card card-outline card-info mb-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
        <div>
            <h3 class="card-title mb-0">
                <i class="fa-solid fa-car-side"></i> Vehículos relacionados
            </h3>
            <div class="help-muted mt-1">Agregue los vehículos antes de registrar la actividad.</div>
        </div>

        <div class="d-flex align-items-center" style="gap:8px; flex-wrap:wrap;">
            <span class="badge badge-light vehiculo-total-badge" id="vehiculosActividadTotal">
                Total: {{ count(old('vehiculos', [])) }}
            </span>

            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAgregarVehiculoActividad">
                <i class="fa-solid fa-plus"></i> Agregar vehículo
            </button>
        </div>
    </div>

    <div class="card-body">
        <div id="vehiculosActividadEmpty" class="alert alert-info mb-0" style="{{ count(old('vehiculos', [])) ? 'display:none;' : '' }}">
            No hay vehículos agregados para esta actividad.
        </div>

        <div id="vehiculosActividadList" class="vehiculos-grid"></div>
        <div id="vehiculosActividadInputs"></div>
    </div>
</div>
