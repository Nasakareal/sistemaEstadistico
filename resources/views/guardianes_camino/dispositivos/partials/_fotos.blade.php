<div id="seccion_fotos" class="d-none">
    <hr>

    <div class="row">
        <div class="col-md-12 mb-2">
            <h5>Evidencia fotográfica</h5>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="fotos">Fotos</label>
                <input
                    type="file"
                    name="fotos[]"
                    id="fotos"
                    class="form-control @error('fotos') is-invalid @enderror @error('fotos.*') is-invalid @enderror"
                    multiple
                    accept=".jpg,.jpeg,.png,.webp"
                >
                @error('fotos')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
                @error('fotos.*')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
                <small class="form-text text-muted">
                    Puede seleccionar varias imágenes. Formatos permitidos: JPG, JPEG, PNG, WEBP. Máximo 5 MB por imagen.
                </small>
            </div>
        </div>

        <div class="col-md-12">
            <div id="preview_fotos_container" class="row"></div>
        </div>
    </div>
</div>
