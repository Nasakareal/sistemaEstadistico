@extends('adminlte::page')

@section('title', 'Operativos')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <h1 class="mb-0">Operativos</h1>

        @can('crear operativos')
        <a href="{{ route('operativos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo operativo
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

        <form method="GET" action="{{ route('operativos.index') }}" class="form-inline">

            <label class="mr-2 mb-0">Fecha</label>

            <input
                type="date"
                name="fecha"
                value="{{ request('fecha', $fechaSeleccionada) }}"
                class="form-control form-control-sm mr-3"
                onchange="this.form.submit()"
            >

            <label class="mr-2 mb-0">Tipo</label>

            <select
                name="operativo_catalogo_id"
                class="form-control form-control-sm mr-3"
                onchange="this.form.submit()"
            >
                <option value="">Todos</option>

                @foreach($catalogos as $c)
                    <option
                        value="{{ $c->id }}"
                        {{ request('operativo_catalogo_id') == $c->id ? 'selected' : '' }}
                    >
                        {{ $c->nombre }}
                    </option>
                @endforeach
            </select>

            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Buscar..."
                class="form-control form-control-sm mr-2"
            >

            <button class="btn btn-sm btn-secondary">
                <i class="fas fa-search"></i>
            </button>

        </form>

    </div>


    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover table-striped mb-0">

                <thead class="thead-light">

                    <tr>
                        <th style="width:120px;">Fecha</th>
                        <th style="width:200px;">Tipo</th>
                        <th>Descripción</th>
                        <th style="width:180px;">Lugar</th>
                        <th style="width:140px;">Vehículos</th>
                        <th style="width:140px;">Personas</th>
                        <th style="width:100px;">Fotos</th>
                        <th style="width:220px;" class="text-right">Acciones</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($operativos as $o)

                    <tr>

                        <td>
                            {{ \Carbon\Carbon::parse($o->fecha)->format('d/m/Y') }}
                        </td>

                        <td>
                            {{ $o->catalogo->nombre ?? '-' }}
                        </td>

                        <td>
                            {{ Str::limit($o->descripcion,80) }}
                        </td>

                        <td>
                            {{ $o->lugar ?? '-' }}
                        </td>

                        <td>
                            {{ $o->vehiculos_inspeccionados }}
                        </td>

                        <td>
                            {{ $o->personas_inspeccionadas }}
                        </td>

                        <td>

                            @if($o->fotos->count())
                                <span class="badge badge-info">
                                    {{ $o->fotos->count() }}
                                </span>
                            @else
                                <span class="text-muted">0</span>
                            @endif

                        </td>


                        <td class="text-right">

                            <a href="{{ route('operativos.show',$o->id) }}"
                               class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>


                            @can('editar operativos')
                            <a href="{{ route('operativos.edit',$o->id) }}"
                               class="btn btn-sm btn-success">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endcan


                            @can('eliminar operativos')
                            <form action="{{ route('operativos.destroy',$o->id) }}"
                                  method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('¿Eliminar este operativo?');">

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
                        <td colspan="8" class="text-center text-muted py-4">
                            No hay operativos registrados para la fecha seleccionada.
                        </td>
                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@stop
