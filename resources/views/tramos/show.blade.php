@extends('adminlte::page')

@section('title', 'Detalle de Tramo')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <h1 class="mb-0">Detalle de Tramo</h1>
        <div class="btn-group">
            <a href="{{ route('tramos.edit', $tramo->id) }}" class="btn btn-success">
                <i class="fa-regular fa-pen-to-square"></i> Editar
            </a>
            <a href="{{ route('tramos.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
@stop

@section('content')
@php
    $hasCoords = $tramo->lat_inicio && $tramo->lng_inicio && $tramo->lat_fin && $tramo->lng_fin;
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="card card-soft h-100">
            <div class="card-header bg-primary d-flex align-items-center justify-content-between">
                <strong>Información</strong>
                <span class="badge {{ $tramo->activo ? 'badge-success' : 'badge-danger' }}">
                    {{ $tramo->activo ? 'ACTIVO' : 'INACTIVO' }}
                </span>
            </div>

            <div class="card-body">
                <div class="mb-2">
                    <div class="text-muted" style="font-size: 12px;">Carretera</div>
                    <div style="font-weight:900; font-size: 16px;">{{ $tramo->carretera }}</div>
                </div>

                <div class="mb-2">
                    <div class="text-muted" style="font-size: 12px;">Nombre</div>
                    <div style="font-weight:900; font-size: 16px;">{{ $tramo->nombre }}</div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-6">
                        <div class="text-muted" style="font-size: 12px;">KM Inicio</div>
                        <div style="font-weight:800;">{{ $tramo->km_inicio ?? '-' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size: 12px;">KM Fin</div>
                        <div style="font-weight:800;">{{ $tramo->km_fin ?? '-' }}</div>
                    </div>
                </div>

                <hr>

                <div class="mb-2">
                    <div class="text-muted" style="font-size: 12px;">Coordenadas</div>

                    @if($hasCoords)
                        <span class="badge badge-success">CONFIGURADAS</span>
                    @else
                        <span class="badge badge-secondary">SIN COORDS</span>
                    @endif
                </div>

                <div class="row">
                    <div class="col-12 mb-2">
                        <div class="text-muted" style="font-size: 12px;">Inicio</div>
                        <div style="font-weight:800;">
                            {{ $tramo->lat_inicio ?? '-' }}, {{ $tramo->lng_inicio ?? '-' }}
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="text-muted" style="font-size: 12px;">Fin</div>
                        <div style="font-weight:800;">
                            {{ $tramo->lat_fin ?? '-' }}, {{ $tramo->lng_fin ?? '-' }}
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex gap-2">
                    <a href="{{ route('tramos.edit', $tramo->id) }}" class="btn btn-success btn-sm">
                        <i class="fa-regular fa-pen-to-square"></i> Editar
                    </a>

                    <form action="{{ route('tramos.destroy', $tramo->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-danger btn-sm delete-btn">
                            <i class="fa-regular fa-trash-can"></i> Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card card-soft">
            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <div>
                    <div style="font-weight:900; color:#0F172A;">Ruta del Tramo</div>
                    <div class="text-muted" style="font-size:12.5px;">
                        {{ $hasCoords ? 'Inicio a fin según coordenadas configuradas.' : 'Configura coordenadas para poder pintar la ruta.' }}
                    </div>
                </div>

                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" id="btn-fit" type="button" {{ $hasCoords ? '' : 'disabled' }}>
                        <i class="fa-solid fa-expand"></i> Ajustar
                    </button>
                    <button class="btn btn-outline-secondary" id="btn-copy" type="button" {{ $hasCoords ? '' : 'disabled' }}>
                        <i class="fa-regular fa-copy"></i> Copiar coords
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="top-status" id="top-status">
                    <div class="status-icon">
                        <i class="fas fa-route"></i>
                    </div>
                    <div class="status-text">
                        <div class="status-title">{{ $tramo->carretera }} - {{ $tramo->nombre }}</div>
                        <div class="status-sub" id="status-sub">
                            {{ $hasCoords ? 'Listo para pintar en mapa.' : 'Faltan lat/lng de inicio/fin.' }}
                        </div>
                    </div>
                    <div class="status-actions">
                        <button class="btn btn-sm btn-outline-primary" id="btn-refresh" type="button" title="Recentrar" {{ $hasCoords ? '' : 'disabled' }}>
                            <i class="fas fa-crosshairs"></i>
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
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .status-sub{
        font-size:12.5px;
        color:#6c757d;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .status-actions{flex:0 0 auto;}

    .leaflet-container{
        background:#f6f7fb;
    }
</style>
@stop

@section('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    const hasCoords = @json($hasCoords ? true : false);

    const latInicio = @json($tramo->lat_inicio);
    const lngInicio = @json($tramo->lng_inicio);
    const latFin = @json($tramo->lat_fin);
    const lngFin = @json($tramo->lng_fin);

    const defaultCenter = [19.703, -101.186];
    const mapCenter = hasCoords ? [latInicio, lngInicio] : defaultCenter;

    const map = L.map('map', { zoomControl: true }).setView(mapCenter, hasCoords ? 13 : 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    let line = null;
    let mInicio = null;
    let mFin = null;

    function paint(){
        if(!hasCoords) return;

        const start = [Number(latInicio), Number(lngInicio)];
        const end = [Number(latFin), Number(lngFin)];

        mInicio = L.marker(start).addTo(map).bindPopup('<strong>Inicio</strong><br>' + start[0] + ', ' + start[1]);
        mFin = L.marker(end).addTo(map).bindPopup('<strong>Fin</strong><br>' + end[0] + ', ' + end[1]);

        line = L.polyline([start, end], { weight: 6, opacity: 0.9 }).addTo(map);

        fitAll();
    }

    function fitAll(){
        if(!line) return;
        map.fitBounds(line.getBounds(), { padding: [25, 25] });
    }

    document.getElementById('btn-fit')?.addEventListener('click', () => fitAll());
    document.getElementById('btn-refresh')?.addEventListener('click', () => fitAll());

    document.getElementById('btn-copy')?.addEventListener('click', async () => {
        if(!hasCoords) return;
        const txt = `${latInicio},${lngInicio} -> ${latFin},${lngFin}`;
        try{
            await navigator.clipboard.writeText(txt);
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Coordenadas copiadas',
                showConfirmButton: false,
                timer: 2000
            });
        }catch(e){
            Swal.fire({
                icon: 'error',
                title: 'No se pudo copiar',
                text: 'Copia manualmente las coordenadas.',
                confirmButtonText: 'Aceptar'
            });
        }
    });

    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        let form = $(this).closest('form');

        Swal.fire({
            title: '¿Eliminar este tramo?',
            text: "No podrás revertir esta acción",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    paint();
</script>
@stop
