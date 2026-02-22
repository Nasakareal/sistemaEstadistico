@extends('adminlte::page')

@section('title', 'Registrar Guardia SCT')

@section('content_header')
    <h1>Registro de una Nueva Guardia SCT</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>

                <div class="card-body">
                    <form action="{{ route('grua-guardias-sct.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <!-- Grúa -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="grua_id">Grúa</label>
                                    <select name="grua_id" id="grua_id"
                                            class="form-control @error('grua_id') is-invalid @enderror" required>
                                        <option value="">-- Seleccione una grúa --</option>
                                        @foreach($gruas as $grua)
                                            <option value="{{ $grua->id }}"
                                                {{ old('grua_id') == $grua->id ? 'selected' : '' }}>
                                                {{ $grua->nombre }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('grua_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Tramo -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tramo_id">Tramo</label>
                                    <select name="tramo_id" id="tramo_id"
                                            class="form-control @error('tramo_id') is-invalid @enderror">
                                        <option value="">GENERAL (sin tramo)</option>
                                        @foreach($tramos as $tramo)
                                            <option value="{{ $tramo->id }}"
                                                {{ old('tramo_id') == $tramo->id ? 'selected' : '' }}>
                                                {{ $tramo->carretera }} - {{ $tramo->nombre }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('tramo_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Día Inicio -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="dia_inicio">Día Inicio</label>
                                    <input type="number" name="dia_inicio" id="dia_inicio" min="1" max="31"
                                           class="form-control @error('dia_inicio') is-invalid @enderror"
                                           value="{{ old('dia_inicio') }}" placeholder="1 - 31" required>
                                    @error('dia_inicio')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Día Fin -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="dia_fin">Día Fin</label>
                                    <input type="number" name="dia_fin" id="dia_fin" min="1" max="31"
                                           class="form-control @error('dia_fin') is-invalid @enderror"
                                           value="{{ old('dia_fin') }}" placeholder="1 - 31" required>
                                    @error('dia_fin')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Activo -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="activo" name="activo"
                                               {{ old('activo', 1) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="activo">ACTIVO</label>
                                    </div>

                                    @error('activo')
                                        <span class="text-danger" style="font-size: 0.9rem;">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-check"></i> Registrar
                                    </button>

                                    <a href="{{ route('grua-guardias-sct.index') }}" class="btn btn-secondary">
                                        <i class="fa-solid fa-ban"></i> Cancelar
                                    </a>
                                </div>
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
    .form-group label { font-weight: bold; }
</style>
@stop

@section('js')
<script>
    $(document).on('submit', 'form', function (e) {
        let diaInicio = parseInt($('#dia_inicio').val() || '0', 10);
        let diaFin = parseInt($('#dia_fin').val() || '0', 10);

        if (diaInicio > 0 && diaFin > 0 && diaInicio > diaFin) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Rango inválido',
                text: 'El día de inicio no puede ser mayor que el día fin.',
                confirmButtonText: 'Aceptar'
            });
        }
    });

    @if ($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Errores en el formulario',
            html: `
                <ul style="text-align: left;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            `,
            confirmButtonText: 'Aceptar'
        });
    @endif
</script>
@stop
