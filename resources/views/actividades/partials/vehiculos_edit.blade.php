@php
    $vehiculosActividad = $actividad->relationLoaded('vehiculos')
        ? $actividad->vehiculos
        : $actividad->vehiculos()->orderBy('vehiculos.id')->get();
@endphp

<div class="card card-outline card-info mt-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
        <div>
            <h3 class="card-title mb-0">
                <i class="fa-solid fa-car-side"></i> Vehículos relacionados
            </h3>
            <div class="help-muted mt-1">Agregue o desvincule vehículos de esta actividad.</div>
        </div>

        <div class="d-flex align-items-center" style="gap:8px; flex-wrap:wrap;">
            <span class="badge badge-light vehiculo-total-badge">
                Total: {{ $vehiculosActividad->count() }}
            </span>

            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAgregarVehiculoActividad">
                <i class="fa-solid fa-plus"></i> Agregar vehículo
            </button>
        </div>
    </div>

    <div class="card-body">
        @if ($vehiculosActividad->count())
            <div class="vehiculos-grid">
                @foreach ($vehiculosActividad as $vehiculo)
                    @php
                        $placas = trim((string) ($vehiculo->placas ?? ''));
                        $placasFmt = $placas !== '' ? $placas : 'SIN PLACAS';
                        $marcaLinea = trim(collect([$vehiculo->marca ?? null, $vehiculo->linea ?? null])->filter()->implode(' '));
                        $marcaLinea = $marcaLinea !== '' ? $marcaLinea : 'VEHÍCULO';
                        $modelo = trim((string) ($vehiculo->modelo ?? ''));
                        $tipo = trim((string) ($vehiculo->tipo ?? ''));
                        $color = trim((string) ($vehiculo->color ?? ''));
                        $servicio = trim((string) ($vehiculo->tipo_servicio ?? ''));
                        $grua = trim((string) ($vehiculo->grua ?? ''));
                        $corralon = trim((string) ($vehiculo->corralon ?? ''));
                        $aseguradora = trim((string) ($vehiculo->aseguradora ?? ''));
                        $serie = trim((string) ($vehiculo->serie ?? ''));
                    @endphp

                    <div class="vehiculo-card">
                        <div class="vehiculo-card-head">
                            <div class="min-w-0">
                                <div class="vehiculo-title text-truncate">{{ $marcaLinea }}</div>
                                <div class="vehiculo-subtitle">
                                    {{ $modelo !== '' ? 'Modelo ' . $modelo : 'Modelo no especificado' }}
                                </div>
                            </div>

                            <span class="vehiculo-placa">
                                <i class="fa-solid fa-id-card"></i> {{ $placasFmt }}
                            </span>
                        </div>

                        <div class="vehiculo-card-body">
                            <div class="vehiculo-chip-row">
                                <span class="vehiculo-chip">
                                    <i class="fa-solid fa-car-rear"></i> {{ $tipo !== '' ? $tipo : 'Tipo N/D' }}
                                </span>
                                <span class="vehiculo-chip">
                                    <i class="fa-solid fa-palette"></i> {{ $color !== '' ? $color : 'Color N/D' }}
                                </span>
                                <span class="vehiculo-chip">
                                    <i class="fa-solid fa-user-group"></i> {{ (int) ($vehiculo->capacidad_personas ?? 0) }}
                                </span>
                            </div>

                            <div class="vehiculo-mini-grid">
                                <div>
                                    <span>Servicio</span>
                                    <strong>{{ $servicio !== '' ? $servicio : '—' }}</strong>
                                </div>
                                <div>
                                    <span>Serie</span>
                                    <strong>{{ $serie !== '' ? $serie : '—' }}</strong>
                                </div>
                                <div>
                                    <span>Grúa</span>
                                    <strong>{{ $grua !== '' ? $grua : '—' }}</strong>
                                </div>
                                <div>
                                    <span>Corralón</span>
                                    <strong>{{ $corralon !== '' ? $corralon : '—' }}</strong>
                                </div>
                                <div>
                                    <span>Aseguradora</span>
                                    <strong>{{ $aseguradora !== '' ? $aseguradora : '—' }}</strong>
                                </div>
                                <div>
                                    <span>Daños</span>
                                    <strong>
                                        {{ $vehiculo->monto_danos !== null ? '$' . number_format((float) $vehiculo->monto_danos, 2) : '—' }}
                                    </strong>
                                </div>
                            </div>

                            @if(!empty($vehiculo->partes_danadas))
                                <div class="vehiculo-nota">
                                    <span>Partes dañadas</span>
                                    <p>{{ $vehiculo->partes_danadas }}</p>
                                </div>
                            @endif

                            <div class="vehiculo-card-actions">
                                <span class="badge {{ $vehiculo->antecedente_vehiculo ? 'badge-danger' : 'badge-success' }}">
                                    Antecedente: {{ $vehiculo->antecedente_vehiculo ? 'SÍ' : 'NO' }}
                                </span>

                                <form action="{{ route('actividades.vehiculos.destroy', [$actividad->id, $vehiculo->id]) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('¿Desvincular este vehículo de la actividad?');">
                                        <i class="fa-solid fa-link-slash"></i> Desvincular
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-info mb-0">
                No hay vehículos vinculados a esta actividad.
            </div>
        @endif
    </div>
</div>

<div class="modal fade modal-actividad-vehiculo" id="modalAgregarVehiculoActividad" tabindex="-1" role="dialog" aria-labelledby="modalAgregarVehiculoActividadLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <form action="{{ route('actividades.vehiculos.store', $actividad->id) }}" method="POST" class="w-100">
            @csrf
            <input type="hidden" name="actividad_vehiculo_modal" value="1">

            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="modalAgregarVehiculoActividadLabel">
                            <i class="fa-solid fa-car-side"></i> Agregar vehículo
                        </h5>
                        <div class="modal-subtitle">Solo datos del vehículo.</div>
                    </div>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    @include('actividades.partials.vehiculo_modal_campos')
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-check"></i> Guardar vehículo
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
