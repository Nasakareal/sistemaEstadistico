@extends('adminlte::page')

@section('title', 'Tutoriales')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-0">Tutoriales</h1>
            <small class="text-muted">Videos publicados para la app movil y web.</small>
        </div>
        <a href="{{ route('settings.tutoriales.create') }}" class="btn btn-primary mt-2 mt-md-0">
            <i class="fa-solid fa-plus"></i> Nuevo tutorial
        </a>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Filtros</h3>
        </div>
        <form method="GET" action="{{ route('settings.tutoriales.index') }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label for="q">Buscar</label>
                        <input type="text"
                               id="q"
                               name="q"
                               value="{{ $filtros['q'] ?? '' }}"
                               class="form-control"
                               placeholder="Titulo, descripcion o link">
                    </div>
                    <div class="col-md-3">
                        <label for="categoria_id">Categoria</label>
                        <select id="categoria_id" name="categoria_id" class="form-control">
                            <option value="">Todas</option>
                            @foreach($categorias as $categoria)
                                <option value="{{ $categoria->id }}" {{ (string)($filtros['categoria_id'] ?? '') === (string)$categoria->id ? 'selected' : '' }}>
                                    {{ $categoria->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="plataforma">Plataforma</label>
                        <select id="plataforma" name="plataforma" class="form-control">
                            <option value="">Todas</option>
                            @foreach($plataformas as $value => $label)
                                <option value="{{ $value }}" {{ ($filtros['plataforma'] ?? '') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="unidad_id">Unidad</label>
                        <select id="unidad_id" name="unidad_id" class="form-control">
                            <option value="">Todas</option>
                            @foreach($unidades as $unidad)
                                <option value="{{ $unidad->id }}" {{ (string)($filtros['unidad_id'] ?? '') === (string)$unidad->id ? 'selected' : '' }}>
                                    {{ $unidad->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" class="form-control">
                            <option value="">Todos</option>
                            <option value="activo" {{ ($filtros['estado'] ?? '') === 'activo' ? 'selected' : '' }}>Activos</option>
                            <option value="inactivo" {{ ($filtros['estado'] ?? '') === 'inactivo' ? 'selected' : '' }}>Inactivos</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <a href="{{ route('settings.tutoriales.index') }}" class="btn btn-default mr-2">
                    Limpiar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title">Listado</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="14%">Categoria</th>
                        <th width="24%">Tutorial</th>
                        <th width="16%">Unidad</th>
                        <th width="12%">Plataforma</th>
                        <th width="10%">Estado</th>
                        <th width="8%">Orden</th>
                        <th width="10%">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tutoriales as $tutorial)
                        <tr>
                            <td>{{ $tutorial->id }}</td>
                            <td>{{ $tutorial->categoria?->nombre ?? 'General' }}</td>
                            <td>
                                <strong>{{ $tutorial->titulo }}</strong>
                                @if($tutorial->descripcion)
                                    <div class="text-muted small">{{ \Illuminate\Support\Str::limit($tutorial->descripcion, 90) }}</div>
                                @endif
                                <a href="{{ $tutorial->youtube_url }}" target="_blank" rel="noopener" class="small">
                                    Ver YouTube
                                </a>
                            </td>
                            <td>
                                @if($tutorial->unidad)
                                    <span class="badge badge-info">{{ $tutorial->unidad->nombre }}</span>
                                @else
                                    <span class="badge badge-light">Todas las unidades</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-secondary">
                                    {{ $plataformas[$tutorial->plataforma] ?? $tutorial->plataforma }}
                                </span>
                            </td>
                            <td>
                                @if($tutorial->activo)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-danger">Inactivo</span>
                                @endif
                            </td>
                            <td>{{ $tutorial->orden }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('settings.tutoriales.edit', $tutorial) }}"
                                       class="btn btn-success btn-sm"
                                       title="Editar">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('settings.tutoriales.destroy', $tutorial) }}"
                                          method="POST"
                                          onsubmit="return confirm('Eliminar este tutorial?');"
                                          style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Eliminar">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                Sin tutoriales registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tutoriales->hasPages())
            <div class="card-footer">
                {{ $tutoriales->links() }}
            </div>
        @endif
    </div>
@stop
