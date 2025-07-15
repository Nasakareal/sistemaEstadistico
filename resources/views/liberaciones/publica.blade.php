@extends('adminlte::page')

@section('title', 'Liberación del Vehículo')

@section('content_header')
    <h1>Estado de Liberación</h1>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Liberación Vía Pública</h3>
                </div>
                <div class="card-body">
                    <p><strong>Vehículo:</strong> {{ $vehiculo->marca }} - {{ $vehiculo->modelo }} - {{ $vehiculo->placas }}</p>

                    @if ($liberacion)
                        <div class="alert alert-success">
                            Este vehículo ya fue liberado.
                        </div>

                        <p><strong>Fecha de Liberación:</strong> {{ \Carbon\Carbon::parse($liberacion->fecha_liberacion)->format('d/m/Y') }}</p>
                        <p><strong>Personas autorizadas:</strong> {{ $liberacion->personas_autorizadas }}</p>
                    @else
                        <div class="alert alert-danger">
                            Este vehículo aún no ha sido liberado.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop
