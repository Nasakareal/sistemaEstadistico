<div id="seccion_apoyo_usuario" class="d-none">
    <hr>

    <div class="row">
        <div class="col-md-12 mb-2">
            <h5>Datos de apoyo a usuario</h5>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="nombre_conductor">Nombre del conductor o usuario</label>
                <input type="text" name="nombre_conductor" id="nombre_conductor" class="form-control @error('nombre_conductor') is-invalid @enderror" value="{{ old('nombre_conductor') }}" placeholder="Nombre completo">
                @error('nombre_conductor')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="ocupacion_conductor">Ocupación</label>
                <input type="text" name="ocupacion_conductor" id="ocupacion_conductor" class="form-control @error('ocupacion_conductor') is-invalid @enderror" value="{{ old('ocupacion_conductor') }}" placeholder="Ejemplo: Psicólogo">
                @error('ocupacion_conductor')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label for="acompanantes_cantidad">Acompañantes</label>
                <input type="number" min="0" name="acompanantes_cantidad" id="acompanantes_cantidad" class="form-control @error('acompanantes_cantidad') is-invalid @enderror" value="{{ old('acompanantes_cantidad', 0) }}">
                @error('acompanantes_cantidad')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-9">
            <div class="form-group">
                <label for="vehiculo_descripcion">Descripción del vehículo</label>
                <input type="text" name="vehiculo_descripcion" id="vehiculo_descripcion" class="form-control @error('vehiculo_descripcion') is-invalid @enderror" value="{{ old('vehiculo_descripcion') }}" placeholder="Ejemplo: Sedan Chevrolet Groove color rojo">
                @error('vehiculo_descripcion')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="placas_apoyado">Placas</label>
                <input type="text" name="placas_apoyado" id="placas_apoyado" class="form-control @error('placas_apoyado') is-invalid @enderror" value="{{ old('placas_apoyado') }}" placeholder="Ejemplo: UYS-474-D">
                @error('placas_apoyado')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="procedencia">Procedencia</label>
                <input type="text" name="procedencia" id="procedencia" class="form-control @error('procedencia') is-invalid @enderror" value="{{ old('procedencia') }}" placeholder="Ejemplo: Ixtapa Zihuatanejo">
                @error('procedencia')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="destino">Destino</label>
                <input type="text" name="destino" id="destino" class="form-control @error('destino') is-invalid @enderror" value="{{ old('destino') }}" placeholder="Destino">
                @error('destino')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="motivo_apoyo">Motivo del apoyo</label>
                <textarea name="motivo_apoyo" id="motivo_apoyo" rows="4" class="form-control @error('motivo_apoyo') is-invalid @enderror" placeholder="Ejemplo: Ponchadura de neumático delantero del lado del copiloto">{{ old('motivo_apoyo') }}</textarea>
                @error('motivo_apoyo')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>
    </div>
</div>
