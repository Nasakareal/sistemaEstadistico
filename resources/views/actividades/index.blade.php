{{-- resources/views/actividades/index.blade.php --}}

@extends('adminlte::page')

@section('title', 'Listado de Actividades')

@section('content_header')
    <h1>Listado de Actividades</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        Actividades
                        <span class="text-muted" style="font-weight: normal;">
                            ({{ \Carbon\Carbon::parse($fechaSeleccionada, 'America/Mexico_City')->format('d/m/Y') }})
                        </span>
                    </h3>

                    <div class="card-tools">
                        <a href="{{ route('actividades.informe.diario', request()->only(['fecha','actividad_categoria_id','actividad_subcategoria_id','q'])) }}" class="btn btn-danger">
                            <i class="fa-solid fa-file-pdf"></i> Generar informe
                        </a>

                        @can('crear actividades')
                            <a href="{{ route('actividades.create') }}" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i> Añadir nueva actividad
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="card-body">

                    {{-- FILTROS (GET) --}}
                    <form method="GET" action="{{ route('actividades.index') }}" id="filtrosForm">
                        <div class="row mb-3">
                            {{-- Fecha --}}
                            <div class="col-md-3">
                                <label for="fecha_filtro">Día:</label>
                                <input
                                    type="date"
                                    id="fecha_filtro"
                                    name="fecha"
                                    class="form-control"
                                    value="{{ $fechaSeleccionada }}"
                                >
                            </div>

                            {{-- Categoría (server-side) --}}
                            <div class="col-md-3">
                                <label for="categoria_filtro">Filtrar por categoría:</label>
                                <select id="categoria_filtro" name="actividad_categoria_id" class="form-control">
                                    <option value="">Todas</option>
                                    @foreach ($categorias as $c)
                                        <option value="{{ $c->id }}" {{ (string)request('actividad_categoria_id') === (string)$c->id ? 'selected' : '' }}>
                                            {{ $c->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Subcategoría (opcional server-side) --}}
                            <div class="col-md-3">
                                <label for="subcategoria_filtro">Filtrar por subcategoría:</label>
                                <select id="subcategoria_filtro" name="actividad_subcategoria_id" class="form-control">
                                    <option value="">Todas</option>
                                    @php
                                        $subcats = collect();
                                        foreach ($actividades as $a) {
                                            if ($a->subcategoria) { $subcats->push($a->subcategoria); }
                                        }
                                        $subcats = $subcats->unique('id')->sortBy('nombre');
                                    @endphp

                                    @foreach ($subcats as $sc)
                                        <option value="{{ $sc->id }}" {{ (string)request('actividad_subcategoria_id') === (string)$sc->id ? 'selected' : '' }}>
                                            {{ $sc->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">
                                    Si no aparece una subcategoría aquí, no hay registros con esa subcategoría en el día seleccionado.
                                </small>
                            </div>

                            {{-- Buscar (server-side) --}}
                            <div class="col-md-3">
                                <label for="q_filtro">Buscar por nombre:</label>
                                <input
                                    type="text"
                                    id="q_filtro"
                                    name="q"
                                    class="form-control"
                                    placeholder="Escriba para buscar..."
                                    value="{{ request('q') }}"
                                >
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12 d-flex" style="gap:8px; flex-wrap: wrap;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-filter"></i> Aplicar filtros
                                </button>

                                <a href="{{ route('actividades.index', ['fecha' => now('America/Mexico_City')->toDateString()]) }}" class="btn btn-outline-secondary">
                                    <i class="fa-solid fa-calendar-day"></i> Hoy
                                </a>

                                <a href="{{ route('actividades.index') }}" class="btn btn-outline-danger">
                                    <i class="fa-solid fa-broom"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <table id="actividades" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>ID</center></th>
                                <th><center>Nombre</center></th>
                                <th><center>Categoría</center></th>
                                <th><center>Subcategoría</center></th>
                                <th><center>Cantidad</center></th>
                                <th><center>Foto</center></th>
                                <th><center>Creado</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($actividades as $a)
                                <tr>
                                    <td>{{ $a->id }}</td>
                                    <td>{{ $a->nombre }}</td>
                                    <td>
                                        {{ $a->categoria ? $a->categoria->nombre : 'Sin categoría' }}
                                    </td>
                                    <td>
                                        {{ $a->subcategoria ? $a->subcategoria->nombre : 'Sin subcategoría' }}
                                    </td>
                                    <td>{{ $a->cantidad }}</td>

                                    <td>
                                        @php
                                            $foto = $a->foto_path;
                                            $urlFoto = $foto ? asset('storage/' . ltrim($foto, '/')) : null;
                                        @endphp

                                        @if ($urlFoto)
                                            <a href="{{ $urlFoto }}" target="_blank" rel="noopener">
                                                <img src="{{ $urlFoto }}" alt="foto_actividad" class="foto-thumb">
                                            </a>
                                        @else
                                            <span class="text-muted">Sin foto</span>
                                        @endif
                                    </td>

                                    <td>{{ optional($a->created_at)->timezone('America/Mexico_City')->format('Y-m-d H:i') }}</td>

                                    <td style="text-align:center;">
                                        <a href="{{ route('actividades.show', $a->id) }}" class="btn btn-info btn-sm">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>

                                        @can('editar actividades')
                                            <a href="{{ route('actividades.edit', $a->id) }}" class="btn btn-success btn-sm">
                                                <i class="fa-solid fa-pencil"></i>
                                            </a>
                                        @endcan

                                        @can('eliminar actividades')
                                            <form action="{{ route('actividades.destroy', $a->id) }}" method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar esta actividad?');">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if($actividades->isEmpty())
                        <div class="alert alert-info mt-3 mb-0">
                            No hay actividades registradas para el día seleccionado.
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }

        .foto-thumb{
            width: 72px;
            height: 52px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid rgba(0,0,0,.12);
            background: #f8f9fa;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {

            var table = $('#actividades').DataTable({
                "pageLength": 10,
                "order": [[0, "desc"]],
                "language": {
                    "emptyTable": "No hay información disponible",
                    "info": "",
                    "infoEmpty": "",
                    "infoFiltered": "",
                    "lengthMenu": "Mostrar _MENU_ Actividades",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
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
                "autoWidth": false,
            });

            $('#fecha_filtro, #categoria_filtro, #subcategoria_filtro').on('change', function () {
                $('#filtrosForm').submit();
            });

            $('#q_filtro').on('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $('#filtrosForm').submit();
                }
            });

            @if (session('success'))
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: '{{ session('success') }}',
                    showConfirmButton: false,
                    timer: 3000
                });
            @endif
        });
    </script>
@stop
