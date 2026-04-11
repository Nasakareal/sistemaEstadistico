<html>
<head>
  <meta charset="UTF-8">
  <title>Reporte del Hecho</title>
  <style>
      @page {
        size: 216mm 356mm;
        margin: 10mm;
      }
      body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 20px;
      }
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
      .boxes-table {
        border-collapse: collapse;
        margin-top: 10px;
        table-layout: fixed;
        width: 100%;
      }
      .boxes-table td, .boxes-table th {
        border: 1px solid #000;
        padding: 5px;
        font-size: 12px;
        white-space: normal;
        word-break: break-word;
        overflow-wrap: break-word;
        vertical-align: top;
        line-height: 1;
        margin: 0;
      }
      .vehiculo-title {
        text-align: center;
        font-weight: bold;
        font-size: 12px;
        background: #e9ecef;
      }
      .section-title {
        background: #e9ecef;
        font-weight: bold;
      }
      .id-big {
        font-size: 18px;
        font-weight: 800;
        line-height: 1.05;
        display: inline-block;
        margin-top: 2px;
      }
  </style>
</head>
<body>
@php
  $croquisPreview = optional($hecho->croquis)->imagen_preview;
  $croquisPreviewUrl = null;

  if ($croquisPreview) {
    $croquisPreviewUrl = \Illuminate\Support\Str::startsWith($croquisPreview, ['data:image', 'http://', 'https://'])
      ? $croquisPreview
      : asset($croquisPreview);
  }
