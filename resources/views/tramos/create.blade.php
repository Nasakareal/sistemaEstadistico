@extends('adminlte::page')

@section('title', 'Registrar Tramo')

@section('content_header')
    <h1>Registro de un Nuevo Tramo</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>

                <div class="card-body">
                    <form action="{{ route('tramos.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="carretera">Carretera</label>
                                    <input type="text"
                                           name="carretera"
                                           id="carretera"
                                           class="form-control @error('carretera') is-invalid @enderror"
                                           value="{{ old('carretera') }}"
                                           required>

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
                                    <input type="text"
                                           name="nombre"
                                           id="nombre"
                                           class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre') }}"
                                           required>

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
                                    <input type="number"
                                           step="0.001"
                                           name="km_inicio"
                                           id="km_inicio"
                                           class="form-control @error('km_inicio') is-invalid @enderror"
                                           value="{{ old('km_inicio') }}">

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
                                    <input type="number"
                                           step="0.001"
                                           name="km_fin"
                                           id="km_fin"
                                           class="form-control @error('km_fin') is-invalid @enderror"
                                           value="{{ old('km_fin') }}">

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
                                    <input type="hidden" name="activo" value="0">

                                    <div class="custom-control custom-switch">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="activo"
                                               name="activo"
                                               value="1"
                                               {{ old('activo', 1) ? 'checked' : '' }}>
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

                        <div class="mb-2">
                            <h5 class="mb-1"><strong>Coordenadas base del tramo</strong></h5>
                            <small class="text-muted">
                                Puedes capturar solo inicio y fin, o además capturar la polyline / puntos_json para que el tramo siga la carretera real.
                            </small>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="lat_inicio">Lat Inicio</label>
                                    <input type="number"
                                           step="0.0000001"
                                           name="lat_inicio"
                                           id="lat_inicio"
                                           class="form-control @error('lat_inicio') is-invalid @enderror"
                                           value="{{ old('lat_inicio') }}"
                                           placeholder="Ej: 19.7000000">

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
                                    <input type="number"
                                           step="0.0000001"
                                           name="lng_inicio"
                                           id="lng_inicio"
                                           class="form-control @error('lng_inicio') is-invalid @enderror"
                                           value="{{ old('lng_inicio') }}"
                                           placeholder="Ej: -101.1850000">

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
                                    <input type="number"
                                           step="0.0000001"
                                           name="lat_fin"
                                           id="lat_fin"
                                           class="form-control @error('lat_fin') is-invalid @enderror"
                                           value="{{ old('lat_fin') }}"
                                           placeholder="Ej: 19.7100000">

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
                                    <input type="number"
                                           step="0.0000001"
                                           name="lng_fin"
                                           id="lng_fin"
                                           class="form-control @error('lng_fin') is-invalid @enderror"
                                           value="{{ old('lng_fin') }}"
                                           placeholder="Ej: -101.1750000">

                                    @error('lng_fin')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-2">
                            <h5 class="mb-1"><strong>Ruta real del tramo</strong></h5>
                            <small class="text-muted">
                                Si capturas esta información, el sistema podrá guardar la geometría curveada del tramo.
                            </small>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="polyline">Polyline</label>
                                    <textarea name="polyline"
                                              id="polyline"
                                              rows="4"
                                              class="form-control @error('polyline') is-invalid @enderror"
                                              placeholder="Pega aquí la polyline codificada del tramo">{{ old('polyline') }}</textarea>

                                    <small class="text-muted">
                                        Ejemplo: cadena codificada como la que se usa en mapas y rutas.
                                    </small>

                                    @error('polyline')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="puntos_json">Puntos JSON</label>
                                    <textarea name="puntos_json"
                                              id="puntos_json"
                                              rows="8"
                                              class="form-control @error('puntos_json') is-invalid @enderror"
                                              placeholder='[
                                                    {"lat": 19.70600, "lng": -101.19500},
                                                    {"lat": 19.70620, "lng": -101.19480},
                                                    {"lat": 19.70650, "lng": -101.19410}
                                                ]'>{{ old('puntos_json') }}</textarea>

                                    <small class="text-muted">
                                        Puedes usar formato con objetos <code>{"lat": ..., "lng": ...}</code> o arreglos <code>[lat, lng]</code>.
                                    </small>

                                    @error('puntos_json')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <strong>Prioridad de guardado:</strong><br>
                            1. Si mandas <strong>puntos_json</strong>, se usará eso.<br>
                            2. Si no mandas puntos_json pero sí <strong>polyline</strong>, se intentará decodificar.<br>
                            3. Si no mandas ninguno de los dos, se usará solo inicio/fin y el tramo será una línea recta.
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-check"></i> Registrar
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
    .form-group label {
        font-weight: bold;
    }

    textarea.form-control {
        font-family: monospace;
    }
</style>
@stop

@section('js')
<script>
    $(document).on('submit', 'form', function (e) {
        let kmInicio = parseFloat($('#km_inicio').val() || '');
        let kmFin = parseFloat($('#km_fin').val() || '');

        let latInicio = $('#lat_inicio').val().trim();
        let lngInicio = $('#lng_inicio').val().trim();
        let latFin = $('#lat_fin').val().trim();
        let lngFin = $('#lng_fin').val().trim();

        let polyline = $('#polyline').val().trim();
        let puntosJson = $('#puntos_json').val().trim();

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

        if (puntosJson !== '') {
            try {
                let parsed = JSON.parse(puntosJson);

                if (!Array.isArray(parsed) || parsed.length < 2) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Puntos JSON inválidos',
                        text: 'puntos_json debe contener al menos 2 puntos.',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }
            } catch (error) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'JSON inválido',
                    text: 'El campo puntos_json no contiene un JSON válido.',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }
        }

        if (polyline !== '' && puntosJson === '' && !allCoord) {
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
</script>
@stop
