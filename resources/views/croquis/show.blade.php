@extends('adminlte::page')

@section('title', 'Croquis')

@section('content_header')
    <h1>Croquis del hecho</h1>
@stop

@section('css')
    <link rel="stylesheet" href="{{ asset('css/croquis.css') }}?v={{ filemtime(public_path('css/croquis.css')) }}">
@stop

@section('content')
@php
    $extensionesCroquis = ['png', 'jpg', 'jpeg', 'webp'];
    $iconosPath = public_path('img/croquis/iconos');
    $vehiculosPath = public_path('img/croquis/vehiculos');

    $obtenerImagenesCroquis = function ($path) use ($extensionesCroquis) {
        if (!is_dir($path)) {
            return collect();
        }

        return collect(scandir($path))
            ->filter(function ($archivo) use ($path, $extensionesCroquis) {
                if ($archivo === '.' || $archivo === '..') {
                    return false;
                }

                if (!is_file($path . DIRECTORY_SEPARATOR . $archivo)) {
                    return false;
                }

                $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

                return in_array($extension, $extensionesCroquis, true);
            })
            ->sort()
            ->values();
    };

    $formatearNombreCroquis = function ($nombreBase) {
        return (string) \Illuminate\Support\Str::of($nombreBase)->replace(['-', '_'], ' ')->title();
    };

    $categoriasIconos = [
        'general' => [
            'label' => 'General',
            'path' => $iconosPath,
            'assetPath' => 'img/croquis/iconos',
            'skipKeys' => ['pow', 'semaforo1', 'semaforo2'],
        ],
        'semaforos_senalamientos' => [
            'label' => 'Semáforos y señalamientos',
            'path' => $iconosPath . DIRECTORY_SEPARATOR . 'semaforos_senalamientos',
            'assetPath' => 'img/croquis/iconos/semaforos_senalamientos',
        ],
        'construcciones' => [
            'label' => 'Construcciones',
            'path' => $iconosPath . DIRECTORY_SEPARATOR . 'construcciones',
            'assetPath' => 'img/croquis/iconos/construcciones',
        ],
        'flechas_especiales' => [
            'label' => 'Flechas e iconos especiales',
            'path' => $iconosPath . DIRECTORY_SEPARATOR . 'flechas_especiales',
            'assetPath' => 'img/croquis/iconos/flechas_especiales',
        ],
    ];

    $iconosCategoriasCroquis = collect($categoriasIconos)
        ->map(function ($config, $categoria) use ($obtenerImagenesCroquis, $formatearNombreCroquis) {
            $items = $obtenerImagenesCroquis($config['path'])
                ->filter(function ($archivo) use ($config) {
                    $skipKeys = $config['skipKeys'] ?? [];

                    if (!$skipKeys) {
                        return true;
                    }

                    $nombreBase = pathinfo($archivo, PATHINFO_FILENAME);

                    return !in_array(\Illuminate\Support\Str::slug($nombreBase, '_'), $skipKeys, true);
                })
                ->map(function ($archivo) use ($categoria, $config, $formatearNombreCroquis) {
                    $nombreBase = pathinfo($archivo, PATHINFO_FILENAME);
                    $keyBase = $categoria === 'general'
                        ? $nombreBase
                        : $categoria . '_' . $nombreBase;

                    return [
                        'key' => \Illuminate\Support\Str::slug($keyBase, '_'),
                        'label' => $formatearNombreCroquis($nombreBase),
                        'src' => asset($config['assetPath'] . '/' . $archivo),
                    ];
                })
                ->values()
                ->all();

            return [
                'key' => $categoria,
                'label' => $config['label'],
                'items' => $items,
            ];
        })
        ->values()
        ->all();

    $iconosRaizCroquis = $obtenerImagenesCroquis($iconosPath)
        ->map(function ($archivo) use ($formatearNombreCroquis) {
            $nombreBase = pathinfo($archivo, PATHINFO_FILENAME);

            return [
                'key' => \Illuminate\Support\Str::slug($nombreBase, '_'),
                'label' => $formatearNombreCroquis($nombreBase),
                'src' => asset('img/croquis/iconos/' . $archivo),
            ];
        })
        ->values()
        ->all();

    $iconosCroquis = collect($iconosCategoriasCroquis)
        ->flatMap(function ($categoria) {
            return $categoria['items'];
        })
        ->merge($iconosRaizCroquis)
        ->unique('key')
        ->values()
        ->all();

    $iconoCardinal = collect($iconosCroquis)->firstWhere('key', 'cardinal_points');

    $categoriasVehiculos = [
        'automovil' => 'Automóvil',
        'camion' => 'Camión',
        'camioneta' => 'Camioneta',
        'bicicleta' => 'Bicicleta',
        'motocicleta' => 'Motocicleta',
        'maquinaria' => 'Maquinaria',
    ];

    $vehiculosCroquis = collect($categoriasVehiculos)
        ->map(function ($label, $categoria) use ($vehiculosPath, $obtenerImagenesCroquis, $formatearNombreCroquis) {
            $categoriaPath = $vehiculosPath . DIRECTORY_SEPARATOR . $categoria;
            $items = $obtenerImagenesCroquis($categoriaPath)
                ->map(function ($archivo) use ($categoria, $categoriaPath, $formatearNombreCroquis) {
                    $nombreBase = pathinfo($archivo, PATHINFO_FILENAME);
                    $imagenPath = $categoriaPath . DIRECTORY_SEPARATOR . $archivo;
                    $dimensiones = @getimagesize($imagenPath) ?: [90, 90];

                    return [
                        'nombre' => $formatearNombreCroquis($nombreBase),
                        'subtipo' => \Illuminate\Support\Str::slug($nombreBase, '_'),
                        'src' => asset('img/croquis/vehiculos/' . $categoria . '/' . $archivo),
                        'anchoOriginal' => $dimensiones[0],
                        'altoOriginal' => $dimensiones[1],
                    ];
                })
                ->values()
                ->all();

            return [
                'key' => $categoria,
                'label' => $label,
                'items' => $items,
            ];
        })
        ->values()
        ->all();
