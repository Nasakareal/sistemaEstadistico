@php
    $fundamentosSeleccionados = array_map('intval', old('fundamento_ids', []));
@endphp
<div class="modal fade modal-actividad-vehiculo" id="modalFundamentosActividad" tabindex="-1" role="dialog" aria-labelledby="modalFundamentosActividadLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title" id="modalFundamentosActividadLabel"><i class="fa-solid fa-scale-balanced"></i> Fundamentos generales</h5><div class="modal-subtitle">Puede seleccionar hasta 20 fundamentos.</div></div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <div class="input-group-prepend"><span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span></div>
                    <input type="search" id="buscarFundamentoActividad" class="form-control" placeholder="Buscar por artículo, nombre, código, descripción o fundamento legal...">
                </div>
                <div id="catalogoFundamentosActividad" class="fundamentos-catalogo">
                    @forelse(($fundamentos ?? collect()) as $fundamento)
                        @php
                            $busquedaFundamento = implode(' ', array_filter([
                                $fundamento->codigo,
                                $fundamento->nombre,
                                $fundamento->referencia_legal_corta,
                                $fundamento->descripcion,
                                $fundamento->fundamento_legal,
                                $fundamento->ambito_vehiculo_texto,
                            ]));
                        @endphp
                        <label class="fundamento-opcion" data-fundamento-id="{{ $fundamento->id }}" data-search="{{ $busquedaFundamento }}">
                            <input type="checkbox" class="js-fundamento-actividad" value="{{ $fundamento->id }}" {{ in_array((int) $fundamento->id, $fundamentosSeleccionados, true) ? 'checked' : '' }}>
                            <span class="fundamento-opcion__body">
                                <strong>{{ $fundamento->referencia_legal_corta ?: $fundamento->codigo }}</strong>
                                <span>{{ $fundamento->nombre }}</span>
                                <small>{{ $fundamento->ambito_vehiculo_texto }} · {{ $fundamento->resumen_sanciones }}</small>
                            </span>
                        </label>
                    @empty
                        <div class="alert alert-warning mb-0">No hay fundamentos activos en el catálogo.</div>
                    @endforelse
                </div>
                <div id="fundamentosSinResultados" class="alert alert-info mt-3 mb-0" style="display:none;">No se encontraron fundamentos con ese texto.</div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-success" data-dismiss="modal"><i class="fa-solid fa-check"></i> Listo</button></div>
        </div>
    </div>
</div>
