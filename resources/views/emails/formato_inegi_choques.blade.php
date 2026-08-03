<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formato INEGI Choques</title>
</head>
<body>
    <p>Buen dia.</p>

    <p>
        Se adjunta el formato INEGI con los choques registrados del
        <strong>{{ $desde->format('d/m/Y') }}</strong>
        al
        <strong>{{ $hasta->format('d/m/Y') }}</strong>.
    </p>

    @if ($motivoReenvio)
        <p>
            Este correo corresponde a un reenvío corregido. El motivo es:
            <strong>{{ $motivoReenvio }}</strong>
        </p>

        <p>
            Ofrecemos una disculpa por el inconveniente y agradecemos que se considere este archivo
            en sustitución del enviado anteriormente.
        </p>
    @endif

    <p>Total de choques incluidos: <strong>{{ $totalChoques }}</strong>.</p>

    <p>Saludos.</p>
</body>
</html>
