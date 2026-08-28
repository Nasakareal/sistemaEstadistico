<div class="modal fade modal-actividad-vehiculo" id="modalAgregarPersonaActividad" tabindex="-1" role="dialog" aria-labelledby="modalAgregarPersonaActividadLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <form class="w-100" id="formPersonaActividadTemporal">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="modalAgregarPersonaActividadLabel"><i class="fa-solid fa-user-plus"></i> Agregar persona</h5>
                        <div class="modal-subtitle">El vehículo es obligatorio para conductores y pasajeros.</div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3"><div class="form-group">
                            <label for="persona_tipo_participacion">Participación <span class="text-danger">*</span></label>
                            <select name="tipo_participacion" id="persona_tipo_participacion" class="form-control" required>
                                <option value="">Seleccione...</option>
                                <option value="CONDUCTOR">Conductor</option>
                                <option value="PASAJERO">Pasajero / acompañante</option>
                                <option value="PEATON">Peatón</option>
                                <option value="OTRO">Otra</option>
                            </select>
                        </div></div>
                        <div class="col-md-5"><div class="form-group">
                            <label for="persona_nombre">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="persona_nombre" class="form-control js-uppercase" maxlength="255" required>
                        </div></div>
                        <div class="col-md-4"><div class="form-group">
                            <label for="persona_vehiculo_indice">Vehículo relacionado</label>
                            <select name="vehiculo_indice" id="persona_vehiculo_indice" class="form-control"><option value="">Sin vehículo</option></select>
                            <small class="help-muted" id="persona_vehiculo_ayuda">Seleccione primero el tipo de participación.</small>
                        </div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-2"><div class="form-group"><label>Edad</label><input type="number" name="edad" min="0" max="120" class="form-control"></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Sexo</label><select name="sexo" class="form-control"><option value="">No especificado</option><option value="MASCULINO">Masculino</option><option value="FEMENINO">Femenino</option><option value="OTRO">Otro</option></select></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Teléfono</label><input type="text" name="telefono" maxlength="30" class="form-control"></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Ocupación</label><input type="text" name="ocupacion" maxlength="255" class="form-control js-uppercase"></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-8"><div class="form-group"><label>Domicilio</label><input type="text" name="domicilio" maxlength="255" class="form-control js-uppercase"></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Nacionalidad</label><input type="text" name="nacionalidad" maxlength="80" class="form-control js-uppercase"></div></div>
                    </div>
                    <div id="personaCamposLicencia" style="display:none;">
                        <div class="vehiculo-section-title"><span>Licencia del conductor</span></div>
                        <div class="row">
                            <div class="col-md-3"><div class="form-group"><label>Tipo</label><input type="text" name="tipo_licencia" maxlength="80" class="form-control js-uppercase"></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Número</label><input type="text" name="numero_licencia" maxlength="80" class="form-control js-uppercase"></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Estado</label><input type="text" name="estado_licencia" maxlength="120" class="form-control js-uppercase"></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Vigencia</label><input type="date" name="vigencia_licencia" class="form-control"></div></div>
                        </div>
                        <div class="d-flex flex-wrap" style="gap:24px;">
                            <div class="custom-control custom-switch"><input type="checkbox" name="permanente" value="1" class="custom-control-input" id="persona_permanente"><label class="custom-control-label" for="persona_permanente">Licencia permanente</label></div>
                            <div class="custom-control custom-switch"><input type="checkbox" name="antecedentes" value="1" class="custom-control-input" id="persona_antecedentes"><label class="custom-control-label" for="persona_antecedentes">Antecedentes</label></div>
                        </div>
                    </div>
                    <div class="form-group mt-3"><label>Observaciones</label><textarea name="observaciones" maxlength="2000" rows="2" class="form-control js-uppercase"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="fa-solid fa-plus"></i> Agregar a la actividad</button>
                </div>
            </div>
        </form>
    </div>
</div>
