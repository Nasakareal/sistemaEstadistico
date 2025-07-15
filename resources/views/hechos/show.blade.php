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
                        {{-- Calle --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="calle">Calle</label>
                                <p class="form-control-static">{{ $hecho->calle }}</p>
                            </div>
                        </div>
                        {{-- Color --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="colonia">Colonia</label>
                                <p class="form-control-static">{{ $hecho->colonia }}</p>
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

                    {{-- ---- ROW Responsbale y Evidencia ---- --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="responsable">Responsable</label>
                                <p class="form-control-static">{{ $hecho->responsable->nombre ?? 'No asignado' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="evidencia">Evidencia Adjunta</label>
                                @if ($hecho->evidencia)
                                    <a href="{{ asset('storage/' . $hecho->evidencia) }}" class="btn btn-info">Ver Evidencia</a>
                                @else
                                    <p class="form-control-static">No hay evidencia adjunta.</p>
                                @endif
                            </div>
                        </div>
                    </div> {{-- <-- CERRAMOS este .row correctamente --}}

                    {{-- ---- NUEVO ROW para Vehículos ---- --}}
                    <div class="row">
                        <div class="col-12">
                            <h3>Vehículos Asociados</h3>
                            @if($hecho->vehiculos->count())
                                <div class="row g-3">
                                    @foreach($hecho->vehiculos as $vehiculo)
                                        <div class="col-sm-6 col-md-4">
                                            <div class="card h-100">
                                                <div class="card-header text-center">
                                                    <strong>{{ $vehiculo->marca }} - {{ $vehiculo->modelo }}</strong>
                                                </div>
                                                <div class="card-body text-center d-flex flex-column justify-content-between">
                                                    @if($vehiculo->fotos)
                                                        <img src="{{ asset('storage/' . $vehiculo->fotos) }}"
                                                             class="img-thumbnail mb-2" style="width:100%;">
                                                    @else
                                                        <p class="text-muted">No hay foto disponible.</p>
                                                    @endif

                                                    @if ($vehiculo->corralon !== null)
                                                        <a href="{{ route('liberacion.publica', $vehiculo->id) }}"
                                                           class="btn btn-outline-primary btn-block mt-2">
                                                            Ver Liberación
                                                        </a>
                                                    @else
                                                        <p class="text-muted mt-2">No está en corralón</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p>No hay vehículos asociados a este hecho.</p>
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
        .btn-liberacion {
            white-space: normal !important;
            text-align: center;
        }

    </style>
@stop

@section('js')
    <script> console.log("Vista de detalles del hecho cargada correctamente."); </script>
@stop
