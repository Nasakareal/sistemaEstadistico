@extends('adminlte::page')

@section('title', 'Editar Consolidado de Operativos')

@section('content_header')
<div class="d-flex align-items-center justify-content-between">
    <div>
        <h1 class="mb-0">Editar Consolidado de Operativos</h1>
        <small class="text-muted">Actualice únicamente los operativos realizados por su destacamento.</small>
    </div>

    <a href="{{ route('operativos.show', $capturaUuid) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Volver
    </a>
</div>
@stop

@section('content')

<div class="card shadow-sm">
    <form action="{{ route('operativos.update', $capturaUuid) }}" method="POST" enctype="multipart/form-data" id="form_operativos">
        @csrf
        @method('PUT')

        <div class="card-body">

            <div class="card card-outline card-primary mb-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle mr-1"></i> Datos generales
                    </h3>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Fecha</label>
                                <input type="date" name="fecha" class="form-control"
                                    value="{{ old('fecha', $fecha) }}" required>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Hora</label>
                                <input type="time" name="hora" class="form-control"
                                    value="{{ old('hora', !empty($hora) ? \Carbon\Carbon::parse($hora)->format('H:i') : '') }}" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Descripción general</label>
                                <input type="text" name="descripcion_general" class="form-control"
                                    value="{{ old('descripcion_general', $descripcionGeneral) }}"
                                    placeholder="Ej. OPERATIVO GUARDIANES DEL CAMINO" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label>Tramos / Municipios</label>
                        <input type="text" name="tramos" class="form-control"
                            value="{{ old('tramos', $tramos) }}"
                            placeholder="Ej. Aeropuerto, Zinapécuaro, Queréndaro, Indaparapeo, Charo, Morelia...">
                    </div>
                </div>
            </div>

            <div class="card card-outline card-primary mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title">
                        <i class="fas fa-shield-alt mr-1"></i> Operativos capturados
                    </h3>
                    <span class="badge badge-light border" id="contador_operativos_activos">0 operativos con captura</span>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0" id="tabla_operativos">
                            <thead class="bg-light">
                                <tr class="text-center align-middle">
                                    <th style="min-width: 240px;">Operativo</th>
                                    <th style="min-width: 110px;">Realizados</th>
                                    <th style="min-width: 120px;">Vehículos</th>
                                    <th style="min-width: 120px;">Personas</th>
                                    <th style="min-width: 120px;">Veh. impact.</th>
                                    <th style="min-width: 120px;">Pers. impact.</th>
                                    <th style="min-width: 120px;">Edo. fuerza</th>
                                    <th style="min-width: 110px;">KM</th>
                                    <th style="min-width: 220px;">CRP´s</th>
                                    <th style="min-width: 260px;">Fotos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($catalogos as $c)
                                    @php
                                        $op = $operativosPorCatalogo->get($c->id);
                                    @endphp

                                    <tr class="fila-operativo" data-id="{{ $c->id }}">
                                        <td class="align-middle bg-white">
                                            <strong class="text-dark">{{ $c->nombre }}</strong>

                                            <input type="hidden" name="items[{{ $c->id }}][operativo_catalogo_id]" value="{{ $c->id }}">
                                            <input type="hidden" name="items[{{ $c->id }}][operativo_id]" value="{{ old("items.$c->id.operativo_id", $op->id ?? '') }}">
                                        </td>

                                        <td>
                                            <input type="number" min="0" class="form-control form-control-sm text-center campo-operativo"
                                                name="items[{{ $c->id }}][dispositivos_realizados]"
                                                value="{{ old("items.$c->id.dispositivos_realizados", $op->dispositivos_realizados ?? 0) }}">
                                        </td>

                                        <td>
                                            <input type="number" min="0" class="form-control form-control-sm text-center campo-operativo"
                                                name="items[{{ $c->id }}][vehiculos_inspeccionados]"
                                                value="{{ old("items.$c->id.vehiculos_inspeccionados", $op->vehiculos_inspeccionados ?? 0) }}">
                                        </td>

                                        <td>
                                            <input type="number" min="0" class="form-control form-control-sm text-center campo-operativo"
                                                name="items[{{ $c->id }}][personas_inspeccionadas]"
                                                value="{{ old("items.$c->id.personas_inspeccionadas", $op->personas_inspeccionadas ?? 0) }}">
                                        </td>

                                        <td>
                                            <input type="number" min="0" class="form-control form-control-sm text-center campo-operativo"
                                                name="items[{{ $c->id }}][vehiculos_impactados]"
                                                value="{{ old("items.$c->id.vehiculos_impactados", $op->vehiculos_impactados ?? 0) }}">
                                        </td>

                                        <td>
                                            <input type="number" min="0" class="form-control form-control-sm text-center campo-operativo"
                                                name="items[{{ $c->id }}][personas_impactadas]"
                                                value="{{ old("items.$c->id.personas_impactadas", $op->personas_impactadas ?? 0) }}">
                                        </td>

                                        <td>
                                            <input type="number" min="0" class="form-control form-control-sm text-center campo-operativo"
                                                name="items[{{ $c->id }}][estado_fuerza_participante]"
                                                value="{{ old("items.$c->id.estado_fuerza_participante", $op->estado_fuerza_participante ?? 0) }}">
                                        </td>

                                        <td>
                                            <input type="number" min="0" step="0.01" class="form-control form-control-sm text-center campo-operativo"
                                                name="items[{{ $c->id }}][kilometros_recorridos]"
                                                value="{{ old("items.$c->id.kilometros_recorridos", $op->kilometros_recorridos ?? 0) }}">
                                        </td>

                                        <td>
                                            <input type="text" class="form-control form-control-sm campo-operativo"
                                                name="items[{{ $c->id }}][crps_participantes]"
                                                value="{{ old("items.$c->id.crps_participantes", $op->crps_participantes ?? '') }}"
                                                placeholder="25-XXXX, 22-XXXX">
                                        </td>

                                        <td class="align-middle">
                                            @if($op && $op->fotos && $op->fotos->count())
                                                <div class="mb-2">
                                                    @foreach($op->fotos as $foto)
                                                        <div class="foto-existente-item">
                                                            <div class="foto-existente-preview">
                                                                <a href="{{ asset('storage/'.$foto->foto_path) }}" target="_blank">
                                                                    <img src="{{ asset('storage/'.$foto->foto_path) }}" alt="{{ $foto->foto_nombre_original }}">
                                                                </a>
                                                            </div>

                                                            <div class="foto-existente-info">
                                                                <small class="d-block text-light font-weight-bold">
                                                                    {{ $foto->foto_nombre_original ?: 'FOTO' }}
                                                                </small>

                                                                <div class="custom-control custom-checkbox mt-1">
                                                                    <input type="checkbox"
                                                                        class="custom-control-input check-eliminar-foto"
                                                                        id="eliminar_foto_{{ $foto->id }}"
                                                                        name="eliminar_fotos[]"
                                                                        value="{{ $foto->id }}">
                                                                    <label class="custom-control-label text-warning" for="eliminar_foto_{{ $foto->id }}">
                                                                        Eliminar
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <input type="file" class="form-control-file campo-fotos"
                                                name="fotos[{{ $c->id }}][]" accept="image/*" multiple>

                                            <small class="text-muted d-block mt-1 nombre-fotos"></small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-info mb-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar mr-1"></i> Totales generales
                    </h3>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Antecedentes personas</label>
                                <input type="number" min="0" name="totales[antecedentes_personas]" class="form-control"
                                    value="{{ old('totales.antecedentes_personas', $totales['antecedentes_personas'] ?? 0) }}">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Antecedentes vehículos</label>
                                <input type="number" min="0" name="totales[antecedentes_vehiculos]" class="form-control"
                                    value="{{ old('totales.antecedentes_vehiculos', $totales['antecedentes_vehiculos'] ?? 0) }}">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Antecedentes motos</label>
                                <input type="number" min="0" name="totales[antecedentes_motos]" class="form-control"
                                    value="{{ old('totales.antecedentes_motos', $totales['antecedentes_motos'] ?? 0) }}">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Antecedentes camiones</label>
                                <input type="number" min="0" name="totales[antecedentes_camiones]" class="form-control"
                                    value="{{ old('totales.antecedentes_camiones', $totales['antecedentes_camiones'] ?? 0) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-0">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Puestas a disposición</label>
                                <input type="number" min="0" name="totales[puestas_disposicion]" class="form-control"
                                    value="{{ old('totales.puestas_disposicion', $totales['puestas_disposicion'] ?? 0) }}">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Vehículos recuperados</label>
                                <input type="number" min="0" name="totales[vehiculos_recuperados]" class="form-control"
                                    value="{{ old('totales.vehiculos_recuperados', $totales['vehiculos_recuperados'] ?? 0) }}">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Armas aseguradas</label>
                                <input type="number" min="0" name="totales[armas_aseguradas]" class="form-control"
                                    value="{{ old('totales.armas_aseguradas', $totales['armas_aseguradas'] ?? 0) }}">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Mercancía recuperada</label>
                                <input type="number" min="0" name="totales[mercancia_recuperada]" class="form-control"
                                    value="{{ old('totales.mercancia_recuperada', $totales['mercancia_recuperada'] ?? 0) }}">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Decomiso de drogas</label>
                                <input type="number" min="0" name="totales[decomiso_drogas]" class="form-control"
                                    value="{{ old('totales.decomiso_drogas', $totales['decomiso_drogas'] ?? 0) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-secondary mb-0">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-sticky-note mr-1"></i> Observaciones
                    </h3>
                </div>

                <div class="card-body">
                    <textarea name="observaciones" class="form-control" rows="3"
                        placeholder="Observaciones generales del consolidado...">{{ old('observaciones', $observaciones) }}</textarea>
                </div>
            </div>

        </div>

        <div class="card-footer text-right">
            <a href="{{ route('operativos.show', $capturaUuid) }}" class="btn btn-secondary">
                Cancelar
            </a>

            <button type="submit" class="btn btn-primary" id="btn_guardar_operativos">
                <i class="fas fa-save mr-1"></i> Actualizar consolidado
            </button>
        </div>
    </form>
