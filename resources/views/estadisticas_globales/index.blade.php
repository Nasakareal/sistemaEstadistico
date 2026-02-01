@extends('adminlte::page')

@section('title', 'Estadísticas Globales')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Panorama General · Análisis · Tendencias</span>
            </div>

            <div class="sv-hero__title">
                Estadísticas Globales
            </div>

            <div class="sv-hero__subtitle">
                Coordinación del Agrupamiento de Seguridad Vial · Michoacán
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-filter"></i> Filtros
                </div>

                <div class="sv-form">
                    <div class="sv-form__row">
                        <div class="sv-field">
                            <label>Desde</label>
                            <input type="date" id="f_desde" class="form-control form-control-sm">
                        </div>

                        <div class="sv-field">
                            <label>Hasta</label>
                            <input type="date" id="f_hasta" class="form-control form-control-sm">
                        </div>

                        <div class="sv-field">
                            <label>Sector</label>
                            <select id="f_sector" class="form-control form-control-sm">
                                <option value="">(Todos)</option>
                            </select>
                        </div>

                        <div class="sv-field">
                            <label>Tipo de Hecho</label>
                            <select id="f_tipo_hecho" class="form-control form-control-sm">
                                <option value="">(Todos)</option>
                            </select>
                        </div>

                        <div class="sv-field">
                            <label>Tipo de Vehículo</label>
                            <select id="f_veh_tipo" class="form-control form-control-sm">
                                <option value="">(Todos)</option>
                            </select>
                        </div>

                        <div class="sv-field">
                            <label>Lesionados</label>
                            <select id="f_con_lesionados" class="form-control form-control-sm">
                                <option value="">(Todos)</option>
                                <option value="1">Solo con lesionados</option>
                                <option value="0">Solo sin lesionados</option>
                            </select>
                        </div>

                        <div class="sv-field">
                            <label>Agrupar</label>
                            <select id="f_group" class="form-control form-control-sm">
                                <option value="day">Día</option>
                                <option value="month">Mes</option>
                            </select>
                        </div>

                        <div class="sv-field sv-field--actions">
                            <label>&nbsp;</label>
                            <button class="btn sv-btn w-100" id="btn_aplicar" type="button">
                                <i class="fas fa-sync-alt"></i> Aplicar
                            </button>
                        </div>
                    </div>

                    <div class="sv-form__row sv-form__row--small">
                        <div class="sv-field sv-field--wide">
                            <label>Búsqueda (folio, perito, unidad, calle, colonia…)</label>
                            <input
                                type="text"
                                id="f_q"
                                class="form-control form-control-sm"
                                placeholder="Ej: MOR/2026, ALONSO, PERIFERICO..."
                            >
                        </div>

                        <div class="sv-field">
                            <label>Placas</label>
                            <input type="text" id="f_veh_placas" class="form-control form-control-sm" placeholder="Ej: PGD">
                        </div>

                        <div class="sv-field">
                            <label>Serie</label>
                            <input type="text" id="f_veh_serie" class="form-control form-control-sm" placeholder="Ej: LJX">
                        </div>

                        <div class="sv-field">
                            <label>&nbsp;</label>
                            <a class="btn sv-btn sv-btn--ghost w-100" id="btn_export" href="#" target="_blank">
                                <i class="fa-solid fa-file-csv"></i> Export CSV
                            </a>
                        </div>

                        <div class="sv-field">
                            <label>&nbsp;</label>
                            <a class="btn sv-btn w-100" id="btn_export_mensual" href="#" target="_blank">
                                <i class="fa-solid fa-file-excel"></i> Excel mensual
                            </a>
                        </div>
                    </div>

                    <div class="sv-hint">
                        * Tip: si quieres “¿cuántos sedanes?”, selecciona <b>Tipo de Vehículo = SEDAN</b> y aplica.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="row">
        <div class="col-md-4 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-navy"><i class="fa-solid fa-car-burst"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Hechos</div>
                    <div class="sv-kpi__value" id="k_hechos">—</div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-maroon"><i class="fa-solid fa-user-injured"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Lesionados</div>
                    <div class="sv-kpi__value" id="k_lesionados">—</div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-teal"><i class="fa-solid fa-car-side"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Vehículos participantes</div>
                    <div class="sv-kpi__value" id="k_vehiculos">—</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row">
        <div class="col-lg-6 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-chart-line"></i> Hechos en el tiempo
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_hechos_time" height="130"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-chart-pie"></i> Tipos de Hecho (Top)
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_tipo_hecho" height="130"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-motorcycle"></i> Tipos de Vehículo (Top)
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_veh_tipo" height="130"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-list"></i> Hechos filtrados (drilldown)
                </div>
                <div class="sv-panel__body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Folio</th>
                                    <th>Fecha</th>
                                    <th>Sector</th>
                                    <th>Tipo</th>
                                    <th>Situación</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="tb_hechos">
                                <tr><td colspan="8" class="text-center text-muted">Sin datos…</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="sv-pager">
                        <button class="btn sv-btn sv-btn--ghost" id="btn_prev" type="button">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <div class="sv-pager__info" id="pg_info">—</div>
                        <button class="btn sv-btn sv-btn--ghost" id="btn_next" type="button">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/sv-dashboard.css') }}">
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
    const base = "{{ url('estadisticas-globales') }}";
    let page = 1;
    let lastPage = 1;

    const el = (id) => document.getElementById(id);
    const val = (id) => {
        const n = el(id);
        return n ? String(n.value ?? '').trim() : '';
    };

    function qsFromFilters(extra = {}){
        const params = new URLSearchParams();

        const desde = val('f_desde');
        const hasta = val('f_hasta');
        const sector = val('f_sector');
        const tipo_hecho = val('f_tipo_hecho');
        const veh_tipo = val('f_veh_tipo');
        const group = val('f_group');
        const q = val('f_q');
        const veh_placas = val('f_veh_placas');
        const veh_serie = val('f_veh_serie');
        const con_lesionados = val('f_con_lesionados');

        if (desde) params.set('desde', desde);
        if (hasta) params.set('hasta', hasta);
        if (sector) params.set('sector', sector);
        if (tipo_hecho) params.set('tipo_hecho', tipo_hecho);
        if (veh_tipo) params.set('veh_tipo', veh_tipo);
        if (con_lesionados !== '') params.set('con_lesionados', con_lesionados);
        if (group) params.set('group', group);
        if (q) params.set('q', q);
        if (veh_placas) params.set('veh_placas', veh_placas);
        if (veh_serie) params.set('veh_serie', veh_serie);

        for (const k in extra){
            if (extra[k] !== null && extra[k] !== undefined && String(extra[k]).trim() !== ''){
                params.set(k, extra[k]);
            }
        }

        return params.toString();
    }

    async function getJson(path, extra = {}){
        const qs = qsFromFilters(extra);
        const bust = `_=${Date.now()}`;

        const url = qs
            ? `${base}/${path}?${qs}&${bust}`
            : `${base}/${path}?${bust}`;

        const res = await fetch(url, {
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        });

        if (!res.ok) throw new Error(`HTTP ${res.status} en ${path}`);
        return await res.json();
    }

    function monthFromFilters(){
        const desde = val('f_desde');
        const hasta = val('f_hasta');

        if (hasta) {
            const h = new Date(hasta + 'T00:00:00');
            return { anio: h.getFullYear(), mes: h.getMonth() + 1 };
        }

        if (desde) {
            const d = new Date(desde + 'T00:00:00');
            return { anio: d.getFullYear(), mes: d.getMonth() + 1 };
        }

        const now = new Date();
        return { anio: now.getFullYear(), mes: now.getMonth() + 1 };
    }

    function setExportLinks(){
        const qs = qsFromFilters();

        // CSV (ya existe)
        const aCsv = el('btn_export');
        if (aCsv){
            aCsv.href = qs ? `${base}/export/hechos?${qs}` : `${base}/export/hechos`;
        }

        // Excel mensual (nuevo)
        const aXls = el('btn_export_mensual');
        if (aXls){
            const mm = monthFromFilters();
            const params = new URLSearchParams(qs ? qs : '');
            params.set('anio', String(mm.anio));
            params.set('mes', String(mm.mes));
            aXls.href = `${base}/export/mensual?${params.toString()}`;
        }
    }

    // ---------- Charts ----------
    let chTime = null, chTipoHecho = null, chVehTipo = null;

    function mountOrUpdateChart(canvasId, chartRef, type, labels, data){
        const canvas = el(canvasId);
        if (!canvas) return chartRef;

        if (chartRef){
            chartRef.data.labels = labels;
            chartRef.data.datasets[0].data = data;
            chartRef.update();
            return chartRef;
        }

        return new Chart(canvas, {
            type,
            data: { labels, datasets: [{ label: 'Total', data }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: (type === 'pie' || type === 'doughnut') ? {} : {
                    x: { ticks: { color: 'rgba(234,240,255,.75)' }, grid: { color: 'rgba(255,255,255,.08)' } },
                    y: { ticks: { color: 'rgba(234,240,255,.75)' }, grid: { color: 'rgba(255,255,255,.08)' } },
                }
            }
        });
    }

    // ---------- Table ----------
    function escapeHtml(str){
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderHechosTable(paginated){
        const tb = el('tb_hechos');
        if (!tb) return;

        if (!paginated || !paginated.data || paginated.data.length === 0){
            tb.innerHTML = `<tr><td colspan="8" class="text-center text-muted">Sin datos…</td></tr>`;
            if (el('pg_info')) el('pg_info').textContent = '—';
            lastPage = 1;
            return;
        }

        const rows = paginated.data.map(r => {
            const link = "{{ url('hechos') }}/" + r.id;
            return `
                <tr>
                    <td>${r.id}</td>
                    <td>${escapeHtml(r.folio_c5i ?? '')}</td>
                    <td>${escapeHtml(r.fecha ?? '')}</td>
                    <td>${escapeHtml(r.sector ?? '')}</td>
                    <td>${escapeHtml(r.tipo_hecho ?? '')}</td>
                    <td>${escapeHtml(r.situacion ?? '')}</td>
                    <td class="text-right">
                        <a class="btn btn-sm sv-btn" href="${link}">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    </td>
                </tr>
            `;
        }).join('');

        tb.innerHTML = rows;

        page = paginated.current_page || 1;
        lastPage = paginated.last_page || 1;
        if (el('pg_info')) el('pg_info').textContent = `Página ${page} de ${lastPage} · ${paginated.total} registros`;
    }

    // ---------- Fill selects ----------
    function fillSelect(selectId, rows){
        const s = el(selectId);
        if (!s) return;

        const keep = s.value;
        s.innerHTML =
            `<option value="">(Todos)</option>` +
            (rows || []).map(r =>
                `<option value="${escapeHtml(r.label)}">${escapeHtml(r.label)} (${r.total})</option>`
            ).join('');

        if (keep && [...s.options].some(o => o.value === keep)) s.value = keep;
    }

    async function loadAll(){
        setExportLinks();

        // KPIs
        const k = await getJson('kpis');
        if (el('k_hechos')) el('k_hechos').textContent = (k.totales?.hechos ?? 0);
        if (el('k_lesionados')) el('k_lesionados').textContent = (k.totales?.lesionados ?? 0);
        if (el('k_vehiculos')) el('k_vehiculos').textContent = (k.totales?.vehiculos ?? 0);

        // Selects
        const distSector = await getJson('series/sector');
        fillSelect('f_sector', distSector.series || []);

        const distTipoHecho = await getJson('series/tipo-hecho');
        fillSelect('f_tipo_hecho', distTipoHecho.series || []);

        const distVehTipo = await getJson('series/vehiculos/tipo');
        fillSelect('f_veh_tipo', distVehTipo.series || []);

        // Charts
        const time = await getJson('series/hechos');
        chTime = mountOrUpdateChart(
            'ch_hechos_time',
            chTime,
            'bar',
            (time.series || []).map(r => r.x),
            (time.series || []).map(r => Number(r.y || 0))
        );

        const tipoHecho = distTipoHecho.series || [];
        chTipoHecho = mountOrUpdateChart(
            'ch_tipo_hecho',
            chTipoHecho,
            'doughnut',
            tipoHecho.slice(0, 10).map(r => r.label),
            tipoHecho.slice(0, 10).map(r => Number(r.total || 0))
        );

        const vehTipo = distVehTipo.series || [];
        chVehTipo = mountOrUpdateChart(
            'ch_veh_tipo',
            chVehTipo,
            'doughnut',
            vehTipo.slice(0, 10).map(r => r.label),
            vehTipo.slice(0, 10).map(r => Number(r.total || 0))
        );

        // Table
        const hechos = await getJson('hechos', { page });
        renderHechosTable(hechos);
    }

    // Controls
    const btnAplicar = el('btn_aplicar');
    if (btnAplicar){
        btnAplicar.addEventListener('click', async function(e){
            e.preventDefault();
            page = 1;
            await loadAll();
        });
    }

    const btnPrev = el('btn_prev');
    if (btnPrev){
        btnPrev.addEventListener('click', async function(e){
            e.preventDefault();
            if (page <= 1) return;
            page--;
            const hechos = await getJson('hechos', { page });
            renderHechosTable(hechos);
        });
    }

    const btnNext = el('btn_next');
    if (btnNext){
        btnNext.addEventListener('click', async function(e){
            e.preventDefault();
            if (page >= lastPage) return;
            page++;
            const hechos = await getJson('hechos', { page });
            renderHechosTable(hechos);
        });
    }

    // Defaults: último mes
    function setDefaultDates(){
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');

        const end = `${yyyy}-${mm}-${dd}`;

        const startD = new Date(today);
        startD.setDate(startD.getDate() - 30);
        const sy = startD.getFullYear();
        const sm = String(startD.getMonth() + 1).padStart(2, '0');
        const sd = String(startD.getDate()).padStart(2, '0');
        const start = `${sy}-${sm}-${sd}`;

        if (el('f_desde')) el('f_desde').value = start;
        if (el('f_hasta')) el('f_hasta').value = end;
    }

    setDefaultDates();
    loadAll().catch(err => console.error('SV DASH ERROR:', err));
})();
</script>
@stop
