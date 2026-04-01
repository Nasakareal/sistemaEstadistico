@extends('adminlte::page')

@section('title', 'Crear Destacamento')

@section('content_header')
    <h1>Creación de un Nuevo Destacamento</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>

                <div class="card-body">
                    <form action="{{ route('destacamentos.store') }}" method="POST">
                        @csrf

                        <div class="row">

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Clave</label>
                                    <input type="text" name="clave"
                                           class="form-control @error('clave') is-invalid @enderror"
                                           value="{{ old('clave') }}">
                                    @error('clave')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>Nombre del Destacamento</label>
                                    <input type="text" name="nombre"
                                           class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre') }}" required>
                                    @error('nombre')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Municipio</label>
                                    <input type="text" name="municipio"
                                           class="form-control @error('municipio') is-invalid @enderror"
                                           value="{{ old('municipio') }}">
                                    @error('municipio')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Latitud</label>
                                    <input type="text" name="lat"
                                           class="form-control @error('lat') is-invalid @enderror"
                                           value="{{ old('lat') }}">
                                    @error('lat')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Longitud</label>
                                    <input type="text" name="lng"
                                           class="form-control @error('lng') is-invalid @enderror"
                                           value="{{ old('lng') }}">
                                    @error('lng')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Dirección</label>
                                    <input type="text" name="direccion"
                                           class="form-control @error('direccion') is-invalid @enderror"
                                           value="{{ old('direccion') }}">
                                    @error('direccion')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Teléfono</label>
                                    <input type="text" name="telefono"
                                           class="form-control @error('telefono') is-invalid @enderror"
                                           value="{{ old('telefono') }}">
                                    @error('telefono')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Estado</label>
                                    @php $activa = old('activa', 1); @endphp
                                    <select name="activa" class="form-control">
                                        <option value="1" {{ (int)$activa === 1 ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ (int)$activa === 0 ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                </div>
                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Responsable</label>
                                    <input type="text" name="responsable"
                                           class="form-control @error('responsable') is-invalid @enderror"
                                           value="{{ old('responsable') }}">
                                    @error('responsable')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Referencia</label>
                                    <input type="text" name="referencia"
                                           class="form-control @error('referencia') is-invalid @enderror"
                                           value="{{ old('referencia') }}">
                                    @error('referencia')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <hr>

                        <button class="btn btn-primary">
                            <i class="fa fa-check"></i> Registrar
                        </button>

                        <a href="{{ route('destacamentos.index') }}" class="btn btn-secondary">
                            Cancelar
                        </a>

                    </form>
                </div>
            </div>

        </div>
    </div>
@stop

@section('js')
<script>
@if ($errors->any())
Swal.fire({
    icon: 'error',
    title: 'Errores en el formulario',
    html: `<ul style="text-align:left;">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>`
});
@endif
</script>
@stop
