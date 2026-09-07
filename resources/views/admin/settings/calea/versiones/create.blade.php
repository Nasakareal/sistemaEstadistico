@extends('adminlte::page')

@section('title', 'Nueva Versión CALEA')

@section('content_header')
    <h1>Nueva Versión CALEA</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <strong>{{ $directiva->codigo }}</strong> — {{ $directiva->titulo }}
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Estándar:</strong><br>
                            {{ $directiva->codigo }}
                        </div>
                        <div class="col-md-4">
                            <strong>Categoría:</strong><br>
                            {{ $directiva->categoria ?? '—' }}
                        </div>
                        <div class="col-md-4">
                            <strong>Versiones registradas:</strong><br>
                            {{ $directiva->versiones()->count() }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Registrar Versión</h3>
                </div>

                <form action="{{ route('settings.calea.versiones.store', $directiva->id) }}" method="POST" enctype="multipart/form-data" id="formVersion">
                    @csrf

                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>No fue posible guardar la versión.</strong>
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
                                    <input type="number" name="numero_version" id="numero_version" class="form-control @error('numero_version') is-invalid @enderror" value="{{ old('numero_version', $siguienteNumero) }}" min="1" max="65535" required>
                                    @error('numero_version')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="nombre_version">Nombre de versión</label>
                                    <input type="text" name="nombre_version" id="nombre_version" class="form-control @error('nombre_version') is-invalid @enderror" value="{{ old('nombre_version', $siguienteNumero . 'da Versión') }}" maxlength="100" placeholder="Ej. 2da Versión">
                                    @error('nombre_version')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fecha_revision">Fecha de revisión</label>
                                    <input type="date" name="fecha_revision" id="fecha_revision" class="form-control @error('fecha_revision') is-invalid @enderror" value="{{ old('fecha_revision') }}">
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
                                    <input type="date" name="fecha_emision" id="fecha_emision" class="form-control @error('fecha_emision') is-invalid @enderror" value="{{ old('fecha_emision') }}">
                                    @error('fecha_emision')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="form-text text-muted">Úsala solamente si el documento indica día, mes y año.</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="mes_emision">Mes de emisión</label>
                                    <select name="mes_emision" id="mes_emision" class="form-control @error('mes_emision') is-invalid @enderror">
                                        <option value="">Seleccionar</option>
                                        <option value="1" {{ old('mes_emision') == '1' ? 'selected' : '' }}>Enero</option>
                                        <option value="2" {{ old('mes_emision') == '2' ? 'selected' : '' }}>Febrero</option>
                                        <option value="3" {{ old('mes_emision') == '3' ? 'selected' : '' }}>Marzo</option>
                                        <option value="4" {{ old('mes_emision') == '4' ? 'selected' : '' }}>Abril</option>
                                        <option value="5" {{ old('mes_emision') == '5' ? 'selected' : '' }}>Mayo</option>
                                        <option value="6" {{ old('mes_emision') == '6' ? 'selected' : '' }}>Junio</option>
                                        <option value="7" {{ old('mes_emision') == '7' ? 'selected' : '' }}>Julio</option>
                                        <option value="8" {{ old('mes_emision') == '8' ? 'selected' : '' }}>Agosto</option>
                                        <option value="9" {{ old('mes_emision') == '9' ? 'selected' : '' }}>Septiembre</option>
                                        <option value="10" {{ old('mes_emision') == '10' ? 'selected' : '' }}>Octubre</option>
                                        <option value="11" {{ old('mes_emision') == '11' ? 'selected' : '' }}>Noviembre</option>
                                        <option value="12" {{ old('mes_emision') == '12' ? 'selected' : '' }}>Diciembre</option>
                                    </select>
                                    @error('mes_emision')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="anio_emision">Año de emisión</label>
                                    <input type="number" name="anio_emision" id="anio_emision" class="form-control @error('anio_emision') is-invalid @enderror" value="{{ old('anio_emision') }}" min="1900" max="2100" placeholder="Ej. 2021">
                                    @error('anio_emision')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="form-text text-muted">Para documentos que indiquen algo como "julio 2021".</small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3">
                            <i class="fa-solid fa-building-shield"></i> Datos del documento
                        </h5>

                        <div class="form-group">
                            <label for="area_responsable">Área responsable</label>
                            <textarea name="area_responsable" id="area_responsable" rows="2" class="form-control @error('area_responsable') is-invalid @enderror" placeholder="Ej. Subsecretaría de Operación Policial">{{ old('area_responsable') }}</textarea>
                            @error('area_responsable')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="autoriza">Autoriza</label>
                            <textarea name="autoriza" id="autoriza" rows="3" class="form-control @error('autoriza') is-invalid @enderror" placeholder="Nombre, grado, cargo o puesto de quien autoriza">{{ old('autoriza') }}</textarea>
                            @error('autoriza')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="realizado_por">Realizado por</label>
                            <textarea name="realizado_por" id="realizado_por" rows="3" class="form-control @error('realizado_por') is-invalid @enderror" placeholder="Personas responsables de la elaboración del documento">{{ old('realizado_por') }}</textarea>
                            @error('realizado_por')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="leyenda_documento">Leyenda del documento</label>
                            <textarea name="leyenda_documento" id="leyenda_documento" rows="3" class="form-control @error('leyenda_documento') is-invalid @enderror" placeholder="Ej. Documento propiedad de la Secretaría de Seguridad Pública...">{{ old('leyenda_documento', 'Documento propiedad de la Secretaría de Seguridad Pública del Estado de Michoacán. PROHIBIDA SU REPRODUCCIÓN TOTAL O PARCIAL SIN AUTORIZACIÓN DE SSP') }}</textarea>
                            @error('leyenda_documento')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <hr>

                        <h5 class="mb-3">
                            <i class="fa-regular fa-file-pdf"></i> Documento original
                        </h5>

                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label for="archivo">Archivo PDF <span class="text-danger">*</span></label>
                                    <div class="custom-file">
                                        <input type="file" name="archivo" id="archivo" class="custom-file-input @error('archivo') is-invalid @enderror" accept=".pdf,application/pdf" required>
                                        <label class="custom-file-label" for="archivo">Seleccionar PDF...</label>
                                    </div>
                                    @error('archivo')
                                        <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="form-text text-muted">Solo archivos PDF. Tamaño máximo: 50 MB.</small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="numero_paginas">Número de páginas</label>
                                    <input type="number" name="numero_paginas" id="numero_paginas" class="form-control @error('numero_paginas') is-invalid @enderror" value="{{ old('numero_paginas') }}" min="1" max="10000" placeholder="Ej. 2">
                                    @error('numero_paginas')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="texto_busqueda">Texto completo para búsqueda</label>
                            <textarea name="texto_busqueda" id="texto_busqueda" rows="10" class="form-control @error('texto_busqueda') is-invalid @enderror" placeholder="Puedes pegar aquí el texto completo extraído del documento para permitir búsquedas rápidas por palabras, procedimientos, fundamentos, conceptos, etc.">{{ old('texto_busqueda') }}</textarea>
                            @error('texto_busqueda')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                            <small class="form-text text-muted">Este texto no sustituye al PDF original. Se utilizará para el buscador del módulo CALEA.</small>
                        </div>

                        <hr>

                        <h5 class="mb-3">
                            <i class="fa-solid fa-sliders"></i> Estado de la versión
                        </h5>

                        <div class="row">
                            <div class="col-md-6">
                                <input type="hidden" name="vigente" value="0">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="vigente" name="vigente" value="1" {{ old('vigente', '1') == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="vigente">Marcar como versión vigente</label>
                                </div>
                                <small class="form-text text-muted">Al activarla, cualquier otra versión vigente de esta directiva dejará de serlo.</small>
                            </div>

                            <div class="col-md-6">
                                <input type="hidden" name="publicada" value="0">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="publicada" name="publicada" value="1" {{ old('publicada', '1') == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="publicada">Versión publicada</label>
                                </div>
                                <small class="form-text text-muted">Las versiones no publicadas no aparecerán en el apartado normal de consulta.</small>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="{{ route('settings.calea.edit', $directiva->id) }}" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Regresar
                        </a>

                        <button type="submit" class="btn btn-primary float-right" id="guardarVersion">
                            <i class="fa-regular fa-floppy-disk"></i> Guardar Versión
                        </button>
                    </div>
                </form>
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

        .card-body h5 {
            font-weight: 600;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            $('#archivo').on('change', function () {
                let nombre = $(this).val().split('\\').pop();

                if (!nombre) {
                    nombre = 'Seleccionar PDF...';
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

            $('#numero_version').on('input', function () {
                const numero = parseInt($(this).val());

                if (!numero || $('#nombre_version').data('editado') === true) {
                    return;
                }

                let sufijo = 'a';

                if (numero === 1) {
                    sufijo = 'ra';
                } else if (numero === 2) {
                    sufijo = 'da';
                } else if (numero === 3) {
                    sufijo = 'ra';
                } else {
                    sufijo = 'ta';
                }

                $('#nombre_version').val(numero + sufijo + ' Versión');
            });

            $('#nombre_version').on('input', function () {
                $(this).data('editado', true);
            });

            $('#formVersion').on('submit', function () {
                const boton = $('#guardarVersion');

                if (boton.prop('disabled')) {
                    return false;
                }

                boton.prop('disabled', true);
                boton.html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando versión...');
            });
        });
    </script>
@stop
