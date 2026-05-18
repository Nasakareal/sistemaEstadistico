@extends('adminlte::page')

@section('title', 'Imprimir Constancias')

@section('content_header')
    <h1>Imprimir Constancias</h1>
@stop

@section('content')
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

                    <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </a>

                </div>
            </div>

            <div class="card-body">
                <table id="constancias" class="table table-striped table-bordered table-hover table-sm">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Folio</th>
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
                                <td>{{ $index + 1 }}</td>
                                <td><strong>{{ $c->folio }}</strong></td>
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

                                        @if($c->estatus == 'IMPRESA_INACTIVA' && $c->examen && $c->examen->resultado == 'APROBADO')
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
        pageLength: 10,
        responsive: true,
        autoWidth: false,
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
