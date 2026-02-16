@extends('adminlte::page')

@section('title', 'Mapa de Incidencias')

@section('content_header')
    <h1 class="mb-0">Mapa de Incidencias</h1>
@stop

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-soft">
            <div class="card-body p-0">

                <div class="top-status">
                    <div class="status-icon">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="status-text">
                        <div class="status-title">Zonas con más incidencias</div>
                        <div class="status-sub" id="status-sub">Listo para cargar…</div>
                    </div>
                </div>

                <div class="p-3 border-bottom" style="background:#fff;">
                    <div class="d-flex flex-wrap gap-2 align-items-end">
                        <div>
                            <label class="form-label mb-0">Desde</label>
                            <input type="date" id="desde" class="form-control"
                                   value="{{ \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}">
                        </div>

                        <div>
                            <label class="form-label mb-0">Hasta</label>
                            <input type="date" id="hasta" class="form-control"
                                   value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                        </div>

                        <div>
                            <label class="form-label mb-0">Precisión</label>
                            <select id="precision" class="form-select">
                                <option value="2">2 (≈1.1km)</option>
                                <option value="3" selected>3 (≈110m)</option>
                                <option value="4">4 (≈11m)</option>
                            </select>
                        </div>

                        <button class="btn btn-primary" id="btnCargar" type="button">
                            <i class="fas fa-cloud-download-alt mr-1"></i> Cargar
                        </button>
                    </div>
                </div>

                <div class="map-wrap">
                    <div id="map" style="width:100%; height:70vh;"></div>
                </div>

            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
    .card-soft{
        border-radius: 18px !important;
        border: 1px solid #e9ecef !important;
        box-shadow: 0 10px 18px rgba(0,0,0,.06) !important;
        overflow: hidden;
    }
    .map-wrap{
        border-top: 1px solid #eef1f5;
    }
    .top-status{
        display:flex;
        align-items:center;
        gap:10px;
        padding:10px 12px;
        background:#fff;
        border-bottom:1px solid #eef1f5;
    }
    .status-icon{
        width:38px;height:38px;
        border-radius:12px;
        background: rgba(220,53,69,.12);
        display:flex;align-items:center;justify-content:center;
        color:#dc3545;
        flex:0 0 auto;
    }
    .status-text{flex:1 1 auto; min-width:0;}
    .status-title{
        font-weight:900;
        color:#0F172A;
        line-height:1.1;
    }
    .status-sub{
        font-size:12.5px;
        color:#6c757d;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .leaflet-container{ background:#f6f7fb; }
</style>
@stop

@section('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    const urlData = @json(route('mapa.incidencias.data'));
    const statusSub = document.getElementById('status-sub');

    const map = L.map('map', { zoomControl: true }).setView([19.703, -101.186], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    const layer = L.layerGroup().addTo(map);

    setTimeout(() => map.invalidateSize(), 200);

    function colorPorTotal(total){
        if (total >= 20) return '#7f0000';
        if (total >= 10) return '#b30000';
        if (total >=  5) return '#e34a33';
        if (total >=  2) return '#fc8d59';
        return '#fdcc8a';
    }

    function radioPorTotal(total){
        let r = 6 + (Math.sqrt(total) * 6);
        if (r > 40) r = 40;
        return r;
    }

    async function cargar(){
        layer.clearLayers();
        statusSub.textContent = 'Cargando puntos…';

        const desde = document.getElementById('desde').value;
        const hasta = document.getElementById('hasta').value;
        const precision = document.getElementById('precision').value;

        const u = new URL(urlData, window.location.origin);
        u.searchParams.set('desde', desde);
        u.searchParams.set('hasta', hasta);
        u.searchParams.set('precision', precision);

        const res = await fetch(u.toString(), { headers: { 'Accept':'application/json' } });
        if(!res.ok){
            statusSub.textContent = 'Error cargando datos (revisa consola / Network).';
            return;
        }

        const json = await res.json();
        const puntos = (json && Array.isArray(json.data)) ? json.data : [];

        puntos.forEach(p => {
            const total = Number(p.total || 0);
            const c = colorPorTotal(total);

            L.circleMarker([p.lat, p.lng], {
                radius: radioPorTotal(total),
                color: c,
                fillColor: c,
                fillOpacity: 0.55,
                weight: 2
            }).bindPopup(
                `<strong>Incidencias:</strong> ${total}<br>` +
                `<strong>Rango:</strong> ${p.fecha_min || '-'} a ${p.fecha_max || '-'}`
            ).addTo(layer);
        });

        statusSub.textContent = `Puntos: ${puntos.length} · Desde ${desde} hasta ${hasta} · Precisión ${precision}`;
    }

    document.getElementById('btnCargar').addEventListener('click', cargar);

    cargar();
</script>
@stop
