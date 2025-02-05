@extends('adminlte::page')

@section('title', 'Listado de Dictámenes')

@section('content_header')
    <h1>Listado de Dictámenes</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Dictámenes</h3>
                    <div class="card-tools">
                        <a href="{{ route('dictamenes.create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Añadir nuevo dictamen
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtro por año -->
                    <form method="GET" action="{{ route('dictamenes.index') }}" class="mb-3">
                        <div class="form-group row">
                            <label for="anio" class="col-sm-2 col-form-label">Filtrar por Año:</label>
                            <div class="col-sm-4">
                                <select name="anio" id="anio" class="form-control" onchange="this.form.submit()">
                                    @foreach ($anios as $anio)
                                        <option value="{{ $anio }}" {{ request('anio', $anioActual) == $anio ? 'selected' : '' }}>
                                            {{ $anio }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>

                    <table id="dictamenes" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>Número de Dictamen</center></th>
                                <th><center>Año</center></th>
                                <th><center>Policía</center></th>
                                <th><center>MP</center></th>
                                <th><center>Área</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dictamenes as $dictamen)
                                <tr>
                                    <td>{{ $dictamen->numero_dictamen }}</td>
                                    <td>{{ $dictamen->anio }}</td>
                                    <td>{{ $dictamen->nombre_policia }}</td>
                                    <td>{{ $dictamen->nombre_mp }}</td>
                                    <td>{{ $dictamen->area }}</td>
                                    <td style="text-align: center">
                                        <a href="{{ route('dictamenes.show', $dictamen->id) }}" class="btn btn-info btn-sm">
                                            <i class="fa-regular fa-eye"></i> Ver
                                        </a>
                                        <a href="{{ route('dictamenes.edit', $dictamen->id) }}" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-pencil"></i> Editar
                                        </a>
                                        <a href="{{ route('dictamenes.show', $dictamen->id) }}" class="btn btn-warning btn-sm">
                                            <i class="fas fa-download"></i> Descargar
                                        </a>
                                        <form action="{{ route('dictamenes.destroy', $dictamen->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este dictamen?');">
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
            $('#dictamenes').DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información disponible",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Dictámenes",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Dictámenes",
                    "infoFiltered": "(Filtrado de _MAX_ total Dictámenes)",
                    "lengthMenu": "Mostrar _MENU_ Dictámenes",
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
