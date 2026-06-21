@extends('adminlte::page')

@section('title', 'Estadísticas de Aseguramientos')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Aseguramientos · Puestas a disposición · Todas las unidades</span>
            </div>

            <div class="sv-hero__title">
                Estadísticas de Aseguramientos
            </div>

            <div class="sv-hero__subtitle">
                Conteo visual, fuente exacta y tarjeta compartible por unidad
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
                            <label>Hora desde</label>
                            <input type="time" id="f_hora_desde" class="form-control form-control-sm">
                        </div>

                        <div class="sv-field">
                            <label>Hasta</label>
                            <input type="date" id="f_hasta" class="form-control form-control-sm">
                        </div>

                        <div class="sv-field">
                            <label>Hora hasta</label>
                            <input type="time" id="f_hora_hasta" class="form-control form-control-sm">
                        </div>

                        <div class="sv-field">
                            <label>Unidad</label>
                            <select id="f_unidad" class="form-control form-control-sm">
                                <option value="">Todas las unidades visibles</option>
                            </select>
                        </div>

                        <div class="sv-field">
                            <label>Delegación</label>
                            <select id="f_delegacion" class="form-control form-control-sm">
                                <option value="">(Todas)</option>
                            </select>
                        </div>

                        <div class="sv-field">
                            <label>Destacamento</label>
                            <select id="f_destacamento" class="form-control form-control-sm">
                                <option value="">(Todos)</option>
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
                            <input type="text" id="f_q" class="form-control form-control-sm" placeholder="Buscar por folio, motivo, vehículo, objeto, persona, lugar...">
                        </div>
                    </div>

                    <div class="sv-hint">
                        * El tablero cuenta completas las puestas a disposición y separa cada número por rubro. El criterio relevante aplica solo al bloque de hechos/siniestros.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-2 col-md-4 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-navy"><i class="fa-solid fa-folder-open"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Puestas revisadas</div>
                    <div class="sv-kpi__value" id="k_puestas">—</div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-teal"><i class="fa-solid fa-users"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Personas</div>
                    <div class="sv-kpi__value" id="k_personas">—</div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-purple"><i class="fa-solid fa-car-side"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Vehículos</div>
                    <div class="sv-kpi__value" id="k_vehiculos">—</div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-danger"><i class="fa-solid fa-shield-halved"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Armas</div>
                    <div class="sv-kpi__value" id="k_armas">—</div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-warning"><i class="fa-solid fa-vial"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Droga / alcohol</div>
                    <div class="sv-kpi__value" id="k_drogas">—</div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-12">
            <div class="sv-kpi">
                <div class="sv-kpi__icon bg-success"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div class="sv-kpi__body">
                    <div class="sv-kpi__label">Dinero</div>
                    <div class="sv-kpi__value" id="k_dinero">—</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-chart-column"></i> Rubros principales
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_grupos" height="140"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-car-side"></i> Vehículos por motivo
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_vehiculos" height="140"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-users"></i> Personas
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_personas" height="140"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-shield-halved"></i> Armas
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_armas" height="140"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-vial"></i> Droga / alcohol
                </div>
                <div class="sv-panel__body">
                    <canvas id="ch_drogas" height="140"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-5 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-share-nodes"></i> Tarjeta compartible</span>
                    <span class="aseg-pill" id="periodo_label">—</span>
                </div>
                <div class="sv-panel__body">
                    <textarea id="share_card" class="aseg-share" rows="18" readonly></textarea>

                    <div class="aseg-actions">
                        <button class="btn sv-btn" id="btn_share" type="button">
                            <i class="fa-solid fa-share-from-square"></i> Compartir
                        </button>
                        <button class="btn sv-btn sv-btn--ghost" id="btn_copy" type="button">
                            <i class="fa-solid fa-copy"></i> Copiar
                        </button>
                    </div>
                    <div class="sv-hint" id="share_status"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 col-12">
            <div class="sv-panel">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-magnifying-glass-chart"></i> Exactamente qué está contando
                </div>
                <div class="sv-panel__body">
                    <div class="sv-form p-0">
                        <div class="sv-form__row sv-form__row--small">
                            <div class="sv-field sv-field--wide">
                                <label>Rubro contado</label>
                                <select id="f_detalle" class="form-control form-control-sm"></select>
                            </div>
                        </div>
                    </div>

                    <div class="aseg-definition" id="detalle_definicion">—</div>

                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Fuente</th>
                                    <th>Fecha</th>
                                    <th>Unidad</th>
                                    <th>Clasificación</th>
                                    <th>Descripción</th>
                                    <th>Cantidad</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="tb_detalle">
                                <tr><td colspan="7" class="text-center text-muted">Sin datos…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/sv-dashboard.css') }}">
