@extends('adminlte::page')

@section('title', 'Parte de Novedades')

@section('content_header')
    <h1>Parte de Novedades - {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</h1>
@stop

@section('content')
    <form method="GET" action="{{ route('estadisticas.parteNovedades') }}" class="mb-3">
        <div class="form-inline">
            <label for="fecha" class="mr-2">Selecciona la fecha:</label>
            <input type="date" name="fecha" id="fecha" class="form-control mr-2" value="{{ $fecha }}">
            <button type="submit" class="btn btn-primary">Consultar</button>
            <a href="{{ route('estadisticas.parteNovedades.descargar', ['fecha' => $fecha]) }}" class="btn btn-success ml-2">
                <i class="fas fa-file-word"></i> Descargar Word
            </a>

        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <strong>Hechos registrados entre {{ \Carbon\Carbon::parse($fecha)->subDay()->setTime(18, 0)->format('d/m/Y H:i') }}
            y {{ \Carbon\Carbon::parse($fecha)->setTime(18, 0)->format('d/m/Y H:i') }}</strong>
        </div>
        <div class="card-body">
            @forelse ($hechos as $hecho)
                <p>
                    <strong>{{ $hecho->created_at->format('H:i') }}</strong> - {{ $hecho->descripcion }}
                </p>
            @empty
                <p>No se registraron hechos en el rango indicado.</p>
            @endforelse
        </div>
    </div>
@stop
