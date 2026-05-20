<?php

namespace App\Services;

use App\Models\Hechos;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table;

class IphPuestaDisposicionDocxService
{
    private const INK = '1F2933';
    private const CREAM = 'EEE2C8';
    private const GREY = 'E7E7E7';
    private const GREEN = '385C46';
    private const LEGAL_W = 12240;
    private const LEGAL_H = 20160;
    private const LETTER_W = 12240;
    private const LETTER_H = 15840;
    private const MARGIN = 567;
    private const CONTENT_W = 11106;

    private $tempFiles = [];

    public function generar(Hechos $hecho, array $mapeo): array
    {
        Settings::setOutputEscapingEnabled(true);

        $data = $this->prepararDatos($hecho, $mapeo);
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);
        $this->registrarEstilos($phpWord);

        $this->agregarParteInformativo($phpWord, $data);
        $this->agregarIph($phpWord, $data);
        $this->agregarCadenaCustodia($phpWord, $data);

        $directorio = storage_path('app/temp');
        File::ensureDirectoryExists($directorio);

        $path = $directorio . DIRECTORY_SEPARATOR . uniqid('iph_puesta_disposicion_', true) . '.docx';

        try {
            IOFactory::createWriter($phpWord, 'Word2007')->save($path);
        } finally {
            $this->limpiarTemporales();
        }

        $folio = Str::slug((string) ($hecho->folio_c5i ?: $hecho->id), '_') ?: (string) $hecho->id;

