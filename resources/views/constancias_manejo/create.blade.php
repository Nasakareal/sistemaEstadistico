@extends('adminlte::page')

@section('plugins.Select2', true)

@section('title', 'Generar Constancias de Manejo')

@section('content_header')
    <div class="constancia-header">
        <h1>Generar Constancias de Manejo</h1>
        <p>Generación de folios únicos para impresión previa</p>
    </div>
@stop

@section('content')
@php
    $moduloUnico = $modulos->count() === 1 ? $modulos->first() : null;
    $tipoUnico = $tiposModuloDisponibles->count() === 1 ? $tiposModuloDisponibles->first() : null;
    $tipoSeleccionado = old('tipo_modulo', $tipoUnico);
@endphp
<div class="constancia-wrapper">
    <div class="constancia-card">

        <div class="constancia-card-header">
            <div>
                <h3>Lote de Constancias Inactivas</h3>
                <span>Las constancias se imprimen primero y se activan después del examen.</span>
            </div>
            <i class="fa-solid fa-id-card"></i>
        </div>

        <form action="{{ route('constancias_manejo.store') }}" method="POST">
            @csrf

            <div class="constancia-card-body">

                @if($tipoUnico)
                    <input type="hidden" name="tipo_modulo" value="{{ $tipoUnico }}">
                @else
                    <div class="form-group">
                        <label for="tipo_modulo">Origen de las constancias</label>
                        <select name="tipo_modulo" id="tipo_modulo" class="form-control" required>
                            <option value="">Seleccione Siniestros o Delegaciones</option>
                            @if($tiposModuloDisponibles->contains('SINIESTROS'))
                                <option value="SINIESTROS" {{ $tipoSeleccionado === 'SINIESTROS' ? 'selected' : '' }}>
                                    Siniestros
                                </option>
                            @endif
                            @if($tiposModuloDisponibles->contains('DELEGACION'))
                                <option value="DELEGACION" {{ $tipoSeleccionado === 'DELEGACION' ? 'selected' : '' }}>
                                    Delegaciones
                                </option>
                            @endif
                        </select>
                        @error('tipo_modulo')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                @endif

                <div class="form-group">
                    <label for="modulo_id">Módulo que imprimirá las constancias</label>
                    @if($moduloUnico)
                        <input type="hidden" name="modulo_id" value="{{ $moduloUnico->id }}">
                        <input type="text" class="form-control" value="{{ $moduloUnico->nombre }} - {{ $moduloUnico->tipo }}" readonly>
                    @else
                        <select name="modulo_id" id="modulo_id" class="form-control select2" required>
                            <option value="">Seleccione un módulo</option>
                            @foreach($modulos->groupBy('tipo') as $tipo => $modulosDelTipo)
                                <optgroup label="{{ $tipo === 'SINIESTROS' ? 'Siniestros' : 'Delegaciones' }}">
                                    @foreach($modulosDelTipo as $modulo)
                                        <option value="{{ $modulo->id }}"
                                                data-tipo="{{ $modulo->tipo }}"
                                                {{ (string) old('modulo_id') === (string) $modulo->id ? 'selected' : '' }}>
                                            {{ $modulo->nombre }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    @endif
                    @error('modulo_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                    @if($modulos->isEmpty())
                        <small class="text-warning">No hay un módulo activo asignado a tu delegación.</small>
                    @endif
                </div>

                <div class="form-group">
                    <label>Cantidad de constancias a imprimir</label>
                    <input type="number" name="cantidad" class="form-control" min="1" max="100" value="{{ old('cantidad', 1) }}" required>
                    @error('cantidad')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="constancia-info">
                    <i class="fa-solid fa-circle-info"></i>
                    <div>
                        <strong>Asignación automática de folios</strong>
                        <p>El sistema generará folios únicos e irrepetibles. Las constancias quedarán INACTIVAS hasta que el perito las active.</p>
                    </div>
                </div>

            </div>

            <div class="constancia-card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-print"></i> Generar lote
                </button>

                <a href="{{ route('constancias_manejo.index') }}" class="btn btn-secondary">
                    Cancelar
                </a>
            </div>
        </form>

    </div>
</div>
@stop

@section('css')
<style>
.constancia-header {
    text-align: center;
    margin-bottom: 20px;
}

.constancia-header h1 {
    font-weight: 800;
    margin-bottom: 4px;
    color: #f8fafc;
}

.constancia-header p {
    color: #cbd5e1;
    margin: 0;
}

.constancia-wrapper {
    display: flex;
    justify-content: center;
    padding: 10px 15px 35px;
}

.constancia-card {
    width: 100%;
    max-width: 760px;
    border-radius: 18px;
    overflow: visible;
    background: #0f172a;
    border: 1px solid #334155;
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.6);
}

.constancia-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 22px 26px;
    border-bottom: 1px solid #334155;
    background: #1e293b;
}

.constancia-card-header h3 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #f8fafc;
}

.constancia-card-header span {
    color: #cbd5e1;
    font-size: 14px;
}

.constancia-card-header i {
    font-size: 34px;
    color: #60a5fa;
}

.constancia-card-body {
    padding: 26px;
}

.form-group {
    margin-bottom: 22px;
}

