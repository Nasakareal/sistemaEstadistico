@extends('adminlte::page')

@section('title', 'Banco de Preguntas')

@section('content_header')
    <h1>Banco de Preguntas</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Preguntas por tipo de licencia</h3>

                <div class="card-tools">
                    @can('crear modulo examenes')
                        <a href="{{ route('constancias_manejo.preguntas.create', ['tipo_licencia' => $tipoSeleccionado ?: 'GENERAL']) }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Nueva Pregunta
                        </a>
                    @endcan

                    <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Configuraciones
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="row mb-3">
                    @foreach($tiposLicencia as $valor => $label)
                        <div class="col-md-2 col-sm-4 col-6 mb-2">
                            <div class="small-box bg-dark mb-0">
                                <div class="inner">
                                    <h4 class="mb-0">{{ number_format($conteos[$valor] ?? 0) }}</h4>
                                    <p class="mb-0">{{ $label }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('constancias_manejo.preguntas.index') }}" class="row mb-3">
                    <div class="col-md-4">
                        <label>Tipo de licencia</label>
                        <select name="tipo_licencia" class="form-control">
                            <option value="">Todos</option>
                            @foreach($tiposLicencia as $valor => $label)
                                <option value="{{ $valor }}" {{ $tipoSeleccionado === $valor ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Estatus</label>
                        <select name="activo" class="form-control">
                            <option value="">Todos</option>
                            <option value="1" {{ $activoSeleccionado === '1' ? 'selected' : '' }}>Activas</option>
                            <option value="0" {{ $activoSeleccionado === '0' ? 'selected' : '' }}>Inactivas</option>
                        </select>
                    </div>

                    <div class="col-md-5 d-flex align-items-end">
                        <button type="submit" class="btn btn-info mr-2">
                            <i class="fa-solid fa-filter"></i> Filtrar
                        </button>

                        <a href="{{ route('constancias_manejo.preguntas.index') }}" class="btn btn-secondary mr-2">
                            <i class="fa-solid fa-rotate-left"></i> Limpiar
                        </a>

                        <button type="submit" formaction="{{ route('constancias_manejo.preguntas.imprimir') }}" formtarget="_blank" class="btn btn-dark">
                            <i class="fa-solid fa-print"></i> Imprimir examen
                        </button>
                    </div>
                </form>

                <table id="preguntas" class="table table-striped table-bordered table-hover table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tipo</th>
                            <th>Pregunta</th>
                            <th>Respuestas</th>
                            <th>Correcta</th>
                            <th>Estatus</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preguntas as $index => $pregunta)
                            @php
                                $correcta = $pregunta->respuestas->firstWhere('es_correcta', true);
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $tiposLicencia[$pregunta->tipo_licencia] ?? $pregunta->tipo_licencia }}</td>
                                <td class="text-left">{{ $pregunta->pregunta }}</td>
                                <td>{{ $pregunta->respuestas_count }}</td>
                                <td class="text-left">{{ $correcta->respuesta ?? '—' }}</td>
                                <td>
                                    @if($pregunta->activo)
                                        <span class="badge bg-success">ACTIVA</span>
                                    @else
                                        <span class="badge bg-secondary">INACTIVA</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        @can('editar modulo examenes')
                                            <a href="{{ route('constancias_manejo.preguntas.edit', $pregunta) }}" class="btn btn-success btn-sm">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                        @endcan

                                        @can('eliminar modulo examenes')
                                            <form action="{{ route('constancias_manejo.preguntas.destroy', $pregunta) }}" method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-sm delete-btn">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </form>
                                        @endcan
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

    .small-box .inner {
        padding: 10px;
    }
</style>
@stop

@section('js')
<script>
$(function () {
    $('#preguntas').DataTable({
        pageLength: 10,
        responsive: true,
        autoWidth: false,
        language: {
            emptyTable: "No hay preguntas capturadas",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            lengthMenu: "Mostrar _MENU_ registros",
            search: "Buscar:",
            zeroRecords: "Sin resultados encontrados",
            paginate: {
                next: "Siguiente",
                previous: "Anterior"
            }
        }
    });
});

$(document).on('click', '.delete-btn', function (e) {
    e.preventDefault();

    let form = $(this).closest('form');

    Swal.fire({
        title: '¿Eliminar pregunta?',
        text: 'También se eliminarán sus respuestas.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
});

@if (session('success'))
Swal.fire({
    icon: 'success',
    title: '{{ session('success') }}',
    timer: 2500,
    showConfirmButton: false
});
@endif
</script>
@stop
