@php
    $selectedDelegacion = old('delegacion_id', $redApoyo->delegacion_id);
    $selectedDestacamento = old('destacamento_id', $redApoyo->destacamento_id);
    $selectedRegion = old('region', $redApoyo->region);
    $selectedTipo = old('tipo_apoyo', $redApoyo->tipo_apoyo ?: 'Seguridad publica');
    $selectedNivel = old('nivel_gobierno', $redApoyo->nivel_gobierno ?: 'Federal');
@endphp

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="form-group">
            <label for="nivel_gobierno">Nivel de gobierno</label>
            <select name="nivel_gobierno" id="nivel_gobierno" class="form-control @error('nivel_gobierno') is-invalid @enderror" required>
                @foreach($nivelesGobierno as $value => $label)
                    <option value="{{ $value }}" {{ $selectedNivel === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('nivel_gobierno') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-lg-5">
        <div class="form-group">
            <label for="region">Región operativa</label>
            <select name="region" id="region" class="form-control @error('region') is-invalid @enderror">
                <option value="">Sin región específica</option>
                @foreach($delegacionesAgrupadas as $region)
                    <option value="{{ $region->nombre }}" {{ (string)$selectedRegion === (string)$region->nombre ? 'selected' : '' }}>
                        {{ $region->nombre }}
                    </option>
                @endforeach
            </select>
            @error('region') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="form-group">
            <label for="tipo_apoyo">Tipo de apoyo</label>
            <select name="tipo_apoyo" id="tipo_apoyo" class="form-control @error('tipo_apoyo') is-invalid @enderror" required>
                @foreach($tiposApoyo as $value => $label)
                    <option value="{{ $value }}" {{ $selectedTipo === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('tipo_apoyo') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>
</div>

<div class="row red-apoyo-state-only">
    <div class="col-lg-6">
        <div class="form-group">
            <label for="delegacion_id">Delegación estatal / región hija</label>
            <select name="delegacion_id" id="delegacion_id" class="form-control @error('delegacion_id') is-invalid @enderror">
                <option value="">No aplica / cobertura general</option>
                @foreach($delegacionesAgrupadas as $region)
                    <optgroup label="{{ $region->nombre }}">
                        <option value="{{ $region->id }}" data-region="{{ $region->nombre }}" {{ (string)$selectedDelegacion === (string)$region->id ? 'selected' : '' }}>
                            Toda la región estatal - {{ $region->nombre }}
                        </option>
                        @foreach($region->hijas as $hija)
                            <option value="{{ $hija->id }}" data-region="{{ $region->nombre }}" {{ (string)$selectedDelegacion === (string)$hija->id ? 'selected' : '' }}>
                                {{ $hija->nombre }}{{ $hija->municipio ? ' - ' . $hija->municipio : '' }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <small class="form-text text-muted">Solo para contactos estatales; Federal y Municipal se guardan sin delegación.</small>
            @error('delegacion_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-lg-6">
        <div class="form-group">
            <label for="destacamento_id">Destacamento estatal relacionado</label>
            <select name="destacamento_id" id="destacamento_id" class="form-control @error('destacamento_id') is-invalid @enderror">
                <option value="">No aplica</option>
                @foreach($destacamentos as $destacamento)
                    <option value="{{ $destacamento->id }}" {{ (string)$selectedDestacamento === (string)$destacamento->id ? 'selected' : '' }}>
                        {{ $destacamento->nombre }}{{ $destacamento->municipio ? ' - ' . $destacamento->municipio : '' }}
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted">Para encargados de destacamento del nivel estatal.</small>
            @error('destacamento_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="form-group">
            <label for="institucion">Institución</label>
            <input type="text"
                   name="institucion"
                   id="institucion"
                   class="form-control @error('institucion') is-invalid @enderror"
                   value="{{ old('institucion', $redApoyo->institucion) }}"
                   placeholder="Ej. Guardia Nacional, Fiscalía, Policía Municipal"
                   required>
            @error('institucion') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-lg-6">
        <div class="form-group">
            <label for="cargo">Cargo / función</label>
            <input type="text"
                   name="cargo"
                   id="cargo"
                   class="form-control @error('cargo') is-invalid @enderror"
                   value="{{ old('cargo', $redApoyo->cargo) }}"
                   placeholder="Ej. Encargado Guardia Nacional Región Morelia">
            @error('cargo') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="form-group">
            <label for="contacto">Nombre del encargado</label>
            <input type="text"
                   name="contacto"
                   id="contacto"
                   class="form-control @error('contacto') is-invalid @enderror"
                   value="{{ old('contacto', $redApoyo->contacto) }}">
            @error('contacto') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="form-group">
            <label for="telefono">Teléfono principal</label>
            <input type="text"
                   name="telefono"
                   id="telefono"
                   class="form-control @error('telefono') is-invalid @enderror"
                   value="{{ old('telefono', $redApoyo->telefono) }}"
                   maxlength="30">
            @error('telefono') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="form-group">
            <label for="telefono_secundario">Teléfono secundario</label>
            <input type="text"
                   name="telefono_secundario"
                   id="telefono_secundario"
                   class="form-control @error('telefono_secundario') is-invalid @enderror"
                   value="{{ old('telefono_secundario', $redApoyo->telefono_secundario) }}"
                   maxlength="30">
            @error('telefono_secundario') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="form-group">
            <label for="municipio">Municipio base</label>
            <input type="text"
                   name="municipio"
                   id="municipio"
                   class="form-control @error('municipio') is-invalid @enderror"
                   value="{{ old('municipio', $redApoyo->municipio) }}">
            @error('municipio') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="form-group">
            <label for="orden">Orden</label>
            <input type="number"
                   name="orden"
                   id="orden"
                   class="form-control @error('orden') is-invalid @enderror"
                   value="{{ old('orden', $redApoyo->orden ?? 0) }}"
                   min="0"
                   max="999">
            @error('orden') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="form-group">
            <label>Estado</label>
            <div class="custom-control custom-switch mt-2">
                <input type="hidden" name="activo" value="0">
                <input type="checkbox"
                       name="activo"
                       value="1"
                       id="activo"
                       class="custom-control-input"
                       {{ old('activo', $redApoyo->activo ?? true) ? 'checked' : '' }}>
                <label class="custom-control-label" for="activo">Activo</label>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="form-group">
            <label for="direccion">Dirección / base</label>
            <input type="text"
                   name="direccion"
                   id="direccion"
                   class="form-control @error('direccion') is-invalid @enderror"
                   value="{{ old('direccion', $redApoyo->direccion) }}">
            @error('direccion') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-lg-6">
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea name="observaciones"
                      id="observaciones"
                      rows="3"
                      class="form-control @error('observaciones') is-invalid @enderror">{{ old('observaciones', $redApoyo->observaciones) }}</textarea>
            @error('observaciones') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>
</div>

<hr>

<div class="d-flex flex-wrap justify-content-between align-items-center">
    <a href="{{ $redApoyo->exists ? route('directorio_red_apoyo.show', $redApoyo) : route('directorio_red_apoyo.index') }}" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Volver
    </a>

    <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-check"></i> Guardar contacto
    </button>
</div>

<script>
    (function () {
        const nivel = document.getElementById('nivel_gobierno');
        const stateBlocks = document.querySelectorAll('.red-apoyo-state-only');
        const delegacion = document.getElementById('delegacion_id');
        const destacamento = document.getElementById('destacamento_id');
        const region = document.getElementById('region');

        function syncNivelScope() {
            const isEstatal = nivel && nivel.value === 'Estatal';

            stateBlocks.forEach(function (block) {
                block.classList.toggle('is-hidden', !isEstatal);
            });

            [delegacion, destacamento].forEach(function (field) {
                if (!field) {
                    return;
                }

                field.disabled = !isEstatal;

                if (!isEstatal) {
                    field.value = '';
                }
            });
        }

        if (nivel) {
            nivel.addEventListener('change', syncNivelScope);
            syncNivelScope();
        }

        if (delegacion && region) {
            delegacion.addEventListener('change', function () {
                const option = delegacion.options[delegacion.selectedIndex];
                const selectedRegion = option ? option.getAttribute('data-region') : '';

                if (selectedRegion) {
                    region.value = selectedRegion;
                }
            });
        }
    })();
</script>
