@extends('adminlte::page')

@section('title', 'Detalles del Hecho')

@section('content_header')
    <h1>Detalles del Hecho</h1>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Información Registrada</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        {{-- Título del Hecho --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="titulo">Título</label>
                                <p class="form-control-static">{{ $hecho->titulo }}</p>
                            </div>
                        </div>
                        {{-- Fecha del Hecho --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha">Fecha</label>
                                <p class="form-control-static">{{ $hecho->fecha ?? 'No especificada' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Descripción --}}
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="descripcion">Descripción</label>
                                <p class="form-control-static">{{ $hecho->descripcion }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Área relacionada --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="area">Área Relacionada</label>
                                <p class="form-control-static">{{ $hecho->area ?? 'No especificada' }}</p>
                            </div>
                        </div>
                        {{-- Estado del hecho --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <p class="form-control-static">{{ $hecho->estado }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Responsable --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="responsable">Responsable</label>
                                <p class="form-control-static">{{ $hecho->responsable->nombre ?? 'No asignado' }}</p>
                            </div>
                        </div>
                        {{-- Evidencia adjunta --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="evidencia">Evidencia Adjunta</label>
                                @if ($hecho->evidencia)
                                    <div>
                                        <a href="{{ asset('storage/' . $hecho->evidencia) }}" target="_blank" class="btn btn-info">
                                            Ver Evidencia
                                        </a>
                                    </div>
                                @else
                                    <p class="form-control-static">No hay evidencia adjunta.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        {{-- Botón de regreso --}}
                        <div class="col-md-12 text-center">
                            <div class="form-group">
                                <a href="{{ route('hechos.index') }}" class="btn btn-secondary">
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
    </style>
@stop

@section('js')
    <script> console.log("Vista de detalles del hecho cargada correctamente."); </script>
@stop
