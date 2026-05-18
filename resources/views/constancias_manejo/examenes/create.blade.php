@extends('adminlte::page')

@section('title', 'Nuevo Examen de Manejo')

@section('content_header')
    <h1>Nuevo Examen de Manejo</h1>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Datos del examen</h3>
                </div>

                <form method="POST" action="{{ route('constancias_manejo.examenes.store') }}">
                    @csrf

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Módulo evaluador</label>
                                    <select name="modulo_id" class="form-control @error('modulo_id') is-invalid @enderror" required>
                                        <option value="">Seleccione</option>
                                        @foreach($modulos as $modulo)
                                            <option value="{{ $modulo->id }}" {{ old('modulo_id') == $modulo->id ? 'selected' : '' }}>
                                                {{ $modulo->nombre }} - {{ $modulo->tipo }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('modulo_id')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Modalidad</label>
                                    <select name="modalidad" class="form-control @error('modalidad') is-invalid @enderror" required>
                                        <option value="LINEA" {{ old('modalidad', 'LINEA') === 'LINEA' ? 'selected' : '' }}>En línea</option>
                                        <option value="IMPRESO" {{ old('modalidad') === 'IMPRESO' ? 'selected' : '' }}>Impreso</option>
                                    </select>
                                    @error('modalidad')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nombre del solicitante</label>
                                    <input type="text" name="nombre_solicitante" class="form-control @error('nombre_solicitante') is-invalid @enderror" value="{{ old('nombre_solicitante') }}" required>
                                    @error('nombre_solicitante')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Sexo</label>
                                    <select name="sexo" class="form-control @error('sexo') is-invalid @enderror" required>
                                        <option value="">Seleccione</option>
                                        <option value="HOMBRE" {{ old('sexo') === 'HOMBRE' ? 'selected' : '' }}>Hombre</option>
                                        <option value="MUJER" {{ old('sexo') === 'MUJER' ? 'selected' : '' }}>Mujer</option>
                                    </select>
                                    @error('sexo')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Tipo de licencia</label>
                                    <select name="tipo_licencia" class="form-control @error('tipo_licencia') is-invalid @enderror" required>
                                        <option value="">Seleccione</option>
                                        @foreach($tiposLicencia as $valor => $label)
                                            <option value="{{ $valor }}" {{ old('tipo_licencia') === $valor ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('tipo_licencia')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>CURP</label>
                                    <input type="text" name="curp" class="form-control @error('curp') is-invalid @enderror" maxlength="18" value="{{ old('curp') }}">
                                    @error('curp')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Teléfono</label>
                                    <input type="text" name="telefono" class="form-control @error('telefono') is-invalid @enderror" value="{{ old('telefono') }}">
                                    @error('telefono')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer text-right">
                        <a href="{{ route('constancias_manejo.examenes.index') }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-qrcode"></i> Generar examen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop
