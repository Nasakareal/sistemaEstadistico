<div class="modal fade modal-actividad-vehiculo" id="modalAgregarVehiculoActividad" tabindex="-1" role="dialog" aria-labelledby="modalAgregarVehiculoActividadLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <form class="w-100" id="formVehiculoActividadTemporal">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="modalAgregarVehiculoActividadLabel">
                            <i class="fa-solid fa-car-side"></i> Agregar vehículo
                        </h5>
                        <div class="modal-subtitle">Solo datos del vehículo.</div>
                    </div>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    @include('actividades.partials.vehiculo_modal_campos')
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i> Agregar a la actividad
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
