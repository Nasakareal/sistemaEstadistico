@extends('adminlte::page')

@section('title', 'Registrar Pase de Lista')

@section('content_header')
    <h1>Registro de un Nuevo Pase de Lista</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>
                <div class="card-body">
                    <!-- Formulario para registrar pase de lista -->
                    <form action="{{ route('listas.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Área -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="area">Área</label>

                                    @if ($rolUsuario === 'Administrador')
                                        <!-- Selección editable para el Administrador -->
                                        <select name="area" id="area" class="form-control @error('area') is-invalid @enderror" required>
                                            <option value="">Seleccione un área</option>
                                            @foreach ($areasDisponibles as $area)
                                                <option value="{{ $area }}" {{ old('area') == $area ? 'selected' : '' }}>{{ $area }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <!-- Área asignada al usuario común (bloqueada) -->
                                        <input type="text" name="area" id="area" class="form-control" value="{{ $areaUsuario }}" readonly>
                                    @endif

                                    @error('area')
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

                        <!-- Total de asistentes -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="total_asistentes">Total de Asistentes</label>
                                    <input type="number" name="total_asistentes" id="total_asistentes" class="form-control @error('total_asistentes') is-invalid @enderror"
                                           value="{{ old('total_asistentes') }}" placeholder="Ingrese el total de asistentes">
                                    @error('total_asistentes')
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
                                              placeholder="Escriba observaciones adicionales (opcional)">{{ old('observaciones') }}</textarea>
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
                            <div class="col-md-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-check"></i> Registrar
                                    </button>
                                    <a href="{{ route('listas.index') }}" class="btn btn-secondary">
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
        .form-group label {
            font-weight: bold;
        }
    </style>
@stop

@section('js')
    <script>
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
