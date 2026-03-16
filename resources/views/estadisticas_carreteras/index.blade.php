@extends('adminlte::page')

@section('title', 'Estadísticas Carreteras')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Carreteras · Operativos · Puestas a Disposición</span>
            </div>

            <div class="sv-hero__title">
                Estadísticas Carreteras
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
                            <label>Agrupar</label>
                            <select id="f_group" class="form-control form-control-sm">
                                <option value="day">Día</option>
                                <option value="month">Mes</option>
                            </select>
                        </div>

                        <div class="sv-field">
                            <label>Tipo de Puesta</label>
                            <input type="text" id="f_tipo_puesta" class="form-control form-control-sm" placeholder="Ej: PERSONA, VEHICULO, MIXTA">
                        </div>

                        <div class="sv-field">
                            <label>Motivo de Puesta</label>
                            <input type="text" id="f_motivo" class="form-control form-control-sm" placeholder="Ej: ROBO, DELITO">
                        </div>

                        <div class="sv-field">
                            <label>ID Catálogo Operativo</label>
                            <input type="number" id="f_operativo_catalogo_id" class="form-control form-control-sm" placeholder="Ej: 1">
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
                            <label>Búsqueda general</label>
                            <input
                                type="text"
                                id="f_q"
                                class="form-control form-control-sm"
                                placeholder="Ej: tramo, descripción, policía, motivo, nombre..."
                            >
                        </div>

                        <div class="sv-field">
                            <label>Listado</label>
                            <select id="f_listado" class="form-control form-control-sm">
                                <option value="operativos">Operativos</option>
                                <option value="puestas-disposicion">Puestas a disposición</option>
                            </select>
                        </div>

                        <div class="sv-field">
                            <label>&nbsp;</label>
                            <a class="btn sv-btn sv-btn--ghost w-100" id="btn_export_operativos" href="#" target="_blank">
                                <i class="fa-solid fa-file-csv"></i> Operativos CSV
                            </a>
                        </div>

                        <div class="sv-field">
                            <label>&nbsp;</label>
                            <a class="btn sv-btn sv-btn--ghost w-100" id="btn_export_puestas" href="#" target="_blank">
                                <i class="fa-solid fa-file-csv"></i> Puestas CSV
                            </a>
                        </div>
                    </div>

                    <div class="sv-hint">
                        * Los totales de personas, vehículos y objetos se calculan desde los registros relacionados de cada puesta a disposición.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-2 col-md-4 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-maroon"><i class="fa-solid fa-shield-halved"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Operativos</div>
                    <div class="sv-kpi__value" id="k_operativos">—</div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-teal"><i class="fas fa-gavel"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Puestas</div>
                    <div class="sv-kpi__value" id="k_puestas">—</div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-navy"><i class="fa-solid fa-users"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Personas</div>
                    <div class="sv-kpi__value" id="k_personas">—</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-purple"><i class="fa-solid fa-car-side"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Vehículos</div>
                    <div class="sv-kpi__value" id="k_vehiculos">—</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-olive"><i class="fa-solid fa-box-open"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Objetos</div>
                    <div class="sv-kpi__value" id="k_objetos">—</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-chart-line"></i> Operativos en el tiempo
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_operativos_time" height="140"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-chart-line"></i> Puestas en el tiempo
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_puestas_time" height="140"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-chart-pie"></i> Tipos de Puesta
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_tipo_puesta" height="140"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-chart-pie"></i> Calidad de Personas
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_calidad_persona" height="140"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-chart-bar"></i> Motivos de Puesta
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_motivo" height="140"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-chart-bar"></i> Tipos de Objeto
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_tipo_objeto" height="140"></canvas>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="sv-panel">
                <div class="sv-panel__title d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-list"></i> Drilldown</span>
                    <span id="drilldown_title" class="text-muted small">Listado de Operativos</span>
                </div>

                <div class="sv-panel__body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead id="tb_head">
                                <tr>
                                    <th>UUID</th>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Descripción</th>
                                    <th>Operativos</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="tb_data">
                                <tr><td colspan="6" class="text-center text-muted">Sin datos…</td></tr>
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
    const base = "{{ url('estadisticas-carreteras') }}";
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
        const group = val('f_group');
        const q = val('f_q');
        const tipo_puesta = val('f_tipo_puesta');
        const motivo = val('f_motivo');
        const operativo_catalogo_id = val('f_operativo_catalogo_id');

        if (desde) params.set('desde', desde);
        if (hasta) params.set('hasta', hasta);
        if (group) params.set('group', group);
        if (q) params.set('q', q);
        if (tipo_puesta) params.set('tipo_puesta', tipo_puesta);
        if (motivo) params.set('motivo', motivo);
        if (operativo_catalogo_id) params.set('operativo_catalogo_id', operativo_catalogo_id);

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

    function setExportLinks(){
        const qs = qsFromFilters();

        const a2 = el('btn_export_operativos');
        if (a2){
            a2.href = qs ? `${base}/export/operativos?${qs}` : `${base}/export/operativos`;
        }

        const a3 = el('btn_export_puestas');
        if (a3){
            a3.href = qs ? `${base}/export/puestas-disposicion?${qs}` : `${base}/export/puestas-disposicion`;
        }
    }

    function wireExportLinkUpdates(){
        const ids = [
            'f_desde','f_hasta','f_group','f_q','f_tipo_puesta',
            'f_motivo','f_operativo_catalogo_id','f_listado'
        ];

        ids.forEach(id => {
            const n = el(id);
            if (!n) return;

            n.addEventListener('change', setExportLinks);

            if (n.tagName === 'INPUT' && (n.type === 'text' || n.type === 'search' || n.type === 'number')){
                n.addEventListener('keyup', setExportLinks);
            }
        });
    }

    let chOperativos = null;
    let chPuestas = null;
    let chTipoPuesta = null;
    let chCalidadPersona = null;
    let chMotivo = null;
    let chTipoObjeto = null;

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
            data: {
                labels,
                datasets: [{ label: 'Total', data }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: (type === 'pie' || type === 'doughnut') ? {} : {
                    x: {
                        ticks: { color: 'rgba(234,240,255,.75)' },
                        grid: { color: 'rgba(255,255,255,.08)' }
                    },
                    y: {
                        ticks: { color: 'rgba(234,240,255,.75)' },
                        grid: { color: 'rgba(255,255,255,.08)' }
                    }
                }
            }
        });
    }

    function escapeHtml(str){
        return String(str ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function currentListado(){
        return val('f_listado') || 'operativos';
    }

    function renderTableHead(mode){
        const th = el('tb_head');
        const title = el('drilldown_title');
        if (!th) return;

        if (mode === 'operativos'){
            th.innerHTML = `
                <tr>
                    <th>UUID</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Descripción</th>
                    <th>Operativos</th>
                    <th></th>
                </tr>
            `;
            if (title) title.textContent = 'Listado de Operativos';
            return;
        }

        th.innerHTML = `
            <tr>
                <th>No.</th>
                <th>Fecha</th>
                <th>Motivo</th>
                <th>Personas</th>
                <th>Vehículos</th>
                <th>Objetos</th>
                <th></th>
            </tr>
        `;
        if (title) title.textContent = 'Listado de Puestas a Disposición';
    }

    function renderOperativosTable(paginated){
        const tb = el('tb_data');
        if (!tb) return;

        if (!paginated || !paginated.data || paginated.data.length === 0){
            tb.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Sin datos…</td></tr>`;
            if (el('pg_info')) el('pg_info').textContent = '—';
            lastPage = 1;
            return;
        }

        const rows = paginated.data.map(r => {
            const link = "{{ url('operativos') }}/" + encodeURIComponent(r.captura_uuid);

            return `
                <tr>
                    <td>${escapeHtml(r.captura_uuid ?? '')}</td>
                    <td>${escapeHtml(r.fecha ?? '')}</td>
                    <td>${escapeHtml(r.hora ?? '')}</td>
                    <td>${escapeHtml(r.descripcion ?? r.lugar ?? '')}</td>
                    <td>${escapeHtml(r.total_operativos ?? 0)}</td>
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

    function renderPuestasTable(paginated){
        const tb = el('tb_data');
        if (!tb) return;

        if (!paginated || !paginated.data || paginated.data.length === 0){
            tb.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Sin datos…</td></tr>`;
            if (el('pg_info')) el('pg_info').textContent = '—';
            lastPage = 1;
            return;
        }

        const rows = paginated.data.map(r => {
            const link = "{{ url('puestas-disposicion') }}/" + r.id;

            return `
                <tr>
                    <td>${escapeHtml(r.numero_puesta ?? '')}</td>
                    <td>${escapeHtml(r.fecha_puesta ?? '')}</td>
                    <td>${escapeHtml(r.motivo ?? '')}</td>
                    <td>${escapeHtml(r.total_personas ?? 0)}</td>
                    <td>${escapeHtml(r.total_vehiculos ?? 0)}</td>
                    <td>${escapeHtml(r.total_objetos ?? 0)}</td>
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

    async function loadDrilldown(){
        const mode = currentListado();
        renderTableHead(mode);

        if (mode === 'operativos'){
            const data = await getJson('operativos', { page });
            renderOperativosTable(data);
            return;
        }

        const data = await getJson('puestas-disposicion', { page });
        renderPuestasTable(data);
    }

    async function loadAll(){
        setExportLinks();

        const k = await getJson('kpis');

        if (el('k_operativos')) el('k_operativos').textContent = (k.totales?.operativos ?? 0);
        if (el('k_puestas')) el('k_puestas').textContent = (k.totales?.puestas_disposicion ?? 0);
        if (el('k_personas')) el('k_personas').textContent = (k.totales?.personas ?? 0);
        if (el('k_vehiculos')) el('k_vehiculos').textContent = (k.totales?.vehiculos ?? 0);
        if (el('k_objetos')) el('k_objetos').textContent = (k.totales?.objetos ?? 0);

        const operativosTime = await getJson('series/operativos');
        chOperativos = mountOrUpdateChart(
            'ch_operativos_time',
            chOperativos,
            'bar',
            (operativosTime.series || []).map(r => r.x),
            (operativosTime.series || []).map(r => Number(r.y || 0))
        );

        const puestasTime = await getJson('series/puestas-disposicion');
        chPuestas = mountOrUpdateChart(
            'ch_puestas_time',
            chPuestas,
            'bar',
            (puestasTime.series || []).map(r => r.x),
            (puestasTime.series || []).map(r => Number(r.y || 0))
        );

        const tipoPuesta = (k.top?.tipo_puesta || []).slice(0, 10);
        chTipoPuesta = mountOrUpdateChart(
            'ch_tipo_puesta',
            chTipoPuesta,
            'doughnut',
            tipoPuesta.map(r => r.label),
            tipoPuesta.map(r => Number(r.total || 0))
        );

        const calidadPersona = (k.top?.calidad_persona || []).slice(0, 10);
        chCalidadPersona = mountOrUpdateChart(
            'ch_calidad_persona',
            chCalidadPersona,
            'doughnut',
            calidadPersona.map(r => r.label),
            calidadPersona.map(r => Number(r.total || 0))
        );

        const motivo = (k.top?.motivo || []).slice(0, 10);
        chMotivo = mountOrUpdateChart(
            'ch_motivo',
            chMotivo,
            'bar',
            motivo.map(r => r.label),
            motivo.map(r => Number(r.total || 0))
        );

        const tipoObjeto = (k.top?.tipo_objeto || []).slice(0, 10);
        chTipoObjeto = mountOrUpdateChart(
            'ch_tipo_objeto',
            chTipoObjeto,
            'bar',
            tipoObjeto.map(r => r.label),
            tipoObjeto.map(r => Number(r.total || 0))
        );

        await loadDrilldown();
    }

    const btnAplicar = el('btn_aplicar');
    if (btnAplicar){
        btnAplicar.addEventListener('click', async function(e){
            e.preventDefault();
            page = 1;
            await loadAll();
        });
    }

    const listado = el('f_listado');
    if (listado){
        listado.addEventListener('change', async function(){
            page = 1;
            await loadDrilldown();
            setExportLinks();
        });
    }

    const btnPrev = el('btn_prev');
    if (btnPrev){
        btnPrev.addEventListener('click', async function(e){
            e.preventDefault();
            if (page <= 1) return;
            page--;
            await loadDrilldown();
        });
    }

    const btnNext = el('btn_next');
    if (btnNext){
        btnNext.addEventListener('click', async function(e){
            e.preventDefault();
            if (page >= lastPage) return;
            page++;
            await loadDrilldown();
        });
    }

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
    wireExportLinkUpdates();
    setExportLinks();
    loadAll().catch(err => console.error('SV CARRETERAS DASH ERROR:', err));
})();
</script>
@stop
