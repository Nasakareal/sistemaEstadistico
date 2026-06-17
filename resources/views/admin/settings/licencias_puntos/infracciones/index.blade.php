@extends('adminlte::page')

@section('title', 'Infracciones de puntos')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h1 class="mb-1">Infracciones de puntos</h1>
            <p class="text-muted mb-0">Catalogo de infracciones y puntos que se descuentan a la licencia.</p>
        </div>
        <a href="{{ route('settings.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Configuraciones
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
    @php $puedeCrearInfracciones = auth()->user() && auth()->user()->can('crear catalogo infracciones puntos licencias'); @endphp

    @can('crear catalogo infracciones puntos licencias')
        <div class="col-lg-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Agregar infraccion</h3>
                </div>
                <form method="POST" action="{{ route('settings.licencias_puntos.infracciones.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" maxlength="150" required>
                        </div>
                        <div class="form-group">
                            <label>Codigo</label>
                            <input type="text" name="codigo" class="form-control" value="{{ old('codigo') }}" maxlength="50" placeholder="Se genera automaticamente si lo dejas vacio">
                        </div>
                        <div class="form-group">
                            <label>Puntos a descontar</label>
                            <input type="number" name="puntos" class="form-control" value="{{ old('puntos', 1) }}" min="1" max="8" required>
                        </div>
                        <div class="form-group">
                            <label>Descripcion</label>
                            <textarea name="descripcion" class="form-control" rows="4">{{ old('descripcion') }}</textarea>
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
                            <th>Infraccion</th>
                            <th>Codigo</th>
                            <th>Puntos</th>
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
                                </td>
                                <td><code>{{ $infraccion->codigo }}</code></td>
                                <td><span class="badge badge-danger">-{{ $infraccion->puntos }}</span></td>
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
                                <td colspan="6" class="text-center text-muted py-4">No hay infracciones registradas.</td>
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
                            <h5 class="modal-title">Editar infraccion</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nombre</label>
                                        <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $infraccion->nombre) }}" maxlength="150" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Codigo</label>
                                        <input type="text" name="codigo" class="form-control" value="{{ old('codigo', $infraccion->codigo) }}" maxlength="50">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Puntos</label>
                                        <input type="number" name="puntos" class="form-control" value="{{ old('puntos', $infraccion->puntos) }}" min="1" max="8" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Descripcion</label>
                                        <textarea name="descripcion" class="form-control" rows="4">{{ old('descripcion', $infraccion->descripcion) }}</textarea>
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
@stop

@section('css')
<style>
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
