@extends('adminlte::page')

@section('title', 'Usuarios de Grúas')

@section('content_header')
    <h1>Usuarios de Grúas</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-dark">
            <div class="card-header">
                <h3 class="card-title">Listado de Usuarios de Grúas</h3>

                <div class="card-tools">

                    <a href="{{ route('grua_usuarios.create') }}" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i> Nuevo Usuario de Grúa
                    </a>

                </div>
            </div>

            <div class="card-body">
                <table id="grua_usuarios" class="table table-striped table-bordered table-hover table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Grúa</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($usuarios as $index => $usuario)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $usuario->nombre }}</td>
                                <td>{{ $usuario->grua->nombre ?? 'N/A' }}</td>
                                <td>{{ $usuario->email }}</td>
                                <td>{{ $usuario->telefono }}</td>
                                <td>
                                    @if($usuario->activo)
                                        <span class="badge badge-success">ACTIVO</span>
                                    @else
                                        <span class="badge badge-danger">INACTIVO</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">

                                        <a href="{{ route('grua_usuarios.edit', $usuario->id) }}" class="btn btn-success btn-sm">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>

                                        <form action="{{ route('grua_usuarios.destroy', $usuario->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')

                                            <button type="button" class="btn btn-danger btn-sm delete-btn">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>

                                    </div>
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
</style>
@stop

@section('js')
<script>
$(function () {
    $('#grua_usuarios').DataTable({
        pageLength: 10,
        language: {
            emptyTable: "No hay usuarios de grúas",
            info: "Mostrando _START_ a _END_ de _TOTAL_ usuarios",
            infoEmpty: "Mostrando 0 a 0 de 0 usuarios",
            infoFiltered: "(Filtrado de _MAX_ total)",
            lengthMenu: "Mostrar _MENU_ usuarios",
            loadingRecords: "Cargando...",
            processing: "Procesando...",
            search: "Buscar:",
            zeroRecords: "Sin resultados",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        responsive: true,
        lengthChange: true,
        autoWidth: false
    });
});

$(document).on('click', '.delete-btn', function (e) {
    e.preventDefault();

    let form = $(this).closest('form');

    Swal.fire({
        title: '¿Eliminar usuario?',
        text: "Esta acción no se puede deshacer",
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
