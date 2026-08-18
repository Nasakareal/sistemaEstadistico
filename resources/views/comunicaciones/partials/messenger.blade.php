@auth

<div id="svMessenger">

    <button type="button"
            id="svMessengerButton"
            class="sv-messenger-button"
            title="Mensajes">

        <i class="fa-solid fa-comment-dots"></i>

        <span id="svMessengerBadge"
              class="sv-messenger-badge d-none">
            0
        </span>

    </button>

    <div id="svMessengerPanel"
         class="sv-messenger-panel d-none">

        <div class="sv-messenger-header">

            <div>
                <strong>
                    <i class="fa-solid fa-comments mr-1"></i>
                    Mensajes
                </strong>
            </div>

            <div>

                <a href="{{ route('comunicaciones.index') }}"
                   class="sv-header-btn"
                   title="Centro de comunicaciones">

                    <i class="fa-solid fa-inbox"></i>

                </a>

                <button type="button"
                        id="svMessengerClose"
                        class="sv-header-btn"
                        title="Cerrar">

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>

        </div>

        <div id="svMessengerLista"
             class="sv-messenger-lista">

            <div class="sv-messenger-search">

                <div class="input-group">

                    <input type="text"
                           id="svMessengerBuscar"
                           class="form-control"
                           autocomplete="off"
                           placeholder="Buscar personal...">

                    <div class="input-group-append">

                        <span class="input-group-text">
                            <i class="fa-solid fa-search"></i>
                        </span>

                    </div>

                </div>

            </div>

            <div id="svMessengerUsuarios"
                 class="sv-messenger-usuarios">
            </div>

            <div id="svMessengerCargandoUsuarios"
                 class="sv-messenger-empty d-none">

                <i class="fa-solid fa-spinner fa-spin"></i>
                <div>Cargando...</div>

            </div>

        </div>

        <div id="svMessengerConversacion"
             class="sv-messenger-conversacion d-none">

            <div class="sv-chat-user">

                <button type="button"
                        id="svChatBack"
                        class="sv-header-btn"
                        title="Regresar">

                    <i class="fa-solid fa-arrow-left"></i>

                </button>

                <div class="sv-chat-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div class="sv-chat-user-info">

                    <strong id="svChatNombre"></strong>

                    <small id="svChatDetalle"></small>

                </div>

            </div>

            <div class="sv-chat-area">

                <div id="svChatMensajes"
                     class="sv-chat-mensajes">
                </div>

                <div id="svChatSinMensajes"
                     class="sv-messenger-empty sv-chat-empty d-none">

                    <i class="fa-regular fa-comments"></i>

                    <div>
                        No hay mensajes todavía.
                    </div>

                </div>

            </div>

            <form id="svChatForm"
                  class="sv-chat-form"
                  enctype="multipart/form-data">

                <input type="hidden"
                       id="svChatDestinatario">

                <input type="file"
                       id="svChatImagenes"
                       accept="image/jpeg,image/png,image/webp"
                       multiple
                       hidden>

                <div id="svChatPreview"
                     class="sv-chat-preview d-none">
                </div>

                <div class="sv-chat-input-row">

                    <button type="button"
                            id="svChatAdjuntar"
                            class="sv-chat-adjuntar"
                            title="Adjuntar imágenes">

                        <i class="fa-regular fa-image"></i>

                    </button>

                    <textarea id="svChatTexto"
                              rows="1"
                              maxlength="10000"
                              placeholder="Escribe un mensaje..."></textarea>

                    <button type="submit"
                            id="svChatEnviar"
                            class="sv-chat-enviar"
                            title="Enviar">

                        <i class="fa-solid fa-paper-plane"></i>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<style>

    #svMessenger {
        position: fixed;
        right: 22px;
        bottom: 22px;
        z-index: 9999;
        font-family: inherit;
    }

    .sv-messenger-button {
        position: relative;
        width: 58px;
        height: 58px;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: #2563eb;
        color: #ffffff;
        font-size: 23px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 28px rgba(0, 0, 0, .38);
        transition: transform .15s ease, background .15s ease;
    }

    .sv-messenger-button:hover {
        background: #1d4ed8;
        color: #ffffff;
        transform: scale(1.05);
    }

    .sv-messenger-button:focus {
        outline: none;
    }

    .sv-messenger-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        min-width: 23px;
        height: 23px;
        padding: 0 6px;
        border-radius: 15px;
        background: #dc3545;
        color: #ffffff;
        border: 2px solid #111827;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sv-messenger-panel {
        position: absolute;
        right: 0;
        bottom: 72px;
        width: 380px;
        height: 560px;
        max-height: calc(100vh - 110px);
        background: #111827;
        border: 1px solid rgba(255, 255, 255, .13);
        border-radius: 14px;
        box-shadow: 0 18px 55px rgba(0, 0, 0, .5);
        overflow: hidden;
        color: #e5e7eb;
    }

    .sv-messenger-header {
        height: 58px;
        padding: 0 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #172033;
        border-bottom: 1px solid rgba(255, 255, 255, .08);
        font-size: 17px;
    }

    .sv-messenger-header > div:last-child {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .sv-header-btn {
        width: 34px;
        height: 34px;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: transparent;
        color: #cbd5e1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none !important;
    }

    .sv-header-btn:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, .09);
    }

    .sv-header-btn:focus {
        outline: none;
    }

    .sv-messenger-lista {
        height: calc(100% - 58px);
        display: flex;
        flex-direction: column;
    }

    .sv-messenger-search {
        flex: 0 0 auto;
        padding: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, .07);
    }

    .sv-messenger-search .form-control {
        background: #0b1220 !important;
        color: #e5e7eb !important;
        border-color: rgba(255, 255, 255, .14) !important;
    }

    .sv-messenger-search .form-control:focus {
        background: #0b1220 !important;
        color: #ffffff !important;
        border-color: rgba(59, 130, 246, .7) !important;
        box-shadow: 0 0 0 .2rem rgba(59, 130, 246, .16) !important;
    }

    .sv-messenger-search .form-control::placeholder {
        color: #94a3b8;
    }

    .sv-messenger-search .input-group-text {
        background: #0b1220 !important;
        color: #94a3b8;
        border-color: rgba(255, 255, 255, .14) !important;
    }

    .sv-messenger-usuarios {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }

    .sv-messenger-user {
        width: 100%;
        border: 0;
        background: transparent;
        color: #e5e7eb;
        text-align: left;
        padding: 11px 14px;
        display: flex;
        align-items: center;
        gap: 11px;
        border-bottom: 1px solid rgba(255, 255, 255, .055);
    }

    .sv-messenger-user:hover {
        background: rgba(37, 99, 235, .14);
        color: #ffffff;
    }

    .sv-messenger-user:focus {
        outline: none;
    }

    .sv-user-avatar,
    .sv-chat-avatar {
        flex: 0 0 auto;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #253149;
        color: #93c5fd;
    }

    .sv-user-data {
        min-width: 0;
        flex: 1;
    }

    .sv-user-data strong {
        display: block;
        color: #f8fafc;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sv-user-data small {
        display: block;
        color: #94a3b8;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sv-messenger-empty {
        color: #94a3b8;
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 25px;
    }

    .sv-messenger-empty > i {
        font-size: 25px;
    }

    .sv-messenger-conversacion {
        height: calc(100% - 58px);
        display: flex;
        flex-direction: column;
    }

    .sv-chat-user {
        flex: 0 0 60px;
        height: 60px;
        padding: 0 10px;
        display: flex;
        align-items: center;
        gap: 9px;
        background: #172033;
        border-bottom: 1px solid rgba(255, 255, 255, .08);
    }

    .sv-chat-avatar {
        width: 36px;
        height: 36px;
    }

    .sv-chat-user-info {
        min-width: 0;
        flex: 1;
    }

    .sv-chat-user-info strong {
        display: block;
        color: #f8fafc;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sv-chat-user-info small {
        display: block;
        color: #94a3b8;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sv-chat-area {
        position: relative;
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
        background: #111827;
    }

    .sv-chat-mensajes {
        width: 100%;
        height: 100%;
        overflow-y: auto;
        padding: 14px 12px;
        display: flex;
        flex-direction: column;
        gap: 9px;
    }

    .sv-chat-empty {
        position: absolute;
        inset: 0;
        padding: 20px;
    }

    .sv-chat-mensaje {
        max-width: 82%;
        padding: 9px 12px;
        border-radius: 16px;
        line-height: 1.35;
        word-break: break-word;
    }

    .sv-chat-mensaje-mio {
        align-self: flex-end;
        background: #2563eb;
        color: #ffffff;
        border-bottom-right-radius: 5px;
    }

    .sv-chat-mensaje-otro {
        align-self: flex-start;
        background: #263248;
        color: #f1f5f9;
        border-bottom-left-radius: 5px;
    }

    .sv-chat-texto {
        white-space: pre-wrap;
    }

    .sv-chat-hora {
        display: block;
        margin-top: 5px;
        font-size: 10px;
        opacity: .68;
        text-align: right;
    }

    .sv-chat-adjuntos {
        display: grid;
        gap: 5px;
        margin-top: 3px;
        margin-bottom: 3px;
    }

    .sv-chat-adjuntos-1 {
        grid-template-columns: 1fr;
    }

    .sv-chat-adjuntos-varios {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .sv-chat-imagen {
        position: relative;
        display: block;
        overflow: hidden;
        min-width: 150px;
        max-width: 260px;
        height: 175px;
        border-radius: 11px;
        background: #0b1220;
        color: inherit;
    }

    .sv-chat-adjuntos-varios .sv-chat-imagen {
        min-width: 0;
        width: 115px;
        height: 115px;
    }

    .sv-chat-imagen img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .sv-chat-imagen-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, .25);
        color: #ffffff;
        opacity: 0;
        transition: opacity .15s ease;
    }

    .sv-chat-imagen:hover .sv-chat-imagen-overlay {
        opacity: 1;
    }

    .sv-chat-form {
        flex: 0 0 auto;
        padding: 8px 10px 10px;
        border-top: 1px solid rgba(255, 255, 255, .08);
        background: #172033;
    }

    .sv-chat-input-row {
        display: flex;
        align-items: flex-end;
        gap: 7px;
    }

    .sv-chat-form textarea {
        flex: 1;
        min-width: 0;
        max-height: 90px;
        min-height: 42px;
        resize: none;
        border: 1px solid rgba(255, 255, 255, .14);
        border-radius: 21px;
        background: #0b1220;
        color: #f8fafc;
        padding: 10px 15px;
        outline: none;
    }

    .sv-chat-form textarea:focus {
        border-color: rgba(59, 130, 246, .7);
    }

    .sv-chat-form textarea::placeholder {
        color: #94a3b8;
    }

    .sv-chat-adjuntar,
    .sv-chat-enviar {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        padding: 0;
        border: 0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sv-chat-adjuntar {
        background: #263248;
        color: #93c5fd;
    }

    .sv-chat-adjuntar:hover {
        background: #334155;
        color: #ffffff;
    }

    .sv-chat-enviar {
        background: #2563eb;
        color: #ffffff;
    }

    .sv-chat-enviar:hover {
        background: #1d4ed8;
        color: #ffffff;
    }

    .sv-chat-adjuntar:focus,
    .sv-chat-enviar:focus {
        outline: none;
    }

    .sv-chat-adjuntar:disabled,
    .sv-chat-enviar:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    .sv-chat-preview {
        display: flex;
        gap: 7px;
        padding: 0 2px 8px;
        overflow-x: auto;
    }

    .sv-chat-preview-item {
        position: relative;
        flex: 0 0 62px;
        width: 62px;
        height: 62px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, .14);
        background: #0b1220;
    }

    .sv-chat-preview-item img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .sv-chat-preview-remove {
        position: absolute;
        top: 3px;
        right: 3px;
        width: 20px;
        height: 20px;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: rgba(0, 0, 0, .78);
        color: #ffffff;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sv-chat-preview-remove:hover {
        background: #dc3545;
    }

    #svMessengerUsuarios::-webkit-scrollbar,
    #svChatMensajes::-webkit-scrollbar,
    #svChatPreview::-webkit-scrollbar {
        width: 7px;
        height: 7px;
    }

    #svMessengerUsuarios::-webkit-scrollbar-thumb,
    #svChatMensajes::-webkit-scrollbar-thumb,
    #svChatPreview::-webkit-scrollbar-thumb {
        background: #475569;
        border-radius: 10px;
    }

    @media (max-width: 575px) {

        #svMessenger {
            right: 12px;
            bottom: 12px;
        }

        .sv-messenger-panel {
            position: fixed;
            left: 10px;
            right: 10px;
            bottom: 82px;
            width: auto;
            height: calc(100vh - 105px);
            max-height: none;
        }

        .sv-chat-imagen {
            min-width: 145px;
            max-width: 220px;
            height: 155px;
        }

        .sv-chat-adjuntos-varios .sv-chat-imagen {
            width: 105px;
            height: 105px;
        }

    }

