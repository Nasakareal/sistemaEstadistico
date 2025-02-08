<html>
<head>
  <meta charset="UTF-8">
  <title>Reporte del Hecho</title>
  <style>
    @page {
      size: 216mm 340mm;
      margin: 20mm;
    }
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
    }
    /* Encabezado centrado: se usa flex para centrar la imagen y el texto */
    .header {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 20px;
    }
    .header img {
      width: 50px;
      height: 50px;
    }
    .header-text {
      text-align: center;
    }
    .title-text {
      font-size: 16px;
      font-weight: bold;
      margin: 0;
    }
    .subtitle-text {
      font-size: 12px;
      margin: 0;
      margin-top: 5px;
    }
    /* Tabla para los cuadros: cada celda se ajusta al contenido */
    .boxes-table {
      border-collapse: collapse;
      margin-top: 20px;
      table-layout: auto;
    }
    .boxes-table td {
      border: 1px solid #000;
      /* Se reduce el padding en la parte superior */
      padding: 0 2px 2px 2px;
      font-size: 12px;
      white-space: nowrap;
      width: 1%;
      vertical-align: top; /* Alinea el contenido al tope */
    }
  </style>
</head>
<body>
  <!-- Encabezado centrado -->
  <div class="header">
    <img src="{{ asset('Favicons.ico') }}" alt="Favicon" class="favicon" width="50" height="50">
    <div class="header-text">
      <p class="title-text">SECRETARÍA DE SEGURIDAD PÚBLICA</p>
      <p class="subtitle-text">INFORME TÉCNICO DE HECHO DE TRÁNSITO</p>
    </div>
  </div>

  <!-- Mapeo de cuadros mediante una tabla -->
  <table class="boxes-table">
    <tr>
      <td>
        <strong>Fecha</strong><br>
        {{ $hecho->fecha }}
      </td>
      <td>
        <strong>Cuadro 2</strong><br>
        Información adicional
      </td>
      <td>
        <strong>Cuadro 3</strong><br>
        Información adicional
      </td>
      <td>
        <strong>Cuadro 4</strong><br>
        Información adicional
      </td>
    </tr>
  </table>

  <!-- Resto de la información -->
  <p><strong>Folio C5i:</strong> {{ $hecho->folio_c5i }}</p>
  <p><strong>Perito:</strong> {{ $hecho->perito }}</p>
  <p><strong>Hora:</strong> {{ $hecho->hora }}</p>
  <p><strong>Sector:</strong> {{ $hecho->sector }}</p>
  <p><strong>Calle:</strong> {{ $hecho->calle }}</p>
  <p><strong>Colonia:</strong> {{ $hecho->colonia }}</p>
  <p><strong>Municipio:</strong> {{ $hecho->municipio }}</p>
  <p><strong>Situación:</strong> {{ $hecho->situacion }}</p>
</body>
</html>
