<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Acuse de Liberación de Vehículo</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 12px;
      margin: 0 40px;
    }
    /* Contenedor con fondo (logo) */
    .header-container {
      position: relative;
      margin-bottom: 20px;

      /* aquí va la imagen de fondo */
      background: url("{{ public_path('ssp.jpg') }}") no-repeat left top;
      background-size: 280px auto;  /* ancho fijo de 150px */
    }

    /* Tabla del encabezado */
    .header-table {
      border-collapse: collapse;
      font-size: 11px;
      margin-left: 320px;
      width: calc(100% - 200px);
    }

    .header-table td {
      border: 1px solid #000;
      padding: 6px;
    }

    h2 {
      text-align: left;
      font-size: 10px;
      margin: 20px 0 10px 320px;
    }

    h1 {
      text-align: left;
      font-size: 10px;
    }


    .main-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    .main-table th,
    .main-table td {
      border: 1px solid #000;
      padding: 6px;
      vertical-align: top;
    }

    .firma {
      margin-top: 50px;
      text-align: center;
    }
    .qr {
      margin-top: 30px;
      text-align: right;
    }
  </style>
</head>
<body>

  {{-- Encabezado con logo de fondo --}}
  <div class="header-container">
    <table class="header-table">
      <tr>
        <td><strong>Dependencia</strong></td>
        <td>Secretaría de Seguridad Pública</td>
      </tr>
      <tr>
        <td><strong>Sub-dependencia</strong></td>
        <td>Coordinación de Seguridad Vial</td>
      </tr>
      <tr>
        <td><strong>Oficina</strong></td>
        <td>Unidad de Atención a Siniestros</td>
      </tr>
      <tr>
        <td><strong>No. de oficio</strong></td>
        <td>UAS/LIBERACION/{{ $liberacion->folio_anual }}</td>
      </tr>
      <tr>
        <td><strong>Asunto</strong></td>
        <td>Orden de Devolución</td>
      </tr>
    </table>
  </div>

    {{-- Título --}}
    @php
        use Carbon\Carbon;
        $fechaFormateada = Carbon::parse($liberacion->fecha_liberacion)->translatedFormat('d \d\e F \d\e Y');
    @endphp

    <h2 style="text-align: left; margin-left: 320px;">
        Morelia, Michoacán a {{ $fechaFormateada }}
    </h2>

    <h1 style="text-align: left;">
        PREVIA IDENTIFICACIÓN ENTREGAR A: {{ $liberacion->personas_autorizadas }}
    </h1>

    <br>

    <h1 style="text-align: left;">
        EL VEHÍCULO DE LAS SIGUIENTES CARACTERISTICAS:
    </h1>


  {{-- Datos del vehículo --}}
  <table class="main-table">
    <tr>
      <th>Marca</th>
      <td>{{ $vehiculo->marca }}</td>
      <th>Tipo</th>
      <td>{{ $vehiculo->tipo }}</td>
    </tr>
    <tr>
      <th>Modelo</th>
      <td>{{ $vehiculo->modelo }}</td>
      <th>Color</th>
      <td>{{ $vehiculo->color }}</td>
    </tr>
    <tr>
      <th>Placas</th>
      <td>{{ $vehiculo->placas }}</td>
      <th>Serie</th>
      <td>{{ $vehiculo->serie }}</td>
    </tr>
    <tr>
      <th>Partes dañadas</th>
      <td colspan="3">{{ $vehiculo->partes_danadas ?? 'N/D' }}</td>
    </tr>
    <tr>
      <th>Monto de daños</th>
      <td colspan="3">${{ number_format($vehiculo->monto_danios ?? 0, 2) }}</td>
    </tr>
    <tr>
      <th>Personas autorizadas</th>
      <td colspan="3">{{ $liberacion->personas_autorizadas }}</td>
    </tr>
    <tr>
      <th>Fecha de liberación</th>
      <td colspan="3">{{ $liberacion->fecha_liberacion }}</td>
    </tr>
    <tr>
      <th>Observaciones (Motivo de devolución)</th>
      <td colspan="3">{{ $liberacion->observaciones ?? 'Ninguna' }}</td>
    </tr>
  </table>

  {{-- Firma --}}
  <div class="firma">
    ___________________________<br>
    Firma del responsable del corralón
  </div>

  {{-- Código QR --}}
  <div class="qr">
    <img src="{{ $qrBase64 }}" width="120" height="120" alt="QR">
  </div>

</body>
</html>
