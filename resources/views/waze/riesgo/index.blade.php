@extends('adminlte::page')

@section('title', 'Riesgo (Waze vs Hechos)')

@section('content_header')
    <h1 class="mb-0">Riesgo (Waze vs Hechos)</h1>
@stop

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-soft">
            <div class="card-body p-0">

                <div class="top-status">
                    <div class="status-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="status-text">
                        <div class="status-title">Zonas de riesgo (auto)</div>
                        <div class="status-sub" id="status-sub">Listo para cargar…</div>
                    </div>
                </div>

                <div class="p-3 border-bottom" style="background:#fff;">
                    <div class="d-flex flex-wrap gap-2 align-items-end">

                        <div>
                            <label class="form-label mb-0">Desde (Hechos)</label>
                            <input type="date" id="desde" class="form-control"
                                   value="{{ \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}">
                        </div>

                        <div>
                            <label class="form-label mb-0">Hasta (Hechos)</label>
                            <input type="date" id="hasta" class="form-control"
                                   value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                        </div>

                        <div>
                            <label class="form-label mb-0">Ventana (min)</label>
                            <select id="ventana" class="form-select">
                                <option value="30">±30</option>
                                <option value="60" selected>±60</option>
                                <option value="90">±90</option>
                                <option value="120">±120</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label mb-0">Precisión (ROUND)</label>
                            <select id="precision" class="form-select">
                                <option value="2">2 (≈1.1km)</option>
                                <option value="3" selected>3 (≈110m)</option>
                                <option value="4">4 (≈11m)</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label mb-0">Waze últimos</label>
                            <select id="wazeHoras" class="form-select">
                                <option value="1">1 hora</option>
                                <option value="2">2 horas</option>
                                <option value="4">4 horas</option>
                                <option value="8">8 horas</option>
                                <option value="12">12 horas</option>
                                <option value="24" selected>24 horas</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2 align-items-center" style="padding:0 6px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="verHechos" checked>
                                <label class="form-check-label" for="verHechos">Hechos</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="verWaze" checked>
                                <label class="form-check-label" for="verWaze">Waze</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="verMatches" checked>
                                <label class="form-check-label" for="verMatches">Matches</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="verRiesgo" checked>
                                <label class="form-check-label" for="verRiesgo">Riesgo</label>
                            </div>
                        </div>

                        <button class="btn btn-primary" id="btnCargar" type="button">
                            <i class="fas fa-cloud-download-alt mr-1"></i> Cargar
                        </button>

                        <button class="btn btn-outline-secondary" id="btnReset" type="button">
                            <i class="fas fa-sync mr-1"></i> Reset vista
                        </button>
                    </div>

                    <div class="pt-3">
                        <div class="kpi-row" id="kpis">
                            <div class="kpi">
                                <div class="kpi-title">Hechos con coord</div>
                                <div class="kpi-val" id="k_hechos">-</div>
                            </div>
                            <div class="kpi">
                                <div class="kpi-title">Waze (jams)</div>
                                <div class="kpi-val" id="k_jams">-</div>
                            </div>
                            <div class="kpi">
                                <div class="kpi-title">Waze (accidents)</div>
                                <div class="kpi-val" id="k_accidents">-</div>
                            </div>
                            <div class="kpi">
                                <div class="kpi-title">Matches</div>
                                <div class="kpi-val" id="k_matches">-</div>
                            </div>
                            <div class="kpi">
                                <div class="kpi-title">Top riesgo</div>
                                <div class="kpi-val" id="k_top">-</div>
                            </div>
                        </div>
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
    .map-wrap{ border-top: 1px solid #eef1f5; }
    .top-status{
        display:flex; align-items:center; gap:10px;
        padding:10px 12px; background:#fff;
        border-bottom:1px solid #eef1f5;
    }
    .status-icon{
        width:38px;height:38px; border-radius:12px;
        background: rgba(255,193,7,.14);
        display:flex;align-items:center;justify-content:center;
        color:#b78103; flex:0 0 auto;
    }
    .status-text{flex:1 1 auto; min-width:0;}
    .status-title{ font-weight:900; color:#0F172A; line-height:1.1; }
    .status-sub{
        font-size:12.5px; color:#6c757d;
        white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .leaflet-container{ background:#f6f7fb; }

    .kpi-row{
        display:grid;
        grid-template-columns: repeat(5, minmax(120px, 1fr));
        gap:10px;
    }
    .kpi{
        border:1px solid #eef1f5;
        border-radius:14px;
        padding:10px 12px;
        background:#fff;
    }
    .kpi-title{ font-size:12px; color:#6c757d; font-weight:700; }
    .kpi-val{ font-size:18px; font-weight:900; color:#0F172A; line-height:1.1; }
    @media(max-width: 1100px){
        .kpi-row{ grid-template-columns: repeat(2, minmax(140px, 1fr)); }
    }
</style>
@stop

@section('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    const urlData = @json(route('waze.riesgo.data'));
    const statusSub = document.getElementById('status-sub');

    function esc(s){
        if (s === null || s === undefined) return '';
        return String(s)
            .replaceAll('&','&amp;')
            .replaceAll('<','&lt;')
            .replaceAll('>','&gt;')
            .replaceAll('"','&quot;')
            .replaceAll("'","&#039;");
    }

    function num(v){
        const n = Number(v);
        return Number.isFinite(n) ? n : null;
    }

    const map = L.map('map', { zoomControl: true }).setView([19.703, -101.186], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    const layerHechos  = L.layerGroup().addTo(map);
    const layerWaze    = L.layerGroup().addTo(map);
    const layerMatches = L.layerGroup().addTo(map);
    const layerRiesgo  = L.layerGroup().addTo(map);

    setTimeout(() => map.invalidateSize(), 200);

    function colorRiesgo(score){
        score = Number(score || 0);
        if (score >= 40) return '#7f0000';
        if (score >= 20) return '#b30000';
        if (score >= 10) return '#e34a33';
        if (score >=  5) return '#fc8d59';
        if (score >   0) return '#fdcc8a';
        return '#d0d7de';
    }

    function radioRiesgo(score){
        score = Number(score || 0);
        let r = 6 + (Math.sqrt(score) * 3);
        if (r > 45) r = 45;
        return r;
    }

    function toggleLayers(){
        const verHechos  = document.getElementById('verHechos').checked;
        const verWaze    = document.getElementById('verWaze').checked;
        const verMatches = document.getElementById('verMatches').checked;
        const verRiesgo  = document.getElementById('verRiesgo').checked;

        if (verHechos) map.addLayer(layerHechos); else map.removeLayer(layerHechos);
        if (verWaze) map.addLayer(layerWaze); else map.removeLayer(layerWaze);
        if (verMatches) map.addLayer(layerMatches); else map.removeLayer(layerMatches);
        if (verRiesgo) map.addLayer(layerRiesgo); else map.removeLayer(layerRiesgo);
    }

    document.getElementById('verHechos').addEventListener('change', toggleLayers);
    document.getElementById('verWaze').addEventListener('change', toggleLayers);
    document.getElementById('verMatches').addEventListener('change', toggleLayers);
    document.getElementById('verRiesgo').addEventListener('change', toggleLayers);

    function setKpis(k){
        document.getElementById('k_hechos').textContent    = (k && k.hechos    !== undefined) ? k.hechos    : '-';
        document.getElementById('k_jams').textContent      = (k && k.jams      !== undefined) ? k.jams      : '-';
        document.getElementById('k_accidents').textContent = (k && k.accidents !== undefined) ? k.accidents : '-';
        document.getElementById('k_matches').textContent   = (k && k.matches   !== undefined) ? k.matches   : '-';
        document.getElementById('k_top').textContent       = (k && k.top       !== undefined) ? k.top       : '-';
    }

    function clearAll(){
        layerHechos.clearLayers();
        layerWaze.clearLayers();
        layerMatches.clearLayers();
        layerRiesgo.clearLayers();
    }

    function addHechosPoints(items){
        (items || []).forEach(p => {
            const lat = num(p.lat);
            const lng = num(p.lng);
            if(lat === null || lng === null) return;

            L.circleMarker([lat, lng], {
                radius: 5,
                color: '#2563eb',
                fillColor: '#2563eb',
                fillOpacity: 0.35,
                weight: 2
            }).bindPopup(
                `<strong>Hechos:</strong> ${esc(p.total || 0)}<br>` +
                `<strong>Celda:</strong> ${esc(p.cell || '-')}`
            ).addTo(layerHechos);
        });
    }

    function addWazePoints(items){
        (items || []).forEach(p => {
            const lat = num(p.lat);
            const lng = num(p.lng);
            if(lat === null || lng === null) return;

            const t = String(p.type || '').toUpperCase();
            const color = (t === 'ACCIDENT') ? '#dc3545' : (t === 'JAM' ? '#f59e0b' : '#6b7280');

            L.circleMarker([lat, lng], {
                radius: 4,
                color: color,
                fillColor: color,
                fillOpacity: 0.55,
                weight: 2
            }).bindPopup(
                `<strong>Waze:</strong> ${esc(p.type || '-')}<br>` +
                `<strong>Calle:</strong> ${esc(p.street_norm || p.street || '-')}<br>` +
                `<strong>Publicado:</strong> ${esc(p.published_at || '-')}`
            ).addTo(layerWaze);
        });
    }

    function addMatches(items){
        (items || []).forEach(m => {
            const lat = num(m.lat);
            const lng = num(m.lng);
            if(lat === null || lng === null) return;

            L.circleMarker([lat, lng], {
                radius: 6,
                color: '#7c3aed',
                fillColor: '#7c3aed',
                fillOpacity: 0.25,
                weight: 3
            }).bindPopup(
                `<strong>MATCH</strong><br>` +
                `<strong>Hecho:</strong> ${esc(m.hecho_id || '-')}<br>` +
                `<strong>Celda:</strong> ${esc(m.cell || '-')}<br>` +
                `<strong>Accidente Waze:</strong> ${esc(m.waze_accident_at || '-')}<br>` +
                `<strong>Primer Jam:</strong> ${esc(m.waze_first_jam_at || '-')}<br>` +
                `<strong>Acc→Hecho (min):</strong> ${esc(m.min_accident_to_hecho ?? '-')}<br>` +
                `<strong>Hecho→Jam (min):</strong> ${esc(m.min_hecho_to_jam ?? '-')}`
            ).addTo(layerMatches);
        });
    }

    function addRiesgoCells(items){
        (items || []).forEach(z => {
            const lat = num(z.lat);
            const lng = num(z.lng);
            if(lat === null || lng === null) return;

            const score = Number(z.score || 0);
            const c = colorRiesgo(score);

            L.circleMarker([lat, lng], {
                radius: radioRiesgo(score),
                color: c,
                fillColor: c,
                fillOpacity: 0.50,
                weight: 2
            }).bindPopup(
                `<strong>Riesgo:</strong> ${esc(score)}<br>` +
                `<strong>Celda:</strong> ${esc(z.cell || '-')}<br>` +
                `<strong>Hechos hist:</strong> ${esc(z.hechos_hist || 0)}<br>` +
                `<strong>Jams (ventana):</strong> ${esc(z.jams_now || 0)}<br>` +
                `<strong>Accidents (ventana):</strong> ${esc(z.accidents_now || 0)}`
            ).addTo(layerRiesgo);
        });
    }

    async function cargar(){
        clearAll();
        statusSub.textContent = 'Cargando…';

        const desde = document.getElementById('desde').value;
        const hasta = document.getElementById('hasta').value;
        const ventana = document.getElementById('ventana').value;
        const precision = document.getElementById('precision').value;
        const wazeHoras = document.getElementById('wazeHoras').value;

        const u = new URL(urlData, window.location.origin);
        u.searchParams.set('desde', desde);
        u.searchParams.set('hasta', hasta);
        u.searchParams.set('ventana', ventana);
        u.searchParams.set('precision', precision);
        u.searchParams.set('waze_horas', wazeHoras);

        let res;
        try{
            res = await fetch(u.toString(), {
                headers: { 'Accept':'application/json' }
            });
        }catch(e){
            console.error('Fetch error:', e);
            statusSub.textContent = 'No se pudo conectar (revisa red / CORS / SSL).';
            setKpis(null);
            return;
        }

        if(!res.ok){
            const txt = await res.text().catch(()=> '');
            console.error('HTTP', res.status, txt);
            statusSub.textContent = `Error HTTP ${res.status} (ver consola)`;
            setKpis(null);
            return;
        }

        let json;
        try{
            json = await res.json();
        }catch(e){
            console.error('JSON parse error:', e);
            statusSub.textContent = 'Respuesta inválida (no es JSON).';
            setKpis(null);
            return;
        }

        setKpis(json.kpis || null);

        addHechosPoints(json.hechos_cells || []);
        addWazePoints(json.waze_points || []);
        addMatches(json.matches || []);
        addRiesgoCells(json.riesgo_cells || []);

        toggleLayers();

        const totalRiesgo = (json.riesgo_cells && json.riesgo_cells.length) ? json.riesgo_cells.length : 0;
        statusSub.textContent = `Riesgo: ${totalRiesgo} celdas · Hechos ${desde}→${hasta} · Waze últimas ${wazeHoras}h · Precisión ${precision} · Ventana ±${ventana}min`;
    }

    document.getElementById('btnCargar').addEventListener('click', cargar);
    document.getElementById('btnReset').addEventListener('click', () => {
        map.setView([19.703, -101.186], 12);
    });

    cargar();
</script>
@stop
