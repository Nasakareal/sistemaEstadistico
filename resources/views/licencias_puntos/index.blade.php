@extends('adminlte::page')

@section('title', 'Puntos de licencia')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h1 class="mb-1">Sistema de puntos de licencia</h1>
            <p class="text-muted mb-0">Resta de puntos, alertas y recuperacion.</p>
        </div>
        <a href="{{ route('licencias_puntos.consulta') }}" class="btn btn-outline-info" target="_blank">
            <i class="fa-solid fa-magnifying-glass"></i> Consulta ciudadana
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

<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fa-solid fa-id-card"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Con movimientos</span>
                <span class="info-box-number">{{ number_format($stats['total']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Advertencia</span>
                <span class="info-box-number">{{ number_format($stats['advertencia']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-danger">
            <span class="info-box-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Criticas</span>
                <span class="info-box-number">{{ number_format($stats['criticas']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-dark">
            <span class="info-box-icon"><i class="fa-solid fa-file-signature"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Agotadas</span>
                <span class="info-box-number">{{ number_format($stats['agotadas']) }}</span>
            </div>
        </div>
    </div>
</div>

@can('registrar infracciones puntos licencias')
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Registrar infraccion (en prueba)</h3>
    </div>
    <form action="{{ route('licencias_puntos.store') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Conductor relacionado</label>
                        <select name="conductor_id" id="conductor_id" class="form-control custom-select">
                            <option value="">Sin relacion</option>
                            @foreach($conductores as $conductor)
                                <option value="{{ $conductor->id }}"
                                    data-nombre="{{ $conductor->nombre }}"
                                    data-licencia="{{ $conductor->numero_licencia }}"
                                    data-tipo="{{ $conductor->tipo_licencia }}"
                                    data-telefono="{{ $conductor->telefono }}"
                                    {{ old('conductor_id', request('conductor_id')) == $conductor->id ? 'selected' : '' }}>
                                    {{ $conductor->nombre }} - {{ $conductor->numero_licencia }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Numero de licencia</label>
                        <input type="text" name="numero_licencia" id="numero_licencia" class="form-control" value="{{ old('numero_licencia', request('numero_licencia')) }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Titular</label>
                        <input type="text" name="titular_nombre" id="titular_nombre" class="form-control" value="{{ old('titular_nombre', request('titular_nombre')) }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Tipo</label>
                        <input type="text" name="tipo_licencia" id="tipo_licencia" class="form-control" value="{{ old('tipo_licencia', request('tipo_licencia')) }}" placeholder="Automovilista, chofer...">
                    </div>
                </div>
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Fecha de infraccion</label>
                        <input type="datetime-local" name="fecha_movimiento" class="form-control" value="{{ old('fecha_movimiento') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Referencia</label>
                        <input type="text" name="referencia" class="form-control" value="{{ old('referencia', request('referencia')) }}" placeholder="Folio de infraccion">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Hecho relacionado</label>
                        <input type="number" name="hecho_id" class="form-control" value="{{ old('hecho_id', request('hecho_id')) }}" min="1">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-md-0">
                        <label>CURP</label>
                        <input type="text" name="curp" class="form-control" value="{{ old('curp', request('curp')) }}" maxlength="18">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-md-0">
                        <label>Telefono</label>
                        <input type="text" name="telefono" id="telefono" class="form-control" value="{{ old('telefono', request('telefono')) }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>Descripcion</label>
                        <input type="text" name="descripcion" class="form-control" value="{{ old('descripcion') }}">
                    </div>
                </div>
                <div class="col-md-6 mt-3 mt-md-0">
                    <div class="form-group mb-0">
                        <label>Observaciones internas</label>
                        <input type="text" name="observaciones" class="form-control" value="{{ old('observaciones') }}">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-right">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-minus-circle"></i> Aplicar descuento
            </button>
        </div>
    </form>
</div>
@endcan

<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title">Licencias con movimientos</h3>
        <div class="card-tools">
            <form method="GET" class="form-inline">
                <input type="text" name="buscar" class="form-control form-control-sm mr-2" value="{{ request('buscar') }}" placeholder="Licencia, titular o CURP">
                <select name="estado" class="form-control form-control-sm custom-select mr-2">
                    <option value="">Todos los estados</option>
                    @foreach([
                        'vigente' => 'Vigente',
                        'procedimiento_administrativo' => 'Procedimiento',
                        'suspendida' => 'Suspendida',
                        'cancelada' => 'Cancelada',
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ request('estado') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-info">
                    <i class="fa-solid fa-filter"></i>
                </button>
            </form>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped mb-0">
            <thead>
                <tr>
                    <th>Licencia</th>
                    <th>Titular</th>
                    <th>Saldo</th>
                    <th>Estado</th>
                    <th>Recuperacion</th>
                    <th>Alertas</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cuentas as $cuenta)
                    <tr>
                        <td>
                            <strong>{{ $cuenta->numero_licencia }}</strong>
                            <small class="d-block text-muted">{{ $cuenta->tipo_licencia ?: 'Sin tipo' }}</small>
                        </td>
                        <td>
                            {{ $cuenta->titular_nombre }}
                            @if($cuenta->conductor)
                                <small class="d-block text-muted">Conductor #{{ $cuenta->conductor->id }}</small>
                            @endif
                        </td>
                        <td>
                            @php
                                $badge = ['normal' => 'success', 'advertencia' => 'warning', 'critico' => 'danger', 'agotado' => 'dark'][$cuenta->nivel_saldo] ?? 'secondary';
                            @endphp
                            <span class="badge badge-{{ $badge }} badge-lg">{{ $cuenta->saldo_actual }} / 8</span>
                        </td>
                        <td>{{ $cuenta->estado_label }}</td>
                        <td>{{ $cuenta->fecha_recuperacion ? $cuenta->fecha_recuperacion->format('d/m/Y') : 'Saldo completo' }}</td>
                        <td>
                            @if($cuenta->alertas_abiertas_count)
                                <span class="badge badge-warning">{{ $cuenta->alertas_abiertas_count }} abiertas</span>
                            @else
                                <span class="badge badge-secondary">Sin alertas</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('licencias_puntos.show', $cuenta) }}" class="btn btn-sm btn-info">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No hay licencias con movimientos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $cuentas->links() }}
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
$(function () {
    $('#conductor_id').on('change', function () {
        const option = $(this).find(':selected');
        if (!option.val()) {
            return;
        }

        $('#titular_nombre').val(option.data('nombre') || $('#titular_nombre').val());
        $('#numero_licencia').val(option.data('licencia') || $('#numero_licencia').val());
        $('#tipo_licencia').val(option.data('tipo') || $('#tipo_licencia').val());
        $('#telefono').val(option.data('telefono') || $('#telefono').val());
    }).trigger('change');
});

@if (session('success'))
Swal.fire({ icon: 'success', title: '{{ session('success') }}', timer: 2500, showConfirmButton: false });
@endif

@if (session('error'))
Swal.fire({ icon: 'error', title: '{{ session('error') }}', timer: 3500, showConfirmButton: false });
@endif
</script>
@stop
