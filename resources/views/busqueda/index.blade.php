@extends('adminlte::page')

@section('title', 'Búsqueda General')

@section('content_header')
    <h1>Búsqueda en el Sistema</h1>
@stop

@section('content')
    <form method="GET" action="{{ route('busqueda.index') }}">
        <div class="row">
            <div class="col-md-8">
                <div class="input-group mb-3">
                    <input type="text" name="query" class="form-control" placeholder="Buscar folio, ubicación, placa, serie o conductor..." value="{{ request('query') }}" required>
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">Buscar</button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <select name="origen" class="form-control mb-3" onchange="this.form.submit()">
                    <option value="todos" {{ ($origen ?? request('origen', 'todos')) === 'todos' ? 'selected' : '' }}>Todos</option>
                    <option value="actuales" {{ ($origen ?? request('origen')) === 'actuales' ? 'selected' : '' }}>Actuales</option>
                    <option value="historicos" {{ ($origen ?? request('origen')) === 'historicos' ? 'selected' : '' }}>Históricos Peritos</option>
                </select>
            </div>
        </div>
    </form>

    @if(isset($query))
        <div class="card">
            <div class="card-header">
                Resultados de la búsqueda para: <strong>{{ $query }}</strong>
            </div>
            <div class="card-body">
                
                <!-- Conductores Encontrados -->
                @if ($conductores->count())
                    <h4>Conductores</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Teléfono</th>
                                <th>Domicilio</th>
                                <th>Licencia</th>
                                <th>Fecha</th>
                                <th>Origen</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($conductores as $conductor)
                                @php
                                    $primerVehiculo = $conductor->vehiculos->first();
                                    $primerHecho = $primerVehiculo ? $primerVehiculo->hechos->first() : null;
                                @endphp
                                <tr>
                                    <td>{{ $conductor->nombre }}</td>
                                    <td>{{ $conductor->telefono }}</td>
                                    <td>{{ $conductor->domicilio }}</td>
                                    <td>{{ $conductor->numero_licencia }}</td>
                                    <td>{{ optional(optional($primerHecho)->fecha)->format('Y-m-d') ?: 'N/A' }}</td>
                                    <td>
                                        @if(optional($primerHecho)->fuente_ubicacion === 'legacy_peritos')
                                            <span class="badge badge-secondary">Histórico Peritos</span>
                                        @else
                                            <span class="badge badge-primary">Actual</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($primerHecho)
                                            <a href="{{ route('hechos.show', $primerHecho->id) }}" class="btn btn-info btn-sm">Ver Hecho</a>
                                        @else
                                            <span class="text-muted">Sin hechos</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <!-- Vehículos Encontrados -->
                @if ($vehiculos->count())
                    <h4>Vehículos</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Placas</th>
                                <th>Serie</th>
                                <th>Conductor</th>
                                <th>Fecha</th>
                                <th>Origen</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($vehiculos as $vehiculo)
                                @php
                                    $primerHecho = $vehiculo->hechos->first();
                                @endphp
                                <tr>
                                    <td>{{ $vehiculo->marca }}</td>
                                    <td>{{ $vehiculo->modelo }}</td>
                                    <td>{{ $vehiculo->placas }}</td>
                                    <td>{{ $vehiculo->serie }}</td>
                                    <td>{{ optional($vehiculo->conductores->first())->nombre ?: 'N/A' }}</td>
                                    <td>{{ optional(optional($primerHecho)->fecha)->format('Y-m-d') ?: 'N/A' }}</td>
                                    <td>
                                        @if(optional($primerHecho)->fuente_ubicacion === 'legacy_peritos')
                                            <span class="badge badge-secondary">Histórico Peritos</span>
                                        @else
                                            <span class="badge badge-primary">Actual</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($primerHecho)
                                            <a href="{{ route('hechos.show', $primerHecho->id) }}" class="btn btn-info btn-sm">Ver Hecho</a>
                                        @else
                                            <span class="text-muted">Sin hechos</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <!-- Hechos Encontrados -->
                @if ($hechos->count())
                    <h4>Hechos</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Ubicación</th>
                                <th>Origen</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hechos as $hecho)
                                <tr>
                                    <td>{{ $hecho->folio_c5i }}</td>
                                    <td>{{ optional($hecho->fecha)->format('Y-m-d') }}</td>
                                    <td>{{ $hecho->calle }}, {{ $hecho->colonia }}, {{ $hecho->municipio }}</td>
                                    <td>
                                        @if($hecho->fuente_ubicacion === 'legacy_peritos')
                                            <span class="badge badge-secondary">Histórico Peritos</span>
                                        @else
                                            <span class="badge badge-primary">Actual</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('hechos.show', $hecho->id) }}" class="btn btn-info btn-sm">Ver</a>
                                        <a href="{{ route('hechos.edit', $hecho->id) }}" class="btn btn-success btn-sm">Editar</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if (!$conductores->count() && !$vehiculos->count() && !$hechos->count())
                    <p class="text-center text-danger">No se encontraron resultados.</p>
                @endif
            </div>
        </div>
    @endif
@stop
