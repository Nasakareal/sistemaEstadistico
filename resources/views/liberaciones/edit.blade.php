@extends('adminlte::page')

@section('title', 'Editar Liberación')

@section('content_header')
    <h1>Editar Liberación del Vehículo</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">Actualizar Datos de Liberación</h3>
            </div>

            <div class="card-body">

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
                                    class="form-control @error('motivo_liberacion') is-invalid @enderror" required>

                                    @php
                                        $motivoSel = old('motivo_liberacion', $liberacion->motivo_liberacion);
                                    @endphp

                                    <option value="">Seleccione una opción</option>
                                    <option value="Convenio entre particulares" {{ $motivoSel == 'Convenio entre particulares' ? 'selected' : '' }}>
                                        Convenio, entregó documentación
                                    </option>
                                    <option value="Error de detención" {{ $motivoSel == 'Error de detención' ? 'selected' : '' }}>
                                        Error de detención
                                    </option>
                                    <option value="Acreditó propiedad" {{ $motivoSel == 'Acreditó propiedad' ? 'selected' : '' }}>
                                        Acreditó propiedad
                                    </option>
                                    <option value="Orden del Ministerio Público" {{ $motivoSel == 'Orden del Ministerio Público' ? 'selected' : '' }}>
                                        Orden del Ministerio Público
                                    </option>
                                    <option value="Otro" {{ $motivoSel == 'Otro' ? 'selected' : '' }}>
                                        Otro
                                    </option>
                                </select>

                                @error('motivo_liberacion')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
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
                                    <option value="">Seleccione un comandante</option>
                                    <option value="POL. 3° JORGE ARMANDO MORALES PÉREZ" {{ $autorizaSel == 'POL. 3° JORGE ARMANDO MORALES PÉREZ' ? 'selected' : '' }}>
                                        POL. 3° JORGE ARMANDO MORALES PÉREZ
                                    </option>
                                    <option value="OFICIAL FERNANDO RUBALCAVA RIVERA" {{ $autorizaSel == 'OFICIAL FERNANDO RUBALCAVA RIVERA' ? 'selected' : '' }}>
                                        OFICIAL FERNANDO RUBALCAVA RIVERA
                                    </option>
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
<style>
    select.form-control {
        background-color: #1f2937 !important;
        color: #ffffff !important;
        border: 1px solid #4b5563 !important;
        font-weight: 600;
    }

    select.form-control option {
        background-color: #111827 !important;
        color: #ffffff !important;
        font-weight: 500;
    }

    select.form-control option:checked {
        background-color: #2563eb !important;
        color: white !important;
    }

    select.form-control option:hover {
        background-color: #374151 !important;
        color: white !important;
    }


    input.form-control,
    textarea.form-control {
        background-color: #1f2937 !important;
        color: #ffffff !important;
        border: 1px solid #4b5563 !important;
    }

    input.form-control::placeholder,
    textarea.form-control::placeholder {
        color: #9ca3af !important;
    }

    .btn-warning {
        background-color: #facc15 !important;
        border: none !important;
        color: #000 !important;
        font-weight: bold;
    }

    .btn-warning:hover {
        background-color: #eab308 !important;
        transform: scale(1.03);
    }

    .btn-secondary {
        font-weight: bold;
    }

    .btn-danger {
        font-weight: bold;
    }

    label {
        color: #ffffff !important;
        font-weight: bold;
    }

</style>
@stop

@section('js')
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
