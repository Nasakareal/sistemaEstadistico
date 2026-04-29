<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Examen no disponible</title>
    <style>
        body {
            align-items: center;
            background: #f3f4f6;
            color: #111827;
            display: flex;
            font-family: Arial, sans-serif;
            justify-content: center;
            margin: 0;
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
        }

        .icon {
            align-items: center;
            background: #fef3c7;
            border-radius: 50%;
            color: #92400e;
            display: inline-flex;
            font-size: 32px;
            height: 64px;
            justify-content: center;
            margin-bottom: 16px;
            width: 64px;
        }

        h1 {
            margin: 0 0 12px;
        }

        p {
            color: #4b5563;
            line-height: 1.5;
            margin: 0;
        }
    </style>
</head>
<body>
    <section class="card">
        <div class="icon">!</div>
        <h1>Examen no disponible</h1>
        <p>{{ $mensaje ?? 'El acceso al examen no está disponible.' }}</p>
    </section>
</body>
</html>
