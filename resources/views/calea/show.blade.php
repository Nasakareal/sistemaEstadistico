@extends('adminlte::page')

@section('title', $directiva->codigo . ' - ' . $directiva->titulo)

@section('content_header')
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1>
                <strong>{{ $directiva->codigo }}</strong>
                {{ $directiva->titulo }}
            </h1>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('calea.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Directivas
            </a>
            <a href="{{ route('calea.buscar') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-magnifying-glass"></i> Buscar
            </a>
            <a href="{{ route('calea.estudio') }}" class="btn btn-success btn-sm">
                <i class="fa-solid fa-graduation-cap"></i> Estudio
            </a>
        </div>
    </div>
@stop

@section('content')
    @php
        $version = $directiva->versionVigente;

        $meses = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre'
        ];

        $aplanarBloques = function ($bloques, $nivel = 0) use (&$aplanarBloques) {
            $resultado = [];

            foreach ($bloques as $bloque) {
                $resultado[] = [
                    'bloque' => $bloque,
                    'nivel' => $nivel
                ];

                if ($bloque->hijosRecursivos && $bloque->hijosRecursivos->count()) {
                    $resultado = array_merge(
                        $resultado,
                        $aplanarBloques($bloque->hijosRecursivos, $nivel + 1)
                    );
                }
            }

            return $resultado;
        };
    @endphp

    @if (!$version)
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Esta directiva no tiene una versión vigente disponible.
        </div>
    @else
        <div class="row">
            <div class="col-lg-9">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <strong>Estándar {{ $directiva->codigo }}</strong>
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-success">
                                {{ $version->nombre_version ?: 'Versión ' . $version->numero_version }}
                            </span>

                            @if ($version->archivo_path)
                                <a href="{{ route('calea.versiones.pdf', $version->id) }}"
                                   target="_blank"
                                   class="btn btn-danger btn-sm ml-2">
                                    <i class="fa-regular fa-file-pdf"></i> PDF original
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="card-body">
                        <h2 class="titulo-directiva">{{ $directiva->titulo }}</h2>

                        @if ($directiva->categoria)
                            <p>
                                <span class="badge badge-info">
                                    {{ $directiva->categoria }}
                                </span>
                            </p>
                        @endif

                        @if ($directiva->descripcion)
                            <p class="descripcion-directiva">
                                {{ $directiva->descripcion }}
                            </p>
                        @endif
                    </div>
                </div>

                @foreach ($version->secciones as $seccion)
                    <div class="card card-outline card-secondary seccion-calea"
                         id="seccion-{{ $seccion->id }}">

                        <div class="card-header">
                            <h3 class="card-title titulo-seccion">
                                @if ($seccion->numero)
                                    <strong>{{ $seccion->numero }}.</strong>
                                @endif

                                <strong>{{ $seccion->titulo }}</strong>
                            </h3>

                            @if ($seccion->pagina_inicio)
                                <div class="card-tools">
                                    <span class="badge badge-light">
                                        <i class="fa-regular fa-file"></i>
                                        Pág.
                                        {{ $seccion->pagina_inicio }}

                                        @if ($seccion->pagina_fin && $seccion->pagina_fin != $seccion->pagina_inicio)
                                            - {{ $seccion->pagina_fin }}
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div class="card-body">
                            @if ($seccion->contenido)
                                <div class="contenido-seccion mb-4">
                                    {!! nl2br(e($seccion->contenido)) !!}
                                </div>
                            @endif

                            @php
                                $bloques = $aplanarBloques($seccion->bloquesRaiz);
                            @endphp

                            @foreach ($bloques as $item)
                                @php
                                    $bloque = $item['bloque'];
                                    $nivel = $item['nivel'];
                                @endphp

                                <div class="bloque-calea nivel-{{ min($nivel, 5) }}"
                                     id="bloque-{{ $bloque->id }}"
                                     style="margin-left: {{ min($nivel, 5) * 24 }}px;">

                                    @if ($bloque->tipo === 'subtitulo' || $bloque->tipo === 'procedimiento')
                                        <h5 class="bloque-subtitulo">
                                            @if ($bloque->numero)
                                                {{ $bloque->numero }}
                                            @endif

                                            {{ $bloque->titulo }}
                                        </h5>

                                        @if ($bloque->contenido)
                                            <div class="bloque-contenido">
                                                {!! nl2br(e($bloque->contenido)) !!}
                                            </div>
                                        @endif
                                    @elseif ($bloque->tipo === 'numeral')
                                        <div class="d-flex">
                                            @if ($bloque->numero)
                                                <div class="numero-bloque">
                                                    <strong>{{ $bloque->numero }}.</strong>
                                                </div>
                                            @endif

                                            <div class="flex-grow-1">
                                                @if ($bloque->titulo)
                                                    <strong>{{ $bloque->titulo }}</strong>
                                                @endif

                                                <div class="bloque-contenido">
                                                    {!! nl2br(e($bloque->contenido)) !!}
                                                </div>
                                            </div>
                                        </div>
                                    @elseif ($bloque->tipo === 'inciso')
                                        <div class="d-flex">
                                            @if ($bloque->numero)
                                                <div class="numero-bloque">
                                                    <strong>{{ $bloque->numero }})</strong>
                                                </div>
                                            @endif

                                            <div class="flex-grow-1">
                                                @if ($bloque->titulo)
                                                    <strong>{{ $bloque->titulo }}</strong>
                                                @endif

                                                <div class="bloque-contenido">
                                                    {!! nl2br(e($bloque->contenido)) !!}
                                                </div>
                                            </div>
                                        </div>
                                    @elseif ($bloque->tipo === 'vineta')
                                        <div class="d-flex">
                                            <div class="numero-bloque">
                                                <i class="fa-solid fa-caret-right"></i>
                                            </div>

                                            <div class="flex-grow-1">
                                                @if ($bloque->titulo)
                                                    <strong>{{ $bloque->titulo }}</strong>
                                                @endif

                                                <div class="bloque-contenido">
                                                    {!! nl2br(e($bloque->contenido)) !!}
                                                </div>
                                            </div>
                                        </div>
                                    @elseif ($bloque->tipo === 'fundamento')
                                        <div class="alert alert-light fundamento-bloque">
                                            @if ($bloque->titulo)
                                                <strong>
                                                    <i class="fa-solid fa-scale-balanced"></i>
                                                    {{ $bloque->titulo }}
                                                </strong>
                                                <br>
                                            @endif

                                            {!! nl2br(e($bloque->contenido)) !!}
                                        </div>
                                    @elseif ($bloque->tipo === 'advertencia')
                                        <div class="alert alert-warning">
                                            @if ($bloque->titulo)
                                                <strong>
                                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                                    {{ $bloque->titulo }}
                                                </strong>
                                                <br>
                                            @endif

                                            {!! nl2br(e($bloque->contenido)) !!}
                                        </div>
                                    @elseif ($bloque->tipo === 'nota')
                                        <div class="alert alert-info">
                                            @if ($bloque->titulo)
                                                <strong>{{ $bloque->titulo }}</strong>
                                                <br>
                                            @endif

                                            {!! nl2br(e($bloque->contenido)) !!}
                                        </div>
                                    @else
                                        @if ($bloque->titulo)
                                            <h6>
                                                @if ($bloque->numero)
                                                    <strong>{{ $bloque->numero }}</strong>
                                                @endif

                                                <strong>{{ $bloque->titulo }}</strong>
                                            </h6>
                                        @endif

                                        @if ($bloque->contenido)
                                            <div class="bloque-contenido">
                                                {!! nl2br(e($bloque->contenido)) !!}
                                            </div>
                                        @endif
                                    @endif

                                    <div class="bloque-meta">
                                        @if ($bloque->pagina_inicio)
                                            <span>
                                                <i class="fa-regular fa-file"></i>
                                                Pág. {{ $bloque->pagina_inicio }}

                                                @if ($bloque->pagina_fin && $bloque->pagina_fin != $bloque->pagina_inicio)
                                                    - {{ $bloque->pagina_fin }}
                                                @endif
                                            </span>
                                        @endif

                                        @if ($bloque->citable)
                                            <span class="ml-2">
                                                <i class="fa-solid fa-quote-left"></i>
                                                Citable
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if ($version->secciones->isEmpty())
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Esta versión todavía no tiene contenido estructurado disponible.
                    </div>
                @endif
            </div>

            <div class="col-lg-3">
                <div class="card card-outline card-info sticky-calea">
                    <div class="card-header">
                        <h3 class="card-title">Documento</h3>
                    </div>

                    <div class="card-body">
                        <dl class="mb-0">
                            <dt>Estándar</dt>
                            <dd>{{ $directiva->codigo }}</dd>

                            <dt>Versión</dt>
                            <dd>
                                {{ $version->nombre_version ?: 'Versión ' . $version->numero_version }}
                            </dd>

                            <dt>Fecha de emisión</dt>
                            <dd>
                                @if ($version->fecha_emision)
                                    {{ $version->fecha_emision->format('d/m/Y') }}
                                @elseif ($version->mes_emision && $version->anio_emision)
                                    {{ $meses[$version->mes_emision] ?? $version->mes_emision }}
                                    {{ $version->anio_emision }}
                                @elseif ($version->anio_emision)
                                    {{ $version->anio_emision }}
                                @else
                                    —
                                @endif
                            </dd>

                            <dt>Última revisión</dt>
                            <dd>
                                {{ $version->fecha_revision ? $version->fecha_revision->format('d/m/Y') : '—' }}
                            </dd>

                            <dt>Área responsable</dt>
                            <dd>{{ $version->area_responsable ?? '—' }}</dd>

                            @if ($version->autoriza)
                                <dt>Autoriza</dt>
                                <dd>{!! nl2br(e($version->autoriza)) !!}</dd>
                            @endif

                            @if ($version->realizado_por)
                                <dt>Realizado por</dt>
                                <dd>{!! nl2br(e($version->realizado_por)) !!}</dd>
                            @endif
                        </dl>

                        @if ($version->archivo_path)
                            <a href="{{ route('calea.versiones.pdf', $version->id) }}"
                               target="_blank"
                               class="btn btn-danger btn-block mt-3">
                                <i class="fa-regular fa-file-pdf"></i>
                                Abrir PDF original
                            </a>
                        @endif
                    </div>
                </div>

                @if ($version->secciones->count())
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Contenido</h3>
                        </div>

                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @foreach ($version->secciones as $seccion)
                                    <a href="#seccion-{{ $seccion->id }}"
                                       class="list-group-item list-group-item-action enlace-seccion">
                                        @if ($seccion->numero)
                                            <strong>{{ $seccion->numero }}.</strong>
                                        @endif

                                        {{ $seccion->titulo }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if ($version->leyenda_documento)
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-secondary text-center small">
                        {!! nl2br(e($version->leyenda_documento)) !!}
                    </div>
                </div>
            </div>
        @endif
    @endif
@stop

@section('css')
    <style>
        html {
            scroll-behavior: smooth;
        }

        .titulo-directiva {
            font-size: 1.65rem;
            font-weight: 600;
        }

        .descripcion-directiva {
            font-size: 1rem;
            line-height: 1.65;
        }

        .titulo-seccion {
            font-size: 1.05rem;
        }

        .contenido-seccion {
            font-size: 1rem;
            line-height: 1.7;
            text-align: justify;
        }

        .bloque-calea {
            position: relative;
            margin-top: 14px;
            margin-bottom: 14px;
            padding-bottom: 10px;
        }

        .bloque-subtitulo {
            font-weight: 700;
            margin-top: 20px;
            margin-bottom: 8px;
        }

        .bloque-contenido {
            line-height: 1.7;
            text-align: justify;
        }

        .numero-bloque {
            min-width: 35px;
            padding-right: 8px;
        }

        .bloque-meta {
            margin-top: 5px;
            font-size: .75rem;
            color: #6c757d;
        }

        .fundamento-bloque {
            border-left: 4px solid #6c757d;
        }

        .sticky-calea {
            position: sticky;
            top: 15px;
        }

        .enlace-seccion {
            font-size: .9rem;
        }

        .badge {
            font-size: .85rem;
            padding: .4rem .55rem;
        }

        @media (max-width: 991px) {
            .sticky-calea {
                position: static;
            }

            .bloque-calea {
                margin-left: 0 !important;
            }
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            $('.enlace-seccion').on('click', function () {
                $('.enlace-seccion').removeClass('active');
                $(this).addClass('active');
            });
        });
    </script>
@stop
