@php
    $dispositivoForm = $dispositivo ?? null;
    $vehiculosCollection = collect();

    if ($dispositivoForm && $dispositivoForm->relationLoaded('vehiculos')) {
        $vehiculosCollection = $dispositivoForm->vehiculos->values();
    }

    $vehiculosJson = $vehiculosCollection->map(function ($vehiculo) {
        $descripcion = trim(collect([
            $vehiculo->marca,
            $vehiculo->linea,
            $vehiculo->tipo,
            $vehiculo->modelo,
            $vehiculo->color,
        ])->filter()->implode(' '));

        return [
            'id' => $vehiculo->id,
            'marca' => $vehiculo->marca,
            'modelo' => $vehiculo->modelo,
            'tipo' => $vehiculo->tipo,
            'linea' => $vehiculo->linea,
            'color' => $vehiculo->color,
            'placas' => $vehiculo->placas ?: '',
            'estado_placas' => $vehiculo->estado_placas,
            'serie' => $vehiculo->serie ?: '',
            'capacidad_personas' => $vehiculo->capacidad_personas,
            'tipo_servicio' => $vehiculo->tipo_servicio,
            'tarjeta_circulacion_nombre' => $vehiculo->tarjeta_circulacion_nombre,
            'grua' => $vehiculo->grua,
            'corralon' => $vehiculo->corralon,
            'aseguradora' => $vehiculo->aseguradora,
            'antecedente_vehiculo' => (bool) $vehiculo->antecedente_vehiculo,
            'label' => $descripcion ?: 'VEHICULO RELACIONADO',
        ];
    })->values();

    $vehiculosIniciales = old('vehiculos');
    if (is_null($vehiculosIniciales) && $dispositivoForm && $dispositivoForm->relationLoaded('vehiculos')) {
        $vehiculosIniciales = $dispositivoForm->vehiculos->pluck('id')->all();
    }
    $vehiculosIniciales = array_values(array_filter((array) $vehiculosIniciales));

    $vehiculosMetaInicial = old('vehiculos_meta');
    if (is_null($vehiculosMetaInicial) && $dispositivoForm && $dispositivoForm->relationLoaded('vehiculos')) {
        $vehiculosMetaInicial = $dispositivoForm->vehiculos->mapWithKeys(function ($vehiculo) {
            return [
                (string) $vehiculo->id => [
                    'rol' => $vehiculo->pivot->rol ?: 'IMPACTADO',
                    'observaciones' => $vehiculo->pivot->observaciones,
                ],
            ];
        })->all();
    }
    $vehiculosMetaInicial = (array) ($vehiculosMetaInicial ?? []);

    $personasIniciales = old('personas');
    if (is_null($personasIniciales) && $dispositivoForm && $dispositivoForm->relationLoaded('personas')) {
        $personasIniciales = $dispositivoForm->personas->map(function ($persona) {
            return [
                'nombre' => $persona->nombre,
                'tipo_participacion' => $persona->tipo_participacion,
                'curp' => $persona->curp,
                'telefono' => $persona->telefono,
                'domicilio' => $persona->domicilio,
                'sexo' => $persona->sexo,
                'ocupacion' => $persona->ocupacion,
                'edad' => $persona->edad,
                'tipo_licencia' => $persona->tipo_licencia,
                'estado_licencia' => $persona->estado_licencia,
                'vigencia_licencia' => optional($persona->vigencia_licencia)->format('Y-m-d'),
                'numero_licencia' => $persona->numero_licencia,
                'permanente' => (bool) $persona->permanente,
                'cinturon' => (bool) $persona->cinturon,
                'antecedentes' => (bool) $persona->antecedentes,
                'certificado_lesiones' => (bool) $persona->certificado_lesiones,
                'certificado_alcoholemia' => (bool) $persona->certificado_alcoholemia,
                'aliento_etilico' => (bool) $persona->aliento_etilico,
                'observaciones' => $persona->observaciones,
            ];
        })->values()->all();
    }
    $personasIniciales = array_values(array_filter((array) ($personasIniciales ?? []), function ($persona) {
        return is_array($persona) && !empty($persona['nombre']);
    }));
