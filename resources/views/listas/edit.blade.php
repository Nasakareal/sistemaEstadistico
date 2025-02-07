@extends('adminlte::page')

@section('title', 'Editar Pase de Lista')

@section('content_header')
    <h1>Editar Pase de Lista</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Actualizar Datos del Pase de Lista</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('listas.update', $lista->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT') <!-- Para actualizar usamos PUT -->

                        <!-- Área -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="area">Área</label>
                                    <select name="area" id="area" class="form-control @error('area') is-invalid @enderror" required>
                                        <option value="">Seleccione un área</option>
                                        @foreach ($areasDisponibles as $area)
                                            <option value="{{ $area }}" {{ old('area', $lista->area) == $area ? 'selected' : '' }}>
                                                {{ $area }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('area')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Total de asistentes -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="total_asistentes">Total de Asistentes</label>
                                    <input type="number" name="total_asistentes" id="total_asistentes" class="form-control @error('total_asistentes') is-invalid @enderror"
                                           value="{{ old('total_asistentes', $lista->total_asistentes) }}" placeholder="Ingrese el total de asistentes">
                                    @error('total_asistentes')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Fotos -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="foto_1">Foto 1</label>
                                    <input type="file" name="foto_1" id="foto_1" class="form-control @error('foto_1') is-invalid @enderror">
                                    @error('foto_1')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="foto_2">Foto 2</label>
                                    <input type="file" name="foto_2" id="foto_2" class="form-control @error('foto_2') is-invalid @enderror">
                                    @error('foto_2')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="observaciones">Observaciones</label>
                                    <textarea name="observaciones" id="observaciones" class="form-control @error('observaciones') is-invalid @enderror" rows="3"
                                              placeholder="Escriba observaciones adicionales">{{ old('observaciones', $lista->observaciones) }}</textarea>
                                    @error('observaciones')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-check"></i> Guardar Cambios
                                </button>
                                <a href="{{ route('listas.index') }}" class="btn btn-secondary">
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
    <script>
        @if (session('success'))
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 1500
            });
        @endif

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Error en el formulario',
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
