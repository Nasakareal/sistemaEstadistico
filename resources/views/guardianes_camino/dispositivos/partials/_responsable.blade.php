<div id="seccion_responsable" class="d-none">
    <hr>

    <div class="row">
        <div class="col-md-12 mb-2">
            <h5>Responsable y personal participante</h5>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="cargo_responsable">Cargo responsable</label>
                <input type="text" name="cargo_responsable" id="cargo_responsable" class="form-control @error('cargo_responsable') is-invalid @enderror" value="{{ old('cargo_responsable') }}" placeholder="Ejemplo: Of. Coronado Martinez Iroyochi">
                @error('cargo_responsable')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-8">
            <div class="form-group">
                <label for="nombre_responsable">Nombre responsable</label>
                <input type="text" name="nombre_responsable" id="nombre_responsable" class="form-control @error('nombre_responsable') is-invalid @enderror" value="{{ old('nombre_responsable') }}" placeholder="Nombre del responsable del reporte">
                @error('nombre_responsable')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="elementos_participantes_texto">Elementos participantes</label>
                <textarea name="elementos_participantes_texto" id="elementos_participantes_texto" rows="5" class="form-control @error('elementos_participantes_texto') is-invalid @enderror" placeholder="Ejemplo: 25-7934&#10;P.P. Alonso Duran David&#10;P. Castrejón Zamudio Maribel&#10;P. Hernández Maya Christian Miguel">{{ old('elementos_participantes_texto') }}</textarea>
                @error('elementos_participantes_texto')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="requiere_evidencia">¿Requiere evidencia?</label>
                <select name="requiere_evidencia" id="requiere_evidencia" class="form-control @error('requiere_evidencia') is-invalid @enderror">
                    <option value="0" {{ old('requiere_evidencia', 0) == 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('requiere_evidencia') == 1 ? 'selected' : '' }}>Sí</option>
                </select>
                @error('requiere_evidencia')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>
    </div>
</div>
