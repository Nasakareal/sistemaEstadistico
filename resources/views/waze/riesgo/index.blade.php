@extends('adminlte::page')

@section('title', 'Mapa Predictivo')

@section('content_header')
    <div class="risk-header">
        <div>
            <p class="risk-kicker">Centro predictivo</p>
            <h1>Mapa Predictivo</h1>
        </div>
        <div class="risk-header-meta">
            <span>{{ number_format((int) ($hechosConCoordenada ?? 0)) }} hechos geo</span>
            <span>{{ (int) ($diasDisponibles ?? 0) }} dias</span>
            <span>{{ number_format((int) ($wazeRecientes ?? 0)) }} Waze recientes</span>
        </div>
    </div>
@stop

@section('content')
<div class="risk-shell" id="riskConsole">
    <section class="risk-toolbar" aria-label="Controles del mapa predictivo">
        <div class="risk-preset-group" aria-label="Periodos rapidos">
            <button type="button" data-preset="30">30d</button>
            <button type="button" data-preset="90">90d</button>
            <button type="button" data-preset="180" class="is-active">180d</button>
            <button type="button" data-preset="todo">Todo</button>
        </div>

        <label class="risk-field">
            <span>Desde</span>
            <input type="date" id="desde" class="form-control" value="{{ $fechaInicio }}">
        </label>

        <label class="risk-field">
            <span>Hasta</span>
            <input type="date" id="hasta" class="form-control" value="{{ $fechaFin }}">
        </label>

        <label class="risk-field compact">
            <span>Horizonte</span>
            <select id="horizonte" class="form-control">
                <option value="1">1 h</option>
                <option value="3" selected>3 h</option>
                <option value="6">6 h</option>
                <option value="12">12 h</option>
            </select>
        </label>

        <label class="risk-field compact">
            <span>Waze</span>
            <select id="wazeHoras" class="form-control">
                <option value="4">4 h</option>
                <option value="8">8 h</option>
                <option value="12">12 h</option>
                <option value="24">24 h</option>
                <option value="72" selected>72 h</option>
                <option value="168">7 d</option>
            </select>
        </label>

        <label class="risk-field compact">
            <span>Ventana</span>
            <select id="ventana" class="form-control">
                <option value="30">30 m</option>
                <option value="60" selected>60 m</option>
                <option value="120">120 m</option>
                <option value="240">240 m</option>
            </select>
        </label>

        <label class="risk-field compact">
            <span>Precision</span>
            <select id="precision" class="form-control">
                <option value="2">Regional</option>
                <option value="3" selected>Operativa</option>
                <option value="4">Calle</option>
            </select>
        </label>

        <label class="risk-field compact">
            <span>Nodos</span>
            <select id="limite" class="form-control">
                <option value="30">30</option>
                <option value="50" selected>50</option>
                <option value="80">80</option>
                <option value="120">120</option>
            </select>
        </label>

        <div class="risk-layer-toggles" aria-label="Capas">
            <label><input type="checkbox" id="verRiesgo" checked> Riesgo</label>
            <label><input type="checkbox" id="verWaze" checked> Waze</label>
            <label><input type="checkbox" id="verHechos"> Historico</label>
            <label><input type="checkbox" id="verMatches" checked> Matches</label>
        </div>

        <div class="risk-actions">
            <button type="button" class="btn risk-primary" id="btnCargar">
                <i class="fa-solid fa-rotate"></i>
                <span>Actualizar</span>
            </button>
            <button type="button" class="btn risk-secondary" id="btnVista" aria-label="Centrar mapa">
                <i class="fa-solid fa-crosshairs"></i>
            </button>
            <button type="button" class="btn risk-secondary" id="btnFullscreen" aria-label="Pantalla completa">
                <i class="fa-solid fa-expand"></i>
            </button>
        </div>
    </section>

    <section class="risk-kpi-strip" aria-label="Indicadores predictivos">
        <div class="risk-kpi danger">
            <span>Riesgo top</span>
            <strong id="k_top">0</strong>
            <small id="k_top_label">Sin senal</small>
        </div>
        <div class="risk-kpi warn">
            <span>Zonas altas</span>
            <strong id="k_zonas">0</strong>
            <small>prioridad</small>
        </div>
        <div class="risk-kpi">
            <span>Hechos</span>
            <strong id="k_hechos">0</strong>
            <small id="k_periodo">periodo</small>
        </div>
        <div class="risk-kpi live">
            <span>Waze</span>
            <strong id="k_waze">0</strong>
            <small><span id="k_jams">0</span> jams / <span id="k_accidents">0</span> acc.</small>
        </div>
        <div class="risk-kpi signal">
            <span>Matches</span>
            <strong id="k_matches">0</strong>
            <small id="k_hora">hora base</small>
        </div>
        <div class="risk-status" id="status-sub">Listo.</div>
    </section>

    <div class="risk-workspace">
        <main class="risk-map-panel">
            <div id="map" class="risk-map"></div>
            <div class="risk-legend">
                <span><i class="critical"></i>Critico</span>
                <span><i class="high"></i>Alto</span>
                <span><i class="watch"></i>Vigilancia</span>
                <span><i class="live"></i>Waze</span>
            </div>
        </main>

        <aside class="risk-side-panel">
            <section class="risk-panel risk-selected" id="selected-zone">
                <div class="risk-panel-head">
                    <h2>Zona seleccionada</h2>
                    <span>--</span>
                </div>
                <p>Selecciona un nodo para ver senales y accion sugerida.</p>
            </section>

            <section class="risk-panel">
                <div class="risk-panel-head">
                    <h2>Prioridad operativa</h2>
                    <span id="top-zones-count">0</span>
                </div>
                <div id="top-zones" class="risk-zone-list"></div>
            </section>
        </aside>
    </div>

    <section class="risk-intel-grid">
        <div class="risk-panel">
            <div class="risk-panel-head">
                <h2>Firma horaria</h2>
                <span id="hour-total">0</span>
            </div>
            <div id="hour-chart" class="risk-hour-chart"></div>
        </div>

        <div class="risk-panel">
            <div class="risk-panel-head">
                <h2>Tipo de hecho</h2>
                <span>top</span>
            </div>
            <div id="rank-tipo" class="risk-ranking"></div>
        </div>

        <div class="risk-panel">
            <div class="risk-panel-head">
                <h2>Municipios</h2>
                <span>top</span>
            </div>
            <div id="rank-municipio" class="risk-ranking"></div>
        </div>

        <div class="risk-panel">
            <div class="risk-panel-head">
                <h2>Bandas</h2>
                <span id="band-total">0</span>
            </div>
            <div id="band-bars" class="risk-bands"></div>
        </div>
    </section>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    :root {
        --risk-bg: #081018;
        --risk-surface: #0d1720;
        --risk-surface-2: #111d27;
        --risk-line: #263b4a;
        --risk-soft-line: rgba(148, 190, 210, .20);
        --risk-text: #eef7ff;
        --risk-muted: #a8b8c5;
        --risk-cyan: #21c7df;
        --risk-green: #35d08f;
        --risk-amber: #f5a623;
        --risk-red: #ff4d4d;
        --risk-violet: #c084fc;
        --risk-radius: 8px;
    }

    .content-wrapper,
    .content-wrapper > .content,
    .content-wrapper > .content-header {
        background: var(--risk-bg) !important;
    }

    .content-wrapper > .content-header {
        padding: 14px 18px 8px;
        border-bottom: 1px solid rgba(255,255,255,.08);
    }

    .risk-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 12px;
        color: var(--risk-text);
    }

    .risk-kicker {
        margin: 0 0 3px;
        color: var(--risk-green);
        font-size: .72rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0;
    }

    .risk-header h1 {
        margin: 0;
        color: #fff !important;
        font-size: 1.55rem;
        line-height: 1.1;
        font-weight: 900;
        letter-spacing: 0;
    }

    .risk-header-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .risk-header-meta span {
        min-height: 32px;
        display: inline-flex;
        align-items: center;
        border: 1px solid var(--risk-line);
        border-radius: var(--risk-radius);
        padding: 6px 10px;
        background: #0b141d;
        color: #dff5ff;
        font-size: .82rem;
        font-weight: 800;
    }

    .risk-shell {
        display: grid;
        gap: 10px;
        padding-bottom: 16px;
        color: var(--risk-text);
    }

    .risk-toolbar,
    .risk-kpi-strip,
    .risk-map-panel,
    .risk-side-panel,
    .risk-panel {
        border: 1px solid var(--risk-line);
        border-radius: var(--risk-radius);
        background: var(--risk-surface);
        box-shadow: 0 12px 30px rgba(0, 0, 0, .24);
    }

    .risk-toolbar {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        flex-wrap: wrap;
        padding: 10px;
    }

    .risk-preset-group {
        display: inline-grid;
        grid-template-columns: repeat(4, 50px);
        gap: 6px;
    }

    .risk-preset-group button,
    .risk-actions .btn {
        min-height: 38px;
        border-radius: var(--risk-radius);
        font-weight: 900;
    }

    .risk-preset-group button {
        border: 1px solid var(--risk-line);
        background: var(--risk-surface-2);
        color: #d7e8f2;
    }

    .risk-preset-group button.is-active,
    .risk-preset-group button:hover {
        border-color: var(--risk-cyan);
        background: rgba(33, 199, 223, .14);
        color: #fff;
    }

    .risk-field {
        display: grid;
        gap: 4px;
        min-width: 136px;
        margin: 0;
    }

    .risk-field.compact {
        min-width: 96px;
    }

    .risk-field span {
        color: var(--risk-muted);
        font-size: .72rem;
        font-weight: 900;
    }

    .risk-field .form-control {
        min-height: 38px;
        border: 1px solid var(--risk-line);
        border-radius: var(--risk-radius);
        background-color: #09131c !important;
        color: #f3fbff !important;
        font-weight: 800;
        color-scheme: dark;
    }

    .risk-layer-toggles {
        min-height: 38px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        padding: 6px 8px;
        border: 1px solid var(--risk-line);
        border-radius: var(--risk-radius);
        background: #09131c;
    }

    .risk-layer-toggles label {
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #d7e8f2;
        font-size: .78rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .risk-layer-toggles input {
        accent-color: var(--risk-cyan);
    }

    .risk-actions {
        display: inline-grid;
        grid-template-columns: minmax(108px, auto) 38px 38px;
        gap: 6px;
        margin-left: auto;
    }

    .risk-primary,
    .risk-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
    }

    .risk-primary {
        border: 0;
        background: var(--risk-cyan);
        color: #031117;
    }

    .risk-primary:hover,
    .risk-primary:focus {
        color: #031117;
        filter: brightness(1.08);
    }

    .risk-secondary {
        border: 1px solid var(--risk-line);
        background: var(--risk-surface-2);
        color: #e9f6ff;
    }

    .risk-secondary:hover,
    .risk-secondary:focus {
        border-color: var(--risk-cyan);
        color: #fff;
    }

    .risk-kpi-strip {
        display: grid;
        grid-template-columns: repeat(5, minmax(120px, 1fr)) minmax(220px, 1.4fr);
        gap: 0;
        overflow: hidden;
    }

    .risk-kpi,
    .risk-status {
        min-height: 72px;
        padding: 10px 12px;
        border-right: 1px solid rgba(255,255,255,.08);
    }

    .risk-kpi span,
    .risk-kpi small,
    .risk-status {
        color: var(--risk-muted);
        font-size: .76rem;
        font-weight: 800;
    }

    .risk-kpi strong {
        display: block;
        margin-top: 2px;
        color: #fff;
        font-size: 1.45rem;
        line-height: 1.05;
        font-weight: 900;
    }

    .risk-kpi.danger strong { color: var(--risk-red); }
    .risk-kpi.warn strong { color: var(--risk-amber); }
    .risk-kpi.live strong { color: var(--risk-green); }
    .risk-kpi.signal strong { color: var(--risk-cyan); }

    .risk-status {
        display: flex;
        align-items: center;
        border-right: 0;
        color: #d7e8f2;
    }

    .risk-workspace {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 360px;
        gap: 10px;
        min-width: 0;
    }

    .risk-map-panel {
        position: relative;
        min-width: 0;
        overflow: hidden;
    }

    .risk-map {
        width: 100%;
        height: min(64vh, 620px);
        min-height: 470px;
        background: #071017;
    }

    .leaflet-container {
        background: #071017;
        font-family: inherit;
    }

    .risk-legend {
        position: absolute;
        left: 12px;
        bottom: 12px;
        z-index: 420;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 8px;
        border: 1px solid rgba(255,255,255,.14);
        border-radius: var(--risk-radius);
        background: rgba(7, 16, 23, .88);
        color: #d7e8f2;
        font-size: .75rem;
        font-weight: 900;
    }

    .risk-legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .risk-legend i {
        width: 10px;
        height: 10px;
        display: inline-block;
        border-radius: 50%;
    }

    .risk-legend .critical { background: var(--risk-red); }
    .risk-legend .high { background: var(--risk-amber); }
    .risk-legend .watch { background: var(--risk-cyan); }
    .risk-legend .live { background: var(--risk-green); }

    .risk-side-panel {
        display: grid;
        grid-template-rows: auto minmax(0, 1fr);
        gap: 10px;
        min-height: 0;
        padding: 10px;
    }

    .risk-panel {
        min-width: 0;
        padding: 12px;
        background: #0b141d;
    }

    .risk-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .risk-panel-head h2 {
        margin: 0;
        color: #fff;
        font-size: .94rem;
        font-weight: 900;
    }

    .risk-panel-head span {
        color: var(--risk-green);
        font-size: .78rem;
        font-weight: 900;
    }

    .risk-selected p {
        margin: 8px 0 0;
        color: var(--risk-muted);
        font-weight: 800;
    }

    .risk-selected dl {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 7px;
        margin: 10px 0 0;
    }

    .risk-selected dl div {
        border: 1px solid var(--risk-soft-line);
        border-radius: var(--risk-radius);
        padding: 8px;
        background: #101b25;
    }

    .risk-selected dt {
        margin: 0;
        color: var(--risk-muted);
        font-size: .7rem;
        font-weight: 900;
    }

    .risk-selected dd {
        margin: 2px 0 0;
        color: #fff;
        font-size: .95rem;
        font-weight: 900;
    }

    .risk-zone-list {
        display: grid;
        gap: 7px;
        margin-top: 10px;
        max-height: 430px;
        overflow: auto;
        padding-right: 2px;
        scrollbar-width: thin;
    }

    .risk-zone-row {
        width: 100%;
        border: 1px solid var(--risk-soft-line);
        border-left: 4px solid var(--zone-color, var(--risk-cyan));
        border-radius: var(--risk-radius);
        padding: 9px 10px;
        background: #101b25;
        color: var(--risk-text);
        text-align: left;
    }

    .risk-zone-row:hover,
    .risk-zone-row.is-active {
        background: #142433;
        border-color: var(--zone-color, var(--risk-cyan));
    }

    .risk-zone-title,
    .risk-zone-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .risk-zone-title strong {
        min-width: 0;
        color: #fff;
        font-size: .86rem;
        font-weight: 900;
    }

    .risk-zone-title span {
        color: var(--zone-color, var(--risk-cyan));
        font-weight: 900;
        flex: 0 0 auto;
    }

    .risk-zone-cell {
        margin-top: 3px;
        color: var(--risk-muted);
        font-size: .74rem;
        font-weight: 800;
        word-break: break-word;
    }

    .risk-zone-meta {
        margin-top: 5px;
        color: var(--risk-muted);
        font-size: .72rem;
        font-weight: 800;
    }

    .risk-meter {
        height: 4px;
        margin-top: 7px;
        overflow: hidden;
        border-radius: 999px;
        background: rgba(255,255,255,.08);
    }

    .risk-meter span {
        display: block;
        width: var(--w, 0%);
        height: 100%;
        background: var(--c, var(--risk-cyan));
    }

    .risk-intel-grid {
        display: grid;
        grid-template-columns: 1.25fr 1fr 1fr .85fr;
        gap: 10px;
    }

    .risk-hour-chart {
        height: 112px;
        display: grid;
        grid-template-columns: repeat(24, minmax(7px, 1fr));
        align-items: end;
        gap: 3px;
        margin-top: 12px;
    }

    .risk-hour-bar {
        min-width: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        gap: 4px;
    }

    .risk-hour-bar span {
        width: 100%;
        min-height: 4px;
        height: var(--h, 0%);
        border-radius: 4px 4px 0 0;
        background: var(--risk-cyan);
    }

    .risk-hour-bar strong {
        color: var(--risk-muted);
        font-size: .58rem;
        font-weight: 900;
    }

    .risk-ranking,
    .risk-bands {
        display: grid;
        gap: 8px;
        margin-top: 12px;
    }

    .risk-rank-row,
    .risk-band-row {
        display: grid;
        gap: 5px;
        color: #eaf7ff;
        font-size: .78rem;
        font-weight: 800;
    }

    .risk-rank-main,
    .risk-band-main {
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }

    .risk-rank-main span {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .risk-empty {
        padding: 10px;
        border: 1px dashed rgba(255,255,255,.18);
        border-radius: var(--risk-radius);
        color: var(--risk-muted);
        font-weight: 800;
    }

    .risk-popup {
        min-width: 220px;
        color: #0b141d;
    }

    .risk-popup-title {
        font-weight: 900;
        font-size: .96rem;
    }

    .risk-popup-meta {
        margin-top: 5px;
        color: #475569;
        font-size: .78rem;
        font-weight: 800;
    }

    .risk-popup-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
        margin-top: 10px;
    }

    .risk-popup-grid div {
        border: 1px solid #dbe5ef;
        border-radius: 8px;
        padding: 6px;
        background: #f8fafc;
    }

    .risk-popup-grid span {
        display: block;
        color: #64748b;
        font-size: .68rem;
        font-weight: 900;
    }

    .risk-popup-grid strong {
        display: block;
        color: #0f172a;
        font-size: .9rem;
        font-weight: 900;
    }

    @media (max-width: 1400px) {
        .risk-kpi-strip {
            grid-template-columns: repeat(3, minmax(120px, 1fr));
        }

        .risk-status {
            grid-column: span 3;
        }

        .risk-workspace {
            grid-template-columns: minmax(0, 1fr) 330px;
        }

        .risk-intel-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 1050px) {
        .risk-workspace {
            grid-template-columns: 1fr;
        }

        .risk-map {
            height: 560px;
        }

        .risk-zone-list {
            max-height: 320px;
        }
    }

    @media (max-width: 760px) {
        .risk-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .risk-preset-group {
            grid-template-columns: repeat(4, 1fr);
            width: 100%;
        }

        .risk-field,
        .risk-field.compact,
        .risk-layer-toggles,
        .risk-actions {
            width: 100%;
        }

        .risk-actions {
            grid-template-columns: 1fr 42px 42px;
        }

        .risk-kpi-strip,
        .risk-intel-grid {
            grid-template-columns: 1fr;
        }

        .risk-status {
            grid-column: auto;
        }

        .risk-map {
            min-height: 480px;
        }
    }
