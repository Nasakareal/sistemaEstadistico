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
                    <h3 class="card-title">Actividades</h3>

                    <div class="card-tools">
                        @can('crear actividades')
                            <a href="{{ route('actividades.create') }}" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i> Añadir nueva actividad
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="categoria_filtro">Filtrar por categoría:</label>
                            <select id="categoria_filtro" class="form-control">
                                <option value="">Todas</option>
                                @foreach ($categorias as $c)
                                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="q_filtro">Buscar por nombre:</label>
                            <input type="text" id="q_filtro" class="form-control" placeholder="Escriba para buscar...">
                        </div>
                    </div>

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
                                    <td data-categoria-id="{{ $a->actividad_categoria_id }}">
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

                                    <td>{{ optional($a->created_at)->format('Y-m-d H:i') }}</td>

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

            $('#categoria_filtro').on('change', function () {
                var selectedCat = $(this).val();

                if (!selectedCat) {
                    table.column(2).search('').draw();
                    return;
                }

                table.column(2).search('^' + selectedCat + '$', true, false).draw();
            });

            // para que el filtro por categoría funcione, filtramos con un regex usando el data-categoria-id
            // hack simple: al dibujar, reemplazamos la búsqueda de columna con el valor del atributo data
            // DataTables busca texto, así que metemos el id al texto oculto
            $('#actividades tbody tr').each(function () {
                var td = $(this).find('td').eq(2);
                var catId = td.data('categoria-id');
                if (catId) {
                    td.prepend('<span class="d-none">' + catId + '</span>');
                }
            });

            $('#q_filtro').on('keyup change', function () {
                table.search($(this).val()).draw();
            });

            // inicializa
            $('#categoria_filtro').trigger('change');
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
    </script>
@stop
