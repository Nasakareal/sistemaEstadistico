@extends('adminlte::page')

@section('title', 'Listado de Patrullas')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="mb-0">Control de Patrullas</h1>
            <small class="text-muted">Busca por número económico, placas, unidad o responsable y abre su operación sin usar IDs.</small>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-car-side mr-2"></i>Patrullas Registradas</h3>
                    <div class="card-tools">
                        <a href="{{ url('/admin/settings/patrullas/create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Crear Nueva Patrulla
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="patrullas" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>#</center></th>
                                <th><center>Número Económico</center></th>
                                <th><center>Unidad</center></th>
                                <th><center>Estado</center></th>
                                <th><center>Servicio actual</center></th>
                                <th><center>A resguardo de</center></th>
                                <th><center>Resguardo</center></th>
                                <th><center>Fecha de Registro</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($patrullas as $index => $patrulla)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $patrulla->numero_economico }}</td>
                                    <td>{{ $patrulla->unidad->nombre ?? '—' }}</td>
                                    <td>
                                        @if ($patrulla->activa)
                                            <span class="badge badge-success">Activa</span>
                                        @else
                                            <span class="badge badge-danger">Inactiva</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php($bitacoraActual = $patrulla->ultimaBitacoraServicio)
                                        @if($bitacoraActual && $bitacoraActual->estatus === 'abierta')
                                            <span class="badge badge-success mb-1">
                                                <i class="fa-solid fa-circle-play"></i> En servicio
                                            </span>
                                            <div class="small">
                                                {{ $bitacoraActual->capturado_por_nombre ?: ($bitacoraActual->capturadoPor->name ?? 'Sin responsable') }}
                                            </div>
                                            <div class="small text-muted">
                                                Desde {{ $bitacoraActual->hora_inicio ? substr($bitacoraActual->hora_inicio, 0, 5) : '—' }}
                                            </div>
                                        @else
                                            <span class="badge badge-secondary">Disponible</span>
                                        @endif
                                    </td>
                                    <td>{{ $patrulla->resguardo_nombre ?? '—' }}</td>
                                    <td>
                                        @if ($patrulla->resguardo_pdf_url)
                                            <a href="{{ $patrulla->resguardo_pdf_url }}" target="_blank" rel="noopener" class="btn btn-outline-info btn-sm">
                                                <i class="fa-regular fa-file-pdf"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($patrulla->created_at)->format('d-m-Y') }}</td>
                                    <td>
                                        <div class="d-flex flex-wrap justify-content-center patrol-actions">
                                            <a href="{{ route('patrullas.bitacoras.index', $patrulla->id) }}"
                                               class="btn btn-primary btn-sm"
                                               title="Consultar bitácoras y servicios">
                                                <i class="fa-solid fa-clipboard-list"></i> Bitácoras
                                            </a>

                                            <a href="{{ route('patrullas.entregas_recepciones.index', $patrulla->id) }}"
                                               class="btn btn-info btn-sm"
                                               title="Consultar entregas y recepciones">
                                                <i class="fa-solid fa-right-left"></i> Entregas
                                            </a>

                                            <a href="{{ route('patrullas.show', $patrulla->id) }}"
                                               class="btn btn-secondary btn-sm"
                                               title="Ver ficha de la patrulla">
                                                <i class="fa-regular fa-eye"></i> Ficha
                                            </a>

                                            @can('editar patrullas')
                                                <a href="{{ route('patrullas.edit', $patrulla->id) }}"
                                                   class="btn btn-success btn-sm"
                                                   title="Editar patrulla">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                            @endcan

                                            @can('eliminar patrullas')
                                                <form action="{{ route('patrullas.destroy', $patrulla->id) }}"
                                                      method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-danger btn-sm delete-btn" title="Eliminar patrulla">
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

        .patrol-actions {
            gap: .3rem;
            min-width: 300px;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            $('#patrullas').DataTable({
                "pageLength": 5,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Patrullas",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Patrullas",
                    "infoFiltered": "(Filtrado de _MAX_ total Patrullas)",
                    "lengthMenu": "Mostrar _MENU_ Patrullas",
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
            }).buttons().container().appendTo('#patrullas_wrapper .col-md-6:eq(0)');
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
                title: '¿Estás seguro de eliminar esta patrulla?',
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
