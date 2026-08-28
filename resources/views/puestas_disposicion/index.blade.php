@extends('adminlte::page')

@section('title', 'Puestas a Disposición')

@section('css')
    <style>
        .pd-filters select.form-control,
        .pd-filters input.form-control {
            background-color: #12263c !important;
            color: #f8fafc !important;
            border-color: rgba(125, 178, 225, .45) !important;
            color-scheme: dark;
        }

        .pd-filters select.form-control:focus,
        .pd-filters input.form-control:focus {
            background-color: #12263c !important;
            color: #ffffff !important;
            border-color: #64b5f6 !important;
            box-shadow: 0 0 0 .2rem rgba(100, 181, 246, .18) !important;
        }

        .pd-filters select.form-control option,
        .pd-filters select.form-control optgroup {
            background-color: #12263c !important;
            color: #f8fafc !important;
        }

        .pd-filters select.form-control option:checked {
            background: #2563d8 linear-gradient(0deg, #2563d8 0%, #2563d8 100%) !important;
            color: #ffffff !important;
        }

        .pd-filters select.form-control option:disabled {
            color: #94a3b8 !important;
        }
    </style>
@stop

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
            <form method="GET" action="{{ route('puestas_disposicion.index') }}" class="pd-filters">
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-3">
                        <label for="q">Buscar</label>
                        <div class="input-group">
                            <input type="search"
                                   id="q"
                                   name="q"
                                   class="form-control"
                                   value="{{ $busqueda }}"
                                   placeholder="Carpeta, oficio, policía, persona, CURP o número">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary" title="Buscar">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-3 mb-3">
                        <label for="mes">Mes</label>
                        <input type="month" id="mes" name="mes" class="form-control" value="{{ $mesSeleccionado }}">
                    </div>

                    <div class="col-lg-2 col-md-3 mb-3">
                        <label for="anio">Año</label>
                        <select id="anio" name="anio" class="form-control">
                            <option value="TODOS" {{ strtoupper((string)$anioSeleccionado) === 'TODOS' ? 'selected' : '' }}>Todos los años</option>
                            @foreach($anios as $anio)
                                <option value="{{ $anio }}" {{ (string)$anioSeleccionado === (string)$anio ? 'selected' : '' }}>
                                    {{ $anio }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($puedeFiltrarUnidad)
                        <div class="col-lg-4 col-md-6 mb-3">
                            <label for="unidad_id">Unidad</label>
                            <select id="unidad_id" name="unidad_id" class="form-control">
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

                    <div class="col-lg-3 col-md-6 mb-3">
                        <label for="carpeta_investigacion">Carpeta de investigación</label>
                        <input type="text"
                               id="carpeta_investigacion"
                               name="carpeta_investigacion"
                               class="form-control"
                               value="{{ $carpetaSeleccionada }}"
                               placeholder="Buscar por carpeta">
                    </div>

                    <div class="col-lg-3 col-md-6 mb-3">
                        <label for="oficio">Oficio</label>
                        <input type="text"
                               id="oficio"
                               name="oficio"
                               class="form-control"
                               value="{{ $oficioSeleccionado }}"
                               placeholder="Buscar por oficio">
                    </div>

                    <div class="col-lg-2 col-md-4 mb-3">
                        <label for="tipo_puesta">Tipo</label>
                        <select id="tipo_puesta" name="tipo_puesta" class="form-control">
                            <option value="">Todos los tipos</option>
                            @foreach($tiposFiltro as $tipo)
                                <option value="{{ $tipo }}" {{ $tipoSeleccionado === $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4 mb-3">
                        <label for="motivo">Motivo</label>
                        <select id="motivo" name="motivo" class="form-control">
                            <option value="">Todos los motivos</option>
                            @foreach($motivosFiltro as $motivo)
                                <option value="{{ $motivo }}" {{ $motivoSeleccionado === $motivo ? 'selected' : '' }}>{{ $motivo }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-filter"></i> Aplicar
                        </button>
                        <a href="{{ route('puestas_disposicion.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
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
                            <th>Carpeta</th>
                            <th>Oficio</th>
                            <th>Policía</th>
                            <th style="width:160px;">Tipo</th>
                            <th style="width:170px;">Motivo</th>
                            <th style="width:160px;">Unidad</th>
                            <th style="width:160px;">Delegación</th>
                            <th style="width:160px;">Destacamento</th>
                            <th style="width:95px;">Personas</th>
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

                                <td>{{ $p->carpeta_investigacion ?: '—' }}</td>

                                <td>{{ $p->oficio ?: '—' }}</td>

                                <td>{{ $p->nombre_policia }}</td>

                                <td>{{ $p->tipo_puesta }}</td>

                                <td>{{ $p->motivo }}</td>

                                <td>{{ optional($p->unidad)->nombre ?? $p->area ?? 'SIN ASIGNAR' }}</td>

                                <td>{{ optional($p->delegacion)->nombre ?? '—' }}</td>

                                <td>{{ optional($p->destacamento)->nombre ?? '—' }}</td>

                                <td class="text-center">
                                    <span class="badge badge-info">{{ (int)$p->personas_count }}</span>
                                </td>

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
                                <td colspan="14" class="text-center text-muted py-4">
                                    No hay puestas a disposición para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($puestas->hasPages())
            <div class="card-footer d-flex justify-content-center">
                {{ $puestas->links() }}
            </div>
        @endif
    </div>
@stop