</div>

@stop

@section('css')
<style>
    #tabla_operativos {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
        background: transparent;
    }

    #tabla_operativos td,
    #tabla_operativos th {
        vertical-align: middle;
        border-color: rgba(255, 255, 255, 0.12) !important;
    }

    #tabla_operativos thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: linear-gradient(180deg, #3b434f 0%, #313844 100%) !important;
        color: #f4f6f9 !important;
        font-weight: 700;
        box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.08);
        text-align: center;
    }

    #tabla_operativos tbody tr {
        background: linear-gradient(90deg, rgba(42, 50, 66, 0.96) 0%, rgba(58, 63, 88, 0.96) 100%);
        transition: background-color .2s ease, box-shadow .2s ease;
    }

    #tabla_operativos tbody tr:hover {
        background: linear-gradient(90deg, rgba(50, 60, 79, 0.98) 0%, rgba(70, 76, 105, 0.98) 100%);
    }

    #tabla_operativos tbody td {
        color: #e9eef5;
        background: transparent !important;
    }

    #tabla_operativos tbody td.bg-white,
    #tabla_operativos tbody td:first-child {
        background: linear-gradient(180deg, #f2f4f7 0%, #e4e8ee 100%) !important;
        color: #253041 !important;
        border-right: 1px solid rgba(0, 0, 0, 0.08) !important;
    }

    #tabla_operativos tbody td.bg-white strong,
    #tabla_operativos tbody td:first-child strong {
        color: #46566d !important;
        font-weight: 700;
        letter-spacing: .3px;
    }

    .fila-operativo.activa {
        background: linear-gradient(90deg, rgba(25, 92, 160, 0.18) 0%, rgba(79, 70, 229, 0.18) 100%) !important;
        box-shadow: inset 0 0 0 1px rgba(110, 168, 254, 0.18);
    }

    .fila-operativo.activa td:first-child {
        border-left: 4px solid #4ea3ff !important;
        background: linear-gradient(180deg, #f7faff 0%, #e8f2ff 100%) !important;
    }

    .card {
        border-radius: .85rem;
        overflow: hidden;
    }

    .card-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .table-responsive {
        background: linear-gradient(90deg, #263142 0%, #3a3c58 100%);
        border-radius: 0 0 .75rem .75rem;
    }

    .form-control-sm {
        min-width: 85px;
    }

    #tabla_operativos .form-control,
    #tabla_operativos .form-control-sm {
        background: rgba(21, 27, 38, 0.55) !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        color: #ffffff !important;
        border-radius: 999px;
        text-align: center;
        box-shadow: none;
    }

    #tabla_operativos .form-control::placeholder,
    #tabla_operativos .form-control-sm::placeholder {
        color: rgba(255, 255, 255, 0.45) !important;
    }

    #tabla_operativos .form-control:focus,
    #tabla_operativos .form-control-sm:focus {
        border-color: rgba(120, 178, 255, 0.9) !important;
        box-shadow: 0 0 0 .15rem rgba(0, 123, 255, 0.18) !important;
        background: rgba(21, 27, 38, 0.8) !important;
        color: #fff !important;
    }

    #tabla_operativos input[type="file"].form-control-file {
        display: block;
        width: 100%;
        color: #dbe4f0;
        font-size: .80rem;
    }

    #tabla_operativos input[type="file"]::file-selector-button {
        margin-right: .5rem;
        border: 0;
        border-radius: 999px;
        padding: .35rem .8rem;
        background: #4c84ff;
        color: #fff;
        cursor: pointer;
        transition: .2s ease;
    }

    #tabla_operativos input[type="file"]::file-selector-button:hover {
        background: #3a73ef;
    }

    .nombre-fotos {
        font-size: .75rem;
        line-height: 1.2;
        color: rgba(255, 255, 255, 0.72) !important;
        word-break: break-word;
    }

    #contador_operativos_activos {
        background: rgba(255, 255, 255, 0.95) !important;
        color: #1f2d3d !important;
        border-radius: 999px;
        padding: .45rem .8rem;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .card.card-outline.card-primary,
    .card.card-outline.card-info,
    .card.card-outline.card-secondary {
        border-top-width: 3px;
    }

    .foto-existente-item {
        display: flex;
        gap: 10px;
        align-items: center;
        padding: 8px;
        margin-bottom: 8px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.08);
    }

    .foto-existente-preview {
        width: 58px;
        height: 58px;
        border-radius: 10px;
        overflow: hidden;
        flex-shrink: 0;
        background: #1f2937;
        box-shadow: 0 2px 8px rgba(0,0,0,.20);
    }

    .foto-existente-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .foto-existente-info {
        min-width: 0;
        flex: 1;
    }

    .foto-existente-info small {
        word-break: break-word;
    }

    #tabla_operativos .custom-control-label {
        font-size: .80rem;
        cursor: pointer;
    }
