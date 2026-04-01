@extends('adminlte::page')

@section('title', 'Editar Destacamento')

@section('content_header')
    <h1>Editar Destacamento</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Actualizar Datos</h3>

                    <div class="card-tools">
                        <a href="{{ route('destacamentos.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">

                    <form action="{{ route('destacamentos.update', $destacamento->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="clave">Clave</label>
                                    <input type="text"
                                           name="clave"
                                           id="clave"
                                           class="form-control @error('clave') is-invalid @enderror"
                                           value="{{ old('clave', $destacamento->clave) }}">

                                    @error('clave')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="nombre">Nombre del Destacamento</label>
                                    <input type="text"
                                           name="nombre"
                                           id="nombre"
                                           class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre', $destacamento->nombre) }}"
                                           required>

                                    @error('nombre')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="municipio">Municipio</label>
                                    <input type="text"
                                           name="municipio"
                                           id="municipio"
                                           class="form-control @error('municipio') is-invalid @enderror"
                                           value="{{ old('municipio', $destacamento->municipio) }}">

                                    @error('municipio')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="lat">Latitud</label>
                                    <input type="text"
                                           name="lat"
                                           id="lat"
                                           class="form-control @error('lat') is-invalid @enderror"
                                           value="{{ old('lat', $destacamento->lat) }}">

                                    @error('lat')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="lng">Longitud</label>
                                    <input type="text"
                                           name="lng"
                                           id="lng"
                                           class="form-control @error('lng') is-invalid @enderror"
                                           value="{{ old('lng', $destacamento->lng) }}">

                                    @error('lng')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="direccion">Dirección</label>
                                    <input type="text"
                                           name="direccion"
                                           id="direccion"
                                           class="form-control @error('direccion') is-invalid @enderror"
                                           value="{{ old('direccion', $destacamento->direccion) }}">

                                    @error('direccion')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="telefono">Teléfono</label>
                                    <input type="text"
                                           name="telefono"
                                           id="telefono"
                                           class="form-control @error('telefono') is-invalid @enderror"
                                           value="{{ old('telefono', $destacamento->telefono) }}">

                                    @error('telefono')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="activa">Estado</label>
                                    <select name="activa"
                                            id="activa"
                                            class="form-control @error('activa') is-invalid @enderror">
                                        @php $activo = old('activa', (int) $destacamento->activo); @endphp
                                        <option value="1" {{ (int)$activo === 1 ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ (int)$activo === 0 ? 'selected' : '' }}>Inactivo</option>
                                    </select>

                                    @error('activa')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="responsable">Responsable</label>
                                    <input type="text"
                                           name="responsable"
                                           id="responsable"
                                           class="form-control @error('responsable') is-invalid @enderror"
                                           value="{{ old('responsable', $destacamento->responsable) }}">

                                    @error('responsable')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="referencia">Referencia</label>
                                    <input type="text"
                                           name="referencia"
                                           id="referencia"
                                           class="form-control @error('referencia') is-invalid @enderror"
                                           value="{{ old('referencia', $destacamento->referencia) }}">

                                    @error('referencia')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fa-solid fa-save"></i> Guardar Cambios
                                    </button>

                                    <a href="{{ route('destacamentos.index') }}" class="btn btn-secondary">
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
        .form-group label { font-weight: bold; }
    </style>
@stop

@section('js')
<script>
    @if ($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Errores en el formulario',
            html: `
                <ul style="text-align:left;">
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
