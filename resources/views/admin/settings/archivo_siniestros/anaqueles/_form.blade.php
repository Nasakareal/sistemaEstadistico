@php($anaquel = $anaquel ?? null)
<div class="row">
    <div class="col-md-4 form-group">
        <label for="area_id">Área *</label>
        <select name="area_id" id="area_id" class="form-control" required>
            <option value="">Seleccione un área</option>
            @foreach($areas as $area)<option value="{{ $area->id }}" {{ (string) old('area_id', $anaquel->area_id ?? '') === (string) $area->id ? 'selected' : '' }}>{{ $area->nombre }} ({{ $area->codigo }})</option>@endforeach
        </select>
    </div>
    <div class="col-md-3 form-group"><label for="codigo">Código *</label><input id="codigo" name="codigo" maxlength="30" required class="form-control" value="{{ old('codigo', $anaquel->codigo ?? '') }}" placeholder="A01"></div>
    <div class="col-md-5 form-group"><label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="150" class="form-control" value="{{ old('nombre', $anaquel->nombre ?? '') }}" placeholder="Pared norte"></div>
    <div class="col-md-3 form-group"><label for="total_filas">Filas *</label><input type="number" id="total_filas" name="total_filas" class="form-control" min="1" max="100" required value="{{ old('total_filas', $anaquel->total_filas ?? 4) }}"></div>
    <div class="col-md-3 form-group"><label for="total_columnas">Columnas *</label><input type="number" id="total_columnas" name="total_columnas" class="form-control" min="1" max="100" required value="{{ old('total_columnas', $anaquel->total_columnas ?? 3) }}"></div>
    <div class="col-md-3 form-group"><label for="rotacion">Rotación (grados) *</label><input type="number" id="rotacion" name="rotacion" class="form-control" min="0" max="359" required value="{{ old('rotacion', $anaquel->rotacion ?? 0) }}"></div>
    <div class="col-md-3 form-group"><label for="activo">Estado *</label><select id="activo" name="activo" class="form-control"><option value="1" {{ (string) old('activo', (int) ($anaquel->activo ?? 1)) === '1' ? 'selected' : '' }}>Activo</option><option value="0" {{ (string) old('activo', (int) ($anaquel->activo ?? 1)) === '0' ? 'selected' : '' }}>Inactivo</option></select></div>
    <div class="col-md-3 form-group"><label for="plano_x">Posición X</label><input type="number" step="0.01" min="0" id="plano_x" name="plano_x" class="form-control" value="{{ old('plano_x', $anaquel->plano_x ?? '') }}"></div>
    <div class="col-md-3 form-group"><label for="plano_y">Posición Y</label><input type="number" step="0.01" min="0" id="plano_y" name="plano_y" class="form-control" value="{{ old('plano_y', $anaquel->plano_y ?? '') }}"></div>
    <div class="col-md-3 form-group"><label for="plano_ancho">Ancho en plano</label><input type="number" step="0.01" min="0" id="plano_ancho" name="plano_ancho" class="form-control" value="{{ old('plano_ancho', $anaquel->plano_ancho ?? '') }}"></div>
    <div class="col-md-3 form-group"><label for="plano_alto">Alto en plano</label><input type="number" step="0.01" min="0" id="plano_alto" name="plano_alto" class="form-control" value="{{ old('plano_alto', $anaquel->plano_alto ?? '') }}"></div>
</div>
<div class="alert alert-info mb-0"><i class="fa-solid fa-circle-info"></i> Las filas y columnas definen la cuadrícula del anaquel. Después registrarás cada bloque y su capacidad individual.</div>
