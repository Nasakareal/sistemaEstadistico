@extends('adminlte::page')

@section('title', 'Registrar Oficio')

@section('content_header')
    <h1>Registro de un Nuevo Oficio</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('oficios.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <!-- Número de Oficio -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="numero_oficio">Número de Oficio</label>
                                    <input type="text" name="numero_oficio" id="numero_oficio" 
                                           class="form-control @error('numero_oficio') is-invalid @enderror"
                                           value="{{ old('numero_oficio') }}" required>
                                    @error('numero_oficio')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Descripción -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="descripcion">Descripción</label>
                                    <textarea name="descripcion" id="descripcion" rows="3" 
                                              class="form-control @error('descripcion') is-invalid @enderror"
                                              placeholder="Ingrese una breve descripción del oficio">{{ old('descripcion') }}</textarea>
                                    @error('descripcion')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Archivo PDF del Oficio -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pdf_path">Archivo del Oficio (PDF)</label>
                                    <input type="file" name="pdf_path" id="pdf_path" 
                                           class="form-control @error('pdf_path') is-invalid @enderror" required>
                                    @error('pdf_path')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Fotos del Oficio -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fotos">Fotos (máximo 3)</label>
                                    <input type="file" name="fotos[]" id="fotos" multiple 
                                           class="form-control @error('fotos') is-invalid @enderror">
                                    @error('fotos')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    @error('fotos.*')
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
                                    <a href="{{ route('oficios.index') }}" class="btn btn-secondary">
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
