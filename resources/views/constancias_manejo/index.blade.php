@extends('adminlte::page')

@section('title', 'Imprimir Constancias')

@section('content_header')
    <h1>Imprimir Constancias</h1>
@stop

@section('content')
@php
    $filtrosBase = array_filter(request()->only(['buscar', 'estatus']), function ($valor) {
        return $valor !== null && $valor !== '';
    });
@endphp

@if($isSuperadmin)
<div class="card card-outline card-info">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <strong class="mb-2 mb-md-0">Origen de las constancias</strong>
            <div class="btn-group" role="group" aria-label="Filtrar por origen">
                <a href="{{ route('constancias_manejo.index', $filtrosBase) }}"
                   class="btn {{ !$tipoModulo ? 'btn-info' : 'btn-outline-info' }}">
                    Todos
                </a>
                <a href="{{ route('constancias_manejo.index', array_merge($filtrosBase, ['tipo_modulo' => 'SINIESTROS'])) }}"
                   class="btn {{ $tipoModulo === 'SINIESTROS' ? 'btn-danger' : 'btn-outline-danger' }}">
                    <i class="fa-solid fa-car-burst"></i> Solo Siniestros
                </a>
                <a href="{{ route('constancias_manejo.index', array_merge($filtrosBase, ['tipo_modulo' => 'DELEGACION'])) }}"
                   class="btn {{ $tipoModulo === 'DELEGACION' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="fa-solid fa-building-shield"></i> Solo Delegaciones
                </a>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Lotes generados</h3>
                <span class="text-muted ml-2">Puedes descargarlos nuevamente para llevarlos a otra computadora.</span>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Origen</th>
                                <th>Módulo</th>
                                <th>Folios</th>
                                <th>Cantidad</th>
                                <th>Generó</th>
                                <th>Descarga</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lotes as $lote)
                                <tr>
                                    <td>{{ $lote->fecha_generacion ? $lote->fecha_generacion->format('d/m/Y H:i') : '—' }}</td>
                                    <td>
                                        @if(optional($lote->modulo)->tipo === 'SINIESTROS')
                                            <span class="badge badge-danger">SINIESTROS</span>
                                        @else
                                            <span class="badge badge-primary">DELEGACIONES</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($lote->modulo)->nombre ?? 'Módulo no disponible' }}</td>
                                    <td><strong>{{ $lote->folio_inicial }}</strong> a <strong>{{ $lote->folio_final }}</strong></td>
                                    <td>{{ $lote->cantidad }}</td>
                                    <td>{{ optional($lote->usuario)->name ?? '—' }}</td>
                                    <td>
                                        @can('crear modulo examenes')
                                            <a href="{{ route('constancias_manejo.lotes.descargar', $lote->lote_uuid) }}"
                                               class="btn btn-success btn-sm"
                                               title="Descargar lote completo en PDF">
                                                <i class="fa-solid fa-file-pdf"></i> Descargar PDF
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-muted py-3">No hay lotes para los filtros seleccionados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($lotes->hasPages())
                <div class="card-footer">
                    {{ $lotes->onEachSide(1)->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">

            <div class="card-header">
                <h3 class="card-title">Inventario de constancias impresas</h3>

                <div class="card-tools">

                    @can('crear modulo examenes')
                        <a href="{{ route('constancias_manejo.create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-print"></i> Generar lote
                        </a>
                    @endcan

                    @can('editar modulo examenes')
                        <a href="{{ route('constancias_manejo.activar_manual') }}" class="btn btn-success">
                            <i class="fa-solid fa-keyboard"></i> Activar manual
                        </a>
                    @endcan

                    <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </a>

                </div>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('constancias_manejo.index') }}" class="row align-items-end mb-3">
                    @if($tipoModulo)
                        <input type="hidden" name="tipo_modulo" value="{{ $tipoModulo }}">
                    @endif

                    <div class="col-md-4">
                        <div class="form-group mb-md-0">
                            <label for="buscar">Buscar</label>
                            <input type="text"
                                   id="buscar"
                                   name="buscar"
                                   class="form-control"
                                   value="{{ request('buscar') }}"
                                   placeholder="Folio, nombre o CURP">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group mb-md-0">
                            <label for="estatus">Estatus</label>
                            <select id="estatus" name="estatus" class="form-control">
                                <option value="">Todos</option>
                                @foreach(['IMPRESA_INACTIVA' => 'Inactiva', 'ACTIVA' => 'Activa', 'EXPIRADA' => 'Expirada', 'CANCELADA' => 'Cancelada'] as $valor => $etiqueta)
                                    <option value="{{ $valor }}" {{ request('estatus') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group mb-md-0">
                            <label for="modulo_id">Módulo</label>
                            <select id="modulo_id" name="modulo_id" class="form-control">
                                <option value="">Todos los módulos</option>
                                @foreach($modulosFiltro as $moduloFiltro)
                                    @if(!$tipoModulo || $moduloFiltro->tipo === $tipoModulo)
                                        <option value="{{ $moduloFiltro->id }}" {{ (string) request('modulo_id') === (string) $moduloFiltro->id ? 'selected' : '' }}>
                                            {{ $moduloFiltro->nombre }} - {{ $moduloFiltro->tipo === 'SINIESTROS' ? 'Siniestros' : 'Delegaciones' }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-info btn-block">
                            <i class="fa-solid fa-filter"></i> Filtrar
                        </button>
                        <a href="{{ route('constancias_manejo.index', $tipoModulo ? ['tipo_modulo' => $tipoModulo] : []) }}"
                           class="btn btn-link btn-sm btn-block">Limpiar búsqueda</a>
                    </div>
                </form>

                <table id="constancias" class="table table-striped table-bordered table-hover table-sm">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Folio</th>
                            <th>Origen</th>
                            <th>Módulo</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Modalidad</th>
                            <th>Resultado</th>
                            <th>Estatus</th>
                            <th>Expira</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($constancias as $index => $c)
                            <tr>
                                <td>{{ ($constancias->firstItem() ?? 1) + $index }}</td>
                                <td><strong>{{ $c->folio }}</strong></td>
                                <td>
                                    @if(optional($c->modulo)->tipo === 'SINIESTROS')
                                        <span class="badge badge-danger">SINIESTROS</span>
                                    @else
                                        <span class="badge badge-primary">DELEGACIONES</span>
                                    @endif
                                </td>
                                <td>{{ optional($c->modulo)->nombre ?? '—' }}</td>
                                <td>{{ $c->nombre_solicitante }}</td>
                                <td>{{ $c->tipo_licencia }}</td>
                                <td>{{ $c->tipo_examen }}</td>

                                <td>
                                    @if($c->examen)
                                        @if($c->examen->resultado == 'APROBADO')
                                            <span class="badge bg-success">APROBADO</span>
                                        @else
                                            <span class="badge bg-danger">REPROBADO</span>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">SIN EXAMEN</span>
                                    @endif
                                </td>

                                <td>
                                    @if($c->estatus == 'ACTIVA')
                                        <span class="badge bg-success">ACTIVA</span>
                                    @elseif($c->estatus == 'IMPRESA_INACTIVA')
                                        <span class="badge bg-warning">INACTIVA</span>
                                    @elseif($c->estatus == 'EXPIRADA')
                                        <span class="badge bg-danger">EXPIRADA</span>
                                    @else
                                        <span class="badge bg-dark">CANCELADA</span>
                                    @endif
                                </td>

                                <td>
                                    {{ $c->fecha_expiracion ? $c->fecha_expiracion->format('d-m-Y') : '—' }}
                                </td>

                                <td>
                                    <div class="btn-group">

                                        <a href="{{ route('constancias_manejo.show', $c->id) }}"
                                           class="btn btn-info btn-sm">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>

                                        <a href="{{ route('constancias_manejo.imprimir', $c->id) }}"
                                           class="btn btn-secondary btn-sm">
                                            <i class="fa-solid fa-print"></i>
                                        </a>

                                        @can('editar modulo examenes')
                                            @if($c->estatus === 'IMPRESA_INACTIVA' && $c->puedeActivarDirectamente())
                                                <a href="{{ route('constancias_manejo.activar_manual', ['folio' => $c->folio]) }}"
                                                   class="btn btn-success btn-sm">
                                                    <i class="fa-solid fa-keyboard"></i>
                                                </a>
                                            @endif
                                        @endcan

                                        @php
                                            $puedeActivarConstancia = $c->puedeActivar();
                                        @endphp

                                        @if($puedeActivarConstancia)
                                            <form action="{{ route('constancias_manejo.activar', $c->id) }}"
                                                  method="POST" style="display:inline;">
                                                @csrf
                                                <button class="btn btn-success btn-sm">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </form>
                                        @endif

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>

                @if($constancias->hasPages())
                    <div class="mt-3">
                        {{ $constancias->onEachSide(1)->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
@stop


@section('css')
<style>
.table th, .table td {
    text-align: center;
    vertical-align: middle;
}
</style>
@stop


@section('js')
<script>
$(function () {
    $('#constancias').DataTable({
        responsive: true,
        autoWidth: false,
        paging: false,
        searching: false,
        info: false,
        ordering: false,
        language: {
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ a _END_ de _TOTAL_",
            paginate: {
                next: "Siguiente",
                previous: "Anterior"
            }
        }
    });
});

@if (session('success'))
Swal.fire({
    icon: 'success',
    title: '{{ session('success') }}',
    timer: 3000,
    showConfirmButton: false
});
@endif
</script>
@stop
