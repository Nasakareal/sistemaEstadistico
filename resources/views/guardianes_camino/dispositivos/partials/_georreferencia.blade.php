<div id="seccion_georreferencia" class="d-none">
    <hr>

    <div class="row">
        <div class="col-md-12 mb-2">
            <h5>Georreferencia y tramo</h5>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="carretera">Carretera</label>
                <input
                    type="text"
                    name="carretera"
                    id="carretera"
                    class="form-control @error('carretera') is-invalid @enderror"
                    value="{{ old('carretera') }}"
                    placeholder="Ejemplo: Autopista Siglo XXI"
                >
                @error('carretera')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="tramo">Tramo</label>
                <input
                    type="text"
                    name="tramo"
                    id="tramo"
                    class="form-control @error('tramo') is-invalid @enderror"
                    value="{{ old('tramo') }}"
                    placeholder="Ejemplo: Infiernillo - Las Cañas"
                >
                @error('tramo')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="kilometro">Kilómetro</label>
                <input
                    type="text"
                    name="kilometro"
                    id="kilometro"
                    class="form-control @error('kilometro') is-invalid @enderror"
                    value="{{ old('kilometro') }}"
                    placeholder="Ejemplo: 217+500"
                >
                @error('kilometro')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}">
        <input type="hidden" name="lng" id="lng" value="{{ old('lng') }}">
        <input type="hidden" name="coordenadas_texto" id="coordenadas_texto" value="{{ old('coordenadas_texto') }}">
        <input type="hidden" name="calidad_geo" id="calidad_geo" value="{{ old('calidad_geo') }}">
        <input type="hidden" name="fuente_ubicacion" id="fuente_ubicacion" value="{{ old('fuente_ubicacion') }}">

        <div class="col-md-12">
            <div class="form-group">
                <label>Ubicación (coordenadas)</label>

                <div class="d-flex align-items-center" style="gap:10px; flex-wrap:wrap;">
                    <button type="button" class="btn btn-outline-info" id="btn_geo">
                        <i class="fa-solid fa-location-crosshairs"></i> Usar mi ubicación
                    </button>

                    <span id="geo_status" class="help-muted">
                        @if(old('lat') && old('lng'))
                            OK: {{ old('lat') }}, {{ old('lng') }}
                            @if(old('calidad_geo')) (±{{ old('calidad_geo') }} m) @endif
                        @else
                            Sin coordenadas
                        @endif
                    </span>

                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        id="btn_geo_clear"
                        style="{{ old('lat') && old('lng') ? 'display:inline-block;' : 'display:none;' }}"
                    >
                        <i class="fa-solid fa-trash"></i> Quitar
                    </button>
                </div>

                <small class="help-muted">
                    Captura automática desde el navegador.
                </small>

                @error('lat')
                    <div class="text-danger mt-1"><strong>{{ $message }}</strong></div>
                @enderror

                @error('lng')
                    <div class="text-danger mt-1"><strong>{{ $message }}</strong></div>
                @enderror
            </div>
        </div>
    </div>
</div>
