@extends('adminlte::page')

@section('title', 'Detalles del Oficio')

@section('content_header')
    <h1>Detalles del Oficio</h1>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Datos Registrados</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Número de Oficio -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="numero_oficio">Número de Oficio</label>
                                <p class="form-control-static">{{ $oficio->numero_oficio }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Descripción -->
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="descripcion">Descripción</label>
                                <p class="form-control-static">{{ $oficio->descripcion }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Fecha de Creación -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_creacion">Fecha de Registro</label>
                                <p class="form-control-static">{{ $oficio->created_at->format('d-m-Y') }}</p>
                            </div>
                        </div>
                    </div>

                    @if ($oficio->pdf_path)
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Archivo del Oficio</label>
                                    <p><a href="{{ asset('storage/' . $oficio->pdf_path) }}" target="_blank">Ver archivo PDF</a></p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($oficio->fotos)
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Fotos del Oficio</label>
                                    <div class="d-flex flex-wrap">
                                        @foreach ($oficio->fotos as $foto)
                                            <div class="m-2">
                                                <img src="{{ asset('storage/' . $foto) }}" alt="Foto del Oficio" class="img-thumbnail" width="150">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <hr>
                    <div class="row">
                        <!-- Botón de regreso -->
                        <div class="col-md-12 text-center">
                            <div class="form-group">
                                <a href="{{ route('oficios.index') }}" class="btn btn-secondary">
                                    <i class="fa-solid fa-arrow-left"></i> Volver
                                </a>
                            </div>
                        </div>
                    </div>
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
        .form-control-static {
            display: block;
            font-size: 1rem;
            margin-top: 0.5rem;
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
    <script> console.log("Vista de detalles del oficio cargada correctamente."); </script>
@stop