@endphp

<hr>

<div id="seccion_relacionados" class="mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap: 10px;">
        <div>
            <h5 class="mb-1">Vehículos y personas relacionadas</h5>
            <small class="help-muted">Crea los vehículos y agrega las personas necesarias para el dispositivo.</small>
        </div>

        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#modalRelacionadosGuardianes">
            <i class="fa-solid fa-plus"></i> Agregar
        </button>
    </div>

    <div id="guardianesRelacionadosInputs"></div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <strong>Vehículos</strong>
                <span id="guardianesVehiculosTotal" class="badge badge-info vehiculo-total-badge">Total: 0</span>
            </div>

            <div id="guardianesVehiculosEmpty" class="alert alert-secondary mb-0">
                No hay vehículos relacionados.
            </div>

            <div id="guardianesVehiculosList" class="vehiculos-grid"></div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <strong>Personas</strong>
                <span id="guardianesPersonasTotal" class="badge badge-info vehiculo-total-badge">Total: 0</span>
            </div>

            <div id="guardianesPersonasEmpty" class="alert alert-secondary mb-0">
                No hay personas relacionadas.
            </div>

            <div id="guardianesPersonasList" class="vehiculos-grid"></div>
        </div>
    </div>
</div>

<script type="application/json" id="guardianes-vehiculos-disponibles">@json($vehiculosJson)</script>
<script type="application/json" id="guardianes-vehiculos-iniciales">@json($vehiculosIniciales)</script>
<script type="application/json" id="guardianes-vehiculos-meta-inicial">@json($vehiculosMetaInicial)</script>
<script type="application/json" id="guardianes-personas-iniciales">@json($personasIniciales)</script>

