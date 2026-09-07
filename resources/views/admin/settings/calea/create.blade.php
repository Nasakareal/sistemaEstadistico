@extends('adminlte::page')

@section('title', 'Nueva Directiva CALEA')

@section('content_header')
    <h1>Nueva Directiva CALEA</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Registrar Directiva</h3>
                </div>
                <form action="{{ route('settings.calea.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>No fue posible guardar la directiva.</strong>
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
                                    <input type="text" name="codigo" id="codigo" class="form-control @error('codigo') is-invalid @enderror" value="{{ old('codigo') }}" placeholder="Ej. 1.2.5" maxlength="30" autocomplete="off" required>
                                    @error('codigo')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="form-text text-muted">Formato: 1.2.5, 40.1.2, 41.2.3, etc.</small>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label for="titulo">Título <span class="text-danger">*</span></label>
                                    <input type="text" name="titulo" id="titulo" class="form-control @error('titulo') is-invalid @enderror" value="{{ old('titulo') }}" placeholder="Ej. Arresto con Orden y sin Orden" maxlength="500" required>
                                    @error('titulo')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="categoria">Categoría</label>
                            <input type="text" name="categoria" id="categoria" class="form-control @error('categoria') is-invalid @enderror" value="{{ old('categoria') }}" list="categoriasCalea" placeholder="Ej. APLICACIÓN DE LA LEY POLICÍA" maxlength="255" autocomplete="off">
                            <datalist id="categoriasCalea">
                                @foreach ($categorias as $categoria)
                                    <option value="{{ $categoria }}">
                                @endforeach
                            </datalist>
                            @error('categoria')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                            <small class="form-text text-muted">Puedes seleccionar una categoría existente o escribir una nueva.</small>
                        </div>
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea name="descripcion" id="descripcion" rows="5" class="form-control @error('descripcion') is-invalid @enderror" placeholder="Descripción general de la directiva, si aplica.">{{ old('descripcion') }}</textarea>
                            @error('descripcion')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="form-group mb-0">
                            <input type="hidden" name="activo" value="0">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="activo" name="activo" value="1" {{ old('activo', '1') == '1' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="activo">Directiva activa</label>
                            </div>
                            <small class="form-text text-muted">Una directiva inactiva no aparecerá en el módulo normal de consulta.</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('settings.calea.index') }}" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Regresar
                        </a>
                        <button type="submit" class="btn btn-primary float-right" id="guardarDirectiva">
                            <i class="fa-regular fa-floppy-disk"></i> Guardar Directiva
                        </button>
                    </div>
                </form>
            </div>
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">¿Qué se registra aquí?</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">En esta pantalla se registra únicamente la identidad del estándar CALEA.</p>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Ejemplo:</strong>
                            <table class="table table-sm table-bordered mt-2 mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width: 35%">Estándar</th>
                                        <td>1.2.5</td>
                                    </tr>
                                    <tr>
                                        <th>Título</th>
                                        <td>Arresto con Orden y sin Orden</td>
                                    </tr>
                                    <tr>
                                        <th>Categoría</th>
                                        <td>APLICACIÓN DE LA LEY POLICÍA</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <strong>La versión se registra después:</strong>
                            <ul class="mt-2 mb-0">
                                <li>PDF original</li>
                                <li>Número de versión</li>
                                <li>Fecha de emisión</li>
                                <li>Fecha de revisión</li>
                                <li>Área responsable</li>
                                <li>Autoriza</li>
                                <li>Realizado por</li>
                                <li>Contenido y secciones</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
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

            $('form').on('submit', function () {
                const boton = $('#guardarDirectiva');

                if (boton.prop('disabled')) {
                    return false;
                }

                boton.prop('disabled', true);
                boton.html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...');
            });
        });
    </script>
@stop
