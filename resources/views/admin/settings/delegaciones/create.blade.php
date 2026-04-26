@extends('adminlte::page')

@section('title', 'Crear Delegación')

@section('content_header')
    <h1>Crear Delegación</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Nueva Delegación</h3>
                    <div class="card-tools">
                        <a href="{{ route('delegaciones.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <form action="{{ route('delegaciones.store') }}" method="POST" autocomplete="off">
                    @csrf

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
                                           value="{{ old('clave') }}"
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
                                           value="{{ old('nombre') }}"
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
                                           value="{{ old('municipio') }}"
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
                                           value="{{ old('lat') }}"
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
                                           value="{{ old('lng') }}"
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
                                                {{ old('delegacion_padre_id') == $p->id ? 'selected' : '' }}>
                                                {{ $p->nombre_con_clave ?? $p->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('delegacion_padre_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    <small class="text-muted">
                                        Si eliges un padre, esta delegación se guardará como hija de esa delegación.
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
                                               {{ old('activa', '1') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="activa">Activa</label>
                                    </div>
                                    @error('activa') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        {{-- HIJAS --}}
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h5 class="mb-0">
                                Delegaciones hijas (opcional)
                            </h5>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn_add_hija">
                                <i class="fa-solid fa-plus"></i> Agregar hija
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm" id="tabla_hijas">
                                <thead>
                                    <tr>
                                        <th style="width: 14%">Clave</th>
                                        <th style="width: 24%">Nombre</th>
                                        <th style="width: 22%">Municipio</th>
                                        <th style="width: 15%">Latitud</th>
                                        <th style="width: 15%">Longitud</th>
                                        <th style="width: 5%; text-align:center">Activa</th>
                                        <th style="width: 5%; text-align:center">Quitar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $oldHijas = old('hijas', []);
                                    @endphp

                                    @if (!empty($oldHijas))
                                        @foreach ($oldHijas as $i => $h)
                                            <tr>
                                                <td>
                                                    <input type="text"
                                                           name="hijas[{{ $i }}][clave]"
                                                           class="form-control"
                                                           value="{{ $h['clave'] ?? '' }}"
                                                           placeholder="Ej: SD-01">
                                                </td>
                                                <td>
                                                    <input type="text"
                                                           name="hijas[{{ $i }}][nombre]"
                                                           class="form-control"
                                                           value="{{ $h['nombre'] ?? '' }}"
                                                           placeholder="Nombre hija">
                                                </td>
                                                <td>
                                                    <input type="text"
                                                           name="hijas[{{ $i }}][municipio]"
                                                           class="form-control"
                                                           value="{{ $h['municipio'] ?? '' }}"
                                                           placeholder="Municipio">
                                                </td>
                                                <td>
                                                    <input type="text"
                                                           name="hijas[{{ $i }}][lat]"
                                                           class="form-control"
                                                           value="{{ $h['lat'] ?? '' }}"
                                                           placeholder="19.7033333">
                                                </td>
                                                <td>
                                                    <input type="text"
                                                           name="hijas[{{ $i }}][lng]"
                                                           class="form-control"
                                                           value="{{ $h['lng'] ?? '' }}"
                                                           placeholder="-101.1922222">
                                                </td>
                                                <td style="text-align:center; vertical-align:middle;">
                                                    <input type="checkbox"
                                                           name="hijas[{{ $i }}][activa]"
                                                           value="1"
                                                           {{ !empty($h['activa']) ? 'checked' : '' }}>
                                                </td>
                                                <td style="text-align:center; vertical-align:middle;">
                                                    <button type="button" class="btn btn-danger btn-sm btn_remove_row">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>

                            <small class="text-muted">
                                Puedes crear subdelegaciones aquí mismo. Si no necesitas hijas, deja la tabla vacía.
                            </small>
                        </div>

                    </div>

                    <div class="card-footer">
                        @can('crear delegaciones')
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Guardar
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
        .table th, .table td {
            vertical-align: middle;
        }
    </style>
@stop

@section('js')
    <script>
        (function () {
            let hijaIndex = (function () {
                const rows = document.querySelectorAll('#tabla_hijas tbody tr');
                return rows.length ? rows.length : 0;
            })();

            function makeRow(i) {
                return `
                    <tr>
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
                if (e.target.closest('.btn_remove_row')) {
                    e.preventDefault();
                    const tr = e.target.closest('tr');
                    tr.remove();
                }
            });
        })();
    </script>
@stop
