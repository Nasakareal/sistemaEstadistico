@extends('adminlte::page')

@section('title', 'Exámenes de Manejo')

@section('content_header')
    <h1>Exámenes de Manejo</h1>
@stop

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Exámenes generados</h3>
            <div class="card-tools">
                @can('crear modulo examenes')
                    <a href="{{ route('constancias_manejo.examenes.create', request('modalidad') ? ['modalidad' => request('modalidad')] : []) }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus"></i> Nuevo examen
                    </a>
                @endcan
            </div>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('constancias_manejo.examenes.index') }}" class="row mb-3">
                <div class="col-md-3 mb-2">
                    <input type="text" name="buscar" class="form-control" placeholder="Folio, nombre o CURP" value="{{ request('buscar') }}">
                </div>
                <div class="col-md-2 mb-2">
                    <select name="estatus" class="form-control">
                        <option value="">Estatus</option>
                        @foreach(['PENDIENTE', 'APROBADO', 'REPROBADO'] as $estatus)
                            <option value="{{ $estatus }}" {{ request('estatus') === $estatus ? 'selected' : '' }}>{{ $estatus }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="modalidad" class="form-control">
                        <option value="">Modalidad</option>
                        <option value="LINEA" {{ request('modalidad') === 'LINEA' ? 'selected' : '' }}>En línea</option>
                        <option value="IMPRESO" {{ request('modalidad') === 'IMPRESO' ? 'selected' : '' }}>Impreso</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="custom-control custom-checkbox mt-2">
                        <input type="checkbox" name="sin_constancia" value="1" class="custom-control-input" id="sinConstancia" {{ request()->boolean('sin_constancia') ? 'checked' : '' }}>
                        <label class="custom-control-label" for="sinConstancia">Sin constancia activada</label>
                    </div>
                </div>
                <div class="col-md-2 mb-2 text-right">
                    <button class="btn btn-primary btn-block">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Folio examen</th>
                            <th>Solicitante</th>
                            <th>Módulo</th>
                            <th>Licencia</th>
                            <th>Modalidad</th>
                            <th>Resultado</th>
                            <th>Constancia</th>
                            <th>Fecha examen</th>
                            <th style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($solicitudes as $solicitud)
                            <tr>
                                <td><strong>{{ $solicitud->folio_examen }}</strong></td>
                                <td class="text-left">
                                    {{ $solicitud->nombre_solicitante }}
                                    @if($solicitud->curp)
                                        <br><small class="text-muted">{{ $solicitud->curp }}</small>
                                    @endif
                                </td>
                                <td>{{ optional($solicitud->modulo)->nombre ?? 'N/A' }}</td>
                                <td>{{ $tiposLicencia[$solicitud->tipo_licencia] ?? $solicitud->tipo_licencia }}</td>
                                <td>
                                    <span class="badge badge-{{ $solicitud->modalidad === 'LINEA' ? 'info' : 'secondary' }}">
                                        {{ $solicitud->modalidad === 'LINEA' ? 'EN LÍNEA' : 'IMPRESO' }}
                                    </span>
                                </td>
                                <td>
                                    @if($solicitud->estatus === 'APROBADO')
                                        <span class="badge badge-success">APROBADO</span>
                                    @elseif($solicitud->estatus === 'REPROBADO')
                                        <span class="badge badge-danger">REPROBADO</span>
                                    @else
                                        <span class="badge badge-warning">PENDIENTE</span>
                                    @endif
                                </td>
                                <td>
                                    @if($solicitud->constancia)
                                        <a href="{{ route('constancias_manejo.show', $solicitud->constancia) }}">
                                            {{ $solicitud->constancia->folio }}
                                        </a>
                                    @elseif($solicitud->estatus === 'APROBADO')
                                        <span class="badge badge-warning">Por activar</span>
                                    @else
                                        <span class="text-muted">Sin asignar</span>
                                    @endif
                                </td>
                                <td>{{ $solicitud->fecha_examen ? $solicitud->fecha_examen->format('d-m-Y H:i') : 'N/A' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('constancias_manejo.examenes.show', $solicitud) }}" class="btn btn-info btn-sm">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>
                                    @if($solicitud->modalidad === 'IMPRESO')
                                        <a href="{{ url('/constancias-manejo/examenes/' . $solicitud->getRouteKey() . '/descargar-pdf') }}" class="btn btn-success btn-sm" title="Descargar examen PDF">
                                            <i class="fa-solid fa-file-pdf"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No hay exámenes registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $solicitudes->links() }}
        </div>
    </div>
@stop

@section('css')
<style>
    .table th,
    .table td {
        text-align: center;
        vertical-align: middle;
    }
</style>
@stop

@section('js')
<script>
@if (session('success'))
Swal.fire({
    icon: 'success',
    title: '{{ session('success') }}',
    timer: 3000,
    showConfirmButton: false
});
@endif

@if (session('error'))
Swal.fire({
    icon: 'error',
    title: '{{ session('error') }}',
    timer: 3500,
    showConfirmButton: false
});
@endif
</script>
@stop
