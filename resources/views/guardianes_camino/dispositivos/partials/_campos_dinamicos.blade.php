<div id="seccion_datos_dinamicos" class="d-none">
    <hr>

    <div class="row">
        <div class="col-md-12 mb-2">
            <h5>Datos del dispositivo</h5>
        </div>

        @php
            $dispositivoForm = $dispositivo ?? null;
            $campos = [
                'vehiculos_inspeccionados' => ['Vehículos inspeccionados', 'number', '0'],
                'personas_inspeccionadas' => ['Personas inspeccionadas', 'number', '0'],
                'vehiculos_impactados' => ['Vehículos impactados', 'number', '0'],
                'personas_impactadas' => ['Personas impactadas', 'number', '0'],
                'estado_fuerza_participante' => ['Estado de fuerza participante', 'number', '0'],
                'kilometros_recorridos' => ['Kilómetros recorridos', 'decimal', '0'],
                'crps_participantes' => ['CRPS participantes', 'text', ''],
                'acompanamientos' => ['Acompañamientos', 'number', '0'],
                'abanderamientos' => ['Abanderamientos', 'number', '0'],
                'auxilios_viales' => ['Auxilios viales', 'number', '0'],
                'prox_empresas' => ['Empresas', 'number', '0'],
                'prox_tiendas_conveniencia' => ['Tiendas de conveniencia', 'number', '0'],
                'prox_escuelas' => ['Escuelas', 'number', '0'],
                'prox_hospitales' => ['Hospitales', 'number', '0'],
                'folio_atendido' => ['N° folio atendido', 'text', ''],
                'motivo_folio' => ['Motivo del folio', 'text', ''],
            ];
        @endphp

        @foreach($campos as $name => [$label, $type, $default])
            <div class="col-md-{{ $name === 'crps_participantes' ? '6' : '3' }} campo-dinamico" data-campo="{{ $name }}">
                <div class="form-group">
                    <label for="{{ $name }}">{{ $label }}</label>

                    @if($type === 'text')
                        <input
                            type="text"
                            name="{{ $name }}"
                            id="{{ $name }}"
                            class="form-control @error($name) is-invalid @enderror"
                            value="{{ old($name, $dispositivoForm->{$name} ?? $default) }}"
                            placeholder="Ejemplo: 25-1234 y 22-5678"
                            disabled
                        >
                    @elseif($type === 'decimal')
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="{{ $name }}"
                            id="{{ $name }}"
                            class="form-control @error($name) is-invalid @enderror"
                            value="{{ old($name, $dispositivoForm->{$name} ?? $default) }}"
                            disabled
                        >
                    @else
                        <input
                            type="number"
                            min="0"
                            name="{{ $name }}"
                            id="{{ $name }}"
                            class="form-control @error($name) is-invalid @enderror"
                            value="{{ old($name, $dispositivoForm->{$name} ?? $default) }}"
                            disabled
                        >
                    @endif

                    @error($name)
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
            </div>
        @endforeach

        <div class="col-md-3 campo-dinamico" data-campo="tipo_acompanamiento">
            <div class="form-group">
                <label for="tipo_acompanamiento">Tipo de acompañamiento</label>
                <select
                    name="tipo_acompanamiento"
                    id="tipo_acompanamiento"
                    class="form-control @error('tipo_acompanamiento') is-invalid @enderror"
                    disabled
                >
                    <option value="">Seleccione una opción</option>
                    <option value="ESCOLTA" {{ old('tipo_acompanamiento', $dispositivoForm->tipo_acompanamiento ?? '') == 'ESCOLTA' ? 'selected' : '' }}>Escolta</option>
                    <option value="CARAVANA" {{ old('tipo_acompanamiento', $dispositivoForm->tipo_acompanamiento ?? '') == 'CARAVANA' ? 'selected' : '' }}>Caravana</option>
                    <option value="EMERGENCIA" {{ old('tipo_acompanamiento', $dispositivoForm->tipo_acompanamiento ?? '') == 'EMERGENCIA' ? 'selected' : '' }}>Emergencia</option>
                    <option value="OTRO" {{ old('tipo_acompanamiento', $dispositivoForm->tipo_acompanamiento ?? '') == 'OTRO' ? 'selected' : '' }}>Otro</option>
                </select>

                @error('tipo_acompanamiento')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-3 campo-dinamico" data-campo="tipo_abanderamiento">
            <div class="form-group">
                <label for="tipo_abanderamiento">Tipo de abanderamiento</label>
                <select
                    name="tipo_abanderamiento"
                    id="tipo_abanderamiento"
                    class="form-control @error('tipo_abanderamiento') is-invalid @enderror"
                    disabled
                >
                    <option value="">Seleccione una opción</option>
                    <option value="SINIESTROS" {{ old('tipo_abanderamiento', $dispositivoForm->tipo_abanderamiento ?? '') == 'SINIESTROS' ? 'selected' : '' }}>Siniestros</option>
                    <option value="EVENTOS" {{ old('tipo_abanderamiento', $dispositivoForm->tipo_abanderamiento ?? '') == 'EVENTOS' ? 'selected' : '' }}>Eventos</option>
                    <option value="OTRO" {{ old('tipo_abanderamiento', $dispositivoForm->tipo_abanderamiento ?? '') == 'OTRO' ? 'selected' : '' }}>Otro</option>
                </select>

                @error('tipo_abanderamiento')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-3 campo-dinamico" data-campo="tipo_auxilio_vial">
            <div class="form-group">
                <label for="tipo_auxilio_vial">Tipo de auxilio vial</label>
                <select
                    name="tipo_auxilio_vial"
                    id="tipo_auxilio_vial"
                    class="form-control @error('tipo_auxilio_vial') is-invalid @enderror"
                    disabled
                >
                    <option value="">Seleccione una opción</option>
                    <option value="FALLA MECANICA" {{ old('tipo_auxilio_vial', $dispositivoForm->tipo_auxilio_vial ?? '') == 'FALLA MECANICA' ? 'selected' : '' }}>Falla mecánica</option>
                    <option value="PEATON" {{ old('tipo_auxilio_vial', $dispositivoForm->tipo_auxilio_vial ?? '') == 'PEATON' ? 'selected' : '' }}>Peatón</option>
                    <option value="OTRO" {{ old('tipo_auxilio_vial', $dispositivoForm->tipo_auxilio_vial ?? '') == 'OTRO' ? 'selected' : '' }}>Otro</option>
                </select>

                @error('tipo_auxilio_vial')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>
    </div>
</div>
