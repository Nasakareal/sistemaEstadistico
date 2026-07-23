@extends('adminlte::page')

@section('title', 'Puestas a Disposición')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <h1 class="mb-0">Puestas a Disposición</h1>

        @can('crear puestas a disposicion')
            <a href="{{ route('puestas_disposicion.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva puesta a disposición
            </a>
        @endcan
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('puestas_disposicion.index') }}" class="form-inline align-items-end">
                @if($puedeFiltrarUnidad)
                    <div class="form-group mr-3 mb-2 mb-md-0">
                        <label for="unidad_id" class="mr-2 mb-0">Unidad</label>
                        <select id="unidad_id" name="unidad_id" class="form-control form-control-sm">
                            <option value="">Todas las unidades</option>
                            @foreach($unidadesFiltro as $unidad)
                                <option value="{{ $unidad->id }}"
                                    {{ (int)$unidadSeleccionadaId === (int)$unidad->id ? 'selected' : '' }}>
                                    {{ $unidad->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <label class="mr-2 mb-0">Año</label>

                <select name="anio" class="form-control form-control-sm mr-2">
                    @foreach($anios as $anio)
                        <option value="{{ $anio }}" {{ request('anio', $anioActual) == $anio ? 'selected' : '' }}>
                            {{ $anio }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-sm btn-primary mr-2">
                    <i class="fas fa-filter"></i> Filtrar
                </button>

                @if(request()->filled('unidad_id') || request()->filled('anio'))
                    <a href="{{ route('puestas_disposicion.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                @endif
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:110px;">No.</th>
                            <th style="width:90px;">Año</th>
                            <th style="width:140px;">Fecha</th>
                            <th>Policía</th>
                            <th style="width:160px;">Tipo</th>
                            <th style="width:170px;">Motivo</th>
                            <th style="width:160px;">Unidad</th>
                            <th style="width:160px;">Delegación</th>
                            <th style="width:160px;">Destacamento</th>
                            <th style="width:150px;">Archivo</th>
                            <th style="width:240px;" class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($puestas as $p)
                            <tr>
                                <td>
                                    <strong>{{ $p->numero_puesta }}</strong>
                                </td>

                                <td>{{ $p->anio }}</td>

                                <td>
                                    {{ $p->fecha_puesta ? \Carbon\Carbon::parse($p->fecha_puesta)->format('d/m/Y') : '—' }}
                                </td>

                                <td>{{ $p->nombre_policia }}</td>

                                <td>{{ $p->tipo_puesta }}</td>

                                <td>{{ $p->motivo }}</td>

                                <td>{{ optional($p->unidad)->nombre ?? $p->area ?? 'SIN ASIGNAR' }}</td>

                                <td>{{ optional($p->delegacion)->nombre ?? '—' }}</td>

                                <td>{{ optional($p->destacamento)->nombre ?? '—' }}</td>

                                <td>
                                    @if($p->archivo_puesta)
                                        <a class="btn btn-sm btn-outline-danger"
                                           target="_blank"
                                           href="{{ route('puestas_disposicion.archivo', $p->id) }}">
                                            <i class="fas fa-file-pdf"></i> Ver PDF
                                        </a>
                                    @else
                                        <span class="text-muted">Sin archivo</span>
                                    @endif
                                </td>

                                <td class="text-right">
                                    <a href="{{ route('puestas_disposicion.show', $p->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    @can('editar puestas a disposicion')
                                        <a href="{{ route('puestas_disposicion.edit', $p->id) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endcan

                                    @can('eliminar puestas a disposicion')
                                        <form action="{{ route('puestas_disposicion.destroy', $p->id) }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('¿Eliminar esta puesta a disposición?');">
                                            @csrf
                                            @method('DELETE')

                                            <button class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    No hay puestas a disposición para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
