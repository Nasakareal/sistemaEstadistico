<div id="seccion_georreferencia" class="d-none">
    <hr>

    <div class="row">
        <div class="col-md-12 mb-2">
            <h5>Georreferencia y tramo</h5>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="carretera">Carretera</label>
                <input type="text" name="carretera" id="carretera" class="form-control @error('carretera') is-invalid @enderror" value="{{ old('carretera') }}" placeholder="Ejemplo: Autopista Siglo XXI">
                @error('carretera')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="tramo">Tramo</label>
                <input type="text" name="tramo" id="tramo" class="form-control @error('tramo') is-invalid @enderror" value="{{ old('tramo') }}" placeholder="Ejemplo: Infiernillo - Las Cañas">
                @error('tramo')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="kilometro">Kilómetro</label>
                <input type="text" name="kilometro" id="kilometro" class="form-control @error('kilometro') is-invalid @enderror" value="{{ old('kilometro') }}" placeholder="Ejemplo: 217+500">
                @error('kilometro')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="lat">Latitud</label>
                <input type="number" step="0.0000001" name="lat" id="lat" class="form-control @error('lat') is-invalid @enderror" value="{{ old('lat') }}" placeholder="Ejemplo: 18.1124090">
                @error('lat')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="lng">Longitud</label>
                <input type="number" step="0.0000001" name="lng" id="lng" class="form-control @error('lng') is-invalid @enderror" value="{{ old('lng') }}" placeholder="Ejemplo: -101.9062920">
                @error('lng')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="coordenadas_texto">Coordenadas texto</label>
                <input type="text" name="coordenadas_texto" id="coordenadas_texto" class="form-control @error('coordenadas_texto') is-invalid @enderror" value="{{ old('coordenadas_texto') }}" placeholder="Ejemplo: 18.112409,-101.906292">
                @error('coordenadas_texto')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>
    </div>
</div>
