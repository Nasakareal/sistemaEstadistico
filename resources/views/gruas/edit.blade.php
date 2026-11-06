@extends('adminlte::page')

@section('title', 'Editar Grúa')

@section('content_header')
    <h1>Editar Grúa</h1>
@stop

@section('content')
    @php
        $usuario = auth()->user();
        $esSuperadmin = $usuario && $usuario->hasRole('Superadmin');
        $unidadId = (int) ($usuario->unidad_id ?? 0);

        $puedeVerSiniestros = $esSuperadmin || $unidadId === 1 || $unidadId === 3;
        $puedeVerDelegaciones = $esSuperadmin || $unidadId === 2 || $unidadId === 3;

        $unidadesSeleccionadas = old('unidades', $grua->unidades->pluck('id')->map(fn($id) => (int) $id)->toArray());
        $delegacionesSeleccionadas = old('delegaciones', $grua->delegaciones->pluck('id')->map(fn($id) => (int) $id)->toArray());
        $ubicacionSiniestrosActual = optional(optional($grua->unidades->firstWhere('id', 1))->pivot)->ubicacion_corralon;
        $ubicacionesDelegacionesActuales = $grua->delegaciones->mapWithKeys(fn($delegacion) => [
            $delegacion->id => optional($delegacion->pivot)->ubicacion_corralon,
        ])->all();
        $ubicacionesDelegaciones = old('ubicaciones_delegaciones', $ubicacionesDelegacionesActuales);
    @endphp

    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Actualizar Datos de la Grúa</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('gruas.update', $grua->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nombre">Nombre de la Grúa <span class="text-danger">*</span></label>
                                    <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre', $grua->nombre) }}"
                                           placeholder="Ingrese el nombre de la grúa" required>
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
                                           value="{{ old('direccion', $grua->direccion) }}"
                                           placeholder="Ingrese la dirección">
                                    @error('direccion')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="ubicacion_corralon">Ubicación general (respaldo)</label>
                                    <input type="text" name="ubicacion_corralon" id="ubicacion_corralon" class="form-control @error('ubicacion_corralon') is-invalid @enderror"
                                           value="{{ old('ubicacion_corralon', $grua->ubicacion_corralon) }}"
                                           placeholder="Se usa sólo si una asignación no tiene dirección propia">
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
                                           value="{{ old('telefono', $grua->telefono) }}"
                                           placeholder="Ingrese el teléfono">
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
                                           value="{{ old('email', $grua->email) }}"
                                           placeholder="Ingrese el correo electrónico">
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

                            @if($puedeVerSiniestros)
                                <div class="col-md-12">
                                    <div class="form-group mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input class="custom-control-input" type="checkbox" id="unidad_siniestros" name="unidades[]" value="1"
                                                {{ in_array(1, $unidadesSeleccionadas) ? 'checked' : '' }}>
                                            <label for="unidad_siniestros" class="custom-control-label">Siniestros</label>
                                        </div>
                                        <div id="ubicacion_siniestros_container" class="mt-2" style="display: none;">
                                            <label for="ubicacion_siniestros">Dirección del corralón para Siniestros / Morelia</label>
                                            <input type="text" id="ubicacion_siniestros" name="ubicaciones_unidades[1]"
                                                class="form-control @error('ubicaciones_unidades.1') is-invalid @enderror"
                                                value="{{ old('ubicaciones_unidades.1', $ubicacionSiniestrosActual) }}"
                                                placeholder="Dirección que se imprimirá para servicios de Siniestros">
                                            @error('ubicaciones_unidades.1')
                                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($puedeVerDelegaciones)
                                <div class="col-md-12" id="contenedor_delegaciones">
                                    <div class="form-group">
                                        <label for="delegaciones">Delegaciones</label>
                                        <select name="delegaciones[]" id="delegaciones" class="form-control select2 @error('delegaciones') is-invalid @enderror @error('delegaciones.*') is-invalid @enderror" multiple>
                                            @foreach($delegaciones as $delegacion)
                                                <option value="{{ $delegacion->id }}" {{ in_array($delegacion->id, $delegacionesSeleccionadas) ? 'selected' : '' }}>
                                                    {{ $delegacion->nombre }}{{ !empty($delegacion->clave) ? ' ('.$delegacion->clave.')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted">Puede seleccionar una o varias delegaciones.</small>
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
                                    <div id="ubicaciones_delegaciones">
                                        @foreach($delegaciones as $delegacion)
                                            @php $seleccionada = in_array((int) $delegacion->id, $delegacionesSeleccionadas, true); @endphp
                                            <div class="form-group ubicacion-delegacion" data-delegacion-id="{{ $delegacion->id }}" style="display: {{ $seleccionada ? 'block' : 'none' }};">
                                                <label for="ubicacion_delegacion_{{ $delegacion->id }}">Dirección del corralón para {{ $delegacion->nombre }}</label>
                                                <input type="text" id="ubicacion_delegacion_{{ $delegacion->id }}"
                                                    name="ubicaciones_delegaciones[{{ $delegacion->id }}]"
                                                    class="form-control @error('ubicaciones_delegaciones.'.$delegacion->id) is-invalid @enderror"
                                                    value="{{ $ubicacionesDelegaciones[$delegacion->id] ?? '' }}"
                                                    placeholder="Dirección que se imprimirá para esta delegación"
                                                    {{ $seleccionada ? '' : 'disabled' }}>
                                                @error('ubicaciones_delegaciones.'.$delegacion->id)
                                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @error('unidades')
                                <div class="col-md-12">
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                </div>
                            @enderror
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12 text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-check"></i> Guardar Cambios
                                </button>
                                <a href="{{ route('gruas.index') }}" class="btn btn-secondary">
                                    <i class="fa-solid fa-ban"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    @include('gruas._styles')
@stop

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            @if($puedeVerDelegaciones)
                $('#delegaciones').select2({
                    placeholder: 'Seleccione una o más delegaciones',
                    allowClear: true,
                    width: '100%'
                });

                function actualizarUbicacionesDelegaciones() {
                    const seleccionadas = ($('#delegaciones').val() || []).map(String);
                    $('.ubicacion-delegacion').each(function () {
                        const visible = seleccionadas.includes(String($(this).data('delegacion-id')));
                        $(this).toggle(visible).find('input').prop('disabled', !visible);
                    });
                }

                $('#delegaciones').on('change', actualizarUbicacionesDelegaciones);
                actualizarUbicacionesDelegaciones();
            @endif

            @if($puedeVerSiniestros)
                function actualizarUbicacionSiniestros() {
                    const activo = $('#unidad_siniestros').is(':checked');
                    $('#ubicacion_siniestros_container').toggle(activo);
                    $('#ubicacion_siniestros').prop('disabled', !activo);
                }
                $('#unidad_siniestros').on('change', actualizarUbicacionSiniestros);
                actualizarUbicacionSiniestros();
            @endif
        });

        @if (session('success'))
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 1500
            });
        @endif

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Error en el formulario',
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
