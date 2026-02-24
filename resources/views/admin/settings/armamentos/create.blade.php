@extends('adminlte::page')

@section('title', 'Registrar Armamento')

@section('content_header')
    <h1>Registro de Armamento</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>

                <div class="card-body">
                    <form action="{{ route('armamentos.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <!-- Unidad -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="unidad_id">Unidad</label>
                                    <select
                                        name="unidad_id"
                                        id="unidad_id"
                                        class="form-control @error('unidad_id') is-invalid @enderror"
                                        required
                                    >
                                        <option value="" disabled selected>Seleccione una unidad</option>
                                        @foreach ($unidades as $u)
                                            <option value="{{ $u->id }}" {{ old('unidad_id') == $u->id ? 'selected' : '' }}>
                                                {{ $u->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('unidad_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Estatus -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="estatus">Estatus</label>
                                    <select
                                        name="estatus"
                                        id="estatus"
                                        class="form-control @error('estatus') is-invalid @enderror"
                                        required
                                    >
                                        <option value="" disabled {{ old('estatus') ? '' : 'selected' }}>Seleccione</option>
                                        <option value="ACTIVO" {{ old('estatus', 'ACTIVO') == 'ACTIVO' ? 'selected' : '' }}>ACTIVO</option>
                                        <option value="BAJA" {{ old('estatus') == 'BAJA' ? 'selected' : '' }}>BAJA</option>
                                        <option value="RESGUARDO" {{ old('estatus') == 'RESGUARDO' ? 'selected' : '' }}>RESGUARDO</option>
                                        <option value="MANTENIMIENTO" {{ old('estatus') == 'MANTENIMIENTO' ? 'selected' : '' }}>MANTENIMIENTO</option>
                                    </select>
                                    @error('estatus')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="text-muted">Estatus operativo actual del arma.</small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3"><strong>Datos del Armamento</strong></h5>

                        <div class="row">
                            <!-- Tipo -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tipo">Tipo</label>
                                    <input
                                        type="text"
                                        name="tipo"
                                        id="tipo"
                                        class="form-control @error('tipo') is-invalid @enderror"
                                        value="{{ old('tipo') }}"
                                        placeholder="Ej. ARMA CORTA / ARMA LARGA / ESCOPETA"
                                        required
                                    >
                                    @error('tipo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Clase -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="clase">Clase</label>
                                    <input
                                        type="text"
                                        name="clase"
                                        id="clase"
                                        class="form-control @error('clase') is-invalid @enderror"
                                        value="{{ old('clase') }}"
                                        placeholder="Ej. PISTOLA / RIFLE / REVOLVER"
                                    >
                                    @error('clase')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Calibre -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="calibre">Calibre</label>
                                    <input
                                        type="text"
                                        name="calibre"
                                        id="calibre"
                                        class="form-control @error('calibre') is-invalid @enderror"
                                        value="{{ old('calibre') }}"
                                        placeholder="Ej. 9MM / .40 / .223"
                                    >
                                    @error('calibre')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Matrícula -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="matricula">Matrícula</label>
                                    <input
                                        type="text"
                                        name="matricula"
                                        id="matricula"
                                        class="form-control @error('matricula') is-invalid @enderror"
                                        value="{{ old('matricula') }}"
                                        placeholder="Ej. UTM-00123"
                                    >
                                    @error('matricula')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="text-muted">No debe repetirse.</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Marca -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="marca">Marca</label>
                                    <input
                                        type="text"
                                        name="marca"
                                        id="marca"
                                        class="form-control @error('marca') is-invalid @enderror"
                                        value="{{ old('marca') }}"
                                        placeholder="Ej. GLOCK, BERETTA, COLT"
                                    >
                                    @error('marca')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Modelo -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="modelo">Modelo</label>
                                    <input
                                        type="text"
                                        name="modelo"
                                        id="modelo"
                                        class="form-control @error('modelo') is-invalid @enderror"
                                        value="{{ old('modelo') }}"
                                        placeholder="Ej. 17, 19X, M4A1..."
                                    >
                                    @error('modelo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Serie -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="serie">Serie</label>
                                    <input
                                        type="text"
                                        name="serie"
                                        id="serie"
                                        class="form-control @error('serie') is-invalid @enderror"
                                        value="{{ old('serie') }}"
                                        placeholder="Ej. ABC1234567"
                                    >
                                    @error('serie')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="text-muted">No debe repetirse.</small>
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
                                        class="form-control @error('observaciones') is-invalid @enderror"
                                        rows="3"
                                        placeholder="Opcional"
                                    >{{ old('observaciones') }}</textarea>
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
                                    <a href="{{ route('armamentos.index') }}" class="btn btn-secondary">
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
