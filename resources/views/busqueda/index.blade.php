@extends('adminlte::page')

@section('title', 'Búsqueda General')

@section('content_header')
    <h1>Búsqueda en el Sistema</h1>
@stop

@section('content')
    <form method="GET" action="{{ route('busqueda.index') }}">
        <div class="input-group mb-3">
            <input type="text" name="query" class="form-control" placeholder="Buscar..." value="{{ request('query') }}" required>
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit">Buscar</button>
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
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($conductores as $conductor)
                                <tr>
                                    <td>{{ $conductor->nombre }}</td>
                                    <td>{{ $conductor->telefono }}</td>
                                    <td>{{ $conductor->domicilio }}</td>
                                    <td>{{ $conductor->numero_licencia }}</td>
                                    <td>
                                        @php
                                            // Obtenemos el primer vehículo del conductor
                                            $primerVehiculo = $conductor->vehiculos->first();
                                            // Si existe un vehículo, obtenemos el primer hecho relacionado a ese vehículo
                                            $primerHecho = $primerVehiculo ? $primerVehiculo->hechos->first() : null;
                                        @endphp

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
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($vehiculos as $vehiculo)
                                <tr>
                                    <td>{{ $vehiculo->marca }}</td>
                                    <td>{{ $vehiculo->modelo }}</td>
                                    <td>{{ $vehiculo->placas }}</td>
                                    <td>{{ $vehiculo->serie }}</td>
                                    <td>
                                        {{-- Se mantiene la llamada directa en el vehículo, ya que la relación 'hechos' existe en Vehiculo --}}
                                        <a href="{{ route('hechos.show', $vehiculo->hechos()->first()->id ?? '#') }}" class="btn btn-info btn-sm">Ver Hecho</a>
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
                                <th>Ubicación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hechos as $hecho)
                                <tr>
                                    <td>{{ $hecho->folio_c5i }}</td>
                                    <td>{{ $hecho->calle }}, {{ $hecho->colonia }}, {{ $hecho->municipio }}</td>
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