</style>
@stop

@section('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const urlData = @json(route('waze.riesgo.data'));
    const initialDates = {
        min: @json($fechaMin ?? null),
        max: @json($fechaMax ?? null),
        desde: @json($fechaInicio),
        hasta: @json($fechaFin)
    };

    const centerDefault = [19.703, -101.186];
    const markerByCell = new Map();
    let activeZoneCell = null;
    let latestRiskCells = [];

    const controls = {
        desde: document.getElementById('desde'),
        hasta: document.getElementById('hasta'),
        horizonte: document.getElementById('horizonte'),
        wazeHoras: document.getElementById('wazeHoras'),
        ventana: document.getElementById('ventana'),
        precision: document.getElementById('precision'),
        limite: document.getElementById('limite'),
        verRiesgo: document.getElementById('verRiesgo'),
        verWaze: document.getElementById('verWaze'),
        verHechos: document.getElementById('verHechos'),
        verMatches: document.getElementById('verMatches')
    };

    const dom = {
        status: document.getElementById('status-sub'),
        kTop: document.getElementById('k_top'),
        kTopLabel: document.getElementById('k_top_label'),
        kZonas: document.getElementById('k_zonas'),
        kHechos: document.getElementById('k_hechos'),
        kPeriodo: document.getElementById('k_periodo'),
        kWaze: document.getElementById('k_waze'),
        kJams: document.getElementById('k_jams'),
        kAccidents: document.getElementById('k_accidents'),
        kMatches: document.getElementById('k_matches'),
        kHora: document.getElementById('k_hora'),
        selectedZone: document.getElementById('selected-zone'),
        topZones: document.getElementById('top-zones'),
        topZonesCount: document.getElementById('top-zones-count'),
        hourChart: document.getElementById('hour-chart'),
        hourTotal: document.getElementById('hour-total'),
        rankTipo: document.getElementById('rank-tipo'),
        rankMunicipio: document.getElementById('rank-municipio'),
        bandBars: document.getElementById('band-bars'),
        bandTotal: document.getElementById('band-total')
    };

    const map = L.map('map', {
        zoomControl: true,
        preferCanvas: true
    }).setView(centerDefault, 12);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 20,
        attribution: '&copy; OpenStreetMap &copy; CARTO'
    }).addTo(map);

    const layerHechos = L.layerGroup();
    const layerRiesgo = L.layerGroup().addTo(map);
    const layerWaze = L.layerGroup().addTo(map);
    const layerMatches = L.layerGroup().addTo(map);

    setTimeout(() => map.invalidateSize(), 220);

    function esc(value) {
        if (value === null || value === undefined) return '';
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function number(value) {
        const n = Number(value || 0);
        return Number.isFinite(n) ? n.toLocaleString('es-MX') : '0';
    }

    function numeric(value) {
        const n = Number(value);
        return Number.isFinite(n) ? n : null;
    }

    function localIso(date) {
        const copy = new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
        return copy.toISOString().slice(0, 10);
    }

    function coord(value) {
        const n = Number(value || 0);
        return Number.isFinite(n) ? n.toFixed(5) : '0.00000';
    }

    function colorForLevel(level, fallback) {
        if (fallback) return fallback;
        if (level === 'critico') return '#ff4d4d';
        if (level === 'alto') return '#f5a623';
        if (level === 'vigilancia') return '#21c7df';
        return '#35d08f';
    }

    function radiusForScore(score) {
        const value = Number(score || 0);
        return Math.max(5, Math.min(15, 4 + Math.sqrt(value) * 1.05));
    }

    function layerToggle() {
        controls.verRiesgo.checked ? map.addLayer(layerRiesgo) : map.removeLayer(layerRiesgo);
        controls.verWaze.checked ? map.addLayer(layerWaze) : map.removeLayer(layerWaze);
        controls.verHechos.checked ? map.addLayer(layerHechos) : map.removeLayer(layerHechos);
        controls.verMatches.checked ? map.addLayer(layerMatches) : map.removeLayer(layerMatches);
    }

    function clearLayers() {
        markerByCell.clear();
        layerHechos.clearLayers();
        layerRiesgo.clearLayers();
        layerWaze.clearLayers();
        layerMatches.clearLayers();
    }

    function riskPopup(zone) {
        return `
            <div class="risk-popup">
                <div class="risk-popup-title">${esc(zone.nivel_label)} - score ${esc(zone.score)}</div>
                <div class="risk-popup-meta">
                    ${esc(zone.accion || 'Observacion')}<br>
                    ${coord(zone.lat)}, ${coord(zone.lng)} - confianza ${esc(zone.confidence)}%
                </div>
                <div class="risk-popup-grid">
                    <div><span>Historico</span><strong>${number(zone.hechos_hist)}</strong></div>
                    <div><span>30 dias</span><strong>${number(zone.hechos_30d)}</strong></div>
                    <div><span>Waze</span><strong>${number(zone.waze_total)}</strong></div>
                    <div><span>Matches</span><strong>${number(zone.matches)}</strong></div>
                    <div><span>Hora</span><strong>${number(zone.hechos_horizonte)}</strong></div>
                    <div><span>Tendencia</span><strong>${Number(zone.trend_pct || 0).toFixed(1)}%</strong></div>
                </div>
            </div>
        `;
    }

    function selectZone(zone) {
        activeZoneCell = zone.cell;
        document.querySelectorAll('.risk-zone-row').forEach((row) => {
            row.classList.toggle('is-active', row.dataset.cell === activeZoneCell);
        });

        const signals = zone.signals || {};
        const color = colorForLevel(zone.nivel, zone.color);

        dom.selectedZone.innerHTML = `
            <div class="risk-panel-head">
                <h2>${esc(zone.nivel_label)} - ${esc(zone.accion)}</h2>
                <span style="color:${esc(color)}">${Number(zone.score || 0).toFixed(1)}</span>
            </div>
            <p>${coord(zone.lat)}, ${coord(zone.lng)} - confianza ${number(zone.confidence)}%</p>
            <dl>
                <div><dt>Historico</dt><dd>${number(zone.hechos_hist)}</dd></div>
                <div><dt>30 dias</dt><dd>${number(zone.hechos_30d)}</dd></div>
                <div><dt>Waze vivo</dt><dd>${number(zone.waze_total)}</dd></div>
                <div><dt>Matches</dt><dd>${number(zone.matches)}</dd></div>
                <div><dt>Hora</dt><dd>${number(zone.hechos_horizonte)}</dd></div>
                <div><dt>Tendencia</dt><dd>${Number(zone.trend_pct || 0).toFixed(1)}%</dd></div>
                <div><dt>Severidad</dt><dd>${Number(signals.severidad || 0).toFixed(1)}</dd></div>
                <div><dt>Waze score</dt><dd>${Number(signals.waze || 0).toFixed(1)}</dd></div>
            </dl>
        `;
    }

    function addRiskCells(items) {
        (items || []).forEach((zone) => {
            const lat = numeric(zone.lat);
            const lng = numeric(zone.lng);
            if (lat === null || lng === null) return;

            const color = colorForLevel(zone.nivel, zone.color);
            const marker = L.circleMarker([lat, lng], {
                radius: radiusForScore(zone.score),
                color: color,
                fillColor: color,
                fillOpacity: zone.nivel === 'critico' || zone.nivel === 'alto' ? .36 : .2,
                opacity: .82,
                weight: 1.5
            })
                .bindPopup(riskPopup(zone))
                .on('click', () => selectZone(zone));

            markerByCell.set(zone.cell, marker);
            marker.addTo(layerRiesgo);
        });
    }

    function addHechosCells(items) {
        (items || []).forEach((point) => {
            const lat = numeric(point.lat);
            const lng = numeric(point.lng);
            if (lat === null || lng === null) return;

            const total = Number(point.total || point.hechos_hist || 0);
            L.circleMarker([lat, lng], {
                radius: Math.max(2, Math.min(7, 2 + Math.sqrt(total))),
                color: '#8bb8d8',
                fillColor: '#8bb8d8',
                fillOpacity: .14,
                opacity: .45,
                weight: 1
            }).bindPopup(`
                <div class="risk-popup">
                    <div class="risk-popup-title">Historico</div>
                    <div class="risk-popup-meta">${number(total)} hechos - ${coord(lat)}, ${coord(lng)}</div>
                </div>
            `).addTo(layerHechos);
        });
    }

    function addWazePoints(items) {
        (items || []).forEach((point) => {
            const lat = numeric(point.lat);
            const lng = numeric(point.lng);
            if (lat === null || lng === null) return;

            const type = String(point.type || '').toUpperCase();
            const color = type === 'ACCIDENT' ? '#ff4d4d' : (type === 'JAM' ? '#f5a623' : '#35d08f');

            L.circleMarker([lat, lng], {
                radius: type === 'ACCIDENT' ? 5 : 4,
                color: color,
                fillColor: color,
                fillOpacity: .78,
                opacity: .92,
                weight: 1
            }).bindPopup(`
                <div class="risk-popup">
                    <div class="risk-popup-title">Waze - ${esc(type || '-')}</div>
                    <div class="risk-popup-meta">
                        ${esc(point.street_norm || point.street || 'Sin calle')}<br>
                        ${esc(point.published_at || '')}
                    </div>
                </div>
            `).addTo(layerWaze);
        });
    }

    function addMatches(items) {
        (items || []).forEach((match) => {
            const lat = numeric(match.lat);
            const lng = numeric(match.lng);
            if (lat === null || lng === null) return;

            L.circleMarker([lat, lng], {
                radius: 6,
                color: '#c084fc',
                fillColor: '#c084fc',
                fillOpacity: .75,
                opacity: .95,
                weight: 2
            }).bindPopup(`
                <div class="risk-popup">
                    <div class="risk-popup-title">Match Waze-Hecho</div>
                    <div class="risk-popup-meta">
                        Hecho ${esc(match.hecho_id || '-')}<br>
                        Accidente: ${esc(match.waze_accident_at || '-')}<br>
                        Jam: ${esc(match.waze_first_jam_at || '-')}
                    </div>
                </div>
            `).addTo(layerMatches);
        });
    }

    function renderKpis(kpis, filters) {
        dom.kTop.textContent = Number(kpis?.top || 0).toFixed(1);
        dom.kTopLabel.textContent = kpis?.top_nivel || 'Sin senal';
        dom.kZonas.textContent = number(kpis?.zonas_altas || 0);
        dom.kHechos.textContent = number(kpis?.hechos || 0);
        dom.kWaze.textContent = number(kpis?.waze_total || 0);
        dom.kJams.textContent = number(kpis?.jams || 0);
        dom.kAccidents.textContent = number(kpis?.accidents || 0);
        dom.kMatches.textContent = number(kpis?.matches || 0);
        dom.kHora.textContent = `${kpis?.hora_base || '--:--'} / ${number(kpis?.horizonte || 0)}h`;
        dom.kPeriodo.textContent = `${filters?.desde || controls.desde.value} a ${filters?.hasta || controls.hasta.value}`;
    }

    function renderTopZones(zones) {
        const items = Array.isArray(zones) ? zones : [];
        dom.topZonesCount.textContent = number(items.length);

        if (!items.length) {
            dom.topZones.innerHTML = '<div class="risk-empty">Sin nodos con esos filtros.</div>';
            return;
        }

        const max = Math.max(1, ...items.map((zone) => Number(zone.score || 0)));
        dom.topZones.innerHTML = items.map((zone, index) => {
            const color = colorForLevel(zone.nivel, zone.color);
            const width = Math.round((Number(zone.score || 0) / max) * 100);
            return `
                <button type="button" class="risk-zone-row" data-cell="${esc(zone.cell)}" style="--zone-color:${esc(color)}">
                    <div class="risk-zone-title">
                        <strong>#${index + 1} - ${esc(zone.nivel_label)}</strong>
                        <span>${Number(zone.score || 0).toFixed(1)}</span>
                    </div>
                    <div class="risk-zone-cell">${coord(zone.lat)}, ${coord(zone.lng)}</div>
                    <div class="risk-zone-meta">
                        <span>${number(zone.hechos_hist)} hist</span>
                        <span>${number(zone.waze_total)} Waze</span>
                        <span>${number(zone.confidence)}%</span>
                    </div>
                    <div class="risk-meter"><span style="--w:${width}%;--c:${esc(color)}"></span></div>
                </button>
            `;
        }).join('');
    }

    function renderHourChart(rows) {
        const items = Array.isArray(rows) ? rows : [];
        const total = items.reduce((acc, row) => acc + Number(row.total || 0), 0);
        const max = Math.max(1, ...items.map((row) => Number(row.total || 0)));

        dom.hourTotal.textContent = number(total);
        dom.hourChart.innerHTML = items.map((row) => {
            const height = Math.max(4, Math.round((Number(row.total || 0) / max) * 100));
            return `
                <div class="risk-hour-bar" title="${esc(row.label)} - ${number(row.total)}">
                    <span style="--h:${height}%"></span>
                    <strong>${esc(String(row.label || '').slice(0, 2))}</strong>
                </div>
            `;
        }).join('');
    }

    function renderRanking(container, rows) {
        const items = Array.isArray(rows) ? rows : [];
        const max = Math.max(1, ...items.map((row) => Number(row.total || 0)));

        if (!items.length) {
            container.innerHTML = '<div class="risk-empty">Sin datos.</div>';
            return;
        }

        const palette = ['#21c7df', '#35d08f', '#f5a623', '#c084fc', '#ff4d4d'];
        container.innerHTML = items.map((row, index) => {
            const width = Math.round((Number(row.total || 0) / max) * 100);
            const color = palette[index % palette.length];
            return `
                <div class="risk-rank-row">
                    <div class="risk-rank-main">
                        <span title="${esc(row.label)}">${esc(row.label)}</span>
                        <strong>${number(row.total)}</strong>
                    </div>
                    <div class="risk-meter"><span style="--w:${width}%;--c:${color}"></span></div>
                </div>
            `;
        }).join('');
    }

    function renderBands(bands) {
        const order = [
            ['critico', 'Critico', '#ff4d4d'],
            ['alto', 'Alto', '#f5a623'],
            ['vigilancia', 'Vigilancia', '#21c7df'],
            ['latente', 'Latente', '#35d08f']
        ];
        const values = order.map(([key, label, color]) => ({ key, label, color, total: Number(bands?.[key] || 0) }));
        const total = values.reduce((acc, item) => acc + item.total, 0);
        const max = Math.max(1, ...values.map((item) => item.total));

        dom.bandTotal.textContent = number(total);
        dom.bandBars.innerHTML = values.map((item) => {
            const width = Math.round((item.total / max) * 100);
            return `
                <div class="risk-band-row">
                    <div class="risk-band-main">
                        <span>${item.label}</span>
                        <strong>${number(item.total)}</strong>
                    </div>
                    <div class="risk-meter"><span style="--w:${width}%;--c:${item.color}"></span></div>
                </div>
            `;
        }).join('');
    }

    function renderIntel(summary) {
        renderTopZones(summary?.top_zonas || []);
        renderHourChart(summary?.por_hora || []);
        renderRanking(dom.rankTipo, summary?.ranking_tipo || []);
        renderRanking(dom.rankMunicipio, summary?.ranking_municipio || []);
        renderBands(summary?.bandas || {});
    }

    function fitToRisk(items) {
        const coords = (items || [])
            .map((zone) => [numeric(zone.lat), numeric(zone.lng)])
            .filter(([lat, lng]) => lat !== null && lng !== null);

        if (coords.length === 1) {
            map.setView(coords[0], 14);
        } else if (coords.length > 1) {
            map.fitBounds(coords, { padding: [34, 34], maxZoom: 14 });
        }
    }

    function buildUrl() {
        const url = new URL(urlData, window.location.origin);
        url.searchParams.set('desde', controls.desde.value);
        url.searchParams.set('hasta', controls.hasta.value);
        url.searchParams.set('horizonte', controls.horizonte.value);
        url.searchParams.set('waze_horas', controls.wazeHoras.value);
        url.searchParams.set('ventana', controls.ventana.value);
        url.searchParams.set('precision', controls.precision.value);
        url.searchParams.set('limite', controls.limite.value);
        return url;
    }

    async function cargar({ fit = true } = {}) {
        clearLayers();
        dom.status.textContent = 'Procesando senal predictiva...';
        document.getElementById('btnCargar').disabled = true;

        try {
            const response = await fetch(buildUrl().toString(), { headers: { 'Accept': 'application/json' } });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const json = await response.json();
            latestRiskCells = Array.isArray(json.riesgo_cells) ? json.riesgo_cells : [];

            addHechosCells(json.hechos_cells || []);
            addRiskCells(latestRiskCells);
            addWazePoints(json.waze_points || []);
            addMatches(json.matches || []);
            layerToggle();
            renderKpis(json.kpis || {}, json.filters || {});
            renderIntel(json.summary || {});

            if (latestRiskCells.length) {
                selectZone(latestRiskCells[0]);
                if (fit) fitToRisk(latestRiskCells.slice(0, 30));
            } else {
                dom.selectedZone.innerHTML = `
                    <div class="risk-panel-head"><h2>Zona seleccionada</h2><span>--</span></div>
                    <p>No hay senales con esos filtros.</p>
                `;
            }

            const k = json.kpis || {};
            dom.status.textContent = `${number(k.celdas || 0)} nodos cargados / ${number(k.zonas_altas || 0)} zonas altas / Waze ${number(k.waze_total || 0)}`;
        } catch (error) {
            console.error(error);
            dom.status.textContent = 'No se pudo cargar la senal predictiva.';
        } finally {
            document.getElementById('btnCargar').disabled = false;
            setTimeout(() => map.invalidateSize(), 80);
        }
    }

    function setPreset(preset) {
        document.querySelectorAll('.risk-preset-group button').forEach((button) => {
            button.classList.toggle('is-active', button.dataset.preset === preset);
        });

        const maxDate = initialDates.max ? new Date(`${initialDates.max}T12:00:00`) : new Date();

        if (preset === 'todo') {
            controls.desde.value = initialDates.min || initialDates.desde;
            controls.hasta.value = initialDates.max || initialDates.hasta;
            return;
        }

        const days = Number(preset || 180);
        const start = new Date(maxDate);
        start.setDate(maxDate.getDate() - Math.max(0, days - 1));
        controls.desde.value = localIso(start);
        controls.hasta.value = localIso(maxDate);
    }

    document.getElementById('btnCargar').addEventListener('click', () => cargar());
    document.getElementById('btnVista').addEventListener('click', () => fitToRisk(latestRiskCells.slice(0, 30)));
    document.getElementById('btnFullscreen').addEventListener('click', async () => {
        const root = document.getElementById('riskConsole');
        if (!document.fullscreenElement && root.requestFullscreen) {
            await root.requestFullscreen();
        } else if (document.exitFullscreen) {
            await document.exitFullscreen();
        }
        setTimeout(() => map.invalidateSize(), 180);
    });

    [controls.verRiesgo, controls.verWaze, controls.verHechos, controls.verMatches].forEach((input) => {
        input.addEventListener('change', layerToggle);
    });

    [controls.horizonte, controls.wazeHoras, controls.ventana, controls.precision, controls.limite].forEach((input) => {
        input.addEventListener('change', () => cargar({ fit: false }));
    });

    document.querySelectorAll('.risk-preset-group button').forEach((button) => {
        button.addEventListener('click', () => {
            setPreset(button.dataset.preset);
            cargar();
        });
    });

    dom.topZones.addEventListener('click', (event) => {
        const row = event.target.closest('.risk-zone-row');
        if (!row) return;

        const zone = latestRiskCells.find((item) => item.cell === row.dataset.cell);
        const marker = markerByCell.get(row.dataset.cell);
        if (!zone || !marker) return;

        selectZone(zone);
        map.setView(marker.getLatLng(), Math.max(map.getZoom(), 15));
        marker.openPopup();
    });

    document.addEventListener('fullscreenchange', () => {
        setTimeout(() => map.invalidateSize(), 180);
    });

    cargar();
</script>
@stop
