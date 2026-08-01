<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lote de Constancias de Manejo</title>
    <style>
        @page {
            size: letter portrait;
            margin: 6mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #000;
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.7pt;
        }

        .page {
            position: relative;
            height: 258mm;
            padding: 3mm 7mm 3mm 10mm;
        }

        .page.page-break {
            page-break-after: always;
        }

        .logo {
            width: 32mm;
            height: 20mm;
            object-fit: contain;
        }

        .document {
            border-left: 1px solid #000;
            padding-left: 3mm;
        }

        .heading {
            margin-bottom: 3mm;
            padding: 1.7mm 1mm;
            background: #d1cfcf;
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
        }

        .black-rule {
            height: 3mm;
            margin-bottom: 4mm;
            background: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .foundation {
            margin-bottom: 5mm;
            line-height: 1.35;
        }

        .foundation-label {
            width: 29mm;
            padding-right: 2mm;
            vertical-align: top;
            font-weight: bold;
        }

        .field-row {
            margin: 3.5mm 1mm;
        }

        .field-label {
            width: 34mm;
            padding-right: 2mm;
            vertical-align: bottom;
            white-space: nowrap;
        }

        .field-line {
            height: 6mm;
            padding: 0 2mm 1mm;
            border-bottom: 1px solid #000;
            vertical-align: bottom;
            font-weight: bold;
        }

        .option-row {
            margin: 2.5mm 1mm;
        }

        .option-label {
            width: 60mm;
            padding: 1.4mm 2mm;
            background: #d1cfcf;
            vertical-align: middle;
        }

        .option-label.clear {
            background: transparent;
        }

        .options {
            padding-left: 3mm;
            vertical-align: middle;
            white-space: nowrap;
            font-size: 8pt;
        }

        .box {
            display: inline-block;
            min-width: 5mm;
            text-align: center;
            font-weight: bold;
        }

        .permit-note {
            margin: 3.5mm 1mm 5mm;
            padding: 2mm 3mm;
            background: #d1cfcf;
        }

        .signature-table {
            margin: 1mm 5mm 0 8mm;
        }

        .signature-cell {
            width: 58%;
            padding: 2mm 10mm 0 0;
            text-align: center;
            vertical-align: top;
        }

        .signature-line {
            width: 55mm;
            margin: 18mm auto 1mm;
            border-top: 1px solid #000;
        }

        .qr-cell {
            width: 42%;
            height: 48mm;
            padding: 2mm;
            border: 2px solid #000;
            text-align: center;
            vertical-align: middle;
        }

        .qr-cell img {
            width: 36mm;
            height: 36mm;
        }

        .qr-url {
            margin-top: 1mm;
            font-size: 5.5pt;
            line-height: 1.1;
            word-break: break-all;
        }

        .folio {
            position: absolute;
            right: 8mm;
            bottom: 1mm;
            font-size: 10pt;
            font-weight: bold;
        }

        .validity-note {
            position: absolute;
            left: 18mm;
            right: 35mm;
            bottom: 1mm;
            text-align: center;
            font-size: 6.5pt;
            line-height: 1.2;
        }
    </style>
</head>
<body>
@foreach($constancias as $constancia)
    @php
        $tipo = $constancia->tipo_licencia;
        $resultado = optional($constancia->examen)->resultado;
        $esApto = $resultado === 'APROBADO';
        $qr = $constancia->qrDataUri();
        $url = $constancia->qrUrl();
    @endphp

    <section class="page {{ !$loop->last ? 'page-break' : '' }}">
        @if($logoDataUri)
            <img class="logo" src="{{ $logoDataUri }}" alt="Gobierno de Michoacán">
        @endif

        <div class="document">
            <div class="heading">Constancia de Acreditación y Conocimientos Generales</div>
            <div class="black-rule"></div>

            <table class="foundation">
                <tr>
                    <td class="foundation-label">FUNDAMENTO:</td>
                    <td>
                        De conformidad con lo señalado en los numerales 130, 139 y 122 de la Ley de Movilidad y Seguridad
                        Vial del Estado de Michoacán de Ocampo, y artículos 386, 388, 389 y 390 del Reglamento de la Ley
                        de Movilidad y Seguridad Vial del Estado de Michoacán de Ocampo.
                    </td>
                </tr>
            </table>

            <table class="field-row">
                <tr>
                    <td class="field-label">Que el (la) C.</td>
                    <td class="field-line">{{ $constancia->nombre_solicitante ?: '' }}</td>
                </tr>
            </table>

            <table class="field-row">
                <tr>
                    <td class="field-label">Con domicilio en:</td>
                    <td class="field-line">&nbsp;</td>
                </tr>
            </table>

            <table class="option-row">
                <tr>
                    <td class="option-label">Solicitó licencia de:</td>
                    <td class="options">
                        Automovilista [<span class="box">{{ $tipo === 'AUTOMOVILISTA' ? 'X' : '' }}</span>]
                        Chofer [<span class="box">{{ $tipo === 'CHOFER' ? 'X' : '' }}</span>]
                        Motociclista [<span class="box">{{ $tipo === 'MOTOCICLISTA' ? 'X' : '' }}</span>]
                        Servicio Público [<span class="box">{{ $tipo === 'SERVICIO_PUBLICO' ? 'X' : '' }}</span>]
                    </td>
                </tr>
            </table>

            <table class="option-row">
                <tr>
                    <td class="option-label">Con vigencia de:</td>
                    <td class="options">
                        2 años [<span class="box"></span>]
                        3 años [<span class="box"></span>]
                        4 años [<span class="box"></span>]
                        5 años [<span class="box"></span>]
                        PERMANENTE [<span class="box"></span>]
                    </td>
                </tr>
            </table>

            <table class="option-row">
                <tr>
                    <td class="option-label">Solicitó permiso de conducir:</td>
                    <td class="options">Automovilista [<span class="box">{{ $tipo === 'PERMISO' ? 'X' : '' }}</span>]</td>
                </tr>
            </table>

            <table class="option-row">
                <tr>
                    <td class="option-label clear">Con vigencia de:</td>
                    <td class="options">1 año [<span class="box"></span>]</td>
                </tr>
            </table>

            <table class="option-row">
                <tr>
                    <td class="option-label">El mismo se encuentra:</td>
                    <td class="options">
                        APTO [<span class="box">{{ $esApto ? 'X' : '' }}</span>]
                        NO APTO [<span class="box">{{ $resultado && !$esApto ? 'X' : '' }}</span>]
                    </td>
                </tr>
            </table>

            <div class="permit-note">
                Para obtener el Permiso para Conducir <strong>"TIPO A"</strong> al Ciudadano (a) que tenga cumplidos 16 años de edad.
            </div>

            <table class="field-row">
                <tr>
                    <td class="field-label">Lugar y fecha de expedición:</td>
                    <td class="field-line">&nbsp;</td>
                </tr>
            </table>

            <table class="signature-table">
                <tr>
                    <td class="signature-cell">
                        <div>AUTORIDAD QUE CERTIFICA</div>
                        <div class="signature-line"></div>
                        <div>Nombre y Cargo</div>
                    </td>
                    <td class="qr-cell">
                        <img src="{{ $qr }}" alt="QR de activación">
                        <div class="qr-url">{{ $url }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="validity-note">
            El presente certificado tiene vigencia de 10 días hábiles a partir del día siguiente de la fecha de expedición y es válido en las Receptorías de Rentas del Estado.
        </div>
        <div class="folio">{{ $constancia->folio }}</div>
    </section>
@endforeach
</body>
</html>
