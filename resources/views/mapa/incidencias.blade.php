@extends('adminlte::page')

@section('title', 'Mapa de Incidencias')

@section('content_header')
    <div class="geo-page-head">
        <div>
            <p class="geo-kicker">Centro geoespacial</p>
            <h1>Mapa de Incidencias</h1>
        </div>
        <div class="geo-head-actions">
            <button type="button" id="btnFullscreen" class="btn geo-top-action" aria-label="Pantalla completa">
                <i class="fas fa-expand"></i>
                <span>Pantalla</span>
            </button>
            <div class="geo-head-summary">
                <span id="head-period">Periodo activo</span>
                <strong id="head-total">0 hechos</strong>
            </div>
        </div>
    </div>
@stop

@section('content')
@php($catalogos = $catalogos ?? [])
<div class="geo-workbench" id="geo-shell">
    <aside class="geo-sidebar">
        <section class="geo-filter-block">
            <div class="geo-section-title">
                <i class="fas fa-calendar-alt"></i>
                <span>Periodo</span>
            </div>

            <div class="geo-presets" aria-label="Atajos de periodo">
                <button type="button" data-preset="hoy">Hoy</button>
                <button type="button" data-preset="7">7 dias</button>
                <button type="button" data-preset="mes" class="is-active">Mes</button>
                <button type="button" data-preset="anio">Año</button>
                <button type="button" data-preset="todo">Todo</button>
            </div>

            <div class="geo-field-grid">
                <label class="geo-field">
                    <span>Desde</span>
                    <input type="date" id="desde" value="{{ $fechaInicio }}" class="form-control">
                </label>
                <label class="geo-field">
                    <span>Hasta</span>
                    <input type="date" id="hasta" value="{{ $fechaFin }}" class="form-control">
                </label>
            </div>

            <div class="geo-field-grid">
                <label class="geo-field">
                    <span>Hora inicio</span>
                    <input type="time" id="hora_desde" class="form-control">
                </label>
                <label class="geo-field">
                    <span>Hora fin</span>
                    <input type="time" id="hora_hasta" class="form-control">
                </label>
            </div>
        </section>

        <section class="geo-filter-block">
            <div class="geo-section-title">
                <i class="fas fa-sliders-h"></i>
                <span>Filtros</span>
            </div>

            <label class="geo-field">
                <span>Tipo de hecho</span>
                <select id="tipo_hecho" class="form-control geo-multi" multiple size="5">
                    @foreach(($catalogos['tipos'] ?? []) as $item)
                        <option value="{{ $item }}">{{ $item }}</option>
                    @endforeach
                </select>
            </label>

            <div class="geo-field-grid">
                <label class="geo-field">
                    <span>Situacion</span>
                    <select id="situacion" class="form-control geo-multi" multiple size="4">
                        @foreach(($catalogos['situaciones'] ?? []) as $item)
                            <option value="{{ $item }}">{{ $item }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="geo-field">
                    <span>Municipio</span>
                    <select id="municipio" class="form-control geo-multi" multiple size="4">
                        @foreach(($catalogos['municipios'] ?? []) as $item)
                            <option value="{{ $item }}">{{ $item }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label class="geo-field">
                <span>Sector</span>
                <select id="sector" class="form-control geo-multi" multiple size="4">
                    @foreach(($catalogos['sectores'] ?? []) as $item)
                        <option value="{{ $item }}">{{ $item }}</option>
                    @endforeach
                </select>
            </label>

            <label class="geo-check">
                <input type="checkbox" id="solo_relevantes">
                <span>Solo hechos relevantes</span>
            </label>
        </section>

        <section class="geo-filter-block">
            <div class="geo-section-title">
                <i class="fas fa-layer-group"></i>
                <span>Capas</span>
            </div>

            <div class="geo-field-grid">
                <label class="geo-field">
                    <span>Precision</span>
                    <select id="precision" class="form-control">
                        <option value="2">2 - regional</option>
                        <option value="3" selected>3 - operativo</option>
                        <option value="4">4 - calle</option>
                        <option value="5">5 - punto exacto</option>
                    </select>
                </label>
                <label class="geo-field">
                    <span>Min. zona</span>
                    <input type="number" id="min_total" min="1" max="99" value="1" class="form-control">
                </label>
            </div>

            <div class="geo-field-grid">
                <label class="geo-field">
                    <span>Limite</span>
                    <select id="limite" class="form-control">
                        <option value="500">500</option>
                        <option value="1500">1500</option>
                        <option value="3000" selected>3000</option>
                        <option value="5000">5000</option>
                    </select>
                </label>
                <label class="geo-field">
                    <span>Mapa base</span>
                    <select id="basemap" class="form-control">
                        <option value="osm">Calles</option>
                        <option value="light">Claro tecnico</option>
                        <option value="dark">Nocturno</option>
                        <option value="satellite">Satelite</option>
                    </select>
                </label>
            </div>

            <div class="geo-toggle-grid">
                <label class="geo-check">
                    <input type="checkbox" id="show_heat" checked>
                    <span>Calor</span>
                </label>
                <label class="geo-check">
                    <input type="checkbox" id="show_clusters" checked>
                    <span>Clusters</span>
                </label>
                <label class="geo-check">
                    <input type="checkbox" id="show_grid" checked>
                    <span>Cuadricula</span>
                </label>
                <label class="geo-check">
                    <input type="checkbox" id="show_labels">
                    <span>Etiquetas</span>
                </label>
            </div>
        </section>

        <div class="geo-actions">
            <button type="button" id="btnCargar" class="btn geo-primary">
                <i class="fas fa-sync-alt"></i>
                Actualizar
            </button>
            <button type="button" id="btnVista" class="btn geo-secondary">
                <i class="fas fa-crop-alt"></i>
                Vista
            </button>
            <button type="button" id="btnLimpiarVista" class="btn geo-secondary">
                <i class="fas fa-globe-americas"></i>
            </button>
            <button type="button" id="btnReset" class="btn geo-secondary">
                <i class="fas fa-undo"></i>
            </button>
            <button type="button" id="btnExport" class="btn geo-secondary">
                <i class="fas fa-file-csv"></i>
            </button>
        </div>

        <div class="geo-bbox" id="bbox-status">Vista completa</div>
    </aside>

    <main class="geo-main">
        <section class="geo-kpi-strip" aria-label="Metricas del mapa">
            <div class="geo-kpi">
                <span>Puntos</span>
                <strong id="metric-puntos">0</strong>
            </div>
            <div class="geo-kpi">
                <span>Hechos</span>
                <strong id="metric-hechos">0</strong>
            </div>
            <div class="geo-kpi danger">
                <span>Turnados</span>
                <strong id="metric-turnados">0</strong>
            </div>
            <div class="geo-kpi warn">
                <span>Pendientes</span>
                <strong id="metric-pendientes">0</strong>
            </div>
            <div class="geo-kpi">
                <span>Max. zona</span>
                <strong id="metric-maximo">0</strong>
            </div>
            <div class="geo-kpi">
                <span>Hora pico</span>
                <strong id="metric-hora">--:--</strong>
            </div>
        </section>

        <div class="geo-map-layout">
            <section class="geo-map-shell">
                <div id="map" class="geo-map"></div>
                <div class="geo-map-status" id="status-sub">Listo para cargar datos.</div>
            </section>

            <aside class="geo-inspector">
                <div class="geo-inspector-head">
                    <span>Analisis territorial</span>
                    <strong id="inspector-score">0.0</strong>
                </div>

                <div class="geo-selected" id="selected-zone">
                    <h2>Zona seleccionada</h2>
                    <p>Selecciona un punto del mapa para abrir su ficha operativa.</p>
                </div>

                <div class="geo-inspector-section">
                    <div class="geo-panel-head">
                        <h2>Top zonas</h2>
                        <span id="top-zones-count">0</span>
                    </div>
                    <div id="top-zones" class="geo-zone-list"></div>
                </div>
            </aside>
        </div>

        <section class="geo-intel-grid">
            <div class="geo-panel">
                <div class="geo-panel-head">
                    <h2>Distribucion por hora</h2>
                    <span id="hour-total">0</span>
                </div>
                <div id="hour-chart" class="geo-hour-chart"></div>
            </div>

            <div class="geo-panel">
                <div class="geo-panel-head">
                    <h2>Tipo de hecho</h2>
                    <span>Top 10</span>
                </div>
                <div id="rank-tipo" class="geo-ranking"></div>
            </div>

            <div class="geo-panel">
                <div class="geo-panel-head">
                    <h2>Municipios</h2>
                    <span>Top 12</span>
                </div>
                <div id="rank-municipio" class="geo-ranking"></div>
            </div>
        </section>
    </main>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">

<style>
    :root {
        --geo-ink: #132033;
        --geo-muted: #667085;
        --geo-line: #d9e2ec;
        --geo-panel: #ffffff;
        --geo-soft: #f6f8fb;
        --geo-blue: #2563eb;
        --geo-cyan: #0891b2;
        --geo-green: #0f766e;
        --geo-amber: #d97706;
        --geo-red: #b91c1c;
        --geo-violet: #6d28d9;
        --geo-radius: 8px;
    }

    .content-wrapper,
    .content-wrapper > .content,
    .content-wrapper > .content-header {
        background: #071421 !important;
    }

    .content-wrapper > .content-header {
        padding: 18px 20px 12px;
        border-bottom: 1px solid rgba(148, 163, 184, .16);
    }

    .content-wrapper.geo-fullscreen-mode,
    .content-wrapper:fullscreen {
        background: #071421 !important;
        overflow: auto;
    }

    .geo-page-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 16px;
        color: #eef6ff;
    }

    .content-header .geo-page-head h1,
    .geo-page-head h1 {
        margin: 0;
        font-size: 1.65rem;
        font-weight: 900;
        letter-spacing: 0;
        color: #f8fbff !important;
        text-shadow: 0 2px 14px rgba(0, 0, 0, .32);
    }

    .geo-kicker {
        margin: 0 0 4px;
        color: var(--geo-cyan);
        font-size: .75rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .geo-head-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }

    .geo-top-action {
        min-height: 42px;
        border: 1px solid rgba(148, 163, 184, .24);
        border-radius: var(--geo-radius);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 14px;
        background: #102235;
        color: #eaf2ff;
        font-weight: 900;
        box-shadow: 0 12px 24px rgba(0, 0, 0, .20);
        white-space: nowrap;
    }

    .geo-top-action:hover,
    .geo-top-action:focus {
        background: #172b43;
        color: #ffffff;
    }

    .geo-head-summary {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 42px;
        padding: 10px 14px;
        border: 1px solid rgba(148, 163, 184, .24);
        border-radius: var(--geo-radius);
        background: #102235;
        color: #b9c7d9;
        font-size: .86rem;
        box-shadow: 0 12px 24px rgba(0, 0, 0, .20);
    }

    .geo-head-summary strong {
        color: #ffffff;
        font-size: .95rem;
    }

    .geo-workbench {
        display: grid;
        grid-template-columns: 340px minmax(0, 1fr);
        gap: 12px;
        padding: 12px 0 18px;
        color-scheme: light;
    }

    .geo-sidebar,
    .geo-main,
    .geo-inspector,
    .geo-panel,
    .geo-map-shell {
        border: 1px solid rgba(148, 163, 184, .18);
        border-radius: var(--geo-radius);
        background: var(--geo-panel);
        box-shadow: 0 18px 42px rgba(0, 0, 0, .20);
    }

    .geo-sidebar {
        align-self: start;
        position: sticky;
        top: 76px;
        max-height: calc(100vh - 96px);
        overflow: auto;
        padding: 12px;
        background: #f7fafc;
    }

    .geo-main {
        min-width: 0;
        padding: 12px;
        background: #f8fafc;
    }

    .geo-filter-block {
        padding: 12px 0;
        border-bottom: 1px solid #e8eef5;
    }

    .geo-filter-block:first-child {
        padding-top: 0;
    }

    .geo-filter-block:last-of-type {
        border-bottom: 0;
    }

    .geo-section-title,
    .geo-panel-head,
    .geo-inspector-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .geo-section-title {
        justify-content: flex-start;
        margin-bottom: 10px;
        color: var(--geo-ink);
        font-size: .82rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .geo-section-title i {
        color: var(--geo-blue);
    }

    .geo-presets {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 6px;
        margin-bottom: 10px;
    }

    .geo-presets button,
    .geo-actions .btn {
        min-height: 38px;
        border-radius: var(--geo-radius);
        font-weight: 800;
    }

    .geo-presets button {
        border: 1px solid var(--geo-line);
        background: #f8fafc;
        color: #334155;
        font-size: .78rem;
    }

    .geo-presets button.is-active {
        border-color: rgba(37, 99, 235, .42);
        background: #eaf1ff;
        color: var(--geo-blue);
    }

    .geo-field-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 8px;
    }

    .geo-field {
        display: block;
        margin-bottom: 8px;
    }

    .geo-field span,
    .geo-check span {
        display: block;
        margin-bottom: 5px;
        color: #344054;
        font-size: .8rem;
        font-weight: 800;
    }

    .geo-field .form-control {
        min-height: 40px;
        border: 1px solid #cbd5e1;
        border-radius: var(--geo-radius);
        background-color: #ffffff !important;
        color: var(--geo-ink) !important;
        font-weight: 700;
        color-scheme: light;
    }

    .geo-multi {
        height: auto;
        min-height: 92px;
        font-size: .78rem;
        padding: 5px;
        background: #ffffff;
        scrollbar-width: thin;
    }

    .geo-multi option {
        margin: 2px 0;
        padding: 6px 8px;
        border-radius: 6px;
        color: #1f2937;
        background: #ffffff;
        font-weight: 800;
    }

    .geo-multi option:checked {
        background: #dbeafe linear-gradient(0deg, #dbeafe, #dbeafe);
        color: #1e3a8a;
    }

    .geo-check {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 8px 0 0;
        padding: 8px 10px;
        border: 1px solid #e2e8f0;
        border-radius: var(--geo-radius);
        background: #f8fafc;
        cursor: pointer;
    }

    .geo-check input {
        width: 16px;
        height: 16px;
        flex: 0 0 auto;
    }

    .geo-check span {
        margin: 0;
        color: #263446;
        font-size: .82rem;
        text-transform: none;
        letter-spacing: 0;
    }

    .geo-toggle-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
    }

    .geo-actions {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 7px;
        margin-top: 12px;
    }

    .geo-actions .geo-primary {
        grid-column: span 2;
    }

    #btnVista {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding-inline: 10px;
        white-space: nowrap;
    }

    .geo-primary {
        border: 0;
        background: var(--geo-blue);
        color: #fff;
    }

    .geo-primary:hover,
    .geo-primary:focus {
        background: #1d4ed8;
        color: #fff;
    }

    .geo-secondary {
        border: 1px solid var(--geo-line);
        background: #fff;
        color: #334155;
    }

    .geo-secondary:hover,
    .geo-secondary:focus {
        background: #f1f5f9;
        color: #0f172a;
    }

    .geo-workbench.is-fullscreen,
    .geo-workbench:fullscreen,
    .content-wrapper.geo-fullscreen-mode .geo-workbench,
    .content-wrapper:fullscreen .geo-workbench {
        width: 100vw;
        min-height: calc(100vh - 74px);
        overflow: auto;
        padding: 14px;
        background: #071421;
        grid-template-columns: 340px minmax(0, 1fr);
    }

    .geo-workbench.is-fullscreen .geo-sidebar,
    .geo-workbench:fullscreen .geo-sidebar,
    .content-wrapper.geo-fullscreen-mode .geo-sidebar,
    .content-wrapper:fullscreen .geo-sidebar {
        top: 0;
        max-height: calc(100vh - 102px);
    }

    .geo-workbench.is-fullscreen .geo-map,
    .geo-workbench:fullscreen .geo-map,
    .content-wrapper.geo-fullscreen-mode .geo-map,
    .content-wrapper:fullscreen .geo-map {
        height: calc(100vh - 198px);
        min-height: 560px;
    }

    .geo-workbench.is-fullscreen .geo-map-shell,
    .geo-workbench:fullscreen .geo-map-shell,
    .content-wrapper.geo-fullscreen-mode .geo-map-shell,
    .content-wrapper:fullscreen .geo-map-shell {
        min-height: 560px;
    }

    .geo-bbox {
        margin-top: 8px;
        padding: 8px 10px;
        border-radius: var(--geo-radius);
        background: #f1f5f9;
        color: var(--geo-muted);
        font-size: .78rem;
        font-weight: 700;
    }

    .geo-kpi-strip {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 10px;
    }

    .geo-kpi {
        min-width: 0;
        padding: 12px;
        border: 1px solid #e2e8f0;
        border-radius: var(--geo-radius);
        background: #f8fafc;
    }

    .geo-kpi span {
        display: block;
        margin-bottom: 6px;
        color: var(--geo-muted);
        font-size: .75rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .geo-kpi strong {
        display: block;
        color: var(--geo-ink);
        font-size: 1.35rem;
        font-weight: 900;
        line-height: 1;
        overflow-wrap: anywhere;
    }

    .geo-kpi.danger {
        border-color: rgba(185, 28, 28, .24);
        background: #fff5f5;
    }

    .geo-kpi.warn {
        border-color: rgba(217, 119, 6, .24);
        background: #fffbeb;
    }

    .geo-map-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 330px;
        gap: 10px;
    }

    .geo-map-shell {
        position: relative;
        min-height: 650px;
        overflow: hidden;
    }

    .geo-map {
        width: 100%;
        height: 74vh;
        min-height: 650px;
        background: #dbeafe;
    }

    .geo-map-status {
        position: absolute;
        left: 12px;
        bottom: 12px;
        z-index: 500;
        max-width: min(520px, calc(100% - 24px));
        padding: 9px 11px;
        border: 1px solid rgba(15, 23, 42, .12);
        border-radius: var(--geo-radius);
        background: rgba(255, 255, 255, .94);
        color: #334155;
        font-size: .82rem;
        font-weight: 800;
        box-shadow: 0 12px 24px rgba(15, 23, 42, .12);
    }

    .geo-inspector {
        min-width: 0;
        padding: 12px;
    }

    .geo-inspector-head {
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e2e8f0;
        color: var(--geo-muted);
        font-size: .78rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .geo-inspector-head strong {
        color: var(--geo-violet);
        font-size: 1.15rem;
    }

    .geo-selected {
        margin-bottom: 10px;
        padding: 0 0 10px;
        border-bottom: 1px solid #e2e8f0;
    }

    .geo-selected h2,
    .geo-panel h2,
    .geo-inspector-section h2 {
        margin: 0;
        color: var(--geo-ink);
        font-size: .95rem;
        font-weight: 900;
        letter-spacing: 0;
    }

    .geo-selected p {
        margin: 8px 0 0;
        color: var(--geo-muted);
        font-size: .84rem;
        line-height: 1.4;
    }

    .geo-selected dl {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        margin: 10px 0 0;
    }

    .geo-selected dt {
        color: var(--geo-muted);
        font-size: .72rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .geo-selected dd {
        margin: 2px 0 0;
        color: var(--geo-ink);
        font-size: .9rem;
        font-weight: 900;
    }

    .geo-panel {
        padding: 12px;
    }

    .geo-inspector-section {
        padding-top: 2px;
    }

    .geo-panel-head {
        margin-bottom: 10px;
    }

    .geo-panel-head span {
        color: var(--geo-muted);
        font-size: .76rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .geo-intel-grid {
        display: grid;
        grid-template-columns: 1.25fr 1fr 1fr;
        gap: 10px;
        margin-top: 10px;
    }

    .geo-zone-list,
    .geo-ranking {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .zone-row,
    .rank-row {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: var(--geo-radius);
        background: #fff;
        color: var(--geo-ink);
    }

    .zone-row {
        padding: 9px;
        text-align: left;
        cursor: pointer;
        transition: border-color .15s ease, background .15s ease, transform .15s ease;
    }

    .zone-row:hover,
    .zone-row:focus {
        border-color: rgba(37, 99, 235, .38);
        background: #f8fbff;
        transform: translateY(-1px);
    }

    .zone-main,
    .rank-main {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 6px;
        color: var(--geo-ink);
        font-size: .84rem;
        font-weight: 900;
    }

    .zone-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        color: var(--geo-muted);
        font-size: .75rem;
        font-weight: 800;
    }

    .zone-meter,
    .rank-meter {
        height: 5px;
        overflow: hidden;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .zone-meter span,
    .rank-meter span {
        display: block;
        height: 100%;
        width: var(--w, 0%);
        border-radius: inherit;
        background: var(--c, var(--geo-blue));
    }

    .rank-row {
        padding: 8px;
    }

    .rank-main {
        font-size: .8rem;
    }

    .rank-main span:first-child {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .geo-hour-chart {
        display: grid;
        grid-template-columns: repeat(24, minmax(10px, 1fr));
        align-items: end;
        gap: 3px;
        min-height: 150px;
        padding-top: 12px;
    }

    .hour-bar {
        position: relative;
        min-height: 28px;
        border-radius: 4px 4px 0 0;
        background: #e2e8f0;
        overflow: hidden;
    }

    .hour-bar span {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        min-height: 4px;
        height: var(--h, 0%);
        border-radius: inherit;
        background: linear-gradient(180deg, #0891b2, #2563eb);
    }

    .hour-bar strong {
        position: absolute;
        left: 50%;
        bottom: 5px;
        transform: translateX(-50%) rotate(-90deg);
        color: #475569;
        font-size: .62rem;
        font-weight: 900;
        line-height: 1;
        white-space: nowrap;
    }

    .incident-div-icon {
        background: transparent;
        border: 0;
    }

    .incident-marker {
        width: var(--marker-size);
        height: var(--marker-size);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
        border-radius: 50%;
        background: var(--marker-color);
        color: #fff;
        font-size: .74rem;
        font-weight: 900;
        box-shadow: 0 10px 18px rgba(15, 23, 42, .28);
    }

    .incident-marker::after {
        content: "";
        position: absolute;
        inset: -6px;
        border: 1px solid var(--marker-color);
        border-radius: 50%;
        opacity: .28;
    }

    .geo-label-icon {
        background: transparent;
        border: 0;
    }

    .geo-label-pill {
        transform: translate(-50%, -50%);
        padding: 3px 6px;
        border: 1px solid rgba(15, 23, 42, .12);
        border-radius: 999px;
        background: rgba(255, 255, 255, .94);
        color: #0f172a;
        font-size: .68rem;
        font-weight: 900;
        white-space: nowrap;
        box-shadow: 0 8px 18px rgba(15, 23, 42, .12);
    }

    .marker-cluster-small,
    .marker-cluster-medium,
    .marker-cluster-large {
        background-color: rgba(37, 99, 235, .22);
    }

    .marker-cluster-small div,
    .marker-cluster-medium div,
    .marker-cluster-large div {
        background-color: rgba(37, 99, 235, .86);
        color: #fff;
        font-weight: 900;
    }

    .leaflet-popup-content-wrapper {
        border-radius: var(--geo-radius);
        box-shadow: 0 18px 36px rgba(15, 23, 42, .18);
    }

    .leaflet-popup-content {
        min-width: 290px;
        max-width: 420px;
        margin: 12px;
    }

    .incident-popup-title {
        margin-bottom: 8px;
        color: var(--geo-ink);
        font-size: 1rem;
        font-weight: 900;
    }

    .incident-popup-meta {
        margin-bottom: 10px;
        color: var(--geo-muted);
        font-size: .8rem;
        line-height: 1.4;
    }

    .incident-list {
        display: flex;
        flex-direction: column;
        gap: 7px;
        max-height: 330px;
        overflow: auto;
        padding-right: 4px;
    }

    .incident-card {
        display: block;
        padding: 9px 10px;
        border: 1px solid #dbe4f0;
        border-radius: var(--geo-radius);
        background: #f8fafc;
        color: var(--geo-ink);
        text-decoration: none !important;
    }

    .incident-card:hover {
        border-color: rgba(37, 99, 235, .42);
        background: #eef6ff;
        color: var(--geo-ink);
    }

    .incident-card-title {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 4px;
        font-size: .84rem;
        font-weight: 900;
    }

    .incident-card-sub {
        color: #475569;
        font-size: .78rem;
        line-height: 1.35;
    }

    .incident-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-top: 7px;
    }

    .incident-tag {
        padding: 3px 6px;
        border-radius: 999px;
        background: #e2e8f0;
        color: #334155;
        font-size: .68rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .incident-loading,
    .incident-empty {
        padding: 12px;
        border-radius: var(--geo-radius);
        background: #f8fafc;
        color: var(--geo-muted);
        font-size: .84rem;
        text-align: center;
    }

    @media (max-width: 1199.98px) {
        .geo-workbench,
        .geo-map-layout,
        .geo-intel-grid {
            grid-template-columns: 1fr;
        }

        .geo-sidebar {
            position: relative;
            top: auto;
            max-height: none;
        }

        .geo-kpi-strip {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .geo-page-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .geo-head-summary,
        .geo-field-grid,
        .geo-toggle-grid,
        .geo-presets,
        .geo-kpi-strip {
            grid-template-columns: 1fr;
        }

        .geo-head-summary {
            display: grid;
            width: 100%;
        }

        .geo-head-actions {
            width: 100%;
            justify-content: stretch;
        }

        .geo-top-action {
            flex: 1 1 130px;
        }

        .geo-actions {
            grid-template-columns: 1fr 1fr;
        }

        .geo-map,
        .geo-map-shell {
            min-height: 500px;
        }
    }
</style>
@stop

@section('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
    const urlData = @json(route('mapa.incidencias.data'));
    const urlHechos = @json(route('mapa.incidencias.hechos'));
    const defaultFechaInicio = @json($fechaInicio);
    const defaultFechaFin = @json($fechaFin);
    const geoShell = document.getElementById('geo-shell');
    const fullscreenRoot = document.querySelector('.content-wrapper') || geoShell;
    const btnFullscreen = document.getElementById('btnFullscreen');

    const controls = {
        desde: document.getElementById('desde'),
        hasta: document.getElementById('hasta'),
        horaDesde: document.getElementById('hora_desde'),
        horaHasta: document.getElementById('hora_hasta'),
        precision: document.getElementById('precision'),
        minTotal: document.getElementById('min_total'),
        limite: document.getElementById('limite'),
        basemap: document.getElementById('basemap'),
        showHeat: document.getElementById('show_heat'),
        showClusters: document.getElementById('show_clusters'),
        showGrid: document.getElementById('show_grid'),
        showLabels: document.getElementById('show_labels'),
        soloRelevantes: document.getElementById('solo_relevantes')
    };

    const multiFilters = ['tipo_hecho', 'situacion', 'municipio', 'sector'];
    const statusSub = document.getElementById('status-sub');
    const bboxStatus = document.getElementById('bbox-status');
    const selectedZone = document.getElementById('selected-zone');

    const metricPuntos = document.getElementById('metric-puntos');
    const metricHechos = document.getElementById('metric-hechos');
    const metricTurnados = document.getElementById('metric-turnados');
    const metricPendientes = document.getElementById('metric-pendientes');
    const metricMaximo = document.getElementById('metric-maximo');
    const metricHora = document.getElementById('metric-hora');
    const headPeriod = document.getElementById('head-period');
    const headTotal = document.getElementById('head-total');
    const inspectorScore = document.getElementById('inspector-score');

    const topZones = document.getElementById('top-zones');
    const topZonesCount = document.getElementById('top-zones-count');
    const hourChart = document.getElementById('hour-chart');
    const hourTotal = document.getElementById('hour-total');
    const rankTipo = document.getElementById('rank-tipo');
    const rankMunicipio = document.getElementById('rank-municipio');

    let currentPuntos = [];
    let currentSummary = null;
    let activeBbox = null;
    let activeBase = null;
    let heatLayer = null;
    let pointLayer = null;
    let gridLayer = L.layerGroup();
    let labelLayer = L.layerGroup();
    const markerByKey = new Map();

    const map = L.map('map', {
        zoomControl: true,
        preferCanvas: true
    }).setView([19.703, -101.186], 12);

    const baseLayers = {
        osm: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }),
        light: L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 20,
            attribution: '&copy; OpenStreetMap &copy; CARTO'
        }),
        dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 20,
            attribution: '&copy; OpenStreetMap &copy; CARTO'
        }),
        satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            attribution: 'Tiles &copy; Esri'
        })
    };

    activeBase = baseLayers.osm.addTo(map);
    setTimeout(() => map.invalidateSize(), 250);

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function number(value) {
        return Number(value || 0).toLocaleString('es-MX');
    }

    function localIso(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function selectedValues(id) {
        return Array.from(document.getElementById(id).selectedOptions).map((option) => option.value);
    }

    function appendFilters(url) {
        if (controls.desde.value) url.searchParams.set('desde', controls.desde.value);
        if (controls.hasta.value) url.searchParams.set('hasta', controls.hasta.value);
        if (controls.horaDesde.value) url.searchParams.set('hora_desde', controls.horaDesde.value);
        if (controls.horaHasta.value) url.searchParams.set('hora_hasta', controls.horaHasta.value);

        url.searchParams.set('precision', controls.precision.value);
        url.searchParams.set('min_total', controls.minTotal.value || '1');
        url.searchParams.set('limite', controls.limite.value || '3000');

        if (controls.soloRelevantes.checked) {
            url.searchParams.set('solo_relevantes', '1');
        }

        multiFilters.forEach((key) => {
            selectedValues(key).forEach((value) => {
                url.searchParams.append(`${key}[]`, value);
            });
        });

        if (activeBbox) {
            url.searchParams.set('bbox', activeBbox);
        }
    }

    function colorForPoint(punto) {
        if (punto.categoria === 'critico') return '#b91c1c';
        if (punto.categoria === 'alerta') return '#d97706';
        if (punto.categoria === 'observacion') return '#0891b2';
        return '#0f766e';
    }

    function pointKey(punto) {
        return `${Number(punto.lat).toFixed(5)}|${Number(punto.lng).toFixed(5)}`;
    }

    function markerSize(punto) {
        const maxScore = Math.max(1, Number(currentSummary?.totales?.score_max || 1));
        const ratio = Math.min(1, Number(punto.score || punto.total || 1) / maxScore);
        return Math.round(28 + (ratio * 30));
    }

    function buildMarker(punto) {
        const size = markerSize(punto);
        const color = colorForPoint(punto);
        const icon = L.divIcon({
            className: 'incident-div-icon',
            html: `<div class="incident-marker" style="--marker-size:${size}px;--marker-color:${color}">${number(punto.total)}</div>`,
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2]
        });

        const marker = L.marker([punto.lat, punto.lng], { icon });
        marker.bindPopup(buildPopupSkeleton(punto));
        marker.on('click', () => loadHechosDePunto(marker, punto));
        markerByKey.set(pointKey(punto), marker);

        return marker;
    }

    function buildGridCell(punto) {
        const precision = Number(controls.precision.value || 3);
        const step = Math.pow(10, -precision);
        const color = colorForPoint(punto);
        const bounds = [
            [Number(punto.lat) - (step / 2), Number(punto.lng) - (step / 2)],
            [Number(punto.lat) + (step / 2), Number(punto.lng) + (step / 2)]
        ];

        return L.rectangle(bounds, {
            color,
            weight: 1,
            fillColor: color,
            fillOpacity: Math.min(.34, .08 + (Number(punto.total || 1) * .035)),
            interactive: false
        });
    }

    function buildLabel(punto) {
        const icon = L.divIcon({
            className: 'geo-label-icon',
            html: `<span class="geo-label-pill">${number(punto.total)} hechos</span>`,
            iconSize: [1, 1],
            iconAnchor: [0, 0]
        });

        return L.marker([punto.lat, punto.lng], { icon, interactive: false });
    }

    function clearMapLayers() {
        if (heatLayer) {
            map.removeLayer(heatLayer);
            heatLayer = null;
        }

        if (pointLayer) {
            map.removeLayer(pointLayer);
            pointLayer = null;
        }

        map.removeLayer(gridLayer);
        map.removeLayer(labelLayer);
        gridLayer = L.layerGroup();
        labelLayer = L.layerGroup();
        markerByKey.clear();
    }

    function renderLayers() {
        clearMapLayers();

        const maxScore = Math.max(1, Number(currentSummary?.totales?.score_max || 1));

        if (controls.showHeat.checked && L.heatLayer) {
            heatLayer = L.heatLayer(currentPuntos.map((punto) => [
                Number(punto.lat),
                Number(punto.lng),
                Math.max(.12, Number(punto.score || punto.total || 1) / maxScore)
            ]), {
                radius: 28,
                blur: 22,
                maxZoom: 17,
                gradient: {
                    .20: '#0891b2',
                    .45: '#22c55e',
                    .68: '#f59e0b',
                    .84: '#ef4444',
                    1: '#7f1d1d'
                }
            }).addTo(map);
        }

        if (controls.showGrid.checked) {
            currentPuntos.forEach((punto) => gridLayer.addLayer(buildGridCell(punto)));
            gridLayer.addTo(map);
        }

        const useClusters = controls.showClusters.checked && L.markerClusterGroup;
        pointLayer = useClusters
            ? L.markerClusterGroup({
                showCoverageOnHover: false,
                maxClusterRadius: 42,
                disableClusteringAtZoom: 16,
                spiderfyOnMaxZoom: true
            })
            : L.layerGroup();

        currentPuntos.forEach((punto) => pointLayer.addLayer(buildMarker(punto)));
        pointLayer.addTo(map);

        if (controls.showLabels.checked) {
            currentPuntos.forEach((punto) => labelLayer.addLayer(buildLabel(punto)));
            labelLayer.addTo(map);
        }
    }

    function fitToPuntos() {
        const coords = currentPuntos.map((punto) => [Number(punto.lat), Number(punto.lng)]);

        if (coords.length === 1) {
            map.setView(coords[0], 15);
            return;
        }

        if (coords.length > 1) {
            map.fitBounds(coords, { padding: [34, 34] });
        }
    }

    function buildPopupSkeleton(punto) {
        return `
            <div class="incident-popup">
                <div class="incident-popup-title">${punto.total > 1 ? 'Zona de incidencia' : 'Incidencia localizada'}</div>
                <div class="incident-popup-meta">
                    ${number(punto.total)} hecho(s) · score ${Number(punto.score || 0).toFixed(1)}<br>
                    ${Number(punto.lat).toFixed(5)}, ${Number(punto.lng).toFixed(5)}
                </div>
                <div class="incident-loading">Cargando ficha operativa...</div>
            </div>
        `;
    }

    function buildPopupContent(punto, hechos) {
        if (!hechos.length) {
            return `
                <div class="incident-popup">
                    <div class="incident-popup-title">Sin resultados</div>
                    <div class="incident-empty">No hay hechos para este punto con los filtros actuales.</div>
                </div>
            `;
        }

        const cards = hechos.map((hecho) => {
            const ubicacion = [hecho.calle, hecho.colonia, hecho.municipio].filter(Boolean).join(', ');
            const mp = Number(hecho.vehiculos_mp || 0) + Number(hecho.personas_mp || 0);

            return `
                <a class="incident-card" href="${escapeHtml(hecho.show_url)}" target="_blank" rel="noopener noreferrer">
                    <div class="incident-card-title">
                        <span>Hecho #${escapeHtml(hecho.id)}</span>
                        <span>${escapeHtml(hecho.fecha || 'SIN FECHA')}</span>
                    </div>
                    <div class="incident-card-sub">
                        ${escapeHtml(hecho.tipo_hecho || 'SIN TIPO')}
                        ${hecho.situacion ? ' · ' + escapeHtml(hecho.situacion) : ''}<br>
                        ${escapeHtml(hecho.hora || 'SIN HORA')}
                        ${ubicacion ? ' · ' + escapeHtml(ubicacion) : ''}
                        ${hecho.folio_c5i ? '<br>Folio C5I: ' + escapeHtml(hecho.folio_c5i) : ''}
                    </div>
                    <div class="incident-tags">
                        ${hecho.sector ? '<span class="incident-tag">' + escapeHtml(hecho.sector) + '</span>' : ''}
                        ${hecho.es_relevante ? '<span class="incident-tag">relevante</span>' : ''}
                        ${mp > 0 ? '<span class="incident-tag">mp ' + number(mp) + '</span>' : ''}
                    </div>
                </a>
            `;
        }).join('');

        return `
            <div class="incident-popup">
                <div class="incident-popup-title">${hechos.length > 1 ? 'Hechos en la zona' : 'Hecho en este punto'}</div>
                <div class="incident-popup-meta">
                    ${number(hechos.length)} resultado(s) · ${escapeHtml(punto.fecha_min || '-')} a ${escapeHtml(punto.fecha_max || '-')}
                </div>
                <div class="incident-list">${cards}</div>
            </div>
        `;
    }

    function renderSelectedZone(punto, hechos) {
        const loaded = Array.isArray(hechos);
        const detail = loaded
            ? `${number(hechos.length)} hecho(s) cargados en ficha`
            : 'Ficha lista para cargar detalle';

        selectedZone.innerHTML = `
            <h2>Zona seleccionada</h2>
            <p>${detail}</p>
            <dl>
                <div>
                    <dt>Total</dt>
                    <dd>${number(punto.total)}</dd>
                </div>
                <div>
                    <dt>Score</dt>
                    <dd>${Number(punto.score || 0).toFixed(1)}</dd>
                </div>
                <div>
                    <dt>Turnados</dt>
                    <dd>${number(punto.turnados)}</dd>
                </div>
                <div>
                    <dt>Pendientes</dt>
                    <dd>${number(punto.pendientes)}</dd>
                </div>
                <div>
                    <dt>Lat</dt>
                    <dd>${Number(punto.lat).toFixed(5)}</dd>
                </div>
                <div>
                    <dt>Lng</dt>
                    <dd>${Number(punto.lng).toFixed(5)}</dd>
                </div>
            </dl>
        `;
    }

    async function loadHechosDePunto(marker, punto) {
        marker.setPopupContent(buildPopupSkeleton(punto));
        renderSelectedZone(punto);

        const url = new URL(urlHechos, window.location.origin);
        appendFilters(url);
        url.searchParams.set('lat', punto.lat);
        url.searchParams.set('lng', punto.lng);
        url.searchParams.set('precision', controls.precision.value);

        try {
            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error('No se pudo cargar el detalle.');
            }

            const json = await response.json();
            const hechos = Array.isArray(json.data) ? json.data : [];
            marker.setPopupContent(buildPopupContent(punto, hechos));
            renderSelectedZone(punto, hechos);
        } catch (error) {
            console.error(error);
            marker.setPopupContent(`
                <div class="incident-popup">
                    <div class="incident-popup-title">Error</div>
                    <div class="incident-empty">No pude cargar los hechos de esta zona.</div>
                </div>
            `);
        }
    }

    function renderMetrics(summary) {
        const totals = summary?.totales || {};
        metricPuntos.textContent = number(totals.puntos);
        metricHechos.textContent = number(totals.hechos);
        metricTurnados.textContent = number(totals.turnados);
        metricPendientes.textContent = number(totals.pendientes);
        metricMaximo.textContent = number(totals.max_zona);
        metricHora.textContent = totals.hora_pico || '--:--';
        inspectorScore.textContent = Number(totals.score_max || 0).toFixed(1);
        headTotal.textContent = `${number(totals.hechos)} hechos`;

        const from = controls.desde.value || (totals.fecha_min || 'inicio');
        const to = controls.hasta.value || (totals.fecha_max || 'actual');
        headPeriod.textContent = `${from} a ${to}`;
    }

    function renderTopZones(summary) {
        const zones = summary?.top_zonas || [];
        const max = Math.max(1, ...zones.map((zone) => Number(zone.score || zone.total || 0)));
        topZonesCount.textContent = number(zones.length);

        if (!zones.length) {
            topZones.innerHTML = '<div class="incident-empty">No hay zonas con esos filtros.</div>';
            return;
        }

        topZones.innerHTML = zones.map((zone, index) => {
            const width = Math.round((Number(zone.score || zone.total || 0) / max) * 100);
            const color = colorForPoint(zone);

            return `
                <button type="button" class="zone-row" data-zone="${escapeHtml(pointKey(zone))}">
                    <div class="zone-main">
                        <span>#${index + 1} · ${Number(zone.lat).toFixed(4)}, ${Number(zone.lng).toFixed(4)}</span>
                        <strong>${number(zone.total)}</strong>
                    </div>
                    <div class="zone-meta">
                        <span>score ${Number(zone.score || 0).toFixed(1)}</span>
                        <span>turnados ${number(zone.turnados)}</span>
                        <span>pendientes ${number(zone.pendientes)}</span>
                    </div>
                    <div class="zone-meter"><span style="--w:${width}%;--c:${color}"></span></div>
                </button>
            `;
        }).join('');
    }

    function renderRanking(container, rows) {
        const items = Array.isArray(rows) ? rows : [];
        const max = Math.max(1, ...items.map((row) => Number(row.total || 0)));

        if (!items.length) {
            container.innerHTML = '<div class="incident-empty">Sin datos.</div>';
            return;
        }

        container.innerHTML = items.map((row, index) => {
            const width = Math.round((Number(row.total || 0) / max) * 100);
            const palette = ['#2563eb', '#0891b2', '#0f766e', '#d97706', '#6d28d9'];
            const color = palette[index % palette.length];

            return `
                <div class="rank-row">
                    <div class="rank-main">
                        <span title="${escapeHtml(row.label)}">${escapeHtml(row.label)}</span>
                        <strong>${number(row.total)}</strong>
                    </div>
                    <div class="rank-meter"><span style="--w:${width}%;--c:${color}"></span></div>
                </div>
            `;
        }).join('');
    }

    function renderHourChart(rows) {
        const items = Array.isArray(rows) ? rows : [];
        const max = Math.max(1, ...items.map((row) => Number(row.total || 0)));
        const total = items.reduce((acc, row) => acc + Number(row.total || 0), 0);
        hourTotal.textContent = number(total);

        hourChart.innerHTML = items.map((row) => {
            const height = Math.max(4, Math.round((Number(row.total || 0) / max) * 100));

            return `
                <div class="hour-bar" title="${escapeHtml(row.label)} · ${number(row.total)}">
                    <span style="--h:${height}%"></span>
                    <strong>${escapeHtml(String(row.label).slice(0, 2))}</strong>
                </div>
            `;
        }).join('');
    }

    function renderInsights(summary) {
        renderTopZones(summary);
        renderHourChart(summary?.por_hora || []);
        renderRanking(rankTipo, summary?.rankings?.tipo_hecho || []);
        renderRanking(rankMunicipio, summary?.rankings?.municipio || []);
    }

    async function cargar() {
        statusSub.textContent = 'Cargando capas, rankings y zonas calientes...';
        document.getElementById('btnCargar').disabled = true;

        const url = new URL(urlData, window.location.origin);
        appendFilters(url);

        try {
            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error('Error cargando datos.');
            }

            const json = await response.json();
            currentPuntos = Array.isArray(json.data) ? json.data : [];
            currentSummary = json.summary || {};

            renderMetrics(currentSummary);
            renderLayers();
            renderInsights(currentSummary);
            fitToPuntos();

            const totals = currentSummary?.totales || {};
            statusSub.textContent = currentPuntos.length
                ? `${number(totals.puntos)} puntos · ${number(totals.hechos)} hechos · max ${number(totals.max_zona)} por zona · precision ${controls.precision.value}`
                : 'No hay incidencias con esos filtros.';
        } catch (error) {
            console.error(error);
            statusSub.textContent = 'Ocurrio un error al cargar el mapa.';
        } finally {
            document.getElementById('btnCargar').disabled = false;
        }
    }

    function setPreset(preset) {
        document.querySelectorAll('.geo-presets button').forEach((button) => {
            button.classList.toggle('is-active', button.dataset.preset === preset);
        });

        if (preset === 'todo') {
            controls.desde.value = '';
            controls.hasta.value = '';
            return;
        }

        const today = new Date();
        const start = new Date(today);

        if (preset === 'hoy') {
            controls.desde.value = localIso(today);
            controls.hasta.value = localIso(today);
            return;
        }

        if (preset === '7') {
            start.setDate(today.getDate() - 6);
        } else if (preset === 'mes') {
            start.setDate(1);
        } else if (preset === 'anio') {
            start.setMonth(0, 1);
        }

        controls.desde.value = localIso(start);
        controls.hasta.value = localIso(today);
    }

    function resetFilters() {
        controls.desde.value = defaultFechaInicio;
        controls.hasta.value = defaultFechaFin;
        controls.horaDesde.value = '';
        controls.horaHasta.value = '';
        controls.precision.value = '3';
        controls.minTotal.value = '1';
        controls.limite.value = '3000';
        controls.basemap.value = 'osm';
        controls.showHeat.checked = true;
        controls.showClusters.checked = true;
        controls.showGrid.checked = true;
        controls.showLabels.checked = false;
        controls.soloRelevantes.checked = false;
        activeBbox = null;
        bboxStatus.textContent = 'Vista completa';

        multiFilters.forEach((key) => {
            Array.from(document.getElementById(key).options).forEach((option) => {
                option.selected = false;
            });
        });

        document.querySelectorAll('.geo-presets button').forEach((button) => {
            button.classList.toggle('is-active', button.dataset.preset === 'mes');
        });

        switchBasemap('osm');
        cargar();
    }

    function switchBasemap(name) {
        if (activeBase) {
            map.removeLayer(activeBase);
        }

        activeBase = baseLayers[name] || baseLayers.osm;
        activeBase.addTo(map);
    }

    function setBboxFromView() {
        const bounds = map.getBounds();
        activeBbox = [
            bounds.getSouth().toFixed(7),
            bounds.getWest().toFixed(7),
            bounds.getNorth().toFixed(7),
            bounds.getEast().toFixed(7)
        ].join(',');

        bboxStatus.textContent = 'Filtrado por vista actual';
        cargar();
    }

    function clearBbox() {
        activeBbox = null;
        bboxStatus.textContent = 'Vista completa';
        cargar();
    }

    function exportCsv() {
        if (!currentPuntos.length) {
            return;
        }

        const columns = ['lat', 'lng', 'total', 'score', 'turnados', 'pendientes', 'resueltos', 'relevantes', 'categoria', 'fecha_min', 'fecha_max'];
        const rows = currentPuntos.map((punto) => columns.map((column) => {
            const value = String(punto[column] ?? '').replace(/"/g, '""');
            return `"${value}"`;
        }).join(','));

        const csv = [columns.join(','), ...rows].join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `mapa_incidencias_${controls.desde.value || 'todo'}_${controls.hasta.value || 'actual'}.csv`;
        link.click();
        URL.revokeObjectURL(link.href);
    }

    function setFullscreenButton(active) {
        if (!btnFullscreen) {
            return;
        }

        btnFullscreen.innerHTML = active
            ? '<i class="fas fa-compress"></i><span>Salir</span>'
            : '<i class="fas fa-expand"></i><span>Pantalla</span>';
    }

    function refreshFullscreenLayout() {
        setTimeout(() => {
            map.invalidateSize();
            renderLayers();
        }, 160);
    }

    async function toggleFullscreen() {
        if (!fullscreenRoot) {
            return;
        }

        try {
            if (!document.fullscreenElement && fullscreenRoot.requestFullscreen) {
                await fullscreenRoot.requestFullscreen();
            } else if (document.fullscreenElement && document.exitFullscreen) {
                await document.exitFullscreen();
            } else {
                geoShell.classList.toggle('is-fullscreen');
                fullscreenRoot.classList.toggle('geo-fullscreen-mode');
                setFullscreenButton(geoShell.classList.contains('is-fullscreen'));
                refreshFullscreenLayout();
            }
        } catch (error) {
            geoShell.classList.toggle('is-fullscreen');
            fullscreenRoot.classList.toggle('geo-fullscreen-mode');
            setFullscreenButton(geoShell.classList.contains('is-fullscreen'));
            refreshFullscreenLayout();
        }
    }

    document.getElementById('btnCargar').addEventListener('click', cargar);
    btnFullscreen?.addEventListener('click', toggleFullscreen);
    document.getElementById('btnVista').addEventListener('click', setBboxFromView);
    document.getElementById('btnLimpiarVista').addEventListener('click', clearBbox);
    document.getElementById('btnReset').addEventListener('click', resetFilters);
    document.getElementById('btnExport').addEventListener('click', exportCsv);

    document.addEventListener('fullscreenchange', () => {
        const active = document.fullscreenElement === fullscreenRoot;
        geoShell?.classList.toggle('is-fullscreen', active);
        fullscreenRoot?.classList.toggle('geo-fullscreen-mode', active);
        setFullscreenButton(active);
        refreshFullscreenLayout();
    });

    controls.basemap.addEventListener('change', (event) => switchBasemap(event.target.value));
    [controls.showHeat, controls.showClusters, controls.showGrid, controls.showLabels].forEach((input) => {
        input.addEventListener('change', renderLayers);
    });

    document.querySelectorAll('.geo-presets button').forEach((button) => {
        button.addEventListener('click', () => {
            setPreset(button.dataset.preset);
            cargar();
        });
    });

    topZones.addEventListener('click', (event) => {
        const row = event.target.closest('.zone-row');
        if (!row) {
            return;
        }

        const marker = markerByKey.get(row.dataset.zone);
        if (!marker) {
            return;
        }

        const latLng = marker.getLatLng();
        map.setView(latLng, Math.max(map.getZoom(), 15));
        const punto = currentPuntos.find((item) => pointKey(item) === row.dataset.zone);

        const openMarker = () => {
            marker.openPopup();

            if (punto) {
                loadHechosDePunto(marker, punto);
            }
        };

        if (pointLayer && typeof pointLayer.zoomToShowLayer === 'function') {
            pointLayer.zoomToShowLayer(marker, openMarker);
        } else {
            openMarker();
        }
    });

    cargar();
</script>
@stop
