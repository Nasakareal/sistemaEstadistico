<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resultado del Examen</title>
    <style>
        body {
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, sans-serif;
            margin: 0;
        }

        .page {
            align-items: center;
            display: flex;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
        }

        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(17, 24, 39, 0.08);
            max-width: 520px;
            padding: 28px;
            text-align: center;
            width: 100%;
        }

        .badge {
            border-radius: 999px;
            color: #fff;
            display: inline-block;
            font-weight: bold;
            margin-bottom: 18px;
            padding: 8px 14px;
        }

        .ok { background: #15803d; }
        .fail { background: #b91c1c; }

        h1 {
            margin: 0 0 12px;
        }

        .score {
            font-size: 42px;
            font-weight: bold;
            margin: 12px 0;
        }

        .grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, 1fr);
            margin-top: 18px;
        }

        .grid div {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
        }

        small {
            color: #6b7280;
            display: block;
            margin-bottom: 4px;
        }

        @media (max-width: 520px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="card">
            @php
                $aprobado = $examen->resultado === 'APROBADO';
            @endphp

            <span class="badge {{ $aprobado ? 'ok' : 'fail' }}">{{ $examen->resultado }}</span>
            <h1>Resultado del examen</h1>
            <p>Folio {{ $constancia->folio }}</p>

            <div class="score">{{ number_format($examen->calificacion, 2) }}</div>

            <div class="grid">
                <div>
                    <small>Preguntas</small>
                    <strong>{{ $examen->total_preguntas }}</strong>
                </div>
                <div>
                    <small>Aciertos</small>
                    <strong>{{ $examen->aciertos }}</strong>
                </div>
                <div>
                    <small>Errores</small>
                    <strong>{{ $examen->errores }}</strong>
                </div>
            </div>

            <p style="margin-top: 22px;">
                {{ $aprobado ? 'Acude con el perito examinador para continuar la activación.' : 'Solicita apoyo al perito examinador para el siguiente paso.' }}
            </p>
        </section>
    </main>
</body>
</html>
