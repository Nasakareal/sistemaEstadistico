@extends('adminlte::page')

@section('title', 'Editar Actividad')

@section('content_header')
    <h1>Editar Actividad</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Modifique los Datos</h3>
                </div>

                <div class="card-body">
                    <form action="{{ route('actividades.update', $actividad->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nombre">Nombre<span style="color:red">*</span></label>
                                    <input type="text"
                                           name="nombre"
                                           id="nombre"
                                           class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre', auth()->user()->name ?? $actividad->nombre ?? '') }}"
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
                                        <option value="" disabled {{ old('actividad_categoria_id', $actividad->actividad_categoria_id) ? '' : 'selected' }}>Seleccione...</option>
                                        @foreach ($categorias as $c)
                                            <option value="{{ $c->id }}" {{ (string) old('actividad_categoria_id', $actividad->actividad_categoria_id) === (string) $c->id ? 'selected' : '' }}>
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
                        <input type="hidden" name="destacamento_id" value="{{ old('destacamento_id', $actividad->destacamento_id) }}">

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha">Fecha<span style="color:red">*</span></label>
                                    <input type="date"
                                           name="fecha"
                                           id="fecha"
                                           class="form-control @error('fecha') is-invalid @enderror"
                                           value="{{ old('fecha', optional($actividad->fecha)->format('Y-m-d') ?? $actividad->fecha) }}"
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
                                           value="{{ old('hora', $actividad->hora ? \Illuminate\Support\Str::of($actividad->hora)->substr(0,5) : '') }}">
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
                                           value="{{ old('lugar', $actividad->lugar) }}"
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
                                           value="{{ old('municipio', $actividad->municipio ?? 'MORELIA') }}"
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
                                           value="{{ old('carretera', $actividad->carretera) }}">
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
                                           value="{{ old('tramo', $actividad->tramo) }}">
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
                                           value="{{ old('kilometro', $actividad->kilometro) }}"
                                           placeholder="Ej. KM 12+500">
                                    @error('kilometro')
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
                                    <label for="lat">Latitud</label>
                                    <input type="number"
                                           step="0.0000001"
                                           name="lat"
                                           id="lat"
                                           class="form-control @error('lat') is-invalid @enderror"
                                           value="{{ old('lat', $actividad->lat) }}"
                                           placeholder="19.7000000">
                                    @error('lat')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="lng">Longitud</label>
                                    <input type="number"
                                           step="0.0000001"
                                           name="lng"
                                           id="lng"
                                           class="form-control @error('lng') is-invalid @enderror"
                                           value="{{ old('lng', $actividad->lng) }}"
                                           placeholder="-101.1900000">
                                    @error('lng')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fuente_ubicacion">Fuente ubicación</label>
                                    <select name="fuente_ubicacion"
                                            id="fuente_ubicacion"
                                            class="form-control @error('fuente_ubicacion') is-invalid @enderror">
                                        <option value="">Seleccione...</option>
                                        <option value="GPS_APP" {{ old('fuente_ubicacion', $actividad->fuente_ubicacion) == 'GPS_APP' ? 'selected' : '' }}>GPS_APP</option>
                                        <option value="GPS_WEB" {{ old('fuente_ubicacion', $actividad->fuente_ubicacion) == 'GPS_WEB' ? 'selected' : '' }}>GPS_WEB</option>
                                        <option value="MANUAL" {{ old('fuente_ubicacion', $actividad->fuente_ubicacion) == 'MANUAL' ? 'selected' : '' }}>MANUAL</option>
                                        <option value="REFERENCIA" {{ old('fuente_ubicacion', $actividad->fuente_ubicacion) == 'REFERENCIA' ? 'selected' : '' }}>REFERENCIA</option>
                                    </select>
                                    @error('fuente_ubicacion')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nota_geo">Nota geo</label>
                                    <input type="text"
                                           name="nota_geo"
                                           id="nota_geo"
                                           class="form-control @error('nota_geo') is-invalid @enderror"
                                           value="{{ old('nota_geo', $actividad->nota_geo) }}"
                                           placeholder="Ej. ACC:5.2">
                                    @error('nota_geo')
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
                                    <label for="coordenadas_texto">Coordenadas / referencia</label>
                                    <textarea name="coordenadas_texto"
                                              id="coordenadas_texto"
                                              rows="2"
                                              class="form-control @error('coordenadas_texto') is-invalid @enderror"
                                              placeholder="Ej. 19.7000000, -101.1900000 o referencia de ubicación">{{ old('coordenadas_texto', $actividad->coordenadas_texto) }}</textarea>
                                    @error('coordenadas_texto')
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
                                    <label for="motivo">Qué ocasiona / motivo</label>
                                    <textarea name="motivo"
                                              id="motivo"
                                              rows="3"
                                              class="form-control @error('motivo') is-invalid @enderror"
                                              placeholder="Ej. CORTE DE CIRCULACIÓN POR MANIFESTACIÓN">{{ old('motivo', $actividad->motivo) }}</textarea>
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
                                              placeholder="Describa lo ocurrido">{{ old('narrativa', $actividad->narrativa) }}</textarea>
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
                                              placeholder="Acciones realizadas por el personal">{{ old('acciones_realizadas', $actividad->acciones_realizadas) }}</textarea>
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
                                              placeholder="Observaciones adicionales">{{ old('observaciones', $actividad->observaciones) }}</textarea>
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
                                           value="{{ old('personas_alcanzadas', $actividad->personas_alcanzadas ?? 0) }}">
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
                                           value="{{ old('personas_participantes', $actividad->personas_participantes ?? 0) }}">
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
                                           value="{{ old('personas_detenidas', $actividad->personas_detenidas ?? 0) }}">
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
                                              placeholder="Ej. OF. JUAN PÉREZ, OF. LUIS GARCÍA">{{ old('elementos_participantes_texto', $actividad->elementos_participantes_texto) }}</textarea>
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
                                              placeholder="Ej. 3214, 3178, 04-174">{{ old('patrullas_participantes_texto', $actividad->patrullas_participantes_texto) }}</textarea>
                                    @error('patrullas_participantes_texto')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        @php
                            $fotosActuales = $actividad->relationLoaded('fotos')
                                ? $actividad->fotos
                                : $actividad->fotos()->orderBy('orden')->orderBy('id')->get();

                            if ($fotosActuales->isEmpty() && !empty($actividad->foto_path)) {
                                $fotosActuales = collect([
                                    (object) [
                                        'id' => 'legacy',
                                        'foto_path' => $actividad->foto_path,
                                        'foto_nombre_original' => $actividad->foto_nombre_original,
                                        'orden' => 0,
                                    ]
                                ]);
                            }
                        @endphp

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Fotos actuales</label>

                                    <div class="preview-grid" style="{{ $fotosActuales->count() ? '' : 'display:none;' }}">
                                        @foreach ($fotosActuales as $foto)
                                            @php
                                                $fotoUrl = asset('storage/' . ltrim($foto->foto_path, '/'));
                                                $fotoNombre = $foto->foto_nombre_original ?: ('Foto ' . ($loop->iteration));
                                            @endphp
                                            <div class="preview-item">
                                                <a href="{{ $fotoUrl }}" target="_blank" rel="noopener">
                                                    <img src="{{ $fotoUrl }}" class="foto-thumb" alt="{{ $fotoNombre }}">
                                                </a>
                                                <div class="preview-label">{{ $fotoNombre }}</div>
                                            </div>
                                        @endforeach
                                    </div>

                                    @if ($fotosActuales->isEmpty())
                                        <small class="help-muted">No hay fotos registradas actualmente.</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="fotos">Agregar nuevas fotos</label>
                                    <input type="file"
                                           name="fotos[]"
                                           id="fotos"
                                           accept="image/*"
                                           multiple
                                           class="form-control @error('fotos') is-invalid @enderror @error('fotos.*') is-invalid @enderror">

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
                                        <i class="fa-solid fa-save"></i> Guardar cambios
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
            display: block;
        }

        .preview-label {
            margin-top: 4px;
            font-size: 12px;
            color: rgba(234,240,255,.75);
            word-break: break-word;
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

            const fotoInput = document.getElementById('fotos');
            const fotoName = document.getElementById('foto_name');
            const previewWrap = document.getElementById('preview_wrap');

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

                    const oldSub = @json(old('actividad_subcategoria_id', $actividad->actividad_subcategoria_id));
                    if (oldSub !== null && oldSub !== '') {
                        subcatSelect.value = String(oldSub);
                    } else {
                        subcatSelect.value = '';
                    }
                } catch (e) {
                    setSubcatBase('No hay subcategorías para esta categoría');
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

                const oldCat = @json(old('actividad_categoria_id', $actividad->actividad_categoria_id));
                const initialCat = oldCat || categoriaSelect.value || '';

                if (initialCat) {
                    cargarSubcategorias(initialCat);
                } else {
                    setSubcatDisabled('Seleccione una categoría primero...');
                }
            }

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
