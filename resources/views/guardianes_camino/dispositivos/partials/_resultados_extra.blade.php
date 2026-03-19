<div id="seccion_generales_extra" class="d-none">
    <hr>

    <div class="row">
        <div class="col-md-12 mb-2">
            <h5>Resultados complementarios</h5>
        </div>

        @php
            $extras = [
                'puestas_disposicion' => 'Puestas a disposición',
                'vehiculos_recuperados' => 'Vehículos recuperados',
                'armas_aseguradas' => 'Armas aseguradas',
                'mercancia_recuperada' => 'Mercancía recuperada',
                'decomiso_drogas' => 'Decomiso drogas',
                'antecedentes_personas' => 'Antecedentes personas',
                'antecedentes_vehiculos' => 'Antecedentes vehículos',
                'antecedentes_motos' => 'Antecedentes motos',
                'antecedentes_camiones' => 'Antecedentes camiones',
            ];
        @endphp

        @foreach($extras as $name => $label)
            <div class="col-md-3 campo-dinamico" data-campo="{{ $name }}">
                <div class="form-group">
                    <label for="{{ $name }}">{{ $label }}</label>
                    <input
                        type="number"
                        name="{{ $name }}"
                        id="{{ $name }}"
                        min="0"
                        class="form-control @error($name) is-invalid @enderror"
                        value="{{ old($name, 0) }}"
                        disabled
                    >
                    @error($name)
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
            </div>
        @endforeach
    </div>
</div>
