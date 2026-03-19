<div id="seccion_observaciones" class="d-none">
    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="observaciones">Observaciones</label>
                <textarea name="observaciones" id="observaciones" rows="4" class="form-control @error('observaciones') is-invalid @enderror" placeholder="Ingrese observaciones generales del dispositivo">{{ old('observaciones') }}</textarea>
                @error('observaciones')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>
    </div>
</div>
