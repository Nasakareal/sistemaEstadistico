@extends('adminlte::page')

@section('title', 'Registrar Liberación')

@section('content_header')
    <h1>Registrar Liberación del Vehículo</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Datos de Liberación</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('liberacion.store', $vehiculo->id) }}" method="POST" class="liberacion-form">
                    @csrf

                    <div class="row">
                        <!-- Fecha de Liberación -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="fecha_liberacion">Fecha de Liberación</label>
                                <input type="date" name="fecha_liberacion" id="fecha_liberacion"
                                    class="form-control" value="{{ $fechaActual }}" readonly>
                            </div>
                        </div>

                        <!-- Personas Autorizadas -->
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="personas_autorizadas">Personas Autorizadas para Recoger</label>
                                <input type="text" name="personas_autorizadas" id="personas_autorizadas"
                                    class="form-control @error('personas_autorizadas') is-invalid @enderror"
                                    value="{{ old('personas_autorizadas') }}"
                                    placeholder="Nombre completo de las personas autorizadas" required>
                                @error('personas_autorizadas')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Motivo de Liberación -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="motivo_liberacion">Motivo de Liberación</label>
                                @php
                                    $motivoSel = old('motivo_liberacion');
                                    $motivoOtro = old('motivo_liberacion_otro');
                                @endphp

                                <select name="motivo_liberacion" id="motivo_liberacion"
                                    class="form-control @error('motivo_liberacion') is-invalid @enderror"
                                    data-motivo-liberacion="motivo_liberacion_otro_wrap"
                                    required>
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
                                <select name="autoriza" id="autoriza"
                                    class="form-control @error('autoriza') is-invalid @enderror" required>
                                    <option value="">{{ $autorizaPlaceholder ?? 'Seleccione una opción' }}</option>
                                    @foreach(($autorizaOptions ?? []) as $autorizaOption)
                                        <option value="{{ $autorizaOption }}" {{ old('autoriza') == $autorizaOption ? 'selected' : '' }}>
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

                    <hr>

                    <!-- Botones -->
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-check"></i> Registrar Liberación
                            </button>
                            <a href="{{ url()->previous() }}" class="btn btn-secondary">
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
