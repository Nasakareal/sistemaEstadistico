<div class="vehiculo-section-title">
    <span>Identificación</span>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="vehiculo_marca">Marca <span class="text-danger">*</span></label>
            <input type="text" name="marca" id="vehiculo_marca" class="form-control js-uppercase @error('marca') is-invalid @enderror" value="{{ old('marca') }}" placeholder="Ej. NISSAN" required>
            @error('marca')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="vehiculo_modelo">Modelo</label>
            <input type="text" name="modelo" id="vehiculo_modelo" class="form-control js-uppercase @error('modelo') is-invalid @enderror" value="{{ old('modelo') }}" placeholder="Ej. 2020">
            @error('modelo')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="vehiculo_tipo_general">Tipo de vehículo <span class="text-danger">*</span></label>
            <select name="tipo_general" id="vehiculo_tipo_general" class="form-control" required>
                <option value="">Seleccione...</option>
                <option value="semoviente" {{ old('tipo_general') === 'semoviente' ? 'selected' : '' }}>Semoviente</option>
                <option value="automovil" {{ old('tipo_general') === 'automovil' ? 'selected' : '' }}>Automóvil</option>
                <option value="camion" {{ old('tipo_general') === 'camion' ? 'selected' : '' }}>Camión</option>
                <option value="camioneta" {{ old('tipo_general') === 'camioneta' ? 'selected' : '' }}>Camioneta</option>
                <option value="bicicleta" {{ old('tipo_general') === 'bicicleta' ? 'selected' : '' }}>Bicicleta</option>
                <option value="motocicleta" {{ old('tipo_general') === 'motocicleta' ? 'selected' : '' }}>Motocicleta</option>
                <option value="remolque" {{ old('tipo_general') === 'remolque' ? 'selected' : '' }}>Remolque</option>
                <option value="maquinaria" {{ old('tipo_general') === 'maquinaria' ? 'selected' : '' }}>Maquinaria</option>
                <option value="tren" {{ old('tipo_general') === 'tren' ? 'selected' : '' }}>Tren</option>
            </select>
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="vehiculo_tipo">Carrocería <span class="text-danger">*</span></label>
            <select name="tipo" id="vehiculo_tipo" class="form-control @error('tipo') is-invalid @enderror" data-old-tipo="{{ old('tipo') }}" required>
                <option value="">Seleccione un tipo primero...</option>
            </select>
            @error('tipo')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="vehiculo_linea">Línea <span class="text-danger">*</span></label>
            <input type="text" name="linea" id="vehiculo_linea" class="form-control js-uppercase @error('linea') is-invalid @enderror" value="{{ old('linea') }}" placeholder="Ej. NP300" required>
            @error('linea')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="vehiculo_color">Color <span class="text-danger">*</span></label>
            <input type="text" name="color" id="vehiculo_color" class="form-control js-uppercase @error('color') is-invalid @enderror" value="{{ old('color') }}" placeholder="Ej. BLANCO" required>
            @error('color')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="vehiculo_placas">Placas</label>
            <input type="text" name="placas" id="vehiculo_placas" class="form-control js-uppercase @error('placas') is-invalid @enderror" value="{{ old('placas') }}" placeholder="Ej. ABC123A">
            @error('placas')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="vehiculo_estado_placas">Estado de placas</label>
            <input type="text" name="estado_placas" id="vehiculo_estado_placas" class="form-control js-uppercase @error('estado_placas') is-invalid @enderror" value="{{ old('estado_placas') }}" placeholder="Ej. MICHOACÁN">
            @error('estado_placas')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
</div>

<div class="vehiculo-section-title">
    <span>Servicio y resguardo</span>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="vehiculo_serie">Serie</label>
            <input type="text" name="serie" id="vehiculo_serie" class="form-control js-uppercase @error('serie') is-invalid @enderror" value="{{ old('serie') }}" placeholder="NIV / serie">
            @error('serie')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group">
            <label for="vehiculo_capacidad">Capacidad <span class="text-danger">*</span></label>
            <input type="number" name="capacidad_personas" id="vehiculo_capacidad" class="form-control @error('capacidad_personas') is-invalid @enderror" value="{{ old('capacidad_personas', 0) }}" min="0" required>
            @error('capacidad_personas')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="vehiculo_tipo_servicio">Tipo de servicio <span class="text-danger">*</span></label>
            <select name="tipo_servicio" id="vehiculo_tipo_servicio" class="form-control @error('tipo_servicio') is-invalid @enderror" required>
                <option value="">Seleccione...</option>
                <option value="PARTICULAR" {{ old('tipo_servicio') === 'PARTICULAR' ? 'selected' : '' }}>Particular</option>
                <option value="OFICIAL" {{ old('tipo_servicio') === 'OFICIAL' ? 'selected' : '' }}>Oficial</option>
                <option value="PUBLICO" {{ old('tipo_servicio') === 'PUBLICO' ? 'selected' : '' }}>Público</option>
            </select>
            @error('tipo_servicio')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="vehiculo_tarjeta">Tarjeta de circulación</label>
            <input type="text" name="tarjeta_circulacion_nombre" id="vehiculo_tarjeta" class="form-control js-uppercase @error('tarjeta_circulacion_nombre') is-invalid @enderror" value="{{ old('tarjeta_circulacion_nombre') }}" placeholder="Nombre">
            @error('tarjeta_circulacion_nombre')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="vehiculo_grua_id">Grúa</label>
            <select name="grua_id" id="vehiculo_grua_id" class="form-control @error('grua_id') is-invalid @enderror">
                <option value="">Seleccione una grúa</option>
                @foreach (($gruas ?? collect()) as $grua)
                    <option value="{{ $grua->id }}" {{ (string) old('grua_id') === (string) $grua->id ? 'selected' : '' }}>
                        {{ $grua->nombre }}
                    </option>
                @endforeach
            </select>
            @error('grua_id')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="vehiculo_corralon">Corralón</label>
            <select name="corralon" id="vehiculo_corralon" class="form-control @error('corralon') is-invalid @enderror">
                <option value="">Seleccione un corralón</option>
                @foreach (($gruas ?? collect()) as $grua)
                    @php
                        $corralonValor = trim((string) ($grua->ubicacion_corralon ?: $grua->nombre));
                        $corralonTexto = trim((string) ($grua->ubicacion_corralon ?: $grua->nombre));
                    @endphp

                    @if ($corralonValor !== '')
                        <option value="{{ $corralonValor }}" {{ old('corralon') === $corralonValor ? 'selected' : '' }}>
                            {{ $corralonTexto }}
                        </option>
                    @endif
                @endforeach
            </select>
            @error('corralon')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="vehiculo_aseguradora">Aseguradora</label>
            <input type="text" name="aseguradora" id="vehiculo_aseguradora" class="form-control js-uppercase @error('aseguradora') is-invalid @enderror" value="{{ old('aseguradora') }}" placeholder="Nombre de la aseguradora">
            @error('aseguradora')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-2 d-flex align-items-center">
        <div class="custom-control custom-switch mt-3">
            <input type="checkbox" name="antecedente_vehiculo" value="1" class="custom-control-input" id="vehiculo_antecedente" {{ old('antecedente_vehiculo') ? 'checked' : '' }}>
            <label class="custom-control-label" for="vehiculo_antecedente">Antecedente</label>
        </div>
    </div>
</div>
