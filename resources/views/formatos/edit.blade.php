@extends('adminlte::page')

@section('title', 'Editar Formato')

@section('content_header')
    <h1>Editar Formato</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Actualizar Datos del Formato</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('formatos.update', $formato->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT') <!-- Para actualizar usamos PUT -->

                        <div class="row">
                            <!-- Nombre del Formato -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombre del Formato</label>
                                    <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre', $formato->nombre) }}" placeholder="Ingrese el nombre del formato" required>
                                    @error('nombre')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Descripción del Formato -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="descripcion">Descripción</label>
                                    <textarea name="descripcion" id="descripcion" rows="3" class="form-control @error('descripcion') is-invalid @enderror"
                                              placeholder="Ingrese una descripción del formato">{{ old('descripcion', $formato->descripcion) }}</textarea>
                                    @error('descripcion')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Archivo PDF del Formato -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="archivo_pdf">Archivo PDF</label>
                                    <input type="file" name="archivo_pdf" id="archivo_pdf" class="form-control @error('archivo_pdf') is-invalid @enderror">
                                    <small class="text-muted">Deje este campo vacío si no desea cambiar el archivo.</small>
                                    @error('archivo_pdf')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Archivo actual (si existe) -->
                            @if ($formato->archivo_pdf)
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Archivo Actual</label>
                                        <p><a href="{{ Storage::url($formato->archivo_pdf) }}" target="_blank">Ver archivo actual</a></p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <hr>
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-check"></i> Guardar Cambios
                                </button>
                                <a href="{{ route('formatos.index') }}" class="btn btn-secondary">
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
