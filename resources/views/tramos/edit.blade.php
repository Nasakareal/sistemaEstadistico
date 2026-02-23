@extends('adminlte::page')

@section('title', 'Editar Tramo')

@section('content_header')
    <h1>Editar Tramo</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Actualice los Datos</h3>
                </div>

                <div class="card-body">
                    <form action="{{ route('tramos.update', $tramo->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="carretera">Carretera</label>
                                    <input type="text" name="carretera" id="carretera"
                                           class="form-control @error('carretera') is-invalid @enderror"
                                           value="{{ old('carretera', $tramo->carretera) }}" required>

                                    @error('carretera')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombre del Tramo</label>
                                    <input type="text" name="nombre" id="nombre"
                                           class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre', $tramo->nombre) }}" required>

                                    @error('nombre')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="km_inicio">KM Inicio</label>
                                    <input type="number" step="0.001" name="km_inicio" id="km_inicio"
                                           class="form-control @error('km_inicio') is-invalid @enderror"
                                           value="{{ old('km_inicio', $tramo->km_inicio) }}">

                                    @error('km_inicio')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="km_fin">KM Fin</label>
                                    <input type="number" step="0.001" name="km_fin" id="km_fin"
                                           class="form-control @error('km_fin') is-invalid @enderror"
                                           value="{{ old('km_fin', $tramo->km_fin) }}">

                                    @error('km_fin')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="activo" name="activo"
                                               {{ old('activo', $tramo->activo) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="activo">ACTIVO</label>
                                    </div>

                                    @error('activo')
                                        <span class="text-danger" style="font-size: 0.9rem;">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="lat_inicio">Lat Inicio</label>
                                    <input type="number" step="0.0000001" name="lat_inicio" id="lat_inicio"
                                           class="form-control @error('lat_inicio') is-invalid @enderror"
                                           value="{{ old('lat_inicio', $tramo->lat_inicio) }}" placeholder="Ej: 19.7000000">

                                    @error('lat_inicio')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="lng_inicio">Lng Inicio</label>
                                    <input type="number" step="0.0000001" name="lng_inicio" id="lng_inicio"
                                           class="form-control @error('lng_inicio') is-invalid @enderror"
                                           value="{{ old('lng_inicio', $tramo->lng_inicio) }}" placeholder="Ej: -101.1850000">

                                    @error('lng_inicio')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="lat_fin">Lat Fin</label>
                                    <input type="number" step="0.0000001" name="lat_fin" id="lat_fin"
                                           class="form-control @error('lat_fin') is-invalid @enderror"
                                           value="{{ old('lat_fin', $tramo->lat_fin) }}" placeholder="Ej: 19.7100000">

                                    @error('lat_fin')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="lng_fin">Lng Fin</label>
                                    <input type="number" step="0.0000001" name="lng_fin" id="lng_fin"
                                           class="form-control @error('lng_fin') is-invalid @enderror"
                                           value="{{ old('lng_fin', $tramo->lng_fin) }}" placeholder="Ej: -101.1750000">

                                    @error('lng_fin')
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
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-check"></i> Guardar Cambios
                                    </button>

                                    <a href="{{ route('tramos.index') }}" class="btn btn-secondary">
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
    $(document).on('submit', 'form', function (e) {
        let kmInicio = parseFloat($('#km_inicio').val() || '');
        let kmFin = parseFloat($('#km_fin').val() || '');

        let latInicio = $('#lat_inicio').val();
        let lngInicio = $('#lng_inicio').val();
        let latFin = $('#lat_fin').val();
        let lngFin = $('#lng_fin').val();

        let anyCoord = (latInicio !== '' || lngInicio !== '' || latFin !== '' || lngFin !== '');
        let allCoord = (latInicio !== '' && lngInicio !== '' && latFin !== '' && lngFin !== '');

        if (!isNaN(kmInicio) && !isNaN(kmFin) && kmInicio > kmFin) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Rango inválido',
                text: 'El KM inicio no puede ser mayor que el KM fin.',
                confirmButtonText: 'Aceptar'
            });
            return;
        }

        if (anyCoord && !allCoord) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Coordenadas incompletas',
                text: 'Si capturas coordenadas, debes capturar inicio y fin completos (lat/lng).',
                confirmButtonText: 'Aceptar'
            });
            return;
        }
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

    @if (session('success'))
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 4000
        });
    @endif
</script>
@stop