label {
    display: block;
    font-weight: 700;
    color: #e2e8f0;
    margin-bottom: 8px;
}

.form-control {
    height: 46px;
    border-radius: 12px;
    background-color: #020617 !important;
    border: 1px solid #3b82f6 !important;
    color: #ffffff !important;
    font-weight: 600;
    box-shadow: none;
}

.form-control:focus {
    background-color: #020617 !important;
    color: #ffffff !important;
    border-color: #60a5fa !important;
    box-shadow: 0 0 0 0.15rem rgba(96, 165, 250, 0.3) !important;
}

select.form-control,
select.form-control option {
    background-color: #020617 !important;
    color: #ffffff !important;
}

.select2-container {
    width: 100% !important;
}

.select2-container--default .select2-selection--single {
    height: 46px !important;
    border-radius: 12px !important;
    background-color: #020617 !important;
    border: 1px solid #3b82f6 !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #ffffff !important;
    line-height: 46px !important;
    padding-left: 16px !important;
    font-weight: 600 !important;
}

.select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #ffffff !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 46px !important;
    right: 10px !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: #ffffff transparent transparent transparent !important;
}

.select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
    border-color: transparent transparent #ffffff transparent !important;
}

.select2-container--default .select2-dropdown,
.select2-dropdown,
.select2-container .select2-dropdown,
span.select2-container span.select2-dropdown {
    background-color: #020617 !important;
    border: 1px solid #3b82f6 !important;
    color: #ffffff !important;
    box-shadow: 0 14px 40px rgba(0, 0, 0, 0.85) !important;
}

.select2-container--default .select2-results,
.select2-container--default .select2-results > .select2-results__options,
.select2-results,
.select2-results__options {
    background-color: #020617 !important;
    color: #ffffff !important;
}

.select2-container--default .select2-results__option,
.select2-results__option {
    background-color: #020617 !important;
    color: #ffffff !important;
    padding: 10px 14px !important;
    font-weight: 600 !important;
}

.select2-container--default .select2-results__option--selected {
    background-color: #1e40af !important;
    color: #ffffff !important;
}

.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable,
.select2-container--default .select2-results__option--highlighted[aria-selected],
.select2-results__option--highlighted {
    background-color: #2563eb !important;
    color: #ffffff !important;
}

.select2-container--default .select2-search--dropdown,
.select2-search--dropdown {
    background-color: #020617 !important;
    padding: 8px !important;
}

.select2-container--default .select2-search--dropdown .select2-search__field,
.select2-search--dropdown .select2-search__field {
    background-color: #0f172a !important;
    color: #ffffff !important;
    border: 1px solid #3b82f6 !important;
    border-radius: 8px !important;
    outline: none !important;
}

.select2-container--open {
    z-index: 999999 !important;
}

.constancia-info {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    padding: 18px;
    border-radius: 14px;
    background-color: #0c4a6e;
    border: 1px solid #38bdf8;
    color: #e0f2fe;
}

.constancia-info i {
    font-size: 22px;
    color: #38bdf8;
    margin-top: 3px;
}

.constancia-info p {
    margin: 4px 0 0;
    color: #dbeafe;
}

.constancia-card-footer {
    display: flex;
    justify-content: center;
    gap: 10px;
    padding: 20px 26px 26px;
    border-top: 1px solid #334155;
}

.btn {
    border-radius: 12px;
    font-weight: 700;
    padding: 10px 18px;
}
</style>
@stop

@section('js')
@php
    $modulosParaJavascript = $modulos->map(function ($modulo) {
        return [
            'id' => $modulo->id,
            'nombre' => $modulo->nombre,
            'tipo' => $modulo->tipo,
        ];
    })->values();
@endphp
<script>
$(function () {
    const modulos = @json($modulosParaJavascript);
    const moduloAnterior = @json((string) old('modulo_id', ''));
    const $tipoModulo = $('#tipo_modulo');
    const $modulo = $('#modulo_id');
    const select2Disponible = typeof $.fn.select2 === 'function';

    if (select2Disponible) {
        $modulo.select2({
            width: '100%',
            placeholder: 'Seleccione un módulo',
            allowClear: true
        });
    }

    function refrescarSelectorModulo() {
        if (select2Disponible) {
            $modulo.trigger('change.select2');
        }
    }

    function cargarModulos(tipo, seleccionado = '') {
        $modulo.empty();

        if (!tipo) {
            $modulo.append(new Option('Seleccione primero el origen', '', true, false));
            $modulo.prop('disabled', true);
            refrescarSelectorModulo();
            return;
        }

        $modulo.append(new Option('Seleccione un módulo', '', true, false));

        modulos
            .filter(modulo => modulo.tipo === tipo)
            .forEach(modulo => {
                const opcion = new Option(modulo.nombre, modulo.id, false, String(modulo.id) === String(seleccionado));
                $modulo.append(opcion);
            });

        $modulo.prop('disabled', false);
        refrescarSelectorModulo();
    }

    @if(!$moduloUnico)
        const tipoInicial = $tipoModulo.length
            ? $tipoModulo.val()
            : @json($tipoUnico);

        cargarModulos(tipoInicial, moduloAnterior);

        $tipoModulo.on('change', function () {
            cargarModulos(this.value);
        });
    @endif
});
</script>
@stop
