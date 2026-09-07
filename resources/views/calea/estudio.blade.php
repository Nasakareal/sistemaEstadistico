@extends('adminlte::page')

@section('title', 'Estudio CALEA')

@section('content_header')
    <h1>Modo Estudio CALEA</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Material de Estudio</h3>
                    <div class="card-tools">
                        <a href="{{ route('calea.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Directivas
                        </a>
                        <a href="{{ route('calea.buscar') }}" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-magnifying-glass"></i> Buscar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fa-solid fa-graduation-cap"></i>
                        Selecciona una categoría y despliega las directivas para repasar su contenido vigente.
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="filtroEstudio">Buscar dentro del material</label>
                                <input type="text" id="filtroEstudio" class="form-control" placeholder="Ej. flagrancia, arresto, uso de la fuerza, 1.2.5...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filtroCategoria">Categoría</label>
                                <select id="filtroCategoria" class="form-control">
                                    <option value="">Todas las categorías</option>
                                    @foreach ($categorias as $nombreCategoria => $items)
                                        <option value="{{ $nombreCategoria }}">{{ $nombreCategoria }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="contenedorEstudio">
                        @foreach ($categorias as $nombreCategoria => $items)
                            <div class="categoria-estudio mb-4" data-categoria="{{ $nombreCategoria }}">
                                <div class="card card-outline card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fa-solid fa-folder-open"></i>
                                            {{ $nombreCategoria }}
                                        </h3>
                                        <div class="card-tools">
                                            <span class="badge badge-primary">{{ $items->count() }}</span>
                                        </div>
                                    </div>

                                    <div class="card-body p-2">
                                        <div class="accordion" id="accordionCategoria{{ $loop->index }}">
                                            @foreach ($items as $directiva)
                                                @php($version = $directiva->versionVigente)

                                                <div class="card directiva-estudio mb-2"
                                                     data-texto="{{ strtolower($directiva->codigo . ' ' . $directiva->titulo . ' ' . ($directiva->categoria ?? '')) }}">

                                                    <div class="card-header" id="headingDirectiva{{ $directiva->id }}">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-9">
                                                                <button class="btn btn-link text-left p-0 btn-block"
                                                                        type="button"
                                                                        data-toggle="collapse"
                                                                        data-target="#collapseDirectiva{{ $directiva->id }}"
                                                                        aria-expanded="false"
                                                                        aria-controls="collapseDirectiva{{ $directiva->id }}">
                                                                    <strong>{{ $directiva->codigo }}</strong>
                                                                    — {{ $directiva->titulo }}
                                                                </button>
                                                            </div>

                                                            <div class="col-md-3 text-right">
                                                                @if ($version)
                                                                    <span class="badge badge-success">
                                                                        {{ $version->nombre_version ?: 'Versión ' . $version->numero_version }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div id="collapseDirectiva{{ $directiva->id }}"
                                                         class="collapse"
                                                         aria-labelledby="headingDirectiva{{ $directiva->id }}"
                                                         data-parent="#accordionCategoria{{ $loop->parent->index }}">

                                                        <div class="card-body">
                                                            @if ($version)
                                                                <div class="row mb-3">
                                                                    <div class="col-md-3">
                                                                        <strong>Estándar</strong><br>
                                                                        {{ $directiva->codigo }}
                                                                    </div>

                                                                    <div class="col-md-3">
                                                                        <strong>Versión</strong><br>
                                                                        {{ $version->nombre_version ?: 'Versión ' . $version->numero_version }}
                                                                    </div>

                                                                    <div class="col-md-3">
                                                                        <strong>Revisión</strong><br>
                                                                        {{ $version->fecha_revision ? $version->fecha_revision->format('d/m/Y') : '—' }}
                                                                    </div>

                                                                    <div class="col-md-3">
                                                                        <strong>Área responsable</strong><br>
                                                                        {{ $version->area_responsable ?? '—' }}
                                                                    </div>
                                                                </div>

                                                                <hr>

                                                                @if ($version->secciones->count())
                                                                    @foreach ($version->secciones as $seccion)
                                                                        <div class="seccion-estudio mb-4">
                                                                            <h5>
                                                                                @if ($seccion->numero)
                                                                                    <strong>{{ $seccion->numero }}.</strong>
                                                                                @endif
                                                                                <strong>{{ $seccion->titulo }}</strong>
                                                                            </h5>

                                                                            @if ($seccion->contenido)
                                                                                <div class="contenido-estudio">
                                                                                    {!! nl2br(e($seccion->contenido)) !!}
                                                                                </div>
                                                                            @endif

                                                                            @if ($seccion->pagina_inicio)
                                                                                <small class="text-muted">
                                                                                    <i class="fa-regular fa-file"></i>
                                                                                    Página
                                                                                    {{ $seccion->pagina_inicio }}
                                                                                    @if ($seccion->pagina_fin && $seccion->pagina_fin != $seccion->pagina_inicio)
                                                                                        a {{ $seccion->pagina_fin }}
                                                                                    @endif
                                                                                </small>
                                                                            @endif
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <div class="alert alert-warning">
                                                                        Esta versión todavía no tiene contenido estructurado para estudio.
                                                                    </div>
                                                                @endif

                                                                <div class="mt-3">
                                                                    <a href="{{ route('calea.show', $directiva->id) }}" class="btn btn-info btn-sm">
                                                                        <i class="fa-regular fa-eye"></i> Ver directiva completa
                                                                    </a>

                                                                    @if ($version->archivo_path)
                                                                        <a href="{{ route('calea.versiones.pdf', $version->id) }}" target="_blank" class="btn btn-danger btn-sm">
                                                                            <i class="fa-regular fa-file-pdf"></i> Ver PDF original
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            @else
                                                                <div class="alert alert-warning mb-0">
                                                                    No existe una versión vigente disponible.
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div id="sinResultados" class="alert alert-warning text-center d-none">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        No se encontraron directivas con esos criterios.
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .directiva-estudio .card-header {
            padding: .75rem 1rem;
        }

        .directiva-estudio .btn-link {
            color: inherit;
            font-size: 1rem;
            text-decoration: none;
        }

        .directiva-estudio .btn-link:hover {
            text-decoration: none;
        }

        .contenido-estudio {
            white-space: normal;
            line-height: 1.6;
            margin-bottom: .5rem;
        }

        .seccion-estudio {
            border-left: 4px solid #007bff;
            padding-left: 15px;
        }

        .badge {
            font-size: .85rem;
            padding: .4rem .55rem;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            function filtrarEstudio() {
                const texto = $('#filtroEstudio').val().toLowerCase().trim();
                const categoria = $('#filtroCategoria').val();
                let totalVisibles = 0;

                $('.categoria-estudio').each(function () {
                    const categoriaActual = $(this).data('categoria');
                    let visiblesCategoria = 0;

                    $(this).find('.directiva-estudio').each(function () {
                        const contenido = $(this).text().toLowerCase();
                        const textoBase = ($(this).data('texto') || '').toString().toLowerCase();

                        const coincideTexto = texto === '' || contenido.includes(texto) || textoBase.includes(texto);
                        const coincideCategoria = categoria === '' || categoriaActual === categoria;

                        if (coincideTexto && coincideCategoria) {
                            $(this).show();
                            visiblesCategoria++;
                            totalVisibles++;
                        } else {
                            $(this).hide();
                        }
                    });

                    if (visiblesCategoria > 0) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });

                if (totalVisibles === 0) {
                    $('#sinResultados').removeClass('d-none');
                } else {
                    $('#sinResultados').addClass('d-none');
                }
            }

            $('#filtroEstudio').on('keyup', filtrarEstudio);
            $('#filtroCategoria').on('change', filtrarEstudio);
        });
    </script>
@stop
