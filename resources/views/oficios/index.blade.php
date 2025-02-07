@extends('adminlte::page')

@section('title', 'Listado de Oficios')

@section('content_header')
    <h1>Listado de Oficios</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Oficios</h3>
                    <div class="card-tools">
                        <a href="{{ route('oficios.create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Añadir nuevo oficio
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtro por número de oficio -->
                    <form method="GET" action="{{ route('oficios.index') }}" class="mb-3">
                        <div class="form-group row">
                            <label for="numero_oficio" class="col-sm-2 col-form-label">Buscar por Número de Oficio:</label>
                            <div class="col-sm-4">
                                <input type="text" name="numero_oficio" id="numero_oficio" class="form-control" 
                                       value="{{ request('numero_oficio') }}" placeholder="Número de Oficio">
                            </div>
                            <div class="col-sm-2">
                                <button type="submit" class="btn btn-secondary">Filtrar</button>
                            </div>
                        </div>
                    </form>

                    <table id="oficios" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>Número de Oficio</center></th>
                                <th><center>Descripción</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($oficios as $oficio)
                                <tr>
                                    <td>{{ $oficio->numero_oficio }}</td>
                                    <td>{{ $oficio->descripcion }}</td>
                                    <td style="text-align: center">
                                        <a href="{{ route('oficios.show', $oficio->id) }}" class="btn btn-info btn-sm">
                                            <i class="fa-regular fa-eye"></i> Ver
                                        </a>
                                        <a href="{{ route('oficios.edit', $oficio->id) }}" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-pencil"></i> Editar
                                        </a>
                                        <a href="{{ route('oficios.show', $oficio->id) }}" class="btn btn-warning btn-sm">
                                            <i class="fas fa-download"></i> Descargar
                                        </a>
                                        <form action="{{ route('oficios.destroy', $oficio->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este oficio?');">
                                                <i class="fa-solid fa-trash"></i> Eliminar
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
            $('#oficios').DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información disponible",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Oficios",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Oficios",
                    "infoFiltered": "(Filtrado de _MAX_ total Oficios)",
                    "lengthMenu": "Mostrar _MENU_ Oficios",
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
