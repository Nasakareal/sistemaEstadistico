@extends('adminlte::page')

@section('title', 'Mapa de Patrullas')

@section('content_header')
    <h1 class="mb-0">Mapa de Patrullas</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="card h-100 card-soft">
            <div class="card-header bg-primary d-flex align-items-center justify-content-between">
                <strong>Personal</strong>
                @if(auth()->user()?->hasRole('jefe_de_grupo'))
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-light" id="btn-on-all" type="button">Activar</button>
                        <button class="btn btn-light" id="btn-off-all" type="button">Desactivar</button>
                    </div>
                @endif
            </div>

            <div class="card-body p-2">
                <div class="mb-2">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" class="form-control" id="search-personal" placeholder="Buscar por nombre (ej. saenz)">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" id="btn-clear-search" type="button" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-1" id="search-counter">Cargando…</small>
                </div>

                <div class="list-wrap" style="max-height:64vh; overflow-y:auto;">
                    <ul class="list-group list-group-flush" id="lista-personal">
                        <li class="list-group-item text-muted text-center">Cargando…</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card card-soft">
            <div class="card-body p-0">
                <div class="top-status" id="top-status">
                    <div class="status-icon" id="status-icon">
                        <i class="fas fa-map"></i>
                    </div>
                    <div class="status-text">
                        <div class="status-title">Ubicación de patrullas</div>
                        <div class="status-sub" id="status-sub">Cargando…</div>
                    </div>
                    <div class="status-actions">
                        <button class="btn btn-sm btn-outline-primary" id="btn-refresh" type="button" title="Actualizar">
                            <i class="fas fa-sync"></i>
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
        background: rgba(0,123,255,.12);
        display:flex;align-items:center;justify-content:center;
        color:#007bff;
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
    .status-actions{flex:0 0 auto;}

    .personal-item{ cursor:pointer; }
    .personal-item:hover{ background:#f7f7f7; }

    .personal-item .rowline{ display:flex; align-items:center; gap:.6rem; }
    .personal-item .info{ flex:1 1 auto; min-width:0; }
    .personal-item .info strong{ display:block; color:#111; line-height:1.15; white-space:normal; word-break:break-word; }
    .personal-item .info small{ display:block; line-height:1.1; color:#6c757d; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .badge-time{ display:inline-flex; align-items:center; justify-content:center; padding:.25rem .45rem; line-height:1; font-size:.75rem; border-radius:.5rem; white-space:nowrap; flex:0 0 auto; }

    .dot{ width:10px; height:10px; border-radius:50%; flex:0 0 auto; }
    .dot.green{ background:#28a745; }
    .dot.gray{ background:#6c757d; }
    .dot.red{ background:#dc3545; }

    .is-stale strong{ color:#9aa0a6 !important; }
    .is-stale small{ color:#b0b6bd !important; }
    .is-stale{ opacity:.75; }

    .switch-wrap{ display:flex; align-items:center; gap:.35rem; flex:0 0 auto; }
    .mini-switch{ transform:scale(.9); transform-origin:right center; margin:0; }

    .leaflet-container{
        background:#f6f7fb;
    }

    .patrulla-marker{
        width:62px;
        height:62px;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:flex-start;
        gap:3px;
        pointer-events:auto;
    }
    .patrulla-label{
        padding:2px 6px;
        border-radius:8px;
        background: rgba(0,0,0,.75);
        color:#fff;
        font-size:11px;
        font-weight:900;
        line-height:1;
        max-width:62px;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .patrulla-bubble{
        width:44px;height:44px;
        border-radius:999px;
        display:flex;
        align-items:center;
        justify-content:center;
        border:2px solid rgba(0,123,255,.55);
        background: rgba(0,123,255,.12);
    }
    .patrulla-bubble.stale{
        opacity:.55;
        border-color: rgba(108,117,125,.55);
        background: rgba(108,117,125,.12);
    }
    .patrulla-bubble img{
        width:28px;height:28px;
        object-fit:contain;
        display:block;
    }
</style>
@stop

@section('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    const STALE_MINUTES = 3;
    const canToggle = @json(auth()->user()?->hasRole('jefe_de_grupo') ? true : false);

    const urlMapData = @json(route('mapa.patrullas.data'));
    const urlPersonal = @json(route('mapa.mi_personal'));
    const urlToggleUser = (id) => @json(route('mapa.mi_personal.toggle', ['user' => '__ID__'])).replace('__ID__', id);
    const urlToggleAll = @json(route('mapa.mi_personal.toggle_all'));
    const csrf = @json(csrf_token());

    const map = L.map('map', { zoomControl: true }).setView([19.703, -101.186], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    const markers = new Map();
    const lista = document.getElementById('lista-personal');

    const searchInput = document.getElementById('search-personal');
    const btnClearSearch = document.getElementById('btn-clear-search');
    const searchCounter = document.getElementById('search-counter');

    const statusSub = document.getElementById('status-sub');
    const btnRefresh = document.getElementById('btn-refresh');

    let personal = [];
    let mapData = [];
    let lastFetchAt = null;
    let lastError = null;

    function nowTimeStr(){
        const d = new Date();
        const hh = String(d.getHours()).padStart(2,'0');
        const mm = String(d.getMinutes()).padStart(2,'0');
        const ss = String(d.getSeconds()).padStart(2,'0');
        return `${hh}:${mm}:${ss}`;
    }

    function setStatus(){
        if(lastError){
            statusSub.textContent = `Error: ${lastError}`;
            return;
        }
        const total = Array.isArray(personal) ? personal.length : 0;
        if(!lastFetchAt){
            statusSub.textContent = `Cargando…`;
            return;
        }
        statusSub.textContent = `Actualizado: ${lastFetchAt} · Personal: ${total}`;
    }

    function parseDt(capturedAt){
        if(!capturedAt) return null;
        const iso = String(capturedAt).replace(' ', 'T');
        const dt = new Date(iso);
        return isNaN(dt.getTime()) ? null : dt;
    }

    function isStale(capturedAt){
        const dt = parseDt(capturedAt);
        if(!dt) return true;
        const diffMs = Date.now() - dt.getTime();
        const diffMin = diffMs / 60000;
        return diffMin >= STALE_MINUTES;
    }

    // ✅ YA NO USAR ID COMO LABEL: prioriza numero_economico
    function patrullaLabelFromLoc(loc){
        const ne = (loc && loc.numero_economico != null && String(loc.numero_economico).trim() !== '')
            ? String(loc.numero_economico)
            : null;
        return ne; // si no hay, regresamos null y el UI mostrará N/D
    }

    function mergePersonalWithMap(personalList, mapList){
        const byUser = new Map();
        mapList.forEach(x => byUser.set(Number(x.user_id), x));

        return personalList.map(u => {
            const loc = byUser.get(Number(u.user_id)) || null;
            return {
                ...u,
                last_captured_at: loc?.captured_at ?? null,
                last_lat: (loc?.lat ?? null),
                last_lng: (loc?.lng ?? null),
                stale: loc ? isStale(loc.captured_at) : true,
                numero_economico: loc?.numero_economico ?? (u.numero_economico ?? null),
            };
        });
    }

    function clearMarkersNotIn(visibleUserIds){
        for(const [key, marker] of markers.entries()){
            if(!visibleUserIds.has(Number(key))){
                map.removeLayer(marker);
                markers.delete(key);
            }
        }
    }

    function buildMarkerHtml({ label, stale }){
        const safeLabel = (label && String(label).trim() !== '') ? String(label) : 'N/D';
        const bubbleClass = stale ? 'patrulla-bubble stale' : 'patrulla-bubble';
        const carUrl = @json(asset('car.png'));

        return `
            <div class="patrulla-marker">
                <div class="patrulla-label">${safeLabel}</div>
                <div class="${bubbleClass}">
                    <img src="${carUrl}" alt="car">
                </div>
            </div>
        `;
    }

    function upsertMarker(loc){
        const key = String(loc.user_id);
        const latlng = [loc.lat, loc.lng];

        const label = patrullaLabelFromLoc(loc);
        const stale = isStale(loc.captured_at);

        const popup = `
            <strong>${loc.name ?? ''}</strong><br>
            Núm. Económico: ${(label && label.trim() !== '') ? label : 'N/D'}<br>
            Última: ${loc.captured_at ?? ''}
        `;

        const divIcon = L.divIcon({
            className: '',
            html: buildMarkerHtml({ label, stale }),
            iconSize: [62, 62],
            iconAnchor: [31, 62],
            popupAnchor: [0, -62],
        });

        if(markers.has(key)){
            markers.get(key).setLatLng(latlng).setIcon(divIcon).setPopupContent(popup);
        } else {
            const m = L.marker(latlng, { icon: divIcon }).addTo(map).bindPopup(popup);
            markers.set(key, m);
        }
    }

    function timeFromCapturedAt(capturedAt){
        if(!capturedAt) return '--:--';
        const dt = parseDt(capturedAt);
        if(!dt) return String(capturedAt).substr(11,5);
        const hh = String(dt.getHours()).padStart(2,'0');
        const mm = String(dt.getMinutes()).padStart(2,'0');
        return `${hh}:${mm}`;
    }

    function renderLista(merged){
        lista.innerHTML = '';

        const q = (searchInput?.value || '').trim().toLowerCase();
        const filtered = !q ? merged : merged.filter(p => String(p.name || '').toLowerCase().includes(q));

        if(searchCounter){
            searchCounter.textContent = !q
                ? `Mostrando: ${filtered.length} / ${merged.length}`
                : `Mostrando: ${filtered.length} / ${merged.length} (filtro: "${q}")`;
        }

        if(!filtered.length){
            lista.innerHTML = `<li class="list-group-item text-center text-muted">Sin resultados</li>`;
            return;
        }

        filtered.forEach(p => {
            const enabled = !!p.compartir_ubicacion;
            const stale = p.stale === true;
            const dot = !enabled ? 'red' : (stale ? 'gray' : 'green');

            const li = document.createElement('li');
            li.className = 'list-group-item personal-item ' + (stale && enabled ? 'is-stale' : '');

            const timeTxt = timeFromCapturedAt(p.last_captured_at);

            const neTxt = (p.numero_economico != null && String(p.numero_economico).trim() !== '')
                ? String(p.numero_economico)
                : 'N/D';

            li.innerHTML = `
                <div class="rowline">
                    <span class="dot ${dot}"></span>
                    <div class="info">
                        <strong>${p.name}</strong>
                        <small>Núm. Económico: ${neTxt} · ${enabled ? (stale ? 'Sin señal reciente' : 'En línea') : 'Ubicación desactivada'}</small>
                    </div>
                    <span class="badge badge-secondary badge-time">${timeTxt}</span>
                    ${canToggle ? `
                        <span class="switch-wrap" title="Compartir ubicación">
                            <input type="checkbox" class="mini-switch" ${enabled ? 'checked' : ''} data-user="${p.user_id}">
                        </span>
                    ` : ``}
                </div>
            `;

            li.addEventListener('click', (ev) => {
                if(ev.target && ev.target.matches('input.mini-switch')) return;
                if(p.last_lat != null && p.last_lng != null){
                    map.setView([p.last_lat, p.last_lng], 17);
                    const marker = markers.get(String(p.user_id));
                    if(marker) marker.openPopup();
                }
            });

            lista.appendChild(li);
        });

        if(canToggle){
            lista.querySelectorAll('input.mini-switch').forEach(sw => {
                sw.addEventListener('click', (ev) => ev.stopPropagation());
                sw.addEventListener('change', async (ev) => {
                    const input = ev.target;
                    const userId = input.getAttribute('data-user');
                    const enabled = input.checked;
                    input.disabled = true;
                    try{
                        await toggleUser(userId, enabled);
                        await fetchMapOnly();
                        await refreshRenderOnly();
                    }catch(e){
                        input.checked = !enabled;
                        console.error(e);
                        alert('No se pudo guardar.');
                    }finally{
                        input.disabled = false;
                    }
                });
            });
        }
    }

    async function toggleUser(userId, enabled){
        const res = await fetch(urlToggleUser(userId), {
            method: 'POST',
            headers: { 'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ enabled })
        });
        if(!res.ok) throw new Error('toggle user failed');

        personal = personal.map(p => (String(p.user_id) === String(userId)))
            ? ({ ...p, compartir_ubicacion: enabled ? 1 : 0 })
            : p;
    }

    async function toggleAll(enabled){
        const res = await fetch(urlToggleAll, {
            method: 'POST',
            headers: { 'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ enabled })
        });
        if(!res.ok) throw new Error('toggle all failed');
        personal = personal.map(p => ({ ...p, compartir_ubicacion: enabled ? 1 : 0 }));
    }

    async function fetchPersonal(){
        const res = await fetch(urlPersonal, { headers: { 'Accept':'application/json' } });
        if(!res.ok) throw new Error('personal failed');

        const data = await res.json();
        const list = (data && Array.isArray(data.data)) ? data.data : (Array.isArray(data) ? data : []);

        personal = list.map(x => ({
            user_id: x.id ?? x.user_id,
            name: x.name ?? '',
            email: x.email ?? '',
            patrulla_id: x.patrulla_id ?? null,
            numero_economico: x.numero_economico ?? null, // ✅
            compartir_ubicacion: x.compartir_ubicacion ?? 0,
        }));
    }

    async function fetchMapOnly(){
        const res = await fetch(urlMapData, { headers: { 'Accept':'application/json' } });
        if(!res.ok) throw new Error('map failed');

        mapData = await res.json();
        mapData = Array.isArray(mapData) ? mapData : [];
    }

    async function refreshRenderOnly(){
        const merged = mergePersonalWithMap(personal, mapData);

        const visible = merged.filter(p => {
            const enabled = !!p.compartir_ubicacion;
            if(!enabled) return false;
            if(p.last_lat == null || p.last_lng == null) return false;
            return p.stale === false;
        });

        const visibleUserIds = new Set(visible.map(x => Number(x.user_id)));
        clearMarkersNotIn(visibleUserIds);

        visible.forEach(p => {
            const loc = mapData.find(x => Number(x.user_id) === Number(p.user_id));
            if(loc) upsertMarker(loc);
        });

        renderLista(merged);

        lastFetchAt = nowTimeStr();
        lastError = null;
        setStatus();
    }

    async function refreshAll(){
        try{
            btnRefresh && (btnRefresh.disabled = true);
            await fetchPersonal();
            await fetchMapOnly();
            await refreshRenderOnly();
        }catch(e){
            console.error(e);
            lastError = (e && e.message) ? e.message : String(e);
            setStatus();
        }finally{
            btnRefresh && (btnRefresh.disabled = false);
        }
    }

    if(btnRefresh){
        btnRefresh.addEventListener('click', async () => {
            await refreshAll();
        });
    }

    if(searchInput){
        searchInput.addEventListener('input', () => refreshRenderOnly());
    }
    if(btnClearSearch){
        btnClearSearch.addEventListener('click', () => {
            if(searchInput) searchInput.value = '';
            refreshRenderOnly();
            searchInput && searchInput.focus();
        });
    }

    @if(auth()->user()?->hasRole('jefe_de_grupo'))
    document.getElementById('btn-on-all')?.addEventListener('click', async () => {
        try{ await toggleAll(true); await refreshRenderOnly(); }
        catch(e){ console.error(e); alert('No se pudo activar.'); }
    });

    document.getElementById('btn-off-all')?.addEventListener('click', async () => {
        try{ await toggleAll(false); await refreshRenderOnly(); }
        catch(e){ console.error(e); alert('No se pudo desactivar.'); }
    });
    @endif

    refreshAll();
    setInterval(async () => {
        try{
            await fetchMapOnly();
            await refreshRenderOnly();
        }catch(e){
            console.error(e);
            lastError = (e && e.message) ? e.message : String(e);
            setStatus();
        }
    }, 10000);
</script>
@stop
