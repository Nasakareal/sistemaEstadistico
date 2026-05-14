@extends('adminlte::page')

@section('title', 'Catálogos de Actividades')

@section('content_header')
    <h1>Catálogos de Actividades</h1>
@stop

@section('content')

    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-primary">

                <div class="card-header">
                    <h3 class="card-title">Categorías y Subcategorías</h3>

                    <div class="card-tools">

                        @can('crear catalogos actividades')
                            <a href="{{ route('catalogos_actividades.categorias.create') }}"
                               class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i>
                                Nueva Categoría
                            </a>
                        @endcan

                    </div>
                </div>

                <div class="card-body">

                    <table id="catalogos"
                           class="table table-striped table-bordered table-hover table-sm">

                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="25%">Categoría</th>
                                <th width="10%">Estado</th>
                                <th width="45%">Subcategorías</th>
                                <th width="15%">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($categorias as $index => $categoria)

                                <tr>

                                    <td>{{ $index + 1 }}</td>

                                    <td>
                                        <strong>{{ $categoria->nombre }}</strong>
                                    </td>

                                    <td>

                                        @if($categoria->activo)
                                            <span class="badge badge-success">
                                                Activa
                                            </span>
                                        @else
                                            <span class="badge badge-danger">
                                                Inactiva
                                            </span>
                                        @endif

                                    </td>

                                    <td>

                                        <ul class="mb-0 pl-3">

                                            @forelse($categoria->subcategorias as $subcategoria)

                                                <li class="mb-1">

                                                    {{ $subcategoria->nombre }}

                                                    @if($subcategoria->activo)
                                                        <span class="badge badge-success">
                                                            Activa
                                                        </span>
                                                    @else
                                                        <span class="badge badge-danger">
                                                            Inactiva
                                                        </span>
                                                    @endif

                                                    <div class="btn-group btn-group-sm ml-2">

                                                        @can('editar catalogos actividades')
                                                            <a href="{{ route('catalogos_actividades.subcategorias.edit', $subcategoria->id) }}"
                                                               class="btn btn-success btn-sm">
                                                                <i class="fa-regular fa-pen-to-square"></i>
                                                            </a>
                                                        @endcan

                                                        @can('eliminar catalogos actividades')
                                                            <form action="{{ route('catalogos_actividades.subcategorias.destroy', $subcategoria->id) }}"
                                                                  method="POST"
                                                                  style="display:inline-block;">
                                                                @csrf
                                                                @method('DELETE')

                                                                <button type="button"
                                                                        class="btn btn-danger btn-sm delete-btn">
                                                                    <i class="fa-regular fa-trash-can"></i>
                                                                </button>
                                                            </form>
                                                        @endcan

                                                    </div>

                                                </li>

                                            @empty

                                                <li>
                                                    Sin subcategorías registradas
                                                </li>

                                            @endforelse

                                        </ul>

                                    </td>

                                    <td>

                                        <div class="btn-group" role="group">

                                            @can('crear catalogos actividades')
                                                <a href="{{ route('catalogos_actividades.subcategorias.create', $categoria->id) }}"
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fa-solid fa-plus"></i>
                                                </a>
                                            @endcan

                                            @can('editar catalogos actividades')
                                                <a href="{{ route('catalogos_actividades.categorias.edit', $categoria->id) }}"
                                                   class="btn btn-success btn-sm">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                            @endcan

                                            @can('eliminar catalogos actividades')
                                                <form action="{{ route('catalogos_actividades.categorias.destroy', $categoria->id) }}"
                                                      method="POST"
                                                      style="display:inline-block;">

                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="button"
                                                            class="btn btn-danger btn-sm delete-btn">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>

                                                </form>
                                            @endcan

                                        </div>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Programas de Fomento a la Cultura Vial</h3>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th width="25%">Subcategoría</th>
                                    <th width="55%">Programas / talleres / campañas</th>
                                    <th width="20%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fomentoSubcategorias as $subcategoria)
                                    @php
                                        $programas = $fomentoProgramasPorSubcategoria->get($subcategoria->id, collect());
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $subcategoria->nombre }}</strong>
                                        </td>
                                        <td>
                                            <ul class="mb-0 pl-3">
                                                @forelse($programas as $programa)
                                                    <li class="mb-1">
                                                        {{ $programa->nombre }}
                                                        <span class="badge badge-secondary">Orden {{ $programa->orden }}</span>
                                                        @if($programa->activo)
                                                            <span class="badge badge-success">Activo</span>
                                                        @else
                                                            <span class="badge badge-danger">Inactivo</span>
                                                        @endif

                                                        <div class="btn-group btn-group-sm ml-2">
                                                            @can('editar catalogos actividades')
                                                                <a href="{{ route('catalogos_actividades.fomento_programas.edit', $programa->id) }}"
                                                                   class="btn btn-success btn-sm">
                                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                                </a>
                                                            @endcan

                                                            @can('eliminar catalogos actividades')
                                                                <form action="{{ route('catalogos_actividades.fomento_programas.destroy', $programa->id) }}"
                                                                      method="POST"
                                                                      style="display:inline-block;">
                                                                    @csrf
                                                                    @method('DELETE')

                                                                    <button type="button"
                                                                            class="btn btn-danger btn-sm delete-btn">
                                                                        <i class="fa-regular fa-trash-can"></i>
                                                                    </button>
                                                                </form>
                                                            @endcan
                                                        </div>
                                                    </li>
                                                @empty
                                                    <li>Sin programas registrados</li>
                                                @endforelse
                                            </ul>
                                        </td>
                                        <td>
                                            @can('crear catalogos actividades')
                                                <a href="{{ route('catalogos_actividades.fomento_programas.create', $subcategoria->id) }}"
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fa-solid fa-plus"></i> Nuevo programa
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No se encontraron subcategorías de Fomento en CAPACITACIONES.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

@stop

@section('css')

    <style>

        .table th,
        .table td {
            vertical-align: middle;
        }

        ul {
            padding-left: 18px;
        }

    </style>

@stop

@section('js')

<script>

    $(function () {

        $('#catalogos').DataTable({
            "pageLength": 10,
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "language": {
                "emptyTable": "No hay información",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(Filtrado de _MAX_ total registros)",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscador:",
                "zeroRecords": "Sin resultados encontrados",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            }
        });

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

    $(document).on('click', '.delete-btn', function (e) {

        e.preventDefault();

        let form = $(this).closest('form');

        Swal.fire({
            title: '¿Eliminar registro?',
            text: 'Esta acción no se puede revertir',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {

            if (result.isConfirmed) {
                form.submit();
            }

        });

    });

</script>

@stop
