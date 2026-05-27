@extends('adminlte::page')

@section('title', 'Detalle contacto de apoyo')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">{{ $redApoyo->institucion }}</h1>
            <p class="text-muted mb-0">{{ $redApoyo->region ?: 'Sin región' }} · {{ $redApoyo->nivel_gobierno ?: 'Sin nivel' }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('directorio_red_apoyo.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
            @can('editar directorio red apoyo')
                <a href="{{ route('directorio_red_apoyo.edit', $redApoyo) }}" class="btn btn-success">
                    <i class="fa-regular fa-pen-to-square"></i> Editar
                </a>
            @endcan
        </div>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php
        $delegacion = $redApoyo->delegacion;
        $telefono = preg_replace('/\D+/', '', (string) $redApoyo->telefono);
        $telefonoSecundario = preg_replace('/\D+/', '', (string) $redApoyo->telefono_secundario);
    @endphp

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-circle-info"></i> Información del contacto
                    </h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Región</dt>
                        <dd class="col-sm-8">{{ $redApoyo->region ?: optional($delegacion)->nombre ?: '—' }}</dd>

                        <dt class="col-sm-4">Adscripción</dt>
                        <dd class="col-sm-8">
                            @if($delegacion)
                                {{ $delegacion->nombre }}
                                @if($delegacion->padre)
                                    <span class="text-muted">({{ $delegacion->padre->nombre }})</span>
                                @endif
                            @elseif($redApoyo->destacamento)
                                {{ $redApoyo->destacamento->nombre }}
                            @else
                                {{ $redApoyo->nivel_gobierno === 'Estatal' ? 'General estatal' : 'Sin delegación' }}
                            @endif
                        </dd>

                        <dt class="col-sm-4">Nivel de gobierno</dt>
                        <dd class="col-sm-8">{{ $redApoyo->nivel_gobierno ?: '—' }}</dd>

                        <dt class="col-sm-4">Tipo de apoyo</dt>
                        <dd class="col-sm-8">{{ $redApoyo->tipo_apoyo ?: '—' }}</dd>

                        <dt class="col-sm-4">Institución</dt>
                        <dd class="col-sm-8">{{ $redApoyo->institucion }}</dd>

                        <dt class="col-sm-4">Encargado</dt>
                        <dd class="col-sm-8">{{ $redApoyo->contacto ?: '—' }}</dd>

                        <dt class="col-sm-4">Cargo</dt>
                        <dd class="col-sm-8">{{ $redApoyo->cargo ?: '—' }}</dd>

                        <dt class="col-sm-4">Municipio</dt>
                        <dd class="col-sm-8">{{ $redApoyo->municipio ?: '—' }}</dd>

                        <dt class="col-sm-4">Dirección</dt>
                        <dd class="col-sm-8">{{ $redApoyo->direccion ?: '—' }}</dd>

                        <dt class="col-sm-4">Destacamento relacionado</dt>
                        <dd class="col-sm-8">{{ optional($redApoyo->destacamento)->nombre ?: '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-phone"></i> Contacto
                    </h3>
                </div>
                <div class="card-body">
                    @if($telefono)
                        <a href="https://wa.me/{{ $telefono }}" target="_blank" class="btn btn-success btn-block mb-2">
                            <i class="fa-brands fa-whatsapp"></i> WhatsApp principal
                        </a>
                    @endif

                    @if($telefonoSecundario)
                        <a href="https://wa.me/{{ $telefonoSecundario }}" target="_blank" class="btn btn-outline-success btn-block mb-2">
                            <i class="fa-brands fa-whatsapp"></i> WhatsApp secundario
                        </a>
                    @endif

                    <p class="mb-1"><strong>Principal:</strong> {{ $redApoyo->telefono ?: '—' }}</p>
                    <p class="mb-0"><strong>Secundario:</strong> {{ $redApoyo->telefono_secundario ?: '—' }}</p>

                    <hr>

                    <p class="mb-1"><strong>Estado:</strong>
                        <span class="badge {{ $redApoyo->activo ? 'badge-success' : 'badge-secondary' }}">
                            {{ $redApoyo->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </p>
                    <p class="mb-0"><strong>Orden:</strong> {{ $redApoyo->orden }}</p>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-regular fa-note-sticky"></i> Observaciones
                    </h3>
                </div>
                <div class="card-body">
                    <p class="mb-0">{!! nl2br(e($redApoyo->observaciones ?: '—')) !!}</p>
                </div>
            </div>
        </div>
    </div>
@stop
