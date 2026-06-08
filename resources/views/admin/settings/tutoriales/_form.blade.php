@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="titulo">Titulo</label>
            <input type="text"
                   id="titulo"
                   name="titulo"
                   value="{{ old('titulo', $tutorial->titulo) }}"
                   class="form-control @error('titulo') is-invalid @enderror"
                   maxlength="180"
                   required>
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="plataforma">Plataforma</label>
            <select id="plataforma"
                    name="plataforma"
                    class="form-control @error('plataforma') is-invalid @enderror"
                    required>
                @foreach($plataformas as $value => $label)
                    <option value="{{ $value }}" {{ old('plataforma', $tutorial->plataforma ?: 'app_movil') === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="orden">Orden</label>
            <input type="number"
                   id="orden"
                   name="orden"
                   value="{{ old('orden', $tutorial->orden ?? 0) }}"
                   class="form-control @error('orden') is-invalid @enderror"
                   min="0"
                   max="999999">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="categoria_id">Categoria existente</label>
            <select id="categoria_id"
                    name="categoria_id"
                    class="form-control @error('categoria_id') is-invalid @enderror">
                <option value="">General / sin categoria</option>
                @foreach($categorias as $categoria)
                    <option value="{{ $categoria->id }}" {{ (string) old('categoria_id', $tutorial->tutorial_categoria_id) === (string) $categoria->id ? 'selected' : '' }}>
                        {{ $categoria->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="categoria_nueva">Nueva categoria</label>
            <input type="text"
                   id="categoria_nueva"
                   name="categoria_nueva"
                   value="{{ old('categoria_nueva') }}"
                   class="form-control @error('categoria_nueva') is-invalid @enderror"
                   maxlength="150"
                   placeholder="Ej. Hechos, Actividades, Feed">
            <small class="form-text text-muted">Si escribes una nueva, se usara en lugar de la categoria existente.</small>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="unidad_id">Unidad</label>
            <select id="unidad_id"
                    name="unidad_id"
                    class="form-control @error('unidad_id') is-invalid @enderror">
                <option value="">Todas las unidades</option>
                @foreach($unidades as $unidad)
                    <option value="{{ $unidad->id }}" {{ (string) old('unidad_id', $tutorial->unidad_id) === (string) $unidad->id ? 'selected' : '' }}>
                        {{ $unidad->nombre }}
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted">Si eliges una unidad, solo sus usuarios veran este tutorial.</small>
        </div>
    </div>
</div>

<div class="form-group">
    <label for="youtube_url">Link de YouTube</label>
    <input type="text"
           id="youtube_url"
           name="youtube_url"
           value="{{ old('youtube_url', $tutorial->youtube_url) }}"
           class="form-control @error('youtube_url') is-invalid @enderror"
           maxlength="500"
           placeholder="https://www.youtube.com/watch?v=..."
           required>
</div>

<div class="form-group">
    <label for="descripcion">Descripcion</label>
    <textarea id="descripcion"
              name="descripcion"
              class="form-control @error('descripcion') is-invalid @enderror"
              rows="4"
              maxlength="1200">{{ old('descripcion', $tutorial->descripcion) }}</textarea>
</div>

@php
    $activo = old('activo', $tutorial->exists ? $tutorial->activo : true);
@endphp

<div class="custom-control custom-switch">
    <input type="hidden" name="activo" value="0">
    <input type="checkbox"
           class="custom-control-input"
           id="activo"
           name="activo"
           value="1"
           {{ (bool) $activo ? 'checked' : '' }}>
    <label class="custom-control-label" for="activo">Publicado</label>
</div>
