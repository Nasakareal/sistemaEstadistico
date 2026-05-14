@extends('adminlte::page')

@section('title', 'Estadísticas de Actividades')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Actividades · Siniestros · Delegaciones</span>
            </div>

            <div class="sv-hero__title">
                Estadísticas de Actividades
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
                            <label>Unidad</label>
                            <select id="f_unidad" class="form-control form-control-sm">
                                <option value="">Siniestros y Delegaciones</option>
                                <option value="1">Siniestros</option>
                                <option value="2">Delegaciones</option>
                            </select>
                        </div>

                        <div class="sv-field">
                            <label>Delegación</label>
                            <select id="f_delegacion" class="form-control form-control-sm" disabled>
                                <option value="">(Todas)</option>
                            </select>
                        </div>

                        <div class="sv-field">
                            <label>Destacamento</label>
                            <select id="f_destacamento" class="form-control form-control-sm">
                                <option value="">(Todos)</option>
                            </select>
                        </div>

                        <div class="sv-field">
                            <label>Categoría</label>
                            <select id="f_categoria" class="form-control form-control-sm">
                                <option value="">(Todas)</option>
                            </select>
                        </div>

                        <div class="sv-field">
                            <label>Subcategoría</label>
                            <select id="f_subcategoria" class="form-control form-control-sm">
                                <option value="">(Todas)</option>
                            </select>
                        </div>

                        <div class="sv-field">
                            <label>Municipio</label>
                            <select id="f_municipio" class="form-control form-control-sm">
                                <option value="">(Todos)</option>
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
                            <label>Búsqueda</label>
                            <input type="text" id="f_q" class="form-control form-control-sm" placeholder="Buscar actividad, ubicación, responsable, observaciones...">
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
                                <i class="fa-solid fa-file-excel"></i> Export Mensual
                            </a>
                        </div>

                        <div class="sv-field">
                            <label>&nbsp;</label>
                            <a class="btn sv-btn w-100" id="btn_export_fomento_mensual" href="#" target="_blank">
                                <i class="fa-solid fa-school"></i> Fomento mensual
                            </a>
                        </div>

                        <div class="sv-field">
                            <label>&nbsp;</label>
                            <a class="btn sv-btn sv-btn--ghost w-100" id="btn_export_fomento_anual" href="#" target="_blank">
                                <i class="fa-solid fa-calendar-days"></i> Fomento anual
                            </a>
                        </div>
                    </div>

                    <div class="sv-hint">
                        * Puedes consultar actividades de Siniestros, Delegaciones o ambas unidades. Al seleccionar Delegaciones se habilita el filtro por delegación.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-navy"><i class="fa-solid fa-clipboard-list"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Actividades</div>
                    <div class="sv-kpi__value" id="k_actividades">—</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-teal"><i class="fa-solid fa-users"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Personas alcanzadas</div>
                    <div class="sv-kpi__value" id="k_personas_alcanzadas">—</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-info"><i class="fa-solid fa-user-check"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Participantes</div>
                    <div class="sv-kpi__value" id="k_personas_participantes">—</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-maroon"><i class="fa-solid fa-road"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">KM recorridos</div>
                    <div class="sv-kpi__value" id="k_km_recorridos">—</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-chart-line"></i> Actividades en el tiempo
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_actividades_time" height="130"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-building-shield"></i> Actividades por unidad
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_unidad" height="130"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-layer-group"></i> Categorías
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_categoria" height="130"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-list"></i> Actividades filtradas
                </div>

                <div class="sv-panel__body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Unidad</th>
                                    <th>Delegación</th>
                                    <th>Categoría</th>
                                    <th>Subcategoría</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="tb_actividades">
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Sin datos…</td>
                                </tr>
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
    const base = "{{ url('estadisticas-actividades') }}";
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
        const unidad = val('f_unidad');
        const delegacion = val('f_delegacion');
        const destacamento = val('f_destacamento');
        const categoria = val('f_categoria');
        const subcategoria = val('f_subcategoria');
        const municipio = val('f_municipio');
        const group = val('f_group');
        const q = val('f_q');

        if (desde) params.set('desde', desde);
        if (hasta) params.set('hasta', hasta);
        if (unidad) params.set('unidad_org_id', unidad);
        if (unidad === '2' && delegacion) params.set('delegacion_id', delegacion);
        if (destacamento) params.set('destacamento_id', destacamento);
        if (categoria) params.set('actividad_categoria_id', categoria);
        if (subcategoria) params.set('actividad_subcategoria_id', subcategoria);
        if (municipio) params.set('municipio', municipio);
        if (group) params.set('group', group);
        if (q) params.set('q', q);
        params.set('cache_ttl', '0');

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

        const aCsv = el('btn_export');
        if (aCsv){
            aCsv.href = qs ? `${base}/export/actividades?${qs}` : `${base}/export/actividades`;
        }

        const aXls = el('btn_export_mensual');
        if (aXls){
            const mm = monthFromFilters();
            const params = new URLSearchParams(qs ? qs : '');
            params.set('anio', String(mm.anio));
            params.set('mes', String(mm.mes));
            aXls.href = `${base}/export/mensual?${params.toString()}`;
        }

        const aFomentoMensual = el('btn_export_fomento_mensual');
        if (aFomentoMensual){
            const mm = monthFromFilters();
            const params = new URLSearchParams(qs ? qs : '');
            params.set('anio', String(mm.anio));
            params.set('mes', String(mm.mes));
            aFomentoMensual.href = `${base}/export/fomento-cultura-vial?${params.toString()}`;
        }

        const aFomentoAnual = el('btn_export_fomento_anual');
        if (aFomentoAnual){
            const mm = monthFromFilters();
            const params = new URLSearchParams(qs ? qs : '');
            params.set('anio', String(mm.anio));
            params.delete('mes');
            aFomentoAnual.href = `${base}/export/fomento-cultura-vial?${params.toString()}`;
        }
    }

    function wireExportLinkUpdates(){
        const ids = [
            'f_desde',
            'f_hasta',
            'f_unidad',
            'f_delegacion',
            'f_destacamento',
            'f_categoria',
            'f_subcategoria',
            'f_municipio',
            'f_group',
            'f_q'
        ];

        ids.forEach(id => {
            const n = el(id);
            if (!n) return;

            n.addEventListener('change', setExportLinks);

            if (n.tagName === 'INPUT' && (n.type === 'text' || n.type === 'search')){
                n.addEventListener('keyup', setExportLinks);
            }
        });
    }

    function toggleDelegacion(){
        const unidad = val('f_unidad');
        const delegacion = el('f_delegacion');

        if (!delegacion) return;

        if (unidad === '2') {
            delegacion.disabled = false;
        } else {
            delegacion.value = '';
            delegacion.disabled = true;
        }

        setExportLinks();
    }

    let chTime = null;
    let chUnidad = null;
    let chCategoria = null;

    async function getOptionalJson(path, extra = {}, fallback = null){
        try {
            return await getJson(path, extra);
        } catch (err) {
            console.error(`SV ACTIVIDADES ${path}:`, err);
            return fallback;
        }
    }

    function chartPalette(total){
        const baseColors = [
            '#2DA8FF',
            '#19D38C',
            '#F6B84B',
            '#E23E73',
            '#7C5CFF',
            '#23C6D5',
            '#F2693D',
            '#8FD14F',
            '#D36DF2',
            '#4F8CFF'
        ];

        return Array.from({ length: Math.max(total, 1) }, (_, i) => baseColors[i % baseColors.length]);
    }

    function setChartFallback(canvas, show, message = 'Sin datos para graficar'){
        const parent = canvas.parentElement;
        if (!parent) return;

        let fallback = parent.querySelector('.sv-chart-empty');

        if (!fallback) {
            fallback = document.createElement('div');
            fallback.className = 'sv-chart-empty';
            parent.appendChild(fallback);
        }

        fallback.textContent = message;
        fallback.style.display = show ? 'flex' : 'none';
        canvas.style.display = show ? 'none' : 'block';
    }

    function mountOrUpdateChart(canvasId, chartRef, type, labels, data){
        const canvas = el(canvasId);
        if (!canvas) return chartRef;

        const safeLabels = Array.isArray(labels) ? labels : [];
        const safeData = Array.isArray(data) ? data.map(v => Number(v || 0)) : [];
        const hasData = safeData.some(v => v > 0);

        canvas.style.height = type === 'doughnut' ? '250px' : '270px';
        canvas.style.minHeight = canvas.style.height;

        if (typeof Chart === 'undefined') {
            setChartFallback(canvas, true, 'No se pudo cargar Chart.js');
            return chartRef;
        }

        setChartFallback(canvas, !hasData);

        if (!hasData) {
            return chartRef;
        }

        const colors = chartPalette(Math.max(safeLabels.length, safeData.length));
        const dataset = {
            label: 'Total',
            data: safeData,
            backgroundColor: type === 'bar'
                ? colors.map(color => `${color}CC`)
                : colors.map(color => `${color}D9`),
            borderColor: colors,
            borderWidth: 2,
            borderRadius: type === 'bar' ? 8 : 0,
            maxBarThickness: 42
        };

        if (chartRef){
            chartRef.data.labels = safeLabels;
            chartRef.data.datasets[0] = {
                ...chartRef.data.datasets[0],
                ...dataset
            };
            chartRef.resize();
            chartRef.update();
            return chartRef;
        }

        return new Chart(canvas, {
            type,
            data: {
                labels: safeLabels,
                datasets: [dataset]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: (type === 'pie' || type === 'doughnut') ? {} : {
                    x: {
                        ticks: { color: 'rgba(234,240,255,.75)' },
                        grid: { color: 'rgba(255,255,255,.08)' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: 'rgba(234,240,255,.75)', precision: 0 },
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

    function fillSelect(selectId, rows, defaultText = '(Todos)'){
        const s = el(selectId);
        if (!s) return;

        const keep = s.value;

        s.innerHTML =
            `<option value="">${defaultText}</option>` +
            (rows || []).map(r => {
                const value = r.value ?? r.id ?? r.label ?? '';
                const label = r.label ?? r.nombre ?? '';
                const total = Number(r.total || 0);

                return `<option value="${escapeHtml(value)}">${escapeHtml(label)}${total > 0 ? ` (${total})` : ''}</option>`;
            }).join('');

        if (keep && [...s.options].some(o => o.value === keep)){
            s.value = keep;
        }
    }

    function renderActividadesTable(paginated){
        const tb = el('tb_actividades');
        if (!tb) return;

        if (!paginated || !paginated.data || paginated.data.length === 0){
            tb.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Sin datos…</td></tr>`;
            if (el('pg_info')) el('pg_info').textContent = '—';
            lastPage = 1;
            return;
        }

        const rows = paginated.data.map(r => {
            const link = "{{ url('actividades') }}/" + r.id;

            return `
                <tr>
                    <td>${escapeHtml(r.id)}</td>
                    <td>${escapeHtml(r.fecha)}</td>
                    <td>${escapeHtml(r.unidad ?? r.unidad_nombre)}</td>
                    <td>${escapeHtml(r.delegacion ?? r.delegacion_nombre)}</td>
                    <td>${escapeHtml(r.categoria ?? r.categoria_nombre)}</td>
                    <td>${escapeHtml(r.subcategoria ?? r.subcategoria_nombre)}</td>
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

        if (el('pg_info')){
            el('pg_info').textContent = `Página ${page} de ${lastPage} · ${paginated.total} registros`;
        }
    }

    async function loadAll(){
        toggleDelegacion();
        setExportLinks();

        const k = await getJson('kpis');

        if (el('k_actividades')) el('k_actividades').textContent = k.totales?.actividades ?? 0;
        if (el('k_personas_alcanzadas')) el('k_personas_alcanzadas').textContent = k.totales?.personas_alcanzadas ?? 0;
        if (el('k_personas_participantes')) el('k_personas_participantes').textContent = k.totales?.personas_participantes ?? 0;
        if (el('k_km_recorridos')) el('k_km_recorridos').textContent = k.totales?.km_recorridos ?? 0;

        const cat = await getOptionalJson('catalogos/categorias', {}, []);
        fillSelect('f_categoria', cat.map(r => ({
            value: r.id,
            label: r.nombre
        })), '(Todas)');

        const subcat = await getOptionalJson('catalogos/subcategorias', {}, []);
        fillSelect('f_subcategoria', subcat.map(r => ({
            value: r.id,
            label: r.nombre
        })), '(Todas)');

        const deleg = await getOptionalJson('catalogos/delegaciones', {}, []);
        fillSelect('f_delegacion', deleg.map(r => ({
            value: r.id,
            label: (r.clave ? r.clave + ' - ' : '') + r.nombre
        })), '(Todas)');

        const dest = await getOptionalJson('catalogos/destacamentos', {}, []);
        fillSelect('f_destacamento', dest.map(r => ({
            value: r.id,
            label: (r.clave ? r.clave + ' - ' : '') + r.nombre
        })), '(Todos)');

        const distMunicipio = await getOptionalJson('series/municipio', {}, { series: [] });
        fillSelect('f_municipio', distMunicipio.series || [], '(Todos)');

        const time = await getOptionalJson('series/actividades', {}, { series: [] });
        chTime = mountOrUpdateChart(
            'ch_actividades_time',
            chTime,
            'bar',
            (time.series || []).map(r => r.x),
            (time.series || []).map(r => Number(r.y || 0))
        );

        const distUnidad = await getOptionalJson('series/unidad', {}, { series: [] });
        const unidadRows = distUnidad.series || [];
        chUnidad = mountOrUpdateChart(
            'ch_unidad',
            chUnidad,
            'doughnut',
            unidadRows.slice(0, 10).map(r => r.label),
            unidadRows.slice(0, 10).map(r => Number(r.total || 0))
        );

        const distCategoria = await getOptionalJson('series/categoria', {}, { series: [] });
        const categoriaRows = distCategoria.series || [];
        chCategoria = mountOrUpdateChart(
            'ch_categoria',
            chCategoria,
            'bar',
            categoriaRows.slice(0, 10).map(r => r.label),
            categoriaRows.slice(0, 10).map(r => Number(r.total || 0))
        );

        const actividades = await getJson('actividades', { page });
        renderActividadesTable(actividades);

        toggleDelegacion();
    }

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
            const actividades = await getJson('actividades', { page });
            renderActividadesTable(actividades);
        });
    }

    const btnNext = el('btn_next');
    if (btnNext){
        btnNext.addEventListener('click', async function(e){
            e.preventDefault();
            if (page >= lastPage) return;
            page++;
            const actividades = await getJson('actividades', { page });
            renderActividadesTable(actividades);
        });
    }

    const unidadSelect = el('f_unidad');
    if (unidadSelect){
        unidadSelect.addEventListener('change', function(){
            toggleDelegacion();
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
    toggleDelegacion();
    setExportLinks();
    loadAll().catch(err => console.error('SV ACTIVIDADES ERROR:', err));
})();
</script>
@stop
