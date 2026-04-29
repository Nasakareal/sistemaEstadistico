<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Examen {{ $tipoLicenciaLabel }}</title>
    <style>
        @page {
            size: letter;
            margin: 14mm 16mm;
        }

        body {
            font-family: Arial, sans-serif;
            color: #000;
            margin: 0;
            font-size: 11pt;
        }

        .actions {
            margin-bottom: 15px;
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

        .header {
            position: relative;
            min-height: 115px;
            margin-bottom: 10px;
        }

        .logo {
            position: absolute;
            top: 0;
            left: 0;
            width: 105px;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            padding-top: 62px;
            text-transform: uppercase;
        }

        .instructions {
            margin-top: 14px;
            margin-bottom: 18px;
            text-align: justify;
            line-height: 1.45;
        }

        .datos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 10.5pt;
        }

        .datos td {
            padding: 6px 4px;
            vertical-align: bottom;
        }

        .linea {
            border-bottom: 1px solid #000;
            height: 18px;
        }

        .question {
            break-inside: avoid;
            page-break-inside: avoid;
            margin-bottom: 12px;
            line-height: 1.35;
        }

        .question-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .answer {
            margin-left: 18px;
            margin-bottom: 3px;
        }

        .empty {
            margin-top: 30px;
            text-align: center;
            font-weight: bold;
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

    <div class="header">
        <img src="{{ public_path('guardiacivil.png') }}" class="logo">
        <div class="title">
            EXAMEN TEORICO PARA OBTENER LICENCIA DE CONDUCIR TIPO {{ strtoupper($tipoLicenciaLabel) }}
        </div>
    </div>

    <div class="instructions">
        <strong>INSTRUCCIONES:</strong> conteste lo que se indica, en las preguntas de opción múltiple no deberá contestar más de dos opciones o será anulada, para aprobar deberá tener un mínimo de 20 aciertos.
    </div>

    <table class="datos">
        <tr>
            <td width="12%"><strong>NOMBRE:</strong></td>
            <td width="48%" class="linea"></td>
            <td width="10%"><strong>FECHA:</strong></td>
            <td width="30%" class="linea"></td>
        </tr>
        <tr>
            <td><strong>FOLIO:</strong></td>
            <td class="linea"></td>
            <td><strong>CALIF.:</strong></td>
            <td class="linea"></td>
        </tr>
    </table>

    @forelse($preguntas as $index => $pregunta)
        <div class="question">
            <div class="question-title">
                {{ $index + 1 }}.- {{ $pregunta->pregunta }}
            </div>

            @foreach($pregunta->respuestas as $respuestaIndex => $respuesta)
                <div class="answer">
                    {{ chr(65 + $respuestaIndex) }}) {{ $respuesta->respuesta }}
                </div>
            @endforeach
        </div>
    @empty
        <div class="empty">
            No hay preguntas activas para este tipo de licencia.
        </div>
    @endforelse
</body>
</html>
