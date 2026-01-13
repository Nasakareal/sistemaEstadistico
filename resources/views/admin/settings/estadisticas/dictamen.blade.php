@extends('adminlte::page')

@section('title', 'Dictamen de Hechos')

@section('content_header')
    <h1>Dictamen de Hechos</h1>
@stop

@section('content')

    {{-- BUSCADOR --}}
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-body">
                    <form method="GET" action="{{ route('estadisticas.dictamen') }}">
                        <div class="input-group">
                            <input
                                type="text"
                                name="q"
                                class="form-control"
                                placeholder="Buscar por PLACA o ID DEL HECHO"
                                value="{{ $q ?? '' }}"
                                autofocus
                            >
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- RESULTADOS --}}
    @if(!empty($q))
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">
                            Resultados de búsqueda
                            @if($modo === 'placa')
                                (Placa)
                            @elseif($modo === 'id')
                                (ID)
                            @endif
                        </h3>
                    </div>

                    <div class="card-body p-0">
                        <table class="table table-bordered table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Tipo de Hecho</th>
                                    <th>Sector</th>
                                    <th>Ubicación</th>
                                    <th>Vehículos</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>

                                @forelse($resultados as $hecho)
                                    <tr>
                                        <td>{{ $hecho->id }}</td>
                                        <td>{{ \Carbon\Carbon::parse($hecho->fecha)->format('d/m/Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($hecho->hora)->format('H:i') }}</td>
                                        <td>{{ $hecho->tipo_hecho }}</td>
                                        <td>{{ $hecho->sector }}</td>
                                        <td>
                                            {{ $hecho->calle }},
                                            {{ $hecho->colonia }}
                                        </td>
                                        <td>
                                            @foreach($hecho->vehiculos as $v)
                                                <span class="badge badge-info">
                                                    {{ $v->placas }}
                                                </span>
                                            @endforeach
                                        </td>
                                        <td class="text-center">
                                            <a
                                                href="{{ route('estadisticas.dictamen.show', $hecho->id) }}"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                <i class="fas fa-eye"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            No se encontraron resultados
                                        </td>
                                    </tr>
                                @endforelse

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

@stop

@section('css')
    <style>
        .badge-info {
            margin-right: 2px;
        }
    </style>
@stop
