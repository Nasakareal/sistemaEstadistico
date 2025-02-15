@extends('adminlte::page')

@section('title', 'Lesionados del Hecho')

@section('content_header')
    <h1>Lesionados del Hecho: {{ $hecho->folio_c5i }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Listado de Lesionados</h3>
                    <div class="card-tools">
                        <a href="{{ route('lesionados.create', $hecho->id) }}" class="btn btn-success">
                            <i class="fa-solid fa-user-injured"></i> Añadir Lesionado
                        </a>
                        <a href="{{ route('hechos.show', $hecho->id) }}" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Volver al Hecho
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($lesionados->count() > 0)
                        <table id="lesionados" class="table table-striped table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th><center>Nombre</center></th>
                                    <th><center>Edad</center></th>
                                    <th><center>Sexo</center></th>
                                    <th><center>Tipo de Lesión</center></th>
                                    <th><center>Hospitalizado</center></th>
                                    <th><center>Ambulancia</center></th>
                                    <th><center>Acciones</center></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lesionados as $lesionado)
                                    <tr>
                                        <td>{{ $lesionado->nombre }}</td>
                                        <td>{{ $lesionado->edad ?? 'N/A' }}</td>
                                        <td>{{ $lesionado->sexo }}</td>
                                        <td>{{ $lesionado->tipo_lesion }}</td>
                                        <td>
                                            @if($lesionado->hospitalizado)
                                                Sí ({{ $lesionado->hospital ?? 'No especificado' }})
                                            @else
                                                No
                                            @endif
                                        </td>
                                        <td>{{ $lesionado->ambulancia ?? 'No registrada' }}</td>
                                        <td style="text-align: center">
                                            <a href="{{ route('lesionados.edit', ['hecho' => $hecho->id, 'lesionado' => $lesionado->id]) }}" class="btn btn-success btn-sm">
                                                <i class="fa-solid fa-pencil"></i> Editar
                                            </a>
                                            <form action="{{ route('lesionados.destroy', ['hecho' => $hecho->id, 'lesionado' => $lesionado->id]) }}" method="POST" style="display: inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este lesionado?');">
                                                    <i class="fa-solid fa-trash"></i> Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>No hay lesionados registrados para este hecho.</p>
                    @endif
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
            $('#lesionados').DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información disponible",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ lesionados",
                    "infoEmpty": "Mostrando 0 a 0 de 0 lesionados",
                    "infoFiltered": "(Filtrado de _MAX_ total lesionados)",
                    "lengthMenu": "Mostrar _MENU_ lesionados",
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
