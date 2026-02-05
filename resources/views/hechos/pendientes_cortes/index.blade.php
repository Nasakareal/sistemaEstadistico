@extends('adminlte::page')

@section('title', 'Cortes de Pendientes')

@section('content_header')
    <h1>Cortes de Pendientes</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Cortes</h3>
                </div>

                <div class="card-body">
                    @if ($cortes->count() === 0)
                        <div class="alert alert-info">
                            No hay cortes registrados.
                        </div>
                    @endif

                    <table id="cortes" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>ID</center></th>
                                <th><center>Fecha de corte</center></th>
                                <th><center>Generado</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($cortes as $corte)
                                <tr>
                                    <td>{{ $corte->id }}</td>
                                    <td>{{ $corte->corte_fecha }}</td>
                                    <td>{{ $corte->created_at ? $corte->created_at->format('Y-m-d H:i') : '' }}</td>
                                    <td style="text-align:center;">
                                        <a href="{{ route('hechos.pendientes.cortes.show', $corte->id) }}" class="btn btn-info btn-sm">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-3">
                        {{ $cortes->links() }}
                    </div>
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
            $('#cortes').DataTable({
                paging: false,
                info: false,
                order: [[1, "desc"]],
                language: {
                    emptyTable: "No hay información disponible",
                    loadingRecords: "Cargando...",
                    processing: "Procesando...",
                    search: "Buscar:",
                    zeroRecords: "No se encontraron resultados",
                },
                responsive: true,
                lengthChange: false,
                autoWidth: false,
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
