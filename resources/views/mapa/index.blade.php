@extends('adminlte::page')

@section('title', 'Mapa de Patrullas')

@section('content_header')
    <h1>Mapa de Patrullas</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body p-0" style="height: 70vh;">
            <div id="map" style="width: 100%; height: 100%;"></div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@stop

@section('js')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const map = L.map('map').setView([19.703, -101.186], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(map);

        const patrullaIcon = L.icon({
            iconUrl: "{{ asset('car.png') }}",
            iconSize: [44, 44],
            iconAnchor: [22, 44],
            popupAnchor: [0, -44],
        });

        const markers = new Map();

        function upsertMarker(p) {
            const key = String(p.user_id);
            const latlng = [p.lat, p.lng];
            const popup = `<b>${p.name}</b><br>ID: ${p.user_id}<br>${p.captured_at ?? ''}`;

            if (markers.has(key)) {
                markers.get(key).setLatLng(latlng).setPopupContent(popup);
            } else {
                const m = L.marker(latlng, { icon: patrullaIcon }).addTo(map).bindPopup(popup);
                markers.set(key, m);
            }
        }

        async function cargarPatrullas() {
            try {
                const res = await fetch("{{ route('mapa.patrullas.data') }}", {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!res.ok) return;

                const data = await res.json();
                data.forEach(upsertMarker);

            } catch (e) {}
        }

        cargarPatrullas();
        setInterval(cargarPatrullas, 5000);
    </script>
@stop
