@extends('adminlte::page')

@section('title', 'Seguimiento de Hechos')

@section('content_header')
    <h1>Seguimiento de Hechos de Tránsito</h1>
@stop

@section('content')
    @php
        $usuario = auth()->user();
        $puedeVerTarjetaWhatsApp = $usuario && (
            $usuario->hasRole('Superadmin')
            || $hechos->getCollection()->contains(fn ($hecho) => \App\Support\HechoAccess::effectiveUnidadIdForHecho($hecho) === 2)
        );
        $periodoActual = strtoupper($periodo ?? 'SEMANA');
        $situacionActual = strtoupper($situacion ?? 'PENDIENTE');
        $unidadActual = (string) ($unidadFiltro ?? '');
        $puedeFiltrarUnidad = $puedeFiltrarUnidad ?? false;
        $puedeMostrarTodasSituaciones = $puedeMostrarTodasSituaciones ?? false;
        $unidadesFiltro = $unidadesFiltro ?? [
            '1' => 'Siniestros',
            '2' => 'Delegaciones',
            '4' => 'Carreteras',
        ];

        $labelsPeriodo = [
            'SEMANA' => 'Semana',
            'MES' => 'Mes',
            'ANIO' => 'Año',
        ];

        $labelsSituacion = [
            'TODOS' => 'Todos',
            'PENDIENTE' => 'Pendientes',
            'TURNADO' => 'Turnados',
            'RESUELTO' => 'Resueltos',
            'FALTA_COMPLETAR' => 'Falta completar',
        ];

        $clasesSituacion = [
            'TODOS' => 'primary',
            'PENDIENTE' => 'warning',
            'TURNADO' => 'info',
            'RESUELTO' => 'success',
            'FALTA_COMPLETAR' => 'danger',
        ];

        $iconosSituacion = [
            'TODOS' => 'fa-layer-group',
            'PENDIENTE' => 'fa-triangle-exclamation',
            'TURNADO' => 'fa-share',
            'RESUELTO' => 'fa-circle-check',
            'FALTA_COMPLETAR' => 'fa-clipboard-list',
        ];

        $unidadTexto = $unidadActual !== ''
            ? ($unidadesFiltro[$unidadActual] ?? 'Unidad seleccionada')
            : 'Todas las unidades visibles';

        $paramsUnidad = $unidadActual !== '' ? ['unidad_filtro' => $unidadActual] : [];
        $conteosPeriodo = $conteos[strtolower($periodoActual)] ?? [];
        $estadosFiltro = $puedeMostrarTodasSituaciones
            ? ['TODOS', 'PENDIENTE', 'TURNADO', 'RESUELTO', 'FALTA_COMPLETAR']
            : ['PENDIENTE', 'TURNADO', 'RESUELTO', 'FALTA_COMPLETAR'];
    @endphp

    <div class="card card-outline card-primary seguimiento-card">
        <div class="card-header seguimiento-card__header">
            <div>
                <h3 class="card-title mb-0">Panel de seguimiento</h3>
                <div class="seguimiento-card__sub">
                    {{ $labelsSituacion[$situacionActual] ?? $situacionActual }} · {{ $labelsPeriodo[$periodoActual] ?? $periodoActual }} · {{ $unidadTexto }}
                </div>
            </div>

            <div class="card-tools">
                <a href="{{ route('hechos.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-list"></i> Listado
                </a>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('hechos.seguimiento') }}" class="sv-filter-panel" autocomplete="off">
                <div class="sv-filter-grid">
                    <div>
                        <label for="periodo">Periodo</label>
                        <select name="periodo" id="periodo" class="form-control sv-input">
                            <option value="SEMANA" {{ $periodoActual === 'SEMANA' ? 'selected' : '' }}>Semana</option>
                            <option value="MES" {{ $periodoActual === 'MES' ? 'selected' : '' }}>Mes</option>
                            <option value="ANIO" {{ $periodoActual === 'ANIO' ? 'selected' : '' }}>Año</option>
                        </select>
                    </div>

                    <div>
                        <label for="situacion">Situación</label>
                        <select name="situacion" id="situacion" class="form-control sv-input">
                            @if($puedeMostrarTodasSituaciones)
                                <option value="TODOS" {{ $situacionActual === 'TODOS' ? 'selected' : '' }}>Mostrar todo</option>
                            @endif
                            <option value="PENDIENTE" {{ $situacionActual === 'PENDIENTE' ? 'selected' : '' }}>Pendientes</option>
                            <option value="TURNADO" {{ $situacionActual === 'TURNADO' ? 'selected' : '' }}>Turnados</option>
                            <option value="RESUELTO" {{ $situacionActual === 'RESUELTO' ? 'selected' : '' }}>Resueltos</option>
                            <option value="FALTA_COMPLETAR" {{ $situacionActual === 'FALTA_COMPLETAR' ? 'selected' : '' }}>Falta completar</option>
                        </select>
                    </div>

                    @if ($puedeFiltrarUnidad)
                        <div>
                            <label for="unidad_filtro">Unidad</label>
                            <select name="unidad_filtro" id="unidad_filtro" class="form-control sv-input">
                                <option value="">Todas</option>
                                @foreach ($unidadesFiltro as $unidadId => $unidadNombre)
                                    <option value="{{ $unidadId }}" {{ $unidadActual === (string) $unidadId ? 'selected' : '' }}>
                                        {{ $unidadNombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="sv-filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-filter"></i> Filtrar
                        </button>

                        <a href="{{ route('hechos.seguimiento') }}" class="btn btn-secondary">
                            <i class="fa-solid fa-rotate-left"></i> Restablecer
                        </a>
                    </div>
                </div>
            </form>

            <div class="sv-kpi-grid">
                @foreach ($estadosFiltro as $estado)
                    <a
                        class="sv-kpi sv-kpi--{{ strtolower($estado) }} {{ $situacionActual === $estado ? 'is-active' : '' }}"
                        href="{{ route('hechos.seguimiento', array_merge($paramsUnidad, ['periodo' => $periodoActual, 'situacion' => $estado])) }}"
                    >
                        <span class="sv-kpi__icon"><i class="fa-solid {{ $iconosSituacion[$estado] }}"></i></span>
                        <span class="sv-kpi__body">
                            <span class="sv-kpi__label">{{ $labelsSituacion[$estado] }}</span>
                            <span class="sv-kpi__value">{{ $conteosPeriodo[$estado] ?? 0 }}</span>
                        </span>
                    </a>
                @endforeach
            </div>

            <div class="sv-period-links">
                @foreach (['SEMANA', 'MES', 'ANIO'] as $periodoLink)
                    <a
                        href="{{ route('hechos.seguimiento', array_merge($paramsUnidad, ['periodo' => $periodoLink, 'situacion' => $situacionActual])) }}"
                        class="sv-period-link {{ $periodoActual === $periodoLink ? 'is-active' : '' }}"
                    >
                        {{ $labelsPeriodo[$periodoLink] }}
                    </a>
                @endforeach
            </div>

            @if ($hechos->count() === 0)
                <div class="alert alert-info">
                    No hay hechos para la selección actual.
                </div>
            @endif

            <div class="table-responsive seguimiento-table-wrap">
                <table id="seguimiento_hechos" class="table table-hover table-sm seguimiento-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha y hora</th>
                            <th>Unidad</th>
                            <th>Ubicación</th>
                            <th>Foto</th>
                            <th>Situación</th>
                            <th>Corralón</th>
                            <th>Creado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($hechos as $hecho)
                            @php
                                $foto = $hecho->foto_lugar;
                                $urlFoto = $foto ? asset('storage/' . ltrim($foto, '/')) : null;

                                $fechaMostrar = !empty($hecho->fecha)
                                    ? \Carbon\Carbon::parse($hecho->fecha)->format('Y-m-d')
                                    : '';

                                $horaMostrar = !empty($hecho->hora)
                                    ? substr((string) $hecho->hora, 0, 5)
                                    : '';

                                $unidadReal = \App\Support\HechoAccess::effectiveUnidadIdForHecho($hecho);
                                $puedeVerTarjetaWhatsAppHecho = $usuario && ($usuario->hasRole('Superadmin') || $unidadReal === 2);
                                $unidadNombre = optional($hecho->unidadOrganizacional)->nombre
                                    ?: ($unidadesFiltro[(string) $unidadReal] ?? 'Sin unidad');
                                $detalleUnidad = null;

                                if ($unidadReal === 2 && $hecho->delegacion) {
                                    $detalleUnidad = $hecho->delegacion->nombre_con_clave
                                        ?: $hecho->delegacion->nombre
                                        ?: $hecho->delegacion->municipio;

                                    if ($detalleUnidad && strcasecmp(trim($detalleUnidad), trim($unidadNombre)) === 0) {
                                        $detalleUnidad = null;
                                    }
                                }
                            @endphp

                            <tr>
                                <td class="font-weight-bold">#{{ $hecho->id }}</td>
                                <td>{{ trim($fechaMostrar . ' ' . $horaMostrar) }}</td>
                                <td class="sv-unit-cell">
                                    <span class="sv-unit-pill">{{ $unidadNombre }}</span>
                                    @if ($detalleUnidad)
                                        <span class="sv-unit-detail">{{ $detalleUnidad }}</span>
                                    @endif
                                </td>
                                <td class="seguimiento-location">
                                    {{ $hecho->calle }}, {{ $hecho->colonia }}, {{ $hecho->municipio }}
                                </td>

                                <td>
                                    @if ($urlFoto)
                                        <a href="{{ $urlFoto }}" target="_blank" rel="noopener">
                                            <img src="{{ $urlFoto }}" alt="foto_lugar" class="foto-thumb">
                                        </a>
                                    @else
                                        <span class="text-muted">Sin foto</span>
                                    @endif
                                </td>

                                <td class="seguimiento-status">
                                    <span class="sv-status sv-status--{{ $clasesSituacion[$hecho->situacion] ?? 'secondary' }}">
                                        {{ $hecho->situacion }}
                                    </span>

                                    @if ($hecho->mostrar_captura && $hecho->estado_captura === 'INCOMPLETO')
                                        <div class="captura-faltantes">
                                            <span class="captura-faltantes__badge">Captura incompleta</span>
                                            <span class="captura-faltantes__title">Falta capturar</span>
                                            @forelse (($hecho->faltantes_captura ?? []) as $faltante)
                                                <span class="captura-faltantes__item">{{ $faltante }}</span>
                                            @empty
                                                <span class="captura-faltantes__item">Revisar totales esperados</span>
                                            @endforelse
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    @php
                                        $vehiculosCorralon = (int) ($hecho->vehiculos_corralon_count ?? 0);
                                    @endphp

                                    <span class="sv-corralon-count {{ $vehiculosCorralon > 0 ? 'has-items' : '' }}">
                                        <i class="fa-solid fa-warehouse"></i>
                                        {{ $vehiculosCorralon }}
                                        {{ $vehiculosCorralon === 1 ? 'vehículo' : 'vehículos' }}
                                    </span>
                                </td>

                                <td>{{ $hecho->creator ? $hecho->creator->name : 'Desconocido' }}</td>

                                <td class="seguimiento-actions">
                                    <a href="{{ route('hechos.show', $hecho->id) }}" class="btn btn-info btn-sm" title="Ver">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>

                                    <a href="{{ route('hechos.edit', $hecho->id) }}" class="btn btn-success btn-sm" title="Editar">
                                        <i class="fa-solid fa-pencil"></i>
                                    </a>

                                    <a href="{{ route('hechos.descargar', $hecho->id) }}" class="btn btn-warning btn-sm" title="Descargar">
                                        <i class="fas fa-download"></i>
                                    </a>

                                    <button
                                        type="button"
                                        class="btn btn-success btn-sm btn-whatsapp"
                                        data-id="{{ $hecho->id }}"
                                        title="Compartir"
                                    >
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </button>

                                    @if(!empty($hecho->iph_delegaciones_path))
                                        <a
                                            href="{{ asset('storage/' . ltrim($hecho->iph_delegaciones_path, '/')) }}"
                                            class="btn btn-outline-danger btn-sm"
                                            target="_blank"
                                            rel="noopener"
                                            download
                                            title="Descargar IPH"
                                        >
                                            <i class="fa-solid fa-file-pdf"></i>
                                        </a>
                                    @endif

                                    @if($puedeVerTarjetaWhatsAppHecho)
                                        <button
                                            type="button"
                                            class="btn btn-outline-info btn-sm btn-preview-whatsapp-card"
                                            title="Ver tarjeta WhatsApp"
                                            data-url="{{ route('hechos.compartir', $hecho->id) }}"
                                        >
                                            <i class="fa-regular fa-rectangle-list"></i>
                                        </button>
                                    @endif

                                    <form action="{{ route('hechos.destroy', $hecho->id) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-sm delete-btn"
                                            title="Eliminar"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sv-pagination">
                <div class="sv-pagination__summary">
                    @if ($hechos->total() > 0)
                        Mostrando {{ $hechos->firstItem() }} a {{ $hechos->lastItem() }} de {{ $hechos->total() }} hechos
                    @else
                        Sin resultados
                    @endif
                </div>

                {{ $hechos->onEachSide(1)->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>

    @if($puedeVerTarjetaWhatsApp)
        @include('hechos.partials.whatsapp_preview_modal')
    @endif
@stop

@section('css')
    @if($puedeVerTarjetaWhatsApp)
        @include('hechos.partials.whatsapp_preview_styles')
    @endif

    <style>
        .seguimiento-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .seguimiento-card__sub {
            margin-top: .25rem;
            color: rgba(255, 255, 255, .68);
            font-size: .88rem;
        }

        .sv-filter-panel {
            margin-bottom: 1rem;
            padding: 1rem;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 8px;
            background: rgba(15, 23, 42, .45);
        }

        .sv-filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: .85rem;
            align-items: end;
        }

        .sv-filter-grid label {
            margin-bottom: .3rem;
            color: rgba(255, 255, 255, .78);
            font-size: .82rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .sv-input {
            color: #f8fafc !important;
            background-color: rgba(15, 23, 42, .78) !important;
            border-color: rgba(148, 163, 184, .34) !important;
        }

        .sv-input:focus {
            border-color: rgba(56, 189, 248, .7) !important;
            box-shadow: 0 0 0 .15rem rgba(56, 189, 248, .16) !important;
        }

        .sv-input option {
            color: #111827;
            background-color: #ffffff;
        }

        .sv-filter-actions {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .sv-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: .8rem;
            margin-bottom: .85rem;
        }

        .sv-kpi {
            display: flex;
            align-items: center;
            gap: .75rem;
            min-height: 74px;
            padding: .85rem;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 8px;
            color: #f8fafc;
            background: rgba(15, 23, 42, .42);
            text-decoration: none;
        }

        .sv-kpi:hover,
        .sv-kpi.is-active {
            color: #ffffff;
            border-color: rgba(56, 189, 248, .5);
            background: rgba(30, 41, 59, .8);
            text-decoration: none;
        }

        .sv-kpi__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 8px;
            font-size: 1.05rem;
            background: rgba(255, 255, 255, .12);
        }

        .sv-kpi--pendiente .sv-kpi__icon {
            color: #111827;
            background: #facc15;
        }

        .sv-kpi--turnado .sv-kpi__icon {
            color: #082f49;
            background: #38bdf8;
        }

        .sv-kpi--resuelto .sv-kpi__icon {
            color: #052e16;
            background: #4ade80;
        }

        .sv-kpi--falta_completar .sv-kpi__icon {
            color: #450a0a;
            background: #f87171;
        }

        .sv-kpi__body {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .sv-kpi__label {
            font-size: .82rem;
            color: rgba(255, 255, 255, .72);
        }

        .sv-kpi__value {
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.05;
        }

        .sv-period-links {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-bottom: 1rem;
        }

        .sv-period-link {
            padding: .35rem .75rem;
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 999px;
            color: rgba(255, 255, 255, .76);
            background: rgba(15, 23, 42, .42);
            font-size: .88rem;
            text-decoration: none;
        }

        .sv-period-link:hover,
        .sv-period-link.is-active {
            color: #ffffff;
            border-color: rgba(56, 189, 248, .55);
            background: rgba(14, 165, 233, .2);
            text-decoration: none;
        }

        .seguimiento-table-wrap {
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 8px;
            overflow: hidden;
        }

        .seguimiento-table {
            margin-bottom: 0 !important;
        }

        .seguimiento-table th,
        .seguimiento-table td {
            text-align: center;
            vertical-align: middle;
            border-color: rgba(148, 163, 184, .18) !important;
        }

        .seguimiento-table thead th {
            color: #f8fafc;
            background: rgba(15, 23, 42, .88);
            font-size: .82rem;
            text-transform: uppercase;
        }

        .seguimiento-table tbody td {
            color: rgba(255, 255, 255, .86);
            background: rgba(15, 23, 42, .36);
        }

        .seguimiento-table tbody tr:hover td {
            color: #ffffff;
            background: rgba(30, 41, 59, .82);
        }

        .seguimiento-location {
            min-width: 240px;
            max-width: 420px;
            text-align: left !important;
            white-space: normal;
        }

        .foto-thumb {
            width: 72px;
            height: 52px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, .18);
            background: #f8f9fa;
        }

        .sv-unit-cell {
            min-width: 138px;
        }

        .sv-unit-pill,
        .sv-status,
        .captura-faltantes__badge,
        .captura-faltantes__item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-weight: 800;
            line-height: 1.15;
            white-space: nowrap;
        }

        .sv-unit-pill {
            padding: .28rem .5rem;
            color: #e2e8f0;
            border: 1px solid rgba(148, 163, 184, .25);
            background: rgba(15, 23, 42, .65);
            font-size: .78rem;
        }

        .sv-unit-detail {
            display: block;
            max-width: 168px;
            margin: .28rem auto 0;
            color: #f8fafc;
            font-size: .88rem;
            font-weight: 800;
            line-height: 1.2;
            text-transform: uppercase;
            white-space: normal;
            word-break: break-word;
        }

        .sv-status {
            min-width: 94px;
            padding: .32rem .55rem;
            font-size: .78rem;
            text-transform: uppercase;
        }

        .sv-corralon-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .25rem;
            min-width: 92px;
            padding: .32rem .5rem;
            border-radius: 6px;
            color: #e2e8f0;
            background: #64748b;
            font-size: .78rem;
            font-weight: 800;
            line-height: 1.15;
            white-space: nowrap;
        }

        .sv-corralon-count.has-items {
            color: #082f49;
            background: #38bdf8;
        }

        .sv-status--warning {
            color: #111827;
            background: #facc15;
        }

        .sv-status--info {
            color: #082f49;
            background: #38bdf8;
        }

        .sv-status--success {
            color: #052e16;
            background: #4ade80;
        }

        .sv-status--secondary {
            color: #f8fafc;
            background: #64748b;
        }

        .captura-faltantes {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .25rem;
            margin-top: .45rem;
        }

        .captura-faltantes__badge {
            padding: .25rem .45rem;
            color: #ffffff;
            background: #ef4444;
            font-size: .72rem;
            text-transform: uppercase;
        }

        .captura-faltantes__title {
            color: #fecaca;
            font-size: .72rem;
            font-weight: 700;
        }

        .captura-faltantes__item {
            width: max-content;
            max-width: 100%;
            padding: .22rem .45rem;
            color: #1f2937;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, .35);
            font-size: .76rem;
        }

        .seguimiento-actions {
            min-width: 160px;
        }

        .seguimiento-actions .btn {
            width: 31px;
            height: 31px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: .08rem;
        }

        .sv-table-toolbar {
            display: flex;
            justify-content: flex-end;
            margin: .75rem 0;
        }

        #seguimiento_hechos_filter label {
            color: rgba(255, 255, 255, .72);
            margin-bottom: 0;
        }

        #seguimiento_hechos_filter input {
            color: #f8fafc;
            background: rgba(15, 23, 42, .78);
            border: 1px solid rgba(148, 163, 184, .34);
            border-radius: 6px;
            margin-left: .5rem;
            padding: .35rem .55rem;
        }

        .sv-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .sv-pagination__summary {
            color: rgba(255, 255, 255, .72);
            font-size: .86rem;
        }

        .sv-pagination .pagination {
            margin: 0;
            gap: .35rem;
            flex-wrap: wrap;
        }

        .sv-pagination .page-link {
            min-width: 34px;
            height: 34px;
            padding: .36rem .65rem;
            border-radius: 6px !important;
            color: #e2e8f0;
            background: rgba(15, 23, 42, .72);
            border: 1px solid rgba(148, 163, 184, .28);
            font-size: .86rem;
            line-height: 1.2;
        }

        .sv-pagination .page-link:hover {
            color: #ffffff;
            background: rgba(14, 165, 233, .25);
        }

        .sv-pagination .page-item.active .page-link {
            color: #ffffff;
            background: #2563eb;
            border-color: #2563eb;
        }

        .sv-pagination .page-item.disabled .page-link {
            color: rgba(226, 232, 240, .35);
            background: rgba(15, 23, 42, .35);
            border-color: rgba(148, 163, 184, .16);
        }

        .sv-pagination svg {
            width: 14px !important;
            height: 14px !important;
        }

        @media (max-width: 767.98px) {
            .seguimiento-card__header,
            .sv-pagination {
                align-items: flex-start;
                flex-direction: column;
            }

            .sv-filter-actions {
                width: 100%;
            }

            .sv-filter-actions .btn {
                flex: 1 1 auto;
            }

            .sv-table-toolbar {
                justify-content: stretch;
            }

            #seguimiento_hechos_filter,
            #seguimiento_hechos_filter label,
            #seguimiento_hechos_filter input {
                width: 100%;
            }

            #seguimiento_hechos_filter input {
                margin-left: 0;
                margin-top: .35rem;
            }
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            if ($.fn.DataTable.isDataTable('#seguimiento_hechos')) {
                $('#seguimiento_hechos').DataTable().destroy();
            }

            $('#seguimiento_hechos').DataTable({
                paging: false,
                info: false,
                order: [[1, 'desc']],
                responsive: true,
                lengthChange: false,
                autoWidth: false,
                dom: '<"sv-table-toolbar"f>rt',
                language: {
                    emptyTable: 'No hay información disponible',
                    loadingRecords: 'Cargando...',
                    processing: 'Procesando...',
                    search: 'Buscar:',
                    zeroRecords: 'No se encontraron resultados'
                }
            });
        });

        $(document).on('click', '.btn-whatsapp', function () {
            let id = $(this).data('id');

            fetch(`/hechos/${id}/compartir`)
                .then(res => res.json())
                .then(data => {
                    if (!data.texto) {
                        Swal.fire('Error', 'No se pudo generar el mensaje', 'error');
                        return;
                    }

                    let url = `https://wa.me/?text=${encodeURIComponent(data.texto)}`;
                    window.open(url, '_blank');
                })
                .catch(() => {
                    Swal.fire('Error', 'No se pudo compartir', 'error');
                });
        });

        $(document).on('click', '.delete-btn', function (e) {
            e.preventDefault();

            let form = $(this).closest('form');

            Swal.fire({
                title: '¿Estás seguro de eliminar este hecho?',
                text: 'No podrás revertir esta acción.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        @if (session('success'))
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000
            });
        @endif

        @if (session('error'))
            Swal.fire({
                position: 'center',
                icon: 'error',
                title: '{{ session('error') }}',
                showConfirmButton: true
            });
        @endif

        @if (session('info'))
            Swal.fire({
                position: 'center',
                icon: 'info',
                title: '{{ session('info') }}',
                showConfirmButton: false,
                timer: 2500
            });
        @endif
    </script>

    @if($puedeVerTarjetaWhatsApp)
        @include('hechos.partials.whatsapp_preview_scripts')
    @endif
@stop
