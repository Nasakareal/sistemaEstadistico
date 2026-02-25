@extends('adminlte::page')

@section('title', 'Exámenes Diarios - Módulo Licencia')

@section('content_header')
    <h1>Exámenes Diarios - Módulo de Licencia</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Resultados de Exámenes Realizados</h3>

                    <div class="card-tools">

                        @can('crear examenes diarios')
                            <a href="{{ route('modulo_examenes_diarios.create') }}" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i> Nuevo Registro
                            </a>
                        @endcan

                        <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>

                    </div>
                </div>

                <div class="card-body">
                    <table id="examenes_diarios" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Módulo</th>
                                <th>Total</th>
                                <th>Aprobados</th>
                                <th>Reprobados</th>
                                <th>Hombres</th>
                                <th>Mujeres</th>
                                <th>Folios</th>
                                <th>Informado por</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($registros as $index => $r)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ optional($r->fecha)->format('d-m-Y') }}</td>
                                    <td>{{ $r->modulo_nombre }}</td>
                                    <td>{{ number_format($r->total ?? 0) }}</td>
                                    <td>{{ $r->aprobados !== null ? number_format($r->aprobados) : '—' }}</td>
                                    <td>{{ $r->reprobados !== null ? number_format($r->reprobados) : '—' }}</td>
                                    <td>{{ $r->hombres !== null ? number_format($r->hombres) : '—' }}</td>
                                    <td>{{ $r->mujeres !== null ? number_format($r->mujeres) : '—' }}</td>
                                    <td>{{ $r->folios ?? '—' }}</td>
                                    <td>{{ $r->informado_por ?? '—' }}</td>

                                    <td>
                                        <div class="btn-group" role="group">

                                            @can('ver examenes diarios')
                                                <a href="{{ route('modulo_examenes_diarios.show', $r->id) }}"
                                                   class="btn btn-info btn-sm">
                                                    <i class="fa-regular fa-eye"></i>
                                                </a>
                                            @endcan

                                            @can('editar examenes diarios')
                                                <a href="{{ route('modulo_examenes_diarios.edit', $r->id) }}"
                                                   class="btn btn-success btn-sm">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                            @endcan

                                            @can('eliminar examenes diarios')
                                                <form action="{{ route('modulo_examenes_diarios.destroy', $r->id) }}"
                                                      method="POST" style="display:inline-block;">
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
</style>
@stop


@section('js')
<script>
    $(function () {
        $('#examenes_diarios').DataTable({
            pageLength: 10,
            language: {
                emptyTable: "No hay registros",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(Filtrado de _MAX_ total registros)",
                lengthMenu: "Mostrar _MENU_ registros",
                loadingRecords: "Cargando...",
                processing: "Procesando...",
                search: "Buscar:",
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
        }).buttons().container().appendTo('#examenes_diarios_wrapper .col-md-6:eq(0)');
    });

    @if (session('success'))
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 4000
        });
    @endif

    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();

        let form = $(this).closest('form');

        Swal.fire({
            title: '¿Eliminar registro?',
            text: "Esta acción no se puede revertir.",
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
