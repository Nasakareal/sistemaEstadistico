<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar Constancia {{ $constancia->folio }}</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
        }

        .card {
            width: 100%;
            max-width: 560px;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(17, 24, 39, 0.12);
            overflow: hidden;
        }

        .header {
            padding: 22px 24px;
            background: #111827;
            color: #fff;
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
        }

        .body {
            padding: 24px;
        }

        .status {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .status.ACTIVA {
            background: #dcfce7;
            color: #166534;
        }

        .status.IMPRESA_INACTIVA {
            background: #fef3c7;
            color: #92400e;
        }

        .status.EXPIRADA,
        .status.CANCELADA {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 4px;
            margin-bottom: 18px;
            font-weight: 700;
        }

        .alert.success {
            background: #dcfce7;
            color: #166534;
        }

        .alert.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .alert.info {
            background: #e0f2fe;
            color: #075985;
        }

        dl {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 10px 16px;
            margin: 0;
        }

        dt {
            font-weight: 700;
            color: #374151;
        }

        dd {
            margin: 0;
        }

        .footer {
            padding: 16px 24px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #4b5563;
        }
    </style>
</head>
<body>
@php
    $estatusTexto = str_replace('_', ' ', $constancia->estatus);
@endphp

<main class="card">
    <div class="header">
        <h1>Constancia de Manejo</h1>
    </div>

    <div class="body">
        @if($mensaje)
            <div class="alert {{ $tipoMensaje }}">{{ $mensaje }}</div>
        @elseif(!auth()->check() && $constancia->estatus === 'IMPRESA_INACTIVA')
            <div class="alert info">Constancia pendiente de activacion. El perito debe iniciar sesion y volver a escanear el QR.</div>
        @endif

        <span class="status {{ $constancia->estatus }}">{{ $estatusTexto }}</span>

        <dl>
            <dt>Folio</dt>
            <dd>{{ $constancia->folio }}</dd>

            <dt>Solicitante</dt>
            <dd>{{ $constancia->nombre_solicitante ?: 'Pendiente' }}</dd>

            <dt>Tipo</dt>
            <dd>{{ $constancia->tipo_licencia ?: 'Pendiente' }}</dd>

            <dt>Resultado</dt>
            <dd>{{ optional($constancia->examen)->resultado ?: 'Sin examen' }}</dd>

            <dt>Activacion</dt>
            <dd>{{ $constancia->fecha_activacion ? $constancia->fecha_activacion->format('d/m/Y H:i') : 'Pendiente' }}</dd>

            <dt>Expiracion</dt>
            <dd>{{ $constancia->fecha_expiracion ? $constancia->fecha_expiracion->format('d/m/Y H:i') : 'Pendiente' }}</dd>

            <dt>Perito</dt>
            <dd>{{ optional($constancia->peritoActivador)->name ?: 'Pendiente' }}</dd>
        </dl>
    </div>

    <div class="footer">
        La vigencia corre por 10 dias a partir de la activacion de la constancia.
    </div>
</main>
</body>
</html>
