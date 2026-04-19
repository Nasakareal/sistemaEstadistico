@extends('adminlte::page')

@section('title', 'Detalles de la Grúa')

@section('content_header')
    <h1>Detalles de la Grúa</h1>
@stop

@section('content')
    @php
        $esSiniestros = $grua->unidades->contains('id', 1);
        $esDelegaciones = $grua->delegaciones->isNotEmpty();
    @endphp

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Datos Registrados</h3>
                </div>
                <div class="card-body">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nombre</label>
                                <p class="form-control-static">{{ $grua->nombre }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Dirección</label>
                                <p class="form-control-static">{{ $grua->direccion ?? 'No especificada' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Ubicación del corralón</label>
                                <p class="form-control-static">{{ $grua->ubicacion_corralon ?? 'No especificada' }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Teléfono</label>
                                <p class="form-control-static">{{ $grua->telefono ?? 'No especificado' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Correo Electrónico</label>
                                <p class="form-control-static">{{ $grua->email ?? 'No especificado' }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tipo de asignación</label>
                                <p class="form-control-static">
                                    @if($esSiniestros)
                                        <span class="badge badge-primary">SINIESTROS</span>
                                    @elseif($esDelegaciones)
                                        <span class="badge badge-success">DELEGACIONES</span>
                                    @else
                                        <span class="badge badge-secondary">SIN ASIGNACIÓN</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    @if($esDelegaciones)
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Delegaciones</label>
                                    <div>
                                        @foreach($grua->delegaciones as $delegacion)
                                            <span class="badge badge-info mr-1 mb-1">
                                                {{ $delegacion->nombre }}
                                                @if($delegacion->clave)
                                                    ({{ $delegacion->clave }})
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha de Registro</label>
                                <p class="form-control-static">
                                    {{ $grua->created_at ? $grua->created_at->format('d-m-Y') : 'No disponible' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-12 text-center">
                            <a href="{{ route('gruas.index') }}" class="btn btn-secondary">
                                <i class="fa-solid fa-arrow-left"></i> Volver
                            </a>
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
    <script>
        console.log("Vista de detalles de la grúa cargada correctamente.");
    </script>
@stop
