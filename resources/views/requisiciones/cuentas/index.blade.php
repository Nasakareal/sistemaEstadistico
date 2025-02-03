@extends('adminlte::page')

@section('title', 'Requisiciones de ' . $cuentaBancaria->nombre)

@section('content_header')
    <h1>Requisiciones de {{ $cuentaBancaria->nombre }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Requisiciones Asociadas</h3>
                    <div class="card-tools">
                        <a href="{{ route('requisiciones.create', ['cuenta_bancaria_id' => $cuentaBancaria->id]) }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Crear Nueva Requisición
                        </a>

                    </div>
                </div>
                <div class="card-body">
                    <table id="requisiciones" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Número de Requisición</th>
                                <th>Producto o Material</th>
                                <th>Justificación</th>
                                <th>Monto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requisiciones as $requisicion)
                                <tr>
                                    <td>{{ $requisicion->numero_requisicion }}</td>
                                    <td>{{ $requisicion->producto_material }}</td>
                                    <td>{{ $requisicion->justificacion }}</td>
                                    <td>${{ number_format($requisicion->monto, 2) }}</td>
                                    <td>
                                        <a href="{{ route('requisiciones.show', $requisicion->id) }}" class="btn btn-info btn-sm">
                                            <i class="fa-regular fa-eye"></i> Ver
                                        </a>

                                        <a href="{{ route('requisiciones.edit', $requisicion->id) }}" class="btn btn-success btn-sm">
                                            <i class="fa-regular fa-pen-to-square"></i> Editar
                                        </a>

                                        <form action="{{ route('requisiciones.destroy', $requisicion->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fa-regular fa-trash-can"></i> Eliminar
                                            </button>
                                        </form>

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
            $('#requisiciones').DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información disponible",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Requisiciones",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Requisiciones",
                    "infoFiltered": "(Filtrado de _MAX_ total Requisiciones)",
                    "lengthMenu": "Mostrar _MENU_ Requisiciones",
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
        });
    </script>
@stop
