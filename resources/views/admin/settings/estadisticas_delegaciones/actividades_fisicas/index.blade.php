@extends('adminlte::page')

@section('title', 'Actividades Físicas Delegaciones')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Delegaciones · Actividades Físicas</span>
            </div>

            <div class="sv-hero__title">
                Actividades Físicas
            </div>

            <div class="sv-hero__subtitle">
                Registros propios de delegaciones con foto, tipo de ejercicio y elementos participantes
            </div>
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-success">
                    <i class="fa-solid fa-person-running"></i>
                </div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Registros</div>
                    <div class="sv-kpi__value">{{ number_format($resumen['actividades']) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-info">
                    <i class="fa-solid fa-camera"></i>
                </div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Con foto</div>
                    <div class="sv-kpi__value">{{ number_format($resumen['con_foto']) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-primary">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Elementos</div>
                    <div class="sv-kpi__value">{{ number_format($resumen['elementos']) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-warning">
                    <i class="fa-solid fa-building-shield"></i>
                </div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Delegaciones</div>
                    <div class="sv-kpi__value">{{ number_format($resumen['delegaciones']) }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($puedeCapturar)
        <div class="sv-panel">
            <div class="sv-panel__title">
                <i class="fa-solid fa-plus"></i>
                <span>Nuevo registro</span>
            </div>

            <form method="POST" action="{{ route('settings.estadisticas_delegaciones.actividades_fisicas.store') }}" enctype="multipart/form-data" class="sv-form actividad-form">
                @csrf

                <div class="sv-form__row actividad-form__row">
                    <div class="sv-field actividad-form__delegacion">
                        <label for="delegacion_id">Delegación</label>
                        <select name="delegacion_id" id="delegacion_id" class="form-control" required>
                            <option value="">Seleccione...</option>
                            @foreach ($delegaciones as $delegacion)
                                <option value="{{ $delegacion->id }}" {{ (string) old('delegacion_id', $delegacionId ?: '') === (string) $delegacion->id ? 'selected' : '' }}>
                                    {{ $delegacion->nombre_con_clave }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sv-field">
                        <label for="fecha">Fecha</label>
                        <input type="date" name="fecha" id="fecha" class="form-control" value="{{ old('fecha', now('America/Mexico_City')->toDateString()) }}">
                    </div>

                    <div class="sv-field">
                        <label for="hora">Hora</label>
                        <input type="time" name="hora" id="hora" class="form-control" value="{{ old('hora', now('America/Mexico_City')->format('H:i')) }}">
                    </div>

                    <div class="sv-field actividad-form__tipo">
                        <label for="tipo_ejercicio_input">Tipo de ejercicio</label>
                        <input type="text" name="tipo_ejercicio" id="tipo_ejercicio_input" class="form-control" value="{{ old('tipo_ejercicio') }}" list="tipos_ejercicio_opciones" maxlength="180" required>
                        <datalist id="tipos_ejercicio_opciones">
                            @foreach ($tiposEjercicio as $tipo)
                                <option value="{{ $tipo }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="sv-field">
                        <label for="elementos_participantes">Elementos participantes</label>
                        <input type="number" name="elementos_participantes" id="elementos_participantes" class="form-control" min="0" max="999" value="{{ old('elementos_participantes', 0) }}" required>
                    </div>

                    <div class="sv-field actividad-form__foto">
                        <label for="foto">Foto</label>
                        <input type="file" name="foto" id="foto" class="form-control" accept="image/*" required>
                    </div>

                    <div class="sv-field actividad-form__submit">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn sv-btn w-100">
                            <i class="fa-solid fa-check"></i> Guardar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <div class="sv-panel">
        <div class="sv-panel__title">
            <i class="fa-solid fa-filter"></i>
            <span>Filtros</span>
        </div>

        <form method="GET" action="{{ route('settings.estadisticas_delegaciones.actividades_fisicas') }}" class="sv-form actividad-filtros">
            <div class="sv-form__row actividad-filtros__row">
                <div class="sv-field">
                    <label for="fecha_inicio">Desde</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="{{ $fechaInicio }}">
                </div>

                <div class="sv-field">
                    <label for="fecha_fin">Hasta</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="{{ $fechaFin }}">
                </div>

                <div class="sv-field actividad-filtros__delegacion">
                    <label for="delegacion_id_filtro">Delegación</label>
                    <select name="delegacion_id" id="delegacion_id_filtro" class="form-control">
                        <option value="">Todas</option>
                        @foreach ($delegaciones as $delegacion)
                            <option value="{{ $delegacion->id }}" {{ (int) $delegacionId === (int) $delegacion->id ? 'selected' : '' }}>
                                {{ $delegacion->nombre_con_clave }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sv-field actividad-filtros__tipo">
                    <label for="tipo_ejercicio">Tipo de ejercicio</label>
                    <select name="tipo_ejercicio" id="tipo_ejercicio" class="form-control">
                        <option value="">Todos</option>
                        @foreach ($tiposEjercicio as $tipo)
                            <option value="{{ $tipo }}" {{ $tipoEjercicio === $tipo ? 'selected' : '' }}>
                                {{ $tipo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sv-field actividad-filtros__buscar">
                    <label for="buscar">Buscar</label>
                    <input type="text" name="buscar" id="buscar" class="form-control" value="{{ $buscar }}" placeholder="Delegación o tipo">
                </div>

                <div class="sv-field actividad-filtros__acciones">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn sv-btn">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    <a href="{{ route('settings.estadisticas_delegaciones.actividades_fisicas') }}" class="btn sv-btn sv-btn--ghost">
                        <i class="fa-solid fa-rotate-left"></i> Limpiar
                    </a>
                    <a href="{{ route('settings.estadisticas_delegaciones.index') }}" class="btn sv-btn sv-btn--ghost">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="sv-panel">
        <div class="sv-panel__title">
            <i class="fa-solid fa-list"></i>
            <span>Registros</span>
        </div>

        <div class="table-responsive actividad-table-wrap">
            <table class="table table-sm table-hover mb-0 actividad-table">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Fecha</th>
                        <th>Delegación</th>
                        <th>Tipo de ejercicio</th>
                        <th>Elementos participantes</th>
                        <th>Capturó</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($actividades as $actividad)
                        @php
                            $fotoUrl = $actividad->foto_path ? asset('storage/' . ltrim($actividad->foto_path, '/')) : null;
                        @endphp
                        <tr>
                            <td>
                                @if ($fotoUrl)
                                    <a href="{{ $fotoUrl }}" target="_blank" rel="noopener">
                                        <img src="{{ $fotoUrl }}" alt="Foto actividad física" class="actividad-foto">
                                    </a>
                                @else
                                    <span class="text-muted">Sin foto</span>
                                @endif
                            </td>
                            <td>
                                <div class="actividad-main">
                                    {{ $actividad->fecha ? $actividad->fecha->format('d/m/Y') : 'N/A' }}
                                </div>
                                <small>{{ $actividad->hora ? \Illuminate\Support\Str::of($actividad->hora)->substr(0, 5) : 'N/A' }}</small>
                            </td>
                            <td>
                                <div class="actividad-main">
                                    {{ optional($actividad->delegacion)->nombre_con_clave ?: 'Sin delegación' }}
                                </div>
                                @if (optional(optional($actividad->delegacion)->padre)->nombre)
                                    <small>{{ $actividad->delegacion->padre->nombre }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="actividad-chip">{{ $actividad->tipo_ejercicio }}</span>
                            </td>
                            <td>
                                <span class="actividad-count">{{ number_format((int) $actividad->elementos_participantes) }}</span>
                            </td>
                            <td>
                                {{ optional($actividad->creador)->name ?: 'N/A' }}
                                <small>{{ optional($actividad->created_at)->timezone('America/Mexico_City')->format('d/m/Y H:i') }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                No hay actividades físicas registradas con esos filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($actividades->hasPages())
            <div class="actividad-pagination">
                {{ $actividades->onEachSide(1)->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/sv-dashboard.css') }}">
<style>
    .actividad-form__row,
    .actividad-filtros__row {
        grid-template-columns: repeat(12, minmax(0, 1fr));
        align-items: end;
    }

    .actividad-form__delegacion,
    .actividad-filtros__delegacion {
        grid-column: span 3;
    }

    .actividad-form__tipo,
    .actividad-filtros__tipo,
    .actividad-filtros__buscar {
        grid-column: span 2;
    }

    .actividad-form__foto {
        grid-column: span 2;
    }

    .actividad-form__submit {
        grid-column: span 1;
    }

    .actividad-filtros__acciones {
        grid-column: span 3;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .actividad-filtros__acciones .btn {
        flex: 1 1 auto;
    }

    .actividad-table-wrap {
        padding: 0 12px 12px;
    }

    .actividad-table th,
    .actividad-table td {
        vertical-align: middle;
    }

    .actividad-foto {
        width: 108px;
        height: 78px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,.14);
        background: rgba(255,255,255,.06);
    }

    .actividad-main {
        font-weight: 900;
        color: var(--sv-text);
    }

    .actividad-table small {
        display: block;
        margin-top: 3px;
        color: var(--sv-muted);
        font-weight: 700;
    }

    .actividad-chip {
        display: inline-flex;
        align-items: center;
        padding: 5px 9px;
        border-radius: 8px;
        color: #dbeafe;
        background: rgba(37, 99, 235, .22);
        border: 1px solid rgba(96, 165, 250, .28);
        font-weight: 850;
    }

    .actividad-count {
        display: inline-grid;
        place-items: center;
        min-width: 44px;
        height: 36px;
        padding: 0 10px;
        border-radius: 8px;
        color: #dcfce7;
        background: rgba(22, 163, 74, .24);
        border: 1px solid rgba(74, 222, 128, .30);
        font-weight: 950;
        font-size: 16px;
    }

    .actividad-pagination {
        padding: 12px 14px 14px;
        border-top: 1px solid rgba(255,255,255,.08);
    }

    @media (max-width: 992px) {
        .actividad-form__delegacion,
        .actividad-form__tipo,
        .actividad-form__foto,
        .actividad-form__submit,
        .actividad-filtros__delegacion,
        .actividad-filtros__tipo,
        .actividad-filtros__buscar,
        .actividad-filtros__acciones,
        .actividad-form__row .sv-field,
        .actividad-filtros__row .sv-field {
            grid-column: span 12;
        }
    }
</style>
@stop
