@extends('adminlte::page')

@section('title', 'Vehículos · Conduce con Legalidad')

@section('content_header')
    <div class="cl-hero">
        <div class="cl-hero__content">
            <div class="cl-hero__eyebrow"><span></span> Operativos · Conduce con Legalidad</div>
            <h1>Vehículos registrados</h1>
            <p>Consulta el padrón, aplica filtros y descarga exactamente los resultados visibles en Excel.</p>
        </div>
        <div class="cl-hero__actions">
            @can('ver estadisticas globales')
                <a href="{{ route('estadisticas_globales.index') }}" class="btn cl-btn cl-btn--ghost">
                    <i class="fa-solid fa-chart-line"></i> Estadísticas globales
                </a>
            @endcan
            @if(auth()->user()->hasRole('Superadmin') && auth()->user()->can('ver estadisticas'))
                <a href="{{ route('estadisticas.index') }}" class="btn cl-btn cl-btn--ghost">
                    <i class="fa-solid fa-gear"></i> Configuración estadística
                </a>
            @endif
            <a href="{{ route('reportes.conduce_legalidad.vehiculos.excel', request()->except('page')) }}" class="btn cl-btn cl-btn--excel">
                <i class="fa-solid fa-file-excel"></i> Descargar Excel
            </a>
        </div>
    </div>
@stop

