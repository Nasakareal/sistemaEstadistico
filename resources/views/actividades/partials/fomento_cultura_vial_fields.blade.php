@php
    $detalleFomento = $detalleFomento ?? ($actividad->fomentoCulturaVialDetalle ?? null);
    $fomentoValue = function ($campo, $default = null) use ($detalleFomento) {
        return old('fomento.' . $campo, data_get($detalleFomento, $campo, $default));
    };

    $nivelesEducativosFomento = [
        'PREESCOLAR',
        'PRIMARIA',
        'SECUNDARIA',
        'MEDIA SUPERIOR',
        'SUPERIOR',
        'SECTOR PRIVADO',
        'SECTOR PUBLICO',
        'MEDIO RURAL',
    ];

    $sectoresFomento = [
        'CICLISTAS',
        'MOTOCICLISTAS',
        'PARTICULARES',
        'TRANSPORTE PUBLICO Y ESCOLAR',
    ];

    $nivelSeleccionadoFomento = (string) $fomentoValue('nivel_educativo', '');
    $sectorSeleccionadoFomento = (string) $fomentoValue('sector', '');
    $programaSeleccionadoFomento = (string) old(
        'fomento.programa_id',
        data_get($detalleFomento, 'fomento_cultura_vial_programa_id', '')
    );
@endphp

<div id="fomento_cultura_vial_panel"
     class="fomento-panel"
     style="{{ ($mostrarFomentoCulturaVial ?? false) ? '' : 'display:none;' }}">
    <div class="fomento-panel__title">
        <i class="fa-solid fa-school"></i>
        <span>Datos estadísticos de Fomento a la Cultura Vial</span>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="fomento_programa_id">Programa / taller / campaña</label>
                <select name="fomento[programa_id]"
                        id="fomento_programa_id"
                        class="form-control @error('fomento.programa_id') is-invalid @enderror"
                        data-selected="{{ $programaSeleccionadoFomento }}">
                    <option value="">Seleccione una subcategoría primero...</option>
                </select>
                @error('fomento.programa_id')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
                <small id="fomento_programa_help" class="help-muted"></small>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="fomento_nivel_educativo">Nivel educativo</label>
                <select name="fomento[nivel_educativo]"
                        id="fomento_nivel_educativo"
                        class="form-control @error('fomento.nivel_educativo') is-invalid @enderror">
                    <option value="">Seleccione...</option>
                    @foreach ($nivelesEducativosFomento as $opcion)
                        <option value="{{ $opcion }}" {{ $nivelSeleccionadoFomento === $opcion ? 'selected' : '' }}>
                            {{ $opcion }}
                        </option>
                    @endforeach
                    @if ($nivelSeleccionadoFomento !== '' && !in_array($nivelSeleccionadoFomento, $nivelesEducativosFomento, true))
                        <option value="{{ $nivelSeleccionadoFomento }}" selected>{{ $nivelSeleccionadoFomento }}</option>
                    @endif
                </select>
                @error('fomento.nivel_educativo')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="fomento_sector">Sector</label>
                <select name="fomento[sector]"
                        id="fomento_sector"
                        class="form-control @error('fomento.sector') is-invalid @enderror">
                    <option value="">Seleccione...</option>
                    @foreach ($sectoresFomento as $opcion)
                        <option value="{{ $opcion }}" {{ $sectorSeleccionadoFomento === $opcion ? 'selected' : '' }}>
                            {{ $opcion }}
                        </option>
                    @endforeach
                    @if ($sectorSeleccionadoFomento !== '' && !in_array($sectorSeleccionadoFomento, $sectoresFomento, true))
                        <option value="{{ $sectorSeleccionadoFomento }}" selected>{{ $sectorSeleccionadoFomento }}</option>
                    @endif
                </select>
                @error('fomento.sector')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
    </div>

    <div class="row">
        @foreach ([
            'ninas' => 'Niñas',
            'ninos' => 'Niños',
            'adolescentes_mujeres' => 'Adolescentes mujeres',
            'adolescentes_hombres' => 'Adolescentes hombres',
            'docentes_hombres' => 'Docentes hombres',
            'docentes_mujeres' => 'Docentes mujeres',
            'hombres' => 'Hombres',
            'mujeres' => 'Mujeres',
        ] as $campo => $label)
            <div class="col-md-3 col-sm-6">
                <div class="form-group">
                    <label for="fomento_{{ $campo }}">{{ $label }}</label>
                    <input type="number"
                           min="0"
                           name="fomento[{{ $campo }}]"
                           id="fomento_{{ $campo }}"
                           class="form-control @error('fomento.' . $campo) is-invalid @enderror"
                           value="{{ $fomentoValue($campo, 0) }}"
                           data-fomento-total-source="1">
                    @error('fomento.' . $campo)
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>
        @endforeach

        <div class="col-md-3 col-sm-6">
            <div class="form-group">
                <label for="fomento_total_poblacion_atendida">Total población atendida</label>
                <input type="number"
                       min="0"
                       name="fomento[total_poblacion_atendida]"
                       id="fomento_total_poblacion_atendida"
                       class="form-control @error('fomento.total_poblacion_atendida') is-invalid @enderror"
                       value="{{ $fomentoValue('total_poblacion_atendida', 0) }}"
                       readonly>
                @error('fomento.total_poblacion_atendida')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
    </div>
</div>
