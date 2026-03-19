<div id="seccion_datos_dinamicos" class="d-none">
    <hr>

    <div class="row">
        <div class="col-md-12 mb-2">
            <h5>Datos del dispositivo</h5>
        </div>

        @php
            $campos = [
                'cantidad' => ['Cantidad', 'number', '0'],
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
                            value="{{ old($name, $default) }}"
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
                            value="{{ old($name, $default) }}"
                            disabled
                        >
                    @else
                        <input
                            type="number"
                            min="0"
                            name="{{ $name }}"
                            id="{{ $name }}"
                            class="form-control @error($name) is-invalid @enderror"
                            value="{{ old($name, $default) }}"
                            disabled
                        >
                    @endif

                    @error($name)
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
            </div>
        @endforeach
    </div>
</div>
