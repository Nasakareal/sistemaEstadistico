@php
    $selectedTipo = old('tipo', $oficio->tipo ?: 'oficio');
    $selectedSentido = old('sentido', $oficio->sentido ?: 'entrada');
    $selectedUnidad = old('unidad_id', $oficio->unidad_id ?: optional(auth()->user())->unidad_id);
    $selectedContesta = old('contesta_a_id', $oficio->contesta_a_id);
    $esSalida = $selectedSentido === 'salida';
    $prefijosUnidad = $prefijosUnidad ?? [];
    $anioOriginal = $oficio->exists ? optional($oficio->fecha_documento ?: $oficio->created_at)->format('Y') : null;
@endphp

<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="form-group">
            <label for="tipo">Tipo</label>
            <select name="tipo" id="tipo" class="form-control @error('tipo') is-invalid @enderror" required>
                @foreach($tipos as $value => $label)
                    <option value="{{ $value }}" {{ $selectedTipo === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('tipo') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="form-group">
            <label for="sentido">Movimiento</label>
            <select name="sentido" id="sentido" class="form-control @error('sentido') is-invalid @enderror" required>
                @foreach($sentidos as $value => $label)
                    <option value="{{ $value }}" {{ $selectedSentido === $value ? 'selected' : '' }}>
                        {{ $label }} {{ $value === 'entrada' ? '(llega)' : '(se va)' }}
                    </option>
                @endforeach
            </select>
            @error('sentido') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="form-group">
            <label for="fecha_documento">Fecha del documento</label>
            <input type="date"
                   name="fecha_documento"
                   id="fecha_documento"
                   class="form-control @error('fecha_documento') is-invalid @enderror"
                   value="{{ old('fecha_documento', optional($oficio->fecha_documento)->format('Y-m-d')) }}">
            @error('fecha_documento') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="form-group">
            <label for="unidad_id">Unidad</label>
            <select name="unidad_id"
                    id="unidad_id"
                    class="form-control @error('unidad_id') is-invalid @enderror"
                    {{ $puedeElegirUnidad ? '' : 'disabled' }}>
                @if($puedeElegirUnidad)
                    <option value="">Selecciona unidad</option>
                @endif
                @foreach($unidades as $unidad)
                    <option value="{{ $unidad->id }}" {{ (int) $selectedUnidad === (int) $unidad->id ? 'selected' : '' }}>
                        {{ $unidad->nombre }}
                    </option>
                @endforeach
            </select>
            @if(!$puedeElegirUnidad)
                <input type="hidden" name="unidad_id" value="{{ $selectedUnidad }}">
            @endif
            @error('unidad_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="form-group">
            <label for="numero_oficio">Número</label>
            <input type="text"
                   name="numero_oficio"
                   id="numero_oficio"
                   class="form-control oficio-numero @error('numero_oficio') is-invalid @enderror"
                   value="{{ old('numero_oficio', $oficio->numero_oficio) }}"
                   maxlength="500"
                   {{ $esSalida ? 'readonly' : 'required' }}
                   data-original-numero="{{ $oficio->numero_oficio }}"
                   data-original-salida="{{ $oficio->exists && $oficio->sentido === 'salida' ? '1' : '0' }}"
                   data-original-unidad="{{ $oficio->unidad_id }}"
                   data-original-anio="{{ $anioOriginal }}"
                   placeholder="{{ $esSalida ? 'Se asignará al guardar' : 'Ej. SSV/DAJ/AM/000000000000/2026' }}">
            <small id="numero_help" class="form-text text-muted"></small>
            @error('numero_oficio') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-lg-6">
        <div class="form-group">
            <label for="asunto">Asunto</label>
            <input type="text"
                   name="asunto"
                   id="asunto"
                   class="form-control @error('asunto') is-invalid @enderror"
                   value="{{ old('asunto', $oficio->asunto) }}"
                   maxlength="500">
            @error('asunto') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="form-group">
            <label for="remitente">Remitente</label>
            <input type="text"
                   name="remitente"
                   id="remitente"
                   class="form-control @error('remitente') is-invalid @enderror"
                   value="{{ old('remitente', $oficio->remitente) }}"
                   maxlength="255">
            @error('remitente') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-lg-6">
        <div class="form-group">
            <label for="destinatario">Destinatario</label>
            <input type="text"
                   name="destinatario"
                   id="destinatario"
                   class="form-control @error('destinatario') is-invalid @enderror"
                   value="{{ old('destinatario', $oficio->destinatario) }}"
                   maxlength="255">
            @error('destinatario') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="form-group">
            <label for="contesta_a_id">Contesta a</label>
            <select name="contesta_a_id" id="contesta_a_id" class="form-control @error('contesta_a_id') is-invalid @enderror">
                <option value="">No es contestación</option>
                @foreach($oficiosParaContestar as $opcion)
                    <option value="{{ $opcion->id }}" {{ (int) $selectedContesta === (int) $opcion->id ? 'selected' : '' }}>
                        {{ $opcion->tipo_label }} · {{ $opcion->numero_oficio }}
                        @if($puedeElegirUnidad && $opcion->unidad)
                            · {{ $opcion->unidad->nombre }}
                        @endif
                    </option>
                @endforeach
            </select>
            @error('contesta_a_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-lg-6">
        <div class="form-group">
            <label for="pdf_path">Archivo PDF</label>
            <input type="file"
                   name="pdf_path"
                   id="pdf_path"
                   accept="application/pdf"
                   class="form-control @error('pdf_path') is-invalid @enderror">
            @error('pdf_path') <span class="invalid-feedback">{{ $message }}</span> @enderror

            @if($oficio->exists && $oficio->pdf_path)
                <div class="mt-2">
                    <a href="{{ asset('storage/' . $oficio->pdf_path) }}" target="_blank" class="btn btn-outline-info btn-sm">
                        <i class="fa-regular fa-file-pdf"></i> Ver PDF actual
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="form-group">
            <label for="descripcion">Notas internas</label>
            <textarea name="descripcion"
                      id="descripcion"
                      rows="5"
                      class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion', $oficio->descripcion) }}</textarea>
            @error('descripcion') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="col-lg-4">
        <div class="form-group">
            <label for="fotos">Fotos anexas</label>
            <input type="file"
                   name="fotos[]"
                   id="fotos"
                   multiple
                   accept="image/jpeg,image/png,image/jpg,image/webp"
                   class="form-control @error('fotos') is-invalid @enderror @error('fotos.*') is-invalid @enderror">
            @error('fotos') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
            @error('fotos.*') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
        </div>

        @if($oficio->exists && $oficio->fotos)
            <div class="oficio-fotos">
                @foreach($oficio->fotos as $foto)
                    <label class="oficio-foto">
                        <img src="{{ asset('storage/' . $foto) }}" alt="Foto del oficio">
                        <span>
                            <input type="checkbox" name="eliminar_fotos[]" value="{{ $foto }}">
                            Quitar
                        </span>
                    </label>
                @endforeach
            </div>
        @endif
    </div>
</div>

<hr>

<div class="d-flex flex-wrap justify-content-between align-items-center">
    <a href="{{ $oficio->exists ? route('oficios.show', $oficio) : route('oficios.index') }}" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Volver
    </a>

    <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-check"></i> Guardar oficio
    </button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sentido = document.getElementById('sentido');
        const unidad = document.getElementById('unidad_id');
        const fecha = document.getElementById('fecha_documento');
        const numero = document.getElementById('numero_oficio');
        const ayuda = document.getElementById('numero_help');
        const prefijos = @json($prefijosUnidad);

        if (!sentido || !numero || !ayuda) {
            return;
        }

        const updateNumeroState = function () {
            const esSalida = sentido.value === 'salida';
            const anio = fecha && fecha.value ? fecha.value.substring(0, 4) : String(new Date().getFullYear());
            const unidadId = unidad ? unidad.value : '';
            const prefijo = prefijos[unidadId] || 'OF';
            const ejemplo = `${prefijo}/001/${anio}`;

            numero.readOnly = esSalida;
            numero.required = !esSalida;

            if (esSalida) {
                const conservaOriginal = numero.dataset.originalSalida === '1'
                    && String(unidadId) === String(numero.dataset.originalUnidad || '')
                    && String(anio) === String(numero.dataset.originalAnio || '');

                if (!conservaOriginal) {
                    numero.value = '';
                } else if (!numero.value) {
                    numero.value = numero.dataset.originalNumero || '';
                }

                numero.placeholder = `Automático al guardar, ej. ${ejemplo}`;
                ayuda.textContent = numero.value
                    ? 'Número interno ya asignado. Se conserva mientras no cambies la unidad o el año.'
                    : `Se asignará automáticamente al guardar. Vista previa: ${ejemplo}.`;
                return;
            }

            numero.placeholder = 'Captura el número del documento recibido';
            ayuda.textContent = 'Para documentos de entrada, escribe el número tal como llega de la institución remitente.';
        };

        sentido.addEventListener('change', updateNumeroState);

        if (unidad) {
            unidad.addEventListener('change', updateNumeroState);
        }

        if (fecha) {
            fecha.addEventListener('change', updateNumeroState);
        }

        updateNumeroState();
    });
</script>
