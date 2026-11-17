@php($area = $area ?? null)
<div class="row">
    <div class="col-md-3 form-group">
        <label for="codigo">Código *</label>
        <input id="codigo" name="codigo" maxlength="30" required value="{{ old('codigo', $area->codigo ?? '') }}" class="form-control @error('codigo') is-invalid @enderror" placeholder="HISTORICO">
    </div>
    <div class="col-md-6 form-group">
        <label for="nombre">Nombre del área *</label>
        <input id="nombre" name="nombre" maxlength="150" required value="{{ old('nombre', $area->nombre ?? '') }}" class="form-control @error('nombre') is-invalid @enderror" placeholder="Sala de archivo histórico">
    </div>
    <div class="col-md-3 form-group">
        <label for="tipo">Tipo *</label>
        <select id="tipo" name="tipo" class="form-control" required>
            @foreach(['sala' => 'Sala', 'mesa' => 'Mesa', 'otro' => 'Otro'] as $valor => $etiqueta)
                <option value="{{ $valor }}" {{ old('tipo', $area->tipo ?? 'sala') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 form-group">
        <label for="plano_ancho">Ancho del plano</label>
        <input type="number" min="0" step="0.01" id="plano_ancho" name="plano_ancho" class="form-control" value="{{ old('plano_ancho', $area->plano_ancho ?? '') }}">
    </div>
    <div class="col-md-3 form-group">
        <label for="plano_alto">Alto del plano</label>
        <input type="number" min="0" step="0.01" id="plano_alto" name="plano_alto" class="form-control" value="{{ old('plano_alto', $area->plano_alto ?? '') }}">
    </div>
    <div class="col-md-3 form-group">
        <label for="activo">Estado *</label>
        <select id="activo" name="activo" class="form-control" required>
            <option value="1" {{ (string) old('activo', (int) ($area->activo ?? 1)) === '1' ? 'selected' : '' }}>Activa</option>
            <option value="0" {{ (string) old('activo', (int) ($area->activo ?? 1)) === '0' ? 'selected' : '' }}>Inactiva</option>
        </select>
    </div>
    <div class="col-12 form-group">
        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="3" maxlength="5000" class="form-control">{{ old('descripcion', $area->descripcion ?? '') }}</textarea>
    </div>
</div>