@endphp
<br><br>
  <div class="header">
    <img src="{{ asset('Favicons.ico') }}" alt="Favicon" class="favicon" width="50" height="50">
    <div class="header-text">
      <p class="title-text">SECRETARÍA DE SEGURIDAD PÚBLICA</p>
      <p class="subtitle-text">INFORME TÉCNICO DE HECHO DE TRÁNSITO</p>
    </div>
  </div>

  <table class="boxes-table">
    <tr>
      <td><strong>Fecha</strong><br>{{ $hecho->fecha }}</td>
      <td><strong>Hora</strong><br>{{ $hecho->hora }}</td>
      <td><strong>Folio C5i</strong><br>{{ $hecho->folio_c5i }}</td>
      <td><strong>ID</strong><br><span class="id-big">{{ $hecho->id }}</span></td>
    </tr>
  </table>

  <table class="boxes-table">
    <tr>
      <td><strong>Perito</strong><br>{{ $hecho->perito }}</td>
      <td><strong>Unidad</strong><br>{{ $hecho->unidad }}</td>
      <td><strong>Autorización de Práctico</strong><br>{{ $hecho->autorizacion_practico }}</td>
    </tr>
  </table>

  <table class="boxes-table">
    <tr>
      <td><strong>Calle</strong><br>{{ $hecho->calle }}</td>
      <td><strong>Colonia</strong><br>{{ $hecho->colonia }}</td>
      <td><strong>Municipio</strong><br>{{ $hecho->municipio }}</td>
      <td><strong>Sector</strong><br>{{ $hecho->sector }}</td>
    </tr>
  </table>

  <br>

  @foreach($hecho->vehiculos as $vehiculo)
      <table class="boxes-table">
        <tr>
          <th colspan="6" class="vehiculo-title">Vehículo ({{ $loop->iteration }})</th>
        </tr>
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
          <td><strong>Entidad Placas</strong><br>{{ $vehiculo->estado_placas }}</td>
          <td><strong>Tipo de Servicio</strong><br>{{ $vehiculo->tipo_servicio }}</td>
          <td><strong>Capacidad</strong><br>{{ $vehiculo->capacidad_personas }}</td>
          <td><strong>Serie</strong><br>{{ $vehiculo->serie }}</td>
        </tr>
      </table>

      @foreach($vehiculo->conductores as $conductor)
        <table class="boxes-table">
          <tr>
            <td><strong>Tarjeta circulación</strong><br>{{ $vehiculo->tarjeta_circulacion_nombre }}</td>
            <td><strong>Conductor</strong><br>{{ $conductor->nombre }}</td>
            <td><strong>Edad</strong><br>{{ $conductor->edad }}</td>
            <td><strong>Sexo</strong><br>{{ $conductor->sexo }}</td>
            <td><strong>Ocupación</strong><br>{{ $conductor->ocupacion }}</td>
          </tr>
        </table>

        <table class="boxes-table">
          <tr>
            <td><strong>Domicilio</strong><br>{{ $conductor->domicilio }}</td>
            <td><strong>Cinturón</strong><br>{{ $conductor->cinturon }}</td>
            <td><strong>Antecedentes</strong><br>{{ $conductor->antecedentes }}</td>
            <td><strong>Aliento Etílico</strong><br>{{ $conductor->aliento_etilico }}</td>
            <td><strong>Cert. Lesiones</strong><br>{{ $conductor->certificado_lesiones }}</td>
            <td><strong>Cert. Ebriedad</strong><br>{{ $conductor->certificado_alcoholemia }}</td>
          </tr>
        </table>

        <table class="boxes-table">
          <tr>
            <td><strong>Tipo Licencia</strong><br>{{ $conductor->tipo_licencia }}</td>
            <td><strong>Número Licencia</strong><br>{{ $conductor->numero_licencia }}</td>
            <td><strong>Vigencia Licencia</strong><br>{{ $conductor->vigencia_licencia }}</td>
            <td><strong>Estado Licencia</strong><br>{{ $conductor->estado_licencia }}</td>
          </tr>
        </table>
      @endforeach

      <table class="boxes-table">
        <tr>
          <td><strong>Grua</strong><br>{{ $vehiculo->grua }}</td>
          <td><strong>Corralón</strong><br>{{ $vehiculo->corralon }}</td>
        </tr>
      </table>
      <br>
  @endforeach

  @if($hecho->lesionados->isNotEmpty())
    <table class="boxes-table">
      <tr>
        <td><strong>Nombre</strong></td>
        <td><strong>Edad</strong></td>
        <td><strong>Sexo</strong></td>
        <td><strong>Tipo de Lesión</strong></td>
        <td><strong>Hospitalizado</strong></td>
        <td><strong>Hospital</strong></td>
      </tr>
      @foreach($hecho->lesionados as $lesionado)
      <tr>
        <td>{{ $lesionado->nombre }}</td>
        <td>{{ $lesionado->edad ?? 'N/A' }}</td>
        <td>{{ $lesionado->sexo }}</td>
        <td>{{ $lesionado->tipo_lesion }}</td>
        <td>{{ $lesionado->hospitalizado ? 'Sí' : 'No' }}</td>
        <td>{{ $lesionado->hospital ?? 'N/A' }}</td>
      </tr>
      @endforeach
    </table>

    <table class="boxes-table">
      <tr>
        <td><strong>Atención en Sitio</strong></td>
        <td><strong>Ambulancia</strong></td>
        <td><strong>Paramédico</strong></td>
        <td><strong>Observaciones</strong></td>
      </tr>
      @foreach($hecho->lesionados as $lesionado)
      <tr>
        <td>{{ $lesionado->atencion_en_sitio ? 'Sí' : 'No' }}</td>
        <td>{{ $lesionado->ambulancia ?? 'N/A' }}</td>
        <td>{{ $lesionado->paramedico ?? 'N/A' }}</td>
        <td>{{ $lesionado->observaciones ?? 'N/A' }}</td>
      </tr>
      @endforeach
    </table>
  @endif

  <br>

  <table class="boxes-table">
    <tr>
      <td class="section-title"><strong>Estatus</strong></td>
    </tr>
    <tr>
      <td>{{ $hecho->situacion }}</td>
    </tr>
  </table>

  <table class="boxes-table">
    <tr>
      <td><strong>Tipo de Hecho</strong><br>{{ $hecho->tipo_hecho }}</td>
      <td><strong>Tiempo</strong><br>{{ $hecho->tiempo }}</td>
      <td><strong>Control de Tránsito</strong><br>{{ $hecho->control_transito }}</td>
      <td><strong>Superficie de la Vía</strong><br>{{ $hecho->superficie_via }}</td>
    </tr>
  </table>

  <table class="boxes-table">
    <tr>
      <td><strong>Causas</strong><br>{{ $hecho->causas }}</td>
    </tr>
  </table>

  @php
    $vehiculosConDanos = $hecho->vehiculos->filter(function($vehiculo) {
      return $vehiculo->monto_danos || $vehiculo->partes_danadas;
    });
  @endphp

  @if($vehiculosConDanos->count() > 0)
    <br>
    <table class="boxes-table">
      <tr>
        <th>Vehículo</th>
        <th>Partes Dañadas</th>
        <th>Monto Daños</th>
      </tr>
      @foreach($vehiculosConDanos as $vehiculo)
        <tr>
          <td>Vehículo {{ $loop->iteration }}</td>
          <td>{{ $vehiculo->partes_danadas ?? 'N/A' }}</td>
          <td>
            @if($vehiculo->monto_danos)
              ${{ number_format($vehiculo->monto_danos, 2) }}
            @else
              N/A
            @endif
          </td>
        </tr>
      @endforeach
    </table>
  @endif

  @if($hecho->danos_patrimoniales || $hecho->propiedades_afectadas || $hecho->monto_danos_patrimoniales)
    <br>
    <table class="boxes-table">
      <tr>
        <td><strong>Daños Patrimoniales</strong><br>{{ $hecho->danos_patrimoniales ?? 'N/A' }}</td>
      </tr>
    </table>
    <table class="boxes-table">
      <tr>
        <td><strong>Propiedades Afectadas</strong><br>{{ $hecho->propiedades_afectadas ?? 'N/A' }}</td>
        <td><strong>Monto Daños Patrimoniales</strong><br>
          @if($hecho->monto_danos_patrimoniales)
            ${{ number_format($hecho->monto_danos_patrimoniales, 2) }}
          @else
            N/A
          @endif
        </td>
      </tr>
    </table>
  @endif

  <br>
  <div style="margin-top: 20px; text-align: center; border: 2px solid #000; padding: 20px; min-height: 430px;">
    <h2 style="margin: 0 0 14px 0; font-size: 24px;">CROQUIS DEL LUGAR DEL HECHO</h2>
    @if($croquisPreviewUrl)
      <img src="{{ $croquisPreviewUrl }}" alt="Croquis del lugar del hecho" style="display: block; width: 100%; height: auto; margin: 0 auto;">
    @else
      <div style="height: 360px;"></div>
    @endif
  </div>

</body>
</html>
