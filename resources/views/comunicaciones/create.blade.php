@extends('adminlte::page')

@section('title', 'Nueva Comunicación')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-0">
                <i class="fa-solid fa-paper-plane"></i>
                Nueva Comunicación
            </h1>
            <small class="text-muted">
                Envía una orden, aviso o mensaje
            </small>
        </div>

        <a href="{{ route('comunicaciones.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            Regresar
        </a>
    </div>
@stop

@section('content')

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>No se pudo enviar la comunicación.</strong>

            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('comunicaciones.store') }}"
          method="POST"
          enctype="multipart/form-data"
          id="formComunicacion">

        @csrf

        <div class="row">

            <div class="col-lg-8">

                <div class="card card-outline card-primary">

                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-solid fa-pen-to-square mr-1"></i>
                            Comunicación
                        </h3>
                    </div>

                    <div class="card-body">

                        <div class="form-group">
                            <label for="tipo">
                                Tipo de comunicación
                            </label>

                            <select name="tipo"
                                    id="tipo"
                                    class="form-control @error('tipo') is-invalid @enderror"
                                    required>

                                <option value="">
                                    Selecciona...
                                </option>

                                @if ($capacidades['orden'])
                                    <option value="orden"
                                        {{ old('tipo') === 'orden' ? 'selected' : '' }}>
                                        Orden
                                    </option>
                                @endif

                                @if ($capacidades['aviso'])
                                    <option value="aviso"
                                        {{ old('tipo') === 'aviso' ? 'selected' : '' }}>
                                        Aviso
                                    </option>
                                @endif

                                @if ($capacidades['mensaje'])
                                    <option value="mensaje"
                                        {{ old('tipo') === 'mensaje' ? 'selected' : '' }}>
                                        Mensaje directo
                                    </option>
                                @endif

                            </select>

                            @error('tipo')
                                <span class="invalid-feedback">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="form-group"
                             id="grupoAsunto">

                            <label for="asunto">
                                Asunto
                            </label>

                            <input type="text"
                                   name="asunto"
                                   id="asunto"
                                   value="{{ old('asunto') }}"
                                   maxlength="180"
                                   class="form-control @error('asunto') is-invalid @enderror"
                                   placeholder="Escribe el asunto">

                            @error('asunto')
                                <span class="invalid-feedback">
                                    {{ $message }}
                                </span>
                            @enderror

                            <small class="form-text text-muted">
                                Obligatorio para órdenes y avisos.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="contenido">
                                Contenido
                            </label>

                            <textarea name="contenido"
                                      id="contenido"
                                      rows="8"
                                      maxlength="10000"
                                      class="form-control @error('contenido') is-invalid @enderror"
                                      placeholder="Escribe la comunicación...">{{ old('contenido') }}</textarea>

                            @error('contenido')
                                <span class="invalid-feedback">
                                    {{ $message }}
                                </span>
                            @enderror

                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">
                                    El destinatario recibirá este contenido.
                                </small>

                                <small class="text-muted">
                                    <span id="contadorContenido">0</span>/10000
                                </small>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <label for="imagenes">
                                <i class="fa-regular fa-images mr-1"></i>
                                Imágenes
                            </label>

                            <div class="custom-file">

                                <input type="file"
                                       name="imagenes[]"
                                       id="imagenes"
                                       class="custom-file-input @error('imagenes') is-invalid @enderror @error('imagenes.*') is-invalid @enderror"
                                       accept="image/jpeg,image/png,image/webp"
                                       multiple>

                                <label class="custom-file-label"
                                       for="imagenes"
                                       data-browse="Buscar">
                                    Seleccionar imágenes...
                                </label>

                            </div>

                            <small class="form-text text-muted">
                                Puedes adjuntar hasta 10 imágenes JPG, PNG o WebP. Máximo 10 MB por imagen.
                            </small>

                            @error('imagenes')
                                <div class="text-danger small mt-1">
                                    {{ $message }}
                                </div>
                            @enderror

                            @error('imagenes.*')
                                <div class="text-danger small mt-1">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div id="previewImagenes"
                                 class="preview-imagenes mt-3">
                            </div>
                        </div>

                    </div>

                </div>

                <div class="card card-outline card-info">

                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-solid fa-users mr-1"></i>
                            Destinatarios
                        </h3>
                    </div>

                    <div class="card-body">

                        <div class="form-group">
                            <label for="alcance">
                                Enviar a
                            </label>

                            <select name="alcance"
                                    id="alcance"
                                    class="form-control @error('alcance') is-invalid @enderror"
                                    required>

                                <option value="">
                                    Selecciona...
                                </option>

                                @if ($capacidades['todos'])
                                    <option value="todos"
                                        {{ old('alcance') === 'todos' ? 'selected' : '' }}>
                                        Todo el personal
                                    </option>
                                @endif

                                @if ($capacidades['unidad'])
                                    <option value="unidad"
                                        {{ old('alcance') === 'unidad' ? 'selected' : '' }}>
                                        Una unidad
                                    </option>
                                @endif

                                @if ($capacidades['unidad_turno'])
                                    <option value="unidad_turno"
                                        {{ old('alcance') === 'unidad_turno' ? 'selected' : '' }}>
                                        Un turno de una unidad
                                    </option>
                                @endif

                                @if ($capacidades['subdirectores'])
                                    <option value="subdirectores"
                                        {{ old('alcance') === 'subdirectores' ? 'selected' : '' }}>
                                        Todos los Subdirectores
                                    </option>
                                @endif

                                @if ($capacidades['rol'])
                                    <option value="rol"
                                        {{ old('alcance') === 'rol' ? 'selected' : '' }}>
                                        Un rol específico
                                    </option>
                                @endif

                                @if ($capacidades['usuario'])
                                    <option value="usuario"
                                        {{ old('alcance') === 'usuario' ? 'selected' : '' }}>
                                        Una persona
                                    </option>
                                @endif

                            </select>

                            @error('alcance')
                                <span class="invalid-feedback">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="form-group campo-destino"
                             id="campoUnidad">

                            <label for="unidad_id">
                                Unidad
                            </label>

                            <select name="unidad_id"
                                    id="unidad_id"
                                    class="form-control @error('unidad_id') is-invalid @enderror">

                                <option value="">
                                    Selecciona una unidad...
                                </option>

                                @foreach ($unidades as $unidad)
                                    <option value="{{ $unidad->id }}"
                                        {{ (string) old('unidad_id') === (string) $unidad->id ? 'selected' : '' }}>
                                        {{ $unidad->nombre }}
                                    </option>
                                @endforeach

                            </select>

                            @error('unidad_id')
                                <span class="invalid-feedback">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="form-group campo-destino"
                             id="campoTurno">

                            <label for="turno_id">
                                Turno
                            </label>

                            <select name="turno_id"
                                    id="turno_id"
                                    class="form-control @error('turno_id') is-invalid @enderror">

                                <option value="">
                                    Selecciona un turno...
                                </option>

                                @foreach ($turnos as $turno)
                                    <option value="{{ $turno->id }}"
                                        {{ (string) old('turno_id') === (string) $turno->id ? 'selected' : '' }}>
                                        {{ $turno->nombre }}
                                    </option>
                                @endforeach

                            </select>

                            @error('turno_id')
                                <span class="invalid-feedback">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="form-group campo-destino"
                             id="campoRol">

                            <label for="role_id">
                                Rol
                            </label>

                            <select name="role_id"
                                    id="role_id"
                                    class="form-control @error('role_id') is-invalid @enderror">

                                <option value="">
                                    Selecciona un rol...
                                </option>

                                @foreach ($roles as $rol)
                                    <option value="{{ $rol->id }}"
                                        {{ (string) old('role_id') === (string) $rol->id ? 'selected' : '' }}>

                                        {{ $rol->name }}

                                        @if ($rol->unidad)
                                            — {{ $rol->unidad->nombre }}
                                        @endif

                                    </option>
                                @endforeach

                            </select>

                            @error('role_id')
                                <span class="invalid-feedback">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="campo-destino"
                             id="campoUsuario">

                            <div class="form-group mb-2">

                                <label for="buscarUsuario">
                                    Buscar persona
                                </label>

                                <div class="input-group">

                                    <input type="text"
                                           id="buscarUsuario"
                                           class="form-control"
                                           autocomplete="off"
                                           placeholder="Nombre, apellido o correo">

                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <i class="fa-solid fa-search"></i>
                                        </span>
                                    </div>

                                </div>

                            </div>

                            <input type="hidden"
                                   name="destinatario_user_id"
                                   id="destinatario_user_id"
                                   value="{{ old('destinatario_user_id') }}">

                            @error('destinatario_user_id')
                                <div class="text-danger small mb-2">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div id="usuarioSeleccionado"
                                 class="usuario-seleccionado d-none">

                                <div>

                                    <span class="avatar-usuario">
                                        <i class="fa-solid fa-user"></i>
                                    </span>

                                    <div>
                                        <strong id="usuarioSeleccionadoNombre"></strong>

                                        <div class="small text-muted"
                                             id="usuarioSeleccionadoDetalle">
                                        </div>
                                    </div>

                                </div>

                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        id="quitarUsuario">

                                    <i class="fa-solid fa-xmark"></i>

                                </button>

                            </div>

                            <div id="resultadosUsuarios"
                                 class="list-group resultados-usuarios">
                            </div>

                            <div id="buscandoUsuarios"
                                 class="text-center text-muted py-3 d-none">

                                <i class="fa-solid fa-spinner fa-spin mr-1"></i>
                                Buscando personal...

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <div class="col-lg-4">

                <div class="card card-outline card-secondary sticky-resumen">

                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-solid fa-eye mr-1"></i>
                            Resumen
                        </h3>
                    </div>

                    <div class="card-body">

                        <div class="resumen-item">
                            <span>Tipo</span>

                            <strong id="resumenTipo">
                                Sin seleccionar
                            </strong>
                        </div>

                        <div class="resumen-item">
                            <span>Destinatario</span>

                            <strong id="resumenDestino">
                                Sin seleccionar
                            </strong>
                        </div>

                        <div class="resumen-item">
                            <span>Confirmación</span>

                            <strong id="resumenEnterado">
                                No aplica
                            </strong>
                        </div>

                        <hr>

                        <div id="grupoEnterado"
                             class="form-group mb-0">

                            <div class="custom-control custom-switch">

                                <input type="hidden"
                                       name="requiere_enterado"
                                       value="0">

                                <input type="checkbox"
                                       class="custom-control-input"
                                       name="requiere_enterado"
                                       value="1"
                                       id="requiere_enterado"
                                       {{ old('requiere_enterado') ? 'checked' : '' }}>

                                <label class="custom-control-label"
                                       for="requiere_enterado">
                                    Requiere confirmación de enterado
                                </label>

                            </div>

                            <small class="form-text text-muted"
                                   id="textoEnterado">
                                Disponible para avisos.
                            </small>

                        </div>

                    </div>

                    <div class="card-footer">

                        <button type="submit"
                                class="btn btn-primary btn-block btn-lg"
                                id="btnEnviar">

                            <i class="fa-solid fa-paper-plane mr-1"></i>
                            Enviar comunicación

                        </button>

                        <a href="{{ route('comunicaciones.index') }}"
                           class="btn btn-default btn-block">
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

    select.form-control {
        background-color: #111827 !important;
        color: #e5e7eb !important;
        border: 1px solid rgba(255, 255, 255, .18) !important;
        color-scheme: dark;
    }

    select.form-control:focus {
        background-color: #0b1220 !important;
        color: #e5e7eb !important;
        border-color: rgba(59, 130, 246, .65) !important;
        box-shadow: 0 0 0 .2rem rgba(59, 130, 246, .25) !important;
    }

    select.form-control option {
        background-color: #111827 !important;
        color: #e5e7eb !important;
    }

    select.form-control option:checked {
        background-color: #2563eb !important;
        color: #ffffff !important;
    }

    select.form-control option:disabled {
        color: rgba(229, 231, 235, .55) !important;
    }

    input.form-control,
    textarea.form-control {
        background-color: #111827 !important;
        color: #e5e7eb !important;
        border: 1px solid rgba(255, 255, 255, .18) !important;
    }

    input.form-control:focus,
    textarea.form-control:focus {
        background-color: #0b1220 !important;
        color: #e5e7eb !important;
        border-color: rgba(59, 130, 246, .65) !important;
        box-shadow: 0 0 0 .2rem rgba(59, 130, 246, .25) !important;
    }

    input.form-control::placeholder,
    textarea.form-control::placeholder {
        color: #94a3b8 !important;
        opacity: 1;
    }

    .input-group-text {
        background-color: #111827 !important;
        color: #94a3b8 !important;
        border-color: rgba(255, 255, 255, .18) !important;
    }

    .campo-destino {
        display: none;
    }

    .sticky-resumen {
        position: sticky;
        top: 72px;
    }

    .resumen-item {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 15px;
        padding: 12px 0;
        border-bottom: 1px solid rgba(255, 255, 255, .08);
    }

    .resumen-item:last-child {
        border-bottom: 0;
    }

    .resumen-item span {
        color: #94a3b8;
    }

    .resumen-item strong {
        color: #f8fafc;
        text-align: right;
    }

    .resultados-usuarios {
        max-height: 320px;
        overflow-y: auto;
        border-radius: 6px;
    }

    .resultado-usuario {
        cursor: pointer;
        border-left: 0;
        border-right: 0;
        background-color: #111827;
        color: #e5e7eb;
        border-color: rgba(255, 255, 255, .10);
    }

    .resultado-usuario:hover,
    .resultado-usuario:focus {
        background-color: #1e293b;
        color: #ffffff;
    }

    .resultado-usuario .nombre {
        font-weight: 600;
    }

    .resultado-usuario .detalle {
        color: #94a3b8;
        font-size: .82rem;
    }

    .usuario-seleccionado {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 14px;
        border: 1px solid rgba(59, 130, 246, .45);
        border-radius: 6px;
        background: rgba(37, 99, 235, .10);
    }

    .usuario-seleccionado > div {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .avatar-usuario {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #2563eb;
        color: #ffffff;
    }

    textarea {
        resize: vertical;
        min-height: 180px;
    }

    .custom-control-label {
        cursor: pointer;
    }

    .custom-file {
        height: 42px;
    }

    .custom-file-input {
        cursor: pointer;
    }

    .custom-file-label {
        height: 42px;
        line-height: 26px;
        background-color: #111827 !important;
        color: #e5e7eb !important;
        border: 1px solid rgba(255, 255, 255, .18) !important;
        border-radius: .25rem;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .custom-file-label::after {
        height: 40px;
        line-height: 26px;
        background-color: #2563eb !important;
        color: #ffffff !important;
        border-left: 1px solid #2563eb !important;
    }

    .custom-file-input:focus ~ .custom-file-label {
        border-color: rgba(59, 130, 246, .65) !important;
        box-shadow: 0 0 0 .2rem rgba(59, 130, 246, .25) !important;
    }

    .preview-imagenes {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 12px;
    }

    .preview-imagen {
        position: relative;
        height: 130px;
        overflow: hidden;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, .14);
        background: #0b1220;
        box-shadow: 0 3px 10px rgba(0, 0, 0, .20);
    }

    .preview-imagen img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .preview-imagen .preview-nombre {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 6px 8px;
        background: rgba(0, 0, 0, .75);
        color: #ffffff;
        font-size: 11px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    @media (max-width: 991px) {
        .sticky-resumen {
            position: static;
        }
    }

    @media (max-width: 575px) {
        .preview-imagenes {
            grid-template-columns: repeat(2, 1fr);
        }

        .preview-imagen {
            height: 110px;
        }
    }
</style>
@stop

@section('js')
<script>
    $(function () {

        const capacidades = @json($capacidades);

        let temporizadorBusqueda = null;

        const $tipo = $('#tipo');
        const $alcance = $('#alcance');
        const $unidad = $('#unidad_id');
        const $turno = $('#turno_id');
        const $rol = $('#role_id');
        const $destinatario = $('#destinatario_user_id');
        const $buscarUsuario = $('#buscarUsuario');
        const $resultadosUsuarios = $('#resultadosUsuarios');
        const $requiereEnterado = $('#requiere_enterado');
        const $imagenes = $('#imagenes');
        const $previewImagenes = $('#previewImagenes');
        const $contenido = $('#contenido');

        function actualizarContador() {
            $('#contadorContenido').text(
                $contenido.val().length
            );
        }

        function mostrarPreviewImagenes() {

            $previewImagenes.empty();

            const archivos = Array.from(
                $imagenes[0]?.files || []
            );

            if (!archivos.length) {
                $imagenes
                    .next('.custom-file-label')
                    .text('Seleccionar imágenes...');

                return;
            }

            $imagenes
                .next('.custom-file-label')
                .text(
                    archivos.length === 1
                        ? archivos[0].name
                        : archivos.length + ' imágenes seleccionadas'
                );

            archivos.forEach(function (archivo) {

                if (!archivo.type.startsWith('image/')) {
                    return;
                }

                const lector = new FileReader();

                lector.onload = function (e) {

                    const $contenedor = $('<div>', {
                        class: 'preview-imagen'
                    });

                    $('<img>', {
                        src: e.target.result,
                        alt: archivo.name
                    }).appendTo($contenedor);

                    $('<div>', {
                        class: 'preview-nombre',
                        text: archivo.name
                    }).appendTo($contenedor);

                    $previewImagenes.append($contenedor);
                };

                lector.readAsDataURL(archivo);
            });
        }

        function validarImagenes() {

            const archivos = Array.from(
                $imagenes[0]?.files || []
            );

            if (archivos.length > 10) {

                Swal.fire({
                    icon: 'warning',
                    title: 'Demasiadas imágenes',
                    text: 'Puedes seleccionar un máximo de 10 imágenes.'
                });

                $imagenes.val('');
                mostrarPreviewImagenes();

                return false;
            }

            const tiposPermitidos = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            for (const archivo of archivos) {

                if (!tiposPermitidos.includes(archivo.type)) {

                    Swal.fire({
                        icon: 'warning',
                        title: 'Formato no permitido',
                        text: 'Solamente puedes adjuntar imágenes JPG, PNG o WebP.'
                    });

                    $imagenes.val('');
                    mostrarPreviewImagenes();

                    return false;
                }

                if (archivo.size > 10 * 1024 * 1024) {

                    Swal.fire({
                        icon: 'warning',
                        title: 'Imagen demasiado grande',
                        text: 'Cada imagen puede pesar como máximo 10 MB.'
                    });

                    $imagenes.val('');
                    mostrarPreviewImagenes();

                    return false;
                }
            }

            return true;
        }

        function ocultarCamposDestino() {
            $('#campoUnidad').hide();
            $('#campoTurno').hide();
            $('#campoRol').hide();
            $('#campoUsuario').hide();
        }

        function actualizarCamposDestino() {

            ocultarCamposDestino();

            const alcance = $alcance.val();

            if (alcance === 'unidad') {
                $('#campoUnidad').show();
            }

            if (alcance === 'unidad_turno') {
                $('#campoUnidad').show();
                $('#campoTurno').show();
            }

            if (alcance === 'rol') {
                $('#campoRol').show();

                if (capacidades.unidad) {
                    $('#campoUnidad').show();
                }
            }

            if (alcance === 'usuario') {
                $('#campoUsuario').show();

                if (!$destinatario.val()) {
                    cargarUsuarios('');
                }
            }

            actualizarResumen();
        }

        function actualizarTipo() {

            const tipo = $tipo.val();

            if (tipo === 'mensaje') {

                $('#grupoAsunto').hide();

                if ($alcance.val() !== 'usuario') {
                    $alcance.val('usuario');
                }

                $('#alcance option').each(function () {

                    const valor = $(this).val();

                    if (valor && valor !== 'usuario') {
                        $(this).prop('disabled', true);
                    } else {
                        $(this).prop('disabled', false);
                    }
                });

                $requiereEnterado
                    .prop('checked', false)
                    .prop('disabled', true);

                $('#grupoEnterado').hide();

            } else {

                $('#grupoAsunto').show();

                $('#alcance option').prop('disabled', false);

                if (tipo === 'orden') {

                    $requiereEnterado
                        .prop('checked', true)
                        .prop('disabled', true);

                    $('#grupoEnterado').show();

                    $('#textoEnterado').text(
                        'Las órdenes requieren confirmación de enterado.'
                    );

                } else if (tipo === 'aviso') {

                    $requiereEnterado.prop('disabled', false);

                    $('#grupoEnterado').show();

                    $('#textoEnterado').text(
                        'Puedes solicitar confirmación de enterado.'
                    );

                } else {

                    $requiereEnterado
                        .prop('checked', false)
                        .prop('disabled', true);

                    $('#grupoEnterado').hide();
                }
            }

            actualizarCamposDestino();
            actualizarResumen();
        }

        function textoAlcance() {

            const alcance = $alcance.val();

            if (alcance === 'todos') {
                return 'Todo el personal';
            }

            if (alcance === 'unidad') {

                const texto = $unidad
                    .find('option:selected')
                    .text()
                    .trim();

                return $unidad.val()
                    ? texto
                    : 'Unidad';
            }

            if (alcance === 'unidad_turno') {

                const unidad = $unidad
                    .find('option:selected')
                    .text()
                    .trim();

                const turno = $turno
                    .find('option:selected')
                    .text()
                    .trim();

                if ($unidad.val() && $turno.val()) {
                    return unidad + ' / ' + turno;
                }

                return 'Unidad y turno';
            }

            if (alcance === 'subdirectores') {
                return 'Todos los Subdirectores';
            }

            if (alcance === 'rol') {

                const texto = $rol
                    .find('option:selected')
                    .text()
                    .trim();

                return $rol.val()
                    ? texto
                    : 'Rol';
            }

            if (alcance === 'usuario') {

                const nombre = $('#usuarioSeleccionadoNombre')
                    .text()
                    .trim();

                return nombre || 'Una persona';
            }

            return 'Sin seleccionar';
        }

        function actualizarResumen() {

            const tipo = $tipo.val();

            let tipoTexto = 'Sin seleccionar';

            if (tipo === 'orden') {
                tipoTexto = 'Orden';
            }

            if (tipo === 'aviso') {
                tipoTexto = 'Aviso';
            }

            if (tipo === 'mensaje') {
                tipoTexto = 'Mensaje directo';
            }

            $('#resumenTipo').text(tipoTexto);
            $('#resumenDestino').text(textoAlcance());

            if (tipo === 'orden') {

                $('#resumenEnterado').text('Obligatoria');

            } else if (
                tipo === 'aviso'
                && $requiereEnterado.is(':checked')
            ) {

                $('#resumenEnterado').text('Sí');

            } else {

                $('#resumenEnterado').text('No');
            }
        }

        function limpiarUsuario() {

            $destinatario.val('');

            $('#usuarioSeleccionado')
                .addClass('d-none');

            $('#usuarioSeleccionadoNombre').text('');
            $('#usuarioSeleccionadoDetalle').text('');

            $buscarUsuario.val('');
            $resultadosUsuarios.empty();

            actualizarResumen();
        }

        function seleccionarUsuario(usuario) {

            $destinatario.val(usuario.id);

            $('#usuarioSeleccionadoNombre')
                .text(usuario.nombre);

            const detalles = [];

            if (usuario.unidad) {
                detalles.push(usuario.unidad);
            }

            if (usuario.turno) {
                detalles.push(usuario.turno);
            }

            if (
                usuario.roles
                && usuario.roles.length
            ) {
                detalles.push(
                    usuario.roles.join(', ')
                );
            }

            $('#usuarioSeleccionadoDetalle')
                .text(detalles.join(' · '));

            $('#usuarioSeleccionado')
                .removeClass('d-none');

            $resultadosUsuarios.empty();
            $buscarUsuario.val('');

            actualizarResumen();
        }

        function cargarUsuarios(busqueda) {

            $('#buscandoUsuarios')
                .removeClass('d-none');

            $.ajax({
                url: '{{ route('comunicaciones.destinatarios') }}',
                method: 'GET',
                data: {
                    q: busqueda
                },
                success: function (respuesta) {

                    $resultadosUsuarios.empty();

                    const usuarios =
                        respuesta.usuarios || [];

                    if (!usuarios.length) {

                        $resultadosUsuarios.html(
                            '<div class="text-center text-muted py-3">' +
                                'No se encontraron usuarios.' +
                            '</div>'
                        );

                        return;
                    }

                    usuarios.forEach(function (usuario) {

                        const $item = $('<button>', {
                            type: 'button',
                            class: 'list-group-item list-group-item-action resultado-usuario'
                        });

                        $('<div>', {
                            class: 'nombre',
                            text: usuario.nombre
                        }).appendTo($item);

                        const detalle = [];

                        if (usuario.unidad) {
                            detalle.push(usuario.unidad);
                        }

                        if (usuario.turno) {
                            detalle.push(usuario.turno);
                        }

                        if (
                            usuario.roles
                            && usuario.roles.length
                        ) {
                            detalle.push(
                                usuario.roles.join(', ')
                            );
                        }

                        $('<div>', {
                            class: 'detalle',
                            text: detalle.join(' · ')
                        }).appendTo($item);

                        $item.on('click', function () {
                            seleccionarUsuario(usuario);
                        });

                        $resultadosUsuarios.append($item);
                    });
                },
                error: function () {

                    $resultadosUsuarios.html(
                        '<div class="text-center text-danger py-3">' +
                            'No fue posible cargar los usuarios.' +
                        '</div>'
                    );
                },
                complete: function () {

                    $('#buscandoUsuarios')
                        .addClass('d-none');
                }
            });
        }

        $tipo.on('change', actualizarTipo);

        $alcance.on('change', function () {

            if ($(this).val() !== 'usuario') {
                limpiarUsuario();
            }

            actualizarCamposDestino();
        });

        $unidad.on('change', actualizarResumen);
        $turno.on('change', actualizarResumen);
        $rol.on('change', actualizarResumen);
        $requiereEnterado.on('change', actualizarResumen);

        $contenido.on(
            'input',
            actualizarContador
        );

        $imagenes.on('change', function () {

            if (!validarImagenes()) {
                return;
            }

            mostrarPreviewImagenes();
        });

        $buscarUsuario.on('input', function () {

            clearTimeout(
                temporizadorBusqueda
            );

            const termino =
                $(this).val().trim();

            temporizadorBusqueda =
                setTimeout(function () {
                    cargarUsuarios(termino);
                }, 300);
        });

        $('#quitarUsuario').on(
            'click',
            function () {
                limpiarUsuario();
                cargarUsuarios('');
            }
        );

        $('#formComunicacion').on(
            'submit',
            function (e) {

                const tipo = $tipo.val();
                const alcance = $alcance.val();
                const contenido = $contenido.val().trim();

                const cantidadImagenes =
                    $imagenes[0]?.files?.length || 0;

                if (!validarImagenes()) {
                    e.preventDefault();
                    return;
                }

                if (
                    alcance === 'usuario'
                    && !$destinatario.val()
                ) {
                    e.preventDefault();

                    Swal.fire({
                        icon: 'warning',
                        title: 'Selecciona un destinatario',
                        text: 'Debes seleccionar a la persona que recibirá la comunicación.'
                    });

                    return;
                }

                if (
                    tipo !== 'mensaje'
                    && !$('#asunto').val().trim()
                ) {
                    e.preventDefault();

                    Swal.fire({
                        icon: 'warning',
                        title: 'Falta el asunto',
                        text: 'Las órdenes y avisos deben tener un asunto.'
                    });

                    return;
                }

                if (
                    (tipo === 'orden' || tipo === 'aviso')
                    && !contenido
                ) {
                    e.preventDefault();

                    Swal.fire({
                        icon: 'warning',
                        title: 'Falta el contenido',
                        text: 'Las órdenes y avisos deben contener texto.'
                    });

                    return;
                }

                if (
                    tipo === 'mensaje'
                    && !contenido
                    && cantidadImagenes === 0
                ) {
                    e.preventDefault();

                    Swal.fire({
                        icon: 'warning',
                        title: 'Mensaje vacío',
                        text: 'Escribe un mensaje o adjunta al menos una imagen.'
                    });

                    return;
                }

                $('#btnEnviar')
                    .prop('disabled', true)
                    .html(
                        '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Enviando...'
                    );
            }
        );

        actualizarContador();
        actualizarTipo();
        mostrarPreviewImagenes();

        @if (old('destinatario_user_id'))

            $.ajax({
                url: '{{ route('comunicaciones.destinatarios') }}',
                method: 'GET',
                data: {
                    q: ''
                },
                success: function (respuesta) {

                    const usuario =
                        (respuesta.usuarios || [])
                        .find(function (item) {
                            return String(item.id)
                                === @json((string) old('destinatario_user_id'));
                        });

                    if (usuario) {
                        seleccionarUsuario(usuario);
                    }
                }
            });

        @endif

    });
</script>
@stop
