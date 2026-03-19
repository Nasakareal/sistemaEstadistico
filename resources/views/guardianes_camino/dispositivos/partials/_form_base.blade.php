<input type="hidden" name="client_uuid" value="{{ old('client_uuid', (string) \Illuminate\Support\Str::uuid()) }}">
<input type="hidden" name="unidad_org_id" value="{{ old('unidad_org_id', auth()->user()->unidad_org_id ?? '') }}">
<input type="hidden" name="delegacion_id" value="{{ old('delegacion_id', auth()->user()->delegacion_id ?? '') }}">

<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info mb-4">
            <strong>Operativo:</strong> Guardianes del Camino
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="operativo_dispositivo_catalogo_id">Dispositivo <span style="color:red">*</span></label>
            <select name="operativo_dispositivo_catalogo_id" id="operativo_dispositivo_catalogo_id" class="form-control @error('operativo_dispositivo_catalogo_id') is-invalid @enderror" required>
                <option value="" disabled {{ old('operativo_dispositivo_catalogo_id') ? '' : 'selected' }}>Seleccione un dispositivo</option>
                @foreach($catalogos as $catalogo)
                    <option
                        value="{{ $catalogo->id }}"
                        data-nombre="{{ mb_strtoupper(trim($catalogo->nombre), 'UTF-8') }}"
                        {{ (string) old('operativo_dispositivo_catalogo_id') === (string) $catalogo->id ? 'selected' : '' }}>
                        {{ $catalogo->nombre }}
                    </option>
                @endforeach
            </select>
            @error('operativo_dispositivo_catalogo_id')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="fecha">Fecha <span style="color:red">*</span></label>
            <input type="date" name="fecha" id="fecha" class="form-control @error('fecha') is-invalid @enderror" value="{{ old('fecha', now()->toDateString()) }}" required>
            @error('fecha')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="hora">Hora</label>
            <input type="text" name="hora" id="hora" class="form-control @error('hora') is-invalid @enderror" value="{{ old('hora', now()->format('H:i')) }}" placeholder="HH:MM">
            @error('hora')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="hora_inicio">Hora inicio</label>
            <input type="text" name="hora_inicio" id="hora_inicio" class="form-control @error('hora_inicio') is-invalid @enderror" value="{{ old('hora_inicio') }}" placeholder="HH:MM">
            @error('hora_inicio')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="hora_fin">Hora fin</label>
            <input type="text" name="hora_fin" id="hora_fin" class="form-control @error('hora_fin') is-invalid @enderror" value="{{ old('hora_fin') }}" placeholder="HH:MM">
            @error('hora_fin')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="tipo_reporte">Tipo de reporte</label>
            <input type="text" name="tipo_reporte" id="tipo_reporte" class="form-control @error('tipo_reporte') is-invalid @enderror" value="{{ old('tipo_reporte') }}" placeholder="Ejemplo: Patrullaje, Apoyo a usuario">
            @error('tipo_reporte')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="destacamento_id">Destacamento <span style="color:red">*</span></label>
            <select name="destacamento_id" id="destacamento_id" class="form-control @error('destacamento_id') is-invalid @enderror" required>
                <option value="" disabled {{ old('destacamento_id') ? '' : 'selected' }}>Seleccione un destacamento</option>
                @if(isset($destacamentos) && $destacamentos->count())
                    @foreach($destacamentos as $destacamento)
                        <option value="{{ $destacamento->id }}" {{ (string) old('destacamento_id', auth()->user()->destacamento_id ?? '') === (string) $destacamento->id ? 'selected' : '' }}>
                            {{ $destacamento->nombre }}
                        </option>
                    @endforeach
                @endif
            </select>
            @error('destacamento_id')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="asunto">Asunto</label>
            <input type="text" name="asunto" id="asunto" class="form-control @error('asunto') is-invalid @enderror" value="{{ old('asunto') }}" placeholder="Ejemplo: PATRULLAJE DE SEGURIDAD Y VIGILANCIA">
            @error('asunto')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="lugar">Lugar</label>
            <input type="text" name="lugar" id="lugar" class="form-control @error('lugar') is-invalid @enderror" value="{{ old('lugar') }}" placeholder="Ingrese el lugar">
            @error('lugar')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="descripcion">Descripción breve</label>
            <textarea name="descripcion" id="descripcion" rows="3" class="form-control @error('descripcion') is-invalid @enderror" placeholder="Describa brevemente la actividad">{{ old('descripcion') }}</textarea>
            @error('descripcion')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
</div>

<div id="bloque_resumen_dispositivo" class="d-none">
    <div class="alert alert-secondary mb-4">
        <strong id="titulo_dispositivo_dinamico">Dispositivo seleccionado</strong>
    </div>
</div>
