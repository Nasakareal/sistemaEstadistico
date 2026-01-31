{{-- resources/views/actividades/edit.blade.php --}}

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

                        <!-- Nombre, Categoría, Subcategoría -->
                        <div class="row">
                            <!-- Nombre (AUTOMÁTICO / NO EDITABLE) -->
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

                            <!-- Categoría -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="actividad_categoria_id">Categoría<span style="color:red">*</span></label>
                                    <select name="actividad_categoria_id"
                                            id="actividad_categoria_id"
                                            class="form-control @error('actividad_categoria_id') is-invalid @enderror"
                                            required>
                                        <option value="" disabled {{ old('actividad_categoria_id', $actividad->actividad_categoria_id) ? '' : 'selected' }}>Seleccione...</option>

                                        @foreach ($categorias as $c)
                                            <option value="{{ $c->id }}"
                                                {{ (string)old('actividad_categoria_id', $actividad->actividad_categoria_id) === (string)$c->id ? 'selected' : '' }}>
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

                            <!-- Subcategoría (dependiente) -->
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

                        <!-- Cantidad fija (1) -->
                        <input type="hidden" name="cantidad" id="cantidad" value="1">

                        <div class="row">
                            <!-- Foto (OBLIGATORIA en edit también) -->
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

                                    <div class="preview-wrap">
                                        @php
                                            $fotoPathActual = $actividad->foto_path ?? null;
                                            $urlFoto = $fotoPathActual ? asset('storage/' . ltrim($fotoPathActual, '/')) : null;
                                        @endphp

                                        @if ($urlFoto)
                                            <a href="{{ $urlFoto }}" target="_blank" rel="noopener">
                                                <img src="{{ $urlFoto }}" alt="foto" class="foto-thumb">
                                            </a>
                                            <div>
                                                <small class="help-muted">Foto actual</small><br>
                                                <small id="foto_name" class="help-muted"></small>
                                            </div>
                                        @else
                                            <small class="text-muted">Sin foto</small>
                                            <small id="foto_name" class="help-muted d-block"></small>
                                        @endif
                                    </div>

                                    <small class="help-muted d-block mt-1">Debe seleccionar una foto para guardar.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Submit y Cancelar -->
                        <hr>
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-success">
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
        .help-muted { color: rgba(234,240,255,.65); }

        .form-group label {
            font-weight: bold;
            color: #eaf0ff;
        }

        .form-control,
        select.form-control {
            color: #eaf0ff;
            background-color: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 12px;
        }

        .form-control::placeholder {
            color: rgba(234,240,255,.55);
        }

        select option {
            color: #111 !important;
            background-color: #ffffff !important;
        }

        .form-control:focus,
        select:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(45,168,255,.35);
            border-color: rgba(45,168,255,.55);
        }

        .preview-wrap{
            display:flex;
            align-items:center;
            gap:10px;
            margin-top:8px;
        }
        .foto-thumb{
            width: 92px;
            height: 64px;
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

            function setSubcatDisabled(msg) {
                subcatSelect.disabled = true;
                subcatSelect.innerHTML = `<option value="" selected>${msg}</option>`;
            }

            function setSubcatBase(msgOpcional) {
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
                    const url = `{{ route('actividades.subcategorias', ['categoria' => '__ID__']) }}`
                        .replace('__ID__', encodeURIComponent(categoriaId));

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

                    const selected = "{{ old('actividad_subcategoria_id', $actividad->actividad_subcategoria_id) }}";
                    if (selected !== null && selected !== undefined && selected !== '') {
                        subcatSelect.value = selected;
                    } else {
                        subcatSelect.value = '';
                    }

                } catch (e) {
                    setSubcatBase('No hay subcategorías para esta categoría');
                }
            }

            // Bloquea cualquier intento de editar el nombre
            if (nombreInput) {
                nombreInput.addEventListener('input', function () {
                    if (this.value !== nombreActual) this.value = nombreActual;
                });
                nombreInput.addEventListener('keydown', function (e) {
                    if (e.key !== 'Tab') e.preventDefault();
                });
            }

            if (categoriaSelect && subcatSelect) {
                categoriaSelect.addEventListener('change', function () {
                    cargarSubcategorias(this.value);
                });

                const oldCat = "{{ old('actividad_categoria_id') }}";
                const initialCat = oldCat || categoriaSelect.value || '';
                if (initialCat) {
                    cargarSubcategorias(initialCat);
                } else {
                    setSubcatDisabled('Seleccione una categoría primero...');
                }
            }

            if (fotoInput) {
                fotoInput.addEventListener('change', function () {
                    const f = fotoInput.files && fotoInput.files[0] ? fotoInput.files[0].name : '';
                    if (fotoName) {
                        fotoName.textContent = f ? ('Archivo: ' + f) : '';
                    }
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
