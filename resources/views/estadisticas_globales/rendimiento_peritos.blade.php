@extends('adminlte::page')

@section('title', 'Rendimiento de Peritos')

@section('content_header')
    <div class="rp-hero">
        <div>
            <div class="rp-eyebrow"><i class="fas fa-ranking-star"></i> Unidad Siniestros · Turnos A y B</div>
            <h1>Rendimiento de Peritos</h1>
            <p>Siniestros y actividades: volumen, oportunidad y calidad de las capturas realizadas por compañeros con rol Perito.</p>
        </div>
        <div class="rp-hero__periodo">
            <span>Periodo analizado</span>
            <strong>{{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</strong>
        </div>
    </div>
@stop

@section('content')
    @php
        $duracion = function ($minutos) {
            if ($minutos === null) return 'Sin dato';
            if ($minutos < 60) return $minutos . ' min';
            $horas = intdiv((int) $minutos, 60);
            $resto = (int) $minutos % 60;
            return $horas . ' h' . ($resto ? ' ' . $resto . ' min' : '');
        };
        $liderVolumen = $porPerito->sortByDesc('total')->first();
        $liderCalidad = $porPerito->where('hechos', '>=', 5)->sortByDesc('completitud')->first();
    @endphp

    <div class="rp-note">
        <i class="fas fa-circle-info"></i>
        <div>
            <strong>Criterio de lectura.</strong> Se cuentan tanto <b>siniestros como actividades</b> por fecha de creación, no por la fecha operativa del registro. Solo entran capturas de la unidad Siniestros creadas por usuarios que actualmente tienen rol <b>Perito</b>. El turno proviene de la bitácora asociada cuando existe; en capturas anteriores se usa el turno actual del usuario. La completitud y el tiempo de cierre aplican únicamente a siniestros; ese control se incorporó en abril de 2026.
        </div>
    </div>

    <form method="GET" action="{{ route('estadisticas_globales.rendimiento_peritos') }}" class="rp-filter">
        <div class="rp-filter__field">
            <label for="desde">Desde</label>
            <input id="desde" name="desde" type="date" class="form-control" value="{{ $desde }}">
        </div>
        <div class="rp-filter__field">
            <label for="hasta">Hasta</label>
            <input id="hasta" name="hasta" type="date" class="form-control" value="{{ $hasta }}">
        </div>
        <div class="rp-filter__field">
            <label for="turno">Turno</label>
            <select id="turno" name="turno" class="form-control">
                <option value="">A y B</option>
                <option value="A" @selected($turnoSeleccionado === 'A')>Turno A</option>
                <option value="B" @selected($turnoSeleccionado === 'B')>Turno B</option>
            </select>
        </div>
        <div class="rp-filter__field rp-filter__field--perito">
            <label for="perito_id">Compañero</label>
            <select id="perito_id" name="perito_id" class="form-control">
                <option value="">Todos los peritos</option>
                @foreach($peritos as $perito)
                    <option value="{{ $perito->id }}" @selected((int) $peritoSeleccionado === (int) $perito->id)>
                        {{ $perito->name }} · Turno {{ $perito->turno }}
                    </option>
                @endforeach
            </select>
        </div>
        <button class="btn rp-btn" type="submit"><i class="fas fa-filter"></i> Aplicar filtros</button>
        <a class="btn rp-btn rp-btn--ghost" href="{{ route('estadisticas_globales.rendimiento_peritos') }}"><i class="fas fa-rotate-left"></i> Limpiar</a>
    </form>

    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger">Revisa el rango de fechas seleccionado.</div>
    @endif

    <div class="rp-kpis">
        <article class="rp-kpi rp-kpi--blue">
            <span class="rp-kpi__icon"><i class="fas fa-file-circle-check"></i></span>
            <div><small>Registros capturados</small><strong>{{ number_format($kpis['total']) }}</strong><em>{{ number_format($kpis['hechos']) }} siniestros + {{ number_format($kpis['actividades']) }} actividades</em></div>
        </article>
        <article class="rp-kpi rp-kpi--teal">
            <span class="rp-kpi__icon"><i class="fas fa-clipboard-list"></i></span>
            <div><small>Actividades capturadas</small><strong>{{ number_format($kpis['actividades']) }}</strong><em>Cantidad reportada: {{ number_format($kpis['cantidad_actividades']) }}</em></div>
        </article>
        <article class="rp-kpi rp-kpi--green">
            <span class="rp-kpi__icon"><i class="fas fa-circle-check"></i></span>
            <div><small>Siniestros completos</small><strong>{{ number_format($kpis['completas']) }}</strong><em>{{ $kpis['completitud'] }}% de {{ number_format($kpis['hechos']) }} siniestros</em></div>
        </article>
        <article class="rp-kpi rp-kpi--violet">
            <span class="rp-kpi__icon"><i class="fas fa-users"></i></span>
            <div><small>Peritos activos</small><strong>{{ number_format($kpis['peritos_activos']) }}</strong><em>{{ $kpis['promedio_por_perito'] }} capturas por perito</em></div>
        </article>
        <article class="rp-kpi rp-kpi--amber">
            <span class="rp-kpi__icon"><i class="fas fa-stopwatch"></i></span>
            <div><small>Tiempo para completar</small><strong>{{ $duracion($kpis['minutos_promedio_cierre']) }}</strong><em>{{ $kpis['oportunidad_24h'] }}% completadas en ≤ 24 h</em></div>
        </article>
        <article class="rp-kpi rp-kpi--cyan">
            <span class="rp-kpi__icon"><i class="fas fa-location-dot"></i></span>
            <div><small>Con ubicación</small><strong>{{ $kpis['cobertura_ubicacion'] }}%</strong><em>{{ number_format($kpis['con_ubicacion']) }} de {{ number_format($kpis['total']) }}</em></div>
        </article>
        <article class="rp-kpi rp-kpi--indigo">
            <span class="rp-kpi__icon"><i class="fas fa-people-group"></i></span>
            <div><small>Personas alcanzadas</small><strong>{{ number_format($kpis['personas_alcanzadas']) }}</strong><em>{{ number_format($kpis['personas_participantes']) }} participantes en actividades</em></div>
        </article>
        <article class="rp-kpi rp-kpi--rose">
            <span class="rp-kpi__icon"><i class="fas fa-list-check"></i></span>
            <div><small>Elementos relacionados</small><strong>{{ $kpis['cobertura_elementos'] }}%</strong><em>{{ number_format($kpis['elementos_capturados']) }} de {{ number_format($kpis['elementos_esperados']) }} esperados</em></div>
        </article>
    </div>

    <div class="rp-insights">
        <div class="rp-insight">
            <span>Comparación contra el periodo anterior</span>
            <strong>
                @if($comparacion['variacion_total'] === null)
                    Sin base comparable
                @else
                    {{ $comparacion['variacion_total'] >= 0 ? '+' : '' }}{{ $comparacion['variacion_total'] }}% capturas
                @endif
            </strong>
            <small>{{ \Carbon\Carbon::parse($comparacion['desde'])->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($comparacion['hasta'])->format('d/m/Y') }} · {{ number_format($comparacion['total']) }} capturas</small>
        </div>
        <div class="rp-insight">
            <span>Mayor volumen del periodo</span>
            <strong>{{ $liderVolumen?->name ?? 'Sin datos' }}</strong>
            <small>{{ $liderVolumen ? number_format($liderVolumen->total) . ' registros (' . number_format($liderVolumen->hechos) . ' siniestros + ' . number_format($liderVolumen->actividades) . ' actividades) · Turno ' . $liderVolumen->turno : 'No hubo capturas con los filtros actuales' }}</small>
        </div>
        <div class="rp-insight">
            <span>Mayor completitud (mínimo 5 siniestros)</span>
            <strong>{{ $liderCalidad?->name ?? 'Sin datos suficientes' }}</strong>
            <small>{{ $liderCalidad ? $liderCalidad->completitud . '% completos · ' . number_format($liderCalidad->hechos) . ' siniestros' : 'Se requieren al menos 5 siniestros' }}</small>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-12">
            <section class="rp-panel">
                <div class="rp-panel__head">
                    <div><h2>Ritmo de captura</h2><p>Siniestros y actividades por día, separados por turno.</p></div>
                    <span class="rp-chip">{{ $kpis['dias_con_captura'] }} días con actividad</span>
                </div>
                <div class="rp-chart rp-chart--wide"><canvas id="chartDiario"></canvas></div>
            </section>
        </div>
        <div class="col-xl-4 col-12">
            <section class="rp-panel">
                <div class="rp-panel__head"><div><h2>Participación por turno</h2><p>Distribución del volumen total.</p></div></div>
                <div class="rp-chart"><canvas id="chartTurnos"></canvas></div>
            </section>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <section class="rp-panel">
                <div class="rp-panel__head">
                    <div><h2>Comparativa A vs. B</h2><p>Volumen, personal activo y señales de calidad.</p></div>
                </div>
                <div class="table-responsive">
                    <table class="table rp-table mb-0">
                        <thead><tr><th>Turno</th><th>Total</th><th>Siniestros</th><th>Actividades</th><th>Peritos</th><th>Por perito</th><th>Completitud siniestros</th><th>Personas alcanzadas</th><th>Ubicación</th><th>Tiempo cierre siniestros</th></tr></thead>
                        <tbody>
                            @forelse($porTurno as $fila)
                                <tr>
                                    <td><span class="rp-turno rp-turno--{{ strtolower($fila->turno) }}">{{ $fila->turno }}</span></td>
                                    <td><b>{{ number_format($fila->total) }}</b></td>
                                    <td>{{ number_format($fila->hechos) }}</td>
                                    <td>{{ number_format($fila->actividades) }}</td>
                                    <td>{{ number_format($fila->peritos_activos) }}</td>
                                    <td>{{ $fila->promedio_por_perito }}</td>
                                    <td>
                                        @if($fila->hechos > 0)<span class="rp-progress"><i style="width: {{ min($fila->completitud, 100) }}%"></i></span><b>{{ $fila->completitud }}%</b>@else — @endif
                                    </td>
                                    <td>{{ number_format($fila->personas_alcanzadas) }}</td>
                                    <td>{{ $fila->cobertura_ubicacion }}%</td>
                                    <td>{{ $duracion($fila->minutos_promedio_cierre) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="rp-empty">No hay capturas en el periodo seleccionado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 col-12">
            <section class="rp-panel">
                <div class="rp-panel__head"><div><h2>Tipos de siniestro</h2><p>Los 8 más capturados.</p></div></div>
                <div class="rp-chart"><canvas id="chartTipos"></canvas></div>
            </section>
        </div>
        <div class="col-xl-6 col-12">
            <section class="rp-panel">
                <div class="rp-panel__head"><div><h2>Categorías de actividad</h2><p>Las 8 más capturadas por los peritos.</p></div></div>
                <div class="rp-chart"><canvas id="chartActividades"></canvas></div>
            </section>
        </div>
    </div>

    <section class="rp-panel">
        <div class="rp-panel__head rp-panel__head--ranking">
            <div><h2>Detalle por compañero</h2><p>Orden inicial por cantidad de capturas. La completitud muestra calidad, no solo volumen.</p></div>
            <div class="rp-search"><i class="fas fa-search"></i><input id="buscarPerito" type="search" placeholder="Buscar compañero…"></div>
        </div>
        <div class="table-responsive">
            <table class="table rp-table rp-table--ranking mb-0" id="tablaPeritos">
                <thead>
                    <tr><th>#</th><th>Compañero</th><th>Turno</th><th>Total</th><th>Siniestros</th><th>Actividades</th><th>Cant. actividades</th><th>Personas alcanzadas</th><th>Días activos</th><th>Prom./día activo</th><th>Completitud siniestros</th><th>Ubicación</th><th>Aprobadas</th><th>Rechazadas</th><th>Tiempo cierre siniestros</th><th>Última captura</th></tr>
                </thead>
                <tbody>
                    @forelse($porPerito as $indice => $fila)
                        <tr>
                            <td class="rp-rank">{{ $indice + 1 }}</td>
                            <td><b>{{ $fila->name }}</b></td>
                            <td><span class="rp-turno rp-turno--{{ strtolower($fila->turno) }}">{{ $fila->turno }}</span></td>
                            <td><b>{{ number_format($fila->total) }}</b></td>
                            <td>{{ number_format($fila->hechos) }}</td>
                            <td>{{ number_format($fila->actividades) }}</td>
                            <td>{{ number_format($fila->cantidad_actividades) }}</td>
                            <td>{{ number_format($fila->personas_alcanzadas) }}</td>
                            <td>{{ number_format($fila->dias_activos) }}</td>
                            <td>{{ $fila->promedio_dia_activo }}</td>
                            <td>@if($fila->hechos > 0)<span class="rp-quality {{ $fila->completitud >= 90 ? 'is-good' : ($fila->completitud >= 70 ? 'is-mid' : 'is-low') }}">{{ $fila->completitud }}%</span>@else — @endif</td>
                            <td>{{ $fila->cobertura_ubicacion }}%</td>
                            <td>{{ number_format($fila->aprobadas) }}</td>
                            <td>{{ number_format($fila->rechazadas) }}</td>
                            <td>{{ $duracion($fila->minutos_promedio_cierre) }}</td>
                            <td>{{ $fila->ultima_captura ? \Carbon\Carbon::parse($fila->ultima_captura)->format('d/m/Y H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="16" class="rp-empty">No hay capturas de peritos para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@stop

@section('css')
<style>
    :root{--rp-navy:#0d1b3d;--rp-blue:#2da8ff;--rp-line:rgba(255,255,255,.12);--rp-muted:rgba(234,240,255,.65);--rp-bg:transparent}
    .rp-hero{position:relative;overflow:hidden;display:flex;align-items:flex-end;justify-content:space-between;gap:24px;padding:28px 32px;border-radius:20px;color:#fff;background:linear-gradient(120deg,#091632 0%,#153c79 60%,#0f766e 130%);box-shadow:0 18px 45px rgba(12,35,76,.18)}
    .rp-hero:after{content:"";position:absolute;width:260px;height:260px;border-radius:50%;right:-80px;top:-130px;background:rgba(255,255,255,.08)}
    .rp-hero h1{margin:6px 0 5px;font-size:2rem;font-weight:800;letter-spacing:-.03em}.rp-hero p{margin:0;color:#cbd9ef}.rp-eyebrow{font-size:.76rem;text-transform:uppercase;letter-spacing:.12em;color:#a7f3d0;font-weight:700}.rp-hero__periodo{position:relative;z-index:1;min-width:265px;padding:13px 16px;border:1px solid rgba(255,255,255,.2);border-radius:13px;background:rgba(255,255,255,.09);backdrop-filter:blur(8px)}.rp-hero__periodo span,.rp-hero__periodo strong{display:block}.rp-hero__periodo span{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#cbd5e1}.rp-hero__periodo strong{margin-top:3px;font-size:1rem}
    .rp-note{display:flex;gap:12px;align-items:flex-start;margin:18px 0 14px;padding:13px 16px;border:1px solid #bfdbfe;border-radius:12px;background:#eff6ff;color:#334155;font-size:.86rem}.rp-note i{margin-top:3px;color:#2563eb}
    .rp-filter{display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:18px;padding:16px 18px;border:1px solid var(--rp-line);border-radius:15px;background:#fff;box-shadow:0 8px 22px rgba(27,45,82,.05)}.rp-filter__field{width:155px}.rp-filter__field--perito{min-width:280px;flex:1}.rp-filter label{display:block;margin-bottom:5px;font-size:.72rem;letter-spacing:.05em;text-transform:uppercase;color:#64748b}.rp-filter .form-control{height:40px;border-color:#dce3ee;border-radius:9px}.rp-btn{height:40px;padding:9px 15px;border:0;border-radius:9px;background:var(--rp-blue);color:#fff;font-weight:700}.rp-btn:hover{background:#1d4ed8;color:#fff}.rp-btn--ghost{border:1px solid #dbe2ec;background:#fff;color:#475569}.rp-btn--ghost:hover{background:#f8fafc;color:#0f172a}
    .rp-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}.rp-kpi{display:flex;gap:12px;min-height:132px;padding:17px;border:1px solid var(--rp-line);border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(21,42,78,.055)}.rp-kpi__icon{display:grid;place-items:center;flex:0 0 39px;height:39px;border-radius:11px;font-size:1rem}.rp-kpi small,.rp-kpi strong,.rp-kpi em{display:block}.rp-kpi small{min-height:32px;color:#64748b;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;font-weight:700}.rp-kpi strong{margin:2px 0;font-size:1.55rem;line-height:1.1;color:#111827}.rp-kpi em{font-size:.73rem;color:#64748b;font-style:normal}.rp-kpi--blue .rp-kpi__icon{background:#dbeafe;color:#2563eb}.rp-kpi--green .rp-kpi__icon{background:#d1fae5;color:#059669}.rp-kpi--violet .rp-kpi__icon{background:#ede9fe;color:#7c3aed}.rp-kpi--amber .rp-kpi__icon{background:#fef3c7;color:#d97706}.rp-kpi--cyan .rp-kpi__icon{background:#cffafe;color:#0891b2}.rp-kpi--rose .rp-kpi__icon{background:#ffe4e6;color:#e11d48}.rp-kpi--teal .rp-kpi__icon{background:#ccfbf1;color:#0f766e}.rp-kpi--indigo .rp-kpi__icon{background:#e0e7ff;color:#4338ca}
    .rp-insights{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px}.rp-insight{padding:15px 18px;border-radius:14px;background:#111c36;color:#fff}.rp-insight span,.rp-insight strong,.rp-insight small{display:block}.rp-insight span{font-size:.69rem;text-transform:uppercase;letter-spacing:.08em;color:#93c5fd}.rp-insight strong{overflow:hidden;margin:4px 0 2px;text-overflow:ellipsis;white-space:nowrap;font-size:1rem}.rp-insight small{color:#b8c5dc}
    .rp-panel{margin-bottom:18px;border:1px solid var(--rp-line);border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(21,42,78,.055);overflow:hidden}.rp-panel__head{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:18px 20px 10px}.rp-panel__head h2{margin:0 0 3px;color:#17213a;font-size:1.05rem;font-weight:800}.rp-panel__head p{margin:0;color:var(--rp-muted);font-size:.78rem}.rp-chip{padding:5px 10px;border-radius:999px;background:#eef2ff;color:#4338ca;font-size:.72rem;font-weight:700}.rp-chart{position:relative;height:275px;padding:8px 17px 18px}.rp-chart--wide{height:310px}
    .rp-table{font-size:.79rem;color:#334155}.rp-table thead th{border-top:0;border-bottom:1px solid var(--rp-line);background:#f8fafc;color:#64748b;font-size:.68rem;text-transform:uppercase;letter-spacing:.035em;white-space:nowrap}.rp-table td,.rp-table th{vertical-align:middle;padding:.78rem .7rem}.rp-table tbody tr:hover{background:#fafcff}.rp-table--ranking td{white-space:nowrap}.rp-table--ranking td:nth-child(2){min-width:245px;white-space:normal}.rp-turno{display:inline-grid;place-items:center;width:28px;height:28px;border-radius:8px;font-weight:800}.rp-turno--a{background:#dbeafe;color:#1d4ed8}.rp-turno--b{background:#fef3c7;color:#b45309}.rp-progress{display:inline-block;width:55px;height:6px;margin-right:7px;border-radius:99px;background:#e5e7eb;overflow:hidden;vertical-align:middle}.rp-progress i{display:block;height:100%;border-radius:inherit;background:#10b981}.rp-quality{display:inline-block;min-width:54px;padding:4px 8px;border-radius:999px;text-align:center;font-weight:800}.rp-quality.is-good{background:#d1fae5;color:#047857}.rp-quality.is-mid{background:#fef3c7;color:#b45309}.rp-quality.is-low{background:#fee2e2;color:#b91c1c}.rp-rank{color:#94a3b8;font-weight:800}.rp-empty{padding:35px!important;text-align:center;color:#94a3b8}.rp-search{position:relative}.rp-search i{position:absolute;left:11px;top:11px;color:#94a3b8}.rp-search input{width:240px;height:37px;padding:7px 12px 7px 33px;border:1px solid #dbe2ec;border-radius:9px;outline:0}.rp-search input:focus{border-color:#60a5fa;box-shadow:0 0 0 3px #dbeafe}
    /* Mismo lenguaje visual oscuro y translúcido que las demás estadísticas. */
    .rp-hero{border:1px solid rgba(255,255,255,.12);background:radial-gradient(700px 280px at 20% 30%,rgba(45,168,255,.20),transparent 60%),radial-gradient(700px 280px at 80% 30%,rgba(124,92,255,.18),transparent 60%),linear-gradient(180deg,rgba(255,255,255,.10),rgba(255,255,255,.04));box-shadow:0 18px 55px rgba(0,0,0,.35)}
    .rp-hero p{color:rgba(234,240,255,.68)}
    .rp-note{border-color:rgba(45,168,255,.24);background:rgba(45,168,255,.09);color:rgba(234,240,255,.78);box-shadow:0 10px 28px rgba(0,0,0,.16)}.rp-note i{color:#63c5ff}
    .rp-filter,.rp-kpi,.rp-panel{border-color:rgba(255,255,255,.12);background:linear-gradient(180deg,rgba(255,255,255,.09),rgba(255,255,255,.045));box-shadow:0 10px 35px rgba(0,0,0,.22)}
    .rp-filter label{color:rgba(234,240,255,.76)!important}
    .rp-filter .form-control{border-color:rgba(255,255,255,.14)!important;background:linear-gradient(180deg,rgba(12,16,28,.55),rgba(12,16,28,.40))!important;color:rgba(234,240,255,.92)!important;box-shadow:0 10px 22px rgba(0,0,0,.18)}
    .rp-filter select option{background:#0c101c;color:rgba(234,240,255,.92)}
    .rp-btn{border:1px solid rgba(45,168,255,.35)!important;background:linear-gradient(135deg,rgba(45,168,255,.28),rgba(124,92,255,.24))!important;color:rgba(234,240,255,.96)!important}.rp-btn:hover{border-color:rgba(45,168,255,.55)!important;background:linear-gradient(135deg,rgba(45,168,255,.36),rgba(124,92,255,.32))!important}
    .rp-btn--ghost{border-color:rgba(255,255,255,.12)!important;background:rgba(0,0,0,.18)!important;color:rgba(234,240,255,.86)!important}.rp-btn--ghost:hover{background:rgba(0,0,0,.25)!important;color:#fff!important}
    .rp-kpi small,.rp-kpi em{color:rgba(234,240,255,.62)}.rp-kpi strong{color:rgba(234,240,255,.96)}.rp-kpi__icon{border:1px solid rgba(255,255,255,.12);box-shadow:0 10px 22px rgba(0,0,0,.22)}
    .rp-insight{border:1px solid rgba(255,255,255,.12);background:linear-gradient(135deg,rgba(45,168,255,.13),rgba(124,92,255,.10));box-shadow:0 10px 30px rgba(0,0,0,.20)}.rp-insight span{color:#83d3ff}.rp-insight small{color:rgba(234,240,255,.62)}
    .rp-panel__head h2{color:rgba(234,240,255,.95)}.rp-panel__head p{color:rgba(234,240,255,.60)}.rp-chip{border:1px solid rgba(124,92,255,.25);background:rgba(124,92,255,.13);color:#c9bcff}
    .rp-table{color:rgba(234,240,255,.86)!important}.rp-table thead th{border-color:rgba(255,255,255,.14)!important;background:rgba(0,0,0,.16)!important;color:rgba(234,240,255,.78)!important}.rp-table tbody td{border-color:rgba(255,255,255,.08)!important;background:transparent!important;color:rgba(234,240,255,.78)!important}.rp-table tbody td b{color:rgba(234,240,255,.96)}.rp-table tbody tr:hover{background:rgba(45,168,255,.08)!important}
    .rp-rank,.rp-empty{color:rgba(234,240,255,.52)!important}.rp-search i{color:rgba(234,240,255,.50)}.rp-search input{border-color:rgba(255,255,255,.14);background:rgba(0,0,0,.18);color:rgba(234,240,255,.92)}.rp-search input::placeholder{color:rgba(234,240,255,.50)}.rp-search input:focus{border-color:rgba(45,168,255,.52);box-shadow:0 0 0 4px rgba(45,168,255,.14)}.rp-progress{background:rgba(255,255,255,.13)}
    @media(max-width:1400px){.rp-kpis{grid-template-columns:repeat(3,1fr)}}
    @media(max-width:767px){.rp-hero{display:block;padding:22px}.rp-hero__periodo{margin-top:18px;min-width:0}.rp-kpis,.rp-insights{grid-template-columns:1fr}.rp-filter__field,.rp-filter__field--perito{width:100%;min-width:0}.rp-filter .btn{flex:1}.rp-panel__head--ranking{align-items:flex-start;flex-direction:column}.rp-search,.rp-search input{width:100%}}
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    const diario = @json($diario);
    const turnos = @json($porTurno);
    const tipos = @json($tiposHecho);
    const categorias = @json($categoriasActividad);
    const common = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { usePointStyle: true, boxWidth: 8, color: 'rgba(234,240,255,.76)' } } },
        scales: { x: { grid: { display: false }, ticks: { color: 'rgba(234,240,255,.65)', maxTicksLimit: 12 } }, y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,.08)' }, ticks: { precision: 0, color: 'rgba(234,240,255,.65)' } } }
    };

    if (window.Chart) {
        new Chart(document.getElementById('chartDiario'), {
            type: 'bar',
            data: {
                labels: diario.map(x => x.fecha),
                datasets: [
                    { label: 'Siniestros A', data: diario.map(x => x.hechos_A), backgroundColor: '#2563eb', borderRadius: 4, maxBarThickness: 24, stack: 'A' },
                    { label: 'Actividades A', data: diario.map(x => x.actividades_A), backgroundColor: '#22d3ee', borderRadius: 4, maxBarThickness: 24, stack: 'A' },
                    { label: 'Siniestros B', data: diario.map(x => x.hechos_B), backgroundColor: '#f59e0b', borderRadius: 4, maxBarThickness: 24, stack: 'B' },
                    { label: 'Actividades B', data: diario.map(x => x.actividades_B), backgroundColor: '#fb7185', borderRadius: 4, maxBarThickness: 24, stack: 'B' }
                ]
            },
            options: { ...common, scales: { ...common.scales, x: { ...common.scales.x, stacked: true }, y: { ...common.scales.y, stacked: true } } }
        });

        new Chart(document.getElementById('chartTurnos'), {
            type: 'doughnut',
            data: { labels: turnos.map(x => `Turno ${x.turno}`), datasets: [{ data: turnos.map(x => x.total), backgroundColor: turnos.map(x => x.turno === 'A' ? '#2563eb' : '#f59e0b'), borderWidth: 0, hoverOffset: 5 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: common.plugins }
        });

        new Chart(document.getElementById('chartTipos'), {
            type: 'bar',
            data: { labels: tipos.map(x => x.tipo), datasets: [{ label: 'Capturas', data: tipos.map(x => x.total), backgroundColor: '#14b8a6', borderRadius: 5 }] },
            options: { ...common, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ...common.scales.y }, y: { ...common.scales.x, ticks: { color: 'rgba(234,240,255,.68)', callback: function(value) { const label = this.getLabelForValue(value); return label.length > 28 ? label.slice(0, 28) + '…' : label; } } } } }
        });

        new Chart(document.getElementById('chartActividades'), {
            type: 'bar',
            data: { labels: categorias.map(x => x.categoria), datasets: [{ label: 'Actividades', data: categorias.map(x => x.total), backgroundColor: '#8b5cf6', borderRadius: 5 }] },
            options: { ...common, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ...common.scales.y }, y: { ...common.scales.x, ticks: { color: 'rgba(234,240,255,.68)', callback: function(value) { const label = this.getLabelForValue(value); return label.length > 28 ? label.slice(0, 28) + '…' : label; } } } } }
        });
    }

    const buscar = document.getElementById('buscarPerito');
    const filas = [...document.querySelectorAll('#tablaPeritos tbody tr')];
    buscar?.addEventListener('input', () => {
        const termino = buscar.value.trim().toLocaleLowerCase('es');
        filas.forEach(fila => fila.hidden = !fila.textContent.toLocaleLowerCase('es').includes(termino));
    });
})();
</script>
@stop
