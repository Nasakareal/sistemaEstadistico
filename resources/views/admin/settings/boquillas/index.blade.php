@extends('adminlte::page')

@section('title', 'Control de boquillas')

@section('content_header')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
        <div>
            <h1 class="mb-1"><i class="fa-solid fa-boxes-stacked mr-2 text-info"></i>Control de boquillas</h1>
            <p class="text-muted mb-0">Registra las entregas recibidas y cualquier boquilla perdida durante los operativos de alcoholimetría.</p>
        </div>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-light mt-3 mt-md-0">
            <i class="fas fa-arrow-left mr-1"></i> Configuraciones
        </a>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar"><span>&times;</span></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>No se pudo guardar el registro.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card card-outline card-info shadow-sm">
                <div class="card-header border-0">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-plus-circle mr-1"></i> Nueva entrega</h3>
                </div>
                <form method="POST" action="{{ route('settings.boquillas.store') }}">
                    @csrf
                    <div class="card-body pt-2">
                        <div class="form-group">
                            <label for="fecha_recepcion">Fecha de recepción</label>
                            <input type="date" id="fecha_recepcion" name="fecha_recepcion"
                                   class="form-control @error('fecha_recepcion') is-invalid @enderror"
                                   value="{{ old('fecha_recepcion', now()->toDateString()) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="cantidad">Cantidad recibida</label>
                            <div class="input-group">
                                <input type="number" id="cantidad" name="cantidad" min="1" max="1000000" step="1"
                                       class="form-control @error('cantidad') is-invalid @enderror"
                                       value="{{ old('cantidad') }}" placeholder="Ej. 500" required>
                                <div class="input-group-append"><span class="input-group-text">boquillas</span></div>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label for="observaciones">Observaciones <small class="text-muted">(opcional)</small></label>
                            <textarea id="observaciones" name="observaciones" rows="3" maxlength="500"
                                      class="form-control @error('observaciones') is-invalid @enderror"
                                      placeholder="Ej. Entrega semanal, oficio o folio">{{ old('observaciones') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <button type="submit" class="btn btn-info btn-block font-weight-bold">
                            <i class="fas fa-save mr-1"></i> Registrar dotación
                        </button>
                    </div>
                </form>
            </div>

            <div class="callout callout-info">
                <h5 class="font-weight-bold">¿Cómo se captura?</h5>
                <p class="mb-0">Registra una entrada cada vez que reciban boquillas. Si llegan en semanas distintas, cada entrega conserva su propia fecha y cantidad.</p>
            </div>

            <div class="card card-outline card-warning shadow-sm">
                <div class="card-header border-0">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-triangle-exclamation mr-1"></i> Boquillas perdidas</h3>
                </div>
                <form method="POST" action="{{ url('/admin/settings/boquillas') }}">
                    @csrf
                    <input type="hidden" name="operacion_perdida" value="crear">
                    <div class="card-body pt-2">
                        <div class="form-group">
                            <label for="fecha_perdida">Fecha de la pérdida</label>
                            <input type="date" id="fecha_perdida" name="fecha_perdida"
                                   class="form-control @error('fecha_perdida') is-invalid @enderror"
                                   value="{{ old('fecha_perdida', now()->toDateString()) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="cantidad_perdida">Cantidad perdida</label>
                            <div class="input-group">
                                <input type="number" id="cantidad_perdida" name="cantidad" min="1" max="1000000" step="1"
                                       class="form-control" value="{{ old('fecha_perdida') ? old('cantidad') : '' }}"
                                       placeholder="Ej. 3" required>
                                <div class="input-group-append"><span class="input-group-text">boquillas</span></div>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label for="observaciones_perdida">Motivo u observaciones <small class="text-muted">(opcional)</small></label>
                            <textarea id="observaciones_perdida" name="observaciones" rows="3" maxlength="500"
                                      class="form-control"
                                      placeholder="Ej. Extravío durante el operativo">{{ old('fecha_perdida') ? old('observaciones') : '' }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <button type="submit" class="btn btn-warning btn-block font-weight-bold">
                            <i class="fas fa-save mr-1"></i> Registrar pérdida
                        </button>
                    </div>
                </form>
            </div>

            <div class="callout callout-warning">
                <h5 class="font-weight-bold">Conciliación mensual</h5>
                <p class="mb-0">Las pérdidas quedan separadas y auditables. En el formato mensual se suman al total conciliado de pruebas aptas para que coincida con las salidas de boquillas.</p>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="small-box bg-info mb-0">
                        <div class="inner">
                            <h3>{{ number_format($totalRecibidasMes) }}</h3>
                            <p>Recibidas en {{ $tituloMes }}</p>
                        </div>
                        <div class="icon"><i class="fa-solid fa-box-open"></i></div>
                    </div>
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <div class="small-box bg-warning mb-0">
                        <div class="inner">
                            <h3>{{ number_format($resumenMensual['boquillas']['perdidas']) }}</h3>
                            <p>Perdidas en {{ $tituloMes }}</p>
                        </div>
                        <div class="icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    </div>
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <div class="card h-100 mb-0 shadow-sm">
                        <div class="card-body d-flex align-items-center justify-content-between py-3">
                            <a href="{{ route('settings.boquillas.index', ['mes' => $mesAnterior]) }}" class="btn btn-outline-info" title="Mes anterior">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <div class="text-center px-2">
                                <small class="text-muted text-uppercase font-weight-bold">Mes consultado</small>
                                <div class="h5 mb-0 font-weight-bold">{{ $tituloMes }}</div>
                            </div>
                            <a href="{{ route('settings.boquillas.index', ['mes' => $mesSiguiente]) }}" class="btn btn-outline-info" title="Mes siguiente">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-boxes-stacked"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Existencia inicial</span>
                            <span class="info-box-number">{{ number_format($resumenMensual['boquillas']['existencia_inicial']) }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-primary"><i class="fas fa-vial"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Salidas totales</span>
                            <span class="info-box-number">{{ number_format($resumenMensual['boquillas']['salidas_totales']) }}</span>
                            <small>{{ number_format($resumenMensual['pruebas_reales']) }} pruebas + {{ number_format($resumenMensual['boquillas']['perdidas']) }} pérdidas</small>
                            <small class="d-block">
                                {{ number_format($resumenMensual['boquillas']['salidas_inventario_controlado']) }} controladas +
                                {{ number_format($resumenMensual['boquillas']['externas_no_controladas']) }} externas
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-success"><i class="fas fa-warehouse"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Existencia final</span>
                            <span class="info-box-number">{{ number_format($resumenMensual['boquillas']['existencia_final']) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($resumenMensual['boquillas']['externas_no_controladas'] > 0)
                <div class="alert alert-info">
                    <i class="fas fa-circle-info mr-1"></i>
                    Se registraron <strong>{{ number_format($resumenMensual['boquillas']['externas_no_controladas']) }}</strong>
                    salidas adicionales después de agotarse las boquillas proporcionadas. Los recuentos se conservaron
                    completos, sin generar saldo negativo ni consumir retroactivamente entregas posteriores.
                </div>
            @endif

            <div class="card card-outline card-secondary shadow-sm">
                <div class="card-header border-0">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1"></i> Entregas registradas</h3>
                    <div class="card-tools">
                        <form method="GET" action="{{ route('settings.boquillas.index') }}" class="form-inline">
                            <input type="month" name="mes" value="{{ $mes }}" class="form-control form-control-sm mr-1" aria-label="Mes a consultar">
                            <button class="btn btn-sm btn-secondary" type="submit">Consultar</button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width: 150px;">Fecha</th>
                                    <th style="min-width: 150px;">Cantidad</th>
                                    <th style="min-width: 220px;">Observaciones</th>
                                    <th>Registró</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dotaciones as $dotacion)
                                    @php $formId = 'editar-dotacion-' . $dotacion->id; @endphp
                                    <tr>
                                        <td>
                                            <form id="{{ $formId }}" method="POST" action="{{ route('settings.boquillas.update', $dotacion) }}">
                                                @csrf
                                                @method('PUT')
                                            </form>
                                            <input form="{{ $formId }}" type="date" name="fecha_recepcion"
                                                   value="{{ $dotacion->fecha_recepcion->toDateString() }}"
                                                   class="form-control form-control-sm" required>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input form="{{ $formId }}" type="number" name="cantidad" min="1" max="1000000"
                                                       value="{{ $dotacion->cantidad }}" class="form-control" required>
                                                <div class="input-group-append"><span class="input-group-text">pzas.</span></div>
                                            </div>
                                        </td>
                                        <td>
                                            <input form="{{ $formId }}" type="text" name="observaciones" maxlength="500"
                                                   value="{{ $dotacion->observaciones }}" class="form-control form-control-sm"
                                                   placeholder="Sin observaciones">
                                        </td>
                                        <td class="align-middle">
                                            <small>{{ optional($dotacion->creador)->name ?: 'Usuario no disponible' }}</small>
                                        </td>
                                        <td class="align-middle text-right text-nowrap">
                                            <button form="{{ $formId }}" type="submit" class="btn btn-sm btn-outline-info" title="Guardar cambios">
                                                <i class="fas fa-save"></i>
                                            </button>
                                            <form method="POST" action="{{ route('settings.boquillas.destroy', $dotacion) }}" class="d-inline"
                                                  onsubmit="return confirm('¿Eliminar esta entrega del cálculo mensual?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fa-regular fa-folder-open fa-2x mb-2 d-block"></i>
                                            No hay entregas registradas en {{ $tituloMes }}.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td>Total recibido en el mes</td>
                                    <td>{{ number_format($totalRecibidasMes) }} boquillas</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-warning shadow-sm">
                <div class="card-header border-0">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1"></i> Pérdidas registradas</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width: 150px;">Fecha</th>
                                    <th style="min-width: 150px;">Cantidad</th>
                                    <th style="min-width: 220px;">Motivo u observaciones</th>
                                    <th>Registró</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($perdidas as $perdida)
                                    @php $formId = 'editar-perdida-' . $perdida->id; @endphp
                                    <tr>
                                        <td>
                                            <form id="{{ $formId }}" method="POST" action="{{ url('/admin/settings/boquillas') }}">
                                                @csrf
                                                <input type="hidden" name="operacion_perdida" value="actualizar">
                                                <input type="hidden" name="perdida_id" value="{{ $perdida->id }}">
                                            </form>
                                            <input form="{{ $formId }}" type="date" name="fecha_perdida"
                                                   value="{{ $perdida->fecha_perdida->toDateString() }}"
                                                   class="form-control form-control-sm" required>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input form="{{ $formId }}" type="number" name="cantidad" min="1" max="1000000"
                                                       value="{{ $perdida->cantidad }}" class="form-control" required>
                                                <div class="input-group-append"><span class="input-group-text">pzas.</span></div>
                                            </div>
                                        </td>
                                        <td>
                                            <input form="{{ $formId }}" type="text" name="observaciones" maxlength="500"
                                                   value="{{ $perdida->observaciones }}" class="form-control form-control-sm"
                                                   placeholder="Sin observaciones">
                                        </td>
                                        <td class="align-middle">
                                            <small>{{ optional($perdida->creador)->name ?: 'Usuario no disponible' }}</small>
                                        </td>
                                        <td class="align-middle text-right text-nowrap">
                                            <button form="{{ $formId }}" type="submit" class="btn btn-sm btn-outline-warning" title="Guardar cambios">
                                                <i class="fas fa-save"></i>
                                            </button>
                                            <form method="POST" action="{{ url('/admin/settings/boquillas') }}" class="d-inline"
                                                  onsubmit="return confirm('¿Eliminar esta pérdida del cálculo mensual?');">
                                                @csrf
                                                <input type="hidden" name="operacion_perdida" value="eliminar">
                                                <input type="hidden" name="perdida_id" value="{{ $perdida->id }}">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            No hay boquillas perdidas registradas en {{ $tituloMes }}.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td>Total perdido en el mes</td>
                                    <td>{{ number_format($resumenMensual['boquillas']['perdidas']) }} boquillas</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="alert alert-secondary">
                <i class="fas fa-circle-info mr-1"></i>
                La existencia final controla únicamente las boquillas proporcionadas y nunca baja de cero.
                Los recuentos adicionales se conservan como consumo externo no controlado, sin necesidad de registrar las boquillas compradas.
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .small-box .icon > i { font-size: 64px; top: 12px; }
        .table td, .table th { vertical-align: middle; }
        @media (max-width: 767.98px) {
            .card-tools { float: none; margin-top: 12px; }
            .card-tools .form-inline { flex-wrap: nowrap; }
        }
    </style>
@stop
