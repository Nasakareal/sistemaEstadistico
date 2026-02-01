@extends('adminlte::page')

@section('title', 'Listado de Hechos')

@section('content_header')
    <h1>Listado de Hechos de Tránsito</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Hechos</h3>
                    <div class="card-tools">
                        <a href="{{ url('/hechos/create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Añadir nuevo accidente
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="fecha_filtro">Filtrar por fecha:</label>
                            <input type="date" id="fecha_filtro" class="form-control" value="{{ now()->format('Y-m-d') }}">
                        </div>
                    </div>

                    <table id="hechos" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>Folio</center></th>
                                <th><center>Fecha y Hora</center></th>
                                <th><center>Ubicación</center></th>
                                <th><center>Foto Lugar</center></th>
                                <th><center>Estado</center></th>
                                <th><center>Creado por</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hechos as $hecho)
                                <tr>
                                    <td>{{ $hecho->folio_c5i }}</td>
                                    <td data-fecha="{{ $hecho->fecha }}">{{ $hecho->fecha }} {{ $hecho->hora }}</td>
                                    <td>{{ $hecho->calle }}, {{ $hecho->colonia }}, {{ $hecho->municipio }}</td>

                                    <td>
                                        @php
                                            $foto = $hecho->foto_lugar;
                                            $urlFoto = $foto ? asset('storage/' . ltrim($foto, '/')) : null;
                                        @endphp

                                        @if ($urlFoto)
                                            <a href="{{ $urlFoto }}" target="_blank" rel="noopener">
                                                <img src="{{ $urlFoto }}" alt="foto_lugar" class="foto-thumb">
                                            </a>
                                        @else
                                            <span class="text-muted">Sin foto</span>
                                        @endif
                                    </td>

                                    <td>{{ $hecho->situacion }}</td>
                                    <td>{{ $hecho->creator ? $hecho->creator->name : 'Desconocido' }}</td>
                                    <td style="text-align: center">
                                        <a href="{{ route('hechos.show', $hecho->id) }}" class="btn btn-info btn-sm">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                        <a href="{{ route('hechos.edit', $hecho->id) }}" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-pencil"></i>
                                        </a>
                                        <a href="{{ route('hechos.descargar', $hecho->id) }}" class="btn btn-warning btn-sm">
                                            <i class="fas fa-download"></i>
                                        </a>

                                        <form action="{{ route('hechos.destroy', $hecho->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este hecho?');">
                                                <i class="fa-solid fa-trash"></i>
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

        .foto-thumb{
            width: 72px;
            height: 52px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid rgba(0,0,0,.12);
            background: #f8f9fa;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            var table = $('#hechos').DataTable({
                "pageLength": 10,
                "order": [[1, "asc"]],
                "language": {
                    "emptyTable": "No hay información disponible",
                    "info": "",
                    "infoEmpty": "",
                    "infoFiltered": "",
                    "lengthMenu": "Mostrar _MENU_ Hechos",
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

            $('#fecha_filtro').on('change', function () {
                var selectedDate = $(this).val();
                table.columns(1).search(selectedDate).draw();
            });

            $('#fecha_filtro').trigger('change');
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
