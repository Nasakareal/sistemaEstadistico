@extends('adminlte::page')

@section('title', 'Vehículos del Hecho')

@section('content_header')
    <h1>Vehículos del Hecho: {{ $hecho->folio_c5i }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Listado de Vehículos</h3>
                    <div class="card-tools">
                        <a href="{{ route('vehiculos.create', $hecho->id) }}" class="btn btn-success">
                            <i class="fa-solid fa-car-side"></i> Añadir Vehículo
                        </a>
                        <a href="{{ route('hechos.show', $hecho->id) }}" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Volver al Hecho
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($vehiculos->count() > 0)
                        <table id="vehiculos" class="table table-striped table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th><center>Marca</center></th>
                                    <th><center>Modelo</center></th>
                                    <th><center>Placas</center></th>
                                    <th><center>Conductor</center></th>
                                    <th><center>Acciones</center></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($vehiculos as $vehiculo)
                                    <tr>
                                        <td>{{ $vehiculo->marca }}</td>
                                        <td>{{ $vehiculo->modelo }}</td>
                                        <td>{{ $vehiculo->placas }}</td>
                                        <td>
                                            @foreach($vehiculo->conductores as $conductor)
                                                {{ $conductor->nombre }}@if(!$loop->last), @endif
                                            @endforeach
                                        </td>
                                        <td style="text-align: center">
                                            <a href="{{ route('vehiculos.edit', ['hecho' => $hecho->id, 'vehiculo' => $vehiculo->id]) }}" class="btn btn-success btn-sm">
                                                <i class="fa-solid fa-pencil"></i> Editar
                                            </a>
                                            <form action="{{ route('vehiculos.destroy', ['hecho' => $hecho->id, 'vehiculo' => $vehiculo->id]) }}" method="POST" style="display: inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este vehículo?');">
                                                    <i class="fa-solid fa-trash"></i> Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>No hay vehículos registrados para este hecho.</p>
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
            $('#vehiculos').DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información disponible",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ vehículos",
                    "infoEmpty": "Mostrando 0 a 0 de 0 vehículos",
                    "infoFiltered": "(Filtrado de _MAX_ total vehículos)",
                    "lengthMenu": "Mostrar _MENU_ vehículos",
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
