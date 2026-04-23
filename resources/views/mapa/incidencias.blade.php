@extends('adminlte::page')

@section('title', 'Mapa de Incidencias')

@section('content_header')
    <div class="heat-header">
        <div>
            <p class="heat-kicker">Análisis geoespacial</p>
            <h1 class="mb-0">Mapa de Incidencias</h1>
        </div>
        <div class="heat-header-note">
            Agrupa por precisión y abre cada hecho desde el mapa.
        </div>
    </div>
@stop

@section('content')
<div class="heat-shell">
    <div class="heat-panel">
        <div class="heat-panel-head">
            <div class="heat-panel-title-wrap">
                <span class="heat-icon">
                    <i class="fas fa-fire-alt"></i>
                </span>
                <div>
                    <div class="heat-panel-eyebrow">Mapa de calor operativo</div>
                    <div class="heat-panel-title">Zonas con más incidencias</div>
                    <div class="heat-panel-subtitle" id="status-sub">Listo para cargar datos.</div>
                </div>
            </div>

            <div class="heat-metrics">
                <div class="metric-chip">
                    <span class="metric-label">Puntos</span>
                    <span class="metric-value" id="metric-puntos">0</span>
                </div>
                <div class="metric-chip">
                    <span class="metric-label">Hechos</span>
                    <span class="metric-value" id="metric-hechos">0</span>
                </div>
                <div class="metric-chip">
                    <span class="metric-label">Más cargado</span>
                    <span class="metric-value" id="metric-maximo">0</span>
                </div>
            </div>
        </div>

        <div class="heat-toolbar">
            <div class="heat-field">
                <label for="desde">Desde</label>
                <input
                    type="date"
                    id="desde"
                    class="form-control"
                    value="{{ \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}"
                >
            </div>

            <div class="heat-field">
                <label for="hasta">Hasta</label>
                <input
                    type="date"
                    id="hasta"
                    class="form-control"
                    value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                >
            </div>

            <div class="heat-field heat-field-sm">
                <label for="precision">Precisión</label>
                <select id="precision" class="form-control">
                    <option value="2">2 (≈1.1 km)</option>
                    <option value="3" selected>3 (≈110 m)</option>
                    <option value="4">4 (≈11 m)</option>
                    <option value="5">5 (≈1 m)</option>
                </select>
            </div>

            <div class="heat-actions">
                <button class="btn btn-heat-primary" id="btnCargar" type="button">
                    <i class="fas fa-sync-alt mr-1"></i> Actualizar mapa
                </button>
            </div>
        </div>

        <div class="heat-legend">
            <span class="legend-title">Intensidad</span>
            <span class="legend-pill legend-low">1-2</span>
            <span class="legend-pill legend-mid">3-5</span>
            <span class="legend-pill legend-high">6-9</span>
            <span class="legend-pill legend-peak">10+</span>
            <span class="legend-note">Haz clic en cualquier punto para ver los hechos y abrir su expediente.</span>
        </div>

        <div class="map-wrap">
            <div id="map" class="heat-map"></div>
        </div>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
    .heat-header {
        display: flex;
        justify-content: space-between;
        align-items: end;
        gap: 16px;
        color: #e7eef8;
    }

    .heat-kicker {
        margin: 0 0 6px;
        text-transform: uppercase;
        letter-spacing: .12em;
        font-size: .74rem;
        font-weight: 800;
        color: #8ac5ff;
    }

    .heat-header-note {
        max-width: 360px;
        padding: 10px 14px;
        border-radius: 14px;
        background: rgba(255, 255, 255, .08);
        color: #dce9f9;
        font-size: .92rem;
        line-height: 1.35;
    }

    .heat-shell {
        padding-bottom: 12px;
    }

    .heat-panel {
        border-radius: 24px;
        border: 1px solid rgba(148, 163, 184, .2);
        background:
            radial-gradient(circle at top left, rgba(56, 189, 248, .14), transparent 32%),
            radial-gradient(circle at top right, rgba(251, 146, 60, .16), transparent 28%),
            linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 28px 60px rgba(15, 23, 42, .14);
        overflow: hidden;
    }

    .heat-panel-head {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        padding: 22px 22px 16px;
        border-bottom: 1px solid rgba(226, 232, 240, .9);
    }

    .heat-panel-title-wrap {
        display: flex;
        gap: 14px;
        align-items: flex-start;
    }

    .heat-icon {
        width: 48px;
        height: 48px;
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(248, 113, 113, .18), rgba(251, 191, 36, .24));
        color: #dc2626;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .7);
    }

    .heat-panel-eyebrow {
        margin-bottom: 4px;
        color: #64748b;
        font-size: .8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .heat-panel-title {
        color: #0f172a;
        font-size: 1.55rem;
        font-weight: 900;
        line-height: 1.05;
    }

    .heat-panel-subtitle {
        margin-top: 6px;
        color: #475569;
        font-size: .96rem;
        line-height: 1.4;
    }

    .heat-metrics {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .metric-chip {
        min-width: 112px;
        padding: 12px 14px;
        border-radius: 16px;
        background: rgba(255, 255, 255, .84);
        border: 1px solid rgba(148, 163, 184, .2);
        box-shadow: 0 10px 20px rgba(15, 23, 42, .05);
    }

    .metric-label {
        display: block;
        margin-bottom: 4px;
        font-size: .76rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #64748b;
    }

    .metric-value {
        display: block;
        color: #0f172a;
        font-size: 1.3rem;
        font-weight: 900;
        line-height: 1;
    }

    .heat-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        align-items: end;
        padding: 18px 22px 14px;
    }

    .heat-field {
        min-width: 180px;
    }

    .heat-field-sm {
        min-width: 160px;
    }

    .heat-field label {
        display: block;
        margin: 0 0 7px;
        color: #334155;
        font-size: .84rem;
        font-weight: 800;
        letter-spacing: .02em;
    }

    .heat-field .form-control {
        height: 48px;
        border-radius: 14px;
        border: 1px solid #cbd5e1;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, .03);
        font-weight: 700;
        color: #0f172a;
    }

    .heat-field .form-control:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 0 .2rem rgba(56, 189, 248, .18);
    }

    .heat-actions {
        display: flex;
        align-items: end;
        padding-bottom: 1px;
    }

    .btn-heat-primary {
        height: 48px;
        padding: 0 18px;
        border: 0;
        border-radius: 14px;
        background: linear-gradient(135deg, #0f766e 0%, #2563eb 100%);
        color: #fff;
        font-weight: 800;
        box-shadow: 0 14px 30px rgba(37, 99, 235, .24);
    }

    .btn-heat-primary:hover,
    .btn-heat-primary:focus {
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 16px 32px rgba(37, 99, 235, .28);
    }

    .heat-legend {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        padding: 0 22px 18px;
        color: #475569;
    }

    .legend-title {
        font-size: .82rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #334155;
    }

    .legend-pill {
        padding: 6px 10px;
        border-radius: 999px;
        color: #fff;
        font-size: .82rem;
        font-weight: 800;
    }

    .legend-low { background: #f59e0b; }
    .legend-mid { background: #f97316; }
    .legend-high { background: #ef4444; }
    .legend-peak { background: #991b1b; }

    .legend-note {
        color: #64748b;
        font-size: .88rem;
    }

    .map-wrap {
        border-top: 1px solid rgba(226, 232, 240, .85);
        background:
            linear-gradient(180deg, rgba(240, 249, 255, .7), rgba(255, 255, 255, 0));
        padding: 10px;
    }

    .heat-map {
        width: 100%;
        height: 72vh;
        min-height: 520px;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .55);
    }

    .leaflet-container {
        background: #dbeafe;
    }

    .leaflet-popup-content-wrapper {
        border-radius: 16px;
        box-shadow: 0 18px 36px rgba(15, 23, 42, .16);
    }

    .leaflet-popup-content {
        margin: 14px 16px;
        min-width: 250px;
    }

    .incident-popup-title {
        margin-bottom: 8px;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 900;
    }

    .incident-popup-meta {
        margin-bottom: 10px;
        color: #475569;
        font-size: .85rem;
        line-height: 1.45;
    }

    .incident-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-height: 260px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .incident-card {
        display: block;
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid #dbe4f0;
        background: #f8fbff;
        color: #0f172a;
        text-decoration: none !important;
        transition: background .15s ease, border-color .15s ease, transform .15s ease;
    }

    .incident-card:hover {
        background: #eef6ff;
        border-color: #93c5fd;
        transform: translateY(-1px);
        color: #0f172a;
    }

    .incident-card-title {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 4px;
        font-size: .88rem;
        font-weight: 900;
    }

    .incident-card-sub {
        color: #475569;
        font-size: .8rem;
        line-height: 1.4;
    }

    .incident-popup-actions {
        margin-top: 10px;
        color: #64748b;
        font-size: .78rem;
    }

    .incident-loading,
    .incident-empty {
        padding: 12px;
        border-radius: 12px;
        background: #f8fafc;
        color: #64748b;
        font-size: .84rem;
        text-align: center;
    }

    @media (max-width: 991.98px) {
        .heat-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .heat-panel-head {
            flex-direction: column;
        }

        .heat-metrics {
            justify-content: flex-start;
        }

        .heat-map {
            height: 66vh;
            min-height: 420px;
        }
    }
</style>
@stop

@section('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    const urlData = @json(route('mapa.incidencias.data'));
    const urlHechos = @json(route('mapa.incidencias.hechos'));
    const statusSub = document.getElementById('status-sub');
    const metricPuntos = document.getElementById('metric-puntos');
    const metricHechos = document.getElementById('metric-hechos');
    const metricMaximo = document.getElementById('metric-maximo');

    const map = L.map('map', { zoomControl: true }).setView([19.703, -101.186], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const layer = L.layerGroup().addTo(map);

    setTimeout(() => map.invalidateSize(), 240);

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function colorPorTotal(total) {
        if (total >= 10) return '#991b1b';
        if (total >= 6) return '#dc2626';
        if (total >= 3) return '#f97316';
        return '#f59e0b';
    }

    function radioPorTotal(total) {
        let radius = 10 + (Math.sqrt(total) * 5);
        if (radius > 34) radius = 34;
        return radius;
    }

    function buildPopupSkeleton(total, punto) {
        return `
            <div class="incident-popup">
                <div class="incident-popup-title">${total > 1 ? 'Grupo de incidencias' : 'Incidencia localizada'}</div>
                <div class="incident-popup-meta">
                    ${total} hecho(s) agrupado(s)<br>
                    Coordenadas redondeadas: ${punto.lat.toFixed(4)}, ${punto.lng.toFixed(4)}
                </div>
                <div class="incident-loading">Cargando hechos…</div>
            </div>
        `;
    }

    function buildPopupContent(punto, hechos) {
        if (!hechos.length) {
            return `
                <div class="incident-popup">
                    <div class="incident-popup-title">Sin hechos</div>
                    <div class="incident-empty">No encontré hechos para este punto con los filtros actuales.</div>
                </div>
            `;
        }

        const cards = hechos.map((hecho) => {
            const ubicacion = [hecho.calle, hecho.colonia, hecho.municipio].filter(Boolean).join(', ');

            return `
                <a class="incident-card" href="${escapeHtml(hecho.show_url)}" target="_blank" rel="noopener noreferrer">
                    <div class="incident-card-title">
                        <span>Hecho #${escapeHtml(hecho.id)}</span>
                        <span>${escapeHtml(hecho.fecha || 'SIN FECHA')}</span>
                    </div>
                    <div class="incident-card-sub">
                        ${(hecho.tipo_hecho ? escapeHtml(hecho.tipo_hecho) : 'SIN TIPO')}
                        ${hecho.situacion ? ' · ' + escapeHtml(hecho.situacion) : ''}<br>
                        ${escapeHtml(hecho.hora || 'SIN HORA')}
                        ${ubicacion ? ' · ' + escapeHtml(ubicacion) : ''}
                        ${hecho.folio_c5i ? '<br>Folio C5I: ' + escapeHtml(hecho.folio_c5i) : ''}
                    </div>
                </a>
            `;
        }).join('');

        return `
            <div class="incident-popup">
                <div class="incident-popup-title">${hechos.length > 1 ? 'Hechos en esta zona' : 'Hecho en este punto'}</div>
                <div class="incident-popup-meta">
                    ${hechos.length} resultado(s) para ${escapeHtml(punto.fecha_min || '-')} a ${escapeHtml(punto.fecha_max || '-')}
                </div>
                <div class="incident-list">${cards}</div>
                <div class="incident-popup-actions">Puedes abrir cualquier tarjeta para ir directo al detalle del hecho.</div>
            </div>
        `;
    }

    async function cargarHechosDePunto(marker, punto) {
        marker.setPopupContent(buildPopupSkeleton(Number(punto.total || 0), punto));

        const desde = document.getElementById('desde').value;
        const hasta = document.getElementById('hasta').value;
        const precision = document.getElementById('precision').value;
        const url = new URL(urlHechos, window.location.origin);

        url.searchParams.set('lat', punto.lat);
        url.searchParams.set('lng', punto.lng);
        url.searchParams.set('desde', desde);
        url.searchParams.set('hasta', hasta);
        url.searchParams.set('precision', precision);

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
        } catch (error) {
            marker.setPopupContent(`
                <div class="incident-popup">
                    <div class="incident-popup-title">Error</div>
                    <div class="incident-empty">No pude cargar los hechos de este punto.</div>
                </div>
            `);
        }
    }

    async function cargar() {
        layer.clearLayers();
        statusSub.textContent = 'Cargando puntos del mapa…';
        metricPuntos.textContent = '0';
        metricHechos.textContent = '0';
        metricMaximo.textContent = '0';

        const desde = document.getElementById('desde').value;
        const hasta = document.getElementById('hasta').value;
        const precision = document.getElementById('precision').value;

        const url = new URL(urlData, window.location.origin);
        url.searchParams.set('desde', desde);
        url.searchParams.set('hasta', hasta);
        url.searchParams.set('precision', precision);

        try {
            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error('Error cargando datos.');
            }

            const json = await response.json();
            const puntos = Array.isArray(json.data) ? json.data : [];
            let hechosTotales = 0;
            let maximo = 0;
            const bounds = [];

            puntos.forEach((punto) => {
                const total = Number(punto.total || 0);
                hechosTotales += total;
                if (total > maximo) maximo = total;

                const marker = L.circleMarker([punto.lat, punto.lng], {
                    radius: radioPorTotal(total),
                    color: '#ffffff',
                    weight: 2,
                    fillColor: colorPorTotal(total),
                    fillOpacity: 0.82
                });

                marker.bindPopup(buildPopupSkeleton(total, punto));
                marker.on('click', () => cargarHechosDePunto(marker, punto));
                marker.addTo(layer);
                bounds.push([punto.lat, punto.lng]);
            });

            metricPuntos.textContent = puntos.length;
            metricHechos.textContent = hechosTotales;
            metricMaximo.textContent = maximo;

            statusSub.textContent = puntos.length
                ? `Puntos: ${puntos.length} · Hechos: ${hechosTotales} · Desde ${desde} hasta ${hasta} · Precisión ${precision}`
                : 'No hay incidencias con esos filtros.';

            if (bounds.length === 1) {
                map.setView(bounds[0], 15);
            } else if (bounds.length > 1) {
                map.fitBounds(bounds, { padding: [32, 32] });
            }
        } catch (error) {
            console.error(error);
            statusSub.textContent = 'Ocurrió un error al cargar el mapa.';
        }
    }

    document.getElementById('btnCargar').addEventListener('click', cargar);
    cargar();
</script>
@stop
