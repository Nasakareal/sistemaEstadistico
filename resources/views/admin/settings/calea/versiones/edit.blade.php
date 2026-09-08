@extends('adminlte::page')

@section('title', 'Editar Versión CALEA')

@section('content_header')
    <h1>Editar Versión CALEA</h1>
@stop

@section('content')
    <div class="row calea-version-page">
        <div class="col-md-12">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <strong>{{ $version->directiva->codigo }}</strong> — {{ $version->directiva->titulo }}
                    </h3>
                    <div class="card-tools">
                        @if ($version->archivo_path)
                            <a href="{{ route('settings.calea.versiones.pdf', $version->id) }}" target="_blank" class="btn btn-danger btn-sm">
                                <i class="fa-regular fa-file-pdf"></i> Ver PDF
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Estándar:</strong><br>
                            {{ $version->directiva->codigo }}
                        </div>
                        <div class="col-md-3">
                            <strong>Versión:</strong><br>
                            {{ $version->nombre_version ?: 'Versión ' . $version->numero_version }}
                        </div>
                        <div class="col-md-3">
                            <strong>Estatus:</strong><br>
                            @if ($version->vigente)
                                <span class="badge badge-success">Vigente</span>
                            @else
                                <span class="badge badge-secondary">Histórica</span>
                            @endif
                        </div>
                        <div class="col-md-3">
                            <strong>Secciones:</strong><br>
                            {{ $version->secciones_count }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Datos de la Versión</h3>
                </div>

                <form action="{{ route('settings.calea.versiones.update', $version->id) }}" method="POST" enctype="multipart/form-data" id="formVersion">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>No fue posible actualizar la versión.</strong>
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
                                    <label for="numero_version">Número de versión <span class="text-danger">*</span></label>
                                    <input type="number" name="numero_version" id="numero_version" class="form-control @error('numero_version') is-invalid @enderror" value="{{ old('numero_version', $version->numero_version) }}" min="1" max="65535" required>
                                    @error('numero_version')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="nombre_version">Nombre de versión</label>
                                    <input type="text" name="nombre_version" id="nombre_version" class="form-control @error('nombre_version') is-invalid @enderror" value="{{ old('nombre_version', $version->nombre_version) }}" maxlength="100">
                                    @error('nombre_version')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fecha_revision">Fecha de revisión</label>
                                    <input type="date" name="fecha_revision" id="fecha_revision" class="form-control @error('fecha_revision') is-invalid @enderror" value="{{ old('fecha_revision', optional($version->fecha_revision)->format('Y-m-d')) }}">
                                    @error('fecha_revision')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3">
                            <i class="fa-regular fa-calendar"></i> Fecha de emisión
                        </h5>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fecha_emision">Fecha exacta</label>
                                    <input type="date" name="fecha_emision" id="fecha_emision" class="form-control @error('fecha_emision') is-invalid @enderror" value="{{ old('fecha_emision', optional($version->fecha_emision)->format('Y-m-d')) }}">
                                    @error('fecha_emision')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="form-text text-muted">Úsala si el documento indica día, mes y año.</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="mes_emision">Mes de emisión</label>
                                    <select name="mes_emision" id="mes_emision" class="form-control @error('mes_emision') is-invalid @enderror">
                                        <option value="">Seleccionar</option>
                                        @php
                                            $meses = [
                                                1 => 'Enero',
                                                2 => 'Febrero',
                                                3 => 'Marzo',
                                                4 => 'Abril',
                                                5 => 'Mayo',
                                                6 => 'Junio',
                                                7 => 'Julio',
                                                8 => 'Agosto',
                                                9 => 'Septiembre',
                                                10 => 'Octubre',
                                                11 => 'Noviembre',
                                                12 => 'Diciembre'
                                            ];
                                        @endphp
                                        @foreach ($meses as $numero => $nombre)
                                            <option value="{{ $numero }}" {{ old('mes_emision', $version->mes_emision) == $numero ? 'selected' : '' }}>
                                                {{ $nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('mes_emision')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="anio_emision">Año de emisión</label>
                                    <input type="number" name="anio_emision" id="anio_emision" class="form-control @error('anio_emision') is-invalid @enderror" value="{{ old('anio_emision', $version->anio_emision) }}" min="1900" max="2100">
                                    @error('anio_emision')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3">
                            <i class="fa-solid fa-building-shield"></i> Datos del documento
                        </h5>

                        <div class="form-group">
                            <label for="area_responsable">Área responsable</label>
                            <textarea name="area_responsable" id="area_responsable" rows="2" class="form-control @error('area_responsable') is-invalid @enderror">{{ old('area_responsable', $version->area_responsable) }}</textarea>
                            @error('area_responsable')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="autoriza">Autoriza</label>
                            <textarea name="autoriza" id="autoriza" rows="3" class="form-control @error('autoriza') is-invalid @enderror">{{ old('autoriza', $version->autoriza) }}</textarea>
                            @error('autoriza')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="realizado_por">Realizado por</label>
                            <textarea name="realizado_por" id="realizado_por" rows="3" class="form-control @error('realizado_por') is-invalid @enderror">{{ old('realizado_por', $version->realizado_por) }}</textarea>
                            @error('realizado_por')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="leyenda_documento">Leyenda del documento</label>
                            <textarea name="leyenda_documento" id="leyenda_documento" rows="3" class="form-control @error('leyenda_documento') is-invalid @enderror">{{ old('leyenda_documento', $version->leyenda_documento) }}</textarea>
                            @error('leyenda_documento')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <hr>

                        <h5 class="mb-3">
                            <i class="fa-regular fa-file-pdf"></i> Documento original
                        </h5>

                        @if ($version->archivo_path)
                            <div class="alert alert-info">
                                <strong>Archivo actual:</strong>
                                {{ $version->archivo_nombre_original ?? 'Documento PDF' }}
                                @if ($version->archivo_size)
                                    <br>
                                    <small>{{ number_format($version->archivo_size / 1024 / 1024, 2) }} MB</small>
                                @endif
                                @if ($version->archivo_sha256)
                                    <br>
                                    <small><strong>SHA-256:</strong> {{ $version->archivo_sha256 }}</small>
                                @endif
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label for="archivo">Reemplazar PDF</label>
                                    <div class="custom-file">
                                        <input type="file" name="archivo" id="archivo" class="custom-file-input @error('archivo') is-invalid @enderror" accept=".pdf,application/pdf">
                                        <label class="custom-file-label" for="archivo">Seleccionar nuevo PDF...</label>
                                    </div>
                                    @error('archivo')
                                        <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="form-text text-muted">Déjalo vacío para conservar el archivo actual. Máximo 50 MB.</small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="numero_paginas">Número de páginas</label>
                                    <input type="number" name="numero_paginas" id="numero_paginas" class="form-control @error('numero_paginas') is-invalid @enderror" value="{{ old('numero_paginas', $version->numero_paginas) }}" min="1" max="10000">
                                    @error('numero_paginas')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="texto_busqueda">Texto completo para búsqueda</label>
                            <textarea name="texto_busqueda" id="texto_busqueda" rows="10" class="form-control @error('texto_busqueda') is-invalid @enderror">{{ old('texto_busqueda', $version->texto_busqueda) }}</textarea>
                            @error('texto_busqueda')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                            <small class="form-text text-muted">Este contenido se utilizará para búsquedas rápidas dentro de CALEA.</small>
                        </div>

                        <hr>

                        <h5 class="mb-3">
                            <i class="fa-solid fa-sliders"></i> Estado de la versión
                        </h5>

                        <div class="row">
                            <div class="col-md-6">
                                <input type="hidden" name="vigente" value="0">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="vigente" name="vigente" value="1" {{ old('vigente', $version->vigente ? '1' : '0') == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="vigente">Versión vigente</label>
                                </div>
                                <small class="form-text text-muted">Si la activas, las demás versiones dejarán de estar vigentes.</small>
                            </div>

                            <div class="col-md-6">
                                <input type="hidden" name="publicada" value="0">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="publicada" name="publicada" value="1" {{ old('publicada', $version->publicada ? '1' : '0') == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="publicada">Versión publicada</label>
                                </div>
                                <small class="form-text text-muted">Solo las versiones publicadas pueden mostrarse en consulta.</small>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="{{ route('settings.calea.edit', $version->calea_directiva_id) }}" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Regresar
                        </a>

                        <button type="submit" class="btn btn-primary float-right" id="guardarVersion">
                            <i class="fa-regular fa-floppy-disk"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>

            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Secciones de la Versión</h3>
                    <div class="card-tools">
                        <span class="badge badge-info">{{ $version->secciones_count }}</span>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('settings.calea.secciones.store', $version->id) }}" method="POST" class="mb-4">
                        @csrf

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="numero">Número <span class="text-danger">*</span></label>
                                    <input type="number" name="numero" id="numero" class="form-control" value="{{ old('numero') }}" min="0" max="65535" placeholder="Ej. 1" required>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="tipo">Tipo <span class="text-danger">*</span></label>
                                    <select name="tipo" id="tipo" class="form-control" required>
                                        <option value="">Seleccionar</option>
                                        @foreach (\App\Models\CaleaDirectivaSeccion::TIPOS as $valor => $nombre)
                                            <option value="{{ $valor }}" {{ old('tipo') === $valor ? 'selected' : '' }}>{{ $nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-group w-100">
                                    @can('editar calea')
                                        <button type="submit" class="btn btn-warning btn-block">
                                            <i class="fa-solid fa-plus"></i> Agregar Sección
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="contenido">Contenido</label>
                            <textarea name="contenido" id="contenido" rows="4" class="form-control">{{ old('contenido') }}</textarea>
                        </div>

                    </form>

                    <div class="table-responsive">
                    <table id="secciones" class="table table-striped table-bordered table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Tipo</th>
                                <th>Bloques</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($version->secciones as $seccion)
                                <tr>
                                    <td>{{ $seccion->numero ?? '—' }}</td>
                                    <td>{{ \App\Models\CaleaDirectivaSeccion::TIPOS[$seccion->tipo] ?? ($seccion->tipo ?? '—') }}</td>
                                    <td>{{ $seccion->bloques->count() }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @can('editar calea')
                                                <a href="{{ route('settings.calea.secciones.edit', $seccion->id) }}" class="btn btn-success btn-sm" title="Editar sección">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                            @endcan

                                            @can('eliminar calea')
                                                <form action="{{ route('settings.calea.secciones.destroy', $seccion->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-danger btn-sm eliminar-seccion-btn" title="Eliminar sección">
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

                    @if ($version->secciones->isEmpty())
                        <div class="alert alert-warning mt-3 mb-0">
                            Esta versión todavía no tiene secciones registradas.
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

        #secciones th, #secciones td {
            text-align: center;
            vertical-align: middle;
        }

        #secciones td.text-left {
            text-align: left !important;
        }

        .badge {
            font-size: .85rem;
            padding: .4rem .55rem;
        }

        .calea-version-page {
            color: #e5e7eb;
        }

        .calea-version-page .form-group label {
            color: #e5e7eb;
            font-weight: 700;
        }

        .calea-version-page .form-control,
        .calea-version-page .custom-file-label {
            background: rgba(15, 23, 42, .75) !important;
            color: #fff !important;
            border: 1px solid rgba(255, 255, 255, .14) !important;
            border-radius: 18px !important;
            box-shadow: none !important;
        }

        .calea-version-page .custom-file-label::after {
            color: #fff;
            background: rgba(59, 130, 246, .22);
            border-color: rgba(255, 255, 255, .14);
            border-radius: 0 18px 18px 0;
        }

        .calea-version-page .form-control:focus {
            background: rgba(15, 23, 42, .92) !important;
            color: #fff !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 .2rem rgba(59, 130, 246, .25) !important;
        }

        .calea-version-page .form-control::placeholder {
            color: rgba(255, 255, 255, .65) !important;
        }

        .calea-version-page select.form-control option {
            background: #0f172a;
            color: #fff;
        }

        .calea-version-page .card {
            background: linear-gradient(135deg, rgba(30, 41, 59, .95), rgba(49, 46, 129, .92)) !important;
            border: 1px solid rgba(255, 255, 255, .08) !important;
            border-radius: 24px !important;
            overflow: hidden;
        }

        .calea-version-page .card-header,
        .calea-version-page .card-footer {
            background: rgba(255, 255, 255, .04) !important;
            border-color: rgba(255, 255, 255, .06) !important;
        }

        .calea-version-page .card-title,
        .calea-version-page h5,
        .calea-version-page strong,
        .calea-version-page .custom-control-label {
            color: #fff;
        }

        .calea-version-page .text-muted {
            color: rgba(255, 255, 255, .68) !important;
        }

        .calea-version-page .table {
            color: #f8fafc;
            background: rgba(15, 23, 42, .42);
        }

        .calea-version-page .table thead th {
            color: #fff;
            background: rgba(59, 130, 246, .16);
            border-color: rgba(255, 255, 255, .14);
        }

        .calea-version-page .table td {
            border-color: rgba(255, 255, 255, .10);
        }

        .calea-version-page .table-striped tbody tr:nth-of-type(odd) {
            background: rgba(255, 255, 255, .035);
        }

        .calea-version-page .table-hover tbody tr:hover {
            color: #fff;
            background: rgba(59, 130, 246, .14);
        }

        .calea-version-page .btn {
            border-radius: 16px;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            $('#archivo').on('change', function () {
                let nombre = $(this).val().split('\\').pop();

                if (!nombre) {
                    nombre = 'Seleccionar nuevo PDF...';
                }

                $(this).next('.custom-file-label').html(nombre);
            });

            $('#fecha_emision').on('change', function () {
                if (!this.value) {
                    return;
                }

                const partes = this.value.split('-');

                if (partes.length === 3) {
                    $('#anio_emision').val(parseInt(partes[0]));
                    $('#mes_emision').val(parseInt(partes[1]));
                }
            });

            $('#formVersion').on('submit', function () {
                const boton = $('#guardarVersion');

                if (boton.prop('disabled')) {
                    return false;
                }

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

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Ocurrió un problema',
                html: @json($errors->first()),
                confirmButtonText: 'Aceptar'
            });
        @endif

        $(document).on('click', '.eliminar-seccion-btn', function (e) {
            e.preventDefault();

            let form = $(this).closest('form');

            Swal.fire({
                title: '¿Eliminar esta sección?',
                text: 'Se eliminarán también todos los bloques asociados a esta sección.',
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