@section('content')
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Revisa los filtros:</strong> {{ $errors->first() }}
        </div>
    @endif

    <div class="row cl-kpis">
        <div class="col-xl-3 col-sm-6 mb-3">
            <div class="cl-kpi cl-kpi--emerald">
                <div class="cl-kpi__icon"><i class="fa-solid fa-motorcycle"></i></div>
                <div><span>Vehículos</span><strong>{{ number_format($resumen['vehiculos'] ?? 0) }}</strong><small>Con los filtros actuales</small></div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3">
            <div class="cl-kpi cl-kpi--blue">
                <div class="cl-kpi__icon"><i class="fa-solid fa-road-barrier"></i></div>
                <div><span>Operativos</span><strong>{{ number_format($resumen['operativos'] ?? 0) }}</strong><small>Uno por fecha y municipio</small></div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3">
            <div class="cl-kpi cl-kpi--violet">
                <div class="cl-kpi__icon"><i class="fa-solid fa-map-location-dot"></i></div>
                <div><span>Municipios</span><strong>{{ number_format($resumen['municipios'] ?? 0) }}</strong><small>Cobertura del resultado</small></div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3">
            <div class="cl-kpi cl-kpi--amber">
                <div class="cl-kpi__icon"><i class="fa-solid fa-warehouse"></i></div>
                <div><span>Retenidos</span><strong>{{ number_format($resumen['retenidos'] ?? 0) }}</strong><small>Marcados para retención</small></div>
            </div>
        </div>
    </div>

    <div class="card cl-panel mb-4">
        <div class="card-header cl-panel__header">
            <div>
                <span class="cl-panel__kicker">Consulta</span>
                <h3 class="card-title">Filtros del reporte</h3>
            </div>
            <button class="btn cl-filter-toggle d-lg-none" type="button" data-toggle="collapse" data-target="#clFilters" aria-expanded="false">
                <i class="fa-solid fa-sliders"></i> Mostrar filtros
            </button>
        </div>
        <div id="clFilters" class="collapse d-lg-block">
            <div class="card-body">
                <form method="GET" action="{{ route('reportes.conduce_legalidad.vehiculos') }}">
                    <div class="row">
                        <div class="col-lg-4 col-md-6 mb-3">
                            <label for="q">Búsqueda general</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span></div>
                                <input id="q" name="q" class="form-control" value="{{ request('q') }}" placeholder="Lugar, operativo, marca, placas, serie…">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3 col-6 mb-3">
                            <label for="desde">Desde</label>
                            <input id="desde" name="desde" type="date" class="form-control" value="{{ request('desde') }}">
                        </div>
                        <div class="col-lg-2 col-md-3 col-6 mb-3">
                            <label for="hasta">Hasta</label>
                            <input id="hasta" name="hasta" type="date" class="form-control" value="{{ request('hasta') }}">
                        </div>
                        <div class="col-lg-2 col-md-6 mb-3">
                            <label for="municipio">Municipio</label>
                            <select id="municipio" name="municipio" class="form-control">
                                <option value="">Todos</option>
                                @foreach($opciones['municipios'] ?? [] as $opcion)
                                    <option value="{{ $opcion }}" @selected(request('municipio') === $opcion)>{{ $opcion }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6 mb-3">
                            <label for="retenido">Retención</label>
                            <select id="retenido" name="retenido" class="form-control">
                                <option value="">Todos</option>
                                <option value="1" @selected(request('retenido') === '1')>Solo retenidos</option>
                                <option value="0" @selected(request('retenido') === '0')>No retenidos</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <label for="marca">Marca</label>
                            <select id="marca" name="marca" class="form-control">
                                <option value="">Todas</option>
                                @foreach($opciones['marcas'] ?? [] as $opcion)
                                    <option value="{{ $opcion }}" @selected(request('marca') === $opcion)>{{ $opcion }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <label for="tipo">Tipo</label>
                            <select id="tipo" name="tipo" class="form-control">
                                <option value="">Todos</option>
                                @foreach($opciones['tipos'] ?? [] as $opcion)
                                    <option value="{{ $opcion }}" @selected(request('tipo') === $opcion)>{{ $opcion }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <label for="color">Color</label>
                            <select id="color" name="color" class="form-control">
                                <option value="">Todos</option>
                                @foreach($opciones['colores'] ?? [] as $opcion)
                                    <option value="{{ $opcion }}" @selected(request('color') === $opcion)>{{ $opcion }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <label for="linea">Línea</label>
                            <input id="linea" name="linea" class="form-control" value="{{ request('linea') }}" placeholder="Ej. FT150">
                        </div>
                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <label for="modelo">Modelo</label>
                            <input id="modelo" name="modelo" class="form-control" value="{{ request('modelo') }}" placeholder="Ej. 2026">
                        </div>
                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <label for="placas">Placas</label>
                            <input id="placas" name="placas" class="form-control text-uppercase" value="{{ request('placas') }}" placeholder="Ej. 39PNK6">
                        </div>

                        <div class="col-lg-3 col-md-4 mb-3">
                            <label for="serie">Serie</label>
                            <input id="serie" name="serie" class="form-control text-uppercase" value="{{ request('serie') }}" placeholder="Número de serie">
                        </div>
                        <div class="col-lg-2 col-md-4 mb-3">
                            <label for="inventario">No. de inventario</label>
                            <input id="inventario" name="inventario" class="form-control" value="{{ request('inventario') }}" placeholder="Ej. 0863">
                        </div>
                        <div class="col-lg-4 col-md-4 mb-3">
                            <label for="motivo">Motivo o fundamento</label>
                            <input id="motivo" name="motivo" class="form-control" value="{{ request('motivo') }}" placeholder="Artículo, fracción o texto del motivo">
                        </div>
                        <div class="col-lg-3 d-flex align-items-end mb-3 cl-filter-actions">
                            <button class="btn cl-btn cl-btn--primary flex-fill" type="submit"><i class="fa-solid fa-filter"></i> Aplicar</button>
                            <a class="btn cl-btn cl-btn--clear" href="{{ route('reportes.conduce_legalidad.vehiculos') }}" title="Limpiar filtros"><i class="fa-solid fa-rotate-left"></i></a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card cl-panel cl-results">
        <div class="card-header cl-panel__header cl-results__header">
            <div>
                <span class="cl-panel__kicker">Resultados</span>
                <h3 class="card-title">{{ number_format($vehiculos->total()) }} vehículos encontrados</h3>
            </div>
            <div class="cl-results__tools">
                <form method="GET" action="{{ route('reportes.conduce_legalidad.vehiculos') }}" class="d-flex align-items-center">
                    @foreach(request()->except(['page', 'per_page']) as $key => $value)
                        @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <label for="per_page" class="mb-0 mr-2">Mostrar</label>
                    <select id="per_page" name="per_page" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach([25, 50, 100] as $cantidad)
                            <option value="{{ $cantidad }}" @selected((int) request('per_page', 25) === $cantidad)>{{ $cantidad }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('reportes.conduce_legalidad.vehiculos.excel', request()->except('page')) }}" class="btn cl-btn cl-btn--excel cl-btn--small">
                    <i class="fa-solid fa-download"></i> Excel filtrado
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table cl-table mb-0">
                    <thead>
                        <tr>
                            <th class="cl-date-col">Fecha</th>
                            <th class="cl-place-col">Lugar</th>
                            <th>Datos del vehículo</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehiculos as $vehiculo)
                            <tr>
                                <td data-label="Fecha">
                                    <div class="cl-date">
                                        <i class="fa-regular fa-calendar"></i>
                                        <strong>{{ $vehiculo->fecha_reporte ? \Carbon\Carbon::parse($vehiculo->fecha_reporte)->format('d/m/Y') : 'Sin fecha' }}</strong>
                                    </div>
                                    <span class="cl-folio">Operativo #{{ $vehiculo->operativo_id }}</span>
                                </td>
                                <td data-label="Lugar">
                                    <div class="cl-place"><i class="fa-solid fa-location-dot"></i><span>{{ $vehiculo->lugar_reporte }}</span></div>
                                </td>
                                <td data-label="Datos del vehículo">
                                    <div class="cl-vehicle-head">
                                        <span class="cl-vehicle-icon"><i class="fa-solid fa-motorcycle"></i></span>
                                        <div>
                                            <strong>{{ collect([$vehiculo->marca, $vehiculo->linea])->filter()->implode(' · ') ?: 'Vehículo sin marca o línea' }}</strong>
                                            <small>{{ collect([$vehiculo->modelo, $vehiculo->color])->filter()->implode(' · ') }}</small>
                                        </div>
                                    </div>
                                    <div class="cl-tags">
                                        @if($vehiculo->placas)<span><i class="fa-regular fa-id-card"></i> {{ $vehiculo->placas }}</span>@endif
                                        @if($vehiculo->numero_inventario)<span><i class="fa-solid fa-hashtag"></i> Inv. {{ $vehiculo->numero_inventario }}</span>@endif
                                        @if($vehiculo->serie)<span title="{{ $vehiculo->serie }}"><i class="fa-solid fa-barcode"></i> {{ \Illuminate\Support\Str::limit($vehiculo->serie, 20) }}</span>@endif
                                        @if($vehiculo->retencion_vehiculo)<span class="cl-tag--retained"><i class="fa-solid fa-warehouse"></i> Retenido</span>@endif
                                    </div>
                                    <details class="cl-details"><summary>Ver ficha completa</summary><p>{{ $vehiculo->datos_vehiculo ?: 'Sin datos adicionales' }}</p></details>
                                </td>
                                <td data-label="Motivo"><div class="cl-reason">{{ $vehiculo->motivo_reporte }}</div></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="cl-empty"><i class="fa-solid fa-filter-circle-xmark"></i><h4>Sin resultados</h4><p>No hay vehículos que coincidan con los filtros seleccionados.</p><a href="{{ route('reportes.conduce_legalidad.vehiculos') }}" class="btn cl-btn cl-btn--primary">Limpiar filtros</a></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($vehiculos->hasPages())
            <div class="card-footer cl-pagination">{{ $vehiculos->links() }}</div>
        @endif
    </div>
@stop

@section('css')
<style>
    .cl-hero { position:relative; overflow:hidden; display:flex; align-items:center; justify-content:space-between; gap:24px; padding:26px 30px; border:1px solid rgba(45,212,191,.22); border-radius:20px; background:radial-gradient(circle at 85% 0,rgba(20,184,166,.23),transparent 34%),linear-gradient(135deg,#0f172a 0%,#12243b 55%,#0f3b3b 100%); box-shadow:0 18px 45px rgba(2,6,23,.22); }
    .cl-hero:after { content:""; position:absolute; right:-50px; bottom:-90px; width:220px; height:220px; border:38px solid rgba(94,234,212,.07); border-radius:50%; }
    .cl-hero__content,.cl-hero__actions { position:relative; z-index:1; }
    .cl-hero__eyebrow { color:#99f6e4; font-size:.76rem; font-weight:800; letter-spacing:.11em; text-transform:uppercase; }
    .cl-hero__eyebrow span { display:inline-block; width:7px; height:7px; margin-right:7px; border-radius:50%; background:#2dd4bf; box-shadow:0 0 0 5px rgba(45,212,191,.13); }
    .cl-hero h1 { margin:7px 0 4px; color:#f8fafc; font-size:2rem; font-weight:850; }
    .cl-hero p { margin:0; color:#cbd5e1; }
    .cl-hero__actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:9px; }
    .cl-btn { border-radius:10px; border:1px solid transparent; padding:.58rem .85rem; font-weight:750; transition:.18s ease; white-space:nowrap; }
    .cl-btn:hover { transform:translateY(-1px); }
    .cl-btn--ghost { color:#e2e8f0; border-color:rgba(226,232,240,.22); background:rgba(255,255,255,.06); }
    .cl-btn--ghost:hover { color:#fff; background:rgba(255,255,255,.12); }
    .cl-btn--excel { color:#fff; background:linear-gradient(135deg,#059669,#047857); box-shadow:0 8px 18px rgba(5,150,105,.22); }
    .cl-btn--excel:hover { color:#fff; background:linear-gradient(135deg,#10b981,#059669); }
    .cl-btn--primary { color:#fff; background:linear-gradient(135deg,#0f766e,#0d9488); }
    .cl-btn--primary:hover { color:#fff; }
    .cl-btn--clear { margin-left:7px; color:#94a3b8; border-color:rgba(148,163,184,.25); background:rgba(148,163,184,.08); }
    .cl-btn--small { padding:.42rem .7rem; font-size:.84rem; }
    .cl-kpis { margin-top:18px; }
    .cl-kpi { position:relative; overflow:hidden; display:flex; align-items:center; gap:16px; min-height:110px; padding:18px; border:1px solid rgba(148,163,184,.16); border-radius:16px; background:linear-gradient(145deg,rgba(15,23,42,.91),rgba(30,41,59,.83)); box-shadow:0 12px 30px rgba(2,6,23,.16); }
    .cl-kpi:after { content:""; position:absolute; width:80px; height:80px; right:-30px; top:-30px; border-radius:50%; background:currentColor; opacity:.08; }
    .cl-kpi__icon { display:grid; place-items:center; flex:0 0 54px; height:54px; border-radius:14px; color:#fff; font-size:1.35rem; }
    .cl-kpi span,.cl-kpi small { display:block; color:#94a3b8; }
    .cl-kpi span { font-size:.72rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase; }
    .cl-kpi strong { display:block; color:#f8fafc; font-size:1.8rem; line-height:1.15; }
    .cl-kpi small { font-size:.75rem; }
    .cl-kpi--emerald { color:#34d399; }.cl-kpi--emerald .cl-kpi__icon{background:linear-gradient(145deg,#10b981,#047857)}
    .cl-kpi--blue { color:#60a5fa; }.cl-kpi--blue .cl-kpi__icon{background:linear-gradient(145deg,#3b82f6,#1d4ed8)}
    .cl-kpi--violet { color:#a78bfa; }.cl-kpi--violet .cl-kpi__icon{background:linear-gradient(145deg,#8b5cf6,#6d28d9)}
    .cl-kpi--amber { color:#fbbf24; }.cl-kpi--amber .cl-kpi__icon{background:linear-gradient(145deg,#f59e0b,#b45309)}
    .cl-panel { overflow:hidden; border:1px solid rgba(148,163,184,.16); border-radius:16px; background:rgba(15,23,42,.71); box-shadow:0 14px 34px rgba(2,6,23,.14); }
    .cl-panel__header { display:flex; align-items:center; justify-content:space-between; min-height:70px; padding:14px 20px; border-bottom-color:rgba(148,163,184,.14); background:linear-gradient(90deg,rgba(15,118,110,.13),transparent 45%); }
    .cl-panel__header .card-title { float:none; margin:1px 0 0; color:#f1f5f9; font-weight:800; }
    .cl-panel__kicker { color:#2dd4bf; font-size:.68rem; font-weight:850; letter-spacing:.11em; text-transform:uppercase; }
    .cl-panel label { color:#cbd5e1; font-size:.78rem; font-weight:750; }
    .cl-panel .form-control,.cl-panel .input-group-text { border-color:rgba(148,163,184,.2); background:rgba(15,23,42,.78); color:#e2e8f0; }
    .cl-panel .form-control:focus { border-color:#2dd4bf; box-shadow:0 0 0 .16rem rgba(45,212,191,.13); }
    .cl-panel .input-group-text { color:#5eead4; }
    .cl-panel select option { color:#111827; background:#fff; }
    .cl-filter-toggle { color:#cbd5e1; border-color:rgba(148,163,184,.25); background:rgba(148,163,184,.08); }
    .cl-results__tools { display:flex; align-items:center; gap:12px; color:#94a3b8; font-size:.8rem; }
    .cl-results__tools select { width:70px; }
    .cl-table { color:#dbe5f1; }
    .cl-table thead th { padding:13px 16px; border:0; background:#0f766e; color:#fff; font-size:.75rem; letter-spacing:.06em; text-transform:uppercase; }
    .cl-table tbody td { padding:16px; border-top:1px solid rgba(148,163,184,.12); vertical-align:top; }
    .cl-table tbody tr:nth-child(even) { background:rgba(30,41,59,.25); }
    .cl-table tbody tr:hover { background:rgba(20,184,166,.07); }
    .cl-date-col { width:135px; }.cl-place-col { width:22%; }
    .cl-date { display:flex; align-items:center; gap:7px; color:#f8fafc; }
    .cl-date i,.cl-place i { color:#2dd4bf; }
    .cl-folio { display:inline-block; margin-top:7px; color:#7dd3fc; font-size:.75rem; font-weight:700; }
    .cl-place { display:flex; gap:8px; color:#dbe5f1; line-height:1.5; }
    .cl-place i { margin-top:4px; }
    .cl-vehicle-head { display:flex; align-items:center; gap:10px; }
    .cl-vehicle-head strong,.cl-vehicle-head small { display:block; }.cl-vehicle-head small { margin-top:2px; color:#94a3b8; }
    .cl-vehicle-icon { display:grid; place-items:center; flex:0 0 36px; height:36px; border-radius:10px; color:#99f6e4; background:rgba(20,184,166,.13); }
    .cl-tags { display:flex; flex-wrap:wrap; gap:6px; margin-top:10px; }
    .cl-tags span { padding:4px 7px; border:1px solid rgba(148,163,184,.17); border-radius:7px; color:#cbd5e1; background:rgba(148,163,184,.07); font-size:.71rem; }
    .cl-tags .cl-tag--retained { color:#fde68a; border-color:rgba(245,158,11,.24); background:rgba(245,158,11,.1); }
    .cl-details { margin-top:8px; color:#94a3b8; font-size:.75rem; }.cl-details summary{cursor:pointer;color:#5eead4}.cl-details p{margin:6px 0 0;line-height:1.45}
    .cl-reason { max-width:520px; color:#cbd5e1; line-height:1.55; }
    .cl-empty { padding:54px 20px; text-align:center; color:#94a3b8; }.cl-empty i{color:#2dd4bf;font-size:2.1rem}.cl-empty h4{margin:12px 0 4px;color:#f8fafc;font-weight:800}
    .cl-pagination { display:flex; justify-content:center; border-top-color:rgba(148,163,184,.13); background:rgba(15,23,42,.35); }.cl-pagination nav{margin-bottom:0}.cl-pagination .pagination{margin-bottom:0}
    @media(max-width:991.98px){.cl-hero{align-items:flex-start;flex-direction:column}.cl-hero__actions{justify-content:flex-start}.cl-results__header{align-items:flex-start;gap:12px}.cl-results__tools{align-items:flex-end;flex-direction:column}}
    @media(max-width:767.98px){.cl-hero{padding:22px 18px}.cl-hero h1{font-size:1.55rem}.cl-hero__actions{width:100%}.cl-hero__actions .cl-btn{flex:1 1 auto}.cl-results__header{flex-direction:column}.cl-results__tools{width:100%;align-items:stretch}.cl-table thead{display:none}.cl-table,.cl-table tbody,.cl-table tr,.cl-table td{display:block;width:100%}.cl-table tbody tr{padding:8px 0;border-top:1px solid rgba(148,163,184,.18)}.cl-table tbody td{display:grid;grid-template-columns:105px minmax(0,1fr);gap:10px;padding:10px 14px;border:0}.cl-table tbody td:before{content:attr(data-label);color:#5eead4;font-size:.67rem;font-weight:850;letter-spacing:.06em;text-transform:uppercase}.cl-table tbody td[colspan]{display:block}.cl-reason{max-width:none}.cl-filter-actions{padding-top:4px}}
</style>
@stop
