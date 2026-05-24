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
                                        <option value="" disabled {{ ($categoriaSeleccionada ?? old('actividad_categoria_id')) ? '' : 'selected' }}>Seleccione...</option>
                                        @foreach ($categorias as $c)
                                            <option value="{{ $c->id }}"
                                                    data-fomento="{{ in_array((int) $c->id, $fomentoCategoriaIds ?? [], true) ? '1' : '0' }}"
                                                    {{ (string) ($categoriaSeleccionada ?? old('actividad_categoria_id')) === (string) $c->id ? 'selected' : '' }}>
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
                                    <label for="actividad_subcategoria_id">Subcategoría<span style="color:red">*</span></label>
                                    <select name="actividad_subcategoria_id"
                                            id="actividad_subcategoria_id"
                                            class="form-control @error('actividad_subcategoria_id') is-invalid @enderror"
                                            required
                                            disabled>
                                        <option value="" selected>Seleccione una categoría primero...</option>
                                    </select>
                                    @error('actividad_subcategoria_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="cantidad" value="1">
                        <input type="hidden" name="destacamento_id" value="{{ old('destacamento_id') }}">

                        <div class="row">
                            @if($puedeCapturarFechaHora ?? false)
                                <div class="col-md-2">
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

                                <div class="col-md-2">
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
                            @endif

                            <div class="{{ ($puedeCapturarFechaHora ?? false) ? 'col-md-5' : 'col-md-8' }}">
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

                            <div class="{{ ($puedeCapturarFechaHora ?? false) ? 'col-md-3' : 'col-md-4' }}">
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

                        @include('actividades.partials.fomento_cultura_vial_fields', ['detalleFomento' => null])

                        <div class="row">
                            @unless($usuarioEsFomento ?? false)
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
                            @endunless

                            <div class="{{ ($usuarioEsFomento ?? false) ? 'col-md-12' : 'col-md-6' }}">
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
                            @unless($usuarioEsFomento ?? false)
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
                            @endunless

                            <div class="{{ ($usuarioEsFomento ?? false) ? 'col-md-12' : 'col-md-6' }}">
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

                        @if($usuarioEsFomento ?? false)
                            <input type="hidden"
                                   name="personas_alcanzadas"
                                   id="personas_alcanzadas"
                                   value="{{ old('personas_alcanzadas', 0) }}">
                        @endif

                        @php
                            $colPersonasActividad = ($usuarioEsFomento ?? false) ? 'col-md-6' : 'col-md-4';
                        @endphp

                        <div class="row">
                            @unless($usuarioEsFomento ?? false)
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
                            @endunless

                            <div class="{{ $colPersonasActividad }}">
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

                            <div class="{{ $colPersonasActividad }}">
                                <div class="form-group">
                                    <label for="personas_detenidas">Personas detenidas</label>
                                    <input type="number"
                                           min="0"
                                           name="personas_detenidas"
                                           id="personas_detenidas"
                                           class="form-control @error('personas_detenidas') is-invalid @enderror"
                                           max="3"
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

                        @unless($usuarioEsFomento ?? false)
                            @include('actividades.partials.vehiculos_create')
                        @endunless

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="fotos">Fotos<span style="color:red">*</span></label>
                                    <input type="file"
                                           name="fotos[]"
                                           id="fotos"
                                           accept="image/*"
                                           multiple
                                           class="form-control @error('fotos') is-invalid @enderror @error('fotos.*') is-invalid @enderror"
                                           required>

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

                                    <small id="foto_name" class="help-muted"></small>

                                    <div id="preview_wrap" class="preview-grid" style="display:none;"></div>
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

                    @unless($usuarioEsFomento ?? false)
                        @include('actividades.partials.vehiculo_modal_create')
                    @endunless
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    @include('partials.landscape_photo_cropper_styles')

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

        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .preview-item {
            position: relative;
        }

        .foto-thumb {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(255,255,255,.06);
        }

        .preview-label {
            margin-top: 4px;
            font-size: 12px;
            color: rgba(234,240,255,.75);
            word-break: break-word;
        }

        .fomento-panel {
            padding: 16px;
            margin-bottom: 18px;
            border-radius: 12px;
            border: 1px solid rgba(45,168,255,.25);
            background: rgba(45,168,255,.07);
        }

        .fomento-panel__title {
            color: #eaf0ff;
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @include('actividades.partials.form_guardrails_styles')

        @include('actividades.partials.vehiculos_styles')
    </style>
@stop

@section('js')
    @include('partials.landscape_photo_cropper_scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const categoriaSelect = document.getElementById('actividad_categoria_id');
            const subcatSelect = document.getElementById('actividad_subcategoria_id');
            const nombreInput = document.getElementById('nombre');
            const nombreActual = nombreInput ? nombreInput.value : '';

            const fotoInput = document.getElementById('fotos');
            const fotoName = document.getElementById('foto_name');
            const previewWrap = document.getElementById('preview_wrap');

            const latInput = document.getElementById('lat');
            const lngInput = document.getElementById('lng');
            const fuenteInput = document.getElementById('fuente_ubicacion');
            const notaGeoInput = document.getElementById('nota_geo');
            const coordenadasTextoInput = document.getElementById('coordenadas_texto');
            const btnUbicacion = document.getElementById('btnUbicacion');
            const ubicacionEstado = document.getElementById('ubicacion_estado');
            const ubicacionPreviewWrap = document.getElementById('ubicacion_preview_wrap');
            const ubicacionPreviewTexto = document.getElementById('ubicacion_preview_texto');

            if (window.SeguridadVialLandscapeCropper) {
                window.SeguridadVialLandscapeCropper.attach(fotoInput);
            }

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
                optEmpty.textContent = 'Seleccione una subcategoría...';
                optEmpty.selected = true;
                optEmpty.disabled = true;
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

                    subcatSelect.dispatchEvent(new Event('change'));
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

            function limpiarPreviewFotos() {
                if (!previewWrap) return;
                previewWrap.innerHTML = '';
                previewWrap.style.display = 'none';
            }

            function renderPreviewFotos(files) {
                if (!previewWrap) return;

                limpiarPreviewFotos();

                if (!files || files.length === 0) {
                    return;
                }

                previewWrap.style.display = 'grid';

                files.forEach(function (file, index) {
                    if (!file.type || !file.type.startsWith('image/')) {
                        return;
                    }

                    const reader = new FileReader();

                    reader.onload = function (e) {
                        const item = document.createElement('div');
                        item.className = 'preview-item';

                        const img = document.createElement('img');
                        img.className = 'foto-thumb';
                        img.src = e.target.result;
                        img.alt = file.name || `Foto ${index + 1}`;

                        const label = document.createElement('div');
                        label.className = 'preview-label';
                        label.textContent = file.name || `Foto ${index + 1}`;

                        item.appendChild(img);
                        item.appendChild(label);
                        previewWrap.appendChild(item);
                    };

                    reader.readAsDataURL(file);
                });
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
                    const files = Array.from(fotoInput.files || []);

                    if (fotoName) {
                        fotoName.textContent = files.length > 0
                            ? `${files.length} archivo(s) seleccionado(s)`
                            : '';
                    }

                    renderPreviewFotos(files);
                });
            }

            @include('actividades.partials.fomento_cultura_vial_scripts')

            @include('actividades.partials.form_guardrails_scripts')
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

    @unless($usuarioEsFomento ?? false)
        @include('actividades.partials.vehiculos_scripts', ['modo' => 'create', 'vehiculosIniciales' => old('vehiculos', [])])
    @endunless
@stop
