@php
    $dispositivoForm = $dispositivo ?? null;
@endphp

<div id="seccion_narrativa" class="d-none">
    <hr>

    <div class="row">
        <div class="col-md-12 mb-2">
            <h5>Narrativa del reporte</h5>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="narrativa">Narrativa</label>
                <textarea name="narrativa" id="narrativa" rows="6" class="form-control @error('narrativa') is-invalid @enderror" placeholder="Redacte la narrativa formal del reporte">{{ old('narrativa', $dispositivoForm->narrativa ?? '') }}</textarea>
                @error('narrativa')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="acciones_realizadas">Acciones realizadas</label>
                <textarea name="acciones_realizadas" id="acciones_realizadas" rows="4" class="form-control @error('acciones_realizadas') is-invalid @enderror" placeholder="Describa las acciones realizadas">{{ old('acciones_realizadas', $dispositivoForm->acciones_realizadas ?? '') }}</textarea>
                @error('acciones_realizadas')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="frase_institucional">Frase institucional</label>
                <input type="text" name="frase_institucional" id="frase_institucional" class="form-control @error('frase_institucional') is-invalid @enderror" value="{{ old('frase_institucional', $dispositivoForm->frase_institucional ?? 'LA GUARDIA CIVIL NO TE MULTA, TE CUIDA EN EL CAMINO.') }}" placeholder="Frase institucional">
                @error('frase_institucional')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>
    </div>
</div>
