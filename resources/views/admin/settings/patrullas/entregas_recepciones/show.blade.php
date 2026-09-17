@extends('adminlte::page')

@section('title', 'Entrega y Recepción de Patrulla')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>Entrega y Recepción de Patrulla</h1>
            <small class="text-muted">
                Unidad {{ $patrulla->numero_economico }}
            </small>
        </div>

        <div>
            @can('editar patrullas')
                <a href="{{ route('patrullas.entregas_recepciones.edit', [
                    'patrulla' => $patrulla->id,
                    'entregaRecepcionPatrulla' => $entregaRecepcionPatrulla->id
                ]) }}"
                   class="btn btn-success">
                    <i class="fa-regular fa-pen-to-square"></i> Editar
                </a>
            @endcan

            <a href="{{ route('patrullas.entregas_recepciones.index', [
                'patrulla' => $patrulla->id
            ]) }}"
               class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Regresar
            </a>
        </div>
    </div>
@stop

@section('content')

    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-car-side"></i>
                        Datos de la Patrulla
                    </h3>
                </div>

                <div class="card-body">
                    <div class="row">

                        <div class="col-md-3">
                            <strong>Número Económico</strong>
                            <p class="text-muted mb-0">
                                {{ $patrulla->numero_economico ?? '—' }}
                            </p>
                        </div>

                        <div class="col-md-3">
                            <strong>Unidad</strong>
                            <p class="text-muted mb-0">
                                {{ $patrulla->unidad->nombre ?? '—' }}
                            </p>
                        </div>

                        <div class="col-md-3">
                            <strong>Vehículo</strong>
                            <p class="text-muted mb-0">
                                {{ $patrulla->descripcion_vehiculo ?: '—' }}
                            </p>
                        </div>

                        <div class="col-md-3">
                            <strong>Placas</strong>
                            <p class="text-muted mb-0">
                                {{ $patrulla->placas ?? '—' }}
                            </p>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>


    <div class="row">

        <div class="col-md-6">

            <div class="card card-outline card-danger h-100">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        Entrega
                    </h3>
                </div>

                <div class="card-body">

                    <dl class="row mb-0">

                        <dt class="col-sm-4">Elemento</dt>
                        <dd class="col-sm-8">
                            {{ $entregaRecepcionPatrulla->entrega_nombre ?? '—' }}
                        </dd>

                        <dt class="col-sm-4">Usuario</dt>
                        <dd class="col-sm-8">
                            {{ $entregaRecepcionPatrulla->entregaUsuario->email ?? '—' }}
                        </dd>

                        <dt class="col-sm-4">Confirmación</dt>
                        <dd class="col-sm-8">
                            @if($entregaRecepcionPatrulla->aceptada_entrega)
                                <span class="badge badge-success">
                                    <i class="fa-solid fa-check"></i>
                                    Confirmada
                                </span>
                            @else
                                <span class="badge badge-warning">
                                    Pendiente
                                </span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Confirmada el</dt>
                        <dd class="col-sm-8">
                            @if($entregaRecepcionPatrulla->entrega_confirmada_at)
                                {{ $entregaRecepcionPatrulla->entrega_confirmada_at->format('d-m-Y H:i:s') }}
                            @else
                                —
                            @endif
                        </dd>

                    </dl>

                </div>
            </div>

        </div>


        <div class="col-md-6">

            <div class="card card-outline card-success h-100">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        Recepción
                    </h3>
                </div>

                <div class="card-body">

                    <dl class="row mb-0">

                        <dt class="col-sm-4">Elemento</dt>
                        <dd class="col-sm-8">
                            {{ $entregaRecepcionPatrulla->recibe_nombre ?? '—' }}
                        </dd>

                        <dt class="col-sm-4">Usuario</dt>
                        <dd class="col-sm-8">
                            {{ $entregaRecepcionPatrulla->recibeUsuario->email ?? '—' }}
                        </dd>

                        <dt class="col-sm-4">Confirmación</dt>
                        <dd class="col-sm-8">
                            @if($entregaRecepcionPatrulla->aceptada_recepcion)
                                <span class="badge badge-success">
                                    <i class="fa-solid fa-check"></i>
                                    Confirmada
                                </span>
                            @else
                                <span class="badge badge-warning">
                                    Pendiente
                                </span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Confirmada el</dt>
                        <dd class="col-sm-8">
                            @if($entregaRecepcionPatrulla->recepcion_confirmada_at)
                                {{ $entregaRecepcionPatrulla->recepcion_confirmada_at->format('d-m-Y H:i:s') }}
                            @else
                                —
                            @endif
                        </dd>

                    </dl>

                </div>
            </div>

        </div>

    </div>


    <div class="row mt-3">

        <div class="col-md-12">

            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-regular fa-calendar"></i>
                        Datos del Movimiento
                    </h3>
                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-3">
                            <strong>Fecha</strong>
                            <p class="text-muted">
                                {{ optional($entregaRecepcionPatrulla->fecha)->format('d-m-Y') ?? '—' }}
                            </p>
                        </div>

                        <div class="col-md-3">
                            <strong>Hora Programada</strong>
                            <p class="text-muted">
                                {{ $entregaRecepcionPatrulla->hora_programada
                                    ? substr($entregaRecepcionPatrulla->hora_programada, 0, 5)
                                    : '—' }}
                            </p>
                        </div>

                        <div class="col-md-3">
                            <strong>Hora Real</strong>
                            <p class="text-muted">
                                {{ $entregaRecepcionPatrulla->hora_real
                                    ? substr($entregaRecepcionPatrulla->hora_real, 0, 5)
                                    : '—' }}
                            </p>
                        </div>

                        <div class="col-md-3">
                            <strong>Estado</strong>
                            <p>
                                @if(
                                    $entregaRecepcionPatrulla->aceptada_entrega &&
                                    $entregaRecepcionPatrulla->aceptada_recepcion
                                )
                                    <span class="badge badge-success">
                                        Completa
                                    </span>
                                @elseif(
                                    $entregaRecepcionPatrulla->aceptada_entrega ||
                                    $entregaRecepcionPatrulla->aceptada_recepcion
                                )
                                    <span class="badge badge-warning">
                                        Pendiente
                                    </span>
                                @else
                                    <span class="badge badge-secondary">
                                        Sin confirmar
                                    </span>
                                @endif
                            </p>
                        </div>

                    </div>


                    <div class="row">

                        <div class="col-md-4">
                            <strong>Kilometraje</strong>
                            <p class="text-muted">
                                @if(!is_null($entregaRecepcionPatrulla->kilometraje))
                                    {{ number_format($entregaRecepcionPatrulla->kilometraje) }} km
                                @else
                                    —
                                @endif
                            </p>
                        </div>

                        <div class="col-md-4">
                            <strong>Nivel de Combustible</strong>
                            <p class="text-muted">
                                @if(!is_null($entregaRecepcionPatrulla->nivel_combustible))
                                    {{ number_format((float) $entregaRecepcionPatrulla->nivel_combustible, 0) }}%
                                @else
                                    —
                                @endif
                            </p>
                        </div>

                        <div class="col-md-4">
                            <strong>Registro</strong>
                            <p class="text-muted">
                                {{ optional($entregaRecepcionPatrulla->created_at)->format('d-m-Y H:i:s') ?? '—' }}
                            </p>
                        </div>

                    </div>

                </div>
            </div>

        </div>

    </div>


    <div class="row">

        <div class="col-md-12">

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                        Estado de la Unidad
                    </h3>
                </div>

                <div class="card-body">

                    <div class="row">

                        @php
                            $estados = [
                                'Carrocería' => $entregaRecepcionPatrulla->estado_carroceria,
                                'Interiores' => $entregaRecepcionPatrulla->estado_interiores,
                                'Llantas' => $entregaRecepcionPatrulla->estado_llantas,
                                'Luces' => $entregaRecepcionPatrulla->estado_luces,
                                'Torreta' => $entregaRecepcionPatrulla->estado_torreta,
                                'Sirena' => $entregaRecepcionPatrulla->estado_sirena,
                                'Radio' => $entregaRecepcionPatrulla->estado_radio,
                                'Estado Mecánico' => $entregaRecepcionPatrulla->estado_mecanico,
                            ];
                        @endphp

                        @foreach($estados as $nombre => $estado)

                            <div class="col-md-3 col-sm-6 mb-3">

                                <div class="border rounded p-3 text-center h-100">

                                    <strong>{{ $nombre }}</strong>

                                    <div class="mt-2">

                                        @if($estado === 'bueno')

                                            <span class="badge badge-success">
                                                Bueno
                                            </span>

                                        @elseif($estado === 'regular')

                                            <span class="badge badge-warning">
                                                Regular
                                            </span>

                                        @elseif($estado === 'malo')

                                            <span class="badge badge-danger">
                                                Malo
                                            </span>

                                        @elseif($estado === 'no_aplica')

                                            <span class="badge badge-secondary">
                                                No aplica
                                            </span>

                                        @else

                                            <span class="text-muted">
                                                —
                                            </span>

                                        @endif

                                    </div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>
            </div>

        </div>

    </div>


    <div class="row">

        <div class="col-md-12">

            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-toolbox"></i>
                        Equipo de la Patrulla
                    </h3>
                </div>

                <div class="card-body">

                    <div class="row">

                        @php
                            $equipamiento = [
                                'Refacción' => $entregaRecepcionPatrulla->trae_refaccion,
                                'Gato' => $entregaRecepcionPatrulla->trae_gato,
                                'Llave de cruz' => $entregaRecepcionPatrulla->trae_llave_cruz,
                                'Extintor' => $entregaRecepcionPatrulla->trae_extintor,
                                'Botiquín' => $entregaRecepcionPatrulla->trae_botiquin,
                            ];
                        @endphp

                        @foreach($equipamiento as $nombre => $valor)

                            <div class="col-md col-sm-6 mb-2 text-center">

                                <div class="border rounded p-3 h-100">

                                    <strong>{{ $nombre }}</strong>

                                    <div class="mt-2">

                                        @if($valor === true)

                                            <span class="badge badge-success">
                                                <i class="fa-solid fa-check"></i>
                                                Sí
                                            </span>

                                        @elseif($valor === false)

                                            <span class="badge badge-danger">
                                                <i class="fa-solid fa-xmark"></i>
                                                No
                                            </span>

                                        @else

                                            <span class="badge badge-secondary">
                                                Sin registro
                                            </span>

                                        @endif

                                    </div>

                                </div>

                            </div>

                        @endforeach

                    </div>


                    @if($entregaRecepcionPatrulla->equipo_adicional)

                        <hr>

                        <strong>Equipo adicional</strong>

                        <div class="border rounded p-3 mt-2 bg-light">
                            {!! nl2br(e($entregaRecepcionPatrulla->equipo_adicional)) !!}
                        </div>

                    @endif

                </div>
            </div>

        </div>

    </div>


    <div class="row">

        <div class="col-md-12">

            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Daños, Novedades y Observaciones
                    </h3>
                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-4">

                            <strong>Daños Existentes</strong>

                            <div class="border rounded p-3 mt-2 bg-light texto-detalle">
                                @if($entregaRecepcionPatrulla->danos_existentes)
                                    {!! nl2br(e($entregaRecepcionPatrulla->danos_existentes)) !!}
                                @else
                                    <span class="text-muted">Sin daños registrados</span>
                                @endif
                            </div>

                        </div>


                        <div class="col-md-4">

                            <strong>Novedades</strong>

                            <div class="border rounded p-3 mt-2 bg-light texto-detalle">
                                @if($entregaRecepcionPatrulla->novedades)
                                    {!! nl2br(e($entregaRecepcionPatrulla->novedades)) !!}
                                @else
                                    <span class="text-muted">Sin novedades</span>
                                @endif
                            </div>

                        </div>


                        <div class="col-md-4">

                            <strong>Observaciones</strong>

                            <div class="border rounded p-3 mt-2 bg-light texto-detalle">
                                @if($entregaRecepcionPatrulla->observaciones)
                                    {!! nl2br(e($entregaRecepcionPatrulla->observaciones)) !!}
                                @else
                                    <span class="text-muted">Sin observaciones</span>
                                @endif
                            </div>

                        </div>

                    </div>

                </div>
            </div>

        </div>

    </div>


    @php
        $fotosEntrega = [
            'Frontal' => $entregaRecepcionPatrulla->foto_frontal,
            'Trasera' => $entregaRecepcionPatrulla->foto_trasera,
            'Lateral izquierdo' => $entregaRecepcionPatrulla->foto_lateral_izquierdo,
            'Lateral derecho' => $entregaRecepcionPatrulla->foto_lateral_derecho,
            'Tablero' => $entregaRecepcionPatrulla->foto_tablero,
        ];

        $hayFotos = collect($fotosEntrega)->filter()->isNotEmpty();
    @endphp


    @if($hayFotos)

        <div class="row">

            <div class="col-md-12">

                <div class="card card-outline card-dark">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-regular fa-images"></i>
                            Evidencia Fotográfica
                        </h3>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            @foreach($fotosEntrega as $titulo => $foto)

                                @if($foto)

                                    <div class="col-md-4 col-sm-6 mb-3">

                                        <div class="card h-100">

                                            <img
                                                src="{{ asset('storage/' . $foto) }}"
                                                alt="{{ $titulo }}"
                                                class="card-img-top evidencia-img"
                                            >

                                            <div class="card-body text-center p-2">

                                                <strong>
                                                    {{ $titulo }}
                                                </strong>

                                            </div>

                                        </div>

                                    </div>

                                @endif

                            @endforeach

                        </div>

                    </div>
                </div>

            </div>

        </div>

    @endif


    <div class="row">

        <div class="col-md-12">

            <div class="card card-outline card-light">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div class="text-muted">
                            Registro #{{ $entregaRecepcionPatrulla->id }}
                        </div>

                        <div>

                            @can('editar patrullas')
                                <a href="{{ route('patrullas.entregas_recepciones.edit', [
                                    'patrulla' => $patrulla->id,
                                    'entregaRecepcionPatrulla' => $entregaRecepcionPatrulla->id
                                ]) }}"
                                   class="btn btn-success">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                    Editar Registro
                                </a>
                            @endcan

                            <a href="{{ route('patrullas.entregas_recepciones.index', [
                                'patrulla' => $patrulla->id
                            ]) }}"
                               class="btn btn-secondary">
                                <i class="fa-solid fa-arrow-left"></i>
                                Volver al Historial
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
        dt {
            font-weight: bold;
        }

        .texto-detalle {
            min-height: 110px;
        }

        .evidencia-img {
            width: 100%;
            height: 240px;
            object-fit: cover;
            background: #f8f9fa;
        }

        .badge {
            font-size: 90%;
        }
    </style>
@stop


@section('js')

    @if(session('success'))
        <script>
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: @json(session('success')),
                showConfirmButton: false,
                timer: 4000
            });
        </script>
    @endif

@stop
