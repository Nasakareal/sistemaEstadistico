<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Grúas por Delegación</title>
    <style>
        body{
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1f2937;
        }
        h1{
            margin: 0 0 4px;
            font-size: 17px;
            text-align: center;
            color: #0f172a;
        }
        .meta{
            margin-bottom: 10px;
            text-align: center;
            font-size: 9px;
            color: #475569;
        }
        .summary{
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }
        .summary td{
            border: 1px solid #cbd5e1;
            padding: 5px;
            text-align: center;
            font-weight: bold;
        }
        table.data{
            width: 100%;
            border-collapse: collapse;
        }
        table.data th{
            background: #1f4e78;
            color: #fff;
            border: 1px solid #1f4e78;
            padding: 5px;
            text-align: left;
            font-size: 8px;
        }
        table.data td{
            border: 1px solid #cbd5e1;
            padding: 4px;
            vertical-align: top;
        }
        .sin-grua{
            color: #92400e;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Grúas por Delegación</h1>
    <div class="meta">
        Generado {{ now('America/Mexico_City')->format('d/m/Y H:i') }}
        @if ($buscar !== '')
            · Filtro: {{ $buscar }}
        @endif
    </div>

    <table class="summary">
        <tr>
            <td>Delegaciones: {{ number_format($resumen['delegaciones']) }}</td>
            <td>Grúas únicas: {{ number_format($resumen['gruas_asignadas']) }}</td>
            <td>Asignaciones: {{ number_format($resumen['relaciones']) }}</td>
            <td>Sin grúa: {{ number_format($resumen['sin_gruas']) }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 13%;">Regional</th>
                <th style="width: 14%;">Delegación</th>
                <th style="width: 10%;">Municipio</th>
                <th style="width: 16%;">Grúa</th>
                <th style="width: 28%;">Domicilio</th>
                <th style="width: 9%;">Teléfono</th>
                <th style="width: 10%;">Corralón</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php($grua = $row['grua'] ?? null)
                <tr>
                    <td>{{ $row['regional'] }}</td>
                    <td>{{ $row['delegacion'] }}</td>
                    <td>{{ $row['municipio'] ?: '—' }}</td>
                    <td>
                        @if ($grua)
                            {{ $grua['nombre'] }}
                        @else
                            <span class="sin-grua">Sin grúa asignada</span>
                        @endif
                    </td>
                    <td>{{ $grua['direccion'] ?? '—' }}</td>
                    <td>{{ $grua['telefono'] ?? '—' }}</td>
                    <td>{{ $grua['ubicacion_corralon'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No se encontraron registros.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
