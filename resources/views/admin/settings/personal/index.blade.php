@extends('adminlte::page')

@section('title', 'Listado de Personal')

@section('content_header')
    <h1>Listado de Personal</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Personal Registrado</h3>
                    <div class="card-tools">
                        @can('crear personal')
                            <button type="button" class="btn btn-success mr-2" data-toggle="modal" data-target="#modalImportarPersonal">
                                <i class="fa-solid fa-file-excel"></i> Cargar archivo
                            </button>
                            <a href="{{ url('/admin/settings/personal/create') }}" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i> Crear Nuevo Personal
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <table id="personal" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>No.</center></th>
                                <th><center>Nombre</center></th>
                                <th><center>Unidad</center></th>
                                <th><center>No. placa</center></th>
                                <th><center>CUIP</center></th>
                                <th><center>CURP</center></th>
                                <th><center>Grado</center></th>
                                <th><center>Estatus</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($personals as $index => $personal)
                                <tr>
                                    <td style="text-align: center">{{ $index + 1 }}</td>
                                    <td>
                                        {{ $personal->nombre_completo }}
                                    </td>
                                    <td>{{ $personal->unidad->nombre ?? 'N/A' }}</td>
                                    <td>{{ $personal->numero_placa ?? 'N/A' }}</td>
                                    <td>{{ $personal->cuip ?? 'N/A' }}</td>
                                    <td>{{ $personal->curp ?? 'N/A' }}</td>
                                    <td>{{ $personal->grado ?? 'N/A' }}</td>
                                    <td>{{ $personal->estatus ?? 'N/A' }}</td>
                                    <td style="text-align: center">
                                        <div class="btn-group" role="group">
                                            @can('ver personal')
                                                <a href="{{ url('/admin/settings/personal/' . $personal->id) }}" class="btn btn-info btn-sm">
                                                    <i class="fa-regular fa-eye"></i>
                                                </a>
                                            @endcan

                                            @can('editar personal')
                                                <a href="{{ url('/admin/settings/personal/' . $personal->id . '/edit') }}" class="btn btn-success btn-sm">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                            @endcan

                                            @can('borrar personal')
                                                <form action="{{ url('/admin/settings/personal/' . $personal->id) }}"
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

    @can('crear personal')
        <div class="modal fade" id="modalImportarPersonal" tabindex="-1" role="dialog" aria-labelledby="tituloImportarPersonal" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <form action="{{ route('personal.importar') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="tituloImportarPersonal">Carga masiva de personal</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <strong>Unidad de destino:</strong>
                                {{ $unidadImportacion->nombre ?? 'Sin unidad asignada' }}
                                <br>
                                <small>Todos los registros se guardarán en esta unidad. La unidad indicada dentro del Excel, si existiera, será ignorada.</small>
                            </div>

                            <div class="form-group mb-3">
                                <label for="archivo_personal">Archivo Excel</label>
                                <input type="file" name="archivo_personal" id="archivo_personal"
                                       class="form-control-file @error('archivo_personal') is-invalid @enderror"
                                       accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                                       required>
                                <small class="form-text text-muted">Tamaño máximo: 50 MB.</small>
                                @error('archivo_personal')
                                    <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <ul class="small text-muted mb-0 pl-3">
                                <li>Se leerá la hoja <strong>BASE DE DATOS</strong> y la columna <strong>NOMBRE COMPLETO</strong>.</li>
                                <li>La foto, armas, referencias, cursos y columnas no soportadas se ignorarán.</li>
                                <li>Valores como <strong>A POSITIVO</strong>, <strong>O NEGATIVO</strong> o <strong>AB+</strong> se normalizarán automáticamente.</li>
                                <li>El sistema omitirá personal ya registrado y nunca cambiará su unidad por medio de esta carga.</li>
                            </ul>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success" {{ $unidadImportacion ? '' : 'disabled' }}>
                                <i class="fa-solid fa-upload"></i> Importar personal
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endcan
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
            $('#personal').DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ elementos",
                    "infoEmpty": "Mostrando 0 a 0 de 0 elementos",
                    "infoFiltered": "(Filtrado de _MAX_ total elementos)",
                    "lengthMenu": "Mostrar _MENU_ elementos",
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
            }).buttons().container().appendTo('#personal_wrapper .col-md-6:eq(0)');
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

        @if (session('import_result'))
            @php($importResult = session('import_result'))
            Swal.fire({
                icon: {{ ($importResult['omitidos'] ?? 0) > 0 ? "'warning'" : "'success'" }},
                title: 'Carga de personal terminada',
                html: {!! json_encode(
                    '<div class="text-left">' .
                    '<p><strong>Unidad:</strong> ' . e($importResult['unidad'] ?? '') . '</p>' .
                    '<p><strong>Importados:</strong> ' . (int) ($importResult['importados'] ?? 0) .
                    '<br><strong>Omitidos:</strong> ' . (int) ($importResult['omitidos'] ?? 0) . '</p>' .
                    (count($importResult['errores'] ?? [])
                        ? '<hr><strong>Filas omitidas:</strong><ul>' . collect($importResult['errores'])->take(20)->map(fn ($error) => '<li>' . e($error) . '</li>')->implode('') . '</ul>'
                        : '') .
                    (count($importResult['advertencias'] ?? [])
                        ? '<hr><strong>Advertencias:</strong><ul>' . collect($importResult['advertencias'])->take(20)->map(fn ($aviso) => '<li>' . e($aviso) . '</li>')->implode('') . '</ul>'
                        : '') .
                    '</div>',
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ) !!},
                confirmButtonText: 'Aceptar',
                width: 700
            });
        @endif

        @if ($errors->has('archivo_personal'))
            $('#modalImportarPersonal').modal('show');
        @endif

        $(document).on('click', '.delete-btn', function (e) {
            e.preventDefault();

            let form = $(this).closest('form');

            Swal.fire({
                title: '¿Estás seguro de eliminar este elemento?',
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
