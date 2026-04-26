@extends('adminlte::page')

@section('title', 'Editar Delegación')

@section('content_header')
    <h1>Editar Delegación</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        Editar: {{ $delegacion->nombre }}
                    </h3>

                    <div class="card-tools">
                        <a href="{{ route('delegaciones.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>

                        @can('ver delegaciones')
                            <a href="{{ route('delegaciones.show', $delegacion) }}" class="btn btn-info btn-sm">
                                <i class="fa-regular fa-eye"></i> Ver
                            </a>
                        @endcan
                    </div>
                </div>

                <form action="{{ route('delegaciones.update', $delegacion) }}" method="POST" autocomplete="off">
                    @csrf
                    @method('PUT')

                    <div class="card-body">

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>Hay errores en el formulario:</strong>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- DATOS PRINCIPALES --}}
                        <div class="row">

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Clave</label>
                                    <input type="text"
                                           name="clave"
                                           class="form-control @error('clave') is-invalid @enderror"
                                           value="{{ old('clave', $delegacion->clave) }}"
                                           placeholder="Ej: D-01">
                                    @error('clave') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>Nombre <span class="text-danger">*</span></label>
                                    <input type="text"
                                           name="nombre"
                                           class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre', $delegacion->nombre) }}"
                                           placeholder="Nombre de la delegación">
                                    @error('nombre') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Municipio</label>
                                    <input type="text"
                                           name="municipio"
                                           class="form-control @error('municipio') is-invalid @enderror"
                                           value="{{ old('municipio', $delegacion->municipio) }}"
                                           placeholder="Municipio">
                                    @error('municipio') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Latitud</label>
                                    <input type="text"
                                           name="lat"
                                           class="form-control @error('lat') is-invalid @enderror"
                                           value="{{ old('lat', $delegacion->lat) }}"
                                           placeholder="Ej: 19.7033333">
                                    @error('lat') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Longitud</label>
                                    <input type="text"
                                           name="lng"
                                           class="form-control @error('lng') is-invalid @enderror"
                                           value="{{ old('lng', $delegacion->lng) }}"
                                           placeholder="Ej: -101.1922222">
                                    @error('lng') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Delegación padre (opcional)</label>
                                    <select name="delegacion_padre_id"
                                            class="form-control @error('delegacion_padre_id') is-invalid @enderror">
                                        <option value="">— Sin padre —</option>
                                        @foreach ($delegacionesPadre as $p)
                                            <option value="{{ $p->id }}"
                                                {{ (string)old('delegacion_padre_id', $delegacion->delegacion_padre_id) === (string)$p->id ? 'selected' : '' }}>
                                                {{ $p->nombre_con_clave ?? $p->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('delegacion_padre_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    <small class="text-muted">
                                        Si eliges un padre, esta delegación quedará como hija de esa delegación.
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Estatus</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="activa"
                                               name="activa"
                                               value="1"
                                               {{ old('activa', $delegacion->activa ? '1' : '') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="activa">Activa</label>
                                    </div>
                                    @error('activa') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>

                        </div>

                        <hr>

                        {{-- HIJAS --}}
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h5 class="mb-0">Delegaciones hijas</h5>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn_add_hija">
                                <i class="fa-solid fa-plus"></i> Agregar hija
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm" id="tabla_hijas">
                                <thead>
                                    <tr>
                                        <th style="width: 10%">Clave</th>
                                        <th style="width: 22%">Nombre</th>
                                        <th style="width: 20%">Municipio</th>
                                        <th style="width: 14%">Latitud</th>
                                        <th style="width: 14%">Longitud</th>
                                        <th style="width: 7%; text-align:center">Activa</th>
                                        <th style="width: 7%; text-align:center">Eliminar</th>
                                        <th style="width: 6%; text-align:center">Quitar fila</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @php
                                        $oldHijas = old('hijas', null);
                                        $rows = is_array($oldHijas) ? $oldHijas : $delegacion->hijas->map(function ($h) {
                                            return [
                                                'id' => $h->id,
                                                'clave' => $h->clave,
                                                'nombre' => $h->nombre,
                                                'municipio' => $h->municipio,
                                                'lat' => $h->lat,
                                                'lng' => $h->lng,
                                                'activa' => $h->activa ? 1 : 0,
                                            ];
                                        })->values()->all();

                                        $oldDelete = old('hijas_delete', []);
                                        if (!is_array($oldDelete)) $oldDelete = [];
                                    @endphp

                                    @foreach ($rows as $i => $h)
                                        @php
                                            $rowId = $h['id'] ?? null;
                                            $isDeleted = $rowId && in_array((string)$rowId, array_map('strval', $oldDelete), true);
                                        @endphp

                                        <tr class="{{ $isDeleted ? 'table-danger' : '' }}">

                                            <input type="hidden" name="hijas[{{ $i }}][id]" value="{{ $rowId }}">

                                            <td>
                                                <input type="text"
                                                       name="hijas[{{ $i }}][clave]"
                                                       class="form-control"
                                                       value="{{ $h['clave'] ?? '' }}"
                                                       placeholder="Ej: SD-01"
                                                       {{ $isDeleted ? 'disabled' : '' }}>
                                            </td>

                                            <td>
                                                <input type="text"
                                                       name="hijas[{{ $i }}][nombre]"
                                                       class="form-control"
                                                       value="{{ $h['nombre'] ?? '' }}"
                                                       placeholder="Nombre hija"
                                                       {{ $isDeleted ? 'disabled' : '' }}>
                                            </td>

                                            <td>
                                                <input type="text"
                                                       name="hijas[{{ $i }}][municipio]"
                                                       class="form-control"
                                                       value="{{ $h['municipio'] ?? '' }}"
                                                       placeholder="Municipio"
                                                       {{ $isDeleted ? 'disabled' : '' }}>
                                            </td>

                                            <td>
                                                <input type="text"
                                                       name="hijas[{{ $i }}][lat]"
                                                       class="form-control"
                                                       value="{{ $h['lat'] ?? '' }}"
                                                       placeholder="19.7033333"
                                                       {{ $isDeleted ? 'disabled' : '' }}>
                                            </td>

                                            <td>
                                                <input type="text"
                                                       name="hijas[{{ $i }}][lng]"
                                                       class="form-control"
                                                       value="{{ $h['lng'] ?? '' }}"
                                                       placeholder="-101.1922222"
                                                       {{ $isDeleted ? 'disabled' : '' }}>
                                            </td>

                                            <td style="text-align:center; vertical-align:middle;">
                                                <input type="checkbox"
                                                       name="hijas[{{ $i }}][activa]"
                                                       value="1"
                                                       {{ !empty($h['activa']) ? 'checked' : '' }}
                                                       {{ $isDeleted ? 'disabled' : '' }}>
                                            </td>

                                            <td style="text-align:center; vertical-align:middle;">
                                                @if ($rowId)
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox"
                                                               class="custom-control-input chk_delete_hija"
                                                               id="del_{{ $rowId }}"
                                                               data-hija-id="{{ $rowId }}"
                                                               {{ $isDeleted ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="del_{{ $rowId }}">
                                                            Eliminar
                                                        </label>
                                                    </div>
                                                @else
                                                    —
                                                @endif
                                            </td>

                                            <td style="text-align:center; vertical-align:middle;">
                                                <button type="button" class="btn btn-danger btn-sm btn_remove_row">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach

                                </tbody>
                            </table>

                            <div id="hijas_delete_container"></div>

                            <small class="text-muted">
                                Puedes editar hijas, agregar nuevas, o marcar hijas existentes para eliminar.
                            </small>
                        </div>

                    </div>

                    <div class="card-footer">
                        @can('editar delegaciones')
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Guardar cambios
                            </button>
                        @endcan

                        <a href="{{ route('delegaciones.index') }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                    </div>

                </form>
            </div>

        </div>
    </div>
@stop

@section('css')
    <style>
        .table th, .table td { vertical-align: middle; }
        .table-danger input[disabled] { background: #f8d7da; }
    </style>
@stop

@section('js')
    <script>
        (function () {

            let hijaIndex = (function () {
                const rows = document.querySelectorAll('#tabla_hijas tbody tr');
                return rows.length ? rows.length : 0;
            })();

            const deleteContainer = document.getElementById('hijas_delete_container');

            function ensureDeleteHidden(id) {
                const exists = deleteContainer.querySelector(`input[name="hijas_delete[]"][value="${id}"]`);
                if (!exists) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'hijas_delete[]';
                    input.value = id;
                    deleteContainer.appendChild(input);
                }
            }

            function removeDeleteHidden(id) {
                const el = deleteContainer.querySelector(`input[name="hijas_delete[]"][value="${id}"]`);
                if (el) el.remove();
            }

            @php
                $oldDelete = old('hijas_delete', []);
                if (!is_array($oldDelete)) $oldDelete = [];
            @endphp
            const initialDeletes = @json(array_values($oldDelete));
            initialDeletes.forEach(id => ensureDeleteHidden(id));

            function makeRow(i) {
                return `
                    <tr>
                        <input type="hidden" name="hijas[${i}][id]" value="">
                        <td>
                            <input type="text" name="hijas[${i}][clave]" class="form-control" placeholder="Ej: SD-01">
                        </td>
                        <td>
                            <input type="text" name="hijas[${i}][nombre]" class="form-control" placeholder="Nombre hija">
                        </td>
                        <td>
                            <input type="text" name="hijas[${i}][municipio]" class="form-control" placeholder="Municipio">
                        </td>
                        <td>
                            <input type="text" name="hijas[${i}][lat]" class="form-control" placeholder="19.7033333">
                        </td>
                        <td>
                            <input type="text" name="hijas[${i}][lng]" class="form-control" placeholder="-101.1922222">
                        </td>
                        <td style="text-align:center; vertical-align:middle;">
                            <input type="checkbox" name="hijas[${i}][activa]" value="1" checked>
                        </td>
                        <td style="text-align:center; vertical-align:middle;">
                            —
                        </td>
                        <td style="text-align:center; vertical-align:middle;">
                            <button type="button" class="btn btn-danger btn-sm btn_remove_row">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }

            document.getElementById('btn_add_hija').addEventListener('click', function () {
                const tbody = document.querySelector('#tabla_hijas tbody');
                tbody.insertAdjacentHTML('beforeend', makeRow(hijaIndex));
                hijaIndex++;
            });

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn_remove_row');
                if (!btn) return;

                e.preventDefault();
                const tr = btn.closest('tr');

                const chk = tr.querySelector('.chk_delete_hija');
                if (chk) {
                    const id = chk.getAttribute('data-hija-id');
                    removeDeleteHidden(id);
                }

                tr.remove();
            });

            document.addEventListener('change', function (e) {
                const chk = e.target.closest('.chk_delete_hija');
                if (!chk) return;

                const id = chk.getAttribute('data-hija-id');
                const tr = chk.closest('tr');

                const inputs = tr.querySelectorAll('input[type="text"], input[type="checkbox"][name*="[activa]"]');

                if (chk.checked) {
                    ensureDeleteHidden(id);
                    tr.classList.add('table-danger');
                    inputs.forEach(inp => {
                        inp.disabled = true;
                    });
                } else {
                    removeDeleteHidden(id);
                    tr.classList.remove('table-danger');
                    inputs.forEach(inp => inp.disabled = false);
                }
            });

        })();
    </script>
@stop
