<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Concentrado mensual de alcoholimetría</title>
</head>
<body>
    <p>Buen día.</p>

    <p>
        Se adjunta el concentrado mensual de alcoholimetría correspondiente a
        <strong>{{ ucfirst($mes->locale('es')->translatedFormat('F Y')) }}</strong>.
    </p>

    <ul>
        <li>Municipio: <strong>{{ $resumen['municipio'] }}</strong>.</li>
        <li>Pruebas registradas en el sistema: <strong>{{ number_format($resumen['pruebas_reales']) }}</strong>.</li>
        <li>Conductores no aptos: <strong>{{ number_format($resumen['conductores_no_aptos']) }}</strong>.</li>
        <li>
            Conductores aptos presentados en el formato:
            <strong>{{ number_format($resumen['conductores_aptos_reportados']) }}</strong>
            (incluye el ajuste de {{ number_format($resumen['ajuste_aptos_por_boquillas_perdidas']) }}
            por boquillas perdidas).
        </li>
        <li>Boquillas perdidas: <strong>{{ number_format($resumen['boquillas']['perdidas']) }}</strong>.</li>
        <li>Total conciliado presentado en el formato: <strong>{{ number_format($resumen['pruebas_reportadas']) }}</strong>.</li>
    </ul>

    <p>
        Conciliación de boquillas:
        {{ number_format($resumen['boquillas']['existencia_inicial']) }} iniciales +
        {{ number_format($resumen['boquillas']['recibidas']) }} recibidas −
        {{ number_format($resumen['boquillas']['salidas_totales']) }} salidas =
        <strong>{{ number_format($resumen['boquillas']['existencia_final']) }} finales</strong>.
    </p>

    <p>Saludos.</p>
</body>
</html>
