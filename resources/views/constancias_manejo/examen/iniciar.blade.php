<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Examen de Manejo</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f3f4f6;
            color: #111827;
            margin: 0;
        }

        .page {
            margin: 0 auto;
            max-width: 920px;
            padding: 24px;
        }

        .panel {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(17, 24, 39, 0.08);
            overflow: hidden;
        }

        .header {
            background: #111827;
            color: #fff;
            padding: 24px;
        }

        .header h1 {
            font-size: 26px;
            margin: 0 0 8px;
        }

        .header p {
            margin: 0;
            opacity: 0.86;
        }

        .body {
            padding: 24px;
        }

        .meta {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(4, 1fr);
            margin-bottom: 22px;
        }

        .meta div {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
        }

        .meta small {
            color: #6b7280;
            display: block;
            margin-bottom: 4px;
        }

        .question {
            border-top: 1px solid #e5e7eb;
            padding: 20px 0;
        }

        .question h3 {
            font-size: 18px;
            margin: 0 0 14px;
        }

        .answer {
            align-items: flex-start;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            padding: 12px;
        }

        .answer input {
            margin-top: 3px;
        }

        .actions {
            border-top: 1px solid #e5e7eb;
            margin-top: 8px;
            padding-top: 20px;
            text-align: right;
        }

        button {
            background: #0f766e;
            border: 0;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            padding: 12px 18px;
        }

        .empty {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            color: #9a3412;
            padding: 16px;
        }

        .errors {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            color: #991b1b;
            margin-bottom: 18px;
            padding: 14px;
        }

        @media (max-width: 720px) {
            .page {
                padding: 12px;
            }

            .meta {
                grid-template-columns: 1fr;
            }

            .header,
            .body {
                padding: 18px;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="panel">
            <div class="header">
                <h1>Examen de Manejo</h1>
                <p>Contesta todas las preguntas antes de enviar tu examen.</p>
            </div>

            <div class="body">
                <div class="meta">
                    <div>
                        <small>Folio</small>
                        <strong>{{ $constancia->folio }}</strong>
                    </div>
                    <div>
                        <small>Solicitante</small>
                        <strong>{{ $constancia->nombre_solicitante ?? 'Pendiente' }}</strong>
                    </div>
                    <div>
                        <small>Sexo</small>
                        <strong>{{ $constancia->sexo ?? 'Pendiente' }}</strong>
                    </div>
                    <div>
                        <small>Tipo de licencia</small>
                        <strong>{{ str_replace('_', ' ', $constancia->tipo_licencia) }}</strong>
                    </div>
                </div>

                @if($errors->any())
                    <div class="errors">
                        <strong>Revisa tu examen.</strong>
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($preguntas->isEmpty())
                    <div class="empty">
                        No hay preguntas activas para este tipo de licencia. Solicita apoyo al perito examinador.
                    </div>
                @else
                    <form action="{{ route('constancias_manejo.examen.guardar', $token) }}" method="POST">
                        @csrf

                        @foreach($preguntas as $index => $pregunta)
                            <section class="question">
                                <h3>{{ $index + 1 }}. {{ $pregunta->pregunta }}</h3>
                                <input type="hidden" name="preguntas[]" value="{{ $pregunta->id }}">

                                @foreach($pregunta->respuestas as $respuesta)
                                    <label class="answer">
                                        <input
                                            type="radio"
                                            name="respuestas[{{ $pregunta->id }}]"
                                            value="{{ $respuesta->id }}"
                                            required
                                        >
                                        <span>{{ $respuesta->respuesta }}</span>
                                    </label>
                                @endforeach
                            </section>
                        @endforeach

                        <div class="actions">
                            <button type="submit">Enviar examen</button>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
