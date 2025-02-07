@extends('adminlte::page')

@section('title', 'Listado de Formatos')

@section('content_header')
    <h1>Listado de Formatos</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Formatos</h3>
                    <div class="card-tools">
                        <a href="{{ route('formatos.create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Añadir nuevo formato
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="formatos" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>Nombre</center></th>
                                <th><center>Descripción</center></th>
                                <th><center>Archivo</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($formatos as $formato)
                                <tr>
                                    <td>{{ $formato->nombre }}</td>
                                    <td>{{ $formato->descripcion }}</td>
                                    <td>
                                        <a href="{{ asset('storage/' . $formato->archivo_pdf) }}" target="_blank" class="btn btn-warning btn-sm">
                                            <i class="fas fa-download"></i> Descargar
                                        </a>

                                    </td>
                                    <td style="text-align: center">
                                        <a href="{{ route('formatos.show', $formato->id) }}" class="btn btn-info btn-sm">
                                            <i class="fa-regular fa-eye"></i> Ver
                                        </a>
                                        <a href="{{ route('formatos.edit', $formato->id) }}" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-pencil"></i> Editar
                                        </a>
                                        <form action="{{ route('formatos.destroy', $formato->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este formato?');">
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
            $('#formatos').DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información disponible",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ formatos",
                    "infoEmpty": "Mostrando 0 a 0 de 0 formatos",
                    "infoFiltered": "(Filtrado de _MAX_ total formatos)",
                    "lengthMenu": "Mostrar _MENU_ formatos",
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
