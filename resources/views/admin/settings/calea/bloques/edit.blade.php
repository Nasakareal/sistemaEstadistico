@extends('adminlte::page')

@section('title', 'Editar Bloque CALEA')

@section('content_header')
    <h1>Editar Bloque CALEA</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <strong>{{ $bloque->seccion->version->directiva->codigo }}</strong> — {{ $bloque->seccion->version->directiva->titulo }}
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Versión:</strong><br>
                            {{ $bloque->seccion->version->nombre_version ?: 'Versión ' . $bloque->seccion->version->numero_version }}
                        </div>
                        <div class="col-md-3">
                            <strong>Sección:</strong><br>
                            {{ $bloque->seccion->numero ? $bloque->seccion->numero . '. ' : '' }}{{ $bloque->seccion->titulo }}
                        </div>
                        <div class="col-md-3">
                            <strong>Tipo:</strong><br>
                            {{ $tipos[$bloque->tipo] ?? ($bloque->tipo ?? '—') }}
                        </div>
                        <div class="col-md-3">
                            <strong>Bloques hijos:</strong><br>
                            {{ $bloque->hijos->count() }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Datos del Bloque</h3>
                </div>

                <form action="{{ route('settings.calea.bloques.update', $bloque->id) }}" method="POST" id="formBloque">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>No fue posible actualizar el bloque.</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="parent_id">Bloque padre</label>
                                    <select name="parent_id" id="parent_id" class="form-control @error('parent_id') is-invalid @enderror">
                                        <option value="">Sin padre</option>
                                        @foreach ($bloquesDisponibles as $bloquePadre)
                                            <option value="{{ $bloquePadre->id }}" {{ old('parent_id', $bloque->parent_id) == $bloquePadre->id ? 'selected' : '' }}>
                                                {{ $bloquePadre->numero ? $bloquePadre->numero . ' - ' : '' }}{{ $bloquePadre->titulo ?: \Illuminate\Support\Str::limit($bloquePadre->contenido, 70) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('parent_id')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="orden">Orden <span class="text-danger">*</span></label>
                                    <input type="number" name="orden" id="orden" class="form-control @error('orden') is-invalid @enderror" value="{{ old('orden', $bloque->orden) }}" min="0" max="65535" required>
                                    @error('orden')
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
                                            <option value="{{ $valor }}" {{ old('tipo', $bloque->tipo) === $valor ? 'selected' : '' }}>
                                                {{ $nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('tipo')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="numero">Número</label>
                                    <input type="text" name="numero" id="numero" class="form-control @error('numero') is-invalid @enderror" value="{{ old('numero', $bloque->numero) }}" maxlength="100" placeholder="Ej. 1">
                                    @error('numero')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="pagina_inicio">Página inicial</label>
                                    <input type="number" name="pagina_inicio" id="pagina_inicio" class="form-control @error('pagina_inicio') is-invalid @enderror" value="{{ old('pagina_inicio', $bloque->pagina_inicio) }}" min="1">
                                    @error('pagina_inicio')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="titulo">Título</label>
                            <input type="text" name="titulo" id="titulo" class="form-control @error('titulo') is-invalid @enderror" value="{{ old('titulo', $bloque->titulo) }}" maxlength="500" placeholder="Ej. PROCEDIMIENTO ORDEN DE APREHENSIÓN">
                            @error('titulo')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="contenido">Contenido <span class="text-danger">*</span></label>
                            <textarea name="contenido" id="contenido" rows="10" class="form-control @error('contenido') is-invalid @enderror" required>{{ old('contenido', $bloque->contenido) }}</textarea>
                            @error('contenido')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="pagina_fin">Página final</label>
                                    <input type="number" name="pagina_fin" id="pagina_fin" class="form-control @error('pagina_fin') is-invalid @enderror" value="{{ old('pagina_fin', $bloque->pagina_fin) }}" min="1">
                                    @error('pagina_fin')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group mt-4">
                                    <input type="hidden" name="buscable" value="0">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="buscable" name="buscable" value="1" {{ old('buscable', $bloque->buscable ? '1' : '0') == '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="buscable">Buscable</label>
                                    </div>
                                    <small class="form-text text-muted">Permite que este bloque aparezca en búsquedas de CALEA.</small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group mt-4">
                                    <input type="hidden" name="citable" value="0">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="citable" name="citable" value="1" {{ old('citable', $bloque->citable ? '1' : '0') == '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="citable">Citable</label>
                                    </div>
                                    <small class="form-text text-muted">Permite utilizar este contenido como fundamento en tarjetas, oficios y documentos.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="{{ route('settings.calea.secciones.edit', $bloque->calea_directiva_seccion_id) }}" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Regresar
                        </a>

                        <button type="submit" class="btn btn-primary float-right" id="guardarBloque">
                            <i class="fa-regular fa-floppy-disk"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>

            @if ($bloque->padre)
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Bloque Padre</h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-1">
                            <strong>{{ $bloque->padre->numero ?? '' }}</strong>
                            {{ $bloque->padre->titulo ?? '' }}
                        </p>
                        <p class="mb-0 text-muted">
                            {{ \Illuminate\Support\Str::limit($bloque->padre->contenido, 300) }}
                        </p>
                    </div>
                </div>
            @endif

            @if ($bloque->hijos->count())
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Bloques Hijos</h3>
                        <div class="card-tools">
                            <span class="badge badge-info">{{ $bloque->hijos->count() }}</span>
                        </div>
                    </div>

                    <div class="card-body">
                        <table id="hijos" class="table table-striped table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Orden</th>
                                    <th>Tipo</th>
                                    <th>Número</th>
                                    <th>Título</th>
                                    <th>Contenido</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bloque->hijos as $hijo)
                                    <tr>
                                        <td>{{ $hijo->orden }}</td>
                                        <td>{{ $tipos[$hijo->tipo] ?? ($hijo->tipo ?? '—') }}</td>
                                        <td>{{ $hijo->numero ?? '—' }}</td>
                                        <td class="text-left">{{ $hijo->titulo ?? '—' }}</td>
                                        <td class="text-left">{{ \Illuminate\Support\Str::limit($hijo->contenido, 150) }}</td>
                                        <td>
                                            <a href="{{ route('settings.calea.bloques.edit', $hijo->id) }}" class="btn btn-success btn-sm">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
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

        #hijos th, #hijos td {
            text-align: center;
            vertical-align: middle;
        }

        #hijos td.text-left {
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
            $('#formBloque').on('submit', function () {
                const boton = $('#guardarBloque');

                if (boton.prop('disabled')) {
                    return false;
                }

                boton.prop('disabled', true);
                boton.html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...');
            });

            @if ($bloque->hijos->count())
                $('#hijos').DataTable({
                    "pageLength": 10,
                    "order": [],
                    "language": {
                        "emptyTable": "No hay bloques hijos",
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
            @endif
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
    </script>
@stop
