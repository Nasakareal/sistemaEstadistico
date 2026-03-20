@extends('adminlte::page')

@section('title', 'Editar Patrulla')

@section('content_header')
    <h1>Edición de Patrulla</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Actualizar Datos</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('patrullas.update', $patrulla->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="numero_economico">Número Económico</label>
                                    <input
                                        type="text"
                                        name="numero_economico"
                                        id="numero_economico"
                                        class="form-control @error('numero_economico') is-invalid @enderror"
                                        value="{{ old('numero_economico', $patrulla->numero_economico) }}"
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
                                        <option value="" disabled>Seleccione una unidad</option>
                                        @foreach ($unidades as $u)
                                            <option value="{{ $u->id }}"
                                                {{ (string) old('unidad_id', $patrulla->unidad_id) === (string) $u->id ? 'selected' : '' }}>
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
                                        <option value="" {{ old('turno_id', $patrulla->turno_id) ? '' : 'selected' }}>
                                            Sin turno
                                        </option>
                                        @foreach ($turnos as $t)
                                            <option value="{{ $t->id }}"
                                                {{ (string) old('turno_id', $patrulla->turno_id) === (string) $t->id ? 'selected' : '' }}>
                                                {{ $t->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('turno_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="text-muted">
                                        Opcional. Útil si la patrulla está fija a un turno.
                                    </small>
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
                                        <option value="1" {{ (string) old('activa', (int) $patrulla->activa) === '1' ? 'selected' : '' }}>
                                            Activa
                                        </option>
                                        <option value="0" {{ (string) old('activa', (int) $patrulla->activa) === '0' ? 'selected' : '' }}>
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
                                        <option value="" {{ old('tipo', $patrulla->tipo) ? '' : 'selected' }}>Seleccione</option>
                                        <option value="PICKUP" {{ old('tipo', $patrulla->tipo) == 'PICKUP' ? 'selected' : '' }}>Pick-Up</option>
                                        <option value="SUV" {{ old('tipo', $patrulla->tipo) == 'SUV' ? 'selected' : '' }}>SUV</option>
                                        <option value="SEDAN" {{ old('tipo', $patrulla->tipo) == 'SEDAN' ? 'selected' : '' }}>Sedán</option>
                                        <option value="MOTO" {{ old('tipo', $patrulla->tipo) == 'MOTO' ? 'selected' : '' }}>Moto</option>
                                        <option value="VAN" {{ old('tipo', $patrulla->tipo) == 'VAN' ? 'selected' : '' }}>Van</option>
                                        <option value="OTRO" {{ old('tipo', $patrulla->tipo) == 'OTRO' ? 'selected' : '' }}>Otro</option>
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
                                        value="{{ old('marca', $patrulla->marca) }}"
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
                                        value="{{ old('linea', $patrulla->linea) }}"
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
                                        value="{{ old('modelo', $patrulla->modelo) }}"
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
                                        value="{{ old('placas', $patrulla->placas) }}"
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
                                        value="{{ old('serie', $patrulla->serie) }}"
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
                                        value="{{ old('color', $patrulla->color) }}"
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
                                        value="{{ old('no_motor', $patrulla->no_motor) }}"
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
                                    <label for="foto">Foto de la Patrulla</label>
                                    <input
                                        type="file"
                                        name="foto"
                                        id="foto"
                                        class="form-control @error('foto') is-invalid @enderror"
                                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    >
                                    @error('foto')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="text-muted">Opcional. Si seleccionas una nueva imagen, reemplazará la actual.</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Vista previa</label>
                                    <div class="border rounded p-2 text-center bg-light" style="min-height: 220px;">
                                        @if ($patrulla->foto)
                                            <img
                                                id="preview_foto"
                                                src="{{ asset('storage/' . $patrulla->foto) }}"
                                                alt="Foto actual de la patrulla"
                                                style="max-width: 100%; max-height: 200px; display: inline-block;"
                                            >
                                            <div id="preview_placeholder" class="text-muted" style="display: none; padding-top: 85px;">
                                                Aún no se ha seleccionado una imagen
                                            </div>
                                        @else
                                            <img
                                                id="preview_foto"
                                                src="#"
                                                alt="Vista previa de la patrulla"
                                                style="max-width: 100%; max-height: 200px; display: none;"
                                            >
                                            <div id="preview_placeholder" class="text-muted" style="padding-top: 85px;">
                                                Esta patrulla no tiene imagen cargada
                                            </div>
                                        @endif
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
                                    >{{ old('observaciones', $patrulla->observaciones) }}</textarea>
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
                                    <button type="submit" class="btn btn-success">
                                        <i class="fa-solid fa-check"></i> Guardar Cambios
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
            const inputFoto = document.getElementById('foto');
            const previewFoto = document.getElementById('preview_foto');
            const previewPlaceholder = document.getElementById('preview_placeholder');

            if (inputFoto) {
                inputFoto.addEventListener('change', function (e) {
                    const archivo = e.target.files[0];

                    if (!archivo) {
                        @if ($patrulla->foto)
                            previewFoto.src = "{{ asset('storage/' . $patrulla->foto) }}";
                            previewFoto.style.display = 'inline-block';
                            previewPlaceholder.style.display = 'none';
                        @else
                            previewFoto.src = '#';
                            previewFoto.style.display = 'none';
                            previewPlaceholder.style.display = 'block';
                        @endif
                        return;
                    }

                    const lector = new FileReader();

                    lector.onload = function (evento) {
                        previewFoto.src = evento.target.result;
                        previewFoto.style.display = 'inline-block';
                        previewPlaceholder.style.display = 'none';
                    };

                    lector.readAsDataURL(archivo);
                });
            }
        });
    </script>
@stop
