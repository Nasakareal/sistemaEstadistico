@extends('adminlte::page')

@section('title', 'Detalle de Comunicación')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-0">
                <i class="fa-solid fa-envelope-open-text"></i>
                Detalle de Comunicación
            </h1>
            <small class="text-muted">
                Consulta del mensaje, aviso u orden
            </small>
        </div>

        <a href="{{ route('comunicaciones.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            Regresar
        </a>
    </div>
@stop

@section('content')

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa-solid fa-circle-check mr-1"></i>
            {{ session('success') }}

            <button type="button"
                    class="close"
                    data-dismiss="alert"
                    aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>No se pudo completar la operación.</strong>

            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">

        <div class="col-lg-8">

            <div class="card card-outline
                @if ($comunicacion->tipo === 'orden')
                    card-danger
                @elseif ($comunicacion->tipo === 'aviso')
                    card-warning
                @else
                    card-info
                @endif
            ">

                <div class="card-header">

                    <h3 class="card-title">
                        @if ($comunicacion->tipo === 'orden')
                            <i class="fa-solid fa-bullhorn mr-1 text-danger"></i>
                            Orden
                        @elseif ($comunicacion->tipo === 'aviso')
                            <i class="fa-solid fa-bell mr-1 text-warning"></i>
                            Aviso
                        @else
                            <i class="fa-solid fa-comment mr-1 text-info"></i>
                            Mensaje
                        @endif
                    </h3>

                    <div class="card-tools">

                        @if ($comunicacion->tipo === 'orden')
                            <span class="badge badge-danger">
                                ORDEN
                            </span>
                        @elseif ($comunicacion->tipo === 'aviso')
                            <span class="badge badge-warning">
                                AVISO
                            </span>
                        @else
                            <span class="badge badge-info">
                                MENSAJE
                            </span>
                        @endif

                    </div>

                </div>

                <div class="card-body">

                    <h3 class="mb-3">
                        {{ $comunicacion->asunto }}
                    </h3>

                    <div class="row mb-4">

                        <div class="col-md-6">
                            <div class="dato-label">
                                Remitente
                            </div>

                            <div class="dato-valor">
                                <i class="fa-solid fa-user mr-1"></i>
                                {{ $comunicacion->remitente->nombre_completo
                                    ?? $comunicacion->remitente->name
                                    ?? 'Usuario' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="dato-label">
                                Fecha de envío
                            </div>

                            <div class="dato-valor">
                                <i class="fa-regular fa-clock mr-1"></i>

                                @if ($comunicacion->enviado_at)
                                    {{ $comunicacion->enviado_at->format('d/m/Y H:i') }}
                                @else
                                    -
                                @endif
                            </div>
                        </div>

                    </div>

                    <hr>

                    @if (trim((string) $comunicacion->contenido) !== '')
                        <div class="contenido-comunicacion">
                            {!! nl2br(e($comunicacion->contenido)) !!}
                        </div>
                    @endif

                    @if ($comunicacion->adjuntos->count())
                        <div class="adjuntos-comunicacion {{ trim((string) $comunicacion->contenido) !== '' ? 'mt-4' : '' }}">

                            <div class="adjuntos-titulo">
                                <i class="fa-regular fa-images mr-1"></i>
                                Imágenes adjuntas
                                <span class="badge badge-secondary ml-1">
                                    {{ $comunicacion->adjuntos->count() }}
                                </span>
                            </div>

                            <div class="galeria-adjuntos">

                                @foreach ($comunicacion->adjuntos as $adjunto)

                                    @if ($adjunto->esImagen())

                                        <a href="{{ route('comunicaciones.adjuntos.show', $adjunto) }}"
                                           target="_blank"
                                           class="adjunto-imagen"
                                           title="{{ $adjunto->nombre_original }}">

                                            <img src="{{ route('comunicaciones.adjuntos.show', $adjunto) }}"
                                                 alt="{{ $adjunto->nombre_original }}"
                                                 loading="lazy">

                                            <div class="adjunto-overlay">
                                                <i class="fa-solid fa-magnifying-glass-plus"></i>
                                            </div>

                                        </a>

                                    @endif

                                @endforeach

                            </div>

                        </div>
                    @endif

                </div>

                @if (!$esRemitente && $registroDestinatario)

                    <div class="card-footer">

                        @if ($comunicacion->requiere_enterado)

                            @if ($registroDestinatario->enterado_at)

                                <div class="alert alert-success mb-0">
                                    <i class="fa-solid fa-check-double mr-1"></i>

                                    Confirmaste de enterado el
                                    <strong>
                                        {{ $registroDestinatario->enterado_at->format('d/m/Y H:i') }}
                                    </strong>.
                                </div>

                            @else

                                <form action="{{ route('comunicaciones.enterado', $comunicacion) }}"
                                      method="POST"
                                      id="formEnterado">

                                    @csrf

                                    <button type="button"
                                            class="btn btn-success btn-lg btn-block"
                                            id="btnEnterado">

                                        <i class="fa-solid fa-check-double mr-1"></i>
                                        ENTERADO

                                    </button>

                                </form>

                            @endif

                        @elseif ($registroDestinatario->leido_at)

                            <div class="text-muted">
                                <i class="fa-solid fa-check mr-1"></i>
                                Comunicación leída el
                                {{ $registroDestinatario->leido_at->format('d/m/Y H:i') }}.
                            </div>

                        @endif

                    </div>

                @endif

            </div>

        </div>

        <div class="col-lg-4">

            <div class="card card-outline card-secondary">

                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-circle-info mr-1"></i>
                        Información
                    </h3>
                </div>

                <div class="card-body">

                    <div class="resumen-item">
                        <span>Tipo</span>

                        <strong>
                            {{ ucfirst($comunicacion->tipo) }}
                        </strong>
                    </div>

                    <div class="resumen-item">
                        <span>Alcance</span>

                        <strong>
                            @switch($comunicacion->alcance)

                                @case('todos')
                                    Todo el personal
                                    @break

                                @case('unidad')
                                    {{ $comunicacion->unidad->nombre ?? 'Unidad' }}
                                    @break

                                @case('unidad_turno')
                                    {{ $comunicacion->unidad->nombre ?? 'Unidad' }}
                                    /
                                    {{ $comunicacion->turno->nombre ?? 'Turno' }}
                                    @break

                                @case('subdirectores')
                                    Todos los Subdirectores
                                    @break

                                @case('rol')
                                    {{ $comunicacion->role->name ?? 'Rol' }}
                                    @break

                                @case('usuario')
                                    {{ $comunicacion->destinatario->nombre_completo
                                        ?? $comunicacion->destinatario->name
                                        ?? 'Usuario' }}
                                    @break

                                @default
                                    {{ $comunicacion->alcance }}

                            @endswitch
                        </strong>
                    </div>

                    <div class="resumen-item">
                        <span>Requiere enterado</span>

                        <strong>
                            {{ $comunicacion->requiere_enterado ? 'Sí' : 'No' }}
                        </strong>
                    </div>

                    @if (!$esRemitente && $registroDestinatario)

                        <div class="resumen-item">
                            <span>Leído</span>

                            <strong>
                                @if ($registroDestinatario->leido_at)
                                    {{ $registroDestinatario->leido_at->format('d/m/Y H:i') }}
                                @else
                                    Pendiente
                                @endif
                            </strong>
                        </div>

                        @if ($comunicacion->requiere_enterado)

                            <div class="resumen-item">
                                <span>Enterado</span>

                                <strong>
                                    @if ($registroDestinatario->enterado_at)
                                        {{ $registroDestinatario->enterado_at->format('d/m/Y H:i') }}
                                    @else
                                        Pendiente
                                    @endif
                                </strong>
                            </div>

                        @endif

                    @endif

                </div>

            </div>

        </div>

    </div>

    @if ($esRemitente)

        <div class="card card-outline card-primary">

            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-users mr-1"></i>
                    Destinatarios
                </h3>

                <div class="card-tools">
                    <span class="badge badge-secondary">
                        {{ $comunicacion->destinatarios->count() }}
                        destinatarios
                    </span>
                </div>
            </div>

            <div class="card-body p-0">

                @if ($comunicacion->destinatarios->count())

                    <div class="table-responsive">

                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Unidad</th>
                                    <th>Turno</th>
                                    <th>Lectura</th>

                                    @if ($comunicacion->requiere_enterado)
                                        <th>Enterado</th>
                                    @endif

                                    <th>Estado</th>
                                </tr>
                            </thead>

                            <tbody>

                                @foreach ($comunicacion->destinatarios as $registro)

                                    <tr>

                                        <td>
                                            <strong>
                                                {{ $registro->usuario->nombre_completo
                                                    ?? $registro->usuario->name
                                                    ?? 'Usuario' }}
                                            </strong>
                                        </td>

                                        <td>
                                            {{ $registro->usuario->unidad->nombre ?? 'Sin unidad' }}
                                        </td>

                                        <td>
                                            {{ $registro->usuario->turno->nombre ?? 'Sin turno' }}
                                        </td>

                                        <td>
                                            @if ($registro->leido_at)
                                                {{ $registro->leido_at->format('d/m/Y H:i') }}
                                            @else
                                                <span class="text-muted">
                                                    Pendiente
                                                </span>
                                            @endif
                                        </td>

                                        @if ($comunicacion->requiere_enterado)

                                            <td>
                                                @if ($registro->enterado_at)
                                                    {{ $registro->enterado_at->format('d/m/Y H:i') }}
                                                @else
                                                    <span class="text-muted">
                                                        Pendiente
                                                    </span>
                                                @endif
                                            </td>

                                        @endif

                                        <td>

                                            @if (
                                                $comunicacion->requiere_enterado
                                                && $registro->enterado_at
                                            )

                                                <span class="badge badge-success">
                                                    <i class="fa-solid fa-check-double"></i>
                                                    Enterado
                                                </span>

                                            @elseif ($registro->leido_at)

                                                @if ($comunicacion->requiere_enterado)

                                                    <span class="badge badge-warning">
                                                        <i class="fa-solid fa-eye"></i>
                                                        Leído
                                                    </span>

                                                @else

                                                    <span class="badge badge-success">
                                                        <i class="fa-solid fa-check"></i>
                                                        Leído
                                                    </span>

                                                @endif

                                            @else

                                                <span class="badge badge-danger">
                                                    <i class="fa-solid fa-clock"></i>
                                                    Pendiente
                                                </span>

                                            @endif

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>
                        </table>

                    </div>

                @else

                    <div class="text-center text-muted py-5">
                        No hay destinatarios registrados.
                    </div>

                @endif

            </div>

        </div>

    @endif

@stop

@section('css')
<style>

    .contenido-comunicacion {
        min-height: 180px;
        padding: 20px;
        border-radius: 8px;
        background: rgba(255, 255, 255, .035);
        border: 1px solid rgba(255, 255, 255, .08);
        font-size: 1rem;
        line-height: 1.7;
        white-space: normal;
    }

    .dato-label {
        color: #94a3b8;
        font-size: .82rem;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .dato-valor {
        font-weight: 600;
    }

    .resumen-item {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 15px;
        padding: 11px 0;
        border-bottom: 1px solid rgba(255, 255, 255, .08);
    }

    .resumen-item:last-child {
        border-bottom: 0;
    }

    .resumen-item span {
        color: #94a3b8;
    }

    .resumen-item strong {
        text-align: right;
    }

    .adjuntos-comunicacion {
        width: 100%;
    }

    .adjuntos-titulo {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
        font-weight: 600;
        color: #e5e7eb;
    }

    .galeria-adjuntos {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
    }

    .adjunto-imagen {
        position: relative;
        display: block;
        height: 180px;
        overflow: hidden;
        border-radius: 9px;
        border: 1px solid rgba(255, 255, 255, .12);
        background: #0b1220;
        box-shadow: 0 3px 12px rgba(0, 0, 0, .20);
    }

    .adjunto-imagen img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .2s ease;
    }

    .adjunto-imagen:hover img {
        transform: scale(1.04);
    }

    .adjunto-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, .35);
        color: #ffffff;
        font-size: 24px;
        opacity: 0;
        transition: opacity .2s ease;
    }

    .adjunto-imagen:hover .adjunto-overlay {
        opacity: 1;
    }

    table th,
    table td {
        vertical-align: middle !important;
    }

    @media (max-width: 767px) {

        .contenido-comunicacion {
            min-height: 120px;
            padding: 15px;
        }

        .galeria-adjuntos {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .adjunto-imagen {
            height: 140px;
        }

    }

    @media (max-width: 400px) {

        .galeria-adjuntos {
            grid-template-columns: 1fr;
        }

        .adjunto-imagen {
            height: 200px;
        }

    }

</style>
@stop

@section('js')
<script>

    $(function () {

        $('#btnEnterado').on('click', function () {

            Swal.fire({
                title: '¿Confirmar de enterado?',
                text: 'Se registrará la fecha y hora de tu confirmación.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, estoy enterado',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {

                if (result.isConfirmed) {

                    $('#btnEnterado')
                        .prop('disabled', true)
                        .html(
                            '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Registrando...'
                        );

                    $('#formEnterado').submit();
                }

            });

        });

    });

    @if (session('success'))

        Swal.fire({
            icon: 'success',
            title: @json(session('success')),
            showConfirmButton: false,
            timer: 2500
        });

    @endif

</script>
@stop
