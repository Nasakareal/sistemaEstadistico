@extends('adminlte::page')

@section('title', 'Gráfico de Servicios por Grúa')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
        <h1 class="mb-0">Gráfico de Servicios por Grúa</h1>

        <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
            <a class="btn btn-outline-secondary"
               href="{{ url()->current() }}?anchor={{ \Carbon\Carbon::parse($anchor)->subDays(7)->toDateString() }}{{ !empty($gruasSeleccionadas) ? '&gruas='.implode(',', $gruasSeleccionadas) : '' }}{{ !empty($origen) ? '&origen='.$origen : '' }}">
                <i class="fa-solid fa-chevron-left"></i>
            </a>

            <a class="btn btn-outline-secondary"
               href="{{ url()->current() }}?anchor={{ \Carbon\Carbon::parse($anchor)->addDays(7)->toDateString() }}{{ !empty($gruasSeleccionadas) ? '&gruas='.implode(',', $gruasSeleccionadas) : '' }}{{ !empty($origen) ? '&origen='.$origen : '' }}">
                <i class="fa-solid fa-chevron-right"></i>
            </a>

            <span class="badge badge-light" style="padding:10px 12px;">
                Semana: <b>{{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}</b> - <b>{{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</b>
            </span>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary sv-card">
                <div class="card-header">
                    <h3 class="card-title">Filtros</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ url()->current() }}">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="anchor">Semana</label>
                                    <input type="date" name="anchor" id="anchor" class="form-control" value="{{ $anchor }}">
                                </div>
                            </div>

                            @if(!empty($puedeFiltrarOrigen) && $puedeFiltrarOrigen)
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="origen">Origen</label>
                                        <select name="origen" id="origen" class="form-control">
                                            <option value="todos" {{ ($origen ?? '') === 'todos' ? 'selected' : '' }}>Todos</option>
                                            <option value="siniestros" {{ ($origen ?? '') === 'siniestros' ? 'selected' : '' }}>Siniestros</option>
                                            <option value="delegaciones" {{ ($origen ?? '') === 'delegaciones' ? 'selected' : '' }}>Delegaciones</option>
                                        </select>
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-{{ (!empty($puedeFiltrarOrigen) && $puedeFiltrarOrigen) ? '6' : '9' }}">
                                <div class="form-group">
                                    <label for="gruas">Grúas</label>
                                    <select name="gruas[]" id="gruas" class="form-control select2" multiple>
                                        @foreach($gruasCatalogo as $grua)
                                            <option value="{{ $grua->nombre }}" {{ in_array($grua->nombre, $gruasSeleccionadas ?? [], true) ? 'selected' : '' }}>
                                                {{ $grua->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-12 d-flex flex-wrap" style="gap:8px;">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fa-solid fa-filter"></i> Aplicar filtros
                                </button>

                                <a class="btn btn-outline-secondary" href="{{ url()->current() }}">
                                    <i class="fa-solid fa-rotate-left"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="sv-hint mt-3">
                        @if(($origen ?? '') === 'siniestros')
                            Mostrando únicamente grúas asignadas a <b>Siniestros</b>.
                        @elseif(($origen ?? '') === 'delegaciones')
                            Mostrando únicamente grúas asignadas a <b>Delegaciones</b>.
                        @else
                            Mostrando grúas de <b>Siniestros</b> y <b>Delegaciones</b>.
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card card-outline card-primary sv-card">
                <div class="card-header">
                    <h3 class="card-title">Cantidad de Servicios por Grúa</h3>
                </div>
                <div class="card-body">
                    <div class="sv-kpis mb-3" id="sv-kpis">
                        <div class="sv-kpi">
                            <div class="sv-kpi__label">Total servicios (visible)</div>
                            <div class="sv-kpi__value" id="kpi-total-servicios">0</div>
                        </div>
                        <div class="sv-kpi">
                            <div class="sv-kpi__label">Total vehículos (visible)</div>
                            <div class="sv-kpi__value" id="kpi-total-vehiculos">0</div>
                        </div>
                        <div class="sv-kpi">
                            <div class="sv-kpi__label">Seguro SÍ / NO (visible)</div>
                            <div class="sv-kpi__value" id="kpi-seguro">0 / 0</div>
                        </div>
                    </div>

                    <div class="chart-container">
                        <canvas id="grafico-servicios"></canvas>
                    </div>

                    <div class="mt-2 text-muted" style="font-size:12.5px;">
                        Mostrando datos únicamente del rango: <b>{{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}</b> - <b>{{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</b>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card card-outline card-primary sv-card">
                <div class="card-header">
                    <h3 class="card-title">Listado (detalle por grúa)</h3>
                </div>
                <div class="card-body" id="sv-listado"></div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .sv-card { margin: 20px; }

        .sv-hint{
            font-size: 13px;
            color: #6b7280;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            padding: 10px 12px;
            border-radius: 10px;
        }

        .sv-kpis{
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }
        .sv-kpi{
            border: 1px solid #e5e7eb;
            background: #ffffff;
            border-radius: 12px;
            padding: 12px 12px;
        }
        .sv-kpi__label{
            font-size: 12px;
            color: #6b7280;
            font-weight: 700;
        }
        .sv-kpi__value{
            font-size: 22px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.2;
        }

        .sv-grua{
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .sv-grua__head{
            background: #ffffff;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .sv-grua__title{
            font-weight: 900;
            color: #0f172a;
        }
        .sv-grua__meta{
            font-size: 12.5px;
            color: #64748b;
            margin-top: 2px;
        }
        .sv-badges{
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: flex-end;
        }
        .sv-badge{
            font-size: 12px;
            font-weight: 800;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            color: #0f172a;
            white-space: nowrap;
        }
        .sv-badge--ok{ background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.25); }
        .sv-badge--no{ background: rgba(245,158,11,.10); border-color: rgba(245,158,11,.25); }

        .sv-grua__body{
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 12px 14px;
            display: none;
        }

        .sv-veh{
            border: 1px solid #e5e7eb;
            background: #ffffff;
            border-radius: 12px;
            padding: 10px 12px;
            margin-top: 8px;
        }
        .sv-veh__title{
            font-weight: 900;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sv-veh__desc{
            font-size: 12.5px;
            color: #64748b;
            margin-top: 4px;
        }

        .chart-container { position: relative; width: 100%; height: 420px; }

        @media (max-width: 768px) {
            .chart-container { height: 60vh; }
            .select2 { width: 100% !important; }
            .sv-kpis{ grid-template-columns: 1fr; }
        }
    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            $('.select2').select2({
                placeholder: 'Selecciona una o más grúas',
                allowClear: true,
                width: '100%'
            });
        });

        const serviciosDataRaw = @json($gruasServicios);

        function toInt(v){
            if (v === null || v === undefined) return 0;
            if (Number.isInteger(v)) return v;
            const n = parseInt(v, 10);
            return isNaN(n) ? 0 : n;
        }

        function safeStr(v){
            return (v === null || v === undefined) ? '' : String(v);
        }

        function parseDateMaybe(s){
            const str = safeStr(s).trim();
            if (!str) return null;
            const iso = str.includes(' ') ? str.replace(' ', 'T') : str;
            const d = new Date(iso);
            return isNaN(d.getTime()) ? null : d;
        }

        function normalizeItem(item){
            const nombre = safeStr(item.nombre).trim();
            const serviciosCount = toInt(item.servicios_count);

            const vehiculos = Array.isArray(item.vehiculos)
                ? item.vehiculos.filter(v => v && typeof v === 'object')
                : [];

            const fechaUlt = item.fecha_ultimo_servicio;
            const d = parseDateMaybe(fechaUlt);

            return {
                nombre,
                servicios_count: serviciosCount,
                fecha_ultimo_servicio: fechaUlt,
                fecha_ultimo_dt: d,
                vehiculos
            };
        }

        const serviciosData = (Array.isArray(serviciosDataRaw) ? serviciosDataRaw : [])
            .map(normalizeItem)
            .filter(i => i.nombre.length > 0);

        function computeResumen(list){
            let totalServicios = 0;
            let totalVehiculos = 0;
            let seguroSi = 0;
            let seguroNo = 0;

            for (const g of list){
                totalServicios += toInt(g.servicios_count);
                totalVehiculos += Array.isArray(g.vehiculos) ? g.vehiculos.length : 0;

                if (Array.isArray(g.vehiculos)) {
                    for (const v of g.vehiculos){
                        const tieneSeguro = toInt(v.tiene_seguro) === 1;
                        if (tieneSeguro) seguroSi++; else seguroNo++;
                    }
                }
            }

            return { totalServicios, totalVehiculos, seguroSi, seguroNo };
        }

        function fmtFechaShort(s){
            const d = parseDateMaybe(s);
            if (!d) return '';
            const dd = String(d.getDate()).padStart(2,'0');
            const mm = String(d.getMonth()+1).padStart(2,'0');
            const yy = d.getFullYear();
            return `${dd}/${mm}/${yy}`;
        }

        function buildListado(list){
            const $wrap = $('#sv-listado');
            $wrap.empty();

            if (!list.length){
                $wrap.html('<div class="text-muted">Sin resultados en esta semana.</div>');
                return;
            }

            for (let idx = 0; idx < list.length; idx++){
                const g = list[idx];
                const id = `sv-body-${idx}`;

                let seguroSi = 0, seguroNo = 0;
                let aseguradoras = {};

                for (const v of (g.vehiculos || [])){
                    const tieneSeguro = toInt(v.tiene_seguro) === 1;
                    if (tieneSeguro) seguroSi++; else seguroNo++;

                    const aseg = safeStr(v.aseguradora).trim();
                    if (aseg){
                        aseguradoras[aseg] = (aseguradoras[aseg] || 0) + 1;
                    }
                }

                const fechaUlt = fmtFechaShort(g.fecha_ultimo_servicio);

                const badges = [];
                badges.push(`<span class="sv-badge">Servicios: ${toInt(g.servicios_count)}</span>`);
                badges.push(`<span class="sv-badge">Vehículos: ${(g.vehiculos || []).length}</span>`);
                badges.push(`<span class="sv-badge sv-badge--ok">Seguro SÍ: ${seguroSi}</span>`);
                badges.push(`<span class="sv-badge sv-badge--no">Seguro NO: ${seguroNo}</span>`);

                let bodyHtml = '';

                if (!g.vehiculos || g.vehiculos.length === 0){
                    bodyHtml = `<div class="text-muted">Sin vehículos/servicios en el rango.</div>`;
                } else {
                    const asegKeys = Object.keys(aseguradoras);
                    let asegHtml = '';

                    if (asegKeys.length){
                        asegKeys.sort((a,b) => aseguradoras[b] - aseguradoras[a]);
                        const chips = asegKeys.map(k => `<span class="sv-badge">Aseg: ${k} (${aseguradoras[k]})</span>`).join(' ');
                        asegHtml = `<div class="mb-2" style="display:flex;flex-wrap:wrap;gap:6px;">${chips}</div>`;
                    }

                    const vehHtml = g.vehiculos.map(v => {
                        const placas = safeStr(v.placas).trim();
                        const marca = safeStr(v.marca).trim();
                        const linea = safeStr(v.linea).trim();
                        const modelo = safeStr(v.modelo).trim();
                        const color = safeStr(v.color).trim();

                        const tipo = safeStr((v.tipo_vehiculo ?? v.tipo)).trim();
                        const aseguradora = safeStr(v.aseguradora).trim();

                        const tieneSeguro = toInt(v.tiene_seguro) === 1;
                        const servicioId = toInt(v.servicio_id);
                        const fecha = safeStr(v.fecha_servicio).trim();

                        const titleParts = [];
                        if (placas) titleParts.push(placas);
                        if (tipo) titleParts.push(tipo);

                        const descParts = [];
                        const mm = [marca, linea, modelo].filter(s => s).join(' ');
                        if (mm) descParts.push(mm);
                        if (color) descParts.push(color);
                        if (aseguradora) descParts.push(`Aseg: ${aseguradora}`);
                        descParts.push(`Seguro: ${tieneSeguro ? 'SÍ' : 'NO'}`);
                        if (servicioId > 0) descParts.push(`Servicio #${servicioId}`);
                        if (fecha) descParts.push(fecha);

                        return `
                            <div class="sv-veh">
                                <div class="sv-veh__title">
                                    <i class="fa-solid ${tieneSeguro ? 'fa-shield-alt' : 'fa-triangle-exclamation'}"></i>
                                    ${titleParts.length ? titleParts.join(' · ') : 'Vehículo'}
                                </div>
                                <div class="sv-veh__desc">${descParts.join(' · ')}</div>
                            </div>
                        `;
                    }).join('');

                    bodyHtml = `${asegHtml}${vehHtml}`;
                }

                const card = `
                    <div class="sv-grua">
                        <div class="sv-grua__head">
                            <div>
                                <div class="sv-grua__title">
                                    <i class="fa-solid fa-truck-moving mr-2"></i>${g.nombre}
                                </div>
                                <div class="sv-grua__meta">
                                    Último servicio: ${fechaUlt ? fechaUlt : '—'}
                                </div>
                            </div>

                            <div class="sv-badges">
                                ${badges.join(' ')}
                                <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="sv-collapse" data-target="#${id}">
                                    <i class="fa-solid fa-chevron-down"></i> Ver
                                </button>
                            </div>
                        </div>

                        <div class="sv-grua__body" id="${id}">
                            ${bodyHtml}
                        </div>
                    </div>
                `;

                $wrap.append(card);
            }

            $('[data-toggle="sv-collapse"]').off('click').on('click', function(){
                const target = $(this).data('target');
                const $body = $(target);
                const $icon = $(this).find('i');

                if ($body.is(':visible')){
                    $body.slideUp(160);
                    $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                } else {
                    $body.slideDown(160);
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                }
            });
        }

        const canvasElem = document.getElementById('grafico-servicios');
        const ctx = canvasElem.getContext('2d');

        const labels = serviciosData.map(i => i.nombre);
        const values = serviciosData.map(i => toInt(i.servicios_count));

        let chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Cantidad de Servicios',
                    data: values,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                },
                scales: {
                    x: {
                        title: { display: true, text: 'Grúas' },
                        ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 }
                    },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Servicios Registrados' }
                    }
                }
            }
        });

        const r = computeResumen(serviciosData);
        $('#kpi-total-servicios').text(String(r.totalServicios));
        $('#kpi-total-vehiculos').text(String(r.totalVehiculos));
        $('#kpi-seguro').text(`${r.seguroSi} / ${r.seguroNo}`);

        buildListado(serviciosData);
    </script>
@stop
