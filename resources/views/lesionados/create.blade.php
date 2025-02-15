@extends('adminlte::page')

@section('title', 'Agregar Lesionado al Hecho')

@section('content_header')
    <h1>Agregar Lesionado al Hecho: {{ $hecho->folio_c5i }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Datos del Lesionado</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('lesionados.store', $hecho->id) }}" method="POST">
                        @csrf

                        <div class="row">
                            <!-- Nombre -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombre Completo</label>
                                    <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" 
                                           value="{{ old('nombre') }}" placeholder="Ingrese el nombre del lesionado" required>
                                    @error('nombre')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Edad -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="edad">Edad</label>
                                    <input type="number" name="edad" id="edad" class="form-control @error('edad') is-invalid @enderror" 
                                           value="{{ old('edad') }}" placeholder="Ingrese la edad" min="0">
                                    @error('edad')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Sexo -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="sexo">Sexo</label>
                                    <select name="sexo" id="sexo" class="form-control @error('sexo') is-invalid @enderror" required>
                                        <option value="" disabled selected>Seleccione</option>
                                        <option value="Masculino" {{ old('sexo') == 'Masculino' ? 'selected' : '' }}>Masculino</option>
                                        <option value="Femenino" {{ old('sexo') == 'Femenino' ? 'selected' : '' }}>Femenino</option>
                                        <option value="Otro" {{ old('sexo') == 'Otro' ? 'selected' : '' }}>Otro</option>
                                    </select>
                                    @error('sexo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Tipo de Lesión -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tipo_lesion">Tipo de Lesión</label>
                                    <select name="tipo_lesion" id="tipo_lesion" class="form-control @error('tipo_lesion') is-invalid @enderror" required>
                                        <option value="" disabled selected>Seleccione</option>
                                        <option value="Leve" {{ old('tipo_lesion') == 'Leve' ? 'selected' : '' }}>Leve</option>
                                        <option value="Moderada" {{ old('tipo_lesion') == 'Moderada' ? 'selected' : '' }}>Moderada</option>
                                        <option value="Grave" {{ old('tipo_lesion') == 'Grave' ? 'selected' : '' }}>Grave</option>
                                        <option value="Fallecido" {{ old('tipo_lesion') == 'Fallecido' ? 'selected' : '' }}>Fallecido</option>
                                    </select>
                                    @error('tipo_lesion')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Hospitalizado -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="hospitalizado">¿Hospitalizado?</label>
                                    <select name="hospitalizado" id="hospitalizado" class="form-control @error('hospitalizado') is-invalid @enderror" required>
                                        <option value="1" {{ old('hospitalizado') == '1' ? 'selected' : '' }}>Sí</option>
                                        <option value="0" {{ old('hospitalizado') == '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                    @error('hospitalizado')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Hospital -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="hospital">Nombre del Hospital (si aplica)</label>
                                    <input type="text" name="hospital" id="hospital" class="form-control @error('hospital') is-invalid @enderror" 
                                           value="{{ old('hospital') }}" placeholder="Ingrese el hospital">
                                    @error('hospital')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Atención en el Lugar -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="atencion_en_sitio">¿Recibió atención en el sitio?</label>
                                    <select name="atencion_en_sitio" id="atencion_en_sitio" class="form-control @error('atencion_en_sitio') is-invalid @enderror" required>
                                        <option value="1" {{ old('atencion_en_sitio') == '1' ? 'selected' : '' }}>Sí</option>
                                        <option value="0" {{ old('atencion_en_sitio') == '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                    @error('atencion_en_sitio')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Ambulancia -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="ambulancia">Número o Nombre de la Ambulancia</label>
                                    <input type="text" name="ambulancia" id="ambulancia" class="form-control @error('ambulancia') is-invalid @enderror" 
                                           value="{{ old('ambulancia') }}" placeholder="Ingrese la ambulancia">
                                    @error('ambulancia')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Paramédico -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="paramedico">Nombre del Paramédico</label>
                                    <input type="text" name="paramedico" id="paramedico" class="form-control @error('paramedico') is-invalid @enderror" 
                                           value="{{ old('paramedico') }}" placeholder="Ingrese el nombre del paramédico">
                                    @error('paramedico')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div class="form-group">
                            <label for="observaciones">Observaciones</label>
                            <textarea name="observaciones" id="observaciones" class="form-control @error('observaciones') is-invalid @enderror" 
                                      placeholder="Ingrese observaciones">{{ old('observaciones') }}</textarea>
                            @error('observaciones')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <!-- Botones -->
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-check"></i> Registrar
                            </button>
                            <a href="{{ route('lesionados.index', $hecho->id) }}" class="btn btn-secondary">
                                <i class="fa-solid fa-ban"></i> Cancelar
                            </a>
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
