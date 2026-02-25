@extends('adminlte::page')

@section('title', 'Nuevo Kilometraje')

@section('content_header')
    <h1>Nuevo Registro de Kilometraje</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    Patrulla {{ $patrulla->numero_economico }}
                </h3>
            </div>

            <div class="card-body">
                <form action="{{ route('patrullas.kilometrajes.store', $patrulla->id) }}" method="POST">
                    @csrf

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
                                    value="{{ old('fecha', now()->format('Y-m-d')) }}"
                                    required
                                >
                                @error('fecha')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
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
                                    value="{{ old('kilometraje_reportado') }}"
                                    min="0"
                                    required
                                >
                                @error('kilometraje_reportado')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror

                                @if($ultimo)
                                    <small class="text-muted">
                                        Último registrado:
                                        {{ number_format($ultimo->kilometraje_reportado) }}
                                        km ({{ \Carbon\Carbon::parse($ultimo->fecha)->format('d-m-Y') }})
                                    </small>
                                @endif
                            </div>
                        </div>

                        <!-- Kilómetros Recorridos (solo informativo) -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Kilómetros Recorridos</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    value="Se calculará automáticamente"
                                    disabled
                                >
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
                                >{{ old('observaciones') }}</textarea>
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
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-check"></i> Registrar
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
