@extends('adminlte::page')

@section('title', 'Mapa de Colonias Morelia')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between flex-wrap">
        <div>
            <h1 class="mb-0">Mapa de Colonias de Morelia</h1>
            <small class="text-muted">Colonias y asentamientos oficiales por código postal, con trazos iniciales de sector.</small>
        </div>
        <span class="badge badge-primary mt-2 mt-sm-0" id="colonias-resumen">Cargando...</span>
    </div>
@stop

@section('content')
<div class="colonias-layout">
    <aside class="colonias-panel">
        <div class="panel-header">
            <div>
                <strong>Colonias</strong>
                <div class="panel-sub" id="colonias-status">Cargando capas...</div>
            </div>
            <button class="btn btn-sm btn-outline-primary" id="btn-fit-colonias" type="button" title="Ver todo">
                <i class="fas fa-expand-arrows-alt"></i>
            </button>
        </div>

        <div class="panel-search">
            <div class="input-group input-group-sm">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" class="form-control" id="buscar-colonia" placeholder="Buscar colonia">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" id="limpiar-busqueda" type="button" title="Limpiar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="panel-layers">
            <label class="layer-toggle">
                <input type="checkbox" id="toggle-etiquetas" checked>
                <span>Etiquetas de colonias</span>
                <span class="badge badge-primary" id="etiquetas-count">...</span>
            </label>
            <label class="layer-toggle">
                <input type="checkbox" id="toggle-poligonos" checked>
                <span>Polígonos por código postal</span>
                <span class="badge badge-light" id="poligonos-count">...</span>
            </label>
            <label class="layer-toggle">
                <input type="checkbox" id="toggle-sectores" checked>
                <span>Líneas de sector</span>
                <span class="badge badge-info">2</span>
            </label>
        </div>

        <div class="colonias-lista" id="colonias-lista">
            <div class="p-3 text-muted">Cargando...</div>
        </div>

        <div class="panel-footer">
            Fuente colonias: 16-Mich.geojson + Catálogo Nacional de Códigos Postales SEPOMEX.
        </div>
    </aside>

    <section class="colonias-map-card">
        <div id="mapaColonias"></div>
    </section>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
    .colonias-layout {
        display: grid;
        grid-template-columns: 330px minmax(0, 1fr);
        gap: 14px;
        min-height: 76vh;
    }

    .colonias-panel,
    .colonias-map-card {
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
    }

    .colonias-panel {
        display: flex;
        flex-direction: column;
        min-height: 76vh;
    }

    .panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px;
        border-bottom: 1px solid #eef1f5;
    }

    .panel-sub {
        color: #6b7280;
        font-size: 12px;
        line-height: 1.2;
        margin-top: 2px;
    }

    .panel-search,
    .panel-layers {
        padding: 10px 12px;
        border-bottom: 1px solid #eef1f5;
    }

    .panel-layers {
        display: grid;
        gap: 7px;
    }

    .layer-toggle {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        color: #111827;
        font-size: 12.5px;
        cursor: pointer;
    }

    .layer-toggle input {
        margin: 0;
    }

    .layer-toggle span:nth-child(2) {
        flex: 1 1 auto;
        min-width: 0;
    }

    .colonias-lista {
        overflow-y: auto;
        flex: 1 1 auto;
    }

    .colonia-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 9px 12px;
        border-bottom: 1px solid #f1f3f5;
        cursor: pointer;
    }

    .colonia-item:hover,
    .colonia-item.active {
        background: #f8fafc;
    }

    .colonia-name {
        min-width: 0;
        font-weight: 700;
        color: #111827;
        line-height: 1.1;
        word-break: break-word;
    }

    .colonia-type {
        display: block;
        color: #6b7280;
        font-size: 11.5px;
        font-weight: 400;
        margin-top: 2px;
    }

    .colonia-chip {
        flex: 0 0 auto;
        font-size: 11px;
    }

    .panel-footer {
        border-top: 1px solid #eef1f5;
        color: #6b7280;
        font-size: 11.5px;
        padding: 8px 12px;
        background: #f8fafc;
    }

    #mapaColonias {
        width: 100%;
        height: 76vh;
        background: #f7fafc;
    }

    .leaflet-container {
        font-family: inherit;
    }

    .colonia-label-marker {
        background: transparent;
        border: 0;
    }

    .colonia-label-marker span {
        display: inline-block;
        max-width: 145px;
        padding: 3px 6px;
        border: 1px solid rgba(37, 99, 235, .22);
        border-radius: 4px;
        background: rgba(255, 255, 255, .92);
        color: #1d4ed8;
        box-shadow: 0 1px 4px rgba(15, 23, 42, .16);
        font-size: 10.5px;
        font-weight: 850;
        line-height: 1.05;
        text-align: center;
        white-space: normal;
        transform: translate(-50%, -50%);
    }

    .colonia-label-marker.is-selected span {
        background: #111827;
        border-color: #111827;
        color: #fff;
        font-size: 12px;
        box-shadow: 0 3px 10px rgba(15, 23, 42, .28);
    }

    .polygon-label {
        border: 0;
        border-radius: 5px;
        background: rgba(15, 23, 42, .76);
        color: #fff;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .2);
        font-size: 10.5px;
        font-weight: 800;
        line-height: 1.1;
        padding: 3px 5px;
        white-space: normal;
        max-width: 150px;
        text-align: center;
    }

    .polygon-label::before {
        display: none;
    }

    .sector-label {
        border: 0;
        border-radius: 999px;
        color: #fff;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .25);
        font-size: 11px;
        font-weight: 900;
        padding: 4px 8px;
    }

    .sector-label::before {
        display: none;
    }

    .sector-label--morelos {
        background: rgba(2, 132, 199, .96);
    }

    .sector-label--madero {
        background: rgba(220, 38, 38, .96);
    }

    .sector-side-label {
        border: 1px solid rgba(15, 23, 42, .15);
        border-radius: 999px;
        background: rgba(255, 255, 255, .94);
        color: #111827;
        box-shadow: 0 1px 5px rgba(15, 23, 42, .18);
        font-size: 12px;
        font-weight: 900;
        padding: 5px 9px;
        white-space: nowrap;
    }

    .colonia-popup strong {
        color: #111827;
    }

    @media (max-width: 992px) {
        .colonias-layout {
            grid-template-columns: 1fr;
        }

        .colonias-panel {
            min-height: auto;
            max-height: 42vh;
        }

        #mapaColonias {
            height: 58vh;
        }
    }
