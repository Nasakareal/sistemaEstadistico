@php
    $respuestasFormulario = old('respuestas');

    if (!$respuestasFormulario) {
        $respuestasFormulario = [];

        if ($pregunta->exists) {
            foreach ($pregunta->respuestas as $respuesta) {
                $respuestasFormulario[] = [
                    'respuesta' => $respuesta->respuesta,
                    'es_correcta' => $respuesta->es_correcta,
                ];
            }
        }
    }

    while (count($respuestasFormulario) < 4) {
        $respuestasFormulario[] = [
            'respuesta' => '',
            'es_correcta' => false,
        ];
    }

    $respuestaCorrecta = old('respuesta_correcta');

    if ($respuestaCorrecta === null) {
        $respuestaCorrecta = 0;

        foreach ($respuestasFormulario as $index => $respuesta) {
            if (!empty($respuesta['es_correcta'])) {
                $respuestaCorrecta = $index;
                break;
            }
        }
    }
@endphp

@csrf

@if($pregunta->exists)
    @method('PUT')
@endif

<div class="row">
    <div class="col-md-8">
        <div class="form-group">
            <label for="pregunta">Pregunta</label>
            <textarea
                name="pregunta"
                id="pregunta"
                class="form-control @error('pregunta') is-invalid @enderror"
                rows="4"
                required
            >{{ old('pregunta', $pregunta->pregunta) }}</textarea>
            @error('pregunta')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="tipo_licencia">Tipo de licencia</label>
            <select
                name="tipo_licencia"
                id="tipo_licencia"
                class="form-control @error('tipo_licencia') is-invalid @enderror"
                required
            >
                @foreach($tiposLicencia as $valor => $label)
                    <option value="{{ $valor }}" {{ old('tipo_licencia', $pregunta->tipo_licencia) === $valor ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('tipo_licencia')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="form-group">
            <div class="custom-control custom-switch">
                <input
                    type="checkbox"
                    name="activo"
                    value="1"
                    class="custom-control-input"
                    id="activo"
                    {{ old('activo', $pregunta->activo ?? true) ? 'checked' : '' }}
                >
                <label class="custom-control-label" for="activo">Pregunta activa</label>
            </div>
        </div>
    </div>
</div>

<hr>

<h5 class="mb-3"><strong>Respuestas</strong></h5>
<p class="text-muted mb-3">Captura al menos dos opciones y marca cuál es la correcta.</p>

@error('respuestas')
    <div class="alert alert-danger">{{ $message }}</div>
@enderror

@error('respuesta_correcta')
    <div class="alert alert-danger">{{ $message }}</div>
@enderror

@foreach($respuestasFormulario as $index => $respuesta)
    <div class="form-group answer-row">
        <div class="input-group">
            <div class="input-group-prepend">
                <div class="input-group-text">
                    <input
                        type="radio"
                        name="respuesta_correcta"
                        value="{{ $index }}"
                        {{ (int) $respuestaCorrecta === (int) $index ? 'checked' : '' }}
                        aria-label="Respuesta correcta"
                    >
                </div>
            </div>
            <input
                type="text"
                name="respuestas[{{ $index }}][respuesta]"
                class="form-control"
                value="{{ old('respuestas.' . $index . '.respuesta', $respuesta['respuesta'] ?? '') }}"
                placeholder="Respuesta {{ $index + 1 }}"
            >
        </div>
    </div>
@endforeach

<hr>

<button type="submit" class="btn btn-primary">
    <i class="fa-solid fa-check"></i> Guardar
</button>

<a href="{{ route('constancias_manejo.preguntas.index') }}" class="btn btn-secondary">
    <i class="fa-solid fa-ban"></i> Cancelar
</a>
