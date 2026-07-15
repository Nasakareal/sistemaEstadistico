@php
    $nombre = $nombre ?? '';
    $etiqueta = $etiqueta ?? $nombre;
    $opciones = $opciones ?? [];
    $requerido = $requerido ?? true;
    $valorActual = old($nombre, $valor ?? null);
    $valorNormalizado = mb_strtoupper(Illuminate\Support\Str::ascii(trim((string) $valorActual)), 'UTF-8');
    $errorCatalogo = $errors->first($nombre);
@endphp

<div class="form-group">
    <label for="{{ $nombre }}">
        {{ $etiqueta }}@if($requerido)<span style="color: red">*</span>@endif
    </label>
    <select name="{{ $nombre }}" id="{{ $nombre }}"
            class="form-control {{ $errorCatalogo ? 'is-invalid' : '' }}"
            @if($requerido) required @endif>
        <option value="" disabled {{ $valorNormalizado === '' ? 'selected' : '' }}>
            {{ $placeholder ?? 'Seleccione una opción' }}
        </option>
        @foreach($opciones as $valorOpcion => $textoOpcion)
            @php
                $opcionNormalizada = mb_strtoupper(Illuminate\Support\Str::ascii(trim((string) $valorOpcion)), 'UTF-8');
            @endphp
            <option value="{{ $valorOpcion }}" {{ $valorNormalizado === $opcionNormalizada ? 'selected' : '' }}>
                {{ $textoOpcion }}
            </option>
        @endforeach
    </select>
    @if($errorCatalogo)
        <span class="invalid-feedback" role="alert"><strong>{{ $errorCatalogo }}</strong></span>
    @endif
</div>
