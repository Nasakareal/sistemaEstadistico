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
                <form action="{{ route('liberacion.store', $vehiculo->id) }}" method="POST">
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
                                <select name="motivo_liberacion" id="motivo_liberacion"
                                    class="form-control @error('motivo_liberacion') is-invalid @enderror" required>
                                    <option value="">Seleccione una opción</option>
                                    <option value="Convenio entre particulares" {{ old('Convenio entre particulares') == 'Convenio entre particulares' ? 'selected' : '' }}>Convenio, entregó documentación</option>
                                    <option value="Error de detención" {{ old('motivo_liberacion') == 'Error de detención' ? 'selected' : '' }}>Error de detención</option>
                                    <option value="Acreditó propiedad" {{ old('motivo_liberacion') == 'Acreditó propiedad' ? 'selected' : '' }}>Acreditó propiedad</option>
                                    <option value="Orden del Ministerio Público" {{ old('motivo_liberacion') == 'Orden del Ministerio Público' ? 'selected' : '' }}>Orden del Ministerio Público</option>
                                    <option value="Otro" {{ old('motivo_liberacion') == 'Otro' ? 'selected' : '' }}>Otro</option>
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
                                <select name="autoriza" id="autoriza"
                                    class="form-control @error('autoriza') is-invalid @enderror" required>
                                    <option value="">Seleccione un comandante</option>
                                    <option value="POL. 3° JORGE ARMANDO MORALES PÉREZ" {{ old('autoriza') == 'POL. 3° JORGE ARMANDO MORALES PÉREZ' ? 'selected' : '' }}>POL. 3° JORGE ARMANDO MORALES PÉREZ</option>
                                    <option value="OFICIAL FERNANDO RUBALCAVA RIVERA" {{ old('autoriza') == 'OFICIAL FERNANDO RUBALCAVA RIVERA' ? 'selected' : '' }}>OFICIAL FERNANDO RUBALCAVA RIVERA</option>
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
    <style>
        .form-group label {
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
