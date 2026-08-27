@extends('adminlte::page')

@section('title', 'Estadísticas de Actividades')

@section('content_header')
    <div class="sv-hero">
        <div class="sv-hero__inner">
            <div class="sv-hero__badge">
                <span class="sv-dot"></span>
                <span>Actividades · Unidades operativas</span>
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
                        <div class="sv-field sv-field--date">
                            <label for="f_desde">Desde</label>
                            <input type="date" id="f_desde" class="form-control form-control-sm" aria-describedby="f_desde_legible">
                            <div class="sv-date-readable" id="f_desde_legible">
                                <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                <span>Seleccione una fecha</span>
                            </div>
                        </div>

                        <div class="sv-field">
                            <label>Hora desde</label>
                            <input type="time" id="f_hora_desde" class="form-control form-control-sm">
                        </div>

                        <div class="sv-field sv-field--date">
                            <label for="f_hasta">Hasta</label>
                            <input type="date" id="f_hasta" class="form-control form-control-sm" aria-describedby="f_hasta_legible">
                            <div class="sv-date-readable" id="f_hasta_legible">
                                <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                <span>Seleccione una fecha</span>
                            </div>
                        </div>

                        <div class="sv-field">
                            <label>Hora hasta</label>
                            <input type="time" id="f_hora_hasta" class="form-control form-control-sm">
                        </div>

                        <div class="sv-field">
                            <label>Unidad</label>
                            <select id="f_unidad" class="form-control form-control-sm">
                                <option value="">Todas</option>
                                @foreach(($unidadesFiltro ?? collect()) as $unidad)
                                    @php
                                        $nombreUnidad = match ((int) $unidad->id) {
                                            1 => 'UNIDAD DE ATENCIÓN A SINIESTROS',
                                            2 => 'UNIDAD DE DELEGACIONES',
                                            4 => 'UNIDAD DE PROTECCIÓN A CARRETERAS',
                                            5 => 'UNIDAD DE PROTECCIÓN EN VIALIDADES URBANAS',
                                            6 => 'UNIDAD DE FOMENTO A LA CULTURA VIAL',
                                            default => $unidad->nombre,
                                        };
                                    @endphp

                                    <option value="{{ $unidad->id }}">{{ $nombreUnidad }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sv-field">
                            <label>Delegación</label>
                            <select id="f_delegacion" class="form-control form-control-sm" disabled>
                                <option value="">(Todas)</option>
                            </select>
                        </div>

                        <div class="sv-field" id="campo_destacamento">
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
                            <select id="f_subcategoria" class="form-control form-control-sm" disabled>
                                <option value="">Seleccione una categoría</option>
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
                        * Puedes consultar actividades por unidad. Seguridad Vial queda fuera de este filtro; al seleccionar Delegaciones se habilita el filtro por delegación.
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

            <div class="sv-panel sv-category-summary">
                <div class="sv-panel__title">
                    <i class="fa-solid fa-table-list"></i>
                    <div>
                        <span>Resumen por categoría</span>
                        <small id="resumen_periodo">Periodo seleccionado</small>
                    </div>
                    <div class="sv-category-summary__actions">
                        <button class="btn sv-btn sv-btn--ghost" id="btn_resumen_csv" type="button" disabled>
                            <i class="fa-solid fa-file-csv"></i> Descargar CSV
                        </button>
                        <button class="btn sv-btn sv-category-summary__whatsapp" id="btn_resumen_compartir" type="button" disabled>
                            <i class="fa-brands fa-whatsapp"></i> Compartir
                        </button>
                    </div>
                </div>

                <div class="sv-panel__body p-0">
                    <div class="table-responsive sv-category-summary__scroll">
                        <table class="table table-sm mb-0 sv-category-summary__table">
                            <thead>
                                <tr>
                                    <th>Categoría / subcategoría</th>
                                    <th class="text-right">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody id="tb_resumen_categorias">
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Cargando resumen…</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total general</th>
                                    <th class="text-right" id="resumen_total">—</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
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
<style>
    .sv-field--date {
        padding: 9px;
        border: 1px solid rgba(255, 63, 83, .72);
        border-radius: 12px;
        background: linear-gradient(180deg, rgba(255, 45, 68, .16), rgba(255, 45, 68, .06));
        box-shadow: 0 0 0 1px rgba(255, 63, 83, .10), 0 0 18px rgba(255, 45, 68, .14);
    }

    .sv-field--date > label {
        display: inline-flex;
        align-items: center;
        margin-bottom: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        background: #ef233c;
        color: #fff;
        font-size: 11px;
        font-weight: 950;
        letter-spacing: .45px;
        text-transform: uppercase;
    }

    .sv-field--date input[type="date"] {
        min-height: 38px;
        border: 1px solid #ff4d62 !important;
        background: #181420 !important;
        color: #fff !important;
        font-size: 14px;
        font-weight: 900;
        color-scheme: dark;
        box-shadow: inset 0 0 0 1px rgba(255, 77, 98, .16);
    }

    .sv-field--date input[type="date"]:hover,
    .sv-field--date input[type="date"]:focus {
        border-color: #ff7585 !important;
        box-shadow: 0 0 0 3px rgba(255, 45, 68, .22) !important;
    }

    .sv-field--date input[type="date"]::-webkit-calendar-picker-indicator {
        padding: 5px;
        border-radius: 6px;
        background-color: #ef233c;
        cursor: pointer;
        filter: invert(1);
        opacity: 1;
    }

    .sv-date-readable {
        display: flex;
        align-items: center;
        gap: 6px;
        min-height: 26px;
        margin-top: 5px;
        padding: 4px 8px;
        border: 1px solid rgba(255, 77, 98, .38);
        border-radius: 9px;
        background: rgba(239, 35, 60, .12);
        color: #ffe8eb;
        font-size: 11px;
        font-weight: 850;
        line-height: 1.2;
        text-transform: capitalize;
    }

    .sv-date-readable i {
        flex: 0 0 auto;
        color: #ff7585;
    }

    .sv-category-summary .sv-panel__title > div:not(.sv-category-summary__actions) {
        display: flex;
        min-width: 0;
        flex-direction: column;
    }

    .sv-category-summary__actions {
        display: flex;
        flex-direction: row;
        gap: 8px;
        margin-left: auto;
    }

    .sv-category-summary__actions .btn {
        padding: 6px 10px;
        font-size: 12px;
        white-space: nowrap;
    }

    .sv-category-summary__whatsapp {
        border-color: #25d366;
        background: #25d366;
        color: #07150c;
    }

    .sv-category-summary__whatsapp:hover,
    .sv-category-summary__whatsapp:focus {
        border-color: #20bd5a;
        background: #20bd5a;
        color: #07150c;
    }

    @media (max-width: 575.98px) {
        .sv-category-summary .sv-panel__title {
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .sv-category-summary__actions {
            width: 100%;
            margin-top: 9px;
            margin-left: 0;
        }

        .sv-category-summary__actions .btn {
            flex: 1 1 0;
        }
    }

    .sv-category-summary .sv-panel__title small {
        margin-top: 2px;
        color: rgba(234, 240, 255, .62);
        font-size: 11px;
        font-weight: 700;
        text-transform: none;
    }

    .sv-category-summary__scroll {
        max-height: 560px;
        overflow-y: auto;
    }

    .sv-category-summary__table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        padding: 10px 14px;
        background: #171d2c;
    }

    .sv-category-summary__table tbody th,
    .sv-category-summary__table tbody td,
    .sv-category-summary__table tfoot th {
        padding: 8px 14px;
    }

    .sv-category-summary__category th,
    .sv-category-summary__category td {
        background: rgba(45, 168, 255, .10);
        color: rgba(234, 240, 255, .96) !important;
        font-weight: 950;
        text-transform: uppercase;
    }

    .sv-category-summary__subcategory td:first-child {
        padding-left: 34px;
    }

    .sv-category-summary__subcategory .sv-branch {
        margin-right: 7px;
        color: rgba(84, 184, 255, .75);
    }

    .sv-category-summary__table td:last-child,
    .sv-category-summary__table th:last-child {
        width: 100px;
        text-align: right;
    }

    .sv-category-summary__table tfoot th {
        position: sticky;
        bottom: 0;
        z-index: 2;
        border-top: 1px solid rgba(45, 168, 255, .35);
        background: #111827;
        color: #fff;
        font-size: 14px;
        text-transform: uppercase;
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
    const base = "{{ url('estadisticas-actividades') }}";
    const unidadUsuarioId = Number(@json($unidadUsuarioId ?? 0));
    let page = 1;
    let lastPage = 1;
    let currentCategorySummary = { categorias: [], total: 0 };

    const el = (id) => document.getElementById(id);

    const val = (id) => {
        const n = el(id);
        return n ? String(n.value ?? '').trim() : '';
    };

    function parseLocalDate(value){
        if (!/^\d{4}-\d{2}-\d{2}$/.test(String(value || ''))) return null;

        const [year, month, day] = value.split('-').map(Number);
        const date = new Date(year, month - 1, day, 12, 0, 0);

        if (
            date.getFullYear() !== year ||
            date.getMonth() !== month - 1 ||
            date.getDate() !== day
        ) return null;

        return date;
    }

    function formatDateLong(value, includeWeekday = false){
        const date = parseLocalDate(value);
        if (!date) return 'Fecha no seleccionada';

        const options = {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        };

        if (includeWeekday) options.weekday = 'long';

        const text = new Intl.DateTimeFormat('es-MX', options).format(date);
        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    function updateReadableDates(){
        [
            ['f_desde', 'f_desde_legible'],
            ['f_hasta', 'f_hasta_legible']
        ].forEach(([inputId, outputId]) => {
            const output = el(outputId)?.querySelector('span');
            if (output) output.textContent = formatDateLong(val(inputId), true);
        });
    }

    function summaryPeriodTitle(){
        const desdeValue = val('f_desde');
        const hastaValue = val('f_hasta');
        const desde = parseLocalDate(desdeValue);
        const hasta = parseLocalDate(hastaValue);

        if (desde && hasta && desdeValue === hastaValue) {
            return `Actividades del ${formatDateLong(desdeValue).toLowerCase()}`;
        }

        if (desde && hasta && desde.getFullYear() === hasta.getFullYear() && desde.getMonth() === hasta.getMonth()) {
            const ultimoDia = new Date(desde.getFullYear(), desde.getMonth() + 1, 0).getDate();

            if (desde.getDate() === 1 && hasta.getDate() === ultimoDia) {
                return `Actividades de ${new Intl.DateTimeFormat('es-MX', {
                    month: 'long',
                    year: 'numeric'
                }).format(desde)}`;
            }
        }

        if (desde && hasta) {
            return `Del ${formatDateLong(desdeValue).toLowerCase()} al ${formatDateLong(hastaValue).toLowerCase()}`;
        }

        if (desde) return `Desde el ${formatDateLong(desdeValue).toLowerCase()}`;
        if (hasta) return `Hasta el ${formatDateLong(hastaValue).toLowerCase()}`;
        return 'Todos los registros disponibles';
    }

    function categorySummaryRows(){
        const rows = [['Tipo', 'Categoría / subcategoría', 'Cantidad']];

        currentCategorySummary.categorias.forEach(categoria => {
            rows.push(['Categoría', categoria.nombre || '', Number(categoria.total || 0)]);

            const subcategorias = Array.isArray(categoria.subcategorias) ? categoria.subcategorias : [];
            subcategorias.forEach(subcategoria => {
                rows.push(['Subcategoría', subcategoria.nombre || '', Number(subcategoria.total || 0)]);
            });
        });

        rows.push(['Total general', '', Number(currentCategorySummary.total || 0)]);
        return rows;
    }

    function csvCell(value){
        return `"${String(value ?? '').replace(/"/g, '""')}"`;
    }

    function categorySummaryCsv(){
        const period = summaryPeriodTitle();
        const rows = [[period, '', ''], ...categorySummaryRows()];
        return '\uFEFF' + rows.map(row => row.map(csvCell).join(',')).join('\r\n');
    }

    function categorySummaryFilename(){
        const desde = val('f_desde') || 'inicio';
        const hasta = val('f_hasta') || 'actual';
        return `resumen-categorias-${desde}-a-${hasta}.csv`;
    }

    function downloadCategorySummary(){
        const url = URL.createObjectURL(new Blob([categorySummaryCsv()], { type: 'text/csv;charset=utf-8' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = categorySummaryFilename();
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }

    function categorySummaryShareText(){
        const lines = ['*Resumen por categoría*', summaryPeriodTitle(), ''];

        currentCategorySummary.categorias.forEach(categoria => {
            lines.push(`*${categoria.nombre || 'Sin categoría'}:* ${Number(categoria.total || 0).toLocaleString('es-MX')}`);

            const subcategorias = Array.isArray(categoria.subcategorias) ? categoria.subcategorias : [];
            subcategorias.forEach(subcategoria => {
                lines.push(`↳ ${subcategoria.nombre || 'Sin subcategoría'}: ${Number(subcategoria.total || 0).toLocaleString('es-MX')}`);
            });
        });

        lines.push('', `*TOTAL GENERAL: ${Number(currentCategorySummary.total || 0).toLocaleString('es-MX')}*`);
        return lines.join('\n');
    }

    async function shareCategorySummary(){
        const file = new File(
            [categorySummaryCsv()],
            categorySummaryFilename(),
            { type: 'text/csv;charset=utf-8' }
        );

        if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
            try {
                await navigator.share({
                    title: 'Resumen por categoría',
                    text: summaryPeriodTitle(),
                    files: [file]
                });
                return;
            } catch (error) {
                if (error.name === 'AbortError') return;
            }
        }

        window.open(`https://wa.me/?text=${encodeURIComponent(categorySummaryShareText())}`, '_blank', 'noopener');
    }

    function qsFromFilters(extra = {}){
        const params = new URLSearchParams();

        const desde = val('f_desde');
        const hora_desde = val('f_hora_desde');
        const hasta = val('f_hasta');
        const hora_hasta = val('f_hora_hasta');
        const unidad = val('f_unidad');
        const delegacion = val('f_delegacion');
        const destacamento = val('f_destacamento');
        const categoria = val('f_categoria');
        const subcategoria = val('f_subcategoria');
        const municipio = val('f_municipio');
        const group = val('f_group');
        const q = val('f_q');

        if (desde) params.set('desde', desde);
        if (hora_desde) params.set('hora_desde', hora_desde);
        if (hasta) params.set('hasta', hasta);
        if (hora_hasta) params.set('hora_hasta', hora_hasta);
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
            'f_hora_desde',
            'f_hasta',
            'f_hora_hasta',
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

    function toggleDestacamento(){
        const campo = el('campo_destacamento');
        const destacamento = el('f_destacamento');
        const unidadSeleccionada = val('f_unidad');
        const ocultar = unidadUsuarioId === 2 || unidadSeleccionada === '2';

        if (!campo || !destacamento) return;

        campo.style.display = ocultar ? 'none' : '';
        destacamento.disabled = ocultar;

        if (ocultar) {
            destacamento.value = '';
        }

        setExportLinks();
    }

    function toggleSubcategoria(){
        const categoria = val('f_categoria');
        const subcategoria = el('f_subcategoria');

        if (!subcategoria) return;

        subcategoria.disabled = !categoria;

        if (!categoria) {
            subcategoria.value = '';
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
                    <td>${escapeHtml(formatDateLong(r.fecha))}</td>
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

    function renderCategorySummary(summary){
        const tbody = el('tb_resumen_categorias');
        const total = el('resumen_total');
        const period = el('resumen_periodo');

        currentCategorySummary = {
            categorias: Array.isArray(summary?.categorias) ? summary.categorias : [],
            total: Number(summary?.total || 0)
        };

        if (period) period.textContent = summaryPeriodTitle();
        if (total) total.textContent = Number(summary?.total || 0).toLocaleString('es-MX');
        if (el('btn_resumen_csv')) el('btn_resumen_csv').disabled = currentCategorySummary.categorias.length === 0;
        if (el('btn_resumen_compartir')) el('btn_resumen_compartir').disabled = currentCategorySummary.categorias.length === 0;
        if (!tbody) return;

        const categorias = Array.isArray(summary?.categorias) ? summary.categorias : [];

        if (categorias.length === 0) {
            tbody.innerHTML = `<tr><td colspan="2" class="text-center text-muted">Sin actividades para los filtros seleccionados.</td></tr>`;
            return;
        }

        tbody.innerHTML = categorias.map(categoria => {
            const subcategorias = Array.isArray(categoria.subcategorias) ? categoria.subcategorias : [];
            const categoryRow = `
                <tr class="sv-category-summary__category">
                    <th scope="row">${escapeHtml(categoria.nombre)}</th>
                    <td>${Number(categoria.total || 0).toLocaleString('es-MX')}</td>
                </tr>
            `;
            const subcategoryRows = subcategorias.map(subcategoria => `
                <tr class="sv-category-summary__subcategory">
                    <td><span class="sv-branch" aria-hidden="true">↳</span>${escapeHtml(subcategoria.nombre)}</td>
                    <td>${Number(subcategoria.total || 0).toLocaleString('es-MX')}</td>
                </tr>
            `).join('');

            return categoryRow + subcategoryRows;
        }).join('');
    }

    async function loadAll(){
        toggleDelegacion();
        toggleDestacamento();
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

        toggleSubcategoria();

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

        const categorySummary = await getOptionalJson('resumen/categorias', {}, { categorias: [], total: 0 });
        renderCategorySummary(categorySummary);

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
            toggleDestacamento();
        });
    }

    async function recargarSubcategorias(){
        const categoria = val('f_categoria');
        const subcategoria = el('f_subcategoria');

        if (!subcategoria) return;

        subcategoria.value = '';

        if (!categoria) {
            subcategoria.innerHTML = '<option value="">Seleccione una categoría</option>';
            subcategoria.disabled = true;
            setExportLinks();
            return;
        }

        subcategoria.disabled = true;
        subcategoria.innerHTML = '<option value="">Cargando...</option>';

        const subcat = await getOptionalJson('catalogos/subcategorias', {}, []);

        fillSelect('f_subcategoria', subcat.map(r => ({
            value: r.id,
            label: r.nombre
        })), '(Todas)');

        subcategoria.disabled = false;
        setExportLinks();
    }

    const categoriaSelect = el('f_categoria');
    if (categoriaSelect){
        categoriaSelect.addEventListener('change', async function(){
            await recargarSubcategorias();
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
        updateReadableDates();
    }

    const btnResumenCsv = el('btn_resumen_csv');
    if (btnResumenCsv){
        btnResumenCsv.addEventListener('click', downloadCategorySummary);
    }

    const btnResumenCompartir = el('btn_resumen_compartir');
    if (btnResumenCompartir){
        btnResumenCompartir.addEventListener('click', shareCategorySummary);
    }

    setDefaultDates();
    let temporizadorFecha = null;

    ['f_desde', 'f_hasta'].forEach(id => {
        const input = el(id);
        if (!input) return;

        input.addEventListener('input', updateReadableDates);

        input.addEventListener('change', function () {
            updateReadableDates();
            clearTimeout(temporizadorFecha);

            temporizadorFecha = setTimeout(async function () {
                page = 1;

                try {
                    await loadAll();
                } catch (error) {
                    console.error('Error al aplicar las fechas:', error);
                }
            }, 300);
        });
    });
    wireExportLinkUpdates();
    toggleDelegacion();
    toggleDestacamento();
    setExportLinks();
    loadAll().catch(err => console.error('SV ACTIVIDADES ERROR:', err));
})();
</script>
@stop
