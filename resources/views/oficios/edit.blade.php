@extends('adminlte::page')

@section('title', 'Editar Oficio')

@section('content_header')
    <h1>Editar Oficio</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Actualizar Datos del Oficio</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('oficios.update', $oficio->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Número de Oficio -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="numero_oficio">Número de Oficio</label>
                                    <input type="text" name="numero_oficio" id="numero_oficio" 
                                           class="form-control @error('numero_oficio') is-invalid @enderror"
                                           value="{{ old('numero_oficio', $oficio->numero_oficio) }}" required>
                                    @error('numero_oficio')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Descripción -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="descripcion">Descripción</label>
                                    <textarea name="descripcion" id="descripcion" rows="3" 
                                              class="form-control @error('descripcion') is-invalid @enderror"
                                              placeholder="Ingrese una breve descripción">{{ old('descripcion', $oficio->descripcion) }}</textarea>
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
                                           class="form-control @error('pdf_path') is-invalid @enderror">
                                    @error('pdf_path')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror

                                    @if ($oficio->pdf_path)
                                        <p class="mt-2">
                                            Archivo actual: 
                                            <a href="{{ asset('storage/' . $oficio->pdf_path) }}" target="_blank">Ver archivo PDF</a>
                                        </p>
                                    @endif
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

                                    @if ($oficio->fotos)
                                        <div class="d-flex flex-wrap mt-2">
                                            @foreach ($oficio->fotos as $foto)
                                                <div class="m-2">
                                                    <img src="{{ asset('storage/' . $foto) }}" alt="Foto del Oficio" class="img-thumbnail" width="150">
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-check"></i> Guardar Cambios
                                </button>
                                <a href="{{ route('oficios.index') }}" class="btn btn-secondary">
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
        .img-thumbnail {
            border: 1px solid #dee2e6;
            padding: 4px;
            max-height: 150px;
            object-fit: cover;
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
