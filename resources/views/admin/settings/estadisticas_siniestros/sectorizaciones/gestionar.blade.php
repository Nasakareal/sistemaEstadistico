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
        $ef = $asignaciones['estado_fuerza'] ?? [];

        $cards = collect($personal)->map(function ($item) use ($guardadosById) {
            $saved = $guardadosById->get((int) $item['id']);

            return [
                'id' => (int) $item['id'],
                'nombre_completo' => $item['nombre_completo'],
                'patrulla' => $item['patrulla'] ?: 'Sin patrulla',
                'estado_fuerza' => $item['estado_fuerza'],
                'turno' => $item['turno'] ?? '',
                'sector' => $saved['sector'] ?? null,
                'x' => $saved['x'] ?? null,
                'y' => $saved['y'] ?? null,
            ];
        })->values();

        $libres = $cards->filter(fn ($c) => empty($c['sector']))->values();
        $enMapa = $cards->filter(fn ($c) => !empty($c['sector']))->values();
        $turnoTexto = $asignaciones['turno'] ?? ($turnoActivo->nombre ?? '');
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

                        <a href="{{ route('settings.estadisticas_siniestros.sectorizaciones.descargar', $fecha) }}" class="btn sv-btn">
                            <i class="fas fa-download"></i> Descargar
                        </a>

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
                                <div>UNIDAD DE ATENCIÓN A SINIESTROS</div>
                                <div>SECTORIZACIÓN DE UNIDADES</div>
                                <div>TURNO <span id="tituloTurno">{{ $turnoTexto }}</span></div>
                                <div>{{ \Carbon\Carbon::parse($fecha)->translatedFormat('d \d\e F \d\e Y') }}</div>
                            </div>

                            <div class="sv-state-box">
                                <div class="sv-state-box__title">ESTADO DE FUERZA</div>
                                <div class="sv-state-line"><span>ESTADO DE FUERZA:</span><strong>{{ $ef['estado_fuerza'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>LABORANDO:</span><strong>{{ $ef['laborando'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>CMDT DE TURNO:</span><strong>{{ $ef['cmdt_turno'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>BASE RADIO:</span><strong>{{ $ef['base_radio'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>ELEMENTOS EN RECORRIDO:</span><strong>{{ $ef['elementos_recorrido'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>MÓDULO:</span><strong>{{ $ef['modulo'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>CURSO:</span><strong>{{ $ef['curso'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>PERMISO:</span><strong>{{ $ef['permiso'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>INCAPACIDAD:</span><strong>{{ $ef['incapacidad'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>FRANCOS:</span><strong>{{ $ef['francos'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>VACACIONES:</span><strong>{{ $ef['vacaciones'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>FALTANDO:</span><strong>{{ $ef['faltando'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>COMISIONADOS:</span><strong>{{ $ef['comisionados'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>CRP:</span><strong>{{ $ef['crp'] ?? '' }}</strong></div>
                                <div class="sv-state-line"><span>MOTOS:</span><strong>{{ $ef['motos'] ?? '' }}</strong></div>
                            </div>

                            <div class="sv-legend-box">
                                <div class="sv-legend-box__title">SECTORES</div>
                                <div class="sv-legend-line"><span>1.- Revolución</span><i style="background:#f2a9c2"></i></div>
                                <div class="sv-legend-line"><span>2.- Nueva España</span><i style="background:#8fd3f4"></i></div>
                                <div class="sv-legend-line"><span>3.- Independencia</span><i style="background:#eadfa6"></i></div>
                                <div class="sv-legend-line"><span>4.- República</span><i style="background:#b9ef3a"></i></div>
                            </div>

                            <svg class="sv-map-svg" viewBox="0 0 1400 900" preserveAspectRatio="none">
                                <polygon points="740,120 930,120 1130,310 1245,455 1175,635 1025,750 760,760 740,560" fill="#8fd3f4" stroke="#111" stroke-width="4"></polygon>
                                <polygon points="740,120 915,115 1080,300 1230,495 1240,615 980,585 835,450 740,450" fill="#f2a9c2" stroke="#111" stroke-width="4"></polygon>
                                <polygon points="110,410 455,330 520,430 740,430 740,700 555,845 225,780 145,645" fill="#eadfa6" stroke="#111" stroke-width="4"></polygon>
                                <polygon points="250,170 505,120 740,120 740,450 520,440 415,360 170,400 145,260" fill="#b9ef3a" stroke="#111" stroke-width="4"></polygon>

                                <line x1="760" y1="0" x2="690" y2="900" stroke="#111" stroke-width="4"></line>
                                <line x1="80" y1="520" x2="1290" y2="360" stroke="#111" stroke-width="4"></line>
                                <line x1="350" y1="210" x2="460" y2="480" stroke="#111" stroke-width="4"></line>
                                <line x1="545" y1="435" x2="470" y2="785" stroke="#111" stroke-width="4"></line>

                                <text x="1035" y="265" class="sv-svg-sector">SECTOR REVOLUCIÓN</text>
                                <text x="875" y="730" class="sv-svg-sector">SECTOR</text>
                                <text x="842" y="768" class="sv-svg-sector">NUEVA ESPAÑA</text>
                                <text x="200" y="720" class="sv-svg-sector">SECTOR INDEPENDENCIA</text>
                                <text x="410" y="120" class="sv-svg-sector">SECTOR REPÚBLICA</text>

                                <text x="290" y="520" class="sv-svg-road">PERIFÉRICO PASEO DE LA REPÚBLICA</text>
                                <text x="870" y="795" class="sv-svg-road">AV. CAMELINAS</text>
                                <text x="300" y="382" class="sv-svg-small">28</text>
                                <text x="756" y="315" class="sv-svg-small">4</text>
                                <text x="1160" y="402" class="sv-svg-small">5</text>
                                <text x="1180" y="620" class="sv-svg-small">1</text>
                                <text x="525" y="840" class="sv-svg-small">2</text>
                                <text x="120" y="450" class="sv-svg-small">3</text>
                            </svg>

                            <div class="sv-canvas-cards" id="canvasCards">
                                @foreach($enMapa as $item)
                                    @php
                                        $sectorActual = $item['sector'] ?? 'I';
                                        $sx = $item['x'] ?? ($defaultPositions[$sectorActual]['x'] ?? 50);
                                        $sy = $item['y'] ?? ($defaultPositions[$sectorActual]['y'] ?? 50);
                                        $color = $sectorMeta[$sectorActual]['color'] ?? '#ffffff';
                                    @endphp
                                    <div class="sv-canvas-card"
                                         data-id="{{ $item['id'] }}"
                                         data-sector="{{ $sectorActual }}"
                                         data-x="{{ $sx }}"
                                         data-y="{{ $sy }}"
                                         data-nombre="{{ $item['nombre_completo'] }}"
                                         data-patrulla="{{ $item['patrulla'] }}"
                                         data-estado="{{ $item['estado_fuerza'] }}"
                                         data-turno="{{ $item['turno'] }}"
                                         style="left:{{ $sx }}%; top:{{ $sy }}%; --sector-color: {{ $color }};">
                                        <div class="sv-canvas-card__grip"><i class="fas fa-arrows-alt"></i></div>
                                        <div class="sv-canvas-card__body">
                                            <div class="sv-canvas-card__name">{{ $item['patrulla'] }}</div>
                                            <div class="sv-canvas-card__person">{{ $item['nombre_completo'] }}</div>
                                        </div>
                                        <div class="sv-canvas-card__sector">{{ $sectorActual }}</div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="sv-sector-hints">
                                <div class="sv-sector-hint hint-i" data-sector="I" data-color="#f2a9c2">I</div>
                                <div class="sv-sector-hint hint-ii" data-sector="II" data-color="#8fd3f4">II</div>
                                <div class="sv-sector-hint hint-iii" data-sector="III" data-color="#eadfa6">III</div>
                                <div class="sv-sector-hint hint-iv" data-sector="IV" data-color="#b9ef3a">IV</div>
                            </div>
                        </div>
                    </div>

                    <div class="sv-help">
                        Arrastra libremente dentro del mapa. Doble clic sobre una tarjeta del mapa para regresarla a disponibles.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" id="fechaSectorizacion" value="{{ $fecha }}">
    <input type="hidden" id="turnoSectorizacion" value="{{ $turnoTexto }}">
@stop

@section('css')
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
        overflow:auto;
    }

    .sv-canvas{
        position:relative;
        width:1400px;
        height:900px;
        border-radius:28px;
        border:1px solid rgba(255,255,255,.12);
        background:#ededed;
        overflow:hidden;
        box-shadow:0 22px 60px rgba(0,0,0,.28);
    }

    .sv-map-svg{
        position:absolute;
        inset:0;
        width:100%;
        height:100%;
    }

    .sv-svg-sector{
        font-size:22px;
        font-weight:700;
        fill:#1fa1ff;
        font-family:"Times New Roman", serif;
        text-decoration:underline;
    }

    .sv-svg-road{
        font-size:18px;
        font-weight:700;
        fill:#1fa1ff;
        font-family:"Times New Roman", serif;
    }

    .sv-svg-small{
        font-size:18px;
        font-weight:700;
        fill:#111;
        font-family:"Times New Roman", serif;
    }

    .sv-title-box,
    .sv-state-box,
    .sv-legend-box{
        position:absolute;
        background:rgba(255,255,255,.96);
        border:3px solid #222;
        box-shadow:0 8px 25px rgba(0,0,0,.12);
        z-index:2;
    }

    .sv-title-box{
        left:18px;
        top:28px;
        width:305px;
        text-align:center;
        padding:12px 10px;
        font-family:"Times New Roman", serif;
        font-weight:700;
        font-size:21px;
        line-height:1.15;
    }

    .sv-title-box div + div{
        margin-top:6px;
    }

    .sv-state-box{
        right:24px;
        bottom:32px;
        width:280px;
        padding:12px 14px;
        font-family:"Times New Roman", serif;
    }

    .sv-state-box__title{
        font-size:22px;
        font-weight:700;
        text-align:center;
        text-decoration:underline;
        margin-bottom:10px;
    }

    .sv-state-line{
        display:flex;
        justify-content:space-between;
        gap:10px;
        font-size:14px;
        line-height:1.35;
        font-weight:700;
    }

    .sv-state-line strong{
        font-weight:700;
    }

    .sv-legend-box{
        left:40px;
        bottom:34px;
        width:200px;
        padding:12px 14px;
        font-family:"Times New Roman", serif;
    }

    .sv-legend-box__title{
        text-align:center;
        font-size:20px;
        font-weight:700;
        text-decoration:underline;
        margin-bottom:12px;
    }

    .sv-legend-line{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        font-size:15px;
        margin-bottom:8px;
    }

    .sv-legend-line i{
        width:18px;
        height:18px;
        border:1px solid #555;
        display:inline-block;
    }

    .sv-sector-hints .sv-sector-hint{
        position:absolute;
        z-index:2;
        width:34px;
        height:34px;
        border-radius:999px;
        display:grid;
        place-items:center;
        font-weight:900;
        color:#111;
        border:2px solid rgba(0,0,0,.35);
        box-shadow:0 8px 18px rgba(0,0,0,.18);
        cursor:pointer;
    }

    .hint-i{
        top:325px;
        right:590px;
        background:#f2a9c2;
    }

    .hint-ii{
        bottom:170px;
        left:500px;
        background:#8fd3f4;
    }

    .hint-iii{
        top:445px;
        left:108px;
        background:#eadfa6;
    }

    .hint-iv{
        top:292px;
        left:738px;
        background:#b9ef3a;
    }

    .sv-canvas-cards{
        position:absolute;
        inset:0;
        z-index:4;
    }

    .sv-canvas-card{
        position:absolute;
        min-width:140px;
        max-width:190px;
        border:3px dashed #222;
        background:color-mix(in srgb, var(--sector-color) 78%, white 22%);
        box-shadow:0 10px 24px rgba(0,0,0,.16);
        transform:translate(-50%, -50%);
        cursor:grab;
        user-select:none;
    }

    .sv-canvas-card__grip{
        position:absolute;
        top:-12px;
        right:-12px;
        width:28px;
        height:28px;
        border-radius:999px;
        display:grid;
        place-items:center;
        background:#111;
        color:#fff;
        font-size:12px;
        box-shadow:0 6px 12px rgba(0,0,0,.18);
    }

    .sv-canvas-card__body{
        padding:14px 12px 10px 12px;
        text-align:center;
        font-family:"Times New Roman", serif;
        color:#111;
    }

    .sv-canvas-card__name{
        font-size:18px;
        font-weight:700;
        line-height:1.05;
    }

    .sv-canvas-card__person{
        margin-top:6px;
        font-size:17px;
        line-height:1.08;
        font-weight:700;
        text-transform:uppercase;
        word-break:break-word;
    }

    .sv-canvas-card__sector{
        position:absolute;
        left:6px;
        top:6px;
        padding:1px 6px;
        border-radius:999px;
        font-size:11px;
        font-weight:900;
        background:rgba(0,0,0,.12);
        color:#111;
    }

    .sv-help{
        margin-top:12px;
        color:var(--sv-muted);
        font-size:12px;
        font-weight:800;
    }

    @media (max-width: 1199.98px){
        .sv-canvas{
            width:1400px;
        }
    }
</style>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('sectorCanvas');
    const canvasCards = document.getElementById('canvasCards');
    const pool = document.getElementById('pool-personal');
    const fecha = document.getElementById('fechaSectorizacion').value;
    const turno = document.getElementById('turnoSectorizacion').value || '';
    let active = null;
    let offsetX = 0;
    let offsetY = 0;

    const estadoFuerzaPayload = @json($ef);

    const sectorPolygons = {
        I: [[740,120],[915,115],[1080,300],[1230,495],[1240,615],[980,585],[835,450],[740,450]],
        II: [[740,450],[980,585],[1175,635],[1025,750],[760,760],[740,700]],
        III: [[110,410],[455,330],[520,430],[740,430],[740,700],[555,845],[225,780],[145,645]],
        IV: [[250,170],[505,120],[740,120],[740,450],[520,440],[415,360],[170,400],[145,260]]
    };

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

    function detectSector(percentX, percentY) {
        const x = (percentX / 100) * 1400;
        const y = (percentY / 100) * 900;

        for (const sector in sectorPolygons) {
            if (pointInPolygon([x, y], sectorPolygons[sector])) {
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
        const sector = detectSector(px, py);

        active.style.left = px + '%';
        active.style.top = py + '%';
        active.dataset.x = px.toFixed(2);
        active.dataset.y = py.toFixed(2);
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

        active.classList.remove('dragging');
        active = null;
    }

    function makeCanvasCardFromPool(poolCard, sector, x, y) {
        const card = document.createElement('div');
        const nombre = poolCard.dataset.nombre || '';
        const patrulla = poolCard.dataset.patrulla || 'Sin patrulla';
        const estado = poolCard.dataset.estado || '';
        const turnoCard = poolCard.dataset.turno || '';

        card.className = 'sv-canvas-card';
        card.dataset.id = poolCard.dataset.id;
        card.dataset.sector = sector;
        card.dataset.x = x;
        card.dataset.y = y;
        card.dataset.nombre = nombre;
        card.dataset.patrulla = patrulla;
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

    function makePoolCardFromCanvas(card) {
        const poolCard = document.createElement('div');

        poolCard.className = 'sv-card-personal sv-card-personal--pool';
        poolCard.dataset.id = card.dataset.id || '';
        poolCard.dataset.sector = '';
        poolCard.dataset.x = '';
        poolCard.dataset.y = '';
        poolCard.dataset.nombre = card.dataset.nombre || '';
        poolCard.dataset.patrulla = card.dataset.patrulla || 'Sin patrulla';
        poolCard.dataset.estado = card.dataset.estado || 'EN_SERVICIO';
        poolCard.dataset.turno = card.dataset.turno || '';

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

            const newCard = makeCanvasCardFromPool(card, 'I', 50, 50);
            canvasCards.appendChild(newCard);
            card.remove();

            startDrag(newCard, e.clientX, e.clientY);
            moveDrag(e.clientX, e.clientY);
        });
    }

    function bindCanvasCard(card) {
        card.addEventListener('mousedown', function (e) {
            if (e.button !== 0) {
                return;
            }

            startDrag(card, e.clientX, e.clientY);
        });

        card.addEventListener('dblclick', function () {
            const newPoolCard = makePoolCardFromCanvas(card);
            pool.appendChild(newPoolCard);
            card.remove();
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

    document.querySelectorAll('.sv-sector-hint').forEach(function (hint) {
        hint.addEventListener('click', function () {
            const card = canvasCards.querySelector('.sv-canvas-card.dragging');
            if (!card) {
                return;
            }

            const sector = hint.dataset.sector || '';
            const color = hint.dataset.color || sectorColor(sector);

            card.dataset.sector = sector;
            card.style.setProperty('--sector-color', color);

            const badge = card.querySelector('.sv-canvas-card__sector');
            if (badge) {
                badge.textContent = sector || '?';
            }
        });
    });

    document.getElementById('btnGuardarSectorizacion').addEventListener('click', function () {
        const payload = {
            fecha: fecha,
            turno: turno,
            estado_fuerza: estadoFuerzaPayload,
            elementos: []
        };

        canvasCards.querySelectorAll('.sv-canvas-card').forEach(function (card) {
            payload.elementos.push({
                personal_id: parseInt(card.dataset.id, 10),
                sector: card.dataset.sector || '',
                x: parseFloat(card.dataset.x || 0),
                y: parseFloat(card.dataset.y || 0)
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
