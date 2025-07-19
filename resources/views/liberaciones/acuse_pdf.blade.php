<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Acuse de Liberación de Vehículo</title>

  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size:12px;
      margin:0 40px;
      position: relative; /* necesario para los absolute */
    }

    /* Encabezado con logo */
    .header-container {
      position:relative;
      margin-bottom:20px;
      background:url('{{ public_path("ssp.jpg") }}') no-repeat left top;
      background-size:280px auto;
    }
    .header-table {
      border-collapse:collapse;
      font-size:11px;
      margin-left:320px;
      width:380px;
    }
    .header-table td {
      border:1px solid #000;
      padding:6px;
    }

    h2 {
      text-align:left;
      font-size:10px;
      margin:20px 0 10px 320px;
    }
    h1 {
      text-align:left;
      font-size:10px;
    }

    /* Tabla principal */
    .main-table {
      border-collapse:collapse;
      width:380px;
      margin-bottom:20px;
    }
    .main-table th,
    .main-table td {
      border:1px solid #000;
      padding:6px;
      vertical-align:top;
    }
    .main-table th { width:40%; }
    .main-table td { width:60%; }

    /* Receptor ABSOLUTO y girado */
    .receptor {
      position: absolute;
      /* Ajusta top para bajar/ sube el bloque */
      top: 460px;
      /* Ajusta right para acercar/alejar del borde derecho */
      right: -140px;
      width:160px;
      height:160px;
      padding:6px;
      font-size:10px;
      border:none;
      text-align:left;
      transform: rotate(-90deg);
      transform-origin: top right;
    }

    /* Firma y QR */
    .firma {
      margin-top: 200px;
      text-align:center;
      font-size:10px;
    }
    .qr {
      position:absolute;
      bottom:40px;
      right:40px;
    }
  </style>
</head>
<body>

  {{-- Encabezado --}}
  <div class="header-container">
    <table class="header-table">
      <tr><td><strong>Dependencia</strong></td><td>Secretaría de Seguridad Pública</td></tr>
      <tr><td><strong>Sub‑dependencia</strong></td><td>Coordinación de Seguridad Vial</td></tr>
      <tr><td><strong>Oficina</strong></td><td>Unidad de Atención a Siniestros</td></tr>
      <tr><td><strong>No. de oficio</strong></td><td>UAS/LIBERACION/{{ $liberacion->folio_anual }}</td></tr>
      <tr><td><strong>Asunto</strong></td><td>Orden de Devolución</td></tr>
    </table>
  </div>

  {{-- Fecha larga --}}
  @php
    use Carbon\Carbon;
    $fechaFormateada = Carbon::parse($liberacion->fecha_liberacion)
                             ->translatedFormat('d \d\e F \d\e Y');
  @endphp
  <h2>Morelia, Michoacán a {{ $fechaFormateada }}</h2>
  <br><br><br><br>

  <h1>PREVIA IDENTIFICACIÓN ENTREGAR A: {{ $liberacion->personas_autorizadas }}</h1>
  <br>
  <h1>EL VEHÍCULO DE LAS SIGUIENTES CARACTERÍSTICAS:</h1>

  {{-- Datos del vehículo --}}
  <table class="main-table">
    <tr><th>Fecha de resguardo</th>
        <td>{{ \Carbon\Carbon::parse($liberacion->hecho->fecha)->format('d/m/Y') }}</td></tr>
    <tr><th>Marca</th>   <td>{{ $vehiculo->marca }}</td></tr>
    <tr><th>Tipo</th>    <td>{{ $vehiculo->tipo }}</td></tr>
    <tr><th>Modelo</th>  <td>{{ $vehiculo->modelo }}</td></tr>
    <tr><th>Serie</th>   <td>{{ $vehiculo->serie }}</td></tr>
    <tr><th>Placas</th>  <td>{{ $vehiculo->placas }}</td></tr>
    <tr><th>Color</th>   <td>{{ $vehiculo->color }}</td></tr>
    <tr><th>Motivo de devolución</th><td>{{ $liberacion->motivo_liberacion ?? 'No especificado' }}</td></tr>
  </table>

  {{-- Bloque receptor girado y posicionado con absolute --}}
  <div class="receptor">
    <strong>RECIBE:</strong><br><br>
    Nombre:<br><br>
    Fecha:<br><br>
    Firma:<br><br>
    Teléfono:
  </div>

  {{-- Firma autorizador --}}
  <div class="firma">
    <strong>ATENTAMENTE<br><br><br></strong>
    <strong>{{ $liberacion->autoriza ?? 'Ninguno' }}</strong><br>
    ___________________________
  </div>
  <br>

  {{-- Código QR --}}
  <div class="qr">
    <img src="{{ $qrBase64 }}" width="140" height="140" alt="QR">
  </div>

</body>
</html>
