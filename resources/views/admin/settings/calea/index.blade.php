@extends('adminlte::page')

@section('title', 'Directivas CALEA')

@section('content_header')
    <h1>Directivas CALEA</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Administración de Directivas CALEA</h3>
                    <div class="card-tools">
                        @can('crear calea')
                            <a href="{{ route('settings.calea.create') }}" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i> Nueva Directiva
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <table id="directivas" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Estándar</th>
                                <th>Título</th>
                                <th>Categoría</th>
                                <th>Versión vigente</th>
                                <th>Revisión</th>
                                <th>Versiones</th>
                                <th>Estatus</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($directivas as $index => $directiva)
                                @php($version = $directiva->versionVigente)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $directiva->codigo }}</strong></td>
                                    <td class="text-left">{{ $directiva->titulo }}</td>
                                    <td>{{ $directiva->categoria ?? '—' }}</td>
                                    <td>
                                        @if ($version)
                                            <span class="badge badge-primary">
                                                {{ $version->nombre_version ?: 'Versión ' . $version->numero_version }}
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">Sin versión</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($version && $version->fecha_revision)
                                            {{ $version->fecha_revision->format('d/m/Y') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $directiva->versiones_count }}</span>
                                    </td>
                                    <td>
                                        @if ($directiva->activo)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-danger">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @can('ver calea')
                                                <a href="{{ route('calea.show', $directiva->id) }}" class="btn btn-info btn-sm" title="Consultar directiva">
                                                    <i class="fa-regular fa-eye"></i>
                                                </a>
                                            @endcan
                                            @if ($version && $version->archivo_path)
                                                <a href="{{ route('settings.calea.versiones.pdf', $version->id) }}" class="btn btn-secondary btn-sm" target="_blank" title="Ver PDF vigente">
                                                    <i class="fa-regular fa-file-pdf"></i>
                                                </a>
                                            @endif
                                            @can('crear calea')
                                                <a href="{{ route('settings.calea.versiones.create', $directiva->id) }}" class="btn btn-warning btn-sm" title="Agregar versión">
                                                    <i class="fa-solid fa-code-branch"></i>
                                                </a>
                                            @endcan
                                            @can('editar calea')
                                                <a href="{{ route('settings.calea.edit', $directiva->id) }}" class="btn btn-success btn-sm" title="Editar directiva">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                            @endcan
                                            @can('eliminar calea')
                                                @if ($directiva->activo)
                                                    <form action="{{ route('settings.calea.destroy', $directiva->id) }}" method="POST" style="display:inline-block;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-danger btn-sm desactivar-btn" title="Desactivar directiva">
                                                            <i class="fa-solid fa-ban"></i>
                                                        </button>
                                                    </form>
                                                @endif
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
        #directivas th, #directivas td {
            text-align: center;
            vertical-align: middle;
        }

        #directivas td.text-left {
            text-align: left !important;
        }

        #directivas .badge {
            font-size: .85rem;
            padding: .4rem .55rem;
        }

        #directivas .btn-group .btn {
            margin-right: 2px;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            $('#directivas').DataTable({
                "pageLength": 25,
                "order": [],
                "language": {
                    "emptyTable": "No hay directivas CALEA registradas",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ directivas",
                    "infoEmpty": "Mostrando 0 a 0 de 0 directivas",
                    "infoFiltered": "(Filtrado de _MAX_ directivas)",
                    "lengthMenu": "Mostrar _MENU_ directivas",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "No se encontraron directivas",
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
                ]
            }).buttons().container().appendTo('#directivas_wrapper .col-md-6:eq(0)');
        });

        @if (session('success'))
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: @json(session('success')),
                showConfirmButton: false,
                timer: 5000
            });
        @endif

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Ocurrió un problema',
                html: @json($errors->first()),
                confirmButtonText: 'Aceptar'
            });
        @endif

        $(document).on('click', '.desactivar-btn', function (e) {
            e.preventDefault();
            let form = $(this).closest('form');

            Swal.fire({
                title: '¿Desactivar esta directiva?',
                text: 'Dejará de aparecer en la consulta normal, pero conservará todas sus versiones y contenido.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, desactivar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
@stop
