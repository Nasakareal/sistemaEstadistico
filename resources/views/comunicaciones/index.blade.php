@extends('adminlte::page')

@section('title', 'Comunicaciones')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-0">
                <i class="fa-solid fa-comments"></i>
                Comunicaciones
            </h1>
            <small class="text-muted">
                Órdenes, avisos y mensajes
            </small>
        </div>

        <a href="{{ route('comunicaciones.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i>
            Nueva comunicación
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

    <div class="row mb-3">

        <div class="col-md-6">
            <div class="info-box">
                <span class="info-box-icon bg-primary">
                    <i class="fa-solid fa-inbox"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">
                        Recibidas
                    </span>

                    <span class="info-box-number">
                        {{ number_format($recibidas->total()) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="info-box">
                <span class="info-box-icon bg-info">
                    <i class="fa-solid fa-paper-plane"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">
                        Enviadas
                    </span>

                    <span class="info-box-number">
                        {{ number_format($enviadas->total()) }}
                    </span>
                </div>
            </div>
        </div>

    </div>

    <div class="card card-outline card-primary">

        <div class="card-header p-0 border-bottom-0">

            <ul class="nav nav-tabs"
                id="comunicaciones-tabs"
                role="tablist">

                <li class="nav-item">
                    <a class="nav-link active"
                       id="recibidas-tab"
                       data-toggle="pill"
                       href="#recibidas"
                       role="tab">

                        <i class="fa-solid fa-inbox mr-1"></i>
                        Recibidas

                        <span class="badge badge-primary ml-1">
                            {{ $recibidas->total() }}
                        </span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link"
                       id="enviadas-tab"
                       data-toggle="pill"
                       href="#enviadas"
                       role="tab">

                        <i class="fa-solid fa-paper-plane mr-1"></i>
                        Enviadas

                        <span class="badge badge-info ml-1">
                            {{ $enviadas->total() }}
                        </span>
                    </a>
                </li>

            </ul>
        </div>

        <div class="card-body p-0">

            <div class="tab-content">

                <div class="tab-pane fade show active"
                     id="recibidas"
                     role="tabpanel">

                    @if ($recibidas->count() > 0)

                        <div class="table-responsive">

                            <table class="table table-hover mb-0 tabla-comunicaciones">

                                <thead>
                                    <tr>
                                        <th style="width: 55px;"></th>
                                        <th>Remitente</th>
                                        <th>Asunto</th>
                                        <th>Tipo</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th style="width: 90px;">
                                            Acción
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @foreach ($recibidas as $registro)

                                        @php
                                            $comunicacion = $registro->comunicacion;
                                        @endphp

                                        @if ($comunicacion)

                                            <tr class="{{ is_null($registro->leido_at) ? 'comunicacion-no-leida' : '' }}">

                                                <td class="text-center">

                                                    @if ($comunicacion->tipo === 'orden')
                                                        <span class="tipo-icono tipo-orden"
                                                              title="Orden">
                                                            <i class="fa-solid fa-bullhorn"></i>
                                                        </span>

                                                    @elseif ($comunicacion->tipo === 'aviso')
                                                        <span class="tipo-icono tipo-aviso"
                                                              title="Aviso">
                                                            <i class="fa-solid fa-bell"></i>
                                                        </span>

                                                    @else
                                                        <span class="tipo-icono tipo-mensaje"
                                                              title="Mensaje">
                                                            <i class="fa-solid fa-comment"></i>
                                                        </span>
                                                    @endif

                                                </td>

                                                <td>
                                                    <strong>
                                                        {{ $comunicacion->remitente->nombre_completo ?? $comunicacion->remitente->name ?? 'Usuario' }}
                                                    </strong>

                                                    @if (is_null($registro->leido_at))
                                                        <span class="badge badge-primary ml-1">
                                                            Nuevo
                                                        </span>
                                                    @endif
                                                </td>

                                                <td>
                                                    <a href="{{ route('comunicaciones.show', $comunicacion) }}"
                                                       class="comunicacion-asunto">

                                                        {{ $comunicacion->asunto }}

                                                    </a>

                                                    <div class="texto-previo">
                                                        {{ \Illuminate\Support\Str::limit($comunicacion->contenido, 90) }}
                                                    </div>
                                                </td>

                                                <td>
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
                                                </td>

                                                <td>
                                                    @if ($comunicacion->enviado_at)
                                                        {{ $comunicacion->enviado_at->format('d/m/Y') }}

                                                        <div class="text-muted small">
                                                            {{ $comunicacion->enviado_at->format('H:i') }}
                                                        </div>
                                                    @else
                                                        -
                                                    @endif
                                                </td>

                                                <td>

                                                    @if ($comunicacion->requiere_enterado)

                                                        @if ($registro->enterado_at)

                                                            <span class="badge badge-success">
                                                                <i class="fa-solid fa-check-double"></i>
                                                                Enterado
                                                            </span>

                                                        @elseif ($registro->leido_at)

                                                            <span class="badge badge-warning">
                                                                <i class="fa-solid fa-eye"></i>
                                                                Leído, falta enterado
                                                            </span>

                                                        @else

                                                            <span class="badge badge-danger">
                                                                <i class="fa-solid fa-circle-exclamation"></i>
                                                                Pendiente
                                                            </span>

                                                        @endif

                                                    @else

                                                        @if ($registro->leido_at)

                                                            <span class="badge badge-secondary">
                                                                <i class="fa-solid fa-check"></i>
                                                                Leído
                                                            </span>

                                                        @else

                                                            <span class="badge badge-primary">
                                                                <i class="fa-solid fa-envelope"></i>
                                                                Sin leer
                                                            </span>

                                                        @endif

                                                    @endif

                                                </td>

                                                <td class="text-center">
                                                    <a href="{{ route('comunicaciones.show', $comunicacion) }}"
                                                       class="btn btn-info btn-sm"
                                                       title="Ver">

                                                        <i class="fa-regular fa-eye"></i>

                                                    </a>
                                                </td>

                                            </tr>

                                        @endif

                                    @endforeach

                                </tbody>
                            </table>

                        </div>

                        <div class="p-3">
                            {{ $recibidas->appends([
                                'enviadas_page' => request('enviadas_page')
                            ])->links() }}
                        </div>

                    @else

                        <div class="estado-vacio">
                            <i class="fa-regular fa-envelope-open"></i>

                            <h5>
                                No tienes comunicaciones recibidas
                            </h5>

                            <p>
                                Cuando recibas una orden, aviso o mensaje aparecerá aquí.
                            </p>
                        </div>

                    @endif

                </div>

                <div class="tab-pane fade"
                     id="enviadas"
                     role="tabpanel">

                    @if ($enviadas->count() > 0)

                        <div class="table-responsive">

                            <table class="table table-hover mb-0 tabla-comunicaciones">

                                <thead>
                                    <tr>
                                        <th style="width: 55px;"></th>
                                        <th>Asunto</th>
                                        <th>Tipo</th>
                                        <th>Alcance</th>
                                        <th>Fecha</th>
                                        <th>Destinatarios</th>
                                        <th>Leídos</th>
                                        <th>Enterados</th>
                                        <th style="width: 90px;">
                                            Acción
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @foreach ($enviadas as $comunicacion)

                                        <tr>

                                            <td class="text-center">

                                                @if ($comunicacion->tipo === 'orden')
                                                    <span class="tipo-icono tipo-orden">
                                                        <i class="fa-solid fa-bullhorn"></i>
                                                    </span>

                                                @elseif ($comunicacion->tipo === 'aviso')
                                                    <span class="tipo-icono tipo-aviso">
                                                        <i class="fa-solid fa-bell"></i>
                                                    </span>

                                                @else
                                                    <span class="tipo-icono tipo-mensaje">
                                                        <i class="fa-solid fa-comment"></i>
                                                    </span>
                                                @endif

                                            </td>

                                            <td>
                                                <a href="{{ route('comunicaciones.show', $comunicacion) }}"
                                                   class="comunicacion-asunto">

                                                    {{ $comunicacion->asunto }}

                                                </a>

                                                <div class="texto-previo">
                                                    {{ \Illuminate\Support\Str::limit($comunicacion->contenido, 80) }}
                                                </div>
                                            </td>

                                            <td>

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

                                            </td>

                                            <td>

                                                @switch($comunicacion->alcance)

                                                    @case('todos')
                                                        <span class="badge badge-dark">
                                                            Todo el personal
                                                        </span>
                                                        @break

                                                    @case('unidad')
                                                        <span class="badge badge-primary">
                                                            {{ $comunicacion->unidad->nombre ?? 'Unidad' }}
                                                        </span>
                                                        @break

                                                    @case('unidad_turno')
                                                        <span class="badge badge-primary">
                                                            {{ $comunicacion->unidad->nombre ?? 'Unidad' }}
                                                        </span>

                                                        <span class="badge badge-secondary">
                                                            {{ $comunicacion->turno->nombre ?? 'Turno' }}
                                                        </span>
                                                        @break

                                                    @case('subdirectores')
                                                        <span class="badge badge-dark">
                                                            Subdirectores
                                                        </span>
                                                        @break

                                                    @case('rol')
                                                        <span class="badge badge-secondary">
                                                            {{ $comunicacion->role->name ?? 'Rol' }}
                                                        </span>
                                                        @break

                                                    @case('usuario')
                                                        <span class="badge badge-info">
                                                            <i class="fa-solid fa-user"></i>

                                                            {{ $comunicacion->destinatario->nombre_completo
                                                                ?? $comunicacion->destinatario->name
                                                                ?? 'Usuario' }}
                                                        </span>
                                                        @break

                                                    @default
                                                        {{ $comunicacion->alcance }}

                                                @endswitch

                                            </td>

                                            <td>
                                                @if ($comunicacion->enviado_at)
                                                    {{ $comunicacion->enviado_at->format('d/m/Y') }}

                                                    <div class="text-muted small">
                                                        {{ $comunicacion->enviado_at->format('H:i') }}
                                                    </div>
                                                @else
                                                    -
                                                @endif
                                            </td>

                                            <td class="text-center">
                                                <span class="badge badge-secondary contador-badge">
                                                    {{ $comunicacion->destinatarios_count }}
                                                </span>
                                            </td>

                                            <td class="text-center">
                                                <span class="badge badge-info contador-badge">
                                                    {{ $comunicacion->leidos_count }}
                                                    /
                                                    {{ $comunicacion->destinatarios_count }}
                                                </span>
                                            </td>

                                            <td class="text-center">

                                                @if ($comunicacion->requiere_enterado)

                                                    @php
                                                        $todosEnterados =
                                                            $comunicacion->destinatarios_count > 0
                                                            &&
                                                            $comunicacion->enterados_count
                                                            == $comunicacion->destinatarios_count;
                                                    @endphp

                                                    <span class="badge {{ $todosEnterados ? 'badge-success' : 'badge-warning' }} contador-badge">

                                                        {{ $comunicacion->enterados_count }}
                                                        /
                                                        {{ $comunicacion->destinatarios_count }}

                                                    </span>

                                                @else
                                                    <span class="text-muted">
                                                        —
                                                    </span>
                                                @endif

                                            </td>

                                            <td class="text-center">

                                                <a href="{{ route('comunicaciones.show', $comunicacion) }}"
                                                   class="btn btn-info btn-sm"
                                                   title="Ver detalle">

                                                    <i class="fa-regular fa-eye"></i>

                                                </a>

                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                        <div class="p-3">
                            {{ $enviadas->appends([
                                'recibidas_page' => request('recibidas_page')
                            ])->links() }}
                        </div>

                    @else

                        <div class="estado-vacio">

                            <i class="fa-regular fa-paper-plane"></i>

                            <h5>
                                No has enviado comunicaciones
                            </h5>

                            <p>
                                Tus órdenes, avisos y mensajes enviados aparecerán aquí.
                            </p>

                            <a href="{{ route('comunicaciones.create') }}"
                               class="btn btn-primary">

                                <i class="fa-solid fa-plus"></i>
                                Nueva comunicación

                            </a>

                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

@stop

@section('css')

    <style>

        .tabla-comunicaciones th {
            background: rgba(255, 255, 255, 0.04);
            white-space: nowrap;
            vertical-align: middle;
        }

        .tabla-comunicaciones td {
            vertical-align: middle;
        }

        .comunicacion-no-leida {
            font-weight: 500;
            background: rgba(0, 123, 255, 0.09);
        }

        .comunicacion-asunto {
            font-weight: 600;
        }

        .texto-previo {
            color: #8c98a4;
            font-size: 0.84rem;
            margin-top: 3px;
            max-width: 480px;
        }

        .tipo-icono {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            font-size: 14px;
        }

        .tipo-orden {
            background: rgba(220, 53, 69, 0.18);
            color: #dc3545;
        }

        .tipo-aviso {
            background: rgba(255, 193, 7, 0.18);
            color: #ffc107;
        }

        .tipo-mensaje {
            background: rgba(23, 162, 184, 0.18);
            color: #17a2b8;
        }

        .contador-badge {
            min-width: 46px;
            padding: 6px 9px;
            font-size: 0.82rem;
        }

        .estado-vacio {
            text-align: center;
            padding: 60px 20px;
            color: #8c98a4;
        }

        .estado-vacio > i {
            display: block;
            font-size: 54px;
            margin-bottom: 18px;
            opacity: .55;
        }

        .estado-vacio h5 {
            color: inherit;
            font-weight: 600;
        }

        .nav-tabs .nav-link {
            padding: 14px 20px;
        }

        @media (max-width: 767px) {

            .texto-previo {
                max-width: 250px;
            }

        }

    </style>

@stop

@section('js')

    <script>

        $(function () {

            const tabGuardado = sessionStorage.getItem('comunicaciones_tab');

            if (tabGuardado) {
                $('#comunicaciones-tabs a[href="' + tabGuardado + '"]').tab('show');
            }

            $('#comunicaciones-tabs a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
                sessionStorage.setItem(
                    'comunicaciones_tab',
                    $(e.target).attr('href')
                );
            });

        });

        @if (session('success'))

            Swal.fire({
                position: 'center',
                icon: 'success',
                title: @json(session('success')),
                showConfirmButton: false,
                timer: 3000
            });

        @endif

    </script>

@stop