        return [$path, "iph_puesta_disposicion_{$folio}.docx"];
    }

    private function prepararDatos(Hechos $hecho, array $mapeo): array
    {
        $hechoIph = $mapeo['hecho'] ?? [];
        $puesta = $mapeo['puesta_disposicion'] ?? [];
        $ubicacion = $hechoIph['ubicacion'] ?? [];
        $vehiculos = $mapeo['vehiculos_hecho'] ?? [];
        $lesionados = $mapeo['lesionados_hecho'] ?? [];
        $anexos = $mapeo['anexos'] ?? [];

        $fechaHecho = trim((string) ($hechoIph['fecha'] ?? ''));
        $horaHecho = trim((string) ($hechoIph['hora'] ?? ''));
        $fechaPuesta = trim((string) ($puesta['fecha_puesta'] ?? '')) ?: now('America/Mexico_City')->format('Y-m-d');
        $horaPuesta = trim((string) ($puesta['hora_puesta'] ?? '')) ?: now('America/Mexico_City')->format('H:i');
        $fechaHoraHecho = $this->carbon($fechaHecho, $horaHecho);
        $arribo = $fechaHoraHecho ? $fechaHoraHecho->copy()->addMinutes(30) : null;
        $recoleccion = $fechaHoraHecho ? $fechaHoraHecho->copy()->addMinutes(35) : now('America/Mexico_City')->addMinutes(35);
        $lugar = collect([$ubicacion['calle'] ?? null, $ubicacion['colonia'] ?? null, $ubicacion['municipio'] ?? null])
            ->map(fn ($dato) => trim((string) $dato))
            ->filter()
            ->implode(', ');

        if ($lugar === '') {
            $lugar = trim((string) ($ubicacion['ubicacion_formateada'] ?? ''));
        }

        $unidadId = (int) ($hechoIph['unidad_org_id'] ?? 0);
        $oficina = $unidadId === 2 ? 'Unidad de Delegaciones' : 'Unidad de Atención a Siniestros';
        $municipio = $this->title($ubicacion['municipio'] ?? 'Lazaro Cárdenas');
        $folio = preg_replace('/\s+/', '', (string) ($hechoIph['folio_c5i'] ?? '')) ?: (string) ($hecho->id ?? '');
        $nombrePolicia = $this->valor($puesta['nombre_policia'] ?? ($hechoIph['creador_nombre'] ?? ($hechoIph['perito'] ?? null)), '');
        $adscripcion = mb_strtoupper($this->valor($hechoIph['unidad_org_nombre'] ?? $oficina, ''), 'UTF-8');
        $gruas = collect($vehiculos)->map(function (array $vehiculo) {
            $nombre = $this->valorGrua($vehiculo['grua_nombre'] ?? null) ?: $this->valorGrua($vehiculo['grua'] ?? null);
            $direccion = $this->valorGrua($vehiculo['grua_direccion'] ?? null)
                ?: $this->valorGrua($vehiculo['grua_ubicacion_corralon'] ?? null)
                ?: $this->valorGrua($vehiculo['corralon'] ?? null);

            return (!$nombre && !$direccion) ? null : ['nombre' => $nombre, 'direccion' => $direccion];
        })->filter()->unique(fn ($grua) => mb_strtoupper(($grua['nombre'] ?? '') . '|' . ($grua['direccion'] ?? ''), 'UTF-8'))->values();
        $ubicacionCadena = mb_strtoupper($lugar, 'UTF-8');

        if (($ubicacion['lat'] ?? null) && ($ubicacion['lng'] ?? null)) {
            $ubicacionCadena .= ' CON COORDENADAS ' . $ubicacion['lat'] . ', ' . $ubicacion['lng'];
        }

        $lugarEntrega = collect([
            $gruas->pluck('nombre')->filter()->first(),
            $gruas->pluck('direccion')->filter()->first(),
            $ubicacionCadena,
        ])->filter()->first() ?: $ubicacionCadena;
        $fotos = [];

        if ($foto = $this->resolverImagen($anexos['foto_lugar'] ?? null)) {
            $fotos[] = ['path' => $foto, 'caption' => 'Fijación fotográfica del lugar de intervención'];
        }

        foreach ($vehiculos as $i => $vehiculo) {
            if ($foto = $this->resolverImagen($vehiculo['foto'] ?? null)) {
                $fotos[] = ['path' => $foto, 'caption' => 'Fijación fotográfica del vehículo ' . $this->letraIndice($i)];
            }
        }

        return [
            'hecho' => $hechoIph,
            'puesta' => $puesta ?: [],
            'ubicacion' => $ubicacion,
            'vehiculos' => $vehiculos,
            'lesionados' => $lesionados,
            'objetos' => $mapeo['objetos'] ?? [],
            'folio' => $folio,
            'fecha_hecho' => $fechaHecho,
            'hora_hecho' => $horaHecho,
            'fecha_puesta' => $fechaPuesta,
            'hora_puesta' => $horaPuesta,
            'fecha_texto' => $this->fechaTexto($fechaHecho),
            'fecha_puesta_texto' => $this->fechaTexto($fechaPuesta),
            'fecha_encabezado' => now('America/Mexico_City')->format('d-m-Y'),
            'fecha_intervencion_cadena' => $fechaHoraHecho ? $fechaHoraHecho->format('d-m-Y') : $this->fechaCorta($fechaHecho),
            'hora_intervencion_cadena' => $fechaHoraHecho ? $fechaHoraHecho->format('H:i') : substr($horaHecho, 0, 5),
            'hora_recoleccion_cadena' => $recoleccion->format('H:i'),
            'arribo' => $arribo,
            'lugar' => $lugar,
            'ubicacion_cadena' => $ubicacionCadena,
            'lugar_entrega' => mb_strtoupper($lugarEntrega, 'UTF-8'),
            'oficina' => $oficina,
            'municipio' => $municipio,
            'municipio_mayus' => mb_strtoupper($municipio, 'UTF-8'),
            'oficio' => $this->valor($puesta['oficio'] ?? ($hechoIph['oficio_mp'] ?? null), ''),
            'expediente' => $this->valor($puesta['carpeta_investigacion'] ?? null, ''),
            'nombre_policia' => $nombrePolicia,
            'nombre_policia_mayus' => mb_strtoupper($nombrePolicia, 'UTF-8'),
            'adscripcion' => $adscripcion,
            'autoridad' => mb_strtoupper($this->valor($puesta['autoridad_receptora'] ?? 'DIRECCIÓN DE CARPETAS DE INVESTIGACIÓN DE LA FISCALÍA GENERAL DE JUSTICIA EN EL ESTADO', ''), 'UTF-8'),
            'tipo_hecho' => mb_strtoupper($this->valor($hechoIph['tipo_hecho'] ?? 'HECHO DE TRÁNSITO', 'HECHO DE TRÁNSITO'), 'UTF-8'),
            'causas' => mb_strtoupper($this->valor($hechoIph['causas'] ?? null, ''), 'UTF-8'),
            'croquis' => $this->resolverImagen($anexos['croquis_preview'] ?? null),
            'fotos' => array_values(array_unique($fotos, SORT_REGULAR)),
        ];
    }

    private function registrarEstilos(PhpWord $phpWord): void
    {
        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 80]);
        $phpWord->addTableStyle('HeaderInfo', ['borderSize' => 0, 'cellMargin' => 35, 'alignment' => JcTable::CENTER, 'layout' => Table::LAYOUT_FIXED, 'unit' => TblWidth::TWIP]);
        $phpWord->addTableStyle('Clean', ['borderSize' => 0, 'cellMargin' => 40, 'alignment' => JcTable::CENTER, 'layout' => Table::LAYOUT_FIXED, 'unit' => TblWidth::TWIP]);
        $phpWord->addTableStyle('FormTable', ['borderSize' => 6, 'borderColor' => self::INK, 'cellMargin' => 45, 'alignment' => JcTable::CENTER, 'layout' => Table::LAYOUT_FIXED, 'unit' => TblWidth::TWIP]);
        $phpWord->addTableStyle('NoBorder', ['borderSize' => 0, 'cellMargin' => 20, 'alignment' => JcTable::CENTER, 'layout' => Table::LAYOUT_FIXED, 'unit' => TblWidth::TWIP]);
    }

    private function agregarParteInformativo(PhpWord $phpWord, array $d): void
    {
        $section = $this->seccion($phpWord, 'legal');
        $this->membreteParte($section, $d);
        $this->texto($section, $d['municipio'] . ' Michoacán a ' . $d['fecha_encabezado'] . '.', [], ['alignment' => Jc::RIGHT, 'spaceAfter' => 160]);
        $this->texto($section, $d['autoridad'], ['bold' => true, 'size' => 11], ['spaceAfter' => 0]);
        $this->texto($section, 'P R E S E N T E .', ['bold' => true, 'size' => 11, 'spacing' => 4], ['spaceAfter' => 260]);
        $this->texto($section, 'Por medio del presente, hago de su conocimiento que se pone a disposición el informe relacionado con el hecho registrado bajo el folio ' . $d['folio'] . ', ocurrido el día ' . $d['fecha_texto'] . ' aproximadamente a las ' . substr($d['hora_hecho'], 0, 5) . ' horas, en ' . $d['lugar'] . '.', [], ['alignment' => Jc::BOTH]);
        $this->texto($section, 'El presente parte informativo se emite con los datos capturados en el sistema, quedando pendiente la narrativa pormenorizada por parte del personal actuante cuando corresponda.', [], ['alignment' => Jc::BOTH]);
        $this->tituloReporte($section, 'I. PLANTEAMIENTO DEL PROBLEMA:');
        $this->texto($section, 'Determinar las circunstancias generales del hecho de tránsito y documentar los vehículos, personas, indicios y actuaciones relacionadas con la puesta a disposición.', [], ['alignment' => Jc::BOTH]);
        $this->tituloReporte($section, 'II. METODOLOGÍA APLICADA AL PRESENTE INFORME:');
        $this->texto($section, 'Se realizó revisión de la información capturada, inspección visual de los datos del lugar, vehículos, personas relacionadas, croquis, fijación fotográfica y elementos disponibles para la puesta a disposición.', [], ['alignment' => Jc::BOTH]);
        $this->tituloReporte($section, 'III. MATERIAL UTILIZADO:');
        foreach (['Unidad oficial', 'Cámara fotográfica o dispositivo de captura', 'Croquis del lugar', 'Datos capturados en el sistema'] as $item) {
            $section->addListItem($item, 0, ['size' => 10], null, ['spaceAfter' => 0]);
        }
        $this->tituloReporte($section, 'IV. UBICACIÓN Y CONDICIONES:');
        $this->texto($section, 'Lugar de intervención: ' . $d['lugar'] . '.', [], ['alignment' => Jc::BOTH]);
        $this->texto($section, 'Tipo de hecho: ' . $d['tipo_hecho'] . ($d['causas'] !== '' ? ' POR ' . $d['causas'] : '') . '.', [], ['alignment' => Jc::BOTH]);

        $section->addPageBreak();
        $this->tituloReporte($section, 'V. DESCRIPCIÓN DE VEHÍCULOS:');

        foreach ($d['vehiculos'] as $i => $vehiculo) {
            $this->texto($section, $this->descripcionVehiculo($vehiculo, $i), [], ['alignment' => Jc::BOTH]);
        }

        if (empty($d['vehiculos'])) {
            $this->texto($section, 'No se registraron vehículos relacionados en el sistema.', [], ['alignment' => Jc::BOTH]);
        }

        $this->tituloReporte($section, 'VI. DAÑOS MATERIALES:');

        foreach ($d['vehiculos'] as $i => $vehiculo) {
            $this->texto($section, $this->daniosVehiculo($vehiculo, $i), [], ['alignment' => Jc::BOTH]);
        }

        $this->tituloReporte($section, 'VII. OBSERVACIONES:');
        $this->texto($section, $this->observacionesGruas($d['vehiculos']), [], ['alignment' => Jc::BOTH]);

        $section->addPageBreak();
        $this->tituloReporte($section, 'VIII. CROQUIS DEL LUGAR:');
        $this->imagenEnMarco($section, $d['croquis'], 640, 760, 'Sin croquis registrado en el sistema.');

        foreach ($d['fotos'] as $foto) {
            $section->addPageBreak();
            $this->tituloReporte($section, strtoupper($foto['caption']) . ':');
            $this->imagenEnMarco($section, $foto['path'], 640, 760, 'Sin imagen disponible.');
        }
    }

    private function agregarIph(PhpWord $phpWord, array $d): void
    {
        $section = $this->seccion($phpWord, 'letter');
        $this->iphPortada($section, $d);

        $section = $this->seccion($phpWord, 'letter');
        $this->iphPrimerRespondiente($section, $d);

        $section = $this->seccion($phpWord, 'letter');
        $this->iphCroquisInspeccion($section, $d);

        $section = $this->seccion($phpWord, 'letter');
        $this->iphNarrativa($section, $d);

        foreach ($d['vehiculos'] as $i => $vehiculo) {
            $section = $this->seccion($phpWord, 'letter');
            $this->iphVehiculo($section, $d, $vehiculo, $i);
        }
    }

    private function agregarCadenaCustodia(PhpWord $phpWord, array $d): void
    {
        $section = $this->seccion($phpWord, 'letter');
        $this->cadenaRegistro($section, $d);

        $section = $this->seccion($phpWord, 'letter');
        $this->cadenaEntregaRecepcion($section, $d);
    }

    private function membreteParte($section, array $d): void
    {
        $logo = public_path('img/SSP_horizontal.png');

        if (is_file($logo)) {
            $this->addImageFit($section, $logo, 245, 115, Jc::LEFT);
        }

        $table = $section->addTable('HeaderInfo');
        $rows = [
            ['Dependencia', 'Secretaría de Seguridad Pública'],
            ['', 'Del Estado de Michoacán de Ocampo'],
            ['Sub-dependencia', ''],
            ['Oficina', $d['oficina']],
            ['No. de oficio', $d['oficio']],
            ['Expediente', $d['expediente']],
            ['Asunto:', ''],
        ];

        foreach ($rows as $row) {
            $table->addRow(310);
            $table->addCell(2600, ['bgColor' => self::GREY, 'valign' => 'center'])->addText($row[0], ['size' => 9], $this->p0());
            $table->addCell(8500, ['bgColor' => self::GREY, 'valign' => 'center'])->addText($row[1], ['bold' => $row[1] !== '', 'size' => 9], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }

        $section->addTextBreak(1);
    }

    private function iphPortada($section, array $d): void
    {
        $this->texto($section, $d['municipio_mayus'] . ' - ' . $d['municipio_mayus'], ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
        $top = $section->addTable('FormTable');
        $top->addRow(360);
        $top->addCell(4700)->addText('SISTEMA NACIONAL DE SEGURIDAD PÚBLICA', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $top->addCell(6400)->addText('NO. DE REFERENCIA', ['bold' => true, 'size' => 8], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $top->addRow(430);
        $top->addCell(4700)->addText('CNSP', ['size' => 8], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $top->addCell(6400)->addText($this->referenciaSistema($d), ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $top->addRow(330);
        $top->addCell(4700)->addText('', [], $this->p0());
        $top->addCell(6400)->addText('NO. DE FOLIO ASIGNADO POR EL SISTEMA', ['bold' => true, 'size' => 8], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $top->addRow(430);
        $top->addCell(4700)->addText('', [], $this->p0());
        $top->addCell(6400)->addText($d['folio'], ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $this->texto($section, 'INFORME POLICIAL HOMOLOGADO (IPH2019)', ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER, 'spaceBefore' => 120, 'spaceAfter' => 30]);
        $this->barra($section, 'HECHO PROBABLEMENTE DELICTIVO', self::CREAM, '000000');
        $this->barra($section, 'SECCIÓN 1. PUESTA A DISPOSICIÓN', 'FFFFFF', '000000', true);
        $this->barra($section, 'Apartado 1.1. Fecha y hora de la puesta a disposición.', self::CREAM, '000000', true);
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(620);
        $tabla->addCell(3200)->addText('Fecha: ' . $this->fechaCorta($d['fecha_puesta']), [], $this->p0());
        $tabla->addCell(2600)->addText('Hora: ' . substr($d['hora_puesta'], 0, 5), [], $this->p0());
        $tabla->addCell(5300)->addText('No. de expediente: ' . $d['expediente'], [], $this->p0());
        $this->barra($section, 'Apartado 1.2. Datos generales de la puesta a disposición.', self::CREAM, '000000', true);
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(600);
        $tabla->addCell(5550)->addText('Tipo de evento: ' . $d['tipo_hecho'], [], $this->p0());
        $tabla->addCell(5550)->addText('Folio C5i: ' . $d['folio'], [], $this->p0());
        $tabla->addRow(720);
        $tabla->addCell(11100, ['gridSpan' => 2])->addText('Lugar de intervención: ' . $d['lugar'], [], $this->p0());
        $this->barra($section, 'Apartado 1.3. Datos de quien pone a disposición.', self::CREAM, '000000', true);
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(620);
        $tabla->addCell(6000)->addText('Nombre: ' . $d['nombre_policia_mayus'], [], $this->p0());
        $tabla->addCell(5100)->addText('Adscripción: ' . $d['adscripcion'], [], $this->p0());
        $tabla->addRow(620);
        $tabla->addCell(6000)->addText('Cargo/grado: POLICÍA', [], $this->p0());
        $tabla->addCell(5100)->addText('Firma:', [], $this->p0());
        $this->barra($section, 'Apartado 1.4. Datos del lugar de intervención.', self::CREAM, '000000', true);
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(900);
        $tabla->addCell(11100)->addText($d['lugar'], [], ['alignment' => Jc::BOTH, 'spaceAfter' => 0]);
    }

    private function iphPrimerRespondiente($section, array $d): void
    {
        $this->barra($section, 'SECCIÓN 2. PRIMER RESPONDIENTE.', 'FFFFFF', '000000', true);
        $this->barra($section, 'Apartado 2.1. Datos de identificación', self::CREAM, '000000', true);
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(720);
        $tabla->addCell(3700)->addText('Primer apellido', ['size' => 8], $this->p0());
        $tabla->addCell(3700)->addText('Segundo apellido', ['size' => 8], $this->p0());
        $tabla->addCell(3700)->addText('Nombre(s): ' . $d['nombre_policia_mayus'], ['size' => 8], $this->p0());
        $tabla->addRow(720);
        $tabla->addCell(5550)->addText('Adscripción: ' . $d['adscripcion'], [], $this->p0());
        $tabla->addCell(5550, ['gridSpan' => 2])->addText('Cargo/grado: POLICÍA', [], $this->p0());
        $this->barra($section, 'SECCIÓN 3. CONOCIMIENTO DEL HECHO Y SEGUIMIENTO DE LA ACTUACIÓN DE LA AUTORIDAD', 'FFFFFF', '000000', true);
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(700);
        $tabla->addCell(11100)->addText('¿Cómo se enteró del hecho? Llamada de emergencia / C5i. Folio: ' . $d['folio'], [], $this->p0());
        $tabla->addRow(700);
        $tabla->addCell(11100)->addText('Fecha y hora del conocimiento: ' . $this->fechaCorta($d['fecha_hecho']) . ' ' . substr($d['hora_hecho'], 0, 5) . ' horas.', [], $this->p0());
        $tabla->addRow(700);
        $tabla->addCell(11100)->addText('Arribo al lugar: ' . ($d['arribo'] ? $d['arribo']->format('d-m-Y H:i') : 'Pendiente') . ' horas.', [], $this->p0());
        $this->barra($section, 'Apartado 4.1 Ubicación geográfica', self::CREAM, '000000', true);
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(2500);
        $tabla->addCell(11100)->addText(mb_strtoupper($d['lugar'], 'UTF-8'), ['bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
    }

    private function iphCroquisInspeccion($section, array $d): void
    {
        $this->barra($section, 'Croquis del lugar', self::CREAM, '000000', true);
        $this->imagenEnMarco($section, $d['croquis'], 590, 470, '');
        $this->barra($section, 'Apartado 4.2 Inspección del lugar', self::CREAM, '000000', true);
        $tabla = $section->addTable('FormTable');
        $rows = [
            ['¿Realizó la inspección del lugar?', 'Sí X', 'No'],
            ['Al momento de realizar la inspección del lugar, ¿encontró algún objeto relacionado con los hechos?', empty($d['objetos']) ? 'Sí' : 'Sí X', empty($d['objetos']) ? 'No X' : 'No'],
            ['¿Preservó el lugar de la intervención?', 'Sí', 'No X'],
            ['¿Llevó a cabo la priorización en el lugar de la intervención?', 'Sí', 'No X'],
        ];

        foreach ($rows as $row) {
            $tabla->addRow(420);
            $tabla->addCell(7700)->addText($row[0], [], $this->p0());
            $tabla->addCell(1700)->addText($row[1], [], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $tabla->addCell(1700)->addText($row[2], [], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }

        $tabla->addRow(600);
        $tabla->addCell(11100, ['gridSpan' => 3])->addText('Tipo de riesgo presentado: Sociales ___    Naturales ___    Especifique: ______________________________', [], $this->p0());
    }

    private function iphNarrativa($section, array $d): void
    {
        $this->texto($section, 'SECCIÓN 5. NARRATIVA DE LOS HECHOS', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
        $this->barra($section, 'Apartado 5.1 Descripción de los hechos y actuación de la autoridad', self::CREAM, '000000', true);
        $narrativa = 'Siendo aproximadamente las ' . substr($d['hora_hecho'], 0, 5) . ' horas del día ' . $d['fecha_texto'] . ', quien suscribe ' . $d['nombre_policia'] . ', adscrito a ' . $d['adscripcion'] . ', intervino en el hecho registrado como ' . $d['tipo_hecho'] . ', en ' . $d['lugar'] . ', relacionado con el folio ' . $d['folio'] . '. PENDIENTE DE COMPLEMENTAR: falta narrar de manera cronológica y detallada cómo se tuvo conocimiento del hecho, el traslado y arribo al lugar, las condiciones observadas, personas, vehículos u objetos localizados, acciones realizadas por la autoridad y forma de puesta a disposición.';
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(9000);
        $tabla->addCell(11100)->addText($narrativa, ['size' => 10], ['alignment' => Jc::BOTH, 'spaceAfter' => 0]);
    }

    private function iphVehiculo($section, array $d, array $vehiculo, int $i): void
    {
        $this->texto($section, 'INSPECCIÓN DE VEHÍCULO ' . ($i + 1), ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 30]);
        $this->texto($section, 'Llene este Anexo por cada vehículo inspeccionado', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER, 'spaceAfter' => 80]);
        $this->barra($section, 'Vehículo: ' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT), self::CREAM, '000000', true);
        $this->barra($section, 'Apartado C.1 Fecha y hora de la inspección', self::CREAM, '000000', true);
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(620);
        $tabla->addCell(5550)->addText('Fecha: ' . ($d['arribo'] ? $d['arribo']->format('d-m-Y') : $this->fechaCorta($d['fecha_hecho'])), [], $this->p0());
        $tabla->addCell(5550)->addText('Hora: ' . ($d['arribo'] ? $d['arribo']->format('H:i') : substr($d['hora_hecho'], 0, 5)), [], $this->p0());
        $this->barra($section, 'Apartado C.2 Datos generales del vehículo inspeccionado', self::CREAM, '000000', true);
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(620);
        $tabla->addCell(3700)->addText('Tipo: X Terrestre', [], $this->p0());
        $tabla->addCell(3700)->addText('Procedencia: X Nacional', [], $this->p0());
        $tabla->addCell(3700)->addText('Servicio: X Particular', [], $this->p0());
        $tabla->addRow(620);
        $tabla->addCell(3700)->addText('Marca: ' . mb_strtoupper($this->clean($vehiculo['marca'] ?? ''), 'UTF-8'), [], $this->p0());
        $tabla->addCell(3700)->addText('Submarca: ' . mb_strtoupper($this->clean($vehiculo['linea'] ?? ''), 'UTF-8'), [], $this->p0());
        $tabla->addCell(3700)->addText('Modelo: ' . mb_strtoupper($this->clean($vehiculo['modelo'] ?? ''), 'UTF-8'), [], $this->p0());
        $tabla->addRow(620);
        $tabla->addCell(3700)->addText('Color: ' . mb_strtoupper($this->clean($vehiculo['color'] ?? ''), 'UTF-8'), [], $this->p0());
        $tabla->addCell(3700)->addText('Placa/Matrícula: ' . mb_strtoupper($this->clean($vehiculo['placas'] ?? ''), 'UTF-8'), [], $this->p0());
        $tabla->addCell(3700)->addText('No. de serie: ' . mb_strtoupper($this->clean($vehiculo['serie'] ?? ''), 'UTF-8'), [], $this->p0());
        $tabla->addRow(700);
        $tabla->addCell(11100, ['gridSpan' => 3])->addText('Situación: ' . (!empty($vehiculo['antecedente_vehiculo']) ? 'Con reporte de robo X' : 'Sin reporte de robo X'), [], $this->p0());
        $tabla->addRow(1200);
        $tabla->addCell(11100, ['gridSpan' => 3])->addText($this->daniosInspeccion($vehiculo, $i), [], ['alignment' => Jc::BOTH, 'spaceAfter' => 0]);
        $tabla->addRow(1000);
        $tabla->addCell(11100, ['gridSpan' => 3])->addText($this->destinoVehiculo($vehiculo), [], ['alignment' => Jc::BOTH, 'spaceAfter' => 0]);
        $this->barra($section, 'Apartado C.3 Objetos encontrados en el vehículo inspeccionado', self::CREAM, '000000', true);
        $this->texto($section, '¿Encontró objetos relacionados con los hechos? Sí ___    No X', [], ['spaceAfter' => 80]);
        $this->barra($section, 'Apartado C.4 Datos del primer respondiente que realizó la inspección', self::CREAM, '000000', true);
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(900);
        $tabla->addCell(3700)->addText('Primer apellido', ['size' => 8], $this->p0());
        $tabla->addCell(3700)->addText('Segundo apellido', ['size' => 8], $this->p0());
        $tabla->addCell(3700)->addText('Nombre(s)', ['size' => 8], $this->p0());
        $tabla->addRow(700);
        $tabla->addCell(3700)->addText('Adscripción:', [], $this->p0());
        $tabla->addCell(3700)->addText('Cargo/grado:', [], $this->p0());
        $tabla->addCell(3700)->addText('Firma:', [], $this->p0());
    }

    private function cadenaRegistro($section, array $d): void
    {
        $this->texto($section, 'Registro de Cadena de Custodia', ['bold' => true, 'size' => 16], ['spaceAfter' => 40]);
        $this->texto($section, '+No. de referencia: ' . $d['folio'], ['bold' => true, 'size' => 10], ['alignment' => Jc::RIGHT, 'spaceAfter' => 80]);
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(420);
        foreach (['Institución o unidad administrativa', 'Folio o llamado', 'Lugar de intervención', 'Fecha y hora de intervención'] as $head) {
            $tabla->addCell(2775, ['bgColor' => self::GREEN])->addText($head, ['bold' => true, 'color' => 'FFFFFF', 'size' => 8], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }
        $tabla->addRow(1000);
        $tabla->addCell(2775)->addText($d['adscripcion'], ['bold' => true], $this->p0());
        $tabla->addCell(2775)->addText($d['folio'], ['bold' => true], $this->p0());
        $tabla->addCell(2775)->addText($d['ubicacion_cadena'], ['bold' => true, 'size' => 8], $this->p0());
        $tabla->addCell(2775)->addText($d['fecha_intervencion_cadena'] . "\n" . $d['hora_intervencion_cadena'] . ' HORAS', ['bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $this->texto($section, 'Inicio de la cadena de custodia.  Localización X    Descubrimiento ___    Aportación ___', ['bold' => true, 'size' => 11], ['spaceBefore' => 360, 'spaceAfter' => 120]);
        $this->texto($section, '1. Identidad. Número, letra o combinación alfanumérica asignada al indicio o elemento material probatorio.', ['bold' => true], ['spaceAfter' => 80]);
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(420);
        foreach ([2100 => 'Identificación', 4600 => 'Descripción', 3300 => 'Ubicación en el lugar', 1100 => 'Hora de recolección'] as $w => $head) {
            $tabla->addCell($w, ['bgColor' => self::GREEN])->addText($head, ['bold' => true, 'color' => 'FFFFFF', 'size' => 8], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }
        foreach ($d['vehiculos'] as $i => $vehiculo) {
            $tabla->addRow(1300);
            $tabla->addCell(2100)->addText('VEHÍCULO ' . $this->letraIndice($i), ['bold' => true], $this->p0());
            $tabla->addCell(4600)->addText($this->descripcionCadenaVehiculo($vehiculo, $i), ['bold' => true, 'size' => 8], $this->p0());
            $tabla->addCell(3300)->addText($d['ubicacion_cadena'], ['bold' => true, 'size' => 8], $this->p0());
            $tabla->addCell(1100)->addText($d['hora_recoleccion_cadena'] . "\nHORAS", ['bold' => true, 'size' => 8], $this->p0());
        }
        $this->texto($section, '5. Traslado. Vía: Terrestre X    Aérea ___    Marítima ___    Condiciones especiales para traslado: No X    Sí ___', ['bold' => true, 'size' => 9], ['spaceBefore' => 220, 'spaceAfter' => 120]);
        $this->texto($section, '6. Continuidad y trazabilidad.', ['bold' => true, 'size' => 11], ['spaceAfter' => 60]);
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(420);
        foreach ([2300 => 'Fecha y hora de entrega recepción', 5000 => 'Nombre, institución y cargo o identificación de quien entrega', 2500 => 'Actividad/propósito', 1300 => 'Firma'] as $w => $head) {
            $tabla->addCell($w, ['bgColor' => self::GREEN])->addText($head, ['bold' => true, 'color' => 'FFFFFF', 'size' => 8], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }
        $tabla->addRow(900);
        $tabla->addCell(2300)->addText($d['fecha_intervencion_cadena'] . "\n" . $d['hora_recoleccion_cadena'] . ' HORAS', ['bold' => true, 'size' => 8], $this->p0());
        $tabla->addCell(5000)->addText($d['nombre_policia_mayus'] . "\n" . $d['adscripcion'] . '/POLICÍA ESTATAL', ['bold' => true, 'size' => 8], $this->p0());
        $tabla->addCell(2500)->addText('ENTREGA', ['bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $tabla->addCell(1300)->addText('', [], $this->p0());
        $this->pieCadena($section, 'Registro de Cadena de Custodia');
    }

    private function cadenaEntregaRecepcion($section, array $d): void
    {
        $this->texto($section, 'ENTREGA-RECEPCIÓN DE LOS INDICIOS Y/O ELEMENTOS MATERIALES PROBATORIOS', ['size' => 8, 'underline' => 'single'], ['alignment' => Jc::RIGHT, 'spaceAfter' => 160]);
        $top = $section->addTable('Clean');
        $top->addRow();
        $top->addCell(6900)->addText('Entrega-recepción de Indicios o elementos materiales probatorios', ['bold' => true, 'size' => 15], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $top->addCell(4200)->addText('No. de referencia: ' . $d['folio'], ['bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(420);
        foreach ([2200 => 'Folio o llamado', 6500 => 'Lugar de la entrega-recepción', 2400 => 'Fecha y hora entrega/recepción'] as $w => $head) {
            $tabla->addCell($w, ['bgColor' => self::GREEN])->addText($head, ['bold' => true, 'color' => 'FFFFFF', 'size' => 8], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }
        $tabla->addRow(780);
        $tabla->addCell(2200)->addText($d['folio'], ['bold' => true], $this->p0());
        $tabla->addCell(6500)->addText($d['lugar_entrega'], ['bold' => true, 'size' => 13], $this->p0());
        $tabla->addCell(2400)->addText($d['fecha_intervencion_cadena'] . "\n" . $d['hora_recoleccion_cadena'] . ' HORAS', ['bold' => true, 'size' => 8], $this->p0());
        $this->texto($section, '1. Inventario. Escriba el número, letra o combinación alfanumérica con la que se identifica a cada indicio o elemento material probatorio que se entrega.', ['bold' => true, 'size' => 11], ['spaceBefore' => 220, 'spaceAfter' => 60]);
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(420);
        $tabla->addCell(2500, ['bgColor' => self::GREEN])->addText('Identificación', ['bold' => true, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $tabla->addCell(8600, ['bgColor' => self::GREEN])->addText('Descripción', ['bold' => true, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        foreach ($d['vehiculos'] as $i => $vehiculo) {
            $tabla->addRow(900);
            $tabla->addCell(2500)->addText('VEHÍCULO ' . $this->letraIndice($i), ['bold' => true], $this->p0());
            $tabla->addCell(8600)->addText($this->descripcionCadenaVehiculo($vehiculo, $i), ['bold' => true, 'size' => 9], $this->p0());
        }
        for ($i = 0; $i < max(6 - count($d['vehiculos']), 2); $i++) {
            $tabla->addRow(480);
            $tabla->addCell(2500)->addText('', [], $this->p0());
            $tabla->addCell(8600)->addText('', [], $this->p0());
        }
        $this->texto($section, '2. Embalaje. Señale las condiciones en las que se encuentran los embalajes.', ['bold' => true, 'size' => 11], ['spaceBefore' => 220, 'spaceAfter' => 60]);
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(1100);
        $tabla->addCell(11100)->addText('NO SE PUEDE POR SUS DIMENSIONES', ['bold' => true, 'size' => 13], $this->p0());
        $firmas = $section->addTable('FormTable');
        $firmas->addRow(330);
        $firmas->addCell(5350, ['bgColor' => self::GREEN])->addText('Persona que entrega', ['bold' => true, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $firmas->addCell(400, ['borderSize' => 0])->addText('', [], $this->p0());
        $firmas->addCell(5350, ['bgColor' => self::GREEN])->addText('Persona que recibe', ['bold' => true, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $firmas->addRow(1300);
        $firmas->addCell(5350)->addText($d['nombre_policia_mayus'] . "\n" . $d['adscripcion'] . "\nPOLICÍA ESTATAL", ['bold' => true, 'size' => 9], $this->p0());
        $firmas->addCell(400, ['borderSize' => 0])->addText('', [], $this->p0());
        $firmas->addCell(5350)->addText('', [], $this->p0());
        $firmas->addRow(300);
        $firmas->addCell(5350, ['bgColor' => self::GREEN])->addText('Nombre completo, institución, cargo y firma', ['bold' => true, 'color' => 'FFFFFF', 'size' => 8], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $firmas->addCell(400, ['borderSize' => 0])->addText('', [], $this->p0());
        $firmas->addCell(5350, ['bgColor' => self::GREEN])->addText('Nombre completo, institución, cargo y firma', ['bold' => true, 'color' => 'FFFFFF', 'size' => 8], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $this->pieCadena($section, 'Entrega-Recepción de indicios o elementos materiales probatorios');
    }

    private function referenciaSistema(array $d): string
    {
        return '16 PE 010 000 ' . str_replace('-', ' ', $this->fechaCorta($d['fecha_puesta'])) . ' HH MM';
    }

    private function seccion(PhpWord $phpWord, string $size)
    {
        $isLegal = $size === 'legal';

        return $phpWord->addSection([
            'pageSizeW' => $isLegal ? self::LEGAL_W : self::LETTER_W,
            'pageSizeH' => $isLegal ? self::LEGAL_H : self::LETTER_H,
            'marginTop' => self::MARGIN,
            'marginRight' => self::MARGIN,
            'marginBottom' => self::MARGIN,
            'marginLeft' => self::MARGIN,
        ]);
    }

    private function barra($section, string $text, string $bgColor, string $fontColor = '000000', bool $left = false): void
    {
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(320);
        $tabla->addCell(self::CONTENT_W, ['bgColor' => $bgColor])->addText($text, ['bold' => true, 'color' => $fontColor, 'size' => 9], ['alignment' => $left ? Jc::LEFT : Jc::CENTER, 'spaceAfter' => 0]);
    }

    private function tituloReporte($section, string $text): void
    {
        $this->texto($section, $text, ['bold' => true, 'size' => 11], ['spaceBefore' => 180, 'spaceAfter' => 50]);
    }

    private function texto($container, string $text, array $font = [], array $paragraph = []): void
    {
        $container->addText($this->plain($text), array_merge(['size' => 10], $font), array_merge(['spaceAfter' => 80], $paragraph));
    }

    private function p0(): array
    {
        return ['spaceAfter' => 0, 'spaceBefore' => 0];
    }

    private function imagenEnMarco($section, ?string $path, int $maxW, int $maxH, string $empty): void
    {
        $tabla = $section->addTable('FormTable');
        $tabla->addRow(Converter::pointToTwip($maxH + 20));
        $cell = $tabla->addCell(self::CONTENT_W, ['valign' => 'center']);

        if ($path && is_file($path)) {
            $this->addImageFit($cell, $path, $maxW, $maxH, Jc::CENTER);
            return;
        }

        if ($empty !== '') {
            $cell->addText($empty, ['italic' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }
    }

    private function addImageFit($container, string $path, int $maxW, int $maxH, string $alignment = Jc::CENTER): bool
    {
        if (!is_file($path)) {
            return false;
        }

        $size = @getimagesize($path);
        $width = $maxW;
        $height = null;

        if ($size && $size[0] > 0 && $size[1] > 0) {
            $ratio = min($maxW / $size[0], $maxH / $size[1], 1);
            $width = max(1, (int) floor($size[0] * $ratio));
            $height = max(1, (int) floor($size[1] * $ratio));
        }

        $style = ['width' => $width, 'alignment' => $alignment];

        if ($height) {
            $style['height'] = $height;
        }

        $container->addImage($path, $style);

        return true;
    }

    private function pieCadena($section, string $left): void
    {
        $section->addTextBreak(1);
        $table = $section->addTable('Clean');
        $table->addRow();
        $table->addCell(6500)->addText($left, ['size' => 8], $this->p0());
        $table->addCell(4600)->addText('Página ___ de ___', ['bold' => true, 'size' => 8], ['alignment' => Jc::RIGHT, 'spaceAfter' => 0]);
    }

    private function resolverImagen($valor): ?string
    {
        if (is_array($valor)) {
            $valor = reset($valor);
        }

        $src = trim((string) $valor);

        if ($src === '') {
            return null;
        }

        if (preg_match('/^data:image\/([a-z0-9+.-]+);base64,(.+)$/i', $src, $matches)) {
            $ext = str_contains($matches[1], 'jpeg') ? 'jpg' : (str_contains($matches[1], 'png') ? 'png' : 'img');
            $path = storage_path('app/temp/' . uniqid('iph_img_', true) . '.' . $ext);
            File::ensureDirectoryExists(dirname($path));
            file_put_contents($path, base64_decode($matches[2]));
            $this->tempFiles[] = $path;

            return $path;
        }

        if (is_file($src)) {
            return $src;
        }

        $path = parse_url($src, PHP_URL_PATH) ?: $src;
        $path = urldecode(str_replace('\\', '/', ltrim($path, '/')));
        $candidatos = [public_path($path), public_path('storage/' . $path)];

        foreach (['storage/', 'img/'] as $marca) {
            $pos = strpos($path, $marca);

            if ($pos !== false) {
                $candidatos[] = public_path(substr($path, $pos));
            }
        }

        foreach ($candidatos as $candidato) {
            if (is_file($candidato)) {
                return $candidato;
            }
        }

        return null;
    }

    private function limpiarTemporales(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->tempFiles = [];
    }

    private function carbon(string $fecha, string $hora): ?Carbon
    {
        if ($fecha === '' || $hora === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d H:i', $fecha . ' ' . substr($hora, 0, 5), 'America/Mexico_City');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function fechaTexto(?string $fecha): string
    {
        if (!$fecha) {
            return 'fecha no especificada';
        }

        try {
            return Carbon::parse($fecha, 'America/Mexico_City')->translatedFormat('d \\d\\e F \\d\\e Y');
        } catch (\Throwable $e) {
            return (string) $fecha;
        }
    }

    private function fechaCorta(?string $fecha): string
    {
        if (!$fecha) {
            return '';
        }

        try {
            return Carbon::parse($fecha, 'America/Mexico_City')->format('d-m-Y');
        } catch (\Throwable $e) {
            return (string) $fecha;
        }
    }

    private function descripcionVehiculo(array $vehiculo, int $i): string
    {
        return 'VEHÍCULO (' . $this->letraIndice($i) . ').- ' . trim(collect([
            $this->clean($vehiculo['marca'] ?? null) ? 'Marca ' . $this->clean($vehiculo['marca'] ?? null) : null,
            $this->clean($vehiculo['modelo'] ?? null) ? 'Modelo ' . $this->clean($vehiculo['modelo'] ?? null) : null,
            $this->clean($vehiculo['tipo'] ?? null) ? 'Tipo ' . $this->clean($vehiculo['tipo'] ?? null) : null,
            $this->clean($vehiculo['linea'] ?? null) ? 'Línea ' . $this->clean($vehiculo['linea'] ?? null) : null,
            $this->clean($vehiculo['color'] ?? null) ? 'Color ' . $this->clean($vehiculo['color'] ?? null) : null,
            $this->clean($vehiculo['placas'] ?? null) ? 'Placas ' . $this->clean($vehiculo['placas'] ?? null) : null,
            $this->clean($vehiculo['serie'] ?? null) ? 'Serie ' . $this->clean($vehiculo['serie'] ?? null) : null,
        ])->filter()->implode(', ')) . '.';
    }

    private function daniosVehiculo(array $vehiculo, int $i): string
    {
        $partes = $this->clean($vehiculo['partes_danadas'] ?? null);
        $monto = $vehiculo['monto_danos'] ?? null;
        $texto = 'VEHÍCULO (' . $this->letraIndice($i) . ').- ' . ($partes ? 'Presenta daños en ' . mb_strtolower($partes, 'UTF-8') : 'No se cuenta con partes dañadas registradas');

        if (is_numeric($monto) && (float) $monto > 0) {
            $texto .= ', con valor aproximado de $' . number_format((float) $monto, 2);
        }

        return $texto . '.';
    }

    private function daniosInspeccion(array $vehiculo, int $i): string
    {
        return 'DAÑOS DEL VEHÍCULO (' . $this->title($this->numeroALetras($i + 1)) . '): A simple vista ' . mb_strtolower($this->daniosVehiculo($vehiculo, $i), 'UTF-8');
    }

    private function destinoVehiculo(array $vehiculo): string
    {
        $nombre = $this->valorGrua($vehiculo['grua_nombre'] ?? null) ?: $this->valorGrua($vehiculo['grua'] ?? null);
        $destino = $this->valorGrua($vehiculo['grua_direccion'] ?? null)
            ?: $this->valorGrua($vehiculo['grua_ubicacion_corralon'] ?? null)
            ?: $this->valorGrua($vehiculo['corralon'] ?? null);

        if (!$nombre && !$destino) {
            return 'Destino que se le dio: pendiente de precisar en el presente anexo.';
        }

        return 'Destino que se le dio: El vehículo fue remitido' . ($nombre ? ' al corralón de Grúas ' . $nombre : '') . ($destino ? ' ubicado en ' . $destino : '') . '.';
    }

    private function descripcionCadenaVehiculo(array $vehiculo, int $i): string
    {
        return 'VEHÍCULO ' . $this->letraIndice($i) . ' EL CUAL ES DE LA ' . mb_strtoupper(trim(collect([
            $this->clean($vehiculo['marca'] ?? null) ? 'Marca ' . $this->clean($vehiculo['marca'] ?? null) : null,
            $this->clean($vehiculo['tipo'] ?? null) ? 'Tipo ' . $this->clean($vehiculo['tipo'] ?? null) : null,
            $this->clean($vehiculo['linea'] ?? null) ? 'Línea ' . $this->clean($vehiculo['linea'] ?? null) : null,
            $this->clean($vehiculo['modelo'] ?? null) ? 'Modelo ' . $this->clean($vehiculo['modelo'] ?? null) : null,
            $this->clean($vehiculo['color'] ?? null) ? 'Color ' . $this->clean($vehiculo['color'] ?? null) : null,
            $this->clean($vehiculo['placas'] ?? null) ? 'con número de placas de circulación ' . $this->clean($vehiculo['placas'] ?? null) : null,
            $this->clean($vehiculo['serie'] ?? null) ? 'con número de serie ' . $this->clean($vehiculo['serie'] ?? null) : null,
        ])->filter()->implode(', ')), 'UTF-8') . '.';
    }

    private function observacionesGruas(array $vehiculos): string
    {
        $gruas = collect($vehiculos)->map(fn ($vehiculo) => $this->valorGrua($vehiculo['grua_nombre'] ?? null) ?: $this->valorGrua($vehiculo['grua'] ?? null))->filter()->unique()->implode(' y ');

        if ($gruas === '') {
            return 'Los vehículos no cuentan con registro de traslado o resguardo por grúa en el sistema.';
        }

        return 'Los vehículos fueron resguardados con apoyo de ' . $gruas . '.';
    }

    private function valorGrua($valor): ?string
    {
        $texto = $this->clean($valor);

        if (!$texto) {
            return null;
        }

        return in_array(mb_strtoupper($texto, 'UTF-8'), ['0', 'NO', 'SIN GRUA', 'SIN GRÚA', 'SIN CORRALON', 'SIN CORRALÓN'], true) ? null : $texto;
    }

    private function letraIndice(int $i): string
    {
        return chr(65 + ($i % 26));
    }

    private function numeroALetras(int $n): string
    {
        $map = [1 => 'UNO', 2 => 'DOS', 3 => 'TRES', 4 => 'CUATRO', 5 => 'CINCO', 6 => 'SEIS', 7 => 'SIETE', 8 => 'OCHO', 9 => 'NUEVE', 10 => 'DIEZ'];

        return $map[$n] ?? (string) $n;
    }

    private function valor($valor, string $default = 'No especificado'): string
    {
        $texto = trim((string) $valor);

        return $texto === '' ? $default : $texto;
    }

    private function clean($valor): string
    {
        $texto = trim((string) $valor);
        $normalizado = mb_strtoupper($texto, 'UTF-8');

        return in_array($normalizado, ['', '-', 'N/A', 'NA', 'NO APLICA', 'NO ESPECIFICADO'], true) ? '' : $texto;
    }

    private function plain(string $text): string
    {
        return html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function title($value): string
    {
        return mb_convert_case(mb_strtolower(trim((string) $value), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
}
