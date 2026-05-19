@php
    $hechoIph = $mapeo['hecho'] ?? [];
    $puesta = $mapeo['puesta_disposicion'] ?? null;
    $vehiculosHecho = $mapeo['vehiculos_hecho'] ?? [];
    $conductoresHecho = $mapeo['conductores_hecho'] ?? [];
    $personasPuesta = $mapeo['personas'] ?? [];
    $vehiculosPuesta = $mapeo['vehiculos'] ?? [];
    $objetosPuesta = $mapeo['objetos'] ?? [];

    $valor = function ($valor, string $default = 'No especificado') {
        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }

        if (is_null($valor) || trim((string) $valor) === '') {
            return $default;
        }

        return (string) $valor;
    };

    $ubicacion = $hechoIph['ubicacion'] ?? [];
    $lugarHecho = collect([
        $ubicacion['calle'] ?? null,
        $ubicacion['colonia'] ?? null,
        $ubicacion['municipio'] ?? null,
    ])->filter()->implode(', ');
@endphp

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IPH Puesta a Disposición - Hecho {{ $hecho->id }}</title>
    <style>
        :root {
            --ink: #111827;
            --muted: #4b5563;
            --line: #cbd5e1;
            --soft: #f8fafc;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #e5e7eb;
            color: var(--ink);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.35;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            background: #111827;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .18);
        }

        .toolbar button,
        .toolbar a {
            border: 1px solid rgba(255, 255, 255, .25);
            border-radius: 4px;
            padding: 7px 12px;
            background: #ffffff;
            color: #111827;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .sheet {
            width: 216mm;
            min-height: 279mm;
            margin: 14px auto;
            padding: 13mm;
            background: #fff;
            box-shadow: 0 8px 30px rgba(15, 23, 42, .18);
        }

        .doc-header {
            border: 2px solid var(--ink);
            padding: 10px;
            text-align: center;
            text-transform: uppercase;
        }

        .doc-header .kicker {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .4px;
        }

        .doc-header h1 {
            margin: 6px 0 4px;
            font-size: 18px;
            letter-spacing: .6px;
        }

        .doc-header p {
            margin: 0;
            color: var(--muted);
            font-size: 11px;
        }

        .section {
            margin-top: 12px;
            border: 1px solid var(--line);
        }

        .section-title {
            padding: 6px 8px;
            background: var(--ink);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border-top: 1px solid var(--line);
        }

        .field {
            min-height: 44px;
            padding: 6px 7px;
            border-right: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
        }

        .field:nth-child(4n) {
            border-right: 0;
        }

        .field.wide {
            grid-column: span 2;
        }

        .field.full {
            grid-column: 1 / -1;
            border-right: 0;
        }

        .label {
            display: block;
            margin-bottom: 3px;
            color: var(--muted);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .value {
            white-space: pre-wrap;
            word-break: break-word;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 6px;
            border: 1px solid var(--line);
            vertical-align: top;
            text-align: left;
        }

        th {
            background: var(--soft);
            color: var(--muted);
            font-size: 10px;
            text-transform: uppercase;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-top: 28px;
        }

        .signature {
            padding-top: 34px;
            border-top: 1px solid var(--ink);
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .hint {
            margin-top: 8px;
            color: var(--muted);
            font-size: 10px;
        }

        @page {
            size: letter;
            margin: 10mm;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            .section {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Imprimir</button>
        <a href="{{ route('hechos.show', $hecho->id) }}">Volver al hecho</a>
    </div>

    <main class="sheet">
        <header class="doc-header">
            <div class="kicker">Secretaría de Seguridad Vial</div>
            <h1>Informe Policial Homologado</h1>
            <p>Puesta a disposición generada desde el sistema · Hecho {{ $hecho->id }}</p>
        </header>

        <section class="section">
            <div class="section-title">1. Datos del hecho</div>
            <div class="grid">
                <div class="field">
                    <span class="label">Folio C5i</span>
                    <div class="value">{{ $valor($hechoIph['folio_c5i'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Fecha</span>
                    <div class="value">{{ $valor($hechoIph['fecha'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Hora</span>
                    <div class="value">{{ $valor($hechoIph['hora'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Estatus</span>
                    <div class="value">{{ $valor($hechoIph['situacion'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Unidad</span>
                    <div class="value">{{ $valor($hechoIph['unidad_org_nombre'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Delegación</span>
                    <div class="value">{{ $valor($hechoIph['delegacion_nombre'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Perito / Patrullero</span>
                    <div class="value">{{ $valor($hechoIph['perito'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Unidad económica</span>
                    <div class="value">{{ $valor($hechoIph['unidad_numero_economico'] ?? null) }}</div>
                </div>
                <div class="field wide">
                    <span class="label">Tipo de hecho</span>
                    <div class="value">{{ $valor($hechoIph['tipo_hecho'] ?? null) }}</div>
                </div>
                <div class="field wide">
                    <span class="label">Colisión / Camino</span>
                    <div class="value">{{ $valor($hechoIph['colision_camino'] ?? null) }}</div>
                </div>
                <div class="field full">
                    <span class="label">Lugar del hecho</span>
                    <div class="value">{{ $valor($lugarHecho) }}</div>
                </div>
                <div class="field full">
                    <span class="label">Causas / Observaciones iniciales</span>
                    <div class="value">{{ $valor($hechoIph['causas'] ?? null) }}</div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-title">2. Datos de la puesta a disposición</div>
            <div class="grid">
                <div class="field">
                    <span class="label">No. puesta</span>
                    <div class="value">{{ $valor($puesta['folio'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Tipo</span>
                    <div class="value">{{ $valor($puesta['tipo_puesta'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Motivo</span>
                    <div class="value">{{ $valor($puesta['motivo'] ?? 'HECHO DE TRÁNSITO TURNADO') }}</div>
                </div>
                <div class="field">
                    <span class="label">Oficio</span>
                    <div class="value">{{ $valor($puesta['oficio'] ?? ($hechoIph['oficio_mp'] ?? null)) }}</div>
                </div>
                <div class="field">
                    <span class="label">Fecha puesta</span>
                    <div class="value">{{ $valor($puesta['fecha_puesta'] ?? ($hechoIph['fecha'] ?? null)) }}</div>
                </div>
                <div class="field">
                    <span class="label">Hora puesta</span>
                    <div class="value">{{ $valor($puesta['hora_puesta'] ?? ($hechoIph['hora'] ?? null)) }}</div>
                </div>
                <div class="field wide">
                    <span class="label">Lugar puesta</span>
                    <div class="value">{{ $valor($puesta['lugar_puesta'] ?? $lugarHecho) }}</div>
                </div>
                <div class="field wide">
                    <span class="label">Policía que pone a disposición</span>
                    <div class="value">{{ $valor($puesta['nombre_policia'] ?? ($hechoIph['perito'] ?? null)) }}</div>
                </div>
                <div class="field wide">
                    <span class="label">Autoridad receptora / MP</span>
                    <div class="value">{{ $valor($puesta['autoridad_receptora'] ?? ($puesta['nombre_mp'] ?? null)) }}</div>
                </div>
                <div class="field full">
                    <span class="label">Narrativa</span>
                    <div class="value">{{ $valor($puesta['narrativa'] ?? null, '') }}</div>
                </div>
                <div class="field full">
                    <span class="label">Observaciones</span>
                    <div class="value">{{ $valor($puesta['observaciones'] ?? null, '') }}</div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-title">3. Personas relacionadas</div>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Calidad</th>
                        <th>Edad</th>
                        <th>Sexo</th>
                        <th>Domicilio / Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($personasPuesta as $persona)
                        <tr>
                            <td>{{ $valor($persona['nombre_completo'] ?? null) }}</td>
                            <td>{{ $valor($persona['calidad'] ?? null) }}</td>
                            <td>{{ $valor($persona['edad'] ?? null) }}</td>
                            <td>{{ $valor($persona['sexo'] ?? null) }}</td>
                            <td>{{ $valor($persona['domicilio'] ?? null) }}<br>{{ $valor($persona['observaciones'] ?? null, '') }}</td>
                        </tr>
                    @empty
                        @forelse($conductoresHecho as $conductor)
                            <tr>
                                <td>{{ $valor($conductor['nombre'] ?? null) }}</td>
                                <td>CONDUCTOR</td>
                                <td>{{ $valor($conductor['edad'] ?? null) }}</td>
                                <td>{{ $valor($conductor['sexo'] ?? null) }}</td>
                                <td>{{ $valor($conductor['domicilio'] ?? null) }}<br>{{ $valor($conductor['vehiculo_label'] ?? null, '') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">No hay personas mapeadas todavía.</td>
                            </tr>
                        @endforelse
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="section">
            <div class="section-title">4. Vehículos relacionados</div>
            <table>
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Placas</th>
                        <th>Serie</th>
                        <th>Color</th>
                        <th>Calidad / Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @php $vehiculosTabla = !empty($vehiculosPuesta) ? $vehiculosPuesta : $vehiculosHecho; @endphp
                    @forelse($vehiculosTabla as $vehiculo)
                        <tr>
                            <td>{{ $valor(collect([$vehiculo['tipo'] ?? null, $vehiculo['marca'] ?? null, $vehiculo['submarca'] ?? ($vehiculo['linea'] ?? null), $vehiculo['modelo'] ?? null])->filter()->implode(' / ')) }}</td>
                            <td>{{ $valor($vehiculo['placas'] ?? null) }}</td>
                            <td>{{ $valor($vehiculo['serie'] ?? null) }}</td>
                            <td>{{ $valor($vehiculo['color'] ?? null) }}</td>
                            <td>{{ $valor($vehiculo['calidad'] ?? null, '') }} {{ $valor($vehiculo['observaciones'] ?? ($vehiculo['partes_danadas'] ?? null), '') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No hay vehículos mapeados todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="section">
            <div class="section-title">5. Objetos / indicios relacionados</div>
            <table>
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Cadena de custodia</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($objetosPuesta as $objeto)
                        <tr>
                            <td>{{ $valor($objeto['tipo_objeto'] ?? null) }}</td>
                            <td>{{ $valor($objeto['descripcion'] ?? null) }}</td>
                            <td>{{ $valor($objeto['cantidad'] ?? null) }} {{ $valor($objeto['unidad_medida'] ?? null, '') }}</td>
                            <td>{{ $valor($objeto['cadena_custodia'] ?? null) }}</td>
                            <td>{{ $valor($objeto['observaciones'] ?? null) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No hay objetos mapeados todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <div class="signatures">
            <div class="signature">Policía que pone a disposición</div>
            <div class="signature">Autoridad receptora</div>
            <div class="signature">Recibió</div>
        </div>

        <div class="hint">
            Formato generado desde el sistema para impresión y entrega física ante el MP.
        </div>
    </main>
</body>
</html>
