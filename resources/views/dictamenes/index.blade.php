@extends('adminlte::page')

@section('title', 'Dictámenes')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <h1 class="mb-0">Dictámenes</h1>
        <a href="{{ route('dictamenes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo dictamen
        </a>
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
            <form method="GET" action="{{ route('dictamenes.index') }}" class="form-inline">
                <label class="mr-2 mb-0">Año</label>
                <select name="anio" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    @foreach($anios as $anio)
                        <option value="{{ $anio }}" {{ request('anio', $anioActual) == $anio ? 'selected' : '' }}>
                            {{ $anio }}
                        </option>
                    @endforeach
                </select>
                <noscript><button class="btn btn-sm btn-secondary">Filtrar</button></noscript>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:120px;">No.</th>
                            <th style="width:90px;">Año</th>
                            <th>Policía</th>
                            <th>MP</th>
                            <th>Área</th>
                            <th style="width:180px;">Archivo</th>
                            <th style="width:240px;" class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dictamenes as $d)
                            <tr>
                                <td><strong>{{ $d->numero_dictamen }}</strong></td>
                                <td>{{ $d->anio }}</td>
                                <td>{{ $d->nombre_policia }}</td>
                                <td>{{ $d->nombre_mp }}</td>
                                <td>{{ $d->area }}</td>
                                <td>
                                    @if($d->archivo_dictamen)
                                        <a class="btn btn-sm btn-outline-danger" target="_blank"
                                           href="{{ asset('storage/'.$d->archivo_dictamen) }}">
                                            <i class="fas fa-file-pdf"></i> Ver PDF
                                        </a>
                                    @else
                                        <span class="text-muted">Sin archivo</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('dictamenes.show', $d->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    @php
                                        $u = auth()->user();
                                        $puedeEditar = ($u->id === $d->created_by) || $u->hasRole(['Administrador','Superadmin','Administrativo']);
                                        $esAdmin = $u->hasRole(['Administrador','Superadmin','Administrativo']);
                                    @endphp

                                    @if($puedeEditar)
                                        <a href="{{ route('dictamenes.edit', $d->id) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif

                                    @if($esAdmin)
                                        <form action="{{ route('dictamenes.destroy', $d->id) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('¿Eliminar este dictamen?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No hay dictámenes para el año seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
