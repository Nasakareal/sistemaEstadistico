@extends('adminlte::page')

@section('title', 'Registrar Grúa')

@section('content_header')
    <h1>Registro de una Nueva Grúa</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('gruas.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nombre">Nombre de la Grúa <span class="text-danger">*</span></label>
                                    <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre') }}" placeholder="Ingrese el nombre de la grúa" required>
                                    @error('nombre')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="direccion">Dirección</label>
                                    <input type="text" name="direccion" id="direccion" class="form-control @error('direccion') is-invalid @enderror"
                                           value="{{ old('direccion') }}" placeholder="Ingrese la dirección">
                                    @error('direccion')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="ubicacion_corralon">Ubicación del corralón</label>
                                    <input type="text" name="ubicacion_corralon" id="ubicacion_corralon" class="form-control @error('ubicacion_corralon') is-invalid @enderror"
                                           value="{{ old('ubicacion_corralon') }}" placeholder="Ingrese la ubicación del corralón">
                                    @error('ubicacion_corralon')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="telefono">Teléfono</label>
                                    <input type="text" name="telefono" id="telefono" class="form-control @error('telefono') is-invalid @enderror"
                                           value="{{ old('telefono') }}" placeholder="Ingrese el teléfono">
                                    @error('telefono')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email') }}" placeholder="Ingrese el correo electrónico">
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="mb-3">Asignación <span class="text-danger">*</span></h5>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input class="custom-control-input" type="radio" id="tipo_asignacion_siniestros" name="tipo_asignacion" value="siniestros" {{ old('tipo_asignacion') === 'siniestros' ? 'checked' : '' }} required>
                                        <label for="tipo_asignacion_siniestros" class="custom-control-label">Siniestros</label>
                                    </div>

                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input class="custom-control-input" type="radio" id="tipo_asignacion_delegaciones" name="tipo_asignacion" value="delegaciones" {{ old('tipo_asignacion') === 'delegaciones' ? 'checked' : '' }} required>
                                        <label for="tipo_asignacion_delegaciones" class="custom-control-label">Delegaciones</label>
                                    </div>

                                    @error('tipo_asignacion')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    @error('asignacion')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12" id="contenedor_delegaciones" style="{{ old('tipo_asignacion') === 'delegaciones' ? '' : 'display:none;' }}">
                                <div class="form-group">
                                    <label for="delegaciones">Delegaciones <span class="text-danger" id="delegaciones_required" style="{{ old('tipo_asignacion') === 'delegaciones' ? '' : 'display:none;' }}">*</span></label>
                                    <select name="delegaciones[]" id="delegaciones" class="form-control select2 @error('delegaciones') is-invalid @enderror @error('delegaciones.*') is-invalid @enderror" multiple>
                                        @foreach($delegaciones as $delegacion)
                                            <option value="{{ $delegacion->id }}" {{ in_array($delegacion->id, old('delegaciones', [])) ? 'selected' : '' }}>
                                                {{ $delegacion->nombre }}{{ !empty($delegacion->clave) ? ' ('.$delegacion->clave.')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('delegaciones')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    @error('delegaciones.*')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="unidad_siniestros_id" value="1">

                        <hr>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-check"></i> Registrar
                                    </button>
                                    <a href="{{ route('gruas.index') }}" class="btn btn-secondary">
                                        <i class="fa-solid fa-ban"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .form-group label {
            font-weight: bold;
        }

        .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--multiple {
            min-height: 38px;
            border: 1px solid #ced4da;
            border-radius: .25rem;
            padding: 2px 6px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #007bff;
            border: 1px solid #007bff;
            color: #fff;
            border-radius: 4px;
            padding: 2px 8px;
            margin-top: 4px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff;
            margin-right: 6px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
@stop

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#delegaciones').select2({
                placeholder: 'Seleccione una o más delegaciones',
                allowClear: true,
                width: '100%'
            });

            function toggleDelegaciones() {
                const esDelegaciones = $('#tipo_asignacion_delegaciones').is(':checked');

                if (esDelegaciones) {
                    $('#contenedor_delegaciones').slideDown(150);
                    $('#delegaciones').prop('required', true);
                    $('#delegaciones_required').show();
                } else {
                    $('#contenedor_delegaciones').slideUp(150);
                    $('#delegaciones').prop('required', false).val(null).trigger('change');
                    $('#delegaciones_required').hide();
                }
            }

            $('input[name="tipo_asignacion"]').on('change', toggleDelegaciones);
            toggleDelegaciones();
        });

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Errores en el formulario',
                html: `
                    <ul style="text-align: left;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                `,
                confirmButtonText: 'Aceptar'
            });
        @endif
    </script>
@stop
