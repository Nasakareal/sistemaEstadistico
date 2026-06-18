@extends('adminlte::page')

@section('title', 'Cuenta de puntos')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h1 class="mb-1">{{ $cuenta->numero_licencia }}</h1>
            <p class="text-muted mb-0">{{ $cuenta->titular_nombre }}</p>
        </div>
        <a href="{{ route('licencias_puntos.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('content')
@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Revisa los datos capturados.</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@unless(auth()->user() && auth()->user()->hasRole('Superadmin'))
    <div class="alert alert-warning">
        <strong>Herramienta en desarrollo.</strong>
        Puedes consultar esta cuenta, pero por ahora los movimientos estan bloqueados.
        Solo el rol Superadmin puede aplicar descuentos o recuperaciones durante la prueba.
    </div>
@endunless

<div class="row">
    <div class="col-lg-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Saldo</h3>
            </div>
            <div class="card-body text-center">
                @php
                    $badge = ['normal' => 'success', 'advertencia' => 'warning', 'critico' => 'danger', 'agotado' => 'dark'][$cuenta->nivel_saldo] ?? 'secondary';
                @endphp
                <div class="display-4 font-weight-bold text-{{ $badge }}">{{ $cuenta->saldo_actual }} / 8</div>
                <span class="badge badge-{{ $badge }} mt-2">{{ strtoupper($cuenta->nivel_saldo) }}</span>
                <hr>
                <dl class="row text-left mb-0">
                    <dt class="col-sm-5">Estado</dt>
                    <dd class="col-sm-7">{{ $cuenta->estado_label }}</dd>
                    <dt class="col-sm-5">Recuperacion</dt>
                    <dd class="col-sm-7">{{ $cuenta->fecha_recuperacion ? $cuenta->fecha_recuperacion->format('d/m/Y') : 'Saldo completo' }}</dd>
                    <dt class="col-sm-5">Reincidencias</dt>
                    <dd class="col-sm-7">{{ $cuenta->reincidencias_cero }}</dd>
                    <dt class="col-sm-5">Expediente</dt>
                    <dd class="col-sm-7">{{ $cuenta->expediente_folio ?: 'Sin expediente' }}</dd>
                    <dt class="col-sm-5">Oficio</dt>
                    <dd class="col-sm-7">{{ $cuenta->oficio_folio ?: 'Sin oficio' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Datos de licencia</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Tipo</dt>
                    <dd class="col-sm-7">{{ $cuenta->tipo_licencia ?: 'N/A' }}</dd>
                    <dt class="col-sm-5">CURP</dt>
                    <dd class="col-sm-7">{{ $cuenta->curp ?: 'N/A' }}</dd>
                    <dt class="col-sm-5">Telefono</dt>
                    <dd class="col-sm-7">{{ $cuenta->telefono ?: 'N/A' }}</dd>
                    <dt class="col-sm-5">Emision Finanzas</dt>
                    <dd class="col-sm-7">{{ $cuenta->fecha_emision ? $cuenta->fecha_emision->format('d/m/Y') : 'N/A' }}</dd>
                    <dt class="col-sm-5">Vencimiento</dt>
                    <dd class="col-sm-7">{{ $cuenta->fecha_vencimiento ? $cuenta->fecha_vencimiento->format('d/m/Y') : 'N/A' }}</dd>
                    <dt class="col-sm-5">Consulta</dt>
                    <dd class="col-sm-7">
                        <a href="{{ route('licencias_puntos.consulta', ['numero_licencia' => $cuenta->numero_licencia]) }}" target="_blank">
                            abrir
                        </a>
                    </dd>
                </dl>
            </div>
        </div>

        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Notificaciones WhatsApp</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notificacionesWhatsapp as $notificacion)
                            @php
                                $metadata = $notificacion->metadata ?: [];
                                $ok = (bool)($metadata['ok'] ?? false);
                                $skipped = (bool)($metadata['skipped'] ?? false);
                                $badge = $ok ? 'success' : ($skipped ? 'secondary' : 'danger');
                                $estado = $ok ? 'Enviada' : ($skipped ? 'Omitida' : 'Fallida');
                            @endphp
                            <tr>
                                <td>{{ $notificacion->fecha_movimiento ? $notificacion->fecha_movimiento->format('d/m/Y H:i') : 'N/A' }}</td>
                                <td>{{ str_replace('_', ' ', $metadata['tipo_notificacion'] ?? $notificacion->referencia) }}</td>
                                <td>
                                    <span class="badge badge-{{ $badge }}">{{ $estado }}</span>
                                    @if(!empty($metadata['reason']))
                                        <small class="d-block text-muted">{{ $metadata['reason'] }}</small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Sin notificaciones registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($cuenta->titular_notificado_at)
                <div class="card-footer text-muted">
                    Agotamiento notificado al titular: {{ $cuenta->titular_notificado_at->format('d/m/Y H:i') }}
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-8">
        @can('registrar infracciones puntos licencias')
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title">Registrar infraccion (en prueba)</h3>
            </div>
            <form action="{{ route('licencias_puntos.infracciones.store', $cuenta) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Infraccion</label>
                                <select name="infraccion_id" class="form-control custom-select" required>
                                    <option value="">Seleccionar</option>
                                    @foreach($infracciones as $infraccion)
                                        <option value="{{ $infraccion->id }}" {{ old('infraccion_id') == $infraccion->id ? 'selected' : '' }}>
                                            {{ $infraccion->nombre }} (-{{ $infraccion->puntos }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fecha</label>
                                <input type="datetime-local" name="fecha_movimiento" class="form-control" value="{{ old('fecha_movimiento') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Referencia</label>
                                <input type="text" name="referencia" class="form-control" value="{{ old('referencia') }}" placeholder="Folio de infraccion">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-md-0">
                                <label>Hecho relacionado</label>
                                <input type="number" name="hecho_id" class="form-control" value="{{ old('hecho_id') }}" min="1">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group mb-0">
                                <label>Descripcion</label>
                                <input type="text" name="descripcion" class="form-control" value="{{ old('descripcion') }}">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-minus-circle"></i> Aplicar descuento
                    </button>
                </div>
            </form>
        </div>
        @endcan

        @php
            $puedeSumarPuntos = auth()->user() && auth()->user()->can('acreditar capacitacion puntos licencias');
            $puedeRecuperarPorTiempo = auth()->user() && auth()->user()->can('editar puntos licencias');
        @endphp

        @if($puedeSumarPuntos || $puedeRecuperarPorTiempo)
        <div class="row">
            @can('acreditar capacitacion puntos licencias')
            <div class="{{ $puedeRecuperarPorTiempo ? 'col-md-7' : 'col-12' }}">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title">Acreditar capacitacion</h3>
                    </div>
                    <form action="{{ route('licencias_puntos.capacitacion.store', $cuenta) }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label>Puntos</label>
                                    <input type="number" name="puntos" class="form-control" value="{{ old('puntos') }}" min="1" max="{{ \App\Models\LicenciaPuntoCuenta::SALDO_MAXIMO }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label>Fecha</label>
                                    <input type="date" name="fecha_movimiento" class="form-control" value="{{ old('fecha_movimiento') }}">
                                </div>
                                <div class="col-md-4">
                                    <label>Referencia</label>
                                    <input type="text" name="referencia" class="form-control" value="{{ old('referencia') }}" placeholder="Curso SSP">
                                </div>
                                <div class="col-12 mt-3">
                                    <label>Descripcion</label>
                                    <input type="text" name="descripcion" class="form-control" value="{{ old('descripcion') }}">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button class="btn btn-success">
                                <i class="fa-solid fa-graduation-cap"></i> Acreditar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endcan
            @can('editar puntos licencias')
            <div class="{{ $puedeSumarPuntos ? 'col-md-5' : 'col-12' }}">
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Recuperacion por tiempo</h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">Aplica cuando la licencia cumple 18 meses sin infracciones.</p>
                        <p class="mb-0 text-muted">Fecha calculada: {{ $cuenta->fecha_recuperacion ? $cuenta->fecha_recuperacion->format('d/m/Y') : 'N/A' }}</p>
                    </div>
                    <div class="card-footer text-right">
                        <form action="{{ route('licencias_puntos.recuperar_tiempo', $cuenta) }}" method="POST">
                            @csrf
                            <button class="btn btn-warning">
                                <i class="fa-solid fa-rotate-right"></i> Recuperar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endcan
        </div>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">Alertas</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Nivel</th>
                            <th>Mensaje</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alertas as $alerta)
                            <tr>
                                <td>{{ ucfirst($alerta->nivel) }}</td>
                                <td>{{ $alerta->mensaje }}</td>
                                <td>
                                    @if($alerta->atendida_at)
                                        <span class="badge badge-secondary">Atendida</span>
                                    @else
                                        <span class="badge badge-warning">Abierta</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Sin alertas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Historial de movimientos</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Detalle</th>
                            <th>Puntos</th>
                            <th>Saldo</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movimientos as $movimiento)
                            <tr>
                                <td>{{ $movimiento->fecha_movimiento ? $movimiento->fecha_movimiento->format('d/m/Y H:i') : 'N/A' }}</td>
                                <td>{{ str_replace('_', ' ', ucfirst($movimiento->tipo)) }}</td>
                                <td>
                                    <strong>{{ optional($movimiento->infraccion)->nombre ?: $movimiento->referencia }}</strong>
                                    <small class="d-block text-muted">{{ $movimiento->descripcion }}</small>
                                </td>
                                <td>
                                    @if($movimiento->puntos > 0)
                                        <span class="text-success">+{{ $movimiento->puntos }}</span>
                                    @elseif($movimiento->puntos < 0)
                                        <span class="text-danger">{{ $movimiento->puntos }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td>{{ $movimiento->saldo_anterior }} -> {{ $movimiento->saldo_nuevo }}</td>
                                <td>{{ optional($movimiento->usuario)->name ?: 'Sistema' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Sin movimientos.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $movimientos->links() }}
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    select.form-control,
    select.custom-select {
        background-color: #12263c;
        color: #f8fafc;
        border-color: rgba(125, 178, 225, .45);
    }

    select.form-control:focus,
    select.custom-select:focus {
        background-color: #12263c;
        color: #ffffff;
        border-color: #64b5f6;
        box-shadow: 0 0 0 .2rem rgba(100, 181, 246, .18);
    }

    select.form-control option,
    select.form-control optgroup,
    select.custom-select option,
    select.custom-select optgroup {
        background-color: #12263c;
        color: #f8fafc;
    }

    select.form-control option:checked,
    select.custom-select option:checked {
        background-color: #2563d8;
        color: #ffffff;
    }

    select.form-control option:disabled,
    select.custom-select option:disabled {
        color: #94a3b8;
    }

    .table td {
        vertical-align: middle;
    }
</style>
@stop

@section('js')
<script>
@if (session('success'))
Swal.fire({ icon: 'success', title: '{{ session('success') }}', timer: 2500, showConfirmButton: false });
@endif

@if (session('error'))
Swal.fire({ icon: 'error', title: '{{ session('error') }}', timer: 3500, showConfirmButton: false });
@endif
</script>
@stop
