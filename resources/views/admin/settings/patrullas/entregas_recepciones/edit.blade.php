@extends('adminlte::page')

@section('title', 'Editar Entrega y Recepción')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>Editar Entrega y Recepción</h1>
            <small class="text-muted">
                Patrulla {{ $patrulla->numero_economico }}
            </small>
        </div>

        <a href="{{ route('patrullas.entregas_recepciones.show', [
            'patrulla' => $patrulla->id,
            'entregaRecepcionPatrulla' => $entregaRecepcionPatrulla->id
        ]) }}"
           class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Regresar
        </a>
    </div>
@stop

@section('content')

    <form action="{{ route('patrullas.entregas_recepciones.update', [
        'patrulla' => $patrulla->id,
        'entregaRecepcionPatrulla' => $entregaRecepcionPatrulla->id
    ]) }}"
          method="POST">

        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-solid fa-car-side"></i>
                            Patrulla
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
                                <div class="form-group">
                                    <label for="fecha">Fecha</label>

                                    <input
                                        type="date"
                                        name="fecha"
                                        id="fecha"
                                        class="form-control @error('fecha') is-invalid @enderror"
                                        value="{{ old('fecha', optional($entregaRecepcionPatrulla->fecha)->format('Y-m-d')) }}"
                                        required
                                    >

                                    @error('fecha')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>


                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="hora_programada">
                                        Hora Programada
                                    </label>

                                    <input
                                        type="time"
                                        name="hora_programada"
                                        id="hora_programada"
                                        class="form-control @error('hora_programada') is-invalid @enderror"
                                        value="{{ old(
                                            'hora_programada',
                                            $entregaRecepcionPatrulla->hora_programada
                                                ? substr($entregaRecepcionPatrulla->hora_programada, 0, 5)
                                                : '07:00'
                                        ) }}"
                                        required
                                    >

                                    @error('hora_programada')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>


                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="hora_real">
                                        Hora Real
                                    </label>

                                    <input
                                        type="time"
                                        name="hora_real"
                                        id="hora_real"
                                        class="form-control @error('hora_real') is-invalid @enderror"
                                        value="{{ old(
                                            'hora_real',
                                            $entregaRecepcionPatrulla->hora_real
                                                ? substr($entregaRecepcionPatrulla->hora_real, 0, 5)
                                                : ''
                                        ) }}"
                                    >

                                    @error('hora_real')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>


                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="kilometraje">
                                        Kilometraje
                                    </label>

                                    <div class="input-group">
                                        <input
                                            type="number"
                                            name="kilometraje"
                                            id="kilometraje"
                                            min="0"
                                            class="form-control @error('kilometraje') is-invalid @enderror"
                                            value="{{ old('kilometraje', $entregaRecepcionPatrulla->kilometraje) }}"
                                        >

                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                km
                                            </span>
                                        </div>

                                        @error('kilometraje')
                                            <span class="invalid-feedback">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                        </div>


                        <div class="row">

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nivel_combustible">
                                        Nivel de Combustible
                                    </label>

                                    <div class="input-group">
                                        <input
                                            type="number"
                                            name="nivel_combustible"
                                            id="nivel_combustible"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            class="form-control @error('nivel_combustible') is-invalid @enderror"
                                            value="{{ old('nivel_combustible', $entregaRecepcionPatrulla->nivel_combustible) }}"
                                        >

                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                %
                                            </span>
                                        </div>

                                        @error('nivel_combustible')
                                            <span class="invalid-feedback">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
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

                        <div class="form-group">
                            <label for="entrega_user_id">
                                Usuario que Entrega
                            </label>

                            <select
                                name="entrega_user_id"
                                id="entrega_user_id"
                                class="form-control @error('entrega_user_id') is-invalid @enderror"
                            >
                                <option value="">
                                    Sin usuario vinculado
                                </option>

                                @foreach($usuarios as $usuario)
                                    <option
                                        value="{{ $usuario->id }}"
                                        {{ (string) old(
                                            'entrega_user_id',
                                            $entregaRecepcionPatrulla->entrega_user_id
                                        ) === (string) $usuario->id ? 'selected' : '' }}
                                    >
                                        {{ $usuario->nombre_completo }}
                                        @if($usuario->email)
                                            — {{ $usuario->email }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            @error('entrega_user_id')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>


                        <div class="form-group">
                            <label for="entrega_nombre">
                                Nombre de quien Entrega
                            </label>

                            <input
                                type="text"
                                name="entrega_nombre"
                                id="entrega_nombre"
                                maxlength="150"
                                class="form-control @error('entrega_nombre') is-invalid @enderror"
                                value="{{ old('entrega_nombre', $entregaRecepcionPatrulla->entrega_nombre) }}"
                            >

                            @error('entrega_nombre')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror

                            <small class="text-muted">
                                Si selecciona un usuario, el nombre será tomado automáticamente de su cuenta.
                            </small>
                        </div>


                        <div class="form-group">
                            <label for="aceptada_entrega">
                                Estado de la Entrega
                            </label>

                            <select
                                name="aceptada_entrega"
                                id="aceptada_entrega"
                                class="form-control @error('aceptada_entrega') is-invalid @enderror"
                            >
                                <option
                                    value="0"
                                    {{ (string) old(
                                        'aceptada_entrega',
                                        (int) $entregaRecepcionPatrulla->aceptada_entrega
                                    ) === '0' ? 'selected' : '' }}
                                >
                                    Pendiente
                                </option>

                                <option
                                    value="1"
                                    {{ (string) old(
                                        'aceptada_entrega',
                                        (int) $entregaRecepcionPatrulla->aceptada_entrega
                                    ) === '1' ? 'selected' : '' }}
                                >
                                    Confirmada
                                </option>
                            </select>

                            @error('aceptada_entrega')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

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

                        <div class="form-group">
                            <label for="recibe_user_id">
                                Usuario que Recibe
                            </label>

                            <select
                                name="recibe_user_id"
                                id="recibe_user_id"
                                class="form-control @error('recibe_user_id') is-invalid @enderror"
                            >
                                <option value="">
                                    Sin usuario vinculado
                                </option>

                                @foreach($usuarios as $usuario)
                                    <option
                                        value="{{ $usuario->id }}"
                                        {{ (string) old(
                                            'recibe_user_id',
                                            $entregaRecepcionPatrulla->recibe_user_id
                                        ) === (string) $usuario->id ? 'selected' : '' }}
                                    >
                                        {{ $usuario->nombre_completo }}
                                        @if($usuario->email)
                                            — {{ $usuario->email }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            @error('recibe_user_id')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>


                        <div class="form-group">
                            <label for="recibe_nombre">
                                Nombre de quien Recibe
                            </label>

                            <input
                                type="text"
                                name="recibe_nombre"
                                id="recibe_nombre"
                                maxlength="150"
                                class="form-control @error('recibe_nombre') is-invalid @enderror"
                                value="{{ old('recibe_nombre', $entregaRecepcionPatrulla->recibe_nombre) }}"
                            >

                            @error('recibe_nombre')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror

                            <small class="text-muted">
                                Si selecciona un usuario, el nombre será tomado automáticamente de su cuenta.
                            </small>
                        </div>


                        <div class="form-group">
                            <label for="aceptada_recepcion">
                                Estado de la Recepción
                            </label>

                            <select
                                name="aceptada_recepcion"
                                id="aceptada_recepcion"
                                class="form-control @error('aceptada_recepcion') is-invalid @enderror"
                            >
                                <option
                                    value="0"
                                    {{ (string) old(
                                        'aceptada_recepcion',
                                        (int) $entregaRecepcionPatrulla->aceptada_recepcion
                                    ) === '0' ? 'selected' : '' }}
                                >
                                    Pendiente
                                </option>

                                <option
                                    value="1"
                                    {{ (string) old(
                                        'aceptada_recepcion',
                                        (int) $entregaRecepcionPatrulla->aceptada_recepcion
                                    ) === '1' ? 'selected' : '' }}
                                >
                                    Confirmada
                                </option>
                            </select>

                            @error('aceptada_recepcion')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                    </div>
                </div>

            </div>

        </div>


        <div class="row mt-3">
            <div class="col-md-12">

                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                            Estado de la Unidad
                        </h3>
                    </div>

                    <div class="card-body">

                        @php
                            $estadosGenerales = [
                                '' => 'Sin registro',
                                'bueno' => 'Bueno',
                                'regular' => 'Regular',
                                'malo' => 'Malo',
                            ];

                            $estadosEquipo = [
                                '' => 'Sin registro',
                                'bueno' => 'Bueno',
                                'regular' => 'Regular',
                                'malo' => 'Malo',
                                'no_aplica' => 'No aplica',
                            ];
                        @endphp


                        <div class="row">

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estado_carroceria">
                                        Carrocería
                                    </label>

                                    <select
                                        name="estado_carroceria"
                                        id="estado_carroceria"
                                        class="form-control"
                                    >
                                        @foreach($estadosGenerales as $valor => $texto)
                                            <option
                                                value="{{ $valor }}"
                                                {{ old(
                                                    'estado_carroceria',
                                                    $entregaRecepcionPatrulla->estado_carroceria
                                                ) === $valor ? 'selected' : '' }}
                                            >
                                                {{ $texto }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>


                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estado_interiores">
                                        Interiores
                                    </label>

                                    <select
                                        name="estado_interiores"
                                        id="estado_interiores"
                                        class="form-control"
                                    >
                                        @foreach($estadosGenerales as $valor => $texto)
                                            <option
                                                value="{{ $valor }}"
                                                {{ old(
                                                    'estado_interiores',
                                                    $entregaRecepcionPatrulla->estado_interiores
                                                ) === $valor ? 'selected' : '' }}
                                            >
                                                {{ $texto }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>


                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estado_llantas">
                                        Llantas
                                    </label>

                                    <select
                                        name="estado_llantas"
                                        id="estado_llantas"
                                        class="form-control"
                                    >
                                        @foreach($estadosGenerales as $valor => $texto)
                                            <option
                                                value="{{ $valor }}"
                                                {{ old(
                                                    'estado_llantas',
                                                    $entregaRecepcionPatrulla->estado_llantas
                                                ) === $valor ? 'selected' : '' }}
                                            >
                                                {{ $texto }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>


                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estado_luces">
                                        Luces
                                    </label>

                                    <select
                                        name="estado_luces"
                                        id="estado_luces"
                                        class="form-control"
                                    >
                                        @foreach($estadosGenerales as $valor => $texto)
                                            <option
                                                value="{{ $valor }}"
                                                {{ old(
                                                    'estado_luces',
                                                    $entregaRecepcionPatrulla->estado_luces
                                                ) === $valor ? 'selected' : '' }}
                                            >
                                                {{ $texto }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                        </div>


                        <div class="row">

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estado_torreta">
                                        Torreta
                                    </label>

                                    <select
                                        name="estado_torreta"
                                        id="estado_torreta"
                                        class="form-control"
                                    >
                                        @foreach($estadosEquipo as $valor => $texto)
                                            <option
                                                value="{{ $valor }}"
                                                {{ old(
                                                    'estado_torreta',
                                                    $entregaRecepcionPatrulla->estado_torreta
                                                ) === $valor ? 'selected' : '' }}
                                            >
                                                {{ $texto }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>


                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estado_sirena">
                                        Sirena
                                    </label>

                                    <select
                                        name="estado_sirena"
                                        id="estado_sirena"
                                        class="form-control"
                                    >
                                        @foreach($estadosEquipo as $valor => $texto)
                                            <option
                                                value="{{ $valor }}"
                                                {{ old(
                                                    'estado_sirena',
                                                    $entregaRecepcionPatrulla->estado_sirena
                                                ) === $valor ? 'selected' : '' }}
                                            >
                                                {{ $texto }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>


                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estado_radio">
                                        Radio
                                    </label>

                                    <select
                                        name="estado_radio"
                                        id="estado_radio"
                                        class="form-control"
                                    >
                                        @foreach($estadosEquipo as $valor => $texto)
                                            <option
                                                value="{{ $valor }}"
                                                {{ old(
                                                    'estado_radio',
                                                    $entregaRecepcionPatrulla->estado_radio
                                                ) === $valor ? 'selected' : '' }}
                                            >
                                                {{ $texto }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>


                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estado_mecanico">
                                        Estado Mecánico
                                    </label>

                                    <select
                                        name="estado_mecanico"
                                        id="estado_mecanico"
                                        class="form-control"
                                    >
                                        @foreach($estadosGenerales as $valor => $texto)
                                            <option
                                                value="{{ $valor }}"
                                                {{ old(
                                                    'estado_mecanico',
                                                    $entregaRecepcionPatrulla->estado_mecanico
                                                ) === $valor ? 'selected' : '' }}
                                            >
                                                {{ $texto }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

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
                            Equipamiento
                        </h3>
                    </div>

                    <div class="card-body">

                        @php
                            $equipamiento = [
                                'trae_refaccion' => 'Refacción',
                                'trae_gato' => 'Gato',
                                'trae_llave_cruz' => 'Llave de cruz',
                                'trae_extintor' => 'Extintor',
                                'trae_botiquin' => 'Botiquín',
                            ];
                        @endphp

                        <div class="row">

                            @foreach($equipamiento as $campo => $nombre)

                                <div class="col-md col-sm-6">

                                    <div class="form-group">
                                        <label for="{{ $campo }}">
                                            {{ $nombre }}
                                        </label>

                                        <select
                                            name="{{ $campo }}"
                                            id="{{ $campo }}"
                                            class="form-control"
                                        >
                                            <option
                                                value=""
                                                {{ old(
                                                    $campo,
                                                    $entregaRecepcionPatrulla->{$campo}
                                                ) === null ? 'selected' : '' }}
                                            >
                                                Sin registro
                                            </option>

                                            <option
                                                value="1"
                                                {{ (string) old(
                                                    $campo,
                                                    $entregaRecepcionPatrulla->{$campo}
                                                ) === '1' ? 'selected' : '' }}
                                            >
                                                Sí
                                            </option>

                                            <option
                                                value="0"
                                                {{ old(
                                                    $campo,
                                                    $entregaRecepcionPatrulla->{$campo}
                                                ) !== null
                                                && (string) old(
                                                    $campo,
                                                    $entregaRecepcionPatrulla->{$campo}
                                                ) === '0'
                                                    ? 'selected'
                                                    : '' }}
                                            >
                                                No
                                            </option>
                                        </select>
                                    </div>

                                </div>

                            @endforeach

                        </div>


                        <div class="form-group">
                            <label for="equipo_adicional">
                                Equipo Adicional
                            </label>

                            <textarea
                                name="equipo_adicional"
                                id="equipo_adicional"
                                rows="3"
                                maxlength="5000"
                                class="form-control @error('equipo_adicional') is-invalid @enderror"
                            >{{ old('equipo_adicional', $entregaRecepcionPatrulla->equipo_adicional) }}</textarea>

                            @error('equipo_adicional')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

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
                                <div class="form-group">
                                    <label for="danos_existentes">
                                        Daños Existentes
                                    </label>

                                    <textarea
                                        name="danos_existentes"
                                        id="danos_existentes"
                                        rows="5"
                                        maxlength="5000"
                                        class="form-control @error('danos_existentes') is-invalid @enderror"
                                    >{{ old('danos_existentes', $entregaRecepcionPatrulla->danos_existentes) }}</textarea>

                                    @error('danos_existentes')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>


                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="novedades">
                                        Novedades
                                    </label>

                                    <textarea
                                        name="novedades"
                                        id="novedades"
                                        rows="5"
                                        maxlength="5000"
                                        class="form-control @error('novedades') is-invalid @enderror"
                                    >{{ old('novedades', $entregaRecepcionPatrulla->novedades) }}</textarea>

                                    @error('novedades')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>


                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="observaciones">
                                        Observaciones
                                    </label>

                                    <textarea
                                        name="observaciones"
                                        id="observaciones"
                                        rows="5"
                                        maxlength="5000"
                                        class="form-control @error('observaciones') is-invalid @enderror"
                                    >{{ old('observaciones', $entregaRecepcionPatrulla->observaciones) }}</textarea>

                                    @error('observaciones')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
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
                                Evidencia Fotográfica Registrada
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

                <div class="card card-outline card-success">
                    <div class="card-body">

                        <button
                            type="submit"
                            class="btn btn-success"
                        >
                            <i class="fa-solid fa-check"></i>
                            Guardar Cambios
                        </button>

                        <a href="{{ route('patrullas.entregas_recepciones.show', [
                            'patrulla' => $patrulla->id,
                            'entregaRecepcionPatrulla' => $entregaRecepcionPatrulla->id
                        ]) }}"
                           class="btn btn-secondary">
                            <i class="fa-solid fa-ban"></i>
                            Cancelar
                        </a>

                    </div>
                </div>

            </div>
        </div>

    </form>

@stop


@section('css')
    <style>
        .form-group label {
            font-weight: bold;
        }

        .evidencia-img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            background: #f8f9fa;
        }
    </style>
@stop


@section('js')

    @if($errors->any())
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Errores en el formulario',
                html: `
                    <ul style="text-align:left;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                `,
                confirmButtonText: 'Aceptar'
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const entregaUsuario = document.getElementById('entrega_user_id');
            const entregaNombre = document.getElementById('entrega_nombre');

            const recibeUsuario = document.getElementById('recibe_user_id');
            const recibeNombre = document.getElementById('recibe_nombre');

            function actualizarEstadoNombre(select, input) {
                if (!select || !input) {
                    return;
                }

                if (select.value) {
                    input.setAttribute('readonly', 'readonly');
                    input.classList.add('bg-light');
                } else {
                    input.removeAttribute('readonly');
                    input.classList.remove('bg-light');
                }
            }

            actualizarEstadoNombre(entregaUsuario, entregaNombre);
            actualizarEstadoNombre(recibeUsuario, recibeNombre);

            entregaUsuario.addEventListener('change', function () {
                const opcion = this.options[this.selectedIndex];

                if (this.value) {
                    const texto = opcion.text.split('—')[0].trim();
                    entregaNombre.value = texto;
                }

                actualizarEstadoNombre(
                    entregaUsuario,
                    entregaNombre
                );
            });

            recibeUsuario.addEventListener('change', function () {
                const opcion = this.options[this.selectedIndex];

                if (this.value) {
                    const texto = opcion.text.split('—')[0].trim();
                    recibeNombre.value = texto;
                }

                actualizarEstadoNombre(
                    recibeUsuario,
                    recibeNombre
                );
            });
        });
    </script>

@stop
