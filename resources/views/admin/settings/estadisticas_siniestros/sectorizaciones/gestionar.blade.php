@extends('adminlte::page')

@section('title', 'Gestionar Sectorización')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Siniestros · Sectorización · Lienzo Operativo</span>
            </div>
            <div class="sv-hero__title">Sectorización de {{ $fecha }}</div>
            <div class="sv-hero__subtitle">
                @if(!empty($turnoActivo))
                    Turno activo: {{ $turnoActivo->nombre ?? 'N/D' }}
                @else
                    Sin turno activo detectado
                @endif
            </div>
        </div>
    </div>
@stop

@section('content')
    @php
        $sectorMeta = [
            'I' => ['nombre' => 'Revolución', 'romano' => 'I', 'color' => '#f2a9c2'],
            'II' => ['nombre' => 'Nueva España', 'romano' => 'II', 'color' => '#8fd3f4'],
            'III' => ['nombre' => 'Independencia', 'romano' => 'III', 'color' => '#eadfa6'],
            'IV' => ['nombre' => 'República', 'romano' => 'IV', 'color' => '#b9ef3a'],
        ];

        $defaultPositions = [
            'I' => ['x' => 72, 'y' => 28],
            'II' => ['x' => 64, 'y' => 68],
            'III' => ['x' => 28, 'y' => 66],
            'IV' => ['x' => 28, 'y' => 26],
        ];

        $guardados = $asignaciones['elementos'] ?? [];
        $guardadosById = collect($guardados)->keyBy(fn ($e) => (int) ($e['personal_id'] ?? 0));
        $ef = $estadoFuerza ?? ($asignaciones['estado_fuerza'] ?? []);

        $cards = collect($personal)->map(function ($item) use ($guardadosById) {
            $saved = $guardadosById->get((int) $item['id']);

            return [
                'id' => (int) $item['id'],
                'nombre_completo' => $item['nombre_completo'],
                'patrulla' => $item['patrulla'] ?: 'Sin patrulla',
                'patrulla_id' => $item['patrulla_id'] ?? null,
                'patrulla_tipo' => $item['patrulla_tipo'] ?? null,
                'estado_fuerza' => $item['estado_fuerza'],
                'turno' => $item['turno'] ?? '',
                'sector' => $saved['sector'] ?? null,
                'grupo' => $saved['grupo'] ?? null,
                'x' => $saved['x'] ?? null,
                'y' => $saved['y'] ?? null,
                'lat' => $saved['lat'] ?? null,
                'lng' => $saved['lng'] ?? null,
            ];
        })->values();

        $libres = $cards->filter(fn ($c) => empty($c['sector']))->values();
        $enMapa = $cards->filter(fn ($c) => !empty($c['sector']))->values();
        $gruposMapa = $enMapa->groupBy(fn ($c) => $c['grupo'] ?: 'personal-' . $c['id']);
        $turnoTexto = trim((string) ($turnoActivo->nombre ?? ''));
        $turnoNombre = \Illuminate\Support\Str::upper($turnoTexto);
        $turnoEtiqueta = $turnoNombre === ''
            ? 'SIN TURNO ACTIVO'
            : (\Illuminate\Support\Str::startsWith($turnoNombre, 'TURNO') ? $turnoNombre : 'TURNO ' . $turnoNombre);
        $fechaEtiqueta = \Illuminate\Support\Str::upper(
            \Carbon\Carbon::parse($fecha, 'America/Mexico_City')
                ->locale('es')
                ->translatedFormat('d \d\e F \d\e Y')
        );
    @endphp

    <div class="row">
        <div class="col-xl-3 col-12">
            <div class="sv-panel mb-4">
                <div class="sv-panel__header">
                    <div>
                        <div class="sv-panel__title">Acciones</div>
                        <div class="sv-panel__desc">Arrastra elementos al mapa y guarda.</div>
                    </div>
                </div>
                <div class="sv-panel__body">
                    <div class="sv-actions">
                        <button type="button" class="btn sv-btn" id="btnGuardarSectorizacion">
                            <i class="fas fa-save"></i> Guardar
                        </button>

                        <button type="button" class="btn sv-btn" id="btnDescargarSectorizacion">
                            <i class="fas fa-file-pdf"></i> Descargar PDF
                        </button>

                        <a href="{{ route('settings.estadisticas_siniestros.sectorizaciones') }}" class="btn sv-btn sv-btn--ghost">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>

            <div class="sv-panel">
                <div class="sv-panel__header">
                    <div>
                        <div class="sv-panel__title">Elementos disponibles</div>
                        <div class="sv-panel__desc">Arrastra al mapa. Doble clic para regresar.</div>
                    </div>
                </div>
                <div class="sv-panel__body">
                    <div class="sv-pool" id="pool-personal">
                        @foreach($libres as $item)
                            <div class="sv-card-personal sv-card-personal--pool"
                                 data-id="{{ $item['id'] }}"
                                 data-sector=""
                                 data-x=""
                                 data-y=""
                                 data-nombre="{{ $item['nombre_completo'] }}"
                                 data-patrulla="{{ $item['patrulla'] }}"
                                 data-patrulla-id="{{ $item['patrulla_id'] }}"
                                 data-patrulla-tipo="{{ $item['patrulla_tipo'] }}"
                                 data-estado="{{ $item['estado_fuerza'] }}"
                                 data-turno="{{ $item['turno'] }}">
                                <div class="sv-card-personal__name">{{ $item['nombre_completo'] }}</div>
                                <div class="sv-card-personal__meta">Patrulla: {{ $item['patrulla'] }}</div>
                                <div class="sv-card-personal__meta">Estado: {{ $item['estado_fuerza'] }}</div>
                                @if(!empty($item['turno']))
                                    <div class="sv-card-personal__meta">Turno: {{ $item['turno'] }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-9 col-12">
            <div class="sv-panel">
                <div class="sv-panel__header">
                    <div>
                        <div class="sv-panel__title">Lienzo operativo</div>
                        <div class="sv-panel__desc">Arrastre libre sobre el mapa, como en el PDF final.</div>
                    </div>
                </div>
                <div class="sv-panel__body">
                    <div class="sv-canvas-wrap">
                        <div class="sv-canvas" id="sectorCanvas">
                            <div class="sv-title-box">
                                <div class="sv-title-box__heading">SECTORIZACIÓN DE UNIDADES</div>
                                <div id="tituloTurno">{{ $turnoEtiqueta }}</div>
                                <div>{{ $fechaEtiqueta }}</div>
                            </div>

                            <div class="sv-state-box">
                                <div class="sv-state-box__title">ESTADO DE FUERZA</div>
                                <div class="sv-state-line"><span>ESTADO DE FUERZA:</span><strong data-state-key="estado_fuerza">{{ $ef['estado_fuerza'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>LABORANDO:</span><strong data-state-key="laborando">{{ $ef['laborando'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>CMDT DE TURNO:</span><strong data-state-key="cmdt_turno">{{ $ef['cmdt_turno'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>BASE RADIO:</span><strong data-state-key="base_radio">{{ $ef['base_radio'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>ELEMENTOS EN RECORRIDO:</span><strong data-state-key="elementos_recorrido">{{ $ef['elementos_recorrido'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>MÓDULO:</span><strong data-state-key="modulo">{{ $ef['modulo'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>CURSO:</span><strong data-state-key="curso">{{ $ef['curso'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>PERMISO:</span><strong data-state-key="permiso">{{ $ef['permiso'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>INCAPACIDAD:</span><strong data-state-key="incapacidad">{{ $ef['incapacidad'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>FRANCOS:</span><strong data-state-key="francos">{{ $ef['francos'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>VACACIONES:</span><strong data-state-key="vacaciones">{{ $ef['vacaciones'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>FALTANDO:</span><strong data-state-key="faltando">{{ $ef['faltando'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>COMISIONADOS:</span><strong data-state-key="comisionados">{{ $ef['comisionados'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>CRP:</span><strong data-state-key="crp">{{ $ef['crp'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>MOTOS:</span><strong data-state-key="motos">{{ $ef['motos'] ?? '' }}</strong></div>
                            </div>

                            <div class="sv-legend-box">
                                <div class="sv-legend-box__title">SECTORES</div>
                                <div class="sv-legend-line"><span>1.- Revolución</span><i style="background:#f2a9c2"></i></div>
                                <div class="sv-legend-line"><span>2.- Nueva España</span><i style="background:#8fd3f4"></i></div>
                                <div class="sv-legend-line"><span>3.- Independencia</span><i style="background:#eadfa6"></i></div>
                                <div class="sv-legend-line"><span>4.- República</span><i style="background:#b9ef3a"></i></div>
                            </div>

                            <div class="sv-real-map" id="sectorMap" aria-label="Mapa real de Morelia con el libramiento resaltado"></div>

                            <div class="sv-canvas-cards" id="canvasCards">
                                @foreach($gruposMapa as $grupoId => $miembros)
                                    @php
                                        $item = $miembros->first();
                                        $sectorActual = $item['sector'] ?? 'I';
                                        $sx = $item['x'] ?? ($defaultPositions[$sectorActual]['x'] ?? 50);
                                        $sy = $item['y'] ?? ($defaultPositions[$sectorActual]['y'] ?? 50);
                                        $lat = $item['lat'] ?? null;
                                        $lng = $item['lng'] ?? null;
                                        $color = $sectorMeta[$sectorActual]['color'] ?? '#ffffff';
                                        $miembroConPatrulla = $miembros->first(fn ($m) => ($m['patrulla'] ?? 'Sin patrulla') !== 'Sin patrulla');
                                        $patrullaGrupo = $miembroConPatrulla['patrulla'] ?? 'Sin patrulla';
                                        $miembrosJson = $miembros->map(fn ($m) => [
                                            'id' => (int) $m['id'],
                                            'nombre' => $m['nombre_completo'],
                                            'patrulla' => $m['patrulla'],
                                            'patrulla_id' => $m['patrulla_id'],
                                            'patrulla_tipo' => $m['patrulla_tipo'],
                                            'estado' => $m['estado_fuerza'],
                                            'turno' => $m['turno'],
                                        ])->values()->all();
                                    @endphp
                                    <div class="sv-canvas-card"
                                         data-id="{{ $item['id'] }}"
                                         data-group="{{ $grupoId }}"
                                         data-members="{{ e(json_encode($miembrosJson, JSON_UNESCAPED_UNICODE)) }}"
                                         data-sector="{{ $sectorActual }}"
                                         data-x="{{ $sx }}"
                                         data-y="{{ $sy }}"
                                         data-lat="{{ $lat }}"
                                         data-lng="{{ $lng }}"
                                         data-patrulla="{{ $patrullaGrupo }}"
                                         style="left:{{ $sx }}%; top:{{ $sy }}%; --sector-color: {{ $color }};">
                                        <div class="sv-canvas-card__grip"><i class="fas fa-arrows-alt"></i></div>
                                        <div class="sv-canvas-card__body">
                                            <div class="sv-canvas-card__name">{{ $patrullaGrupo }}</div>
                                            <div class="sv-canvas-card__person">
                                                @foreach($miembros as $miembro)
                                                    <div>{{ $miembro['nombre_completo'] }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="sv-canvas-card__sector">{{ $sectorActual }}</div>
                                    </div>
                                @endforeach
                            </div>

                        </div>
                    </div>

                    <div class="sv-help">
                        Usa +/− o la rueda para acercar y alejar. Suelta un elemento sin patrulla encima de una tarjeta con unidad para unirlo como tripulante; haz doble clic para regresar todo el grupo a disponibles.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" id="fechaSectorizacion" value="{{ $fecha }}">
    <input type="hidden" id="turnoSectorizacion" value="{{ $turnoTexto }}">
@stop

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    :root{
        --sv-text: rgba(234,240,255,.94);
        --sv-muted: rgba(234,240,255,.68);
        --sv-stroke: rgba(255,255,255,.12);
        --sv-card: rgba(255,255,255,.08);
        --sv-card2: rgba(255,255,255,.05);
        --sv-shadow: 0 18px 55px rgba(0,0,0,.35);
    }

    .sv-hero{
        margin: 10px 0 12px;
        border-radius: 26px;
        border: 1px solid rgba(255,255,255,.12);
        background:
            radial-gradient(700px 280px at 20% 30%, rgba(45,168,255,.20), transparent 60%),
            radial-gradient(700px 280px at 80% 30%, rgba(124,92,255,.18), transparent 60%),
            linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.04));
        box-shadow: var(--sv-shadow);
        overflow: hidden;
    }

    .sv-hero__inner{
        padding:18px 18px 16px;
        text-align:center;
    }

    .sv-hero__badge{
        display:inline-flex;
        align-items:center;
        gap:10px;
        padding:8px 12px;
        border-radius:999px;
        background:rgba(0,0,0,.18);
        border:1px solid rgba(255,255,255,.10);
        color:rgba(234,240,255,.85);
        font-weight:800;
        font-size:12px;
        letter-spacing:.35px;
    }

    .sv-dot{
        width:8px;
        height:8px;
        border-radius:999px;
        background:#19D38C;
        box-shadow:0 0 0 5px rgba(25,211,140,.14);
        display:inline-block;
    }

    .sv-hero__title{
        margin-top:10px;
        font-weight:950;
        letter-spacing:-.6px;
        font-size:clamp(22px,2.3vw,32px);
        color:var(--sv-text);
    }

    .sv-hero__subtitle{
        margin-top:6px;
        font-weight:650;
        font-size:13px;
        color:var(--sv-muted);
    }

    .sv-panel{
        border-radius:22px;
        border:1px solid rgba(255,255,255,.12);
        background:linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.05));
        box-shadow:0 10px 35px rgba(0,0,0,.22);
        overflow:hidden;
    }

    .sv-panel__header{
        padding:18px 18px 10px 18px;
        border-bottom:1px solid rgba(255,255,255,.08);
    }

    .sv-panel__body{
        padding:18px;
    }

    .sv-panel__title{
        font-weight:900;
        font-size:16px;
        color:var(--sv-text);
    }

    .sv-panel__desc{
        margin-top:4px;
        font-size:12.5px;
        color:var(--sv-muted);
    }

    .sv-actions{
        display:flex;
        flex-wrap:wrap;
        gap:10px;
    }

    .sv-btn{
        display:inline-flex;
        align-items:center;
        gap:8px;
        border-radius:14px;
        font-weight:900;
        border:1px solid rgba(45,168,255,.35) !important;
        background:linear-gradient(135deg, rgba(45,168,255,.25), rgba(124,92,255,.22)) !important;
        color:rgba(234,240,255,.95) !important;
        padding:8px 12px;
    }

    .sv-btn:hover{
        transform:translateY(-1px);
        border-color:rgba(45,168,255,.55) !important;
        background:linear-gradient(135deg, rgba(45,168,255,.34), rgba(124,92,255,.30)) !important;
        color:rgba(234,240,255,.98) !important;
    }

    .sv-btn--ghost{
        border-color: rgba(255,255,255,.14) !important;
        background: rgba(255,255,255,.06) !important;
    }

    .sv-pool{
        min-height:280px;
        max-height:540px;
        overflow:auto;
        display:flex;
        flex-direction:column;
        gap:10px;
        padding-right:4px;
    }

    .sv-card-personal{
        padding:12px 14px;
        border-radius:16px;
        border:1px solid rgba(255,255,255,.12);
        background:linear-gradient(180deg, rgba(255,255,255,.09), rgba(255,255,255,.05));
        box-shadow:0 8px 22px rgba(0,0,0,.18);
        backdrop-filter:blur(10px);
        cursor:grab;
        user-select:none;
    }

    .sv-card-personal__name{
        font-weight:900;
        color:var(--sv-text);
        font-size:12.8px;
        line-height:1.2;
    }

    .sv-card-personal__meta{
        margin-top:4px;
        color:var(--sv-muted);
        font-size:11.5px;
        font-weight:700;
    }

    .sv-canvas-wrap{
        width:100%;
        overflow:visible;
    }

    .sv-canvas{
        position:relative;
        width:100%;
        height:clamp(620px, 76vh, 900px);
        min-width:0;
        border-radius:28px;
        border:1px solid rgba(255,255,255,.12);
        background:#ededed;
        overflow:hidden;
        box-shadow:0 22px 60px rgba(0,0,0,.28);
    }

    .sv-real-map{
        position:absolute;
        inset:0;
        width:100%;
        height:100%;
        z-index:0;
        background:#dce4e7;
    }

    .sv-real-map .leaflet-tile-pane{
        filter:saturate(.58) contrast(.92) brightness(1.06);
    }

    .sv-real-map .leaflet-control-attribution{
        right:8px;
        bottom:6px;
        border-radius:5px;
        background:rgba(255,255,255,.82);
        font-size:9px;
    }

    .sv-sector-label{
        border:0;
        background:rgba(255,255,255,.84);
        box-shadow:0 2px 8px rgba(15,23,42,.16);
        color:#172033;
        font-size:10.5px;
        font-weight:950;
        letter-spacing:.25px;
        text-transform:uppercase;
    }

    .sv-fit-map-control a{
        display:grid;
        place-items:center;
        width:34px !important;
        height:34px !important;
        color:#172033 !important;
        font-size:15px;
        line-height:34px !important;
    }

    .sv-fit-map-control a:hover{
        background:#f1f5f9 !important;
    }

    .sv-title-box,
    .sv-state-box,
    .sv-legend-box{
        position:absolute;
        background:rgba(255,255,255,.96);
        border:3px solid #222;
        box-shadow:0 8px 25px rgba(0,0,0,.12);
        z-index:2;
        color:#111827;
        -webkit-text-fill-color:#111827;
        text-shadow:none;
    }

    .sv-title-box{
        left:20px;
        top:20px;
        width:250px;
        max-width:calc(100% - 40px);
        text-align:center;
        padding:5px 8px 6px;
        font-family:Arial, Helvetica, sans-serif;
        font-weight:700;
        font-size:13px;
        line-height:1.08;
        border-width:2px;
        box-shadow:none;
    }

    .sv-title-box div + div{
        margin-top:1px;
    }

    .sv-title-box__heading{
        white-space:nowrap;
    }

    .sv-state-box{
        right:20px;
        bottom:26px;
        width:220px;
        padding:8px 9px;
        font-family:"Times New Roman", serif;
    }

    .sv-state-box__title{
        font-size:16px;
        font-weight:700;
        text-align:center;
        text-decoration:underline;
        margin-bottom:6px;
    }

    .sv-state-line{
        display:flex;
        justify-content:space-between;
        gap:6px;
        font-size:10.5px;
        line-height:1.18;
        font-weight:700;
    }

    .sv-state-line strong{
        font-weight:700;
    }

    .sv-legend-box{
        left:30px;
        bottom:28px;
        width:155px;
        padding:8px 9px;
        font-family:"Times New Roman", serif;
    }

    .sv-legend-box__title{
        text-align:center;
        font-size:15px;
        font-weight:700;
        text-decoration:underline;
        margin-bottom:7px;
    }

    .sv-legend-line{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:7px;
        font-size:11.5px;
        margin-bottom:5px;
    }

    .sv-legend-line i{
        width:13px;
        height:13px;
        border:1px solid #555;
        display:inline-block;
    }

    .sv-canvas-cards{
        position:absolute;
        inset:0;
        z-index:4;
        pointer-events:none;
    }

    .sv-canvas-card{
        position:absolute;
        min-width:82px;
        max-width:112px;
        border:1.5px dashed #222;
        background:var(--sector-color, #ffffff);
        box-shadow:0 6px 16px rgba(0,0,0,.14);
        transform:translate(-50%, -50%);
        cursor:grab;
        user-select:none;
        pointer-events:auto;
    }

    .sv-canvas-card__grip{
        position:absolute;
        top:-9px;
        right:-9px;
        width:21px;
        height:21px;
        border-radius:999px;
        display:grid;
        place-items:center;
        background:#111;
        color:#fff;
        font-size:9px;
        box-shadow:0 4px 9px rgba(0,0,0,.18);
    }

    .sv-canvas-card__body{
        padding:6px 5px 5px;
        text-align:center;
        font-family:"Times New Roman", serif;
        color:#111;
    }

    .sv-canvas-card__name{
        font-size:9.5px;
        font-weight:700;
        line-height:1.02;
    }

    .sv-canvas-card__person{
        margin-top:2px;
        font-size:8.5px;
        line-height:1.04;
        font-weight:700;
        text-transform:uppercase;
        word-break:break-word;
    }

    .sv-canvas-card__person > div + div{
        margin-top:2px;
        padding-top:2px;
        border-top:1px solid rgba(17,24,39,.24);
    }

    .sv-canvas-card__sector{
        position:absolute;
        left:3px;
        top:3px;
        padding:1px 4px;
        border-radius:999px;
        font-size:7.5px;
        font-weight:900;
        background:rgba(0,0,0,.12);
        color:#111;
    }

    .sv-canvas-card.dragging{
        z-index:12;
        opacity:.9;
    }

    .sv-help{
        margin-top:12px;
        color:var(--sv-muted);
        font-size:12px;
        font-weight:800;
    }

    @media (max-width: 767.98px){
        .sv-canvas{
            height:680px;
        }
    }

    .sv-canvas.sv-exporting .leaflet-control-zoom,
    .sv-canvas.sv-exporting .sv-fit-map-control,
    .sv-canvas.sv-exporting .sv-canvas-card__grip{
        display:none !important;
    }
</style>
@stop

@section('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.2/dist/jspdf.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('sectorCanvas');
    const canvasCards = document.getElementById('canvasCards');
    const pool = document.getElementById('pool-personal');
    const fecha = document.getElementById('fechaSectorizacion').value;
    const turno = document.getElementById('turnoSectorizacion').value || '';
    const autoDownloadPdf = new URLSearchParams(window.location.search).get('descargar') === 'pdf';
    let active = null;
    let offsetX = 0;
    let offsetY = 0;

    const estadoFuerzaPayload = @json($ef);

    const map = L.map('sectorMap', {
        zoomControl: true,
        attributionControl: true,
        dragging: true,
        scrollWheelZoom: true,
        doubleClickZoom: true,
        boxZoom: true,
        keyboard: true,
        touchZoom: true,
        tap: true,
        preferCanvas: true,
        zoomSnap: .25,
        zoomDelta: .5,
        wheelPxPerZoomLevel: 100
    });
    const moreliaBounds = L.latLngBounds(
        [19.664, -101.255],
        [19.735, -101.139]
    );
    const sectorPolygons = {
        I: [
            [19.7028925,-101.1914683], [19.7214412,-101.1884601],
            [19.7198344,-101.1852941], [19.7202791,-101.1794405],
            [19.7185836,-101.1765598], [19.7164184,-101.1736369],
            [19.7130007,-101.1695268], [19.7094644,-101.1650681],
            [19.7046700,-101.1601110], [19.7003728,-101.1546403],
            [19.6970100,-101.1609065], [19.6958457,-101.1635400],
            [19.6967552,-101.1682834], [19.6990959,-101.1782890]
        ],
        II: [
            [19.7028925,-101.1914683], [19.6990959,-101.1782890],
            [19.6967552,-101.1682834], [19.6958457,-101.1635400],
            [19.6970100,-101.1609065], [19.7003728,-101.1546403],
            [19.6975784,-101.1510437], [19.6952378,-101.1492136],
            [19.6920269,-101.1500614], [19.6872031,-101.1534088],
            [19.6876932,-101.1584748], [19.6869403,-101.1621722],
            [19.6841328,-101.1661754], [19.6820752,-101.1697610],
            [19.6818590,-101.1741046], [19.6814521,-101.1778022],
            [19.6814115,-101.1821745], [19.6810374,-101.1864570],
            [19.6822641,-101.1894391], [19.6817549,-101.1945579],
            [19.6868786,-101.1919516], [19.6912734,-101.1918317],
            [19.6973356,-101.1915751]
        ],
        III: [
            [19.7028925,-101.1914683], [19.6973356,-101.1915751],
            [19.6912734,-101.1918317], [19.6868786,-101.1919516],
            [19.6817549,-101.1945579], [19.6769286,-101.2023209],
            [19.6720326,-101.2097916], [19.6756748,-101.2170608],
            [19.6768404,-101.2257324], [19.6784377,-101.2336628],
            [19.6882652,-101.2383388], [19.7006106,-101.2436675],
            [19.7023860,-101.2346007], [19.7044798,-101.2259362],
            [19.7037738,-101.2161295], [19.7025095,-101.2041507]
        ],
        IV: [
            [19.7028925,-101.1914683], [19.7025095,-101.2041507],
            [19.7037738,-101.2161295], [19.7044798,-101.2259362],
            [19.7023860,-101.2346007], [19.7006106,-101.2436675],
            [19.7037995,-101.2448270], [19.7069449,-101.2371918],
            [19.7178421,-101.2290426], [19.7230355,-101.2254816],
            [19.7269991,-101.2224215], [19.7269079,-101.2143231],
            [19.7254995,-101.2029251], [19.7236214,-101.1933903],
            [19.7214412,-101.1884601]
        ]
    };
    const sectorNames = {
        I: 'Revolución',
        II: 'Nueva España',
        III: 'Independencia',
        IV: 'República'
    };

    const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        crossOrigin: 'anonymous',
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    map.zoomControl.setPosition('topright');

    function fitMorelia() {
        map.fitBounds(moreliaBounds, {
            animate: false,
            padding: [34, 34]
        });
    }

    const FitMoreliaControl = L.Control.extend({
        options: {
            position: 'topright'
        },
        onAdd: function () {
            const container = L.DomUtil.create('div', 'leaflet-bar sv-fit-map-control');
            const button = L.DomUtil.create('a', '', container);

            button.href = '#';
            button.title = 'Mostrar todo Morelia';
            button.setAttribute('role', 'button');
            button.setAttribute('aria-label', 'Mostrar todo Morelia');
            button.innerHTML = '<i class="fas fa-expand-arrows-alt"></i>';

            L.DomEvent.disableClickPropagation(container);
            L.DomEvent.on(button, 'click', function (event) {
                L.DomEvent.preventDefault(event);
                fitMorelia();
            });

            return container;
        }
    });

    new FitMoreliaControl().addTo(map);
    map.setMaxBounds(moreliaBounds.pad(.7));

    requestAnimationFrame(function () {
        map.invalidateSize(false);
        fitMorelia();

        requestAnimationFrame(function () {
            anchorCardsWithoutCoordinates();
            syncCardsToMap();
        });
    });

    Object.entries(sectorPolygons).forEach(function ([sector, points]) {
        const layer = L.polygon(points, {
            color: '#20242a',
            weight: 2.5,
            opacity: .68,
            fillColor: sectorColor(sector),
            fillOpacity: .34,
            interactive: false
        }).addTo(map);

        layer.bindTooltip('Sector ' + sectorNames[sector], {
            permanent: true,
            direction: 'center',
            className: 'sv-sector-label'
        });
    });

    const libramientoReady = fetch(@json(asset('geo/morelia_libramiento.geojson')))
        .then(function (response) {
            if (!response.ok) {
                throw new Error('No se cargó el libramiento de Morelia.');
            }

            return response.json();
        })
        .then(function (geojson) {
            L.geoJSON(geojson, {
                interactive: false,
                style: {
                    color: '#f8fafc',
                    weight: 18,
                    opacity: .72,
                    lineCap: 'round',
                    lineJoin: 'round'
                }
            }).addTo(map);

            L.geoJSON(geojson, {
                interactive: false,
                style: {
                    color: '#24272b',
                    weight: 12,
                    opacity: .98,
                    lineCap: 'round',
                    lineJoin: 'round'
                }
            }).addTo(map);

            const cierreLibramiento = [19.681786, -101.194578];

            L.circleMarker(cierreLibramiento, {
                radius: 9,
                color: '#24272b',
                weight: 0,
                fillColor: '#24272b',
                fillOpacity: 1,
                interactive: false
            }).addTo(map);
        })
        .catch(function (error) {
            console.warn(error.message);
        });

    function pointInPolygon(point, vs) {
        let x = point[0];
        let y = point[1];
        let inside = false;

        for (let i = 0, j = vs.length - 1; i < vs.length; j = i++) {
            const xi = vs[i][0];
            const yi = vs[i][1];
            const xj = vs[j][0];
            const yj = vs[j][1];

            const intersect = ((yi > y) !== (yj > y)) && (x < ((xj - xi) * (y - yi)) / ((yj - yi) || 1e-9) + xi);

            if (intersect) {
                inside = !inside;
            }
        }

        return inside;
    }

    function detectSectorLatLng(latLng) {
        for (const sector in sectorPolygons) {
            const polygon = sectorPolygons[sector].map(function (coordinate) {
                return [coordinate[1], coordinate[0]];
            });

            if (pointInPolygon([latLng.lng, latLng.lat], polygon)) {
                return sector;
            }
        }

        return '';
    }

    function sectorColor(sector) {
        const colors = {
            I: '#f2a9c2',
            II: '#8fd3f4',
            III: '#eadfa6',
            IV: '#b9ef3a'
        };

        return colors[sector] || '#ffffff';
    }

    function anchorCardsWithoutCoordinates() {
        const cards = Array.from(canvasCards.querySelectorAll('.sv-canvas-card'));

        cards.forEach(function (card) {
            const savedLat = parseFloat(card.dataset.lat || '');
            const savedLng = parseFloat(card.dataset.lng || '');

            if (Number.isFinite(savedLat) && Number.isFinite(savedLng)) {
                return;
            }

            const percentX = parseFloat(card.dataset.x || '50');
            const percentY = parseFloat(card.dataset.y || '50');
            const point = L.point(
                (percentX / 100) * canvas.clientWidth,
                (percentY / 100) * canvas.clientHeight
            );
            let latLng = map.containerPointToLatLng(point);
            const expectedSector = card.dataset.sector || '';

            // Los cortes anteriores solo tenían porcentajes de pantalla. Si ese
            // porcentaje ya no cae en su sector, lo recuperamos dentro del sector guardado.
            if (expectedSector && sectorPolygons[expectedSector]
                && detectSectorLatLng(latLng) !== expectedSector) {
                const sectorCards = cards.filter(function (candidate) {
                    return candidate.dataset.sector === expectedSector;
                });
                const sectorIndex = sectorCards.indexOf(card);
                const sectorCenter = L.latLngBounds(sectorPolygons[expectedSector]).getCenter();
                const centerPoint = map.latLngToContainerPoint(sectorCenter);
                const column = (sectorIndex % 3) - 1;
                const row = Math.floor(sectorIndex / 3);

                centerPoint.x += column * 38;
                centerPoint.y += row * 30;
                latLng = map.containerPointToLatLng(centerPoint);
            }

            card.dataset.lat = latLng.lat.toFixed(7);
            card.dataset.lng = latLng.lng.toFixed(7);
        });
    }

    function syncCardsToMap() {
        canvasCards.querySelectorAll('.sv-canvas-card').forEach(function (card) {
            if (card === active) {
                return;
            }

            const lat = parseFloat(card.dataset.lat || '');
            const lng = parseFloat(card.dataset.lng || '');

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const point = map.latLngToContainerPoint([lat, lng]);
            const percentX = (point.x / Math.max(canvas.clientWidth, 1)) * 100;
            const percentY = (point.y / Math.max(canvas.clientHeight, 1)) * 100;

            card.style.left = percentX + '%';
            card.style.top = percentY + '%';
            card.dataset.x = percentX.toFixed(2);
            card.dataset.y = percentY.toFixed(2);
        });
    }

    let syncCardsFrame = null;

    function scheduleCardsSync() {
        if (syncCardsFrame !== null) {
            return;
        }

        syncCardsFrame = requestAnimationFrame(function () {
            syncCardsFrame = null;
            syncCardsToMap();
        });
    }

    map.on('move zoom viewreset resize', scheduleCardsSync);

    function startDrag(card, clientX, clientY) {
        active = card;

        const rect = card.getBoundingClientRect();
        offsetX = clientX - rect.left;
        offsetY = clientY - rect.top;

        card.classList.add('dragging');
    }

    function moveDrag(clientX, clientY) {
        if (!active) {
            return;
        }

        const rect = canvas.getBoundingClientRect();

        let left = clientX - rect.left - offsetX + (active.offsetWidth / 2);
        let top = clientY - rect.top - offsetY + (active.offsetHeight / 2);

        const minX = active.offsetWidth / 2;
        const maxX = rect.width - active.offsetWidth / 2;
        const minY = active.offsetHeight / 2;
        const maxY = rect.height - active.offsetHeight / 2;

        left = Math.max(minX, Math.min(maxX, left));
        top = Math.max(minY, Math.min(maxY, top));

        const px = (left / rect.width) * 100;
        const py = (top / rect.height) * 100;
        const point = L.point(left, top);
        const latLng = map.containerPointToLatLng(point);
        const sector = detectSectorLatLng(latLng);

        active.style.left = px + '%';
        active.style.top = py + '%';
        active.dataset.x = px.toFixed(2);
        active.dataset.y = py.toFixed(2);
        active.dataset.lat = latLng.lat.toFixed(7);
        active.dataset.lng = latLng.lng.toFixed(7);
        active.dataset.sector = sector;
        active.style.setProperty('--sector-color', sectorColor(sector));

        const badge = active.querySelector('.sv-canvas-card__sector');
        if (badge) {
            badge.textContent = sector || '?';
        }
    }

    function endDrag() {
        if (!active) {
            return;
        }

        mergeOverlappingCard(active);
        active.classList.remove('dragging');
        active = null;
        actualizarResumenMapa();
    }

    function escapeHtml(value) {
        const element = document.createElement('span');
        element.textContent = value == null ? '' : String(value);
        return element.innerHTML;
    }

    function readMembers(card) {
        try {
            const members = JSON.parse(card.dataset.members || '[]');

            if (Array.isArray(members) && members.length) {
                return members;
            }
        } catch (error) {
            console.warn('No se pudieron leer los tripulantes de la tarjeta.', error);
        }

        return [{
            id: parseInt(card.dataset.id || 0, 10),
            nombre: card.dataset.nombre || '',
            patrulla: card.dataset.patrulla || 'Sin patrulla',
            patrulla_id: card.dataset.patrullaId || null,
            patrulla_tipo: card.dataset.patrullaTipo || '',
            estado: card.dataset.estado || 'EN_SERVICIO',
            turno: card.dataset.turno || ''
        }];
    }

    function actualizarValorEstadoFuerza(clave, valor) {
        estadoFuerzaPayload[clave] = valor;

        const target = document.querySelector('[data-state-key="' + clave + '"]');
        if (target) {
            target.textContent = valor;
        }
    }

    function actualizarResumenMapa() {
        const miembros = Array.from(canvasCards.querySelectorAll('.sv-canvas-card'))
            .flatMap(readMembers)
            .filter(function (member, index, all) {
                return all.findIndex(function (candidate) {
                    return parseInt(candidate.id, 10) === parseInt(member.id, 10);
                }) === index;
            });
        const patrullas = new Map();

        miembros.forEach(function (member) {
            const numero = memberPatrol(member);
            if (!numero) {
                return;
            }

            const key = member.patrulla_id
                ? 'id-' + member.patrulla_id
                : 'numero-' + numero.toUpperCase();

            patrullas.set(key, String(member.patrulla_tipo || '').toUpperCase());
        });

        let motos = 0;
        let crp = 0;

        patrullas.forEach(function (tipo) {
            if (tipo.includes('MOTO')) {
                motos++;
            } else {
                crp++;
            }
        });

        actualizarValorEstadoFuerza('elementos_recorrido', miembros.length);
        actualizarValorEstadoFuerza('crp', crp);
        actualizarValorEstadoFuerza('motos', motos);
    }

    function memberPatrol(member) {
        const patrol = String(member.patrulla || '').trim();
        return patrol && patrol.toLowerCase() !== 'sin patrulla' ? patrol : '';
    }

    function patrolFromMembers(members) {
        const assigned = members.find(function (member) {
            return memberPatrol(member) !== '';
        });

        return assigned ? memberPatrol(assigned) : 'Sin patrulla';
    }

    function renderCanvasCard(card) {
        const members = readMembers(card);
        const patrol = patrolFromMembers(members);
        const name = card.querySelector('.sv-canvas-card__name');
        const people = card.querySelector('.sv-canvas-card__person');

        card.dataset.members = JSON.stringify(members);
        card.dataset.id = members[0] ? members[0].id : '';
        card.dataset.patrulla = patrol;

        if (name) {
            name.textContent = patrol;
        }

        if (people) {
            people.innerHTML = members.map(function (member) {
                return '<div>' + escapeHtml(member.nombre || '') + '</div>';
            }).join('');
        }
    }

    function mergeOverlappingCard(source) {
        const sourceRect = source.getBoundingClientRect();
        const centerX = sourceRect.left + (sourceRect.width / 2);
        const centerY = sourceRect.top + (sourceRect.height / 2);
        const candidates = Array.from(canvasCards.querySelectorAll('.sv-canvas-card')).reverse();
        const target = candidates.find(function (card) {
            if (card === source) {
                return false;
            }

            const rect = card.getBoundingClientRect();
            return centerX >= rect.left && centerX <= rect.right && centerY >= rect.top && centerY <= rect.bottom;
        });

        if (!target) {
            return false;
        }

        const sourceMembers = readMembers(source);
        const targetMembers = readMembers(target);
        const sourcePatrol = patrolFromMembers(sourceMembers);
        const targetPatrol = patrolFromMembers(targetMembers);
        const sourceAssigned = sourcePatrol !== 'Sin patrulla';
        const targetAssigned = targetPatrol !== 'Sin patrulla';

        if (sourceAssigned && targetAssigned && sourcePatrol !== targetPatrol) {
            return false;
        }

        if (!sourceAssigned && !targetAssigned) {
            return false;
        }

        const host = sourceAssigned && !targetAssigned ? source : target;
        const guest = host === source ? target : source;
        const combined = [...readMembers(host), ...readMembers(guest)].filter(function (member, index, all) {
            return all.findIndex(function (candidate) {
                return parseInt(candidate.id, 10) === parseInt(member.id, 10);
            }) === index;
        });

        host.dataset.group = host.dataset.group || ('personal-' + combined[0].id);
        host.dataset.members = JSON.stringify(combined);
        guest.remove();
        renderCanvasCard(host);

        return true;
    }

    function makeCanvasCardFromPool(poolCard, sector, x, y) {
        const card = document.createElement('div');
        const nombre = poolCard.dataset.nombre || '';
        const patrulla = poolCard.dataset.patrulla || 'Sin patrulla';
        const patrullaId = poolCard.dataset.patrullaId || null;
        const patrullaTipo = poolCard.dataset.patrullaTipo || '';
        const estado = poolCard.dataset.estado || '';
        const turnoCard = poolCard.dataset.turno || '';

        card.className = 'sv-canvas-card';
        card.dataset.id = poolCard.dataset.id;
        card.dataset.group = 'personal-' + poolCard.dataset.id;
        card.dataset.members = JSON.stringify([{
            id: parseInt(poolCard.dataset.id || 0, 10),
            nombre: nombre,
            patrulla: patrulla,
            patrulla_id: patrullaId,
            patrulla_tipo: patrullaTipo,
            estado: estado,
            turno: turnoCard
        }]);
        card.dataset.sector = sector;
        card.dataset.x = x;
        card.dataset.y = y;
        card.dataset.lat = '';
        card.dataset.lng = '';
        card.dataset.nombre = nombre;
        card.dataset.patrulla = patrulla;
        card.dataset.patrullaId = patrullaId || '';
        card.dataset.patrullaTipo = patrullaTipo;
        card.dataset.estado = estado;
        card.dataset.turno = turnoCard;
        card.style.left = x + '%';
        card.style.top = y + '%';
        card.style.setProperty('--sector-color', sectorColor(sector));

        card.innerHTML = `
            <div class="sv-canvas-card__grip"><i class="fas fa-arrows-alt"></i></div>
            <div class="sv-canvas-card__body">
                <div class="sv-canvas-card__name">${patrulla}</div>
                <div class="sv-canvas-card__person">${nombre}</div>
            </div>
            <div class="sv-canvas-card__sector">${sector || '?'}</div>
        `;

        bindCanvasCard(card);

        return card;
    }

    function makePoolCardFromMember(member) {
        const poolCard = document.createElement('div');

        poolCard.className = 'sv-card-personal sv-card-personal--pool';
        poolCard.dataset.id = member.id || '';
        poolCard.dataset.sector = '';
        poolCard.dataset.x = '';
        poolCard.dataset.y = '';
        poolCard.dataset.nombre = member.nombre || '';
        poolCard.dataset.patrulla = member.patrulla || 'Sin patrulla';
        poolCard.dataset.patrullaId = member.patrulla_id || '';
        poolCard.dataset.patrullaTipo = member.patrulla_tipo || '';
        poolCard.dataset.estado = member.estado || 'EN_SERVICIO';
        poolCard.dataset.turno = member.turno || '';

        let html = `
            <div class="sv-card-personal__name">${poolCard.dataset.nombre}</div>
            <div class="sv-card-personal__meta">Patrulla: ${poolCard.dataset.patrulla}</div>
            <div class="sv-card-personal__meta">Estado: ${poolCard.dataset.estado}</div>
        `;

        if (poolCard.dataset.turno) {
            html += `<div class="sv-card-personal__meta">Turno: ${poolCard.dataset.turno}</div>`;
        }

        poolCard.innerHTML = html;

        bindPoolCard(poolCard);

        return poolCard;
    }

    function bindPoolCard(card) {
        card.addEventListener('mousedown', function (e) {
            if (e.button !== 0) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            const newCard = makeCanvasCardFromPool(card, 'I', 50, 50);
            canvasCards.appendChild(newCard);
            card.remove();
            actualizarResumenMapa();

            startDrag(newCard, e.clientX, e.clientY);
            moveDrag(e.clientX, e.clientY);
        });
    }

    function bindCanvasCard(card) {
        renderCanvasCard(card);

        card.addEventListener('mousedown', function (e) {
            if (e.button !== 0) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            startDrag(card, e.clientX, e.clientY);
        });

        card.addEventListener('dblclick', function (e) {
            e.preventDefault();
            e.stopPropagation();

            readMembers(card).forEach(function (member) {
                pool.appendChild(makePoolCardFromMember(member));
            });
            card.remove();
            actualizarResumenMapa();
        });
    }

    document.addEventListener('mousemove', function (e) {
        moveDrag(e.clientX, e.clientY);
    });

    document.addEventListener('mouseup', function () {
        endDrag();
    });

    document.querySelectorAll('.sv-card-personal--pool').forEach(bindPoolCard);
    document.querySelectorAll('.sv-canvas-card').forEach(bindCanvasCard);
    actualizarResumenMapa();

    function esperarMosaicosMapa() {
        return new Promise(function (resolve) {
            if (!canvas.querySelector('.leaflet-tile-loading')) {
                resolve();
                return;
            }

            let terminado = false;
            const completar = function () {
                if (terminado) {
                    return;
                }

                terminado = true;
                tileLayer.off('load', completar);
                resolve();
            };

            tileLayer.once('load', completar);
            window.setTimeout(completar, 8000);
        });
    }

    async function descargarPdf() {
        const button = document.getElementById('btnDescargarSectorizacion');
        actualizarResumenMapa();

        if (!window.html2canvas || !window.jspdf || !window.jspdf.jsPDF) {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo generar el PDF',
                text: 'No se cargaron las herramientas de exportación. Recarga la página e inténtalo nuevamente.'
            });
            return;
        }

        button.disabled = true;
        Swal.fire({
            title: 'Generando PDF',
            text: 'Preparando el mapa con la vista actual...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false
        });
        Swal.showLoading();

        try {
            map.invalidateSize(false);
            await Promise.allSettled([
                libramientoReady,
                esperarMosaicosMapa()
            ]);
            canvas.classList.add('sv-exporting');

            await new Promise(function (resolve) {
                requestAnimationFrame(function () {
                    requestAnimationFrame(resolve);
                });
            });

            const escala = Math.max(1.35, Math.min(2, 2400 / Math.max(canvas.offsetWidth, 1)));
            const imagen = await html2canvas(canvas, {
                backgroundColor: '#ffffff',
                scale: escala,
                useCORS: true,
                allowTaint: false,
                logging: false,
                imageTimeout: 15000,
                foreignObjectRendering: false
            });

            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({
                orientation: 'landscape',
                unit: 'mm',
                format: 'a4',
                compress: true
            });
            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            const margin = 5;
            const availableWidth = pageWidth - (margin * 2);
            const availableHeight = pageHeight - (margin * 2);
            const ratio = Math.min(availableWidth / imagen.width, availableHeight / imagen.height);
            const imageWidth = imagen.width * ratio;
            const imageHeight = imagen.height * ratio;
            const imageX = (pageWidth - imageWidth) / 2;
            const imageY = (pageHeight - imageHeight) / 2;

            pdf.addImage(
                imagen.toDataURL('image/jpeg', 0.93),
                'JPEG',
                imageX,
                imageY,
                imageWidth,
                imageHeight,
                undefined,
                'FAST'
            );
            pdf.save('sectorizacion_' + fecha + '.pdf');
            Swal.close();
        } catch (error) {
            console.error(error);
            Swal.fire({
                icon: 'error',
                title: 'No se pudo generar el PDF',
                text: 'Verifica tu conexión para cargar el mapa completo y vuelve a intentarlo.'
            });
        } finally {
            canvas.classList.remove('sv-exporting');
            button.disabled = false;
        }
    }

    document.getElementById('btnDescargarSectorizacion').addEventListener('click', descargarPdf);

    if (autoDownloadPdf) {
        const cleanUrl = new URL(window.location.href);
        cleanUrl.searchParams.delete('descargar');
        window.history.replaceState({}, document.title, cleanUrl.toString());

        map.whenReady(function () {
            window.setTimeout(descargarPdf, 1800);
        });
    }

    document.getElementById('btnGuardarSectorizacion').addEventListener('click', function () {
        anchorCardsWithoutCoordinates();
        actualizarResumenMapa();

        const payload = {
            fecha: fecha,
            turno: turno,
            estado_fuerza: estadoFuerzaPayload,
            elementos: []
        };

        canvasCards.querySelectorAll('.sv-canvas-card').forEach(function (card) {
            readMembers(card).forEach(function (member) {
                payload.elementos.push({
                    personal_id: parseInt(member.id, 10),
                    sector: card.dataset.sector || '',
                    grupo: card.dataset.group || ('personal-' + member.id),
                    x: parseFloat(card.dataset.x || 0),
                    y: parseFloat(card.dataset.y || 0),
                    lat: parseFloat(card.dataset.lat || 0),
                    lng: parseFloat(card.dataset.lng || 0)
                });
            });
        });

        fetch(@json(route('settings.estadisticas_siniestros.sectorizaciones.guardar')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': @json(csrf_token()),
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(async function (response) {
            const data = await response.json().catch(function () {
                return {};
            });

            return {
                status: response.status,
                data: data
            };
        })
        .then(function (result) {
            if (result.status === 200 && result.data.ok) {
                Swal.fire({
                    icon: 'success',
                    title: 'Guardado',
                    text: result.data.message || 'Sectorización guardada correctamente.'
                });
                return;
            }

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.data.message || 'No se pudo guardar la sectorización.'
            });
        })
        .catch(function () {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo guardar la sectorización.'
            });
        });
    });
});
</script>
@stop
