@extends('adminlte::page')

@section('title', 'Tramos')

@section('content_header')
    <h1>Catálogo de Tramos Carreteros</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Listado de Tramos</h3>
                    <div class="card-tools">
                        <a href="{{ route('tramos.create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Nuevo Tramo
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <table id="tramosTable" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Carretera</th>
                                <th>Nombre</th>
                                <th>KM Inicio</th>
                                <th>KM Fin</th>
                                <th>Coordenadas</th>
                                <th>Activo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tramos as $index => $tramo)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $tramo->carretera }}</td>
                                    <td>{{ $tramo->nombre }}</td>
                                    <td>{{ $tramo->km_inicio ?? '-' }}</td>
                                    <td>{{ $tramo->km_fin ?? '-' }}</td>
                                    <td>
                                        @if($tramo->lat_inicio && $tramo->lng_inicio && $tramo->lat_fin && $tramo->lng_fin)
                                            <span class="badge badge-success">CONFIGURADAS</span>
                                        @else
                                            <span class="badge badge-secondary">SIN COORDS</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($tramo->activo)
                                            <span class="badge badge-success">ACTIVO</span>
                                        @else
                                            <span class="badge badge-danger">INACTIVO</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">

                                            <a href="{{ route('tramos.show', $tramo->id) }}"
                                               class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>

                                            <a href="{{ route('tramos.edit', $tramo->id) }}"
                                               class="btn btn-success btn-sm">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>

                                            <form action="{{ route('tramos.destroy', $tramo->id) }}"
                                                  method="POST" style="display:inline-block;">
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
        $('#tramosTable').DataTable({
            "pageLength": 10,
            "language": {
                "emptyTable": "No hay información",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(Filtrado de _MAX_ total registros)",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "Sin resultados encontrados",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            responsive: true,
            lengthChange: true,
            autoWidth: false,
        });
    });

    @if (session('success'))
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 4000
        });
    @endif

    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        let form = $(this).closest('form');

        Swal.fire({
            title: '¿Eliminar este tramo?',
            text: "No podrás revertir esta acción",
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