@endphp

<div class="card">
    <div class="card-body">
        <div class="croquis-wrap">

            <div class="croquis-toolbar">

                <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        🚗 Vehículos
                    </button>
                    <div class="dropdown-menu">
                        @foreach($vehiculosCroquis as $categoriaVehiculo)
                            <button
                                type="button"
                                class="dropdown-item"
                                data-croquis-action="abrirMenuVehiculo"
                                data-vehicle-category="{{ $categoriaVehiculo['key'] }}">
                                {{ $categoriaVehiculo['label'] }}
                            </button>
                        @endforeach
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

                <div class="btn-group" role="group" aria-label="Carriles">
                    <button type="button" class="btn btn-outline-secondary" data-croquis-action="quitarCarril">- Carril</button>
                    <button type="button" class="btn btn-outline-secondary" data-croquis-action="agregarCarril">+ Carril</button>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        📍 Iconos
                    </button>
                    <div class="dropdown-menu">
                        @foreach($iconosCategoriasCroquis as $categoriaIcono)
                            <button
                                type="button"
                                class="dropdown-item"
                                data-croquis-action="abrirMenuIcono"
                                data-icon-category="{{ $categoriaIcono['key'] }}">
                                {{ $categoriaIcono['label'] }}
                            </button>
                        @endforeach
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
                + Carril / - Carril, Ctrl + scroll o teclas + / -: cambiar carriles.
                Shift + scroll: girar.
                Alt + scroll en curva: cambiar apertura.
                Q / E en curva: cerrar o abrir.
                En vehículos y vialidades, el círculo naranja ajusta largo y ancho; la rueda cambia el tamaño total.
                Supr: borrar.
            </div>

            <form id="formCroquis" method="POST" action="{{ route('croquis.store', $hecho->id) }}">
                @csrf
                <input type="hidden" name="json_dibujo" id="croquisInput">
                <input type="hidden" name="imagen_preview" id="croquisPreviewInput">
            </form>

        </div>
    </div>
</div>
@stop

@section('js')
    <script src="{{ asset('js/croquis/croquis-models.js') }}?v={{ filemtime(public_path('js/croquis/croquis-models.js')) }}"></script>
    <script src="{{ asset('js/croquis/croquis-renderer.js') }}?v={{ filemtime(public_path('js/croquis/croquis-renderer.js')) }}"></script>
    <script src="{{ asset('js/croquis/croquis-editor.js') }}?v={{ filemtime(public_path('js/croquis/croquis-editor.js')) }}"></script>
    <script src="{{ asset('js/croquis/croquis-ui.js') }}?v={{ filemtime(public_path('js/croquis/croquis-ui.js')) }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            CroquisUI.init({
                canvasId: 'croquisCanvas',
                inputId: 'croquisInput',
                previewInputId: 'croquisPreviewInput',
                formId: 'formCroquis',
                submenuContainerId: 'croquisSubmenu',
                iconos: @json($iconosCroquis),
                iconosCategorias: @json($iconosCategoriasCroquis),
                vehiculos: @json($vehiculosCroquis),
                defaultIcono: @json($iconoCardinal),
                initialData: @json(
                    $croquis && $croquis->json_dibujo
                        ? json_decode($croquis->json_dibujo, true)
                        : []
                )
            });
        });
    </script>
@stop
