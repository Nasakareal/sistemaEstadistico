@extends('adminlte::page')

@section('title', 'Calendario SCT - Guardias por Día')

@section('content_header')
    <h1>Calendario SCT - Guardias por Día del Mes</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Rangos de Días SCT</h3>
                    <div class="card-tools">
                        <a href="{{ route('grua-guardias-sct.create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Nueva Guardia SCT
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <table id="guardiasSct" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Grúa</th>
                                <th>Tramo</th>
                                <th>Día Inicio</th>
                                <th>Día Fin</th>
                                <th>Prioridad</th>
                                <th>Activo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($guardias as $index => $guardia)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $guardia->grua->nombre ?? '-' }}</td>
                                    <td>
                                        @if($guardia->tramo)
                                            {{ $guardia->tramo->carretera }} - {{ $guardia->tramo->nombre }}
                                        @else
                                            GENERAL
                                        @endif
                                    </td>
                                    <td>{{ $guardia->dia_inicio }}</td>
                                    <td>
                                        @if($guardia->dia_fin == 31)
                                            {{ $guardia->dia_fin }} (ÚLTIMO DÍA)
                                        @else
                                            {{ $guardia->dia_fin }}
                                        @endif
                                    </td>
                                    <td>{{ $guardia->prioridad }}</td>
                                    <td>
                                        @if($guardia->activo)
                                            <span class="badge badge-success">ACTIVO</span>
                                        @else
                                            <span class="badge badge-danger">INACTIVO</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">

                                            <a href="{{ route('grua-guardias-sct.show', $guardia->id) }}" 
                                               class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>

                                            <a href="{{ route('grua-guardias-sct.edit', $guardia->id) }}" 
                                               class="btn btn-success btn-sm">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>

                                            <form action="{{ route('grua-guardias-sct.destroy', $guardia->id) }}" 
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
        $('#guardiasSct').DataTable({
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
            title: '¿Eliminar esta guardia SCT?',
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
