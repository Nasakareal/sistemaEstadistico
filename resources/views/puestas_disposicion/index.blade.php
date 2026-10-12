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

        .pd-sort-link {
            align-items: center;
            color: inherit;
            display: inline-flex;
            gap: .35rem;
            text-decoration: none;
            white-space: nowrap;
        }

        .pd-sort-link:hover,
        .pd-sort-link:focus {
            color: #0b63ce;
            text-decoration: none;
        }

        .pd-sort-link .fas {
            font-size: .75rem;
            opacity: .75;
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
    @php
        $urlOrden = function (string $columna) use ($orden, $direccion): string {
            $parametros = request()->except('page');
            $parametros['orden'] = $columna;
            $parametros['direccion'] = $orden === $columna && $direccion === 'asc' ? 'desc' : 'asc';

            return route('puestas_disposicion.index', $parametros);
        };

        $iconoOrden = function (string $columna) use ($orden, $direccion): string {
            if ($orden !== $columna) {
                return 'fa-sort';
            }

            return $direccion === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
        };

        $ariaOrden = fn (string $columna): string => $orden === $columna
            ? ($direccion === 'asc' ? 'ascending' : 'descending')
            : 'none';
    @endphp

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('puestas_disposicion.index') }}" class="pd-filters">
                <input type="hidden" name="orden" value="{{ $orden }}">
                <input type="hidden" name="direccion" value="{{ $direccion }}">
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-3">
                        <label for="q">Buscar</label>
                        <div class="input-group">
                            <input type="search"
                                   id="q"
                                   name="q"
                                   class="form-control"
                                   value="{{ $busqueda }}"
                                   placeholder="Carpeta, oficio, policía, persona, CURP o folio">
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

                    <div class="col-lg-2 col-md-4 mb-3">
                        <label for="numero_origen">Núm. Carreteras</label>
                        <input type="text"
                               id="numero_origen"
                               name="numero_origen"
                               class="form-control"
                               value="{{ $numeroOrigenSeleccionado }}"
                               placeholder="Ej. 7, 16 o 25">
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
                            <th style="width:110px;" aria-sort="{{ $ariaOrden('numero') }}">
                                <a class="pd-sort-link" href="{{ $urlOrden('numero') }}" title="Ordenar por número">
                                    Folio sistema <i class="fas {{ $iconoOrden('numero') }}" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th style="min-width:180px;" aria-sort="{{ $ariaOrden('origen') }}">
                                <a class="pd-sort-link" href="{{ $urlOrden('origen') }}" title="Ordenar por secuencia del concentrado de Carreteras">
                                    Referencia Carreteras <i class="fas {{ $iconoOrden('origen') }}" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th style="width:90px;" aria-sort="{{ $ariaOrden('anio') }}">
                                <a class="pd-sort-link" href="{{ $urlOrden('anio') }}" title="Ordenar por año">
                                    Año <i class="fas {{ $iconoOrden('anio') }}" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th style="width:140px;" aria-sort="{{ $ariaOrden('fecha') }}">
                                <a class="pd-sort-link" href="{{ $urlOrden('fecha') }}" title="Ordenar por fecha">
                                    Fecha <i class="fas {{ $iconoOrden('fecha') }}" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th aria-sort="{{ $ariaOrden('carpeta') }}">
                                <a class="pd-sort-link" href="{{ $urlOrden('carpeta') }}" title="Ordenar por carpeta">
                                    Carpeta <i class="fas {{ $iconoOrden('carpeta') }}" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th aria-sort="{{ $ariaOrden('oficio') }}">
                                <a class="pd-sort-link" href="{{ $urlOrden('oficio') }}" title="Ordenar por oficio">
                                    Oficio <i class="fas {{ $iconoOrden('oficio') }}" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th aria-sort="{{ $ariaOrden('policia') }}">
                                <a class="pd-sort-link" href="{{ $urlOrden('policia') }}" title="Ordenar por policía">
                                    Policía <i class="fas {{ $iconoOrden('policia') }}" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th style="width:160px;" aria-sort="{{ $ariaOrden('tipo') }}">
                                <a class="pd-sort-link" href="{{ $urlOrden('tipo') }}" title="Ordenar por tipo">
                                    Tipo <i class="fas {{ $iconoOrden('tipo') }}" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th style="width:170px;" aria-sort="{{ $ariaOrden('motivo') }}">
                                <a class="pd-sort-link" href="{{ $urlOrden('motivo') }}" title="Ordenar por motivo">
                                    Motivo <i class="fas {{ $iconoOrden('motivo') }}" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th style="width:160px;" aria-sort="{{ $ariaOrden('unidad') }}">
                                <a class="pd-sort-link" href="{{ $urlOrden('unidad') }}" title="Ordenar por unidad">
                                    Unidad <i class="fas {{ $iconoOrden('unidad') }}" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th style="width:160px;" aria-sort="{{ $ariaOrden('delegacion') }}">
                                <a class="pd-sort-link" href="{{ $urlOrden('delegacion') }}" title="Ordenar por delegación">
                                    Delegación <i class="fas {{ $iconoOrden('delegacion') }}" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th style="width:160px;" aria-sort="{{ $ariaOrden('destacamento') }}">
                                <a class="pd-sort-link" href="{{ $urlOrden('destacamento') }}" title="Ordenar por destacamento">
                                    Destacamento <i class="fas {{ $iconoOrden('destacamento') }}" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th style="width:95px;" aria-sort="{{ $ariaOrden('personas') }}">
                                <a class="pd-sort-link" href="{{ $urlOrden('personas') }}" title="Ordenar por cantidad de personas">
                                    Personas <i class="fas {{ $iconoOrden('personas') }}" aria-hidden="true"></i>
                                </a>
                            </th>
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

                                <td>
                                    @if($p->numero_origen || $p->folio_origen || $p->secuencia_origen)
                                        <div><strong>Núm. {{ $p->numero_origen ?: '—' }}</strong></div>
                                        <small class="d-block text-muted">Folio: {{ $p->folio_origen ?: '—' }}</small>
                                        <small class="d-block text-muted">Secuencia: {{ $p->secuencia_origen ?: '—' }}</small>
                                    @else
                                        <span class="text-muted">Captura directa</span>
                                    @endif
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
                                        @if($p->fuente_importacion)
                                            <button type="button" class="btn btn-sm btn-secondary" disabled
                                                    title="Registro oficial importado: debe conservar su referencia de origen">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        @else
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
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="text-center text-muted py-4">
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
                {{ $puestas->onEachSide(1)->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@stop
