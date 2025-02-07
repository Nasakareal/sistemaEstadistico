@extends('adminlte::page')

@section('title', 'Pases de Lista')

@section('content_header')
    <h1>Pases de Lista</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Pases de Lista</h3>
                    <div class="card-tools">
                        <a href="{{ route('listas.create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Añadir nuevo pase de lista
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtro por área -->
                    <form method="GET" action="{{ route('listas.index') }}" class="mb-3">
                        <div class="form-group row">
                            <label for="area" class="col-sm-2 col-form-label">Filtrar por Área:</label>
                            <div class="col-sm-4">
                                <select name="area" id="area" class="form-control" onchange="this.form.submit()">
                                    <option value="">Todas las Áreas</option>
                                    @foreach ($areas as $area)
                                        <option value="{{ $area }}" {{ request('area') == $area ? 'selected' : '' }}>
                                            {{ $area }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>

                    <table id="listas" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Número de Lista</th>
                                <th>Área</th>
                                <th>Total Asistentes</th>
                                <th>Fecha de Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($listas as $lista)
                                <tr>
                                    <td>{{ $lista->id }}</td>
                                    <td>{{ $lista->area }}</td>
                                    <td>{{ $lista->total_asistentes }}</td>
                                    <td>{{ $lista->created_at->format('d/m/Y H:i') }}</td>
                                    <td style="text-align: center">
                                        <a href="{{ route('listas.show', $lista->id) }}" class="btn btn-info btn-sm">
                                            <i class="fa-regular fa-eye"></i> Ver
                                        </a>
                                        <a href="{{ route('listas.edit', $lista->id) }}" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-pencil"></i> Editar
                                        </a>
                                        <form action="{{ route('listas.destroy', $lista->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este pase de lista?');">
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
            $('#listas').DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información disponible",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Pases de Lista",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Pases de Lista",
                    "infoFiltered": "(Filtrado de _MAX_ total Pases de Lista)",
                    "lengthMenu": "Mostrar _MENU_ Pases de Lista",
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
