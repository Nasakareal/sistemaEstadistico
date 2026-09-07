@extends('adminlte::page')

@section('title', 'Buscar en CALEA')

@section('content_header')
    <h1>Buscar en Directivas CALEA</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Buscador CALEA</h3>
                    <div class="card-tools">
                        <a href="{{ route('calea.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Directivas
                        </a>
                        <a href="{{ route('calea.estudio') }}" class="btn btn-success btn-sm">
                            <i class="fa-solid fa-graduation-cap"></i> Modo estudio
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('calea.buscar') }}" method="GET">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="form-group">
                                    <label for="q">Texto a buscar</label>
                                    <input type="text" name="q" id="q" class="form-control" value="{{ $texto }}" placeholder="Ej. flagrancia, uso de la fuerza, antecedentes penales, C5-SITEC, 1.2.5...">
                                    <small class="form-text text-muted">Busca en estándar, título, descripción, datos de versión, secciones y bloques.</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="categoria">Categoría</label>
                                    <select name="categoria" id="categoria" class="form-control">
                                        <option value="">Todas las categorías</option>
                                        @foreach ($categorias as $item)
                                            <option value="{{ $item }}" {{ $categoria === $item ? 'selected' : '' }}>{{ $item }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-group w-100">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fa-solid fa-magnifying-glass"></i> Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    @if ($texto !== '' || $categoria !== '')
                        <div class="alert alert-info mb-0">
                            <strong>{{ $directivas->count() }}</strong> {{ $directivas->count() === 1 ? 'directiva encontrada' : 'directivas encontradas' }}
                            @if ($texto !== '')
                                para <strong>“{{ $texto }}”</strong>
                            @endif
                            @if ($categoria !== '')
                                en <strong>{{ $categoria }}</strong>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Resultados</h3>
                </div>
                <div class="card-body">
                    @if ($directivas->count())
                        <table id="resultados" class="table table-striped table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Estándar</th>
                                    <th>Título</th>
                                    <th>Categoría</th>
                                    <th>Versión</th>
                                    <th>Revisión</th>
                                    <th>Área responsable</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($directivas as $directiva)
                                    @php($version = $directiva->versionVigente)
                                    <tr>
                                        <td><strong>{{ $directiva->codigo }}</strong></td>
                                        <td class="text-left">{{ $directiva->titulo }}</td>
                                        <td>{{ $directiva->categoria ?? '—' }}</td>
                                        <td>
                                            @if ($version)
                                                <span class="badge badge-primary">{{ $version->nombre_version ?: 'Versión ' . $version->numero_version }}</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $version && $version->fecha_revision ? $version->fecha_revision->format('d/m/Y') : '—' }}</td>
                                        <td class="text-left">{{ $version->area_responsable ?? '—' }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('calea.show', $directiva->id) }}" class="btn btn-info btn-sm" title="Consultar directiva">
                                                    <i class="fa-regular fa-eye"></i>
                                                </a>
                                                @if ($version && $version->archivo_path)
                                                    <a href="{{ route('calea.versiones.pdf', $version->id) }}" target="_blank" class="btn btn-danger btn-sm" title="Ver PDF original">
                                                        <i class="fa-regular fa-file-pdf"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @elseif ($texto !== '' || $categoria !== '')
                        <div class="alert alert-warning mb-0">
                            <i class="fa-solid fa-triangle-exclamation"></i> No se encontraron directivas con esos criterios.
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fa-solid fa-magnifying-glass fa-3x text-muted mb-3"></i>
                            <h5>Escribe una palabra o selecciona una categoría</h5>
                            <p class="text-muted mb-0">Puedes buscar procedimientos, fundamentos, conceptos, nombres de estándares o palabras contenidas dentro de las directivas.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        #resultados th, #resultados td {
            text-align: center;
            vertical-align: middle;
        }

        #resultados td.text-left {
            text-align: left !important;
        }

        #resultados .badge {
            font-size: .85rem;
            padding: .4rem .55rem;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            @if ($directivas->count())
                $('#resultados').DataTable({
                    "pageLength": 25,
                    "order": [],
                    "language": {
                        "emptyTable": "No hay resultados",
                        "info": "Mostrando _START_ a _END_ de _TOTAL_ resultados",
                        "infoEmpty": "Mostrando 0 a 0 de 0 resultados",
                        "infoFiltered": "(Filtrado de _MAX_ resultados)",
                        "lengthMenu": "Mostrar _MENU_ resultados",
                        "loadingRecords": "Cargando...",
                        "processing": "Procesando...",
                        "search": "Filtrar resultados:",
                        "zeroRecords": "No se encontraron resultados",
                        "paginate": {
                            "first": "Primero",
                            "last": "Último",
                            "next": "Siguiente",
                            "previous": "Anterior"
                        }
                    },
                    "responsive": true,
                    "lengthChange": true,
                    "autoWidth": false
                });
            @endif
        });
    </script>
@stop
