@extends('adminlte::page')

@section('title', 'Destacamentos')

@section('content_header')
    <h1>Destacamentos</h1>
@stop

@section('content')
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-warning">

                <div class="card-header">
                    <h3 class="card-title">Destacamentos Registrados</h3>

                    <div class="card-tools">
                        @can('ver mapa destacamentos')
                            <a href="{{ route('destacamentos.mapa') }}" class="btn btn-info">
                                <i class="fa-solid fa-map-location-dot"></i> Ver mapa
                            </a>
                        @endcan

                        @can('crear destacamentos')
                            <a href="{{ route('destacamentos.create') }}" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i> Nuevo Destacamento
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="card-body">
                    <table id="destacamentos" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>#</center></th>
                                <th><center>Clave</center></th>
                                <th><center>Nombre</center></th>
                                <th><center>Municipio</center></th>
                                <th><center>Unidad</center></th>
                                <th><center>Activo</center></th>
                                <th><center>Creación</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($destacamentos as $i => $d)
                                <tr>
                                    <td style="text-align:center;">{{ $i + 1 }}</td>
                                    <td>{{ $d->clave ?? '-' }}</td>
                                    <td>{{ $d->nombre }}</td>
                                    <td>{{ $d->municipio ?? '-' }}</td>
                                    <td>{{ $d->unidad->nombre ?? '-' }}</td>
                                    <td>
                                        @if($d->activo)
                                            <span class="badge badge-success">ACTIVO</span>
                                        @else
                                            <span class="badge badge-secondary">INACTIVO</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($d->created_at)->format('d-m-Y') }}</td>
                                    <td style="text-align:center;">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('destacamentos.show', $d->id) }}" class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>

                                            @can('editar destacamentos')
                                                <a href="{{ route('destacamentos.edit', $d->id) }}" class="btn btn-success btn-sm">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                            @endcan

                                            @can('eliminar destacamentos')
                                                <form action="{{ route('destacamentos.destroy', $d->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-danger btn-sm delete-btn">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
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

        .card-tools .btn {
            margin-left: 6px;
        }
    </style>
@stop

@section('js')
<script>
    $(function () {
        $('#destacamentos').DataTable({
            "pageLength": 10,
            "language": {
                "emptyTable": "No hay información",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ Destacamentos",
                "infoEmpty": "Mostrando 0 a 0 de 0 Destacamentos",
                "infoFiltered": "(Filtrado de _MAX_ total Destacamentos)",
                "lengthMenu": "Mostrar _MENU_ Destacamentos",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscador:",
                "zeroRecords": "Sin resultados encontrados",
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
            "buttons": [
                {
                    extend: 'collection',
                    text: 'Opciones',
                    buttons: [
                        { extend: 'copy', text: 'Copiar' },
                        { extend: 'pdf', text: 'PDF' },
                        { extend: 'csv', text: 'CSV' },
                        { extend: 'excel', text: 'Excel' },
                        { extend: 'print', text: 'Imprimir' }
                    ]
                },
                { extend: 'colvis', text: 'Visor de columnas' }
            ],
        }).buttons().container().appendTo('#destacamentos_wrapper .col-md-6:eq(0)');
    });

    @if (session('success'))
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 15000
        });
    @endif

    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        let form = $(this).closest('form');

        Swal.fire({
            title: '¿Eliminar este destacamento?',
            text: "¡No podrás revertir esta acción!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
</script>
@stop