</style>

@push('js')
<script>

    $(function () {

        const urls = {
            usuarios: @json(route('comunicaciones.destinatarios')),
            index: @json(route('comunicaciones.index')),
            store: @json(route('comunicaciones.store')),
            pendientes: @json(route('comunicaciones.no_leidas.count'))
        };

        const csrf = @json(csrf_token());

        let usuarioActual = null;
        let temporizadorBusqueda = null;
        let ultimaNotificacionId = null;
        let primeraConsulta = true;
        let audioHabilitado = false;
        let archivosChat = [];

        const $panel = $('#svMessengerPanel');
        const $lista = $('#svMessengerLista');
        const $conversacion = $('#svMessengerConversacion');
        const $usuarios = $('#svMessengerUsuarios');
        const $buscar = $('#svMessengerBuscar');
        const $mensajes = $('#svChatMensajes');
        const $badge = $('#svMessengerBadge');
        const $texto = $('#svChatTexto');
        const $imagenes = $('#svChatImagenes');
        const $preview = $('#svChatPreview');
        const $enviar = $('#svChatEnviar');
        const $adjuntar = $('#svChatAdjuntar');

        $(document).one('click keydown touchstart', function () {
            audioHabilitado = true;
        });

        function reproducirSonido() {

            if (!audioHabilitado) {
                return;
            }

            try {

                const AudioContextClass =
                    window.AudioContext ||
                    window.webkitAudioContext;

                if (!AudioContextClass) {
                    return;
                }

                const contexto =
                    new AudioContextClass();

                const oscilador =
                    contexto.createOscillator();

                const ganancia =
                    contexto.createGain();

                oscilador.type = 'sine';

                oscilador.frequency.setValueAtTime(
                    760,
                    contexto.currentTime
                );

                ganancia.gain.setValueAtTime(
                    0.10,
                    contexto.currentTime
                );

                ganancia.gain.exponentialRampToValueAtTime(
                    0.001,
                    contexto.currentTime + 0.28
                );

                oscilador.connect(ganancia);
                ganancia.connect(contexto.destination);

                oscilador.start();

                oscilador.stop(
                    contexto.currentTime + 0.28
                );

                oscilador.onended = function () {
                    contexto.close();
                };

            } catch (e) {
            }
        }

        function actualizarBadge(cantidad) {

            cantidad = parseInt(cantidad || 0);

            if (cantidad > 0) {

                $badge
                    .text(cantidad > 99 ? '99+' : cantidad)
                    .removeClass('d-none');

            } else {

                $badge
                    .text('0')
                    .addClass('d-none');
            }

            const $navbarBadge =
                $('#comunicacionesBell .badge');

            if ($navbarBadge.length) {

                if (cantidad > 0) {

                    $navbarBadge
                        .text(cantidad > 99 ? '99+' : cantidad)
                        .show();

                } else {

                    $navbarBadge.hide();
                }
            }
        }

        function revisarPendientes() {

            $.ajax({
                url: urls.pendientes,
                method: 'GET',
                cache: false,

                success: function (respuesta) {

                    actualizarBadge(
                        respuesta.count
                    );

                    const nuevoId =
                        respuesta.ultimo_destinatario_id
                            ? String(respuesta.ultimo_destinatario_id)
                            : null;

                    if (primeraConsulta) {

                        ultimaNotificacionId = nuevoId;
                        primeraConsulta = false;

                        return;
                    }

                    if (
                        nuevoId
                        && nuevoId !== ultimaNotificacionId
                    ) {

                        reproducirSonido();

                        if (
                            usuarioActual
                            && respuesta.ultimo_tipo === 'mensaje'
                        ) {
                            cargarConversacion(
                                usuarioActual.id,
                                false
                            );
                        }
                    }

                    ultimaNotificacionId = nuevoId;
                }
            });
        }

        function cargarUsuarios(busqueda) {

            $('#svMessengerCargandoUsuarios')
                .removeClass('d-none');

            $.ajax({
                url: urls.usuarios,
                method: 'GET',

                data: {
                    q: busqueda || ''
                },

                success: function (respuesta) {

                    $usuarios.empty();

                    const listaUsuarios =
                        respuesta.usuarios || [];

                    if (!listaUsuarios.length) {

                        $usuarios.html(
                            '<div class="sv-messenger-empty">' +
                                '<i class="fa-solid fa-user-slash"></i>' +
                                '<div>No se encontraron usuarios.</div>' +
                            '</div>'
                        );

                        return;
                    }

                    listaUsuarios.forEach(function (usuario) {

                        const detalles = [];

                        if (usuario.unidad) {
                            detalles.push(usuario.unidad);
                        }

                        if (usuario.turno) {
                            detalles.push(usuario.turno);
                        }

                        const $item = $('<button>', {
                            type: 'button',
                            class: 'sv-messenger-user'
                        });

                        const $avatar = $('<div>', {
                            class: 'sv-user-avatar'
                        });

                        $('<i>', {
                            class: 'fa-solid fa-user'
                        }).appendTo($avatar);

                        const $datos = $('<div>', {
                            class: 'sv-user-data'
                        });

                        $('<strong>', {
                            text: usuario.nombre
                        }).appendTo($datos);

                        $('<small>', {
                            text: detalles.join(' · ')
                        }).appendTo($datos);

                        $item.append($avatar);
                        $item.append($datos);

                        $item.on('click', function () {
                            abrirConversacion(usuario);
                        });

                        $usuarios.append($item);
                    });
                },

                error: function () {

                    $usuarios.html(
                        '<div class="sv-messenger-empty">' +
                            '<i class="fa-solid fa-triangle-exclamation"></i>' +
                            '<div>No fue posible cargar el personal.</div>' +
                        '</div>'
                    );
                },

                complete: function () {

                    $('#svMessengerCargandoUsuarios')
                        .addClass('d-none');
                }
            });
        }

        function abrirConversacion(usuario) {

            usuarioActual = usuario;

            $('#svChatDestinatario')
                .val(usuario.id);

            $('#svChatNombre')
                .text(usuario.nombre);

            const detalles = [];

            if (usuario.unidad) {
                detalles.push(usuario.unidad);
            }

            if (usuario.turno) {
                detalles.push(usuario.turno);
            }

            $('#svChatDetalle')
                .text(detalles.join(' · '));

            limpiarAdjuntosChat();

            $lista.addClass('d-none');
            $conversacion.removeClass('d-none');

            cargarConversacion(
                usuario.id,
                true
            );
        }

        function formatoHora(fecha) {

            if (!fecha) {
                return '';
            }

            const date = new Date(fecha);

            if (isNaN(date.getTime())) {
                return '';
            }

            return date.toLocaleString(
                'es-MX',
                {
                    day: '2-digit',
                    month: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                }
            );
        }

        function crearAdjuntosMensaje(adjuntos) {

            adjuntos = adjuntos || [];

            if (!adjuntos.length) {
                return null;
            }

            const $contenedor = $('<div>', {
                class:
                    adjuntos.length === 1
                        ? 'sv-chat-adjuntos sv-chat-adjuntos-1'
                        : 'sv-chat-adjuntos sv-chat-adjuntos-varios'
            });

            adjuntos.forEach(function (adjunto) {

                if (
                    adjunto.tipo !== 'imagen'
                    || !adjunto.url
                ) {
                    return;
                }

                const $enlace = $('<a>', {
                    href: adjunto.url,
                    target: '_blank',
                    rel: 'noopener noreferrer',
                    class: 'sv-chat-imagen',
                    title: adjunto.nombre_original || 'Imagen'
                });

                $('<img>', {
                    src: adjunto.url,
                    alt: adjunto.nombre_original || 'Imagen',
                    loading: 'lazy'
                }).appendTo($enlace);

                const $overlay = $('<div>', {
                    class: 'sv-chat-imagen-overlay'
                });

                $('<i>', {
                    class: 'fa-solid fa-magnifying-glass-plus'
                }).appendTo($overlay);

                $enlace.append($overlay);

                $contenedor.append($enlace);
            });

            return $contenedor.children().length
                ? $contenedor
                : null;
        }

        function cargarConversacion(
            usuarioId,
            mostrarCarga
        ) {

            if (mostrarCarga) {

                $mensajes.html(
                    '<div class="sv-messenger-empty">' +
                        '<i class="fa-solid fa-spinner fa-spin"></i>' +
                        '<div>Cargando conversación...</div>' +
                    '</div>'
                );
            }

            $.ajax({
                url: urls.index,
                method: 'GET',

                data: {
                    con_usuario: usuarioId,
                    json: 1
                },

                success: function (respuesta) {

                    if (
                        !usuarioActual
                        || String(usuarioActual.id)
                            !== String(usuarioId)
                    ) {
                        return;
                    }

                    $('#svChatNombre')
                        .text(
                            respuesta.usuario.nombre
                        );

                    const detalleUsuario = [];

                    if (respuesta.usuario.unidad) {
                        detalleUsuario.push(
                            respuesta.usuario.unidad
                        );
                    }

                    if (respuesta.usuario.turno) {
                        detalleUsuario.push(
                            respuesta.usuario.turno
                        );
                    }

                    $('#svChatDetalle')
                        .text(
                            detalleUsuario.join(' · ')
                        );

                    const puedeEnviar =
                        !!respuesta.usuario.puede_enviar;

                    $texto.prop(
                        'disabled',
                        !puedeEnviar
                    );

                    $enviar.prop(
                        'disabled',
                        !puedeEnviar
                    );

                    $adjuntar.prop(
                        'disabled',
                        !puedeEnviar
                    );

                    $mensajes.empty();

                    const listaMensajes =
                        respuesta.mensajes || [];

                    if (!listaMensajes.length) {

                        $('#svChatSinMensajes')
                            .removeClass('d-none');

                    } else {

                        $('#svChatSinMensajes')
                            .addClass('d-none');
                    }

                    listaMensajes.forEach(
                        function (mensaje) {

                            const clase =
                                mensaje.es_mio
                                    ? 'sv-chat-mensaje sv-chat-mensaje-mio'
                                    : 'sv-chat-mensaje sv-chat-mensaje-otro';

                            const $burbuja = $('<div>', {
                                class: clase
                            });

                            const textoMensaje =
                                String(
                                    mensaje.contenido || ''
                                ).trim();

                            if (textoMensaje !== '') {

                                $('<div>', {
                                    class: 'sv-chat-texto',
                                    text: mensaje.contenido
                                }).appendTo($burbuja);
                            }

                            const $adjuntos =
                                crearAdjuntosMensaje(
                                    mensaje.adjuntos
                                );

                            if ($adjuntos) {
                                $burbuja.append(
                                    $adjuntos
                                );
                            }

                            $('<span>', {
                                class: 'sv-chat-hora',
                                text: formatoHora(
                                    mensaje.enviado_at
                                )
                            }).appendTo($burbuja);

                            $mensajes.append(
                                $burbuja
                            );
                        }
                    );

                    if ($mensajes[0]) {

                        $mensajes.scrollTop(
                            $mensajes[0].scrollHeight
                        );
                    }

                    actualizarBadge(
                        respuesta.no_leidas
                    );
                }
            });
        }

        function validarArchivosChat(archivos) {

            if (archivos.length > 10) {

                Swal.fire({
                    icon: 'warning',
                    title: 'Demasiadas imágenes',
                    text: 'Puedes enviar un máximo de 10 imágenes por mensaje.'
                });

                return false;
            }

            const tiposPermitidos = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            for (const archivo of archivos) {

                if (
                    !tiposPermitidos.includes(
                        archivo.type
                    )
                ) {

                    Swal.fire({
                        icon: 'warning',
                        title: 'Formato no permitido',
                        text: 'Solamente puedes enviar imágenes JPG, PNG o WebP.'
                    });

                    return false;
                }

                if (
                    archivo.size >
                    10 * 1024 * 1024
                ) {

                    Swal.fire({
                        icon: 'warning',
                        title: 'Imagen demasiado grande',
                        text: 'Cada imagen puede pesar como máximo 10 MB.'
                    });

                    return false;
                }
            }

            return true;
        }

        function sincronizarInputArchivos() {

            const transfer =
                new DataTransfer();

            archivosChat.forEach(
                function (archivo) {
                    transfer.items.add(archivo);
                }
            );

            $imagenes[0].files =
                transfer.files;
        }

        function mostrarPreviewChat() {

            $preview.empty();

            if (!archivosChat.length) {

                $preview.addClass('d-none');

                return;
            }

            $preview.removeClass('d-none');

            archivosChat.forEach(
                function (archivo, indice) {

                    const $item = $('<div>', {
                        class: 'sv-chat-preview-item'
                    });

                    const lector =
                        new FileReader();

                    lector.onload =
                        function (e) {

                            $('<img>', {
                                src: e.target.result,
                                alt: archivo.name
                            }).prependTo($item);
                        };

                    lector.readAsDataURL(
                        archivo
                    );

                    const $remove =
                        $('<button>', {
                            type: 'button',
                            class: 'sv-chat-preview-remove',
                            title: 'Quitar imagen'
                        });

                    $('<i>', {
                        class: 'fa-solid fa-xmark'
                    }).appendTo($remove);

                    $remove.on(
                        'click',
                        function () {

                            archivosChat.splice(
                                indice,
                                1
                            );

                            sincronizarInputArchivos();
                            mostrarPreviewChat();
                        }
                    );

                    $item.append($remove);
                    $preview.append($item);
                }
            );
        }

        function limpiarAdjuntosChat() {

            archivosChat = [];

            $imagenes.val('');

            $preview
                .empty()
                .addClass('d-none');
        }

        $('#svMessengerButton').on(
            'click',
            function () {

                $panel.toggleClass('d-none');

                if (
                    !$panel.hasClass('d-none')
                ) {

                    if (usuarioActual) {

                        cargarConversacion(
                            usuarioActual.id,
                            true
                        );

                    } else {

                        cargarUsuarios('');

                        setTimeout(
                            function () {
                                $buscar.trigger('focus');
                            },
                            100
                        );
                    }
                }
            }
        );

        $('#svMessengerClose').on(
            'click',
            function () {

                $panel.addClass('d-none');
            }
        );

        $('#svChatBack').on(
            'click',
            function () {

                usuarioActual = null;

                $('#svChatDestinatario')
                    .val('');

                $texto.val('');

                limpiarAdjuntosChat();

                $conversacion
                    .addClass('d-none');

                $lista
                    .removeClass('d-none');

                cargarUsuarios(
                    $buscar.val().trim()
                );
            }
        );

        $buscar.on(
            'input',
            function () {

                clearTimeout(
                    temporizadorBusqueda
                );

                const busqueda =
                    $(this).val().trim();

                temporizadorBusqueda =
                    setTimeout(
                        function () {
                            cargarUsuarios(
                                busqueda
                            );
                        },
                        300
                    );
            }
        );

        $adjuntar.on(
            'click',
            function () {

                if (
                    $(this).prop('disabled')
                ) {
                    return;
                }

                $imagenes.trigger('click');
            }
        );

        $imagenes.on(
            'change',
            function () {

                const nuevosArchivos =
                    Array.from(
                        this.files || []
                    );

                if (!nuevosArchivos.length) {
                    return;
                }

                const combinados = [
                    ...archivosChat,
                    ...nuevosArchivos
                ];

                if (
                    !validarArchivosChat(
                        combinados
                    )
                ) {

                    $imagenes.val('');

                    return;
                }

                archivosChat =
                    combinados.slice(0, 10);

                sincronizarInputArchivos();
                mostrarPreviewChat();
            }
        );

        $('#svChatForm').on(
            'submit',
            function (e) {

                e.preventDefault();

                if (!usuarioActual) {
                    return;
                }

                const texto =
                    $texto.val().trim();

                if (
                    !texto
                    && !archivosChat.length
                ) {
                    return;
                }

                if (
                    !validarArchivosChat(
                        archivosChat
                    )
                ) {
                    return;
                }

                const formData =
                    new FormData();

                formData.append(
                    'tipo',
                    'mensaje'
                );

                formData.append(
                    'alcance',
                    'usuario'
                );

                formData.append(
                    'destinatario_user_id',
                    usuarioActual.id
                );

                formData.append(
                    'contenido',
                    texto
                );

                formData.append(
                    'requiere_enterado',
                    '0'
                );

                archivosChat.forEach(
                    function (archivo) {

                        formData.append(
                            'imagenes[]',
                            archivo,
                            archivo.name
                        );
                    }
                );

                $enviar.prop(
                    'disabled',
                    true
                );

                $adjuntar.prop(
                    'disabled',
                    true
                );

                $.ajax({
                    url: urls.store,
                    method: 'POST',

                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },

                    data: formData,
                    processData: false,
                    contentType: false,

                    success: function () {

                        $texto.val('');

                        limpiarAdjuntosChat();

                        cargarConversacion(
                            usuarioActual.id,
                            false
                        );

                        setTimeout(
                            function () {
                                $texto.trigger(
                                    'focus'
                                );
                            },
                            100
                        );
                    },

                    error: function (xhr) {

                        let mensaje =
                            'No fue posible enviar el mensaje.';

                        if (
                            xhr.responseJSON
                            && xhr.responseJSON.errors
                        ) {

                            const errores =
                                Object.values(
                                    xhr.responseJSON.errors
                                );

                            if (
                                errores.length
                                && errores[0].length
                            ) {
                                mensaje =
                                    errores[0][0];
                            }

                        } else if (
                            xhr.responseJSON
                            && xhr.responseJSON.message
                        ) {

                            mensaje =
                                xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'No se envió',
                            text: mensaje
                        });
                    },

                    complete: function () {

                        $enviar.prop(
                            'disabled',
                            false
                        );

                        $adjuntar.prop(
                            'disabled',
                            false
                        );
                    }
                });
            }
        );

        $texto.on(
            'keydown',
            function (e) {

                if (
                    e.key === 'Enter'
                    && !e.shiftKey
                ) {

                    e.preventDefault();

                    $('#svChatForm')
                        .trigger('submit');
                }
            }
        );

        revisarPendientes();

        setInterval(
            revisarPendientes,
            8000
        );

    });

</script>
@endpush

@endauth
