@extends('adminlte::page')

@section('title', 'Editar Directiva CALEA')

@section('content_header')
    <h1>Editar Directiva CALEA</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        Estándar <strong>{{ $directiva->codigo }}</strong>
                    </h3>
                    <div class="card-tools">
                        @can('crear calea')
                            <a href="{{ route('settings.calea.versiones.create', $directiva->id) }}" class="btn btn-warning btn-sm">
                                <i class="fa-solid fa-plus"></i> Nueva Versión
                            </a>
                        @endcan
                    </div>
                </div>
                <form action="{{ route('settings.calea.update', $directiva->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>No fue posible actualizar la directiva.</strong>
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
                                    <label for="codigo">Estándar <span class="text-danger">*</span></label>
                                    <input type="text" name="codigo" id="codigo" class="form-control @error('codigo') is-invalid @enderror" value="{{ old('codigo', $directiva->codigo) }}" maxlength="30" autocomplete="off" required>
                                    @error('codigo')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="form-text text-muted">Ejemplo: 1.2.5, 40.1.2</small>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label for="titulo">Título <span class="text-danger">*</span></label>
                                    <input type="text" name="titulo" id="titulo" class="form-control @error('titulo') is-invalid @enderror" value="{{ old('titulo', $directiva->titulo) }}" maxlength="500" required>
                                    @error('titulo')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="categoria">Categoría</label>
                            <input type="text" name="categoria" id="categoria" class="form-control @error('categoria') is-invalid @enderror" value="{{ old('categoria', $directiva->categoria) }}" list="categoriasCalea" maxlength="255" autocomplete="off">
                            <datalist id="categoriasCalea">
                                @foreach ($categorias as $categoria)
                                    <option value="{{ $categoria }}">
                                @endforeach
                            </datalist>
                            @error('categoria')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea name="descripcion" id="descripcion" rows="4" class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion', $directiva->descripcion) }}</textarea>
                            @error('descripcion')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="form-group mb-0">
                            <input type="hidden" name="activo" value="0">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="activo" name="activo" value="1" {{ old('activo', $directiva->activo ? '1' : '0') == '1' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="activo">Directiva activa</label>
                            </div>
                            <small class="form-text text-muted">Si está desactivada no aparecerá en el módulo normal de consulta.</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('settings.calea.index') }}" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Regresar
                        </a>
                        <button type="submit" class="btn btn-primary float-right" id="guardarDirectiva">
                            <i class="fa-regular fa-floppy-disk"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>

            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Versiones del Estándar {{ $directiva->codigo }}</h3>
                    <div class="card-tools">
                        <span class="badge badge-info">{{ $directiva->versiones->count() }} versiones</span>
                    </div>
                </div>
                <div class="card-body">
                    <table id="versiones" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Versión</th>
                                <th>Emisión</th>
                                <th>Revisión</th>
                                <th>Área Responsable</th>
                                <th>PDF</th>
                                <th>Publicada</th>
                                <th>Vigente</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($directiva->versiones as $index => $version)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $version->nombre_version ?: 'Versión ' . $version->numero_version }}</strong>
                                    </td>
                                    <td>
                                        @if ($version->fecha_emision)
                                            {{ $version->fecha_emision->format('d/m/Y') }}
                                        @elseif ($version->mes_emision && $version->anio_emision)
                                            @php
                                                $meses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
                                            @endphp
                                            {{ $meses[$version->mes_emision] ?? $version->mes_emision }} {{ $version->anio_emision }}
                                        @elseif ($version->anio_emision)
                                            {{ $version->anio_emision }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        {{ $version->fecha_revision ? $version->fecha_revision->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="text-left">
                                        {{ $version->area_responsable ?? '—' }}
                                    </td>
                                    <td>
                                        @if ($version->archivo_path)
                                            <a href="{{ route('settings.calea.versiones.pdf', $version->id) }}" target="_blank" class="btn btn-danger btn-sm" title="Ver PDF original">
                                                <i class="fa-regular fa-file-pdf"></i>
                                            </a>
                                        @else
                                            <span class="badge badge-secondary">Sin PDF</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($version->publicada)
                                            <span class="badge badge-success">Sí</span>
                                        @else
                                            <span class="badge badge-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($version->vigente)
                                            <span class="badge badge-success">Vigente</span>
                                        @else
                                            <span class="badge badge-secondary">Histórica</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @can('editar calea')
                                                <a href="{{ route('settings.calea.versiones.edit', $version->id) }}" class="btn btn-success btn-sm" title="Editar versión">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                                @if (!$version->vigente)
                                                    <form action="{{ route('settings.calea.versiones.vigente', $version->id) }}" method="POST" style="display:inline-block;">
                                                        @csrf
                                                        <button type="button" class="btn btn-primary btn-sm vigente-btn" title="Marcar como vigente">
                                                            <i class="fa-solid fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan
                                            @can('eliminar calea')
                                                <form action="{{ route('settings.calea.versiones.destroy', $version->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-danger btn-sm eliminar-version-btn" title="Eliminar versión">
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
                    @if ($directiva->versiones->isEmpty())
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Esta directiva todavía no tiene versiones registradas.
                            @can('crear calea')
                                <a href="{{ route('settings.calea.versiones.create', $directiva->id) }}">Registrar la primera versión</a>.
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        #versiones th, #versiones td {
            text-align: center;
            vertical-align: middle;
        }

        #versiones td.text-left {
            text-align: left !important;
        }

        #versiones .badge {
            font-size: .85rem;
            padding: .4rem .55rem;
        }

        .custom-control-label {
            cursor: pointer;
        }

        textarea {
            resize: vertical;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            $('#codigo').on('input', function () {
                let valor = $(this).val().replace(/[^0-9.]/g, '');
                valor = valor.replace(/\.{2,}/g, '.');
                $(this).val(valor);
            });

            $('#versiones').DataTable({
                "pageLength": 10,
                "order": [],
                "language": {
                    "emptyTable": "No hay versiones registradas",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ versiones",
                    "infoEmpty": "Mostrando 0 a 0 de 0 versiones",
                    "infoFiltered": "(Filtrado de _MAX_ versiones)",
                    "lengthMenu": "Mostrar _MENU_ versiones",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "No se encontraron versiones",
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

            $('form[action="{{ route('settings.calea.update', $directiva->id) }}"]').on('submit', function () {
                const boton = $('#guardarDirectiva');
                boton.prop('disabled', true);
                boton.html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...');
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

        $(document).on('click', '.vigente-btn', function (e) {
            e.preventDefault();
            let form = $(this).closest('form');

            Swal.fire({
                title: '¿Marcar esta versión como vigente?',
                text: 'La versión actualmente vigente dejará de serlo.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, marcar como vigente',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        $(document).on('click', '.eliminar-version-btn', function (e) {
            e.preventDefault();
            let form = $(this).closest('form');

            Swal.fire({
                title: '¿Eliminar esta versión?',
                text: 'Se eliminarán también sus secciones y bloques asociados.',
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
