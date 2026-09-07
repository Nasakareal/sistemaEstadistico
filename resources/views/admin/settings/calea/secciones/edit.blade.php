@extends('adminlte::page')

@section('title', 'Editar Sección CALEA')

@section('content_header')
    <h1>Editar Sección CALEA</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <strong>{{ $seccion->version->directiva->codigo }}</strong> — {{ $seccion->version->directiva->titulo }}
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Versión:</strong><br>
                            {{ $seccion->version->nombre_version ?: 'Versión ' . $seccion->version->numero_version }}
                        </div>
                        <div class="col-md-3">
                            <strong>Sección:</strong><br>
                            {{ $seccion->numero ? $seccion->numero . '. ' : '' }}{{ $seccion->titulo }}
                        </div>
                        <div class="col-md-3">
                            <strong>Tipo:</strong><br>
                            {{ $tipos[$seccion->tipo] ?? ($seccion->tipo ?? '—') }}
                        </div>
                        <div class="col-md-3">
                            <strong>Bloques:</strong><br>
                            {{ $seccion->bloques->count() }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Datos de la Sección</h3>
                </div>

                <form action="{{ route('settings.calea.secciones.update', $seccion->id) }}" method="POST" id="formSeccion">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>No fue posible actualizar la sección.</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="orden">Orden <span class="text-danger">*</span></label>
                                    <input type="number" name="orden" id="orden" class="form-control @error('orden') is-invalid @enderror" value="{{ old('orden', $seccion->orden) }}" min="0" required>
                                    @error('orden')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="numero">Número</label>
                                    <input type="text" name="numero" id="numero" class="form-control @error('numero') is-invalid @enderror" value="{{ old('numero', $seccion->numero) }}" maxlength="50" placeholder="Ej. 4">
                                    @error('numero')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tipo">Tipo</label>
                                    <select name="tipo" id="tipo" class="form-control @error('tipo') is-invalid @enderror">
                                        <option value="">Seleccionar</option>
                                        @foreach ($tipos as $valor => $nombre)
                                            <option value="{{ $valor }}" {{ old('tipo', $seccion->tipo) === $valor ? 'selected' : '' }}>
                                                {{ $nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('tipo')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="titulo">Título <span class="text-danger">*</span></label>
                                    <input type="text" name="titulo" id="titulo" class="form-control @error('titulo') is-invalid @enderror" value="{{ old('titulo', $seccion->titulo) }}" maxlength="500" required>
                                    @error('titulo')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="contenido">Contenido general de la sección</label>
                            <textarea name="contenido" id="contenido" rows="7" class="form-control @error('contenido') is-invalid @enderror">{{ old('contenido', $seccion->contenido) }}</textarea>
                            @error('contenido')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                            <small class="form-text text-muted">Úsalo para el contenido general. Los numerales, incisos, subtítulos y procedimientos específicos pueden capturarse como bloques.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="pagina_inicio">Página inicial</label>
                                    <input type="number" name="pagina_inicio" id="pagina_inicio" class="form-control @error('pagina_inicio') is-invalid @enderror" value="{{ old('pagina_inicio', $seccion->pagina_inicio) }}" min="1">
                                    @error('pagina_inicio')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="pagina_fin">Página final</label>
                                    <input type="number" name="pagina_fin" id="pagina_fin" class="form-control @error('pagina_fin') is-invalid @enderror" value="{{ old('pagina_fin', $seccion->pagina_fin) }}" min="1">
                                    @error('pagina_fin')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="{{ route('settings.calea.versiones.edit', $seccion->calea_directiva_version_id) }}" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Regresar
                        </a>

                        <button type="submit" class="btn btn-primary float-right" id="guardarSeccion">
                            <i class="fa-regular fa-floppy-disk"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>

            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Bloques de la Sección</h3>
                    <div class="card-tools">
                        <span class="badge badge-info">{{ $seccion->bloques->count() }}</span>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('settings.calea.bloques.store', $seccion->id) }}" method="POST" class="mb-4" id="formBloque">
                        @csrf

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="parent_id">Bloque padre</label>
                                    <select name="parent_id" id="parent_id" class="form-control">
                                        <option value="">Sin padre</option>
                                        @foreach ($seccion->bloques as $bloquePadre)
                                            <option value="{{ $bloquePadre->id }}">
                                                {{ $bloquePadre->numero ? $bloquePadre->numero . ' - ' : '' }}{{ $bloquePadre->titulo ?: \Illuminate\Support\Str::limit($bloquePadre->contenido, 60) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="orden_bloque">Orden</label>
                                    <input type="number" name="orden" id="orden_bloque" class="form-control" min="0">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tipo_bloque">Tipo</label>
                                    <select name="tipo" id="tipo_bloque" class="form-control">
                                        <option value="">Seleccionar</option>
                                        <option value="parrafo">Párrafo</option>
                                        <option value="subtitulo">Subtítulo</option>
                                        <option value="numeral">Numeral</option>
                                        <option value="inciso">Inciso</option>
                                        <option value="vineta">Viñeta</option>
                                        <option value="procedimiento">Procedimiento</option>
                                        <option value="fundamento">Fundamento</option>
                                        <option value="nota">Nota</option>
                                        <option value="definicion">Definición</option>
                                        <option value="advertencia">Advertencia</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="numero_bloque">Número</label>
                                    <input type="text" name="numero" id="numero_bloque" class="form-control" maxlength="100" placeholder="Ej. 1">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="pagina_inicio_bloque">Página</label>
                                    <input type="number" name="pagina_inicio" id="pagina_inicio_bloque" class="form-control" min="1">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="titulo_bloque">Título</label>
                            <input type="text" name="titulo" id="titulo_bloque" class="form-control" maxlength="500" placeholder="Ej. PROCEDIMIENTO ORDEN DE APREHENSIÓN">
                        </div>

                        <div class="form-group">
                            <label for="contenido_bloque">Contenido <span class="text-danger">*</span></label>
                            <textarea name="contenido" id="contenido_bloque" rows="5" class="form-control" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="pagina_fin_bloque">Página final</label>
                                    <input type="number" name="pagina_fin" id="pagina_fin_bloque" class="form-control" min="1">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group mt-4">
                                    <input type="hidden" name="buscable" value="0">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="buscable" name="buscable" value="1" checked>
                                        <label class="custom-control-label" for="buscable">Buscable</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group mt-4">
                                    <input type="hidden" name="citable" value="0">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="citable" name="citable" value="1" checked>
                                        <label class="custom-control-label" for="citable">Citable</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-group w-100">
                                    @can('editar calea')
                                        <button type="submit" class="btn btn-warning btn-block">
                                            <i class="fa-solid fa-plus"></i> Agregar Bloque
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </form>

                    <table id="bloques" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Padre</th>
                                <th>Tipo</th>
                                <th>Número</th>
                                <th>Título</th>
                                <th>Contenido</th>
                                <th>Página</th>
                                <th>Buscable</th>
                                <th>Citable</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($seccion->bloques as $bloque)
                                <tr>
                                    <td>{{ $bloque->orden }}</td>
                                    <td>
                                        @if ($bloque->padre)
                                            {{ $bloque->padre->numero ?: '#' . $bloque->padre->id }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $bloque->tipo ?? '—' }}</td>
                                    <td>{{ $bloque->numero ?? '—' }}</td>
                                    <td class="text-left">{{ $bloque->titulo ?? '—' }}</td>
                                    <td class="text-left">{{ \Illuminate\Support\Str::limit($bloque->contenido, 120) }}</td>
                                    <td>
                                        @if ($bloque->pagina_inicio && $bloque->pagina_fin)
                                            {{ $bloque->pagina_inicio }} - {{ $bloque->pagina_fin }}
                                        @elseif ($bloque->pagina_inicio)
                                            {{ $bloque->pagina_inicio }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($bloque->buscable)
                                            <span class="badge badge-success">Sí</span>
                                        @else
                                            <span class="badge badge-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($bloque->citable)
                                            <span class="badge badge-success">Sí</span>
                                        @else
                                            <span class="badge badge-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @can('editar calea')
                                                <a href="{{ route('settings.calea.bloques.edit', $bloque->id) }}" class="btn btn-success btn-sm" title="Editar bloque">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                            @endcan

                                            @can('eliminar calea')
                                                <form action="{{ route('settings.calea.bloques.destroy', $bloque->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-danger btn-sm eliminar-bloque-btn" title="Eliminar bloque">
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

                    @if ($seccion->bloques->isEmpty())
                        <div class="alert alert-warning mt-3 mb-0">
                            Esta sección todavía no tiene bloques registrados.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        textarea {
            resize: vertical;
        }

        .custom-control-label {
            cursor: pointer;
        }

        #bloques th, #bloques td {
            text-align: center;
            vertical-align: middle;
        }

        #bloques td.text-left {
            text-align: left !important;
        }

        .badge {
            font-size: .85rem;
            padding: .4rem .55rem;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            $('#formSeccion').on('submit', function () {
                const boton = $('#guardarSeccion');

                if (boton.prop('disabled')) {
                    return false;
                }

                boton.prop('disabled', true);
                boton.html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...');
            });

            $('#bloques').DataTable({
                "pageLength": 25,
                "order": [],
                "language": {
                    "emptyTable": "No hay bloques registrados",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ bloques",
                    "infoEmpty": "Mostrando 0 a 0 de 0 bloques",
                    "infoFiltered": "(Filtrado de _MAX_ bloques)",
                    "lengthMenu": "Mostrar _MENU_ bloques",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "No se encontraron bloques",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false
            });
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

        $(document).on('click', '.eliminar-bloque-btn', function (e) {
            e.preventDefault();

            let form = $(this).closest('form');

            Swal.fire({
                title: '¿Eliminar este bloque?',
                text: 'Si tiene bloques hijos, también serán eliminados.',
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