</style>
@stop

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filas = document.querySelectorAll('.fila-operativo');
        const contador = document.getElementById('contador_operativos_activos');
        const form = document.getElementById('form_operativos');

        function filaActiva(fila) {
            const inputs = fila.querySelectorAll('.campo-operativo');
            const fotos = fila.querySelector('.campo-fotos');
            const checkEliminarFotos = fila.querySelectorAll('.check-eliminar-foto');

            let activa = false;
            let fotosExistentesNoEliminadas = false;

            inputs.forEach(input => {
                const valor = (input.value || '').trim();

                if (input.type === 'number') {
                    if (parseFloat(valor || 0) > 0) activa = true;
                } else {
                    if (valor !== '') activa = true;
                }
            });

            if (fotos && fotos.files && fotos.files.length > 0) {
                activa = true;
            }

            if (checkEliminarFotos.length > 0) {
                checkEliminarFotos.forEach(check => {
                    if (!check.checked) {
                        fotosExistentesNoEliminadas = true;
                    }
                });

                if (fotosExistentesNoEliminadas) {
                    activa = true;
                }
            }

            fila.classList.toggle('activa', activa);
            return activa;
        }

        function actualizarContador() {
            let total = 0;

            filas.forEach(fila => {
                if (filaActiva(fila)) total++;
            });

            contador.textContent = total + ' operativos con captura';
        }

        filas.forEach(fila => {
            const inputs = fila.querySelectorAll('.campo-operativo');
            const fotos = fila.querySelector('.campo-fotos');
            const nombreFotos = fila.querySelector('.nombre-fotos');
            const checkEliminarFotos = fila.querySelectorAll('.check-eliminar-foto');

            inputs.forEach(input => {
                input.addEventListener('input', actualizarContador);
                input.addEventListener('change', actualizarContador);
            });

            checkEliminarFotos.forEach(check => {
                check.addEventListener('change', actualizarContador);
            });

            if (fotos) {
                fotos.addEventListener('change', function () {
                    if (nombreFotos) {
                        if (fotos.files.length > 0) {
                            const nombres = Array.from(fotos.files).map(f => f.name);
                            nombreFotos.textContent = nombres.join(', ');
                        } else {
                            nombreFotos.textContent = '';
                        }
                    }

                    actualizarContador();
                });
            }
        });

        actualizarContador();

        form.addEventListener('submit', function (e) {
            let total = 0;

            filas.forEach(fila => {
                if (filaActiva(fila)) total++;
            });

            if (total === 0) {
                e.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'Sin captura',
                    text: 'Debe conservar o capturar al menos un operativo con datos o fotografías.',
                    confirmButtonText: 'Aceptar'
                });
            }
        });

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Errores en el formulario',
                html: `
                    <ul style="text-align:left; margin:0; padding-left:18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                `,
                confirmButtonText: 'Aceptar'
            });
        @endif
    });
</script>
@stop
