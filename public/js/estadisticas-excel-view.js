(function (window, document) {
    'use strict';

    const COLUMN_LETTERS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function displayNumber(value) {
        const number = Number(value || 0);
        if (!number) return '';
        return number.toLocaleString('es-MX', { maximumFractionDigits: 2 });
    }

    function tableShell(rows) {
        return `
            <table class="sv-excel-table" aria-label="Hoja TOTAL">
                <colgroup>
                    <col class="row-number">
                    ${COLUMN_LETTERS.map(letter => `<col class="col-${letter.toLowerCase()}">`).join('')}
                </colgroup>
                <thead>
                    <tr>
                        <th class="sv-excel-corner"></th>
                        ${COLUMN_LETTERS.map(letter => `<th scope="col">${letter}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        `;
    }

    function cell(value, row, column, classes = '', attrs = '') {
        const content = value === null || value === undefined || value === '' ? '' : escapeHtml(value);
        return `<td class="sv-excel-cell ${classes}" data-cell="${column}${row}" data-value="${escapeHtml(value ?? '')}" ${attrs}>${content}</td>`;
    }

    function rowNumber(row) {
        return `<th scope="row" class="sv-excel-row-number">${row}</th>`;
    }

    function titleRows(title, period) {
        return `
            <tr class="sv-excel-title-row">
                ${rowNumber(1)}
                ${cell('', 1, 'A')}${cell('', 1, 'B')}${cell(title, 1, 'C', 'sv-excel-title-cell')}
                ${COLUMN_LETTERS.slice(3).map(letter => cell('', 1, letter)).join('')}
            </tr>
            <tr class="sv-excel-date-row">
                ${rowNumber(2)}
                ${cell('', 2, 'A')}${cell('FECHA', 2, 'B', 'sv-excel-center')}${cell(period, 2, 'C')}
                ${COLUMN_LETTERS.slice(3).map(letter => cell('', 2, letter)).join('')}
            </tr>
        `;
    }

    function activityHeader(row) {
        const headers = ['No.', 'CATEGORÍA', 'ACTIVIDAD', 'CANTIDAD', 'ESTADO DE FUERZA PARTICIPANTE', 'UNIDADES PARTICIPANTES', 'KILÓMETROS RECORRIDOS', 'PERSONAS ALCANZADAS', 'RECOMENDACIONES'];
        return `<tr class="sv-excel-main-header">${rowNumber(row)}${headers.map((header, index) => {
            const color = index <= 2 ? 'sv-excel-header-blue' : (index <= 6 ? 'sv-excel-header-green' : 'sv-excel-header-cyan');
            return cell(header, row, COLUMN_LETTERS[index], color);
        }).join('')}</tr>`;
    }

    function buildActivities(data, context) {
        const categories = data.summary?.categorias || [];
        const totals = data.kpis?.totales || {};
        const participatingUnits = categories.reduce(
            (total, category) => total + Number(category.unidades_participantes || 0),
            0
        );
        let excelRow = 4;
        let body = titleRows(context.title, context.period) + activityHeader(3);

        if (!categories.length) {
            body += `<tr>${rowNumber(excelRow)}${cell(1, excelRow, 'A', 'sv-excel-number')}${cell('SIN DATOS', excelRow, 'B', 'sv-excel-category')}${cell('No hay actividades para los filtros seleccionados', excelRow, 'C', 'sv-excel-empty')}${COLUMN_LETTERS.slice(3).map(letter => cell('', excelRow, letter, 'sv-excel-number')).join('')}</tr>`;
            excelRow++;
        }

        categories.forEach((category, categoryIndex) => {
            const subcategories = category.subcategorias?.length
                ? category.subcategorias
                : [{ nombre: category.nombre, total: category.total }];
            const band = categoryIndex % 2 === 1 ? 'sv-excel-band' : '';

            subcategories.forEach((subcategory, subIndex) => {
                const row = excelRow++;
                body += `<tr class="${band}">${rowNumber(row)}`;
                body += subIndex === 0
                    ? cell(categoryIndex + 1, row, 'A', 'sv-excel-number sv-excel-bold', `rowspan="${subcategories.length}"`)
                    : '';
                body += subIndex === 0
                    ? cell(category.nombre, row, 'B', 'sv-excel-category', `rowspan="${subcategories.length}"`)
                    : '';
                body += cell(subcategory.nombre, row, 'C');
                body += cell(displayNumber(subcategory.total), row, 'D', 'sv-excel-number');
                body += cell(displayNumber(subcategory.estado_fuerza_participante), row, 'E', 'sv-excel-number');
                body += cell(displayNumber(subcategory.unidades_participantes), row, 'F', 'sv-excel-number');
                body += cell('', row, 'G', 'sv-excel-number');
                body += cell(displayNumber(subcategory.personas_alcanzadas), row, 'H', 'sv-excel-number');
                body += cell('', row, 'I', 'sv-excel-number');
                body += '</tr>';
            });
        });

        body += `<tr class="sv-excel-total">${rowNumber(excelRow)}`;
        body += cell('TOTAL', excelRow, 'A', '', 'colspan="2"');
        body += cell('DISPOSITIVOS REALIZADOS', excelRow, 'C');
        body += cell(displayNumber(totals.actividades), excelRow, 'D', 'sv-excel-number');
        body += cell(displayNumber(totals.personas_participantes), excelRow, 'E', 'sv-excel-number');
        body += cell(displayNumber(participatingUnits), excelRow, 'F', 'sv-excel-number');
        body += cell('', excelRow, 'G', 'sv-excel-number');
        body += cell(displayNumber(totals.personas_alcanzadas), excelRow, 'H', 'sv-excel-number');
        body += cell('', excelRow, 'I', 'sv-excel-number');
        body += '</tr>';

        return tableShell(body);
    }

    function subheader(row, leftTitle, rightTitle = '') {
        return `<tr class="sv-excel-subheader">${rowNumber(row)}`
            + cell('', row, 'A')
            + cell('No.', row, 'B')
            + cell(leftTitle, row, 'C')
            + cell('CANTIDAD', row, 'D')
            + cell('', row, 'E')
            + cell(rightTitle ? 'No.' : '', row, 'F')
            + cell(rightTitle, row, 'G')
            + cell(rightTitle ? 'CANTIDAD' : '', row, 'H')
            + cell('', row, 'I')
            + '</tr>';
    }

    function factsSummary(data, startRow) {
        const totals = data.kpis?.totales || {};
        const situations = data.kpis?.top?.situacion || [];
        const right = [
            ['PERSONAS EN PUESTAS', totals.personas_puestas],
            ['VEHÍCULOS PARTICIPANTES', totals.vehiculos],
            ['PERSONAS LESIONADAS', totals.lesionados],
            ['PERSONAS FALLECIDAS', totals.fallecidos],
        ];
        const count = Math.max(situations.length, right.length, 1);
        let body = subheader(startRow, 'HECHOS DE TRÁNSITO', 'PERSONAS Y VEHÍCULOS');

        for (let i = 0; i < count; i++) {
            const row = startRow + 1 + i;
            const left = situations[i];
            const rightItem = right[i];
            body += `<tr>${rowNumber(row)}${cell('', row, 'A')}`;
            body += cell(left ? i + 1 : '', row, 'B', 'sv-excel-number sv-excel-bold');
            body += cell(left?.label || '', row, 'C');
            body += cell(displayNumber(left?.total), row, 'D', 'sv-excel-number');
            body += cell('', row, 'E');
            body += cell(rightItem ? i + 1 : '', row, 'F', 'sv-excel-number sv-excel-bold');
            body += cell(rightItem?.[0] || '', row, 'G');
            body += cell(displayNumber(rightItem?.[1]), row, 'H', 'sv-excel-number');
            body += cell('', row, 'I');
            body += '</tr>';
        }

        const totalRow = startRow + count + 1;
        body += `<tr class="sv-excel-total">${rowNumber(totalRow)}${cell('', totalRow, 'A')}${cell('TOTAL', totalRow, 'B', '', 'colspan="2"')}${cell(displayNumber(totals.hechos), totalRow, 'D', 'sv-excel-number')}${cell('', totalRow, 'E')}${cell('TOTAL', totalRow, 'F', '', 'colspan="2"')}${cell(displayNumber(Number(totals.personas_puestas || 0) + Number(totals.vehiculos || 0) + Number(totals.lesionados || 0) + Number(totals.fallecidos || 0)), totalRow, 'H', 'sv-excel-number')}${cell('', totalRow, 'I')}</tr>`;
        return { html: body, nextRow: totalRow + 2 };
    }

    function buildFacts(data, context) {
        const totals = data.kpis?.totales || {};
        const types = data.kpis?.top?.tipo_hecho || [];
        const vehicles = data.vehicles?.series || [];
        let body = titleRows(context.title, context.period);
        let row = 119;

        body += `<tr class="sv-excel-section-title">${rowNumber(row)}${cell('CONTROL DE HECHOS DE TRÁNSITO', row, 'A', '', 'colspan="9"')}</tr>`;
        row++;
        const summary = factsSummary(data, row);
        body += summary.html;
        row = summary.nextRow;

        body += `<tr class="sv-excel-subheader">${rowNumber(row)}`
            + cell('', row, 'A') + cell('No.', row, 'B') + cell('HECHOS DE TRÁNSITO', row, 'C')
            + cell('CANTIDAD', row, 'D') + cell('LESIONADOS', row, 'E') + cell('HERIDOS', row, 'F')
            + cell('DEFUNCIONES', row, 'G') + cell('FUERO COMÚN', row, 'H') + cell('', row, 'I') + '</tr>';
        row++;

        if (!types.length) {
            body += `<tr>${rowNumber(row)}${cell('', row, 'A')}${cell(1, row, 'B', 'sv-excel-number sv-excel-bold')}${cell('SIN DATOS PARA LOS FILTROS SELECCIONADOS', row, 'C', 'sv-excel-empty')}${COLUMN_LETTERS.slice(3).map(letter => cell('', row, letter, 'sv-excel-number')).join('')}</tr>`;
            row++;
        } else {
            types.forEach((type, index) => {
                body += `<tr>${rowNumber(row)}${cell('', row, 'A')}${cell(index + 1, row, 'B', 'sv-excel-number sv-excel-bold')}${cell(type.label, row, 'C')}${cell(displayNumber(type.total), row, 'D', 'sv-excel-number')}${cell('', row, 'E', 'sv-excel-number')}${cell('', row, 'F', 'sv-excel-number')}${cell('', row, 'G', 'sv-excel-number')}${cell('', row, 'H', 'sv-excel-number')}${cell('', row, 'I')}</tr>`;
                row++;
            });
        }

        body += `<tr class="sv-excel-total">${rowNumber(row)}${cell('', row, 'A')}${cell('TOTAL', row, 'B', '', 'colspan="2"')}${cell(displayNumber(totals.hechos), row, 'D', 'sv-excel-number')}${cell(displayNumber(Number(totals.lesionados || 0) + Number(totals.fallecidos || 0)), row, 'E', 'sv-excel-number')}${cell(displayNumber(totals.lesionados), row, 'F', 'sv-excel-number')}${cell(displayNumber(totals.fallecidos), row, 'G', 'sv-excel-number')}${cell('', row, 'H')}${cell('', row, 'I')}</tr>`;
        row += 2;

        body += subheader(row, 'CLASIFICACIÓN DE VEHÍCULOS', 'RESUMEN');
        row++;
        const rightSummary = [
            ['TOTAL VEHÍCULOS', totals.vehiculos],
            ['TOTAL HECHOS', totals.hechos],
            ['TOTAL LESIONADOS', totals.lesionados],
            ['TOTAL FALLECIDOS', totals.fallecidos],
        ];
        const vehicleCount = Math.max(vehicles.length, rightSummary.length, 1);
        for (let i = 0; i < vehicleCount; i++) {
            const vehicle = vehicles[i];
            const side = rightSummary[i];
            body += `<tr>${rowNumber(row)}${cell('', row, 'A')}${cell(vehicle ? i + 1 : '', row, 'B', 'sv-excel-number sv-excel-bold')}${cell(vehicle?.label || '', row, 'C')}${cell(displayNumber(vehicle?.total), row, 'D', 'sv-excel-number')}${cell('', row, 'E')}${cell(side ? i + 1 : '', row, 'F', 'sv-excel-number sv-excel-bold')}${cell(side?.[0] || '', row, 'G')}${cell(displayNumber(side?.[1]), row, 'H', 'sv-excel-number')}${cell('', row, 'I')}</tr>`;
            row++;
        }
        body += `<tr class="sv-excel-total">${rowNumber(row)}${cell('', row, 'A')}${cell('TOTAL', row, 'B', '', 'colspan="2"')}${cell(displayNumber(totals.vehiculos), row, 'D', 'sv-excel-number')}${cell('', row, 'E')}${cell('TOTAL', row, 'F', '', 'colspan="2"')}${cell(displayNumber(Number(totals.vehiculos || 0) + Number(totals.hechos || 0) + Number(totals.lesionados || 0) + Number(totals.fallecidos || 0)), row, 'H', 'sv-excel-number')}${cell('', row, 'I')}</tr>`;

        return tableShell(body);
    }

    function periodContext() {
        const from = document.getElementById('f_desde')?.value || '';
        const to = document.getElementById('f_hasta')?.value || '';
        const format = value => {
            if (!value) return '';
            const [year, month, day] = value.split('-');
            return `${day}/${month}/${year}`;
        };
        if (from && to && from !== to) return `${format(from)} AL ${format(to)}`;
        return format(to || from) || 'PERIODO COMPLETO';
    }

    function titleContext(mode) {
        if (mode === 'actividades') {
            const unit = document.getElementById('f_unidad');
            const selected = unit?.selectedOptions?.[0]?.textContent?.trim();
            return selected && !/^todas/i.test(selected) ? selected.toUpperCase() : 'TODAS LAS UNIDADES';
        }
        const origin = document.getElementById('f_origen_hechos');
        const selected = origin?.selectedOptions?.[0]?.textContent?.trim();
        return selected ? selected.toUpperCase() : 'ESTADÍSTICAS GLOBALES';
    }

    async function fetchJson(base, path, query) {
        const separator = query ? '&' : '';
        const response = await fetch(`${base}/${path}?${query}${separator}_=${Date.now()}`, {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        });
        if (!response.ok) throw new Error(`HTTP ${response.status} en ${path}`);
        return response.json();
    }

    function create(options) {
        const root = document.querySelector(options.view);
        const dashboard = document.querySelector(options.dashboard);
        const launch = document.querySelector(options.button);
        if (!root || !dashboard || !launch) return null;

        const sheet = root.querySelector('[data-excel-sheet]');
        const loading = root.querySelector('[data-excel-loading]');
        const periodLabel = root.querySelector('[data-excel-period]');
        const formula = root.querySelector('[data-excel-formula]');
        const nameBox = root.querySelector('[data-excel-name-box]');
        const zoom = root.querySelector('[data-excel-zoom]');
        const zoomLabel = root.querySelector('[data-excel-zoom-label]');
        let visible = false;
        let refreshing = false;

        function setZoom(value) {
            const safe = Math.min(120, Math.max(70, Number(value) || 90));
            zoom.value = String(safe);
            zoomLabel.textContent = `${safe}%`;
            sheet.style.zoom = safe / 100;
        }

        async function refresh() {
            if (refreshing) return;
            refreshing = true;
            loading.classList.remove('d-none');
            periodLabel.textContent = periodContext();
            const query = options.query ? options.query() : '';

            try {
                const context = { title: titleContext(options.mode), period: periodContext() };
                if (options.mode === 'actividades') {
                    const [kpis, summary] = await Promise.all([
                        fetchJson(options.base, 'kpis', query),
                        fetchJson(options.base, 'resumen/categorias', query),
                    ]);
                    sheet.innerHTML = buildActivities({ kpis, summary }, context);
                } else {
                    const [kpis, vehicles] = await Promise.all([
                        fetchJson(options.base, 'kpis', query),
                        fetchJson(options.base, 'series/vehiculos/tipo', query),
                    ]);
                    sheet.innerHTML = buildFacts({ kpis, vehicles }, context);
                }
                selectCell(sheet.querySelector('[data-cell="C1"]'));
            } catch (error) {
                console.error('VISTA EXCEL:', error);
                sheet.innerHTML = `<div class="alert alert-danger m-3"><b>No fue posible preparar la hoja TOTAL.</b><br>${escapeHtml(error.message)}</div>`;
            } finally {
                refreshing = false;
                loading.classList.add('d-none');
            }
        }

        function selectCell(target) {
            if (!target) return;
            sheet.querySelector('.sv-excel-cell--selected')?.classList.remove('sv-excel-cell--selected');
            target.classList.add('sv-excel-cell--selected');
            nameBox.textContent = target.dataset.cell || '';
            formula.textContent = target.dataset.value || '';
        }

        function show() {
            visible = true;
            dashboard.classList.add('d-none');
            root.classList.remove('d-none');
            launch.setAttribute('aria-pressed', 'true');
            root.scrollIntoView({ behavior: 'smooth', block: 'start' });
            refresh();
        }

        function close() {
            visible = false;
            root.classList.add('d-none');
            dashboard.classList.remove('d-none');
            launch.setAttribute('aria-pressed', 'false');
            launch.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        launch.addEventListener('click', event => {
            event.preventDefault();
            visible ? close() : show();
        });
        root.addEventListener('click', event => {
            const action = event.target.closest('[data-excel-action]')?.dataset.excelAction;
            if (action === 'close') close();
            if (action === 'refresh') refresh();
            if (action === 'print') window.print();
            if (action === 'zoom-in') setZoom(Number(zoom.value) + 5);
            if (action === 'zoom-out') setZoom(Number(zoom.value) - 5);
            const selected = event.target.closest('.sv-excel-cell');
            if (selected) selectCell(selected);
        });
        zoom.addEventListener('input', () => setZoom(zoom.value));
        setZoom(zoom.value);

        return {
            refresh,
            refreshIfVisible: () => visible ? refresh() : Promise.resolve(),
            isVisible: () => visible,
            close,
            show,
        };
    }

    window.SvExcelView = { create };
})(window, document);
