@extends('adminlte::page')

@section('title', 'Editar Bitácora de Servicio')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>Editar Bitácora de Servicio</h1>
            <small class="text-muted">
                Patrulla {{ $patrulla->numero_economico }}
                @if($patrulla->descripcion_vehiculo)
                    — {{ $patrulla->descripcion_vehiculo }}
                @endif
            </small>
        </div>

        <a href="{{ route('patrullas.bitacoras.show', [
            'patrulla' => $patrulla->id,
            'bitacoraServicioPatrulla' => $bitacoraServicioPatrulla->id
        ]) }}"
           class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            Regresar
        </a>
    </div>
@stop

@section('content')

    <form action="{{ route('patrullas.bitacoras.update', [
        'patrulla' => $patrulla->id,
        'bitacoraServicioPatrulla' => $bitacoraServicioPatrulla->id
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
            <div class="col-md-12">

                <div class="card card-outline card-info">

                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-regular fa-calendar"></i>
                            Datos del Servicio
                        </h3>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha">
                                        Fecha
                                    </label>

                                    <input
                                        type="date"
                                        name="fecha"
                                        id="fecha"
                                        class="form-control @error('fecha') is-invalid @enderror"
                                        value="{{ old(
                                            'fecha',
                                            optional($bitacoraServicioPatrulla->fecha)->format('Y-m-d')
                                        ) }}"
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
                                    <label for="turno_id">
                                        Turno
                                    </label>

                                    <select
                                        name="turno_id"
                                        id="turno_id"
                                        class="form-control @error('turno_id') is-invalid @enderror"
                                    >

                                        <option value="">
                                            Sin turno
                                        </option>

                                        @foreach($turnos as $turno)

                                            <option
                                                value="{{ $turno->id }}"
                                                {{ (string) old(
                                                    'turno_id',
                                                    $bitacoraServicioPatrulla->turno_id
                                                ) === (string) $turno->id
                                                    ? 'selected'
                                                    : '' }}
                                            >
                                                {{ $turno->nombre }}
                                            </option>

                                        @endforeach

                                    </select>

                                    @error('turno_id')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>


                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="hora_inicio">
                                        Hora de Inicio
                                    </label>

                                    <input
                                        type="time"
                                        name="hora_inicio"
                                        id="hora_inicio"
                                        class="form-control @error('hora_inicio') is-invalid @enderror"
                                        value="{{ old(
                                            'hora_inicio',
                                            $bitacoraServicioPatrulla->hora_inicio
                                                ? substr($bitacoraServicioPatrulla->hora_inicio, 0, 5)
                                                : ''
                                        ) }}"
                                    >

                                    @error('hora_inicio')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>


                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="hora_fin">
                                        Hora de Fin
                                    </label>

                                    <input
                                        type="time"
                                        name="hora_fin"
                                        id="hora_fin"
                                        class="form-control @error('hora_fin') is-invalid @enderror"
                                        value="{{ old(
                                            'hora_fin',
                                            $bitacoraServicioPatrulla->hora_fin
                                                ? substr($bitacoraServicioPatrulla->hora_fin, 0, 5)
                                                : ''
                                        ) }}"
                                    >

                                    @error('hora_fin')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror

                                    <small class="text-muted">
                                        Obligatoria cuando la bitácora se marca como cerrada.
                                    </small>
                                </div>
                            </div>

                        </div>


                        <div class="row">

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="estatus">
                                        Estado
                                    </label>

                                    <select
                                        name="estatus"
                                        id="estatus"
                                        class="form-control @error('estatus') is-invalid @enderror"
                                        required
                                    >

                                        <option
                                            value="abierta"
                                            {{ old(
                                                'estatus',
                                                $bitacoraServicioPatrulla->estatus
                                            ) === 'abierta'
                                                ? 'selected'
                                                : '' }}
                                        >
                                            Abierta
                                        </option>

                                        <option
                                            value="cerrada"
                                            {{ old(
                                                'estatus',
                                                $bitacoraServicioPatrulla->estatus
                                            ) === 'cerrada'
                                                ? 'selected'
                                                : '' }}
                                        >
                                            Cerrada
                                        </option>

                                    </select>

                                    @error('estatus')
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


        <div class="row">
            <div class="col-md-12">

                <div class="card card-outline card-success">

                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-solid fa-user-shield"></i>
                            Responsable del Servicio
                        </h3>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="capturado_por_user_id">
                                        Usuario Responsable
                                    </label>

                                    <select
                                        name="capturado_por_user_id"
                                        id="capturado_por_user_id"
                                        class="form-control @error('capturado_por_user_id') is-invalid @enderror"
                                    >

                                        <option value="">
                                            Sin usuario vinculado
                                        </option>

                                        @foreach($usuarios as $usuario)

                                            <option
                                                value="{{ $usuario->id }}"
                                                {{ (string) old(
                                                    'capturado_por_user_id',
                                                    $bitacoraServicioPatrulla->capturado_por_user_id
                                                ) === (string) $usuario->id
                                                    ? 'selected'
                                                    : '' }}
                                            >
                                                {{ $usuario->nombre_completo }}

                                                @if($usuario->email)
                                                    — {{ $usuario->email }}
                                                @endif
                                            </option>

                                        @endforeach

                                    </select>

                                    @error('capturado_por_user_id')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror

                                    <small class="text-muted">
                                        El sistema utiliza este usuario para localizar sus actividades y hechos dentro del periodo de la bitácora.
                                    </small>
                                </div>
                            </div>


                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="capturado_por_nombre">
                                        Nombre del Responsable
                                    </label>

                                    <input
                                        type="text"
                                        name="capturado_por_nombre"
                                        id="capturado_por_nombre"
                                        maxlength="150"
                                        class="form-control @error('capturado_por_nombre') is-invalid @enderror"
                                        value="{{ old(
                                            'capturado_por_nombre',
                                            $bitacoraServicioPatrulla->capturado_por_nombre
                                        ) }}"
                                    >

                                    @error('capturado_por_nombre')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror

                                    <small class="text-muted">
                                        Si selecciona un usuario, el nombre se obtiene automáticamente de su cuenta.
                                    </small>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

            </div>
        </div>


        <div class="row">

            <div class="col-md-6">

                <div class="card card-outline card-secondary h-100">

                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-solid fa-gauge-high"></i>
                            Kilometraje
                        </h3>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="kilometraje_inicio">
                                        Kilometraje Inicial
                                    </label>

                                    <div class="input-group">

                                        <input
                                            type="number"
                                            name="kilometraje_inicio"
                                            id="kilometraje_inicio"
                                            min="0"
                                            class="form-control @error('kilometraje_inicio') is-invalid @enderror"
                                            value="{{ old(
                                                'kilometraje_inicio',
                                                $bitacoraServicioPatrulla->kilometraje_inicio
                                            ) }}"
                                        >

                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                km
                                            </span>
                                        </div>

                                        @error('kilometraje_inicio')
                                            <span class="invalid-feedback">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror

                                    </div>
                                </div>
                            </div>


                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="kilometraje_fin">
                                        Kilometraje Final
                                    </label>

                                    <div class="input-group">

                                        <input
                                            type="number"
                                            name="kilometraje_fin"
                                            id="kilometraje_fin"
                                            min="0"
                                            class="form-control @error('kilometraje_fin') is-invalid @enderror"
                                            value="{{ old(
                                                'kilometraje_fin',
                                                $bitacoraServicioPatrulla->kilometraje_fin
                                            ) }}"
                                        >

                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                km
                                            </span>
                                        </div>

                                        @error('kilometraje_fin')
                                            <span class="invalid-feedback">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror

                                    </div>
                                </div>
                            </div>

                        </div>


                        <div class="alert alert-light border mb-0">

                            <div class="d-flex justify-content-between">

                                <span>
                                    <strong>Recorrido calculado:</strong>
                                </span>

                                <span id="recorridoCalculado">
                                    @if(!is_null($bitacoraServicioPatrulla->kilometros_recorridos))
                                        {{ number_format($bitacoraServicioPatrulla->kilometros_recorridos) }} km
                                    @else
                                        —
                                    @endif
                                </span>

                            </div>

                        </div>

                    </div>
                </div>

            </div>


            <div class="col-md-6">

                <div class="card card-outline card-warning h-100">

                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-solid fa-gas-pump"></i>
                            Combustible
                        </h3>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="combustible_inicio">
                                        Combustible Inicial
                                    </label>

                                    <div class="input-group">

                                        <input
                                            type="number"
                                            name="combustible_inicio"
                                            id="combustible_inicio"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            class="form-control @error('combustible_inicio') is-invalid @enderror"
                                            value="{{ old(
                                                'combustible_inicio',
                                                $bitacoraServicioPatrulla->combustible_inicio
                                            ) }}"
                                        >

                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                %
                                            </span>
                                        </div>

                                        @error('combustible_inicio')
                                            <span class="invalid-feedback">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror

                                    </div>
                                </div>
                            </div>


                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="combustible_fin">
                                        Combustible Final
                                    </label>

                                    <div class="input-group">

                                        <input
                                            type="number"
                                            name="combustible_fin"
                                            id="combustible_fin"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            class="form-control @error('combustible_fin') is-invalid @enderror"
                                            value="{{ old(
                                                'combustible_fin',
                                                $bitacoraServicioPatrulla->combustible_fin
                                            ) }}"
                                        >

                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                %
                                            </span>
                                        </div>

                                        @error('combustible_fin')
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


        <div class="row mt-3">
            <div class="col-md-12">

                <div class="card card-outline card-secondary">

                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-regular fa-note-sticky"></i>
                            Observaciones
                        </h3>
                    </div>

                    <div class="card-body">

                        <div class="form-group mb-0">

                            <label for="observaciones">
                                Observaciones del Servicio
                            </label>

                            <textarea
                                name="observaciones"
                                id="observaciones"
                                rows="5"
                                maxlength="5000"
                                class="form-control @error('observaciones') is-invalid @enderror"
                                placeholder="Observaciones relacionadas con el turno, la unidad o el servicio..."
                            >{{ old(
                                'observaciones',
                                $bitacoraServicioPatrulla->observaciones
                            ) }}</textarea>

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


                        <a href="{{ route('patrullas.bitacoras.show', [
                            'patrulla' => $patrulla->id,
                            'bitacoraServicioPatrulla' => $bitacoraServicioPatrulla->id
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

            const usuario = document.getElementById(
                'capturado_por_user_id'
            );

            const nombre = document.getElementById(
                'capturado_por_nombre'
            );

            const estatus = document.getElementById(
                'estatus'
            );

            const horaFin = document.getElementById(
                'hora_fin'
            );

            const kmInicio = document.getElementById(
                'kilometraje_inicio'
            );

            const kmFin = document.getElementById(
                'kilometraje_fin'
            );

            const recorrido = document.getElementById(
                'recorridoCalculado'
            );


            function actualizarNombreResponsable() {

                if (!usuario || !nombre) {
                    return;
                }

                if (usuario.value) {

                    const opcion =
                        usuario.options[usuario.selectedIndex];

                    const texto =
                        opcion.text.split('—')[0].trim();

                    nombre.value = texto;

                    nombre.setAttribute(
                        'readonly',
                        'readonly'
                    );

                    nombre.classList.add(
                        'bg-light'
                    );

                } else {

                    nombre.removeAttribute(
                        'readonly'
                    );

                    nombre.classList.remove(
                        'bg-light'
                    );

                }

            }


            function actualizarHoraFin() {

                if (!estatus || !horaFin) {
                    return;
                }

                if (estatus.value === 'cerrada') {

                    horaFin.setAttribute(
                        'required',
                        'required'
                    );

                } else {

                    horaFin.removeAttribute(
                        'required'
                    );

                }

            }


            function calcularRecorrido() {

                if (!kmInicio || !kmFin || !recorrido) {
                    return;
                }

                const inicio =
                    parseInt(kmInicio.value);

                const fin =
                    parseInt(kmFin.value);


                if (
                    Number.isNaN(inicio)
                    || Number.isNaN(fin)
                ) {

                    recorrido.textContent = '—';
                    return;

                }


                if (fin < inicio) {

                    recorrido.innerHTML =
                        '<span class="text-danger">Kilometraje inválido</span>';

                    return;

                }


                recorrido.textContent =
                    (fin - inicio).toLocaleString('es-MX')
                    + ' km';

            }


            actualizarNombreResponsable();

            actualizarHoraFin();

            calcularRecorrido();


            if (usuario) {

                usuario.addEventListener(
                    'change',
                    actualizarNombreResponsable
                );

            }


            if (estatus) {

                estatus.addEventListener(
                    'change',
                    actualizarHoraFin
                );

            }


            if (kmInicio) {

                kmInicio.addEventListener(
                    'input',
                    calcularRecorrido
                );

            }


            if (kmFin) {

                kmFin.addEventListener(
                    'input',
                    calcularRecorrido
                );

            }

        });

    </script>

@stop
