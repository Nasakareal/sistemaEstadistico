@extends('adminlte::page')

@section('title', 'Crear Patrulla')

@section('content_header')
    <h1>Creación de una Nueva Patrulla</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('patrullas.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="numero_economico">Número Económico</label>
                                    <input
                                        type="text"
                                        name="numero_economico"
                                        id="numero_economico"
                                        class="form-control @error('numero_economico') is-invalid @enderror"
                                        value="{{ old('numero_economico') }}"
                                        placeholder="Ej. 3190"
                                        required
                                    >
                                    @error('numero_economico')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="unidad_id">Unidad</label>
                                    <select
                                        name="unidad_id"
                                        id="unidad_id"
                                        class="form-control @error('unidad_id') is-invalid @enderror"
                                        required
                                    >
                                        <option value="" disabled {{ old('unidad_id') ? '' : 'selected' }}>Seleccione una unidad</option>
                                        @foreach ($unidades as $u)
                                            <option value="{{ $u->id }}" {{ old('unidad_id') == $u->id ? 'selected' : '' }}>
                                                {{ $u->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('unidad_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="turno_id">Turno</label>
                                    <select
                                        name="turno_id"
                                        id="turno_id"
                                        class="form-control @error('turno_id') is-invalid @enderror"
                                    >
                                        <option value="" selected>Sin turno</option>
                                        @foreach ($turnos as $t)
                                            <option value="{{ $t->id }}" {{ old('turno_id') == $t->id ? 'selected' : '' }}>
                                                {{ $t->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('turno_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="text-muted">Opcional. Útil si la patrulla está fija a un turno.</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="activa">Estado</label>
                                    <select
                                        name="activa"
                                        id="activa"
                                        class="form-control @error('activa') is-invalid @enderror"
                                        required
                                    >
                                        <option value="1" {{ old('activa', '1') == '1' ? 'selected' : '' }}>
                                            Activa
                                        </option>
                                        <option value="0" {{ old('activa') == '0' ? 'selected' : '' }}>
                                            Inactiva
                                        </option>
                                    </select>
                                    @error('activa')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3"><strong>Datos del Vehículo</strong></h5>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tipo">Tipo</label>
                                    <select
                                        name="tipo"
                                        id="tipo"
                                        class="form-control @error('tipo') is-invalid @enderror"
                                    >
                                        <option value="" selected>Seleccione</option>
                                        <option value="PICKUP" {{ old('tipo') == 'PICKUP' ? 'selected' : '' }}>Pick-Up</option>
                                        <option value="SUV" {{ old('tipo') == 'SUV' ? 'selected' : '' }}>SUV</option>
                                        <option value="SEDAN" {{ old('tipo') == 'SEDAN' ? 'selected' : '' }}>Sedán</option>
                                        <option value="MOTO" {{ old('tipo') == 'MOTO' ? 'selected' : '' }}>Moto</option>
                                        <option value="VAN" {{ old('tipo') == 'VAN' ? 'selected' : '' }}>Van</option>
                                        <option value="OTRO" {{ old('tipo') == 'OTRO' ? 'selected' : '' }}>Otro</option>
                                    </select>
                                    @error('tipo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="marca">Marca</label>
                                    <input
                                        type="text"
                                        name="marca"
                                        id="marca"
                                        class="form-control @error('marca') is-invalid @enderror"
                                        value="{{ old('marca') }}"
                                        placeholder="Ej. FORD, RAM, NISSAN"
                                    >
                                    @error('marca')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="linea">Línea</label>
                                    <input
                                        type="text"
                                        name="linea"
                                        id="linea"
                                        class="form-control @error('linea') is-invalid @enderror"
                                        value="{{ old('linea') }}"
                                        placeholder="Ej. JETTA, CHARGER, F-150, PATRIOT"
                                    >
                                    @error('linea')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="modelo">Modelo (Año)</label>
                                    <input
                                        type="number"
                                        name="modelo"
                                        id="modelo"
                                        class="form-control @error('modelo') is-invalid @enderror"
                                        value="{{ old('modelo') }}"
                                        placeholder="Ej. 2020"
                                        min="1900"
                                        max="2100"
                                    >
                                    @error('modelo')
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
                                    <label for="placas">Placas</label>
                                    <input
                                        type="text"
                                        name="placas"
                                        id="placas"
                                        class="form-control @error('placas') is-invalid @enderror"
                                        value="{{ old('placas') }}"
                                        placeholder="Ej. MC713A9"
                                    >
                                    @error('placas')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="text-muted">Sin espacios ni guiones (opcional).</small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="serie">Serie (VIN)</label>
                                    <input
                                        type="text"
                                        name="serie"
                                        id="serie"
                                        class="form-control @error('serie') is-invalid @enderror"
                                        value="{{ old('serie') }}"
                                        placeholder="Ej. 3FA6P0H73HR..."
                                    >
                                    @error('serie')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="color">Color</label>
                                    <input
                                        type="text"
                                        name="color"
                                        id="color"
                                        class="form-control @error('color') is-invalid @enderror"
                                        value="{{ old('color') }}"
                                        placeholder="Ej. BLANCO"
                                    >
                                    @error('color')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="no_motor">No. Motor</label>
                                    <input
                                        type="text"
                                        name="no_motor"
                                        id="no_motor"
                                        class="form-control @error('no_motor') is-invalid @enderror"
                                        value="{{ old('no_motor') }}"
                                        placeholder="Opcional"
                                    >
                                    @error('no_motor')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fotos">Fotos de la Patrulla</label>
                                    <input
                                        type="file"
                                        name="fotos[]"
                                        id="fotos"
                                        class="form-control @error('fotos') is-invalid @enderror @error('fotos.*') is-invalid @enderror"
                                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                        multiple
                                        required
                                    >
                                    @error('fotos')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    @error('fotos.*')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="text-muted">Obligatorias. Puedes seleccionar varias imágenes. Formatos permitidos: JPG, JPEG, PNG, WEBP.</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Vista previa</label>
                                    <div class="border rounded p-2 bg-light" style="min-height: 220px;">
                                        <div id="preview_fotos" class="row"></div>
                                        <div id="preview_placeholder" class="text-muted text-center" style="padding-top: 85px;">
                                            Aún no se han seleccionado imágenes
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="observaciones">Observaciones</label>
                                    <textarea
                                        name="observaciones"
                                        id="observaciones"
                                        class="form-control @error('observaciones') is-invalid @enderror"
                                        rows="3"
                                        placeholder="Opcional"
                                    >{{ old('observaciones') }}</textarea>
                                    @error('observaciones')
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
                                        <i class="fa-solid fa-check"></i> Registrar
                                    </button>
                                    <a href="{{ route('patrullas.index') }}" class="btn btn-secondary">
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

        .preview-foto-card {
            margin-bottom: 10px;
        }

        .preview-foto-img {
            width: 100%;
            height: 95px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
            background: #ffffff;
        }
    </style>
@stop

@section('js')
    @if ($errors->any())
        <script>
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
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const inputFotos = document.getElementById('fotos');
            const previewFotos = document.getElementById('preview_fotos');
            const previewPlaceholder = document.getElementById('preview_placeholder');

            if (inputFotos) {
                inputFotos.addEventListener('change', function (e) {
                    const archivos = Array.from(e.target.files);

                    previewFotos.innerHTML = '';

                    if (archivos.length === 0) {
                        previewPlaceholder.style.display = 'block';
                        return;
                    }

                    previewPlaceholder.style.display = 'none';

                    archivos.forEach(function (archivo) {
                        const lector = new FileReader();

                        lector.onload = function (evento) {
                            const columna = document.createElement('div');
                            columna.className = 'col-md-4 col-sm-6 preview-foto-card';

                            const imagen = document.createElement('img');
                            imagen.src = evento.target.result;
                            imagen.alt = 'Vista previa de la patrulla';
                            imagen.className = 'preview-foto-img';

                            columna.appendChild(imagen);
                            previewFotos.appendChild(columna);
                        };

                        lector.readAsDataURL(archivo);
                    });
                });
            }
        });
    </script>
@stop
