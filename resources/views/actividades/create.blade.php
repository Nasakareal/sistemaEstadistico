@extends('adminlte::page')

@section('title', 'Crear Actividad')

@section('content_header')
    <h1>Creación de una Nueva Actividad</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>

                <div class="card-body">
                    <form action="{{ route('actividades.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nombre">Nombre<span style="color:red">*</span></label>
                                    <input type="text"
                                           name="nombre"
                                           id="nombre"
                                           class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre', auth()->user()->name ?? '') }}"
                                           readonly
                                           required>
                                    @error('nombre')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="help-muted">Se toma automáticamente del usuario. No se puede editar.</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="actividad_categoria_id">Categoría<span style="color:red">*</span></label>
                                    <select name="actividad_categoria_id"
                                            id="actividad_categoria_id"
                                            class="form-control @error('actividad_categoria_id') is-invalid @enderror"
                                            required>
                                        <option value="" disabled {{ old('actividad_categoria_id') ? '' : 'selected' }}>Seleccione...</option>
                                        @foreach ($categorias as $c)
                                            <option value="{{ $c->id }}" {{ old('actividad_categoria_id') == $c->id ? 'selected' : '' }}>
                                                {{ $c->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('actividad_categoria_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="actividad_subcategoria_id">Subcategoría</label>
                                    <select name="actividad_subcategoria_id"
                                            id="actividad_subcategoria_id"
                                            class="form-control @error('actividad_subcategoria_id') is-invalid @enderror"
                                            disabled>
                                        <option value="" selected>Seleccione una categoría primero...</option>
                                    </select>
                                    @error('actividad_subcategoria_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="help-muted">Opcional. Si no aplica, déjelo vacío.</small>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="cantidad" value="1">
                        <input type="hidden" name="destacamento_id" value="{{ old('destacamento_id') }}">

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha">Fecha<span style="color:red">*</span></label>
                                    <input type="date"
                                           name="fecha"
                                           id="fecha"
                                           class="form-control @error('fecha') is-invalid @enderror"
                                           value="{{ old('fecha', now('America/Mexico_City')->toDateString()) }}"
                                           required>
                                    @error('fecha')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="hora">Hora</label>
                                    <input type="time"
                                           name="hora"
                                           id="hora"
                                           class="form-control @error('hora') is-invalid @enderror"
                                           value="{{ old('hora') }}">
                                    @error('hora')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="lugar">Lugar</label>
                                    <input type="text"
                                           name="lugar"
                                           id="lugar"
                                           class="form-control @error('lugar') is-invalid @enderror"
                                           value="{{ old('lugar') }}"
                                           placeholder="Ej. AV. MADERO PONIENTE Y ABASOLO">
                                    @error('lugar')
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
                                    <label for="municipio">Municipio</label>
                                    <input type="text"
                                           name="municipio"
                                           id="municipio"
                                           class="form-control @error('municipio') is-invalid @enderror"
                                           value="{{ old('municipio', 'MORELIA') }}"
                                           placeholder="Ej. MORELIA">
                                    @error('municipio')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="carretera">Carretera</label>
                                    <input type="text"
                                           name="carretera"
                                           id="carretera"
                                           class="form-control @error('carretera') is-invalid @enderror"
                                           value="{{ old('carretera') }}">
                                    @error('carretera')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tramo">Tramo</label>
                                    <input type="text"
                                           name="tramo"
                                           id="tramo"
                                           class="form-control @error('tramo') is-invalid @enderror"
                                           value="{{ old('tramo') }}">
                                    @error('tramo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="kilometro">Kilómetro</label>
                                    <input type="text"
                                           name="kilometro"
                                           id="kilometro"
                                           class="form-control @error('kilometro') is-invalid @enderror"
                                           value="{{ old('kilometro') }}"
                                           placeholder="Ej. KM 12+500">
                                    @error('kilometro')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}">
                        <input type="hidden" name="lng" id="lng" value="{{ old('lng') }}">
                        <input type="hidden" name="fuente_ubicacion" id="fuente_ubicacion" value="{{ old('fuente_ubicacion') }}">
                        <input type="hidden" name="nota_geo" id="nota_geo" value="{{ old('nota_geo') }}">
                        <input type="hidden" name="coordenadas_texto" id="coordenadas_texto" value="{{ old('coordenadas_texto') }}">

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Ubicación</label>

                                    <div class="d-flex flex-wrap align-items-center" style="gap:10px;">
                                        <button type="button" id="btnUbicacion" class="btn btn-info">
                                            <i class="fa-solid fa-location-crosshairs"></i> Usar mi ubicación
                                        </button>

                                        <span id="ubicacion_estado" class="help-muted">
                                            {{ old('lat') && old('lng') ? 'Ubicación capturada correctamente.' : 'Aún no se ha capturado la ubicación.' }}
                                        </span>
                                    </div>

                                    <div id="ubicacion_preview_wrap" class="mt-2" style="{{ old('lat') && old('lng') ? '' : 'display:none;' }}">
                                        <div class="form-control">
                                            <span id="ubicacion_preview_texto">
                                                {{ old('lat') && old('lng') ? old('lat') . ', ' . old('lng') : '' }}
                                            </span>
                                        </div>
                                    </div>

                                    @error('lat')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror

                                    @error('lng')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror

                                    @error('fuente_ubicacion')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror

                                    @error('nota_geo')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror

                                    @error('coordenadas_texto')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="motivo">Qué ocasiona / motivo</label>
                                    <textarea name="motivo"
                                              id="motivo"
                                              rows="3"
                                              class="form-control @error('motivo') is-invalid @enderror"
                                              placeholder="Ej. CORTE DE CIRCULACIÓN POR MANIFESTACIÓN">{{ old('motivo') }}</textarea>
                                    @error('motivo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="narrativa">Narrativa</label>
                                    <textarea name="narrativa"
                                              id="narrativa"
                                              rows="3"
                                              class="form-control @error('narrativa') is-invalid @enderror"
                                              placeholder="Describa lo ocurrido">{{ old('narrativa') }}</textarea>
                                    @error('narrativa')
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
                                    <label for="acciones_realizadas">Acciones realizadas</label>
                                    <textarea name="acciones_realizadas"
                                              id="acciones_realizadas"
                                              rows="3"
                                              class="form-control @error('acciones_realizadas') is-invalid @enderror"
                                              placeholder="Acciones realizadas por el personal">{{ old('acciones_realizadas') }}</textarea>
                                    @error('acciones_realizadas')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="observaciones">Observaciones</label>
                                    <textarea name="observaciones"
                                              id="observaciones"
                                              rows="3"
                                              class="form-control @error('observaciones') is-invalid @enderror"
                                              placeholder="Observaciones adicionales">{{ old('observaciones') }}</textarea>
                                    @error('observaciones')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="personas_alcanzadas">Personas alcanzadas</label>
                                    <input type="number"
                                           min="0"
                                           name="personas_alcanzadas"
                                           id="personas_alcanzadas"
                                           class="form-control @error('personas_alcanzadas') is-invalid @enderror"
                                           value="{{ old('personas_alcanzadas', 0) }}">
                                    @error('personas_alcanzadas')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="personas_participantes">Personas participantes</label>
                                    <input type="number"
                                           min="0"
                                           name="personas_participantes"
                                           id="personas_participantes"
                                           class="form-control @error('personas_participantes') is-invalid @enderror"
                                           value="{{ old('personas_participantes', 0) }}">
                                    @error('personas_participantes')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="personas_detenidas">Personas detenidas</label>
                                    <input type="number"
                                           min="0"
                                           name="personas_detenidas"
                                           id="personas_detenidas"
                                           class="form-control @error('personas_detenidas') is-invalid @enderror"
                                           value="{{ old('personas_detenidas', 0) }}">
                                    @error('personas_detenidas')
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
                                    <label for="elementos_participantes_texto">Elementos participantes</label>
                                    <textarea name="elementos_participantes_texto"
                                              id="elementos_participantes_texto"
                                              rows="3"
                                              class="form-control @error('elementos_participantes_texto') is-invalid @enderror"
                                              placeholder="Ej. OF. JUAN PÉREZ, OF. LUIS GARCÍA">{{ old('elementos_participantes_texto') }}</textarea>
                                    @error('elementos_participantes_texto')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="patrullas_participantes_texto">Patrullas participantes</label>
                                    <textarea name="patrullas_participantes_texto"
                                              id="patrullas_participantes_texto"
                                              rows="3"
                                              class="form-control @error('patrullas_participantes_texto') is-invalid @enderror"
                                              placeholder="Ej. 3214, 3178, 04-174">{{ old('patrullas_participantes_texto') }}</textarea>
                                    @error('patrullas_participantes_texto')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="foto">Foto<span style="color:red">*</span></label>
                                    <input type="file"
                                           name="foto"
                                           id="foto"
                                           accept="image/*"
                                           class="form-control @error('foto') is-invalid @enderror"
                                           required>
                                    @error('foto')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small id="foto_name" class="help-muted"></small>

                                    <div id="preview_wrap" class="preview-wrap" style="display:none;">
                                        <img id="foto_preview" class="foto-thumb" src="" alt="Vista previa">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12 text-center">
                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-check"></i> Registrar
                                    </button>

                                    <a href="{{ route('actividades.index') }}" class="btn btn-secondary">
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
        .help-muted {
            color: rgba(234,240,255,.65);
        }

        .form-group label {
            font-weight: bold;
            color: #eaf0ff;
        }

        .form-control,
        select.form-control,
        textarea.form-control {
            color: #eaf0ff;
            background-color: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 12px;
        }

        .form-control::placeholder,
        textarea.form-control::placeholder {
            color: rgba(234,240,255,.55);
        }

        select option {
            color: #111 !important;
            background-color: #ffffff !important;
        }

        .form-control:focus,
        select:focus,
        textarea:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(45,168,255,.35);
            border-color: rgba(45,168,255,.55);
        }

        .preview-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
        }

        .foto-thumb {
            width: 140px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(255,255,255,.06);
        }
    </style>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const categoriaSelect = document.getElementById('actividad_categoria_id');
            const subcatSelect = document.getElementById('actividad_subcategoria_id');
            const nombreInput = document.getElementById('nombre');
            const nombreActual = nombreInput ? nombreInput.value : '';
            const fotoInput = document.getElementById('foto');
            const fotoName = document.getElementById('foto_name');
            const previewWrap = document.getElementById('preview_wrap');
            const fotoPreview = document.getElementById('foto_preview');

            const latInput = document.getElementById('lat');
            const lngInput = document.getElementById('lng');
            const fuenteInput = document.getElementById('fuente_ubicacion');
            const notaGeoInput = document.getElementById('nota_geo');
            const coordenadasTextoInput = document.querySelector('input[type="hidden"][name="coordenadas_texto"]');
            const btnUbicacion = document.getElementById('btnUbicacion');
            const ubicacionEstado = document.getElementById('ubicacion_estado');
            const ubicacionPreviewWrap = document.getElementById('ubicacion_preview_wrap');
            const ubicacionPreviewTexto = document.getElementById('ubicacion_preview_texto');

            function setSubcatDisabled(msg) {
                if (!subcatSelect) return;
                subcatSelect.disabled = true;
                subcatSelect.innerHTML = `<option value="" selected>${msg}</option>`;
            }

            function setSubcatBase(msgOpcional) {
                if (!subcatSelect) return;

                subcatSelect.disabled = false;
                subcatSelect.innerHTML = '';

                const optEmpty = document.createElement('option');
                optEmpty.value = '';
                optEmpty.textContent = 'Sin subcategoría';
                subcatSelect.appendChild(optEmpty);

                if (msgOpcional) {
                    const optMsg = document.createElement('option');
                    optMsg.value = '';
                    optMsg.textContent = msgOpcional;
                    optMsg.disabled = true;
                    subcatSelect.appendChild(optMsg);
                }
            }

            async function cargarSubcategorias(categoriaId) {
                if (!categoriaId) {
                    setSubcatDisabled('Seleccione una categoría primero...');
                    return;
                }

                setSubcatDisabled('Cargando...');

                try {
                    const url = `{{ route('actividades.subcategorias', ['categoria' => '__ID__']) }}`.replace('__ID__', encodeURIComponent(categoriaId));

                    const res = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });

                    if (!res.ok) {
                        setSubcatBase('No hay subcategorías para esta categoría');
                        return;
                    }

                    const data = await res.json();

                    if (!Array.isArray(data) || data.length === 0) {
                        setSubcatBase('No hay subcategorías para esta categoría');
                        return;
                    }

                    setSubcatBase(null);

                    data.forEach(function (s) {
                        const opt = document.createElement('option');
                        opt.value = s.id;
                        opt.textContent = s.nombre;
                        subcatSelect.appendChild(opt);
                    });

                    const oldSub = @json(old('actividad_subcategoria_id'));
                    if (oldSub !== null && oldSub !== '') {
                        subcatSelect.value = String(oldSub);
                    } else {
                        subcatSelect.value = '';
                    }

                } catch (e) {
                    setSubcatBase('No hay subcategorías para esta categoría');
                }
            }

            function actualizarPreviewUbicacion(lat, lng) {
                if (!lat || !lng) {
                    if (ubicacionPreviewWrap) ubicacionPreviewWrap.style.display = 'none';
                    if (ubicacionPreviewTexto) ubicacionPreviewTexto.textContent = '';
                    return;
                }

                if (ubicacionPreviewTexto) {
                    ubicacionPreviewTexto.textContent = `${lat}, ${lng}`;
                }

                if (ubicacionPreviewWrap) {
                    ubicacionPreviewWrap.style.display = '';
                }
            }

            if (btnUbicacion) {
                btnUbicacion.addEventListener('click', function () {
                    if (!navigator.geolocation) {
                        if (ubicacionEstado) {
                            ubicacionEstado.textContent = 'Este dispositivo o navegador no permite geolocalización.';
                        }
                        return;
                    }

                    if (ubicacionEstado) {
                        ubicacionEstado.textContent = 'Obteniendo ubicación...';
                    }

                    btnUbicacion.disabled = true;

                    navigator.geolocation.getCurrentPosition(
                        function (position) {
                            const lat = Number(position.coords.latitude).toFixed(7);
                            const lng = Number(position.coords.longitude).toFixed(7);
                            const accuracy = position.coords.accuracy ? Number(position.coords.accuracy).toFixed(1) : null;

                            if (latInput) latInput.value = lat;
                            if (lngInput) lngInput.value = lng;
                            if (fuenteInput) fuenteInput.value = 'GPS_WEB';
                            if (notaGeoInput) notaGeoInput.value = accuracy ? `ACC:${accuracy}m` : 'GPS_WEB';
                            if (coordenadasTextoInput) coordenadasTextoInput.value = `${lat}, ${lng}`;

                            actualizarPreviewUbicacion(lat, lng);

                            if (ubicacionEstado) {
                                ubicacionEstado.textContent = 'Ubicación capturada correctamente.';
                            }

                            btnUbicacion.disabled = false;
                        },
                        function (error) {
                            let mensaje = 'No se pudo obtener la ubicación.';

                            if (error && error.code === 1) mensaje = 'Permiso de ubicación denegado.';
                            if (error && error.code === 2) mensaje = 'Ubicación no disponible.';
                            if (error && error.code === 3) mensaje = 'Tiempo de espera agotado al obtener la ubicación.';

                            if (ubicacionEstado) {
                                ubicacionEstado.textContent = mensaje;
                            }

                            btnUbicacion.disabled = false;
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0
                        }
                    );
                });
            }

            if (nombreInput) {
                nombreInput.addEventListener('input', function () {
                    if (this.value !== nombreActual) {
                        this.value = nombreActual;
                    }
                });

                nombreInput.addEventListener('keydown', function (e) {
                    if (e.key !== 'Tab') {
                        e.preventDefault();
                    }
                });
            }

            if (categoriaSelect && subcatSelect) {
                categoriaSelect.addEventListener('change', function () {
                    cargarSubcategorias(this.value);
                });

                const oldCat = @json(old('actividad_categoria_id'));
                const initialCat = oldCat || categoriaSelect.value || '';

                if (initialCat) {
                    cargarSubcategorias(initialCat);
                } else {
                    setSubcatDisabled('Seleccione una categoría primero...');
                }
            }

            actualizarPreviewUbicacion(
                latInput ? latInput.value : '',
                lngInput ? lngInput.value : ''
            );

            if (fotoInput) {
                fotoInput.addEventListener('change', function () {
                    const file = fotoInput.files && fotoInput.files[0] ? fotoInput.files[0] : null;

                    if (fotoName) {
                        fotoName.textContent = file ? ('Archivo: ' + file.name) : '';
                    }

                    if (!file) {
                        if (previewWrap) previewWrap.style.display = 'none';
                        if (fotoPreview) fotoPreview.src = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        if (fotoPreview) fotoPreview.src = e.target.result;
                        if (previewWrap) previewWrap.style.display = 'flex';
                    };
                    reader.readAsDataURL(file);
                });
            }
        });

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
