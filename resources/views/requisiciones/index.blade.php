@extends('adminlte::page')

@section('title', 'Listado de Cuentas Bancarias')

@section('content_header')
    <h1>Listado de Cuentas Bancarias</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Cuentas Bancarias</h3>
                </div>
                <div class="card-body">
                    <table id="cuentas" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>Número</center></th>
                                <th><center>Nombre</center></th>
                                <th><center>Número de Cuenta</center></th>
                                <th><center>Total de Requisiciones</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cuentas as $index => $cuenta)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $cuenta->nombre }}</td>
                                    <td>{{ $cuenta->numero }}</td>
                                    <td>{{ $cuenta->requisiciones_count }}</td> 
                                    <td style="text-align: center">
                                        <a href="{{ route('requisiciones.cuenta.index', $cuenta->id) }}" class="btn btn-info btn-sm">
                                            <i class="fa-regular fa-eye"></i> Ver Requisiciones
                                        </a>


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
            $('#cuentas').DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información disponible",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Cuentas",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Cuentas",
                    "infoFiltered": "(Filtrado de _MAX_ total Cuentas)",
                    "lengthMenu": "Mostrar _MENU_ Cuentas",
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