</style>
@stop

@section('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    const coloniasUrl = @json(asset('geo/morelia_colonias.geojson'));
    const sectorLinesUrl = @json(asset('geo/morelia_sector_lines.geojson'));

    const mapa = L.map('mapaColonias', {
        zoomControl: true,
        preferCanvas: true,
    }).setView([19.703, -101.194], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 20,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(mapa);

    const lista = document.getElementById('colonias-lista');
    const status = document.getElementById('colonias-status');
    const resumen = document.getElementById('colonias-resumen');
    const buscador = document.getElementById('buscar-colonia');
    const limpiar = document.getElementById('limpiar-busqueda');
    const btnFit = document.getElementById('btn-fit-colonias');
    const toggleEtiquetas = document.getElementById('toggle-etiquetas');
    const togglePoligonos = document.getElementById('toggle-poligonos');
    const toggleSectores = document.getElementById('toggle-sectores');
    const etiquetasCount = document.getElementById('etiquetas-count');
    const poligonosCount = document.getElementById('poligonos-count');

    const etiquetasGroup = L.featureGroup().addTo(mapa);
    const poligonosGroup = L.featureGroup().addTo(mapa);
    const sectoresGroup = L.featureGroup().addTo(mapa);

    const colonyItems = [];
    const labelMarkers = [];
    const polygonItems = [];
    let selectedItem = null;
    let counts = {
        etiquetas: 0,
        poligonos: 0,
        sectores: 0,
    };

    const palette = [
        '#2563eb', '#0891b2', '#059669', '#7c3aed',
        '#dc2626', '#ca8a04', '#0f766e', '#be185d',
    ];

    function colorForName(name) {
        const text = String(name || '');
        let hash = 0;

        for (let i = 0; i < text.length; i++) {
            hash = ((hash << 5) - hash) + text.charCodeAt(i);
            hash |= 0;
        }

        return palette[Math.abs(hash) % palette.length];
    }

    function nombreFeature(feature) {
        return feature?.properties?.nombre || feature?.properties?.name || 'Sin nombre';
    }

    function tipoFeature(feature) {
        const tipo = feature?.properties?.tipo || 'colonia';
        const map = {
            etiqueta_colonia: 'colonia',
            codigo_postal: 'polígono CP',
            residential: 'zona residencial',
            neighbourhood: 'colonia',
            quarter: 'fraccionamiento',
            suburb: 'zona urbana',
        };

        return map[tipo] || tipo;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function baseStyle(feature) {
        const color = colorForName(nombreFeature(feature));

        return {
            color,
            weight: 1.2,
            opacity: .95,
            fillColor: color,
            fillOpacity: .22,
        };
    }

    function popupHtml(feature) {
        const nombre = escapeHtml(nombreFeature(feature));
        const tipo = escapeHtml(tipoFeature(feature));
        const props = feature?.properties || {};
        const fuente = escapeHtml(props.source || 'SEPOMEX / GeoJSON CP');
        const cp = props.codigo_postal ? `<br><span>CP: ${escapeHtml(props.codigo_postal)}</span>` : '';
        const asentamientos = Array.isArray(props.asentamientos) ? props.asentamientos : [];
        const listaAsentamientos = asentamientos.length
            ? `<div class="mt-2"><strong>Colonias en este CP</strong><br>${asentamientos.slice(0, 18).map(item => `&bull; ${escapeHtml(item)}`).join('<br>')}${asentamientos.length > 18 ? `<br><small>y ${asentamientos.length - 18} más...</small>` : ''}</div>`
            : '';

        return `
            <div class="colonia-popup">
                <strong>${nombre}</strong><br>
                <span>Tipo: ${tipo}</span>${cp}<br>
                ${listaAsentamientos}
                <small class="text-muted">${fuente}</small>
            </div>
        `;
    }

    function labelIcon(nombre, selected = false) {
        return L.divIcon({
            className: `colonia-label-marker${selected ? ' is-selected' : ''}`,
            html: `<span>${escapeHtml(nombre)}</span>`,
            iconSize: [1, 1],
            iconAnchor: [0, 0],
        });
    }

    function sectorLabelClass(id) {
        return id === 'sector-francisco-i-madero'
            ? 'sector-label sector-label--madero'
            : 'sector-label sector-label--morelos';
    }

    function updateCounters() {
        etiquetasCount.textContent = counts.etiquetas;
        poligonosCount.textContent = counts.poligonos;
        resumen.textContent = `${counts.etiquetas} colonias/asentamientos · ${counts.poligonos} polígonos CP · ${counts.sectores} líneas`;
    }

    function resetSelected() {
        if (!selectedItem) {
            return;
        }

        if (selectedItem.kind === 'label') {
            selectedItem.layer.setIcon(labelIcon(selectedItem.nombre));
        }

        if (selectedItem.kind === 'polygon') {
            selectedItem.layer.setStyle(baseStyle(selectedItem.feature));
        }

        selectedItem = null;
    }

    function ensureVisible(item) {
        if (item.kind === 'label' && !mapa.hasLayer(etiquetasGroup)) {
            etiquetasGroup.addTo(mapa);
            toggleEtiquetas.checked = true;
        }

        if (item.kind === 'polygon' && !mapa.hasLayer(poligonosGroup)) {
            poligonosGroup.addTo(mapa);
            togglePoligonos.checked = true;
        }
    }

    function fitLayer(layer) {
        if (layer.getBounds && layer.getBounds().isValid()) {
            mapa.fitBounds(layer.getBounds(), {
                padding: [35, 35],
                maxZoom: 16,
            });
            return;
        }

        if (layer.getLatLng) {
            mapa.setView(layer.getLatLng(), 16);
        }
    }

    function selectItem(item) {
        resetSelected();
        ensureVisible(item);
        selectedItem = item;

        if (item.kind === 'label') {
            item.layer.setIcon(labelIcon(item.nombre, true));
        }

        if (item.kind === 'polygon') {
            item.layer.setStyle({
                color: '#111827',
                weight: 3,
                fillOpacity: .42,
            });
        }

        item.layer.bringToFront?.();
        item.layer.openPopup?.();
        fitLayer(item.layer);

        document.querySelectorAll('.colonia-item').forEach(el => {
            el.classList.toggle('active', el.dataset.id === item.id);
        });

        syncLabelVisibility();
    }

    function renderLista() {
        const query = String(buscador.value || '').trim().toLowerCase();
        const filtered = colonyItems.filter(item => {
            return item.searchText.includes(query);
        });

        if (!filtered.length) {
            lista.innerHTML = '<div class="p-3 text-muted">Sin resultados.</div>';
            status.textContent = `Mostrando 0 de ${colonyItems.length}`;
            return;
        }

        lista.innerHTML = filtered.map(item => `
            <div class="colonia-item" data-id="${escapeHtml(item.id)}">
                <div class="colonia-name">
                    ${escapeHtml(item.nombre)}
                    <span class="colonia-type">${escapeHtml(item.detalle)}</span>
                </div>
                <span class="badge ${item.kind === 'label' ? 'badge-primary' : 'badge-light'} colonia-chip">
                    ${item.kind === 'label' ? 'Colonia' : 'CP'}
                </span>
            </div>
        `).join('');

        status.textContent = `Mostrando ${filtered.length} de ${colonyItems.length}`;

        lista.querySelectorAll('.colonia-item').forEach(row => {
            row.addEventListener('click', () => {
                const item = colonyItems.find(capa => capa.id === row.dataset.id);
                if (item) {
                    selectItem(item);
                }
            });
        });
    }

    function labelLimitForZoom(zoom) {
        if (zoom < 13) {
            return -1;
        }

        if (zoom < 14) {
            return 0;
        }

        if (zoom < 15) {
            return 0;
        }

        if (zoom < 16) {
            return 1;
        }

        if (zoom < 17) {
            return 4;
        }

        return Infinity;
    }

    function labelCpBucketAllowed(item, zoom) {
        if (zoom < 14) {
            return item.cpBucket === 0;
        }

        if (zoom < 15) {
            return item.cpBucket <= 1;
        }

        return true;
    }

    function labelSpacingForZoom(zoom) {
        if (zoom < 14) {
            return 108;
        }

        if (zoom < 15) {
            return 88;
        }

        if (zoom < 16) {
            return 68;
        }

        if (zoom < 17) {
            return 48;
        }

        return 28;
    }

    function labelCollides(point, placedPoints, minDistance) {
        return placedPoints.some(placed => {
            const dx = point.x - placed.x;
            const dy = point.y - placed.y;

            return Math.sqrt((dx * dx) + (dy * dy)) < minDistance;
        });
    }

    function labelShouldShow(item, zoom, bounds, placedPoints) {
        if (selectedItem?.id === item.id) {
            return true;
        }

        if (!bounds.contains(item.layer.getLatLng())) {
            return false;
        }

        if (item.rankInCp > labelLimitForZoom(zoom) || !labelCpBucketAllowed(item, zoom)) {
            return false;
        }

        const point = mapa.latLngToContainerPoint(item.layer.getLatLng());

        if (labelCollides(point, placedPoints, labelSpacingForZoom(zoom))) {
            return false;
        }

        placedPoints.push(point);

        return true;
    }

    function syncLabelVisibility() {
        const zoom = mapa.getZoom();
        const etiquetasVisibles = mapa.hasLayer(etiquetasGroup);
        const bounds = mapa.getBounds().pad(.12);
        const placedPoints = [];

        labelMarkers.forEach(item => {
            const show = etiquetasVisibles && labelShouldShow(item, zoom, bounds, placedPoints);

            if (show && !etiquetasGroup.hasLayer(item.layer)) {
                item.layer.addTo(etiquetasGroup);
            }

            if (!show && etiquetasGroup.hasLayer(item.layer) && selectedItem?.id !== item.id) {
                etiquetasGroup.removeLayer(item.layer);
            }
        });
    }

    function fitVisibleLayers() {
        const groups = [etiquetasGroup, poligonosGroup, sectoresGroup].filter(group => mapa.hasLayer(group));

        if (!groups.length) {
            return;
        }

        const visible = L.featureGroup(groups);

        if (visible.getBounds().isValid()) {
            mapa.fitBounds(visible.getBounds(), {
                padding: [24, 24],
                maxZoom: 13,
            });
        }
    }

    function handleLayerToggle(checkbox, group) {
        checkbox.addEventListener('change', () => {
            if (checkbox.checked) {
                group.addTo(mapa);
            } else {
                group.removeFrom(mapa);
            }

            syncLabelVisibility();
        });
    }

    function loadColonias() {
        return fetch(coloniasUrl, { headers: { Accept: 'application/geo+json, application/json' } })
            .then(response => {
                if (!response.ok) {
                    throw new Error('No se pudo cargar el GeoJSON de colonias.');
                }

                return response.json();
            })
            .then(geojson => {
                L.geoJSON(geojson, {
                    filter: feature => feature.geometry?.type !== 'Point',
                    style: baseStyle,
                    onEachFeature: (feature, layer) => {
                        const nombre = nombreFeature(feature);
                        const tipo = tipoFeature(feature);
                        const id = feature.properties?.id || `polygon-${polygonItems.length}`;
                        const cp = feature.properties?.codigo_postal || '';
                        const asentamientos = Array.isArray(feature.properties?.asentamientos)
                            ? feature.properties.asentamientos
                            : [];

                        layer.bindPopup(popupHtml(feature));
                        layer.bindTooltip(nombre, {
                            direction: 'center',
                            className: 'polygon-label',
                            opacity: .9,
                        });

                        const item = {
                            id,
                            nombre,
                            tipo,
                            detalle: cp ? `${tipo} ${cp} · ${asentamientos.length} colonias` : tipo,
                            searchText: [nombre, tipo, cp, ...asentamientos].join(' ').toLowerCase(),
                            layer,
                            feature,
                            kind: 'polygon',
                        };

                        polygonItems.push(item);
                        colonyItems.push(item);

                        layer.on('click', () => selectItem(item));
                        layer.on('mouseover', () => {
                            if (selectedItem?.id !== item.id) {
                                layer.setStyle({ weight: 2.4, fillOpacity: .36 });
                                layer.bringToFront?.();
                            }
                        });
                        layer.on('mouseout', () => {
                            if (selectedItem?.id !== item.id) {
                                layer.setStyle(baseStyle(feature));
                            }
                        });
                    },
                }).addTo(poligonosGroup);

                (geojson.features || [])
                    .filter(feature => feature.geometry?.type === 'Point')
                    .forEach((feature, index) => {
                        const nombre = nombreFeature(feature);
                        const tipo = tipoFeature(feature);
                        const id = feature.properties?.id || `label-${index}`;
                        const cp = feature.properties?.codigo_postal || '';
                        const rankMatch = String(id).match(/-(\d+)$/);
                        const rankInCp = Number(feature.properties?.label_rank ?? rankMatch?.[1] ?? index);
                        const cpBucket = Number.isFinite(Number(cp)) ? Number(cp) % 4 : index % 4;
                        const latlng = [feature.geometry.coordinates[1], feature.geometry.coordinates[0]];
                        const marker = L.marker(latlng, {
                            icon: labelIcon(nombre),
                            riseOnHover: true,
                        });

                        marker.bindPopup(popupHtml(feature));

                        const item = {
                            id,
                            nombre,
                            tipo,
                            detalle: cp ? `${tipo} · CP ${cp}` : tipo,
                            searchText: [nombre, tipo, cp, feature.properties?.municipio || ''].join(' ').toLowerCase(),
                            layer: marker,
                            feature,
                            kind: 'label',
                            hasGeometry: !!feature.properties?.has_geometry,
                            rankInCp,
                            cpBucket,
                        };

                        labelMarkers.push(item);
                        colonyItems.push(item);
                        marker.on('click', () => selectItem(item));
                    });

                counts.etiquetas = labelMarkers.length;
                counts.poligonos = polygonItems.length;
                updateCounters();
            });
    }

    function loadSectorLines() {
        return fetch(sectorLinesUrl, { headers: { Accept: 'application/geo+json, application/json' } })
            .then(response => {
                if (!response.ok) {
                    throw new Error('No se pudo cargar el GeoJSON de sectores.');
                }

                return response.json();
            })
            .then(geojson => {
                L.geoJSON(geojson, {
                    style: feature => ({
                        color: feature.properties?.color || '#0284c7',
                        weight: feature.properties?.id === 'sector-francisco-i-madero' ? 5 : 6,
                        opacity: .96,
                        lineCap: 'round',
                        lineJoin: 'round',
                    }),
                    onEachFeature: (feature, layer) => {
                        const nombre = feature.properties?.nombre || 'Línea de sector';
                        const id = feature.properties?.id || '';
                        const etiquetaCorta = id === 'sector-francisco-i-madero' ? 'Sector 02' : 'Sector 01';

                        layer.bindPopup(`
                            <strong>${escapeHtml(nombre)}</strong><br>
                            <small class="text-muted">${escapeHtml(feature.properties?.source || '')}</small>
                        `);

                        layer.bindTooltip(etiquetaCorta, {
                            permanent: true,
                            direction: 'center',
                            className: sectorLabelClass(id),
                            opacity: .95,
                        });
                    },
                }).addTo(sectoresGroup);

                counts.sectores = (geojson.features || []).length;
                updateCounters();
            });
    }

    function addSectorSideLabels() {
        [
            [[19.7042, -101.2200], 'Sector Poniente'],
            [[19.7042, -101.1645], 'Sector Oriente'],
            [[19.7160, -101.1928], 'Sector Norte'],
            [[19.6850, -101.1910], 'Sector Sur'],
        ].forEach(([latlng, text]) => {
            L.marker(latlng, {
                icon: L.divIcon({
                    className: '',
                    html: `<div class="sector-side-label">${escapeHtml(text)}</div>`,
                }),
                interactive: false,
            }).addTo(sectoresGroup);
        });
    }

    addSectorSideLabels();
    updateCounters();

    Promise.all([loadColonias(), loadSectorLines()])
        .then(() => {
            colonyItems.sort((a, b) => {
                if (a.nombre === b.nombre) {
                    return a.kind.localeCompare(b.kind);
                }

                return a.nombre.localeCompare(b.nombre, 'es');
            });

            renderLista();
            fitVisibleLayers();
            syncLabelVisibility();
        })
        .catch(error => {
            console.error(error);
            lista.innerHTML = '<div class="p-3 text-danger">No se pudieron cargar las capas del mapa.</div>';
            status.textContent = 'Error al cargar datos';
            resumen.textContent = 'Error';
        });

    mapa.on('zoomend moveend', syncLabelVisibility);

    handleLayerToggle(toggleEtiquetas, etiquetasGroup);
    handleLayerToggle(togglePoligonos, poligonosGroup);
    handleLayerToggle(toggleSectores, sectoresGroup);

    buscador.addEventListener('input', renderLista);

    limpiar.addEventListener('click', () => {
        buscador.value = '';
        renderLista();
        buscador.focus();
    });

    btnFit.addEventListener('click', () => {
        resetSelected();
        document.querySelectorAll('.colonia-item').forEach(el => el.classList.remove('active'));
        fitVisibleLayers();
        syncLabelVisibility();
    });
</script>
@stop
