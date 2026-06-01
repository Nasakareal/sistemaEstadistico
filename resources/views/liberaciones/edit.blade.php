@extends('adminlte::page')

@section('title', 'Editar Liberación')

@section('content_header')
    <h1>Editar Liberación del Vehículo</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-warning liberacion-card">
            <div class="card-header">
                <h3 class="card-title">Actualizar Datos de Liberación</h3>
            </div>

            <div class="card-body liberacion-form">

                {{-- Mensaje informativo --}}
                <div class="alert alert-info">
                    <i class="fa-solid fa-circle-info"></i>
                    Al guardar cambios, podrás volver a descargar el acuse con los datos actualizados.
                    El QR y el folio no cambian.
                </div>

                {{-- Datos del vehículo (solo lectura) --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Vehículo</label>
                            <input type="text" class="form-control" readonly
                                   value="{{ $vehiculo->marca }} - {{ $vehiculo->modelo }} - {{ $vehiculo->placas }}">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Corralón actual (del vehículo)</label>
                            <input type="text"
                                   class="form-control {{ $vehiculo->corralon ? '' : 'is-invalid' }}"
                                   readonly
                                   value="{{ $vehiculo->corralon ?? 'NO TIENE CORRALÓN REGISTRADO' }}">
                            @if(!$vehiculo->corralon)
                                <span class="text-danger" style="font-size: 12px;">
                                    * El corralón se corrige en el registro del vehículo, no en la liberación.
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <hr>

                <form action="{{ route('liberacion.update', $vehiculo->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <!-- Fecha de Liberación -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="fecha_liberacion">Fecha de Liberación</label>
                                <input type="date" name="fecha_liberacion" id="fecha_liberacion"
                                    class="form-control @error('fecha_liberacion') is-invalid @enderror"
                                    value="{{ old('fecha_liberacion', \Carbon\Carbon::parse($liberacion->fecha_liberacion)->format('Y-m-d')) }}"
                                    required>
                                @error('fecha_liberacion')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Personas Autorizadas -->
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="personas_autorizadas">Personas Autorizadas para Recoger</label>
                                <input type="text" name="personas_autorizadas" id="personas_autorizadas"
                                    class="form-control @error('personas_autorizadas') is-invalid @enderror"
                                    value="{{ old('personas_autorizadas', $liberacion->personas_autorizadas) }}"
                                    placeholder="Nombre completo de las personas autorizadas"
                                    required>
                                @error('personas_autorizadas')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Motivo de Liberación y Autoriza -->
                    <div class="row">
                        <!-- Motivo de Liberación -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="motivo_liberacion">Motivo de Liberación</label>
                                <select name="motivo_liberacion" id="motivo_liberacion"
                                    class="form-control @error('motivo_liberacion') is-invalid @enderror"
                                    data-motivo-liberacion="motivo_liberacion_otro_wrap"
                                    required>

                                    @php
                                        $motivosCatalogo = array_keys($motivosLiberacion ?? []);
                                        $motivoGuardado = trim((string) ($liberacion->motivo_liberacion ?? ''));
                                        $motivoGuardadoEsOtro = $motivoGuardado !== '' && !in_array($motivoGuardado, $motivosCatalogo, true);
                                        $motivoSel = old('motivo_liberacion', $motivoGuardadoEsOtro ? 'Otro' : $motivoGuardado);
                                        $motivoOtro = old('motivo_liberacion_otro', $motivoGuardadoEsOtro ? $motivoGuardado : '');
                                    @endphp

                                    <option value="">Seleccione una opción</option>
                                    @foreach(($motivosLiberacion ?? []) as $motivoValue => $motivoLabel)
                                        <option value="{{ $motivoValue }}" {{ $motivoSel == $motivoValue ? 'selected' : '' }}>
                                            {{ $motivoLabel }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('motivo_liberacion')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror

                                <div id="motivo_liberacion_otro_wrap" class="liberacion-otro-wrap {{ $motivoSel === 'Otro' ? '' : 'd-none' }}">
                                    <label for="motivo_liberacion_otro">Especificar motivo</label>
                                    <input type="text" name="motivo_liberacion_otro" id="motivo_liberacion_otro"
                                           class="form-control @error('motivo_liberacion_otro') is-invalid @enderror"
                                           value="{{ $motivoOtro }}"
                                           placeholder="Escribe el motivo de liberación"
                                           data-motivo-otro-input>
                                    <div class="liberacion-help">Este texto se guardará como motivo de liberación.</div>
                                    @error('motivo_liberacion_otro')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Autoriza -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="autoriza">Autoriza la Liberación</label>

                                @php
                                    $autorizaSel = old('autoriza', $liberacion->autoriza);
                                @endphp

                                <select name="autoriza" id="autoriza"
                                    class="form-control @error('autoriza') is-invalid @enderror" required>
                                    <option value="">{{ $autorizaPlaceholder ?? 'Seleccione una opción' }}</option>
                                    @foreach(($autorizaOptions ?? []) as $autorizaOption)
                                        <option value="{{ $autorizaOption }}" {{ $autorizaSel == $autorizaOption ? 'selected' : '' }}>
                                            {{ $autorizaOption }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('autoriza')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Observaciones (si existe el campo en BD) -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="observaciones">Observaciones</label>
                                <textarea name="observaciones" id="observaciones"
                                          rows="3"
                                          class="form-control @error('observaciones') is-invalid @enderror"
                                          placeholder="(Opcional)">{{ old('observaciones', $liberacion->observaciones) }}</textarea>
                                @error('observaciones')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Botones -->
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <button type="submit" class="btn btn-warning">
                                <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                            </button>

                            <a href="{{ route('liberacion.detalles', $vehiculo->id) }}" class="btn btn-secondary">
                                <i class="fa-solid fa-arrow-left"></i> Volver a Detalles
                            </a>

                            <a href="{{ route('liberacion.descargar', $vehiculo->id) }}" class="btn btn-danger">
                                <i class="fa-solid fa-file-pdf"></i> Descargar Acuse
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
    @include('liberaciones.partials.form_styles')
@stop

@section('js')
@include('liberaciones.partials.motivo_otro_script')

<script>
    @if ($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Errores en el formulario',
            html: `<ul style="text-align: left;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>`,
            confirmButtonText: 'Aceptar'
        });
    @endif
</script>
@stop
