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
            font-size: 11pt;
            line-height: 1.3;
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
            position: relative;
            min-height: 96px;
            margin-bottom: 8px;
        }

        .logo {
            position: absolute;
            top: 0;
            left: 8mm;
            width: 76px;
            height: 76px;
            object-fit: contain;
        }

        .title {
            text-align: center;
            font-weight: 700;
            font-size: 12pt;
            padding-top: 54px;
            text-transform: uppercase;
        }

        .exam-meta {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: start;
            border: 1px solid #111827;
            padding: 8px 10px;
            margin: 6px 0 12px;
            font-size: 9.5pt;
        }

        .exam-meta strong {
            display: inline-block;
            min-width: 78px;
        }

        .exam-qr {
            width: 84px;
            text-align: center;
            font-size: 7pt;
            overflow-wrap: anywhere;
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
            font-size: 11pt;
            line-height: 1.45;
        }

        .question {
            break-inside: avoid;
            page-break-inside: avoid;
            margin-bottom: 24px;
        }

        .question-title {
            font-weight: 700;
            margin-bottom: 12px;
        }

        .answers {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            column-gap: 18px;
            row-gap: 7px;
            padding-left: 48px;
        }

        .answer {
            min-width: 0;
            white-space: normal;
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
    <div class="actions">
        <button type="button" onclick="window.print()">Imprimir</button>
    </div>

    <main class="sheet">
        <div class="header">
            @if(!empty($logoSrc))
                <img src="{{ $logoSrc }}" class="logo" alt="Guardia Civil">
            @endif
            <div class="title">
                EXAMEN TEORICO PARA OBTENER LICENCIA DE CONDUCIR TIPO {{ strtoupper($tipoLicenciaLabel) }}
            </div>
        </div>

        <div class="instructions">
            <strong>INSTRUCCIONES:</strong> conteste lo que se indica, en las preguntas de opción múltiple no deberá contestar más de dos opciones o será anulada, Para aprobar deberá obtener mínimo 16 aciertos (80%).
        </div>

        @if(!empty($constancia) && !empty($qrBase64))
            <section class="exam-meta">
                <div>
                    <div><strong>Folio:</strong> {{ $constancia->folio }}</div>
                    <div><strong>Solicitante:</strong> {{ $constancia->nombre_solicitante }}</div>
                    <div><strong>Sexo:</strong> {{ $constancia->sexo ?? 'N/D' }}</div>
                    <div><strong>Licencia:</strong> {{ str_replace('_', ' ', $constancia->tipo_licencia) }}</div>
                    <div><strong>Módulo:</strong> {{ optional($constancia->modulo)->nombre ?? 'N/D' }}</div>
                </div>
                <div class="exam-qr">
                    <img src="{{ $qrBase64 }}" alt="QR del examen escrito">
                    <div>Escanear para validar</div>
                </div>
            </section>
        @endif

        @forelse($preguntas as $index => $pregunta)
            <section class="question">
                <div class="question-title">
                    {{ $index + 1 }}.- {{ $pregunta->pregunta }}
                </div>

                <div class="answers">
                    @foreach($pregunta->respuestas as $respuestaIndex => $respuesta)
                        <div class="answer">
                            {{ chr(65 + $respuestaIndex) }}) {{ $respuesta->respuesta }}
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="empty">
                No hay preguntas activas para este tipo de licencia.
            </div>
        @endforelse
    </main>
</body>
</html>