<div class="modal fade modal-actividad-vehiculo" id="modalRelacionadosGuardianes" tabindex="-1" role="dialog" aria-labelledby="modalRelacionadosGuardianesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalRelacionadosGuardianesLabel">
                        <i class="fa-solid fa-link"></i> Agregar relacionado
                    </h5>
                    <div class="modal-subtitle">Captura un vehículo nuevo o una persona relacionada.</div>
                </div>

                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="guardianesRelacionadosTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-vehiculo-relacionado" data-toggle="tab" href="#panel-vehiculo-relacionado" role="tab">
                            <i class="fa-solid fa-car-side"></i> Vehículo
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-persona-relacionada" data-toggle="tab" href="#panel-persona-relacionada" role="tab">
                            <i class="fa-solid fa-user"></i> Persona
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="panel-vehiculo-relacionado" role="tabpanel" aria-labelledby="tab-vehiculo-relacionado">
                        <div class="form-group">
                            <label for="guardianesVehiculoQrRaw">Tarjeta de circulación</label>
                            <textarea id="guardianesVehiculoQrRaw" class="form-control" rows="3" placeholder="Escanea o pega el texto/QR de la tarjeta para autocompletar"></textarea>
                            <button type="button" class="btn btn-outline-info btn-sm mt-2" id="btnAutocompletarVehiculoQr">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Autocompletar
                            </button>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="guardianesVehiculoRol">Rol</label>
                                    <select id="guardianesVehiculoRol" class="form-control">
                                        <option value="IMPACTADO">Impactado</option>
                                        <option value="INSPECCIONADO">Inspeccionado</option>
                                        <option value="APOYO">Apoyo</option>
                                        <option value="RECUPERADO">Recuperado</option>
                                        <option value="OTRO">Otro</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="guardianesVehiculoObservaciones">Observaciones</label>
                                    <input type="text" id="guardianesVehiculoObservaciones" class="form-control js-uppercase-relacionados" maxlength="255" placeholder="Datos adicionales del dispositivo">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4"><div class="form-group"><label>Marca <span class="text-danger">*</span></label><input type="text" id="guardianesVehiculoMarca" class="form-control js-uppercase-relacionados" maxlength="50"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Modelo</label><input type="text" id="guardianesVehiculoModelo" class="form-control js-uppercase-relacionados" maxlength="10"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Tipo general <span class="text-danger">*</span></label><input type="text" id="guardianesVehiculoTipoGeneral" class="form-control js-uppercase-relacionados" maxlength="50" placeholder="AUTOMOVIL, CAMIONETA..."></div></div>
                        </div>

                        <div class="row">
                            <div class="col-md-4"><div class="form-group"><label>Carrocería <span class="text-danger">*</span></label><input type="text" id="guardianesVehiculoTipo" class="form-control js-uppercase-relacionados" maxlength="50"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Línea <span class="text-danger">*</span></label><input type="text" id="guardianesVehiculoLinea" class="form-control js-uppercase-relacionados" maxlength="50"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Color <span class="text-danger">*</span></label><input type="text" id="guardianesVehiculoColor" class="form-control js-uppercase-relacionados" maxlength="30"></div></div>
                        </div>

                        <div class="row">
                            <div class="col-md-4"><div class="form-group"><label>Placas</label><input type="text" id="guardianesVehiculoPlacas" class="form-control js-uppercase-relacionados" maxlength="15"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Estado placas</label><input type="text" id="guardianesVehiculoEstadoPlacas" class="form-control js-uppercase-relacionados" maxlength="30"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>NIV/serie <span class="text-danger">*</span></label><input type="text" id="guardianesVehiculoSerie" class="form-control js-uppercase-relacionados" maxlength="17"></div></div>
                        </div>

                        <div class="row">
                            <div class="col-md-4"><div class="form-group"><label>Capacidad <span class="text-danger">*</span></label><input type="number" id="guardianesVehiculoCapacidad" class="form-control" min="0" value="0"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Tipo servicio <span class="text-danger">*</span></label><input type="text" id="guardianesVehiculoTipoServicio" class="form-control js-uppercase-relacionados" maxlength="50" value="PARTICULAR"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Propietario tarjeta</label><input type="text" id="guardianesVehiculoTarjetaNombre" class="form-control js-uppercase-relacionados" maxlength="60"></div></div>
                        </div>

                        <div class="row">
                            <div class="col-md-4"><div class="form-group"><label>Grúa</label><input type="text" id="guardianesVehiculoGrua" class="form-control js-uppercase-relacionados" maxlength="255"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Corralón</label><input type="text" id="guardianesVehiculoCorralon" class="form-control js-uppercase-relacionados" maxlength="255"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Aseguradora</label><input type="text" id="guardianesVehiculoAseguradora" class="form-control js-uppercase-relacionados" maxlength="100"></div></div>
                        </div>

                        <div class="row">
                            <div class="col-md-4"><div class="form-group"><label>Monto daños <span class="text-danger">*</span></label><input type="number" id="guardianesVehiculoMontoDanos" class="form-control" min="0" step="0.01" value="0"></div></div>
                            <div class="col-md-8"><div class="form-group"><label>Partes dañadas <span class="text-danger">*</span></label><input type="text" id="guardianesVehiculoPartesDanadas" class="form-control js-uppercase-relacionados" placeholder="SIN DAÑOS / descripción"></div></div>
                        </div>

                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="guardianesVehiculoAntecedente">
                            <label class="custom-control-label" for="guardianesVehiculoAntecedente">Antecedente vehicular</label>
                        </div>

                        <button type="button" class="btn btn-primary" id="btnAgregarVehiculoRelacionado">
                            <i class="fa-solid fa-plus"></i> Agregar vehículo
                        </button>
                    </div>

                    <div class="tab-pane fade" id="panel-persona-relacionada" role="tabpanel" aria-labelledby="tab-persona-relacionada">
                        <div class="row">
                            <div class="col-md-8"><div class="form-group"><label for="guardianesPersonaNombre">Nombre <span class="text-danger">*</span></label><input type="text" id="guardianesPersonaNombre" class="form-control js-uppercase-relacionados" maxlength="255" placeholder="Nombre completo"></div></div>
                            <div class="col-md-4"><div class="form-group"><label for="guardianesPersonaTipo">Participación</label><select id="guardianesPersonaTipo" class="form-control"><option value="IMPACTADA">Impactada</option><option value="INSPECCIONADA">Inspeccionada</option><option value="CONDUCTOR">Conductor</option><option value="ACOMPANANTE">Acompañante</option><option value="PEATON">Peatón</option><option value="TESTIGO">Testigo</option><option value="OTRO">Otro</option></select></div></div>
                        </div>

                        <div class="row">
                            <div class="col-md-4"><div class="form-group"><label>CURP</label><input type="text" id="guardianesPersonaCurp" class="form-control js-uppercase-relacionados" maxlength="30"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Teléfono</label><input type="tel" id="guardianesPersonaTelefono" class="form-control" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" placeholder="10 dígitos"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Sexo</label><select id="guardianesPersonaSexo" class="form-control"><option value="">Sin especificar</option><option value="MASCULINO">Masculino</option><option value="FEMENINO">Femenino</option><option value="OTRO">Otro</option></select></div></div>
                        </div>

                        <div class="row">
                            <div class="col-md-8"><div class="form-group"><label>Domicilio</label><input type="text" id="guardianesPersonaDomicilio" class="form-control js-uppercase-relacionados" maxlength="255"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Edad</label><input type="number" id="guardianesPersonaEdad" class="form-control" min="0" max="120"></div></div>
                        </div>

                        <div class="row">
                            <div class="col-md-6"><div class="form-group"><label>Ocupación</label><input type="text" id="guardianesPersonaOcupacion" class="form-control js-uppercase-relacionados" maxlength="255"></div></div>
                            <div class="col-md-6"><div class="form-group"><label>Tipo licencia</label><input type="text" id="guardianesPersonaTipoLicencia" class="form-control js-uppercase-relacionados" maxlength="50"></div></div>
                        </div>

                        <div class="row">
                            <div class="col-md-4"><div class="form-group"><label>Estado licencia</label><input type="text" id="guardianesPersonaEstadoLicencia" class="form-control js-uppercase-relacionados" maxlength="100"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Número licencia</label><input type="text" id="guardianesPersonaNumeroLicencia" class="form-control js-uppercase-relacionados" maxlength="50"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Vigencia licencia</label><input type="date" id="guardianesPersonaVigenciaLicencia" class="form-control"></div></div>
                        </div>

                        <div class="row">
                            <div class="col-md-4"><div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="guardianesPersonaPermanente"><label class="custom-control-label" for="guardianesPersonaPermanente">Permanente</label></div></div>
                            <div class="col-md-4"><div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="guardianesPersonaCinturon"><label class="custom-control-label" for="guardianesPersonaCinturon">Cinturón</label></div></div>
                            <div class="col-md-4"><div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="guardianesPersonaAntecedentes"><label class="custom-control-label" for="guardianesPersonaAntecedentes">Antecedentes</label></div></div>
                        </div>

                        <div class="row">
                            <div class="col-md-4"><div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="guardianesPersonaCertificadoLesiones"><label class="custom-control-label" for="guardianesPersonaCertificadoLesiones">Cert. lesiones</label></div></div>
                            <div class="col-md-4"><div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="guardianesPersonaCertificadoAlcoholemia"><label class="custom-control-label" for="guardianesPersonaCertificadoAlcoholemia">Cert. alcoholemia</label></div></div>
                            <div class="col-md-4"><div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="guardianesPersonaAlientoEtilico"><label class="custom-control-label" for="guardianesPersonaAlientoEtilico">Aliento etílico</label></div></div>
                        </div>

                        <div class="form-group"><label for="guardianesPersonaObservaciones">Observaciones</label><textarea id="guardianesPersonaObservaciones" class="form-control js-uppercase-relacionados" rows="3" placeholder="Lesiones, traslado, datos adicionales"></textarea></div>

                        <button type="button" class="btn btn-primary" id="btnAgregarPersonaRelacionada">
                            <i class="fa-solid fa-plus"></i> Agregar persona
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>