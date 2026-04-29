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

