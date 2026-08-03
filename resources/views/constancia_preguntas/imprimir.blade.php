<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Examen {{ $tipoLicenciaLabel }}</title>
    <style>
        @page {
            size: letter;
            margin: 12mm 15mm 14mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            margin: 0;
            font-size: 10pt;
            line-height: 1.25;
        }

        .actions {
            margin-bottom: 14px;
        }

        button {
            background: #111827;
            border: 0;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
            padding: 10px 14px;
            font-size: 14px;
        }

        .sheet {
            width: 100%;
        }

        .header {
            margin-bottom: 8px;
        }

        .header-table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        .logo-cell {
            text-align: center;
            vertical-align: middle;
            width: 92px;
        }

        .logo {
            width: 76px;
            height: 76px;
            object-fit: contain;
        }

        .title {
            text-align: center;
            font-weight: 700;
            font-size: 12pt;
            padding: 0 92px 0 0;
            text-transform: uppercase;
            vertical-align: middle;
        }

        .exam-meta {
            border-collapse: collapse;
            border: 1px solid #111827;
            margin: 6px 0 12px;
            font-size: 9.5pt;
            width: 100%;
        }

        .exam-data {
            padding: 8px 10px;
            vertical-align: top;
        }

        .exam-meta strong {
            display: inline-block;
            min-width: 78px;
        }

        .exam-qr {
            padding: 6px 8px;
            width: 84px;
            text-align: center;
            font-size: 7pt;
            overflow-wrap: anywhere;
            vertical-align: top;
        }

        .exam-qr img {
            display: block;
            width: 82px;
            height: 82px;
            object-fit: contain;
            margin: 0 auto 2px;
        }

        .instructions {
            margin: 6px 0 24px;
            text-align: justify;
            font-size: 10pt;
            line-height: 1.35;
        }

        .question-table {
            border-collapse: collapse;
            margin-bottom: 9px;
            page-break-inside: avoid;
            width: 100%;
        }

        .question-table tr {
            page-break-inside: avoid;
        }

        .question-cell,
        .answer-marker,
        .answer-text {
            padding: 0;
            vertical-align: top;
        }

        .question-cell {
            text-align: left;
        }

        .question-title {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .answer-marker {
            padding-right: 8px;
            text-align: right;
            width: 64px;
        }

        .answer-text {
            line-height: 1.25;
        }

        .first-answer {
            border-collapse: collapse;
            font-weight: 400;
            width: 100%;
        }

        .empty {
            margin-top: 30px;
            text-align: center;
            font-weight: 700;
        }

        @media print {
            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    @unless($modoPdf ?? false)
        <div class="actions">
            <button type="button" onclick="window.print()">Imprimir</button>
        </div>
    @endunless

    <main class="sheet">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        @if(!empty($logoSrc))
                            <img src="{{ $logoSrc }}" class="logo" alt="Guardia Civil">
                        @endif
                    </td>
                    <td class="title">
                        EXAMEN TEORICO PARA OBTENER LICENCIA DE CONDUCIR TIPO {{ strtoupper($tipoLicenciaLabel) }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="instructions">
            <strong>INSTRUCCIONES:</strong> Conteste lo que se indica. En las preguntas de opción múltiple no deberá marcar más de una opción o la respuesta será anulada. Para aprobar deberá obtener mínimo 16 aciertos (80%).
        </div>

        @if(!empty($constancia) && !empty($qrBase64))
            <table class="exam-meta">
                <tr>
                    <td class="exam-data">
                        <div><strong>Folio:</strong> {{ $constancia->folio }}</div>
                        <div><strong>Solicitante:</strong> {{ $constancia->nombre_solicitante }}</div>
                        <div><strong>Sexo:</strong> {{ $constancia->sexo ?? 'N/D' }}</div>
                        <div><strong>Licencia:</strong> {{ str_replace('_', ' ', $constancia->tipo_licencia) }}</div>
                        <div><strong>Módulo:</strong> {{ optional($constancia->modulo)->nombre ?? 'N/D' }}</div>
                    </td>
                    <td class="exam-qr">
                        <img src="{{ $qrBase64 }}" alt="QR del examen escrito">
                        <div>Escanear para validar</div>
                    </td>
                </tr>
            </table>
        @endif

        @forelse($preguntas as $index => $pregunta)
            <table class="question-table">
                <tbody>
                    <tr>
                        <td class="question-cell" colspan="2">
                            <div class="question-title">
                                {{ $index + 1 }}.- {{ $pregunta->texto_impresion }}
                            </div>
                            @if($pregunta->respuestas->isNotEmpty())
                                <table class="first-answer">
                                    <tr>
                                        <td class="answer-marker">A.</td>
                                        <td class="answer-text">{{ $pregunta->respuestas->first()->texto_impresion }}</td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>
                    @foreach($pregunta->respuestas->slice(1)->values() as $respuestaIndex => $respuesta)
                        <tr>
                            <td class="answer-marker">{{ chr(66 + $respuestaIndex) }}.</td>
                            <td class="answer-text">{{ $respuesta->texto_impresion }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <div class="empty">
                No hay preguntas activas para este tipo de licencia.
            </div>
        @endforelse
    </main>
</body>
</html>

