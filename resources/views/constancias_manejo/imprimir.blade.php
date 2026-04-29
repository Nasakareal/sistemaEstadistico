<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia {{ $constancia->folio }}</title>
    <style>
        @page {
            size: 216mm 279mm;
            margin: 6mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #e5e5e5;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 15px;
        }

        .sheet {
            position: relative;
            width: 204mm;
            min-height: 267mm;
            margin: 0 auto;
            padding: 8mm 10mm 8mm 10mm;
            background: #fff;
        }

        .logo {
            width: 38mm;
            height: 27mm;
            object-fit: contain;
            object-position: left top;
            display: block;
            margin-bottom: 2mm;
        }

        .title-row {
            display: grid;
            grid-template-columns: 10mm 1fr;
            align-items: start;
        }

        .side-label {
            height: 231mm;
            border: 1px solid #000;
            border-right: 0;
            position: relative;
        }

        .side-label span {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 225mm;
            transform: translate(-50%, -50%) rotate(-90deg);
            transform-origin: center;
            font-size: 11px;
            line-height: 1.4;
            text-align: center;
        }

        .document {
            min-height: 231mm;
            border-left: 1px solid #000;
            padding-left: 2mm;
        }

        .heading {
            background: #d1cfcf;
            text-align: center;
            font-weight: 700;
            padding: 2mm 0;
            margin-bottom: 5mm;
        }

        .black-rule {
            height: 3mm;
            background: #000;
            margin-bottom: 7mm;
        }

        .foundation {
            display: grid;
            grid-template-columns: 30mm 1fr;
            gap: 2mm;
            line-height: 1.35;
            margin-bottom: 14mm;
        }

        .label {
            font-weight: 700;
        }

        .line-row {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 2mm;
            align-items: end;
            margin: 7mm 2mm;
        }

        .line {
            border-bottom: 1px solid #000;
            min-height: 5mm;
            padding: 0 2mm 1mm;
            font-weight: 700;
        }

        .option-row {
            display: grid;
            grid-template-columns: 68mm 1fr;
            align-items: center;
            margin: 4mm 2mm;
        }

        .option-label {
            background: #d1cfcf;
            padding: 2mm 3mm;
        }

        .options {
            padding-left: 4mm;
            white-space: nowrap;
        }

        .box {
            display: inline-block;
            min-width: 8mm;
            text-align: center;
            font-weight: 700;
        }

        .permit-note {
            background: #d1cfcf;
            margin: 6mm 2mm 13mm;
            padding: 2mm 3mm;
        }

        .date-line {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 1mm;
            margin: 0 2mm 16mm;
            align-items: end;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 70mm 1fr;
            gap: 18mm;
            align-items: start;
            margin: 0 6mm 0 12mm;
        }

        .signature {
            text-align: center;
            padding-top: 10mm;
        }

        .signature-line {
            margin: 32mm auto 1mm;
            border-top: 1px solid #000;
            width: 56mm;
        }

        .qr-box {
            border: 3px solid #000;
            height: 65mm;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .qr-box img {
            width: 46mm;
            height: 46mm;
        }

        .qr-link {
            position: absolute;
            left: 3mm;
            right: 3mm;
            bottom: 2mm;
            font-size: 8px;
            text-align: center;
            overflow-wrap: anywhere;
        }

        .folio {
            position: absolute;
            right: 10mm;
            bottom: -1mm;
            font-weight: 700;
        }

        .print-actions {
            position: fixed;
            top: 12px;
            right: 12px;
            display: flex;
            gap: 8px;
        }

        .print-actions button {
            border: 0;
            border-radius: 4px;
            padding: 9px 14px;
            background: #111827;
            color: #fff;
            cursor: pointer;
            font-weight: 700;
        }

        @media print {
            html, body {
                width: 216mm;
                min-height: 279mm;
                background: #fff;
            }

            .sheet {
                margin: 0 auto;
                box-shadow: none;
                page-break-after: avoid;
            }

            .print-actions {
                display: none;
            }
        }
    </style>
</head>
<body>
@php
    $tipo = $constancia->tipo_licencia;
    $resultado = optional($constancia->examen)->resultado;
    $esApto = $resultado === 'APROBADO';
    $fechaExpedicion = now('America/Mexico_City')->translatedFormat('d \d\e F \d\e Y');
@endphp

<div class="print-actions">
    <button type="button" onclick="window.print()">Imprimir</button>
</div>

<main class="sheet">
    <img class="logo" src="{{ asset('img/michoacan_vertical.png') }}" alt="Gobierno de Michoacan">

    <section class="title-row">
        <aside class="side-label">
            <span>El presente certificado tiene vigencia de 10 dias habiles a partir del dia siguiente de la fecha de expedicion, y es valido en las Receptorias de rentas del Estado.</span>
        </aside>

        <div class="document">
            <div class="heading">Constancia de Acreditacion y Conocimientos Generales</div>
            <div class="black-rule"></div>

            <div class="foundation">
                <div class="label">FUNDAMENTO:</div>
                <div>
                    De conformidad con lo señalado en los numerales 130, 139, y 122 de la Ley de Movilidad y Seguridad
                    Vial del Estado de Michoacan de Ocampo, y articulos 386, 388, 389 y 390, del Reglamento de la Ley
                    de Movilidad y Seguridad Vial del Estado de Michoacan de Ocampo.
                </div>
            </div>

            <div class="line-row">
                <div>Que el (la) C.</div>
                <div class="line">{{ $constancia->nombre_solicitante }}</div>
            </div>

            <div class="line-row">
                <div>Con domicilio en:</div>
                <div class="line"></div>
            </div>

            <div class="option-row">
                <div class="option-label">Solicito licencia de:</div>
                <div class="options">
                    Automovilista [<span class="box">{{ $tipo === 'AUTOMOVILISTA' ? 'X' : '' }}</span>]
                    Chofer [<span class="box">{{ $tipo === 'CHOFER' ? 'X' : '' }}</span>]
                    Motociclista [<span class="box">{{ $tipo === 'MOTOCICLISTA' ? 'X' : '' }}</span>]
                    Servicio Publico [<span class="box">{{ $tipo === 'SERVICIO_PUBLICO' ? 'X' : '' }}</span>]
                </div>
            </div>

            <div class="option-row">
                <div class="option-label">Con vigencia de:</div>
                <div class="options">
                    2 años [<span class="box"></span>]
                    3 años [<span class="box"></span>]
                    4 años [<span class="box"></span>]
                    5 años [<span class="box"></span>]
                    PERMANENTE [<span class="box"></span>]
                </div>
            </div>

            <div class="option-row">
                <div class="option-label">Solicito permiso de conducir:</div>
                <div class="options">
                    Automovilista [<span class="box">{{ $tipo === 'PERMISO' ? 'X' : '' }}</span>]
                </div>
            </div>

            <div class="option-row">
                <div>Con vigencia de:</div>
                <div class="options">
                    1 año [<span class="box"></span>]
                </div>
            </div>

            <div class="option-row">
                <div class="option-label">El mismo se encuentra:</div>
                <div class="options">
                    APTO [<span class="box">{{ $esApto ? 'X' : '' }}</span>]
                    NO APTO [<span class="box">{{ $resultado && !$esApto ? 'X' : '' }}</span>]
                </div>
            </div>

            <div class="permit-note">
                Para obtener el Permiso para Conducir <strong>"TIPO A"</strong> al Ciudadano (a) que tenga cumplidos 16 años de edad.
            </div>

            <div class="date-line">
                <div>Lugar y fecha de expedicion:</div>
                <div class="line">Morelia, Michoacan a {{ $fechaExpedicion }}</div>
            </div>

            <div class="signature-grid">
                <div class="signature">
                    <div>AUTORIDAD QUE CERTIFICA</div>
                    <div class="signature-line"></div>
                    <div>Nombre y Cargo</div>
                </div>

                <div class="qr-box">
                    <img src="{{ $qrBase64 }}" alt="QR de activacion">
                    <div class="qr-link">{{ $qrUrl }}</div>
                </div>
            </div>
        </div>
    </section>

    <div class="folio">{{ $constancia->folio }}</div>
</main>
</body>
</html>
