@extends('adminlte::page')

@section('title', 'Control de envío INEGI')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h1 class="mb-1"><i class="fas fa-file-circle-check mr-2"></i>Control de envío INEGI</h1>
            <p class="inegi-muted mb-0">Vista previa y evidencia de los hechos de Delegaciones incluidos en el Excel oficial.</p>
        </div>
        <a href="{{ auth()->user()->can('ver configuraciones') ? route('settings.estadisticas_delegaciones.index') : route('home') }}" class="btn btn-secondary mt-2 mt-md-0">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="inegi-rule mb-3">
        <i class="fas fa-circle-info"></i>
        <div>
            <strong>Este es el mismo criterio del Excel que se envía por correo.</strong>
            Un hecho de Delegaciones entra cuando su captura está marcada como completa o cuando vehículos,
            conductores y lesionados capturados cubren las cantidades esperadas.
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-md-6">
            <a class="inegi-stat {{ $seccion === 'enviados' ? 'is-active' : '' }}"
               href="{{ route('estadisticas_delegaciones.control_inegi', array_merge(request()->except(['seccion', 'pagina_enviados', 'pagina_proximos', 'pagina_pendientes']), ['seccion' => 'enviados'])) }}">
                <span class="inegi-stat__icon bg-primary"><i class="fas fa-paper-plane"></i></span>
                <span>
                    <small>Delegaciones del envío seleccionado</small>
                    <strong>{{ number_format($resumen['enviados_delegaciones']) }}</strong>
                </span>
            </a>
        </div>
        <div class="col-lg-4 col-md-6">
            <a class="inegi-stat {{ $seccion === 'proximos' ? 'is-active' : '' }}"
               href="{{ route('estadisticas_delegaciones.control_inegi', array_merge(request()->except(['seccion', 'pagina_enviados', 'pagina_proximos', 'pagina_pendientes']), ['seccion' => 'proximos'])) }}">
                <span class="inegi-stat__icon bg-success"><i class="fas fa-calendar-check"></i></span>
                <span>
                    <small>Contemplados para el próximo envío</small>
                    <strong>{{ number_format($resumen['proximos']) }}</strong>
                </span>
            </a>
        </div>
        <div class="col-lg-4 col-md-6">
            <a class="inegi-stat {{ $seccion === 'pendientes' ? 'is-active' : '' }}"
               href="{{ route('estadisticas_delegaciones.control_inegi', array_merge(request()->except(['seccion', 'pagina_enviados', 'pagina_proximos', 'pagina_pendientes']), ['seccion' => 'pendientes'])) }}">
                <span class="inegi-stat__icon bg-warning"><i class="fas fa-triangle-exclamation"></i></span>
                <span>
                    <small>Incompletos que todavía no entrarían</small>
                    <strong>{{ number_format($resumen['pendientes']) }}</strong>
                </span>
            </a>
        </div>
    </div>

    <div class="card inegi-card">
        <div class="card-body">
            <form method="GET" action="{{ route('estadisticas_delegaciones.control_inegi') }}" class="row align-items-end">
                <input type="hidden" name="seccion" value="{{ $seccion }}">
                <div class="col-lg-5 col-md-6 mb-2">
                    <label for="envio_id">Envío histórico</label>
                    <select name="envio_id" id="envio_id" class="form-control">
                        @if($envios->isEmpty())
                            <option value="">Último mes (reconstruido)</option>
                        @else
                            @foreach($envios as $envio)
                                <option value="{{ $envio->id }}" {{ optional($envioSeleccionado)->id === $envio->id ? 'selected' : '' }}>
                                    {{ $envio->fecha_inicio->format('d/m/Y') }} al {{ $envio->fecha_fin->format('d/m/Y') }} · enviado {{ optional($envio->enviado_at)->format('d/m/Y H:i') }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-lg-5 col-md-6 mb-2">
                    <label for="buscar">Buscar hecho</label>
                    <input type="search" name="buscar" id="buscar" class="form-control" value="{{ $buscar }}"
                           placeholder="ID, folio, delegación, municipio o tipo">
                </div>
                <div class="col-lg-2 mb-2 d-flex">
                    <button class="btn btn-primary flex-fill" type="submit"><i class="fas fa-search mr-1"></i> Consultar</button>
                    @if($buscar !== '')
                        <a href="{{ route('estadisticas_delegaciones.control_inegi', ['seccion' => $seccion, 'envio_id' => optional($envioSeleccionado)->id]) }}"
                           class="btn btn-secondary ml-1" title="Limpiar búsqueda"><i class="fas fa-eraser"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($seccion === 'enviados')
        <div class="card inegi-card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h3 class="card-title font-weight-bold">Hechos del envío: {{ $enviadosDesde->format('d/m/Y') }} al {{ $enviadosHasta->format('d/m/Y') }}</h3>
                    <div class="clearfix"></div>
                    @if($evidenciaExacta)
                        <span class="badge badge-success mt-2"><i class="fas fa-shield-check mr-1"></i>Evidencia exacta guardada al enviar</span>
                        <span class="inegi-muted ml-2">{{ $envioSeleccionado->archivo_nombre }}</span>
                    @else
                        <span class="badge badge-warning mt-2">Periodo reconstruido</span>
                        <span class="inegi-muted ml-2">Los envíos anteriores no guardaban manifiesto; la lista se recalculó con la regla actual.</span>
                    @endif
                </div>
                @if($resumen['enviados_total_archivo'] !== null)
                    <div class="inegi-total-file">
                        <small>Total general del archivo</small>
                        <strong>{{ number_format($resumen['enviados_total_archivo']) }}</strong>
                        <span>incluye otras áreas</span>
                    </div>
                @endif
            </div>
            @include('admin.settings.estadisticas_delegaciones.control_inegi._tabla', ['registros' => $enviados, 'mostrarFaltantes' => false])
        </div>
    @elseif($seccion === 'pendientes')
        <div class="card inegi-card">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">No entrarían todavía: {{ $proximoDesde->format('d/m/Y') }} al {{ $proximoHasta->format('d/m/Y') }}</h3>
                <div class="clearfix"></div>
                <span class="inegi-muted">Completa las cantidades pendientes antes del cierre mensual para que se incorporen al envío.</span>
            </div>
            @include('admin.settings.estadisticas_delegaciones.control_inegi._tabla', ['registros' => $pendientes, 'mostrarFaltantes' => true])
        </div>
    @else
        <div class="card inegi-card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h3 class="card-title font-weight-bold">Próximo Excel: {{ $proximoDesde->format('d/m/Y') }} al {{ $proximoHasta->format('d/m/Y') }}</h3>
                    <div class="clearfix"></div>
                    <span class="inegi-muted">Vista previa actual; se actualiza automáticamente cuando cambia la captura.</span>
                </div>
                <div class="inegi-next-send">
                    <i class="fas fa-clock mr-1"></i>
                    Envío programado: <strong>{{ $proximoEnvioAt->format('d/m/Y H:i') }} h</strong>
                </div>
            </div>
            @include('admin.settings.estadisticas_delegaciones.control_inegi._tabla', ['registros' => $proximos, 'mostrarFaltantes' => false])
        </div>
    @endif
@stop

@section('css')
<style>
    .inegi-muted { color: rgba(232, 239, 255, .66); font-size: .86rem; }
    .inegi-rule { display:flex; gap:12px; padding:14px 16px; border-radius:14px; color:#eaf2ff; background:rgba(23, 162, 184, .16); border:1px solid rgba(23, 162, 184, .42); }
    .inegi-rule > i { margin-top:3px; color:#53d7ef; font-size:1.15rem; }
    .inegi-stat { display:flex; align-items:center; gap:14px; min-height:92px; margin-bottom:16px; padding:15px; border-radius:16px; color:#edf4ff; background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); box-shadow:0 10px 28px rgba(0,0,0,.18); transition:.16s ease; }
    .inegi-stat:hover, .inegi-stat.is-active { color:#fff; text-decoration:none; transform:translateY(-2px); border-color:rgba(66, 165, 245, .62); background:rgba(66, 165, 245, .12); }
    .inegi-stat__icon { width:52px; height:52px; flex:0 0 52px; display:grid; place-items:center; border-radius:15px; font-size:1.2rem; }
    .inegi-stat small, .inegi-stat strong { display:block; }
    .inegi-stat small { color:rgba(232,239,255,.7); font-weight:700; }
    .inegi-stat strong { font-size:1.8rem; line-height:1.05; margin-top:4px; }
    .inegi-card { border-radius:16px; overflow:hidden; background:rgba(20,31,51,.9); border:1px solid rgba(255,255,255,.12); box-shadow:0 14px 35px rgba(0,0,0,.2); }
    .inegi-card .card-header { background:rgba(255,255,255,.045); border-bottom:1px solid rgba(255,255,255,.11); }
    .inegi-table { color:#edf4ff; }
    .inegi-table thead th { border-top:0; border-bottom:1px solid rgba(255,255,255,.16); background:rgba(255,255,255,.055); color:#bcd6ff; font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; }
    .inegi-table td { border-top:1px solid rgba(255,255,255,.08); vertical-align:middle; }
    .inegi-table tbody tr:hover { color:#fff; background:rgba(66,165,245,.08); }
    .inegi-link { color:#66b5ff; font-weight:800; }
    .inegi-missing { color:#ffd27a; font-size:.82rem; margin-top:5px; }
    .inegi-pagination { padding:14px 16px 6px; border-top:1px solid rgba(255,255,255,.08); }
    .inegi-total-file, .inegi-next-send { padding:9px 12px; border-radius:12px; background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.1); }
    .inegi-total-file small, .inegi-total-file strong, .inegi-total-file span { display:block; text-align:right; }
    .inegi-total-file strong { font-size:1.35rem; }
    .inegi-total-file span { color:rgba(232,239,255,.62); font-size:.72rem; }
    @media (max-width: 767px) {
        .inegi-total-file, .inegi-next-send { width:100%; margin-top:12px; }
        .inegi-total-file small, .inegi-total-file strong, .inegi-total-file span { text-align:left; }
    }
</style>
@stop