<style>
    .aseg-share{
        width:100%;
        border-radius:14px;
        border:1px solid rgba(255,255,255,.14);
        background:rgba(12,16,28,.58);
        color:rgba(234,240,255,.9);
        padding:12px;
        font-family:Consolas, Menlo, Monaco, monospace;
        font-size:12.5px;
        resize:vertical;
        outline:none;
    }

    .aseg-actions{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        margin-top:10px;
    }

    .aseg-pill{
        display:inline-flex;
        align-items:center;
        border-radius:999px;
        padding:5px 9px;
        background:rgba(0,0,0,.2);
        border:1px solid rgba(255,255,255,.12);
        color:rgba(234,240,255,.7);
        font-weight:800;
        font-size:11px;
    }

    .aseg-definition{
        margin-top:8px;
        padding:10px 12px;
        border-radius:14px;
        background:rgba(45,168,255,.10);
        border:1px solid rgba(45,168,255,.18);
        color:rgba(234,240,255,.78);
        font-weight:700;
        font-size:12.5px;
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
    const base = "{{ url('estadisticas-aseguramientos') }}";
    const catalogos = @json($catalogos);
    const initialParams = new URLSearchParams(window.location.search);
    let resumenActual = null;
    let chGrupos = null;
    let chVehiculos = null;
    let chPersonas = null;
    let chArmas = null;
    let chDrogas = null;

    const el = (id) => document.getElementById(id);
    const val = (id) => {
        const node = el(id);
        return node ? String(node.value ?? '').trim() : '';
    };

    function escapeHtml(str){
        return String(str ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function pad2(value){
        return String(value).padStart(2, '0');
    }

    function setDefaultDates(){
        const today = new Date();
        const start = new Date(today);
        start.setDate(start.getDate() - 30);

        if (el('f_desde')) el('f_desde').value = `${start.getFullYear()}-${pad2(start.getMonth() + 1)}-${pad2(start.getDate())}`;
        if (el('f_hasta')) el('f_hasta').value = `${today.getFullYear()}-${pad2(today.getMonth() + 1)}-${pad2(today.getDate())}`;
        if (el('f_hora_desde')) el('f_hora_desde').value = '00:00';
        if (el('f_hora_hasta')) el('f_hora_hasta').value = '23:59';
    }

    function fillSelect(selectId, rows, defaultText, formatter){
        const select = el(selectId);
        if (!select) return;

        const keep = select.value;
        select.innerHTML = `<option value="">${defaultText}</option>` + (rows || []).map(row => {
            const value = row.id ?? row.value ?? '';
            const label = formatter ? formatter(row) : (row.nombre ?? row.label ?? value);
            return `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`;
        }).join('');

        if (keep && Array.from(select.options).some(option => option.value === keep)) {
            select.value = keep;
        }
    }

    function fillCatalogos(){
        fillSelect('f_unidad', catalogos.unidades || [], 'Todas las unidades visibles', row => row.nombre);
        fillSelect('f_delegacion', catalogos.delegaciones || [], '(Todas)', row => `${row.clave ? row.clave + ' - ' : ''}${row.nombre}`);
        fillDestacamentos();
    }

    function applyInitialParams(){
        const dateMap = {
            f_desde: 'desde',
            f_hora_desde: 'hora_desde',
            f_hasta: 'hasta',
            f_hora_hasta: 'hora_hasta',
            f_delegacion: 'delegacion_id',
            f_destacamento: 'destacamento_id',
            f_q: 'q'
        };

        Object.entries(dateMap).forEach(([id, key]) => {
            const node = el(id);
            const value = initialParams.get(key);
            if (node && value) node.value = value;
        });

        const unidadId = initialParams.get('unidad_id') || initialParams.get('unidad_org_id');
        const unidadSlug = initialParams.get('unidad_slug');
        const unidadSelect = el('f_unidad');

        if (unidadSelect && unidadId) {
            unidadSelect.value = unidadId;
        } else if (unidadSelect && unidadSlug) {
            const row = (catalogos.unidades || []).find(item => item.slug === unidadSlug);
            if (row) unidadSelect.value = String(row.id);
        }

        fillDestacamentos();
    }

    function fillDestacamentos(){
        const unidadId = val('f_unidad');
        const delegacionId = val('f_delegacion');
        let rows = catalogos.destacamentos || [];

        if (unidadId) {
            rows = rows.filter(row => String(row.unidad_id ?? '') === unidadId);
        }

        if (delegacionId && catalogos.destacamentos_tienen_delegacion) {
            rows = rows.filter(row => String(row.delegacion_id ?? '') === delegacionId);
        }

        fillSelect('f_destacamento', rows, '(Todos)', row => `${row.clave ? row.clave + ' - ' : ''}${row.nombre}`);
    }

    function qsFromFilters(){
        const params = new URLSearchParams();
        const keys = {
            f_desde: 'desde',
            f_hora_desde: 'hora_desde',
            f_hasta: 'hasta',
            f_hora_hasta: 'hora_hasta',
            f_unidad: 'unidad_id',
            f_delegacion: 'delegacion_id',
            f_destacamento: 'destacamento_id',
            f_q: 'q'
        };

        Object.entries(keys).forEach(([id, key]) => {
            const value = val(id);
            if (value) params.set(key, value);
        });

        return params.toString();
    }

    async function getResumen(){
        const qs = qsFromFilters();
        const url = qs ? `${base}/resumen?${qs}&_=${Date.now()}` : `${base}/resumen?_=${Date.now()}`;
        const response = await fetch(url, {
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        return await response.json();
    }

    function formatNumber(value){
        const num = Number(value || 0);
        if (Math.abs(num - Math.round(num)) < 0.00001) {
            return String(Math.round(num));
        }
        return num.toLocaleString('es-MX', { maximumFractionDigits: 2 });
    }

    function formatMoney(value){
        return Number(value || 0).toLocaleString('es-MX', {
            style: 'currency',
            currency: 'MXN'
        });
    }

    function setKpis(resumen){
        const k = resumen.kpis || {};
        if (el('k_puestas')) el('k_puestas').textContent = formatNumber(k.puestas?.value);
        if (el('k_personas')) el('k_personas').textContent = formatNumber(k.personas?.value);
        if (el('k_vehiculos')) el('k_vehiculos').textContent = formatNumber(k.vehiculos?.value);
        if (el('k_armas')) el('k_armas').textContent = formatNumber(k.armas?.value);
        if (el('k_drogas')) el('k_drogas').textContent = formatNumber(k.drogas?.value);
        if (el('k_dinero')) el('k_dinero').textContent = formatMoney(k.dinero?.value);
    }

    function chartPalette(total){
        const colors = ['#2DA8FF', '#19D38C', '#F6B84B', '#E23E73', '#7C5CFF', '#23C6D5', '#F2693D', '#8FD14F'];
        return Array.from({ length: Math.max(total, 1) }, (_, index) => colors[index % colors.length]);
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

    function mountOrUpdateChart(canvasId, chartRef, type, rows){
        const canvas = el(canvasId);
        if (!canvas) return chartRef;

        const labels = (rows || []).map(row => row.label);
        const values = (rows || []).map(row => Number(row.total || 0));
        const hasData = values.some(value => value > 0);

        if (typeof Chart === 'undefined') {
            setChartFallback(canvas, true, 'No se pudo cargar Chart.js');
            return chartRef;
        }

        setChartFallback(canvas, !hasData);

        if (!hasData) return chartRef;

        const colors = chartPalette(labels.length);
        const dataset = {
            label: 'Total',
            data: values,
            backgroundColor: type === 'bar' ? colors.map(color => `${color}CC`) : colors.map(color => `${color}D9`),
            borderColor: colors,
            borderWidth: 2,
            borderRadius: type === 'bar' ? 8 : 0,
            maxBarThickness: 44
        };

        if (chartRef) {
            chartRef.data.labels = labels;
            chartRef.data.datasets[0] = dataset;
            chartRef.resize();
            chartRef.update();
            return chartRef;
        }

        return new Chart(canvas, {
            type,
            data: { labels, datasets: [dataset] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: (type === 'pie' || type === 'doughnut') ? {} : {
                    x: { ticks: { color: 'rgba(234,240,255,.75)' }, grid: { color: 'rgba(255,255,255,.08)' } },
                    y: { beginAtZero: true, ticks: { color: 'rgba(234,240,255,.75)', precision: 0 }, grid: { color: 'rgba(255,255,255,.08)' } }
                }
            }
        });
    }

    function setCharts(resumen){
        const charts = resumen.charts || {};
        chGrupos = mountOrUpdateChart('ch_grupos', chGrupos, 'bar', charts.grupos || []);
        chVehiculos = mountOrUpdateChart('ch_vehiculos', chVehiculos, 'bar', charts.vehiculos || []);
        chPersonas = mountOrUpdateChart('ch_personas', chPersonas, 'doughnut', charts.personas || []);
        chArmas = mountOrUpdateChart('ch_armas', chArmas, 'bar', charts.armas || []);
        chDrogas = mountOrUpdateChart('ch_drogas', chDrogas, 'bar', charts.drogas || []);
    }

    function setTarjeta(resumen){
        if (el('share_card')) el('share_card').value = resumen.tarjeta?.texto || '';
        if (el('periodo_label')) {
            const dateLabel = (value) => {
                if (!value) return '';
                if (typeof value === 'string') return value.substring(0, 16).replace('T', ' ');
                if (value.date) return String(value.date).substring(0, 16);
                return '';
            };
            const inicio = dateLabel(resumen.inicio);
            const fin = dateLabel(resumen.fin);
            el('periodo_label').textContent = inicio && fin ? `${inicio} a ${fin}` : 'Periodo filtrado';
        }
    }

    function setDetalleOptions(resumen){
        const select = el('f_detalle');
        if (!select) return;

        const keep = select.value;
        const groups = resumen.detalle_grupos || [];
        select.innerHTML = groups.map(group => {
            return `<option value="${escapeHtml(group.key)}">${escapeHtml(group.label)} (${formatNumber(group.total)})</option>`;
        }).join('');

        if (keep && groups.some(group => group.key === keep)) {
            select.value = keep;
        } else {
            const withData = groups.find(group => Number(group.total || 0) > 0);
            select.value = withData ? withData.key : (groups[0]?.key || '');
        }
    }

    function renderDetalle(){
        if (!resumenActual) return;

        const key = val('f_detalle');
        const rows = resumenActual.detalles?.[key] || [];
        const definition = resumenActual.definiciones?.[key] || 'Sin definición disponible.';
        const tbody = el('tb_detalle');

        if (el('detalle_definicion')) el('detalle_definicion').textContent = definition;
        if (!tbody) return;

        if (!rows.length) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Sin registros para este rubro en el filtro actual.</td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map(row => {
            const source = row.puesta_id
                ? `Puesta #${row.numero_puesta || row.puesta_id}`
                : `Hecho #${row.hecho_id || ''}`;
            const cantidad = `${formatNumber(row.cantidad)} ${row.unidad_medida || ''}`.trim();
            const ubicacion = [row.unidad, row.delegacion, row.destacamento].filter(Boolean).join(' / ');

            return `
                <tr>
                    <td>${escapeHtml(source)}</td>
                    <td>${escapeHtml([row.fecha, row.hora].filter(Boolean).join(' '))}</td>
                    <td>${escapeHtml(ubicacion)}</td>
                    <td>${escapeHtml(row.clasificacion || '')}</td>
                    <td>${escapeHtml(row.descripcion || row.motivo || '')}</td>
                    <td>${escapeHtml(cantidad)}</td>
                    <td class="text-right">
                        ${row.url ? `<a href="${escapeHtml(row.url)}" class="btn btn-sm sv-btn"><i class="fa-solid fa-eye"></i></a>` : ''}
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function copyText(text){
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return;
        }

        const area = el('share_card');
        if (!area) return;
        area.focus();
        area.select();
        document.execCommand('copy');
    }

    async function copiarTarjeta(){
        const text = el('share_card')?.value || '';
        await copyText(text);
        if (el('share_status')) el('share_status').textContent = 'Tarjeta copiada al portapapeles.';
    }

    async function compartirTarjeta(){
        const text = el('share_card')?.value || '';

        if (navigator.share) {
            await navigator.share({ text });
            if (el('share_status')) el('share_status').textContent = 'Tarjeta enviada al compartido nativo.';
            return;
        }

        await copiarTarjeta();
    }

    async function loadAll(){
        if (el('share_status')) el('share_status').textContent = '';
        resumenActual = await getResumen();
        setKpis(resumenActual);
        setCharts(resumenActual);
        setTarjeta(resumenActual);
        setDetalleOptions(resumenActual);
        renderDetalle();
    }

    el('btn_aplicar')?.addEventListener('click', async function(event){
        event.preventDefault();
        await loadAll();
    });

    el('f_detalle')?.addEventListener('change', renderDetalle);
    el('f_unidad')?.addEventListener('change', function(){
        fillDestacamentos();
    });
    el('f_delegacion')?.addEventListener('change', fillDestacamentos);
    el('btn_copy')?.addEventListener('click', function(event){
        event.preventDefault();
        copiarTarjeta().catch(err => {
            console.error(err);
            if (el('share_status')) el('share_status').textContent = 'No se pudo copiar la tarjeta.';
        });
    });
    el('btn_share')?.addEventListener('click', function(event){
        event.preventDefault();
        compartirTarjeta().catch(err => {
            console.error(err);
            if (el('share_status')) el('share_status').textContent = 'No se pudo abrir el compartido nativo.';
        });
    });

    setDefaultDates();
    fillCatalogos();
    applyInitialParams();
    loadAll().catch(err => console.error('ASEGURAMIENTOS DASH ERROR:', err));
})();
</script>
@stop
