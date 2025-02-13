<html>
<head>
  <meta charset="UTF-8">
  <title>Reporte del Hecho</title>
  <style>
      @page {
        size: 216mm 340mm;
        margin: 10mm;
      }
      body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 20px;
      }
      /* Encabezado centrado */
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
      /* Ajustar cuadros al tamaño de las letras y eliminar espacio superior */
      .boxes-table {
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed; /* Asegura que la tabla ocupe todo el ancho */
            width: 100%; /* Hace que las tablas ocupen todo el ancho disponible */
        }

        .boxes-table td {
            border: 1px solid #000;
            padding: 5px;
            font-size: 12px;
            white-space: nowrap;
            vertical-align: top;
            line-height: 1;
            margin: 0;
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

  <!-- Fecha, Hora, Folio C5i, ID del sistema -->
  <table class="boxes-table">
    <tr>
      <td><strong>Fecha</strong><br>{{ $hecho->fecha }}</td>
      <td><strong>Hora</strong><br>{{ $hecho->hora }}</td>
      <td><strong>Folio C5i</strong><br>{{ $hecho->folio_c5i }}</td>
      <td><strong>ID</strong><br>{{ $hecho->id }}</td>
    </tr>
  </table>

  <!-- Perito, Unidad, Autorización de Práctico -->
  <table class="boxes-table">
    <tr>
      <td><strong>Perito</strong><br>{{ $hecho->perito }}</td>
      <td><strong>Unidad</strong><br>{{ $hecho->unidad }}</td>
      <td><strong>Autorización de Práctico</strong><br>{{ $hecho->autorizacion_practico }}</td>
    </tr>
  </table>

  <!-- Ubicación del Hecho -->
  <table class="boxes-table">
    <tr>
      <td><strong>Calle</strong><br>{{ $hecho->calle }}</td>
      <td><strong>Colonia</strong><br>{{ $hecho->colonia }}</td>
      <td><strong>Municipio</strong><br>{{ $hecho->municipio }}</td>
      <td><strong>Sector</strong><br>{{ $hecho->sector }}</td>
    </tr>
  </table>

  <br>

  <!-- Vehículos involucrados -->
  <table class="boxes-table">
    @foreach($hecho->vehiculos as $vehiculo)
      <!-- Vehículo -->
      <table class="boxes-table">
        <tr>
          <td><strong>Placas</strong><br>{{ $vehiculo->placas }}</td>
          <td><strong>Marca</strong><br>{{ $vehiculo->marca }}</td>
          <td><strong>Modelo</strong><br>{{ $vehiculo->modelo }}</td>
          <td><strong>Color</strong><br>{{ $vehiculo->color }}</td>
          <td><strong>Tipo</strong><br>{{ $vehiculo->tipo }}</td>
          <td><strong>Línea</strong><br>{{ $vehiculo->linea }}</td>
        </tr>
      </table>

      <table class="boxes-table">
        <tr>
          <td><strong>Estado de Expedición de Placas</strong><br>{{ $vehiculo->estado_placas }}</td>
          <td><strong>Tipo de Servicio</strong><br>{{ $vehiculo->tipo_servicio }}</td>
          <td><strong>Capacidad de Personas</strong><br>{{ $vehiculo->capacidad_personas }}</td>
          <td><strong>Serie</strong><br>{{ $vehiculo->serie }}</td>
        </tr>
      </table>

      @foreach($vehiculo->conductores as $conductor)
            <table class="boxes-table">
                <tr>
                  <td><strong>Tarjeta de circulación a nombre de</strong><br>{{ $vehiculo->tarjeta_circulacion_nombre }}</td>
                  <td><strong>Conductor</strong><br>{{ $conductor->nombre }}</td>
                  <td><strong>Edad</strong><br>{{ $conductor->edad }}</td>
                  <td><strong>sexo</strong><br>{{ $conductor->sexo }}</td>
                  <td><strong>ocupacion</strong><br>{{ $conductor->ocupacion }}</td>
                </tr>
              </table>

              <table class="boxes-table">
                <tr>
                  <td><strong>domicilio</strong><br>{{ $conductor->domicilio }}</td>
                  <td><strong>cinturon</strong><br>{{ $conductor->cinturon }}</td>
                  <td><strong>antecedentes</strong><br>{{ $conductor->antecedentes }}</td>
                  <td><strong>Aliento Etilico</strong><br>{{ $conductor->aliento_etilico }}</td>
                  <td><strong>Cert. Lesiones</strong><br>{{ $conductor->certificado_lesiones }}</td>
                  <td><strong>Cert. Ebriedad</strong><br>{{ $conductor->certificado_alcoholemia }}</td>
                </tr>
              </table>

              <table class="boxes-table">
                <tr>
                  <td><strong>tipo_licencia</strong><br>{{ $conductor->tipo_licencia }}</td>
                  <td><strong>numero_licencia</strong><br>{{ $conductor->numero_licencia }}</td>
                  <td><strong>vigencia_licencia</strong><br>{{ $conductor->vigencia_licencia }}</td>
                  <td><strong>estado_licencia</strong><br>{{ $conductor->estado_licencia }}</td>
                </tr>
              </table>
          @endforeach

      <table class="boxes-table">
        <tr>
          <td><strong>grua</strong><br>{{ $vehiculo->grua }}</td>
          <td><strong>corralon</strong><br>{{ $vehiculo->corralon }}</td>
        </tr>
      </table>
      
    @endforeach
  </table>

  <br>

  <!-- Situación del Hecho -->
  <table class="boxes-table">
    <tr>
      <td><strong>Situación</strong><br>{{ $hecho->situacion }}</td>
    </tr>
  </table>

  <!-- Situación del Tipo de hecho, tiempo, control de transito, superficie de la via -->
  <table class="boxes-table">
        <tr>
          <td><strong>tipo_hecho</strong><br>{{ $hecho->tipo_hecho }}</td>
          <td><strong>tiempo</strong><br>{{ $hecho->tiempo }}</td>
          <td><strong>control_transito</strong><br>{{ $hecho->control_transito }}</td>
          <td><strong>superficie_via</strong><br>{{ $hecho->superficie_via }}</td>
        </tr>
  </table>

  <!-- Causas-->
  <table class="boxes-table">
        <tr>
          <td><strong>causas</strong><br>{{ $hecho->causas }}</td>
        </tr>
  </table>


</body>
</html>
