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

        // ICONO PATRULLA (public/car.png)
        const patrullaIcon = L.icon({
            iconUrl: "{{ asset('car.png') }}",
            iconSize: [44, 44],
            iconAnchor: [22, 44],
            popupAnchor: [0, -44],
        });

        // MARCADOR DE PRUEBA (para verlo)
        L.marker([19.703, -101.186], { icon: patrullaIcon })
            .addTo(map)
            .bindPopup("Patrulla 001")
            .openPopup();
    </script>
@stop
