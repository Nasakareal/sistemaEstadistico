@extends('adminlte::page')

@section('title', 'Mapa de Choques por Zona')

@section('content_header')
    <h1>Mapa de Choques por Zona</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-2">
                <input type="date" id="fecha_inicio" class="form-control">
            </div>
            <div class="col-md-2">
                <input type="date" id="fecha_fin" class="form-control">
            </div>
            <div class="col-md-2">
                <select id="grosor_metros" class="form-control">
                    <option value="20">20 metros</option>
                    <option value="50" selected>50 metros</option>
                    <option value="100">100 metros</option>
                    <option value="150">150 metros</option>
                    <option value="200">200 metros</option>
                    <option value="300">300 metros</option>
                </select>
            </div>
            <div class="col-md-3">
                <button id="btnConsultar" class="btn btn-primary btn-block">
                    Consultar Zona
                </button>
            </div>
            <div class="col-md-3">
                <h5 class="mb-0">Total: <span id="total">0</span></h5>
                <small id="tipo_geometria_texto" class="text-muted">Sin geometría</small>
            </div>
        </div>

        <div id="map" style="height:650px;"></div>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">
<style>
    #map {
        border-radius: 8px;
        overflow: hidden;
    }
</style>
@stop

@section('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

<script>
const map = L.map('map').setView([19.705, -101.194], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
}).addTo(map);

const drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);

const markersLayer = L.layerGroup().addTo(map);
let ultimaGeometria = null;

function metrosAPixeles(metros, zoom, lat) {
    const earthCircumference = 40075016.686;
    const latitudeRadians = lat * Math.PI / 180;
    const metersPerPixel = (earthCircumference * Math.cos(latitudeRadians)) / Math.pow(2, zoom + 8);
    return Math.max(3, metros / metersPerPixel);
}

function obtenerGrosorMetros() {
    return parseInt(document.getElementById('grosor_metros').value || '50', 10);
}

function aplicarEstiloGeometria(layer) {
    const grosorMetros = obtenerGrosorMetros();

    if (layer instanceof L.Polyline && !(layer instanceof L.Polygon)) {
        const center = map.getCenter();
        const weight = metrosAPixeles(grosorMetros, map.getZoom(), center.lat);

        layer.setStyle({
            color: '#2563eb',
            weight: weight,
            opacity: 0.35,
            lineCap: 'round',
            lineJoin: 'round'
        });
    } else {
        layer.setStyle({
            color: '#2563eb',
            weight: 3,
            opacity: 0.9,
            fillColor: '#60a5fa',
            fillOpacity: 0.20
        });
    }
}

function actualizarTextoGeometria() {
    const el = document.getElementById('tipo_geometria_texto');

    if (!ultimaGeometria) {
        el.innerText = 'Sin geometría';
        return;
    }

    if (ultimaGeometria instanceof L.Rectangle) {
        el.innerText = 'Rectángulo';
        return;
    }

    if (ultimaGeometria instanceof L.Polygon) {
        el.innerText = 'Polígono';
        return;
    }

    if (ultimaGeometria instanceof L.Polyline) {
        el.innerText = 'Línea / corredor';
        return;
    }

    el.innerText = 'Geometría cargada';
}

const drawControl = new L.Control.Draw({
    draw: {
        polygon: {
            allowIntersection: false,
            showArea: true,
            shapeOptions: {
                color: '#2563eb',
                weight: 3
            }
        },
        rectangle: {
            shapeOptions: {
                color: '#2563eb',
                weight: 3
            }
        },
        polyline: {
            shapeOptions: {
                color: '#2563eb',
                weight: 8
            }
        },
        circle: false,
        circlemarker: false,
        marker: false
    },
    edit: {
        featureGroup: drawnItems
    }
});

map.addControl(drawControl);

map.on(L.Draw.Event.CREATED, function (e) {
    drawnItems.clearLayers();
    markersLayer.clearLayers();

    const layer = e.layer;
    drawnItems.addLayer(layer);
    ultimaGeometria = layer;

    aplicarEstiloGeometria(layer);
    actualizarTextoGeometria();
    document.getElementById('total').innerText = '0';
});

map.on(L.Draw.Event.EDITED, function () {
    const layers = drawnItems.getLayers();
    ultimaGeometria = layers.length ? layers[0] : null;

    if (ultimaGeometria) {
        aplicarEstiloGeometria(ultimaGeometria);
    }

    actualizarTextoGeometria();
});

map.on(L.Draw.Event.DELETED, function () {
    ultimaGeometria = null;
    markersLayer.clearLayers();
    document.getElementById('total').innerText = '0';
    actualizarTextoGeometria();
});

document.getElementById('grosor_metros').addEventListener('change', function () {
    if (ultimaGeometria) {
        aplicarEstiloGeometria(ultimaGeometria);
    }
});

map.on('zoomend', function () {
    if (ultimaGeometria) {
        aplicarEstiloGeometria(ultimaGeometria);
    }
});

document.getElementById('btnConsultar').addEventListener('click', async function () {
    if (!ultimaGeometria) {
        alert('Dibuja una zona o línea primero');
        return;
    }

    const geojson = ultimaGeometria.toGeoJSON();
    const bounds = ultimaGeometria.getBounds();
    const grosorMetros = obtenerGrosorMetros();

    const data = {
        geometry: geojson.geometry,
        bounds: {
            north: bounds.getNorth(),
            south: bounds.getSouth(),
            east: bounds.getEast(),
            west: bounds.getWest()
        },
        buffer_metros: grosorMetros,
        fecha_inicio: document.getElementById('fecha_inicio').value,
        fecha_fin: document.getElementById('fecha_fin').value,
        _token: '{{ csrf_token() }}'
    };

    try {
        const response = await fetch("{{ route('hechos.zonas.consulta') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const raw = await response.text();

        if (!response.ok) {
            console.error(raw);
            alert('Error al consultar: revisa consola');
            return;
        }

        const res = JSON.parse(raw);

        document.getElementById('total').innerText = res.total;
        markersLayer.clearLayers();

        res.hechos.forEach(h => {
            if (h.lat && h.lng) {
                const marker = L.circleMarker([parseFloat(h.lat), parseFloat(h.lng)], {
                    radius: 6,
                    color: '#2563eb',
                    weight: 2,
                    fillColor: '#93c5fd',
                    fillOpacity: 0.85
                });

                marker.bindPopup(
                    `<b>ID:</b> ${h.id}<br>
                     <b>Folio:</b> ${h.folio_c5i ?? 'N/A'}<br>
                     <b>Fecha:</b> ${h.fecha ?? 'N/A'}<br>
                     <b>Hora:</b> ${h.hora ?? 'N/A'}<br>
                     <b>Calle:</b> ${h.calle ?? 'N/A'}<br>
                     <b>Colonia:</b> ${h.colonia ?? 'N/A'}<br>
                     <b>Municipio:</b> ${h.municipio ?? 'N/A'}<br>
                     <b>Tipo:</b> ${h.tipo_hecho ?? 'N/A'}<br>
                     <b>Situación:</b> ${h.situacion ?? 'N/A'}`
                );

                markersLayer.addLayer(marker);
            }
        });
    } catch (error) {
        console.error(error);
        alert('Ocurrió un error al consultar la zona');
    }
});
</script>
@stop
