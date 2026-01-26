@extends('adminlte::page')

@section('title', 'Mapa de Patrullas')

@section('content_header')
    <h1 class="mb-0">Mapa de Patrullas</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-header bg-primary d-flex align-items-center justify-content-between">
                <strong>Personal</strong>
                @if(auth()->user()?->hasRole('jefe_de_grupo'))
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-light" id="btn-on-all" type="button">Activar</button>
                        <button class="btn btn-light" id="btn-off-all" type="button">Desactivar</button>
                    </div>
                @endif
            </div>

            <div class="card-body p-0" style="max-height:70vh; overflow-y:auto;">
                <ul class="list-group list-group-flush" id="lista-personal">
                    <li class="list-group-item text-muted text-center">Cargando…</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card">
            <div class="card-body p-0" style="height:70vh;">
                <div id="map" style="width:100%; height:100%;"></div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
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

    const map = L.map('map').setView([19.703, -101.186], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    const patrullaIcon = L.icon({
        iconUrl: "{{ asset('car.png') }}",
        iconSize: [44, 44],
        iconAnchor: [22, 44],
        popupAnchor: [0, -44],
    });

    const markers = new Map();
    const lista = document.getElementById('lista-personal');

    let personal = [];
    let mapData = [];

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

    function upsertMarker(p){
        const key = String(p.user_id);
        const latlng = [p.lat, p.lng];

        const popup = `
            <strong>${p.name}</strong><br>
            Patrulla: ${p.patrulla_id ?? 'N/D'}<br>
            Última: ${p.captured_at ?? ''}
        `;

        if(markers.has(key)){
            markers.get(key).setLatLng(latlng).setPopupContent(popup);
        } else {
            const m = L.marker(latlng, { icon: patrullaIcon }).addTo(map).bindPopup(popup);
            markers.set(key, m);
        }
    }

    function renderLista(merged){
        lista.innerHTML = '';

        if(!merged.length){
            lista.innerHTML = `<li class="list-group-item text-center text-muted">Sin personal</li>`;
            return;
        }

        merged.forEach(p => {
            const enabled = !!p.compartir_ubicacion;
            const stale = p.stale === true;
            const dot = !enabled ? 'red' : (stale ? 'gray' : 'green');
            const li = document.createElement('li');
            li.className = 'list-group-item personal-item ' + (stale && enabled ? 'is-stale' : '');
            const timeTxt = p.last_captured_at ? String(p.last_captured_at).substr(11,5) : '--:--';

            li.innerHTML = `
                <div class="rowline">
                    <span class="dot ${dot}"></span>
                    <div class="info">
                        <strong>${p.name}</strong>
                        <small>Patrulla: ${p.patrulla_id ?? 'N/D'} · ${enabled ? (stale ? 'Sin señal reciente' : 'En línea') : 'Ubicación desactivada'}</small>
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
        personal = personal.map(p => (String(p.user_id) === String(userId)) ? ({ ...p, compartir_ubicacion: enabled ? 1 : 0 }) : p);
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
    }

    async function refreshAll(){
        await fetchPersonal();
        await fetchMapOnly();
        await refreshRenderOnly();
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
        }
    }, 10000);
</script>
@stop
