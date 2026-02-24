@extends('adminlte::page')

@section('title', 'Listado de Armamentos')

@section('content_header')
    <h1>Listado de Armamentos</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Armamentos Registrados</h3>
                    <div class="card-tools">
                        <a href="{{ url('/admin/settings/armamentos/create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Registrar Nuevo Armamento
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="armamentos" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Unidad</th>
                                <th>Tipo</th>
                                <th>Clase</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Matrícula</th>
                                <th>Serie</th>
                                <th>Calibre</th>
                                <th>Estatus</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($armamentos as $index => $armamento)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $armamento->unidad->nombre ?? '—' }}</td>
                                    <td>{{ $armamento->tipo }}</td>
                                    <td>{{ $armamento->clase ?? '—' }}</td>
                                    <td>{{ $armamento->marca ?? '—' }}</td>
                                    <td>{{ $armamento->modelo ?? '—' }}</td>
                                    <td>{{ $armamento->matricula ?? '—' }}</td>
                                    <td>{{ $armamento->serie ?? '—' }}</td>
                                    <td>{{ $armamento->calibre ?? '—' }}</td>
                                    <td>
                                        @if($armamento->estatus === 'ACTIVO')
                                            <span class="badge badge-success">ACTIVO</span>
                                        @elseif($armamento->estatus === 'BAJA')
                                            <span class="badge badge-danger">BAJA</span>
                                        @else
                                            <span class="badge badge-warning">{{ $armamento->estatus }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ url('/admin/settings/armamentos/' . $armamento->id) }}"
                                               class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>

                                            <a href="{{ url('/admin/settings/armamentos/' . $armamento->id . '/edit') }}"
                                               class="btn btn-success btn-sm">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>

                                            <form action="{{ url('/admin/settings/armamentos/' . $armamento->id) }}"
                                                  method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-sm delete-btn">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </form>
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
</style>
@stop

@section('js')
<script>
    $(function () {
        $('#armamentos').DataTable({
            pageLength: 5,
            language: {
                emptyTable: "No hay información",
                info: "Mostrando _START_ a _END_ de _TOTAL_ Armamentos",
                infoEmpty: "Mostrando 0 a 0 de 0 Armamentos",
                infoFiltered: "(Filtrado de _MAX_ total Armamentos)",
                lengthMenu: "Mostrar _MENU_ Armamentos",
                loadingRecords: "Cargando...",
                processing: "Procesando...",
                search: "Buscador:",
                zeroRecords: "Sin resultados encontrados",
                paginate: {
                    first: "Primero",
                    last: "Último",
                    next: "Siguiente",
                    previous: "Anterior"
                }
            },
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            buttons: [
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
        }).buttons().container().appendTo('#armamentos_wrapper .col-md-6:eq(0)');
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

    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        let form = $(this).closest('form');

        Swal.fire({
            title: '¿Estás seguro de eliminar este armamento?',
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
