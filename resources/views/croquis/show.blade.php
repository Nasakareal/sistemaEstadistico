@extends('adminlte::page')

@section('title', 'Croquis')

@section('content_header')
    <h1>Croquis del hecho</h1>
@stop

@section('css')
    <link rel="stylesheet" href="{{ asset('css/croquis.css') }}">
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <div class="croquis-wrap">

            <div class="croquis-toolbar">

                <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        🚗 Vehículos
                    </button>
                    <div class="dropdown-menu">
                        <button type="button" class="dropdown-item" data-croquis-action="abrirMenuAutomovil">Automóvil</button>
                        <button type="button" class="dropdown-item" data-croquis-action="abrirMenuCamion">Camión</button>
                        <button type="button" class="dropdown-item" data-croquis-action="abrirMenuCamioneta">Camioneta</button>
                        <button type="button" class="dropdown-item" data-croquis-action="abrirMenuBicicleta">Bicicleta</button>
                        <button type="button" class="dropdown-item" data-croquis-action="abrirMenuMotocicleta">Motocicleta</button>
                        <button type="button" class="dropdown-item" data-croquis-action="abrirMenuMaquinaria">Maquinaria</button>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        🛣️ Vialidades
                    </button>
                    <div class="dropdown-menu">
                        <button type="button" class="dropdown-item" data-croquis-action="agregarCalle">Calle recta</button>
                        <button type="button" class="dropdown-item" data-croquis-action="agregarCurva">Curva</button>
                        <button type="button" class="dropdown-item" data-croquis-action="agregarCruce">Cruce</button>
                        <button type="button" class="dropdown-item" data-croquis-action="agregarEntronque">Entronque en T</button>
                        <button type="button" class="dropdown-item" data-croquis-action="agregarGlorieta">Glorieta</button>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        📍 Iconos
                    </button>
                    <div class="dropdown-menu">
                        <button type="button" class="dropdown-item" data-croquis-action="agregarFlecha">Flecha de circulación</button>
                        <button type="button" class="dropdown-item" data-croquis-action="agregarSemaforo">Semáforo</button>
                        <button type="button" class="dropdown-item" data-croquis-action="agregarAlto">Señal de alto</button>
                        <button type="button" class="dropdown-item" data-croquis-action="agregarImpacto">Punto de impacto</button>
                        <button type="button" class="dropdown-item" data-croquis-action="agregarCono">Cono</button>
                        <button type="button" class="dropdown-item" data-croquis-action="agregarPersona">Persona</button>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-dark dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        📝 Texto
                    </button>
                    <div class="dropdown-menu">
                        <button type="button" class="dropdown-item" data-croquis-action="agregarTexto">Agregar texto</button>
                        <button type="button" class="dropdown-item" data-croquis-action="agregarEtiquetaCalle">Nombre de calle</button>
                        <button type="button" class="dropdown-item" data-croquis-action="agregarEtiquetaReferencia">Referencia</button>
                    </div>
                </div>

                <button type="button" class="btn btn-danger" data-croquis-action="limpiar">Limpiar</button>
                <button type="button" class="btn btn-success" data-croquis-action="guardar">Guardar</button>
            </div>

            <div id="croquisSubmenu" class="croquis-submenu" style="display:none;"></div>

            <div class="croquis-canvas-wrap">
                <canvas id="croquisCanvas" width="1200" height="700"></canvas>
            </div>

            <div class="croquis-help">
                Arrastra la pieza para moverla.
                Círculo rojo: girar.
                Círculo naranja: cambiar tamaño.
                Círculo morado en la curva: abrir/cerrar la curva.
                Ctrl + scroll: cambiar carriles.
                Shift + scroll: girar.
                Alt + scroll en curva: cambiar apertura.
                Q / E en curva: cerrar o abrir.
                Supr: borrar.
            </div>

            <form id="formCroquis" method="POST" action="{{ route('croquis.store', $hecho->id) }}">
                @csrf
                <input type="hidden" name="json_dibujo" id="croquisInput">
            </form>

        </div>
    </div>
</div>
@stop

@section('js')
    <script src="{{ asset('js/croquis/croquis-models.js') }}"></script>
    <script src="{{ asset('js/croquis/croquis-renderer.js') }}"></script>
    <script src="{{ asset('js/croquis/croquis-editor.js') }}"></script>
    <script src="{{ asset('js/croquis/croquis-ui.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            CroquisUI.init({
                canvasId: 'croquisCanvas',
                inputId: 'croquisInput',
                formId: 'formCroquis',
                submenuContainerId: 'croquisSubmenu',

                flechaImgSrc: '{{ asset('img/croquis/iconos/flecha.png') }}',
                semaforoImgSrc: '{{ asset('img/croquis/iconos/semaforo.png') }}',
                altoImgSrc: '{{ asset('img/croquis/iconos/alto.png') }}',
                impactoImgSrc: '{{ asset('img/croquis/iconos/impacto.png') }}',
                conoImgSrc: '{{ asset('img/croquis/iconos/cono.png') }}',
                personaImgSrc: '{{ asset('img/croquis/iconos/persona.png') }}',

                initialData: @json(
                    $croquis && $croquis->json_dibujo
                        ? json_decode($croquis->json_dibujo, true)
                        : []
                )
            });
        });
    </script>
@stop
