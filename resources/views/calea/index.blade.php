@extends('adminlte::page')

@section('title', 'Directivas CALEA')

@section('content_header')
    <h1>Directivas CALEA</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Consulta de Directivas CALEA</h3>
                    <div class="card-tools">
                        <a href="{{ route('calea.buscar') }}" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-magnifying-glass"></i> Búsqueda avanzada
                        </a>
                        <a href="{{ route('calea.estudio') }}" class="btn btn-success btn-sm">
                            <i class="fa-solid fa-graduation-cap"></i> Modo estudio
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('calea.buscar') }}" method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="form-group mb-md-0">
                                    <label for="q">Buscar</label>
                                    <input type="text" name="q" id="q" class="form-control" placeholder="Estándar, tema, procedimiento, fundamento, palabra clave...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-md-0">
                                    <label for="categoria">Categoría</label>
                                    <select name="categoria" id="categoria" class="form-control">
                                        <option value="">Todas</option>
                                        @foreach ($categorias as $categoria)
                                            <option value="{{ $categoria }}">{{ $categoria }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-group w-100 mb-0">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fa-solid fa-magnifying-glass"></i> Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <table id="directivas" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Estándar</th>
                                <th>Título</th>
                                <th>Categoría</th>
                                <th>Versión</th>
                                <th>Revisión</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($directivas as $directiva)
                                @php($version = $directiva->versionVigente)
                                <tr>
                                    <td>
                                        <strong>{{ $directiva->codigo }}</strong>
                                    </td>
                                    <td class="text-left">
                                        {{ $directiva->titulo }}
                                    </td>
                                    <td>
                                        {{ $directiva->categoria ?? '—' }}
                                    </td>
                                    <td>
                                        @if ($version)
                                            <span class="badge badge-primary">
                                                {{ $version->nombre_version ?: 'Versión ' . $version->numero_version }}
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($version && $version->fecha_revision)
                                            {{ $version->fecha_revision->format('d/m/Y') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('calea.show', $directiva->id) }}" class="btn btn-info btn-sm" title="Consultar">
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

                    @if ($directivas->isEmpty())
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="fa-solid fa-triangle-exclamation"></i> No hay directivas CALEA disponibles para consulta.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        #directivas th, #directivas td {
            text-align: center;
            vertical-align: middle;
        }

        #directivas td.text-left {
            text-align: left !important;
        }

        #directivas .badge {
            font-size: .85rem;
            padding: .4rem .55rem;
        }

        #directivas tbody tr {
            cursor: default;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            $('#directivas').DataTable({
                "pageLength": 25,
                "order": [],
                "language": {
                    "emptyTable": "No hay directivas CALEA disponibles",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ directivas",
                    "infoEmpty": "Mostrando 0 a 0 de 0 directivas",
                    "infoFiltered": "(Filtrado de _MAX_ directivas)",
                    "lengthMenu": "Mostrar _MENU_ directivas",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Filtrar:",
                    "zeroRecords": "No se encontraron directivas",
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
        });
    </script>
@stop
