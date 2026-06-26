@php
    $name = $name ?? 'infraccion_id';
    $selected = old($name, $selected ?? null);
    $help = $help ?? 'Selecciona una sancion para ver cuando aplica.';
    $infracciones = collect($infracciones ?? []);
    $articulos = $infracciones
        ->pluck('articulo')
        ->filter()
        ->unique()
        ->sortBy(fn ($articulo) => str_pad((string) $articulo, 8, '0', STR_PAD_LEFT))
        ->values();
    $grupos = $infracciones->groupBy(fn ($infraccion) => $infraccion->articulo ? 'Articulo ' . $infraccion->articulo : 'Sin articulo');
@endphp

<div class="form-group js-infraccion-picker {{ $class ?? '' }}">
    <label>{{ $label ?? 'Sancion' }}</label>
    @if($articulos->count() > 1)
        <select class="form-control custom-select form-control-sm js-infraccion-articulo mb-2">
            <option value="">Todos los articulos</option>
            @foreach($articulos as $articulo)
                <option value="{{ $articulo }}">Articulo {{ $articulo }}</option>
            @endforeach
        </select>
    @endif
    <select name="{{ $name }}" class="form-control custom-select js-infraccion-select" required>
        <option value="">Seleccionar</option>
        @foreach($grupos as $grupo => $items)
            <optgroup label="{{ $grupo }}">
                @foreach($items as $infraccion)
                    <option
                        value="{{ $infraccion->id }}"
                        data-articulo="{{ $infraccion->articulo }}"
                        data-referencia="{{ $infraccion->referencia_legal_corta }}"
                        data-sanciones="{{ $infraccion->resumen_sanciones }}"
                        data-descripcion="{{ $infraccion->descripcion }}"
                        data-fundamento="{{ $infraccion->fundamento_legal }}"
                        data-retencion="{{ $infraccion->retencion_vehiculo ? '1' : '0' }}"
                        {{ (string) $selected === (string) $infraccion->id ? 'selected' : '' }}>
                        {{ $infraccion->etiqueta_operativa }}
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
    <small class="form-text text-muted js-infraccion-help">{{ $help }}</small>
</div>
