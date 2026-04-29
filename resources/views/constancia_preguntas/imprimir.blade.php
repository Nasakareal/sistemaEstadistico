<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Examen {{ $tipoLicenciaLabel }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111827;
            margin: 24px;
        }

        .actions {
            margin-bottom: 18px;
        }

        button {
            background: #111827;
            border: 0;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
            padding: 10px 14px;
        }

        .header {
            border-bottom: 2px solid #111827;
            margin-bottom: 18px;
            padding-bottom: 12px;
        }

        .meta {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, 1fr);
            margin: 18px 0;
        }

        .line {
            border-bottom: 1px solid #111827;
            min-height: 24px;
        }

        .question {
            break-inside: avoid;
            margin-bottom: 18px;
        }

        .question strong {
            display: block;
            margin-bottom: 8px;
        }

        .answer {
            margin: 6px 0 6px 18px;
        }

        .key {
            page-break-before: always;
        }

        @media print {
            body {
                margin: 12mm;
            }

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

    <div class="header">
        <h1>Examen de Manejo</h1>
        <p><strong>Tipo de licencia:</strong> {{ $tipoLicenciaLabel }}</p>
    </div>

    <div class="meta">
        <div>
            <strong>Nombre:</strong>
            <div class="line"></div>
        </div>
        <div>
            <strong>Fecha:</strong>
            <div class="line"></div>
        </div>
        <div>
            <strong>Folio:</strong>
            <div class="line"></div>
        </div>
        <div>
            <strong>Calificación:</strong>
            <div class="line"></div>
        </div>
    </div>

    @forelse($preguntas as $index => $pregunta)
        <div class="question">
            <strong>{{ $index + 1 }}. {{ $pregunta->pregunta }}</strong>
            @foreach($pregunta->respuestas as $respuestaIndex => $respuesta)
                <div class="answer">
                    {{ chr(65 + $respuestaIndex) }}) {{ $respuesta->respuesta }}
                </div>
            @endforeach
        </div>
    @empty
        <p>No hay preguntas activas para este tipo de licencia.</p>
    @endforelse

    @if($preguntas->isNotEmpty())
        <div class="key">
            <h2>Clave de respuestas</h2>
            @foreach($preguntas as $index => $pregunta)
                @php
                    $correcta = $pregunta->respuestas->values()->search(function ($respuesta) {
                        return (bool) $respuesta->es_correcta;
                    });
                @endphp
                <p>
                    {{ $index + 1 }}.
                    {{ $correcta === false ? 'Sin respuesta correcta marcada' : chr(65 + $correcta) }}
                </p>
            @endforeach
        </div>
    @endif
</body>
</html>
