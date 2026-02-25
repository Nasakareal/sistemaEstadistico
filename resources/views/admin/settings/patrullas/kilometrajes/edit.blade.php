@extends('adminlte::page')

@section('title', 'Editar Kilometraje')

@section('content_header')
    <h1>Editar Registro de Kilometraje</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">
                    Patrulla {{ $patrulla->numero_economico }} · Editar
                </h3>
            </div>

            <div class="card-body">
                <form action="{{ route('patrullas.kilometrajes.update', [$patrulla->id, $kilometraje->id]) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">

                        <!-- Fecha -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="fecha">Fecha</label>
                                <input
                                    type="date"
                                    name="fecha"
                                    id="fecha"
                                    class="form-control @error('fecha') is-invalid @enderror"
                                    value="{{ old('fecha', \Carbon\Carbon::parse($kilometraje->fecha)->format('Y-m-d')) }}"
                                    required
                                >
                                @error('fecha')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror

                                <small class="text-muted">
                                    Si cambias la fecha, el sistema recalcula recorridos.
                                </small>
                            </div>
                        </div>

                        <!-- Kilometraje Reportado -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="kilometraje_reportado">Kilometraje Actual</label>
                                <input
                                    type="number"
                                    name="kilometraje_reportado"
                                    id="kilometraje_reportado"
                                    class="form-control @error('kilometraje_reportado') is-invalid @enderror"
                                    value="{{ old('kilometraje_reportado', $kilometraje->kilometraje_reportado) }}"
                                    min="0"
                                    required
                                >
                                @error('kilometraje_reportado')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror

                                @if($anterior)
                                    <small class="text-muted">
                                        Anterior:
                                        {{ number_format($anterior->kilometraje_reportado) }}
                                        km ({{ \Carbon\Carbon::parse($anterior->fecha)->format('d-m-Y') }})
                                    </small>
                                @else
                                    <small class="text-muted">
                                        No hay registro anterior (este sería el primero).
                                    </small>
                                @endif
                            </div>
                        </div>

                        <!-- Kilómetros Recorridos (informativo) -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Kilómetros Recorridos</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    value="{{ $kilometraje->kilometros_recorridos !== null ? number_format($kilometraje->kilometros_recorridos) . ' km' : 'Se recalculará automáticamente' }}"
                                    disabled
                                >
                                <small class="text-muted">
                                    Este campo se calcula con base en el registro anterior.
                                </small>
                            </div>
                        </div>

                    </div>

                    <div class="row">
                        <!-- Observaciones -->
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="observaciones">Observaciones</label>
                                <textarea
                                    name="observaciones"
                                    id="observaciones"
                                    rows="3"
                                    class="form-control @error('observaciones') is-invalid @enderror"
                                >{{ old('observaciones', $kilometraje->observaciones) }}</textarea>
                                @error('observaciones')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-warning">
                                <i class="fa-solid fa-check"></i> Guardar Cambios
                            </button>

                            <a href="{{ route('patrullas.kilometrajes.index', $patrulla->id) }}"
                               class="btn btn-secondary">
                                <i class="fa-solid fa-ban"></i> Cancelar
                            </a>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>
@stop


@section('css')
<style>
    .form-group label {
        font-weight: bold;
    }
</style>
@stop


@section('js')
@if ($errors->any())
<script>
    Swal.fire({
        icon: 'error',
        title: 'Errores en el formulario',
        html: `
            <ul style="text-align:left;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        `,
        confirmButtonText: 'Aceptar'
    });
</script>
@endif
@stop
