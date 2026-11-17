@extends('adminlte::page')

@section('title', 'Depósitos vehiculares · Reporte general')

@section('content_header')
    <div class="dv-hero">
        <div class="dv-hero__content">
            <div class="dv-hero__eyebrow"><span></span> Estadísticas · Control vehicular</div>
            <h1>Depósitos vehiculares</h1>
            <p>Consulta los registros de depósito y liberación, aplica filtros y exporta el resultado a Excel.</p>
        </div>
        <div class="dv-hero__actions">
            @can('ver estadisticas globales')
                <a href="{{ route('estadisticas_globales.index') }}" class="btn dv-btn dv-btn--ghost">
                    <i class="fa-solid fa-chart-line"></i> Estadísticas globales
                </a>
            @endcan
            @if(auth()->user()->hasRole('Superadmin') && auth()->user()->can('ver estadisticas'))
                <a href="{{ route('estadisticas.index') }}" class="btn dv-btn dv-btn--ghost">
                    <i class="fa-solid fa-arrow-left"></i> Estadísticas generales
                </a>
            @endif
            <a href="{{ route('reportes.depositos_vehiculares.excel', request()->except('page')) }}" class="btn dv-btn dv-btn--excel">
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

    @php
        $totalRegistros = (int) ($resumen['total'] ?? 0);
        $totalLiberados = (int) ($resumen['liberados'] ?? 0);
        $totalSinLiberacion = (int) ($resumen['sin_liberacion'] ?? 0);
        $porcentajeLiberados = $totalRegistros > 0 ? round(100 * $totalLiberados / $totalRegistros, 1) : 0;
    @endphp

    <div class="row dv-kpis">
        <div class="col-xl-3 col-sm-6 mb-3">
            <div class="dv-kpi dv-kpi--emerald">
                <div class="dv-kpi__icon"><i class="fa-solid fa-car-side"></i></div>
                <div><span>Registros</span><strong>{{ number_format($totalRegistros) }}</strong><small>Con los filtros aplicados</small></div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3">
            <div class="dv-kpi dv-kpi--blue">
                <div class="dv-kpi__icon"><i class="fa-solid fa-circle-check"></i></div>
                <div><span>Con liberación</span><strong>{{ number_format($totalLiberados) }}</strong><small>Egreso registrado</small></div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3">
            <div class="dv-kpi dv-kpi--amber">
                <div class="dv-kpi__icon"><i class="fa-solid fa-warehouse"></i></div>
                <div><span>Sin liberación registrada</span><strong>{{ number_format($totalSinLiberacion) }}</strong><small>No implica permanencia confirmada</small></div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3">
            <div class="dv-kpi dv-kpi--violet">
                <div class="dv-kpi__icon"><i class="fa-solid fa-chart-pie"></i></div>
                <div><span>Porcentaje liberado</span><strong>{{ number_format($porcentajeLiberados, 1) }}%</strong><small>Sobre el resultado filtrado</small></div>
            </div>
        </div>
    </div>

    <div class="card dv-panel mb-4">
        <div class="card-header dv-panel__header">
            <div>
                <span class="dv-panel__kicker">Consulta</span>
                <h3 class="card-title">Filtros del reporte</h3>
            </div>
            <button class="btn dv-filter-toggle d-lg-none" type="button" data-toggle="collapse" data-target="#dvFilters" aria-expanded="false" aria-controls="dvFilters">
                <i class="fa-solid fa-sliders"></i> Mostrar filtros
            </button>
        </div>
        <div id="dvFilters" class="collapse d-lg-block">
            <div class="card-body">
                <form method="GET" action="{{ route('reportes.depositos_vehiculares.index') }}">
                    <div class="row">
                        <div class="col-lg-4 col-md-6 mb-3">
                            <label for="q">Búsqueda general</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span></div>
                                <input id="q" name="q" class="form-control" maxlength="150" value="{{ request('q') }}" placeholder="Placas, serie, inventario, folio, marca…">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-6 mb-3">
                            <label for="fecha_campo">Filtrar fechas de</label>
                            <select id="fecha_campo" name="fecha_campo" class="form-control">
                                <option value="ingreso" @selected(request('fecha_campo', 'ingreso') === 'ingreso')>Ingreso (referencia)</option>
                                <option value="egreso" @selected(request('fecha_campo') === 'egreso')>Egreso</option>
                            </select>
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
                            <label for="estado">Situación</label>
                            <select id="estado" name="estado" class="form-control">
                                <option value="">Todas</option>
                                <option value="liberado" @selected(request('estado') === 'liberado')>Liberados</option>
                                <option value="sin_liberacion" @selected(request('estado') === 'sin_liberacion')>Sin liberación registrada</option>
                            </select>
                        </div>

                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="unidad_id">Unidad</label>
                            <select id="unidad_id" name="unidad_id" class="form-control">
                                <option value="">Todas las unidades</option>
                                @foreach($opciones['unidades'] ?? [] as $unidadId => $nombre)
                                    <option value="{{ $unidadId }}" @selected((string) request('unidad_id') === (string) $unidadId)>
                                        {{ $nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="origen">Origen del registro</label>
                            <select id="origen" name="origen" class="form-control">
                                <option value="">Todos los orígenes</option>
                                <option value="SINIESTROS" @selected(request('origen') === 'SINIESTROS')>Siniestros</option>
                                <option value="ACTIVIDADES" @selected(request('origen') === 'ACTIVIDADES')>Actividades</option>
                                <option value="OTROS" @selected(request('origen') === 'OTROS')>Otros / sin clasificar</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="municipio">Municipio</label>
                            <input id="municipio" name="municipio" class="form-control" maxlength="120" value="{{ request('municipio') }}" placeholder="Buscar municipio">
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="corralon">Depósito / corralón</label>
                            <input id="corralon" name="corralon" class="form-control" maxlength="120" list="dvCorralones" value="{{ request('corralon') }}" placeholder="Nombre o parte del nombre">
                            <datalist id="dvCorralones">
                                @foreach($opciones['corralones'] ?? [] as $nombreCorralon)
                                    <option value="{{ $nombreCorralon }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="tipo">Tipo de vehículo</label>
                            <select id="tipo" name="tipo" class="form-control">
                                <option value="">Todos</option>
                                @foreach($opciones['tipos'] ?? [] as $tipo)
                                    <option value="{{ $tipo }}" @selected(request('tipo') === $tipo)>{{ $tipo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="marca">Marca</label>
                            <select id="marca" name="marca" class="form-control">
                                <option value="">Todas</option>
                                @foreach($opciones['marcas'] ?? [] as $marca)
                                    <option value="{{ $marca }}" @selected(request('marca') === $marca)>{{ $marca }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <label for="linea">Línea</label>
                            <input id="linea" name="linea" class="form-control" maxlength="100" value="{{ request('linea') }}" placeholder="Ej. Jetta">
                        </div>
                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <label for="modelo">Modelo</label>
                            <input id="modelo" name="modelo" class="form-control" maxlength="20" value="{{ request('modelo') }}" placeholder="Ej. 2025">
                        </div>
                        <div class="col-lg-2 col-md-4 mb-3">
                            <label for="color">Color</label>
                            <input id="color" name="color" class="form-control" maxlength="50" value="{{ request('color') }}" placeholder="Color del vehículo">
                        </div>
                        <div class="col-lg-3 d-flex align-items-end mb-3 dv-filter-actions">
                            <button class="btn dv-btn dv-btn--primary flex-fill" type="submit"><i class="fa-solid fa-filter"></i> Aplicar filtros</button>
                            <a class="btn dv-btn dv-btn--clear" href="{{ route('reportes.depositos_vehiculares.index') }}" title="Limpiar filtros" aria-label="Limpiar filtros"><i class="fa-solid fa-rotate-left"></i></a>
                        </div>
                    </div>
                </form>
                <p class="dv-note mb-0"><i class="fa-solid fa-circle-info"></i> La fecha de ingreso es una referencia obtenida del hecho, actividad o inventario; puede no coincidir con la recepción física en el depósito.</p>
            </div>
        </div>
    </div>

    <div class="card dv-panel dv-results mb-4">
        <div class="card-header dv-panel__header dv-results__header">
            <div>
                <span class="dv-panel__kicker">Resultados</span>
                <h3 class="card-title">{{ number_format($vehiculos->total()) }} registros encontrados</h3>
            </div>
            <div class="dv-results__tools">
                <form method="GET" action="{{ route('reportes.depositos_vehiculares.index') }}" class="d-flex align-items-center">
                    @foreach(request()->except(['page', 'per_page']) as $key => $value)
                        @if(is_scalar($value))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label for="per_page" class="mb-0 mr-2">Mostrar</label>
                    <select id="per_page" name="per_page" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach([25, 50, 100] as $cantidad)
                            <option value="{{ $cantidad }}" @selected((int) request('per_page', 25) === $cantidad)>{{ $cantidad }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('reportes.depositos_vehiculares.excel', request()->except('page')) }}" class="btn dv-btn dv-btn--excel dv-btn--small">
                    <i class="fa-solid fa-download"></i> Excel filtrado
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table dv-table mb-0">
                    <thead>
                        <tr>
                            <th class="dv-date-col">Ingreso (referencia)</th>
                            <th>Vehículo</th>
                            <th>Ubicación y origen</th>
                            <th>Depósito / corralón</th>
                            <th class="dv-date-col">Egreso</th>
                            <th>Situación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehiculos as $vehiculo)
                            <tr>
                                <td data-label="Ingreso">
                                    <div class="dv-date">
                                        <i class="fa-regular fa-calendar"></i>
                                        <strong>{{ $vehiculo->fecha_ingreso ? \Carbon\Carbon::parse($vehiculo->fecha_ingreso)->format('d/m/Y') : 'Sin fecha' }}</strong>
                                    </div>
                                    <span class="dv-folio">Registro #{{ $vehiculo->vehiculo_id }}</span>
                                </td>
                                <td data-label="Vehículo">
                                    <div class="dv-vehicle-head">
                                        <span class="dv-vehicle-icon"><i class="fa-solid fa-car-side"></i></span>
                                        <div>
                                            <strong>{{ collect([$vehiculo->marca, $vehiculo->linea])->filter()->implode(' · ') ?: 'Marca / línea sin dato' }}</strong>
                                            <small>{{ collect([$vehiculo->tipo, $vehiculo->modelo, $vehiculo->color])->filter()->implode(' · ') }}</small>
                                        </div>
                                    </div>
                                    <div class="dv-tags">
                                        @if($vehiculo->placas)<span><i class="fa-regular fa-id-card"></i> {{ $vehiculo->placas }}</span>@endif
                                        @if($vehiculo->inventario)<span><i class="fa-solid fa-hashtag"></i> Inv. {{ $vehiculo->inventario }}</span>@endif
                                        @if($vehiculo->serie)<span title="{{ $vehiculo->serie }}"><i class="fa-solid fa-barcode"></i> {{ \Illuminate\Support\Str::limit($vehiculo->serie, 22) }}</span>@endif
                                    </div>
                                </td>
                                <td data-label="Lugar y origen">
                                    <div class="dv-place"><i class="fa-solid fa-location-dot"></i><span>{{ $vehiculo->ubicacion ?: 'SIN ESPECIFICAR' }}</span></div>
                                    @if($vehiculo->municipio)<div class="dv-subline">{{ $vehiculo->municipio }}</div>@endif
                                    <div class="dv-tags">
                                        <span><i class="fa-solid fa-layer-group"></i> {{ $vehiculo->origen ?: 'OTROS' }}</span>
                                        
                                        @if($vehiculo->unidad_id)
                                            <span>
                                                <i class="fa-solid fa-building-shield"></i>
                                                {{ $opciones['unidades'][$vehiculo->unidad_id] ?? 'Unidad sin identificar' }}
                                            </span>
                                        @else
                                            <span>Unidad sin identificar</span>
                                        @endif

                                    </div>
                                </td>
                                <td data-label="Depósito">
                                    <div class="dv-place"><i class="fa-solid fa-warehouse"></i><span>{{ $vehiculo->corralon ?: 'SIN ESPECIFICAR' }}</span></div>
                                </td>
                                <td data-label="Egreso">
                                    <div class="dv-date">
                                        <i class="fa-regular fa-calendar-check"></i>
                                        <strong>{{ $vehiculo->fecha_egreso ? \Carbon\Carbon::parse($vehiculo->fecha_egreso)->format('d/m/Y') : 'Sin fecha' }}</strong>
                                    </div>
                                    @if($vehiculo->folio_liberacion)<span class="dv-folio">Folio {{ $vehiculo->folio_liberacion }}</span>@endif
                                </td>
                                <td data-label="Situación">
                                    @if($vehiculo->fecha_egreso)
                                        <span class="dv-status dv-status--released"><i class="fa-solid fa-circle-check"></i> Liberación registrada</span>
                                    @else
                                        <span class="dv-status dv-status--pending"><i class="fa-solid fa-clock"></i> Sin liberación registrada</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="dv-empty">
                                        <i class="fa-solid fa-filter-circle-xmark"></i>
                                        <h4>Sin resultados</h4>
                                        <p>No hay registros de depósitos vehiculares que coincidan con los filtros.</p>
                                        <a href="{{ route('reportes.depositos_vehiculares.index') }}" class="btn dv-btn dv-btn--primary">Limpiar filtros</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($vehiculos->hasPages())
            <div class="card-footer dv-pagination">{{ $vehiculos->onEachSide(1)->links('pagination::bootstrap-4') }}</div>
        @endif
    </div>
@stop

@section('css')
<style>

    .dv-hero { position:relative; overflow:hidden; display:flex; align-items:center; justify-content:space-between; gap:24px; padding:26px 30px; border:1px solid rgba(45,212,191,.22); border-radius:20px; background:radial-gradient(circle at 85% 0,rgba(20,184,166,.23),transparent 34%),linear-gradient(135deg,#0f172a 0%,#12243b 55%,#0f3b3b 100%); box-shadow:0 18px 45px rgba(2,6,23,.22); }
    .dv-hero:after { content:""; position:absolute; right:-50px; bottom:-90px; width:220px; height:220px; border:38px solid rgba(94,234,212,.07); border-radius:50%; }
    .dv-hero__content,.dv-hero__actions { position:relative; z-index:1; }
    .dv-hero__eyebrow { color:#99f6e4; font-size:.76rem; font-weight:800; letter-spacing:.11em; text-transform:uppercase; }
    .dv-hero__eyebrow span { display:inline-block; width:7px; height:7px; margin-right:7px; border-radius:50%; background:#2dd4bf; box-shadow:0 0 0 5px rgba(45,212,191,.13); }
    .dv-hero h1 { margin:7px 0 4px; color:#f8fafc; font-size:2rem; font-weight:850; }
    .dv-hero p { margin:0; color:#cbd5e1; }
    .dv-hero__actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:9px; }
    .dv-btn { border-radius:10px; border:1px solid transparent; padding:.58rem .85rem; font-weight:750; transition:.18s ease; white-space:nowrap; }
    .dv-btn:hover { transform:translateY(-1px); }
    .dv-btn--ghost { color:#e2e8f0; border-color:rgba(226,232,240,.22); background:rgba(255,255,255,.06); }
    .dv-btn--ghost:hover { color:#fff; background:rgba(255,255,255,.12); }
    .dv-btn--excel { color:#fff; background:linear-gradient(135deg,#059669,#047857); box-shadow:0 8px 18px rgba(5,150,105,.22); }
    .dv-btn--excel:hover { color:#fff; background:linear-gradient(135deg,#10b981,#059669); }
    .dv-btn--primary { color:#fff; background:linear-gradient(135deg,#0f766e,#0d9488); }
    .dv-btn--primary:hover { color:#fff; }
    .dv-btn--clear { margin-left:7px; color:#94a3b8; border-color:rgba(148,163,184,.25); background:rgba(148,163,184,.08); }
    .dv-btn--small { padding:.42rem .7rem; font-size:.84rem; }
    .dv-kpis { margin-top:18px; }
    .dv-kpi { position:relative; overflow:hidden; display:flex; align-items:center; gap:16px; min-height:110px; padding:18px; border:1px solid rgba(148,163,184,.16); border-radius:16px; background:linear-gradient(145deg,rgba(15,23,42,.91),rgba(30,41,59,.83)); box-shadow:0 12px 30px rgba(2,6,23,.16); }
    .dv-kpi:after { content:""; position:absolute; width:80px; height:80px; right:-30px; top:-30px; border-radius:50%; background:currentColor; opacity:.08; }
    .dv-kpi__icon { display:grid; place-items:center; flex:0 0 54px; height:54px; border-radius:14px; color:#fff; font-size:1.35rem; }
    .dv-kpi span,.dv-kpi small { display:block; color:#94a3b8; }
    .dv-kpi span { font-size:.72rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase; }
    .dv-kpi strong { display:block; color:#f8fafc; font-size:1.8rem; line-height:1.15; }
    .dv-kpi small { font-size:.75rem; }
    .dv-kpi--emerald { color:#34d399; }.dv-kpi--emerald .dv-kpi__icon{background:linear-gradient(145deg,#10b981,#047857)}
    .dv-kpi--blue { color:#60a5fa; }.dv-kpi--blue .dv-kpi__icon{background:linear-gradient(145deg,#3b82f6,#1d4ed8)}
    .dv-kpi--violet { color:#a78bfa; }.dv-kpi--violet .dv-kpi__icon{background:linear-gradient(145deg,#8b5cf6,#6d28d9)}
    .dv-kpi--amber { color:#fbbf24; }.dv-kpi--amber .dv-kpi__icon{background:linear-gradient(145deg,#f59e0b,#b45309)}
    .dv-panel { overflow:hidden; border:1px solid rgba(148,163,184,.16); border-radius:16px; background:rgba(15,23,42,.71); box-shadow:0 14px 34px rgba(2,6,23,.14); }
    .dv-panel__header { display:flex; align-items:center; justify-content:space-between; min-height:70px; padding:14px 20px; border-bottom-color:rgba(148,163,184,.14); background:linear-gradient(90deg,rgba(15,118,110,.13),transparent 45%); }
    .dv-panel__header .card-title { float:none; margin:1px 0 0; color:#f1f5f9; font-weight:800; }
    .dv-panel__kicker { color:#2dd4bf; font-size:.68rem; font-weight:850; letter-spacing:.11em; text-transform:uppercase; }
    .dv-panel label { color:#cbd5e1; font-size:.78rem; font-weight:750; }
    .dv-panel .form-control,.dv-panel .input-group-text { border-color:rgba(148,163,184,.2); background:rgba(15,23,42,.78); color:#e2e8f0; }
    .dv-panel .form-control:focus { border-color:#2dd4bf; box-shadow:0 0 0 .16rem rgba(45,212,191,.13); }
    .dv-panel .input-group-text { color:#5eead4; }
    .dv-panel select option { color:#111827; background:#fff; }
    .dv-filter-toggle { color:#cbd5e1; border-color:rgba(148,163,184,.25); background:rgba(148,163,184,.08); }
    .dv-results__tools { display:flex; align-items:center; gap:12px; color:#94a3b8; font-size:.8rem; }
    .dv-results__tools select { width:70px; }
    .dv-table { color:#dbe5f1; }
    .dv-table thead th { padding:13px 16px; border:0; background:#0f766e; color:#fff; font-size:.75rem; letter-spacing:.06em; text-transform:uppercase; }
    .dv-table tbody td { padding:16px; border-top:1px solid rgba(148,163,184,.12); vertical-align:top; }
    .dv-table tbody tr:nth-child(even) { background:rgba(30,41,59,.25); }
    .dv-table tbody tr:hover { background:rgba(20,184,166,.07); }
    .dv-date-col { width:135px; }.dv-place-col { width:22%; }
    .dv-date { display:flex; align-items:center; gap:7px; color:#f8fafc; }
    .dv-date i,.dv-place i { color:#2dd4bf; }
    .dv-folio { display:inline-block; margin-top:7px; color:#7dd3fc; font-size:.75rem; font-weight:700; }
    .dv-place { display:flex; gap:8px; color:#dbe5f1; line-height:1.5; }
    .dv-place i { margin-top:4px; }
    .dv-vehicle-head { display:flex; align-items:center; gap:10px; }
    .dv-vehicle-head strong,.dv-vehicle-head small { display:block; }.dv-vehicle-head small { margin-top:2px; color:#94a3b8; }
    .dv-vehicle-icon { display:grid; place-items:center; flex:0 0 36px; height:36px; border-radius:10px; color:#99f6e4; background:rgba(20,184,166,.13); }
    .dv-tags { display:flex; flex-wrap:wrap; gap:6px; margin-top:10px; }
    .dv-tags span { padding:4px 7px; border:1px solid rgba(148,163,184,.17); border-radius:7px; color:#cbd5e1; background:rgba(148,163,184,.07); font-size:.71rem; }
    .dv-tags .dv-tag--retained { color:#fde68a; border-color:rgba(245,158,11,.24); background:rgba(245,158,11,.1); }
    .dv-details { margin-top:8px; color:#94a3b8; font-size:.75rem; }.dv-details summary{cursor:pointer;color:#5eead4}.dv-details p{margin:6px 0 0;line-height:1.45}
    .dv-reason { max-width:520px; color:#cbd5e1; line-height:1.55; }
    .dv-empty { padding:54px 20px; text-align:center; color:#94a3b8; }.dv-empty i{color:#2dd4bf;font-size:2.1rem}.dv-empty h4{margin:12px 0 4px;color:#f8fafc;font-weight:800}
    .dv-pagination { display:flex; justify-content:center; border-top-color:rgba(148,163,184,.13); background:rgba(15,23,42,.35); }.dv-pagination nav{margin-bottom:0}.dv-pagination .pagination{margin-bottom:0}
    @media(max-width:991.98px){.dv-hero{align-items:flex-start;flex-direction:column}.dv-hero__actions{justify-content:flex-start}.dv-results__header{align-items:flex-start;gap:12px}.dv-results__tools{align-items:flex-end;flex-direction:column}}
    @media(max-width:767.98px){.dv-hero{padding:22px 18px}.dv-hero h1{font-size:1.55rem}.dv-hero__actions{width:100%}.dv-hero__actions .dv-btn{flex:1 1 auto}.dv-results__header{flex-direction:column}.dv-results__tools{width:100%;align-items:stretch}.dv-table thead{display:none}.dv-table,.dv-table tbody,.dv-table tr,.dv-table td{display:block;width:100%}.dv-table tbody tr{padding:8px 0;border-top:1px solid rgba(148,163,184,.18)}.dv-table tbody td{display:grid;grid-template-columns:105px minmax(0,1fr);gap:10px;padding:10px 14px;border:0}.dv-table tbody td:before{content:attr(data-label);color:#5eead4;font-size:.67rem;font-weight:850;letter-spacing:.06em;text-transform:uppercase}.dv-table tbody td[colspan]{display:block}.dv-reason{max-width:none}.dv-filter-actions{padding-top:4px}}

    .dv-panel .form-control::placeholder { color:#94a3b8; opacity:.65; }
    .dv-panel .form-control[type=date] { min-width:0; }
    .dv-note { color:#94a3b8; font-size:.78rem; line-height:1.45; }
    .dv-note i { color:#5eead4; margin-right:5px; }
    .dv-subline { margin:7px 0 0 21px; color:#94a3b8; font-size:.78rem; }
    .dv-table { min-width:990px; }
    .dv-table thead th { white-space:nowrap; }
    .dv-table td { line-height:1.45; }
    .dv-status { display:inline-flex; align-items:center; gap:6px; border-radius:8px; padding:7px 9px; font-size:.72rem; font-weight:750; line-height:1.2; }
    .dv-status--released { background:rgba(16,185,129,.13); border:1px solid rgba(52,211,153,.25); color:#6ee7b7; }
    .dv-status--pending { background:rgba(245,158,11,.12); border:1px solid rgba(251,191,36,.23); color:#fcd34d; }
    .dv-table td .dv-date { align-items:flex-start; }
    @media(max-width:767.98px) {
        .dv-table { min-width:0; }
        .dv-table tbody td { grid-template-columns:110px minmax(0,1fr); }
        .dv-table tbody td .dv-tags { grid-column:2; }
        .dv-status { white-space:normal; }
    }

</style>
@stop
