@extends('adminlte::page')

@section('title', 'Catalogo de sanciones')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h1 class="mb-1">Catalogo de sanciones</h1>
            <p class="text-muted mb-0">Catalogo de sanciones, puntos, retiro de vehiculo y fundamento legal.</p>
        </div>
        <a href="{{ route('settings.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Configuraciones
        </a>
    </div>
@stop

@section('content')
<div class="licencias-puntos-infracciones-page">
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
    @php $puedeCrearInfracciones = auth()->user() && auth()->user()->can('crear catalogo infracciones puntos licencias'); @endphp

    @can('crear catalogo infracciones puntos licencias')
        <div class="col-lg-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Agregar penalización</h3>
                </div>
                <form method="POST" action="{{ route('settings.licencias_puntos.infracciones.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label>Texto humano para operativo</label>
                            <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" maxlength="150" required>
                            <small class="form-text text-muted">Debe decir la conducta en palabras simples. Ejemplo: No usar cinturon.</small>
                        </div>
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label>Articulo</label>
                                    <input type="text" name="articulo" class="form-control" value="{{ old('articulo') }}" maxlength="30" placeholder="419">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label>Fraccion</label>
                                    <input type="text" name="fraccion" class="form-control" value="{{ old('fraccion') }}" maxlength="30" placeholder="I">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label>Inciso</label>
                                    <input type="text" name="inciso" class="form-control" value="{{ old('inciso') }}" maxlength="30" placeholder="a">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Codigo interno</label>
                            <input type="text" name="codigo" class="form-control" value="{{ old('codigo') }}" maxlength="50" placeholder="Se genera automaticamente si lo dejas vacio">
                        </div>
                        <div class="form-group">
                            <label>Puntos a descontar</label>
                            <input type="number" name="puntos" class="form-control" value="{{ old('puntos', 1) }}" min="0" max="{{ \App\Models\LicenciaPuntoCuenta::SALDO_MAXIMO }}" required>
                            <small class="form-text text-muted">Usa 0 cuando la sancion no descuente puntos.</small>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>UMA minima</label>
                                    <input type="number" name="multa_uma_min" class="form-control" value="{{ old('multa_uma_min') }}" min="0" max="9999">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>UMA maxima</label>
                                    <input type="number" name="multa_uma_max" class="form-control" value="{{ old('multa_uma_max') }}" min="0" max="9999">
                                </div>
                            </div>
                        </div>
                        <div class="icheck-warning mb-3">
                            <input type="checkbox" name="retencion_vehiculo" value="1" id="retencion_vehiculo_nueva" {{ old('retencion_vehiculo') ? 'checked' : '' }}>
                            <label for="retencion_vehiculo_nueva">Retiro de vehiculo / corralon</label>
                        </div>
                        <div class="form-group">
                            <label>Descripcion de cuando aplica</label>
                            <textarea name="descripcion" class="form-control" rows="4" placeholder="Explica en que caso se selecciona esta penalizacion">{{ old('descripcion') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Fundamento legal</label>
                            <textarea name="fundamento_legal" class="form-control" rows="4" placeholder="Se genera con articulo, fraccion, inciso, UMAS, puntos y retiro si lo dejas vacio">{{ old('fundamento_legal') }}</textarea>
                            <small class="form-text text-muted">Este texto tambien se envia por WhatsApp cuando hay descuento de puntos.</small>
                        </div>
                        <div class="icheck-primary">
                            <input type="checkbox" name="activa" value="1" id="activa_nueva" {{ old('activa', '1') ? 'checked' : '' }}>
                            <label for="activa_nueva">Activa para nuevas capturas</label>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    <div class="{{ $puedeCrearInfracciones ? 'col-lg-8' : 'col-12' }}">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Catalogo vigente</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Penalización</th>
                            <th>Base legal</th>
                            <th>Codigo</th>
                            <th>Sanciones</th>
                            <th>Estado</th>
                            <th>Uso</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($infracciones as $infraccion)
                            <tr>
                                <td>
                                    <strong>{{ $infraccion->nombre }}</strong>
                                    @if($infraccion->descripcion)
                                        <small class="d-block text-muted">{{ $infraccion->descripcion }}</small>
                                    @endif
                                    @if($infraccion->fundamento_legal)
                                        <small class="d-block text-info">{{ $infraccion->fundamento_legal }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($infraccion->referencia_legal_corta)
                                        <strong>{{ $infraccion->referencia_legal_corta }}</strong>
                                    @else
                                        <span class="text-muted">Sin base</span>
                                    @endif
                                    @if($infraccion->multa_uma_texto)
                                        <small class="d-block text-muted">{{ $infraccion->multa_uma_texto }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $infraccion->codigo }}</code></td>
                                <td>
                                    @if((int) $infraccion->puntos > 0)
                                        <span class="badge badge-danger">-{{ $infraccion->puntos }} pts</span>
                                    @else
                                        <span class="badge badge-secondary">0 pts</span>
                                    @endif
                                    @if($infraccion->retencion_vehiculo)
                                        <span class="badge badge-warning">Corralon</span>
                                    @endif
                                </td>
                                <td>
                                    @if($infraccion->activa)
                                        <span class="badge badge-success">Activa</span>
                                    @else
                                        <span class="badge badge-secondary">Inactiva</span>
                                    @endif
                                </td>
                                <td>{{ number_format($infraccion->movimientos_count) }} movimientos</td>
                                <td class="text-right">
                                    @can('editar catalogo infracciones puntos licencias')
                                        <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#editar-infraccion-{{ $infraccion->id }}">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                    @else
                                        <span class="text-muted">Sin permiso</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No hay penalizaciones registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@can('editar catalogo infracciones puntos licencias')
    @foreach($infracciones as $infraccion)
        <div class="modal fade" id="editar-infraccion-{{ $infraccion->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form method="POST" action="{{ route('settings.licencias_puntos.infracciones.update', $infraccion) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Editar penalización</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Texto humano para operativo</label>
                                        <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $infraccion->nombre) }}" maxlength="150" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Articulo</label>
                                        <input type="text" name="articulo" class="form-control" value="{{ old('articulo', $infraccion->articulo) }}" maxlength="30">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Fraccion</label>
                                        <input type="text" name="fraccion" class="form-control" value="{{ old('fraccion', $infraccion->fraccion) }}" maxlength="30">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Inciso</label>
                                        <input type="text" name="inciso" class="form-control" value="{{ old('inciso', $infraccion->inciso) }}" maxlength="30">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Codigo interno</label>
                                        <input type="text" name="codigo" class="form-control" value="{{ old('codigo', $infraccion->codigo) }}" maxlength="50">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Puntos</label>
                                        <input type="number" name="puntos" class="form-control" value="{{ old('puntos', $infraccion->puntos) }}" min="0" max="{{ \App\Models\LicenciaPuntoCuenta::SALDO_MAXIMO }}" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>UMA minima</label>
                                        <input type="number" name="multa_uma_min" class="form-control" value="{{ old('multa_uma_min', $infraccion->multa_uma_min) }}" min="0" max="9999">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>UMA maxima</label>
                                        <input type="number" name="multa_uma_max" class="form-control" value="{{ old('multa_uma_max', $infraccion->multa_uma_max) }}" min="0" max="9999">
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-center">
                                    <div class="icheck-warning mt-3">
                                        <input type="checkbox" name="retencion_vehiculo" value="1" id="retencion_vehiculo_{{ $infraccion->id }}" {{ old('retencion_vehiculo', $infraccion->retencion_vehiculo) ? 'checked' : '' }}>
                                        <label for="retencion_vehiculo_{{ $infraccion->id }}">Retiro de vehiculo / corralon</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Descripcion de cuando aplica</label>
                                        <textarea name="descripcion" class="form-control" rows="4">{{ old('descripcion', $infraccion->descripcion) }}</textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Fundamento legal</label>
                                        <textarea name="fundamento_legal" class="form-control" rows="4" placeholder="Se genera automaticamente si lo dejas vacio">{{ old('fundamento_legal', $infraccion->fundamento_legal) }}</textarea>
                                        <small class="form-text text-muted">Articulo, fraccion, inciso, multa en UMAS, puntos y retiro. Este texto tambien se envia por WhatsApp cuando hay descuento.</small>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="icheck-primary">
                                        <input type="checkbox" name="activa" value="1" id="activa_{{ $infraccion->id }}" {{ old('activa', $infraccion->activa) ? 'checked' : '' }}>
                                        <label for="activa_{{ $infraccion->id }}">Activa para nuevas capturas</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button class="btn btn-success">
                                <i class="fa-regular fa-floppy-disk"></i> Actualizar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endcan
</div>
@stop

@section('css')
<style>
    .licencias-puntos-infracciones-page .modal-content {
        background-color: #121a28;
        color: #f8fafc;
        border: 1px solid rgba(148, 163, 184, .35);
        box-shadow: 0 24px 80px rgba(0, 0, 0, .7);
    }

    .licencias-puntos-infracciones-page .modal-header,
    .licencias-puntos-infracciones-page .modal-footer {
        background-color: #172235;
        border-color: rgba(148, 163, 184, .25);
    }

    .licencias-puntos-infracciones-page .modal-body {
        background-color: #121a28;
    }

    .licencias-puntos-infracciones-page .modal label {
        color: #e5edf7;
    }

    .licencias-puntos-infracciones-page .modal .form-control {
        background-color: #0b1220;
        color: #f8fafc;
        border-color: rgba(148, 163, 184, .4);
    }

    .licencias-puntos-infracciones-page .modal .form-control:focus {
        background-color: #0b1220;
        color: #ffffff;
        border-color: #38bdf8;
        box-shadow: 0 0 0 .2rem rgba(56, 189, 248, .18);
    }

    .licencias-puntos-infracciones-page .modal .close {
        color: #f8fafc;
        opacity: .85;
        text-shadow: none;
    }

    .modal-backdrop.show {
        opacity: .78;
    }

    .table td {
        vertical-align: middle;
    }

    code {
        color: #d6e8ff;
        background: rgba(255,255,255,.08);
        padding: 3px 6px;
        border-radius: 6px;
    }
</style>
@stop

@section('js')
<script>
@if (session('success'))
Swal.fire({ icon: 'success', title: '{{ session('success') }}', timer: 2500, showConfirmButton: false });
@endif
</script>
@stop
