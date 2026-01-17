<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Hechos;
use App\Models\Conductor;
use App\Models\Vehiculo;
use App\Models\VehiculoConductor;
use App\Models\HechoVehiculo;
use Carbon\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table;


class EstadisticasController extends Controller
{
    public function index()
    {
        return view('admin.settings.estadisticas.index');
    }

    public function parteNovedades(Request $request)
    {
        $fecha = $request->input('fecha') ?? now()->format('Y-m-d');

        $inicio = Carbon::parse($fecha)->setTime(18, 0)->subDay();
        $fin    = Carbon::parse($fecha)->setTime(18, 0);

        $hechos = Hechos::whereBetween('created_at', [$inicio, $fin])->get();

        return view('admin.settings.estadisticas.parte-novedades', compact('hechos', 'fecha'));
    }

    public function descargarParte(Request $request)
    {
        $fecha  = $request->input('fecha') ?? now()->format('Y-m-d');
        $inicio = Carbon::parse($fecha)->setTime(18, 0)->subDay();
        $fin    = Carbon::parse($fecha)->setTime(18, 0);

        $hechos = Hechos::with(['vehiculos.conductores', 'lesionados'])
            ->whereBetween('created_at', [$inicio, $fin])
            ->get();

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection([
            'pageSizeW'   => 12175,
            'pageSizeH'   => 17860,
            'marginTop'   => 1134,
            'marginRight' => 1134,
            'marginBottom'=> 1134,
            'marginLeft'  => 1134,
        ]);

        // === Encabezado con imágenes ===
        $phpWord->addTableStyle('EncabezadoTabla', [
            'borderSize' => 0,
            'borderColor'=> 'FFFFFF',
            'cellMargin' => 0,
            'alignment'  => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);
        $table = $section->addTable('EncabezadoTabla');

        $table->addRow();
        $table->addCell(5000, ['valign' => 'center'])->addImage(public_path('ssp.jpg'), [
            'width'     => 140,
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
        ]);
        $table->addCell(5000, ['valign' => 'center'])->addImage(public_path('vialidad.png'), [
            'width'     => 70,
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT
        ]);

        $table->addRow();
        $table->addCell(5000)->addText('PARTE DE NOVEDADES', ['bold' => true]);
        $table->addCell(5000)->addText('UNIDAD DE ATENCIÓN A SINIESTROS', ['bold' => true], [
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT
        ]);

        // === Fecha a la derecha ===
        $section->addTextBreak(1);
        $fechaFormatoOficio = 'Morelia Michoacán, ' . Carbon::parse($fecha)->format('d') . ' de ' .
            ucfirst(Carbon::parse($fecha)->translatedFormat('F')) . ' de ' . Carbon::parse($fecha)->format('Y') . '.';
        $section->addText($fechaFormatoOficio, [], [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
        ]);

        // === Destinatario ===
        $destinatario = [
            'LIC. ADOLFO MILLAN MONTES',
            'COORDINADOR DE AGRUPAMIENTOS',
            'DE SEGURIDAD VIAL',
            'P R E S E N T E'
        ];
        foreach ($destinatario as $linea) {
            $section->addText($linea, ['bold' => true], [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
            ]);
        }

        $section->addTextBreak(1);

        // === Párrafo explicativo ===
        $diaInicio = Carbon::parse($fecha)->subDay()->format('d');
        $diaFin    = Carbon::parse($fecha)->format('d');
        $anio      = Carbon::parse($fecha)->format('Y');

        $textoNovedades = "Hago de su superior conocimiento, lo relacionado a las novedades ocurridas durante el Servicio de las 18:00 horas del día {$diaInicio}, a las 18:00 horas del día {$diaFin} de {$anio}, por parte de la Unidad de Atención a Siniestros.";
        $section->addText($textoNovedades, [], [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
        ]);

        // Línea de puntos y títulos
        $section->addText(str_repeat('.', 148), [], [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
            'spaceAfter'  => 0,
            'spaceBefore' => 0
        ]);
        $section->addTextBreak(1);

        $section->addText('HECHOS RELEVANTES', ['bold' => true], [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
        ]);

        $section->addTextBreak(1);
        $section->addText(str_repeat('.', 148), [], [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
            'spaceAfter'  => 0,
            'spaceBefore' => 0
        ]);
        $section->addTextBreak(1);

        $section->addText('HECHOS DE TRÁNSITO', ['bold' => true], [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
        ]);
        $section->addTextBreak(1);

        // === Lista de hechos ===
        $contador = 1;
        foreach ($hechos as $hecho) {

            // Texto principal del hecho
            $textRun = $section->addTextRun([
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
            ]);

            $lesionadosTexto = $hecho->lesionados->count() > 0 ? 'CON LESIONADOS' : 'SIN LESIONADOS';

            // Encabezado
            $textRun->addText("{$contador}.-" . strtoupper($hecho->tipo_hecho) . " ({$lesionadosTexto}) SECTOR " . strtoupper($hecho->sector) . ".- ", ['bold' => true]);

            // Hora y lugar
            $textRun->addText("A las " . Carbon::parse($hecho->hora)->format('H:i') . " horas en {$hecho->calle}, de la colonia {$hecho->colonia}, lugar donde ");

            // Vehículos
            $vehiculos = $hecho->vehiculos;
            if ($vehiculos->count() > 0) {
                $textRun->addText("participaron: ");
                $letra = 'A';
                foreach ($vehiculos as $vehiculo) {
                    $textRun->addText("AUTOMÓVIL ({$letra}) ", ['bold' => true]);

                    $partes = [];
                    if ($vehiculo->marca)  $partes[] = "Marca {$vehiculo->marca}";
                    if ($vehiculo->modelo) $partes[] = "Modelo {$vehiculo->modelo}";
                    if ($vehiculo->tipo)   $partes[] = "Tipo {$vehiculo->tipo}";
                    if ($vehiculo->linea)  $partes[] = "Línea {$vehiculo->linea}";
                    if ($vehiculo->color)  $partes[] = "Color {$vehiculo->color}";
                    if ($partes) {
                        $textRun->addText(implode(', ', $partes) . ", ");
                    }

                    if ($vehiculo->placas) {
                        $textRun->addText("Placas ");
                        $textRun->addText($vehiculo->placas, ['bold' => true]);
                        $textRun->addText(" del servicio {$vehiculo->tipo_servicio}, ");
                    }

                    if ($vehiculo->serie) {
                        $textRun->addText("Serie ");
                        $textRun->addText($vehiculo->serie, ['bold' => true]);
                        $textRun->addText(", ");
                    }

                    if ($vehiculo->tarjeta_circulacion_nombre) {
                        $textRun->addText("tarjeta de circulación a nombre de {$vehiculo->tarjeta_circulacion_nombre}, ");
                    }

                    $conductor = $vehiculo->conductores->first();
                    if ($conductor) {
                        $textRun->addText("conducido por el C. ");
                        $textRun->addText($conductor->nombre, ['bold' => true]);
                        if ($conductor->edad)      $textRun->addText(" de {$conductor->edad} años de edad");
                        if ($conductor->domicilio) $textRun->addText(", con domicilio en {$conductor->domicilio}");
                        if ($conductor->estado_licencia) {
                            $textRun->addText(", presentó licencia tipo {$conductor->tipo_licencia}");
                        } else {
                            $textRun->addText(", no presentó licencia");
                        }
                        $textRun->addText("; ");
                    }

                    $letra++;
                }
            } else {
                $textRun->addText("no se encontró información de vehículos. ");
            }

            // Lesionados
            $lesionados = $hecho->lesionados;
            if ($lesionados->count() > 0) {
                foreach ($lesionados as $index => $l) {
                    $linea = "Lesionado " . ($index + 1) . ": ";
                    if ($l->nombre)      $linea .= "C. {$l->nombre}";
                    if ($l->edad)        $linea .= ", de {$l->edad} años";
                    if ($l->sexo)        $linea .= ", sexo {$l->sexo}";
                    if ($l->tipo_lesion) $linea .= ", presenta lesión tipo {$l->tipo_lesion}";
                    if ($l->hospitalizado) {
                        $linea .= ", fue hospitalizado";
                        if ($l->hospital) $linea .= " en {$l->hospital}";
                    } else {
                        $linea .= ", no fue hospitalizado";
                    }
                    if ($l->atencion_en_sitio) $linea .= ", recibió atención en el sitio";
                    if ($l->ambulancia)       $linea .= ", trasladado por la unidad {$l->ambulancia}";
                    if ($l->paramedico)       $linea .= ", atendido por el paramédico {$l->paramedico}";
                    if ($l->observaciones)    $linea .= ", observaciones: {$l->observaciones}";
                    $linea .= ".";

                    $section->addText($linea, [], [
                        'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                        'spaceAfter'  => 0,
                        'spaceBefore' => 0,
                    ]);
                }
            } else {
                $section->addText("SIN LESIONADOS.", [], [
                    'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                    'spaceAfter'  => 0,
                    'spaceBefore' => 0,
                ]);
            }

            // Perito + ID
            $section->addText("Intervino el perito {$hecho->perito}.", [], [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
            ]);
            $section->addText("ID DE REGISTRO {$hecho->id}", [], [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
            ]);

            // Situación + Daños
            $montoTotal = $hecho->vehiculos->sum('monto_danos');
            $section->addText(
                strtoupper($hecho->situacion) . "\tDAÑOS APROXIMADOS $ " . number_format($montoTotal, 2),
                [],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH, 'spaceAfter' => 0, 'spaceBefore' => 0]
            );

            // CAUSAS + Ocupaciones
            $lineaCausas = "CAUSAS: {$hecho->causas}";
            $ocupaciones = collect($vehiculos)->flatMap(function ($v) {
                return $v->conductores->pluck('ocupacion')->filter();
            })->unique()->implode(' – ');
            if ($ocupaciones) {
                $lineaCausas .= " ({$ocupaciones})";
            }
            $section->addText($lineaCausas, [], [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
            ]);

            // Grúa
            $usoGrua = $vehiculos->contains(function ($v) {
                return strtolower($v->grua) !== 'n/a' && $v->grua !== null;
            });
            $section->addText(
                $usoGrua ? "Se utilizó grúa." : "No se utilizó grúa.",
                [],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH, 'spaceAfter' => 0, 'spaceBefore' => 0]
            );

            // Antecedentes
            if ($hecho->checaron_antecedentes) {
                $section->addText(
                    "Se checaron antecedentes de conductores y vehículos, sin novedad.",
                    [],
                    ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH, 'spaceAfter' => 0, 'spaceBefore' => 0]
                );
            }

            // Línea punteada final
            $section->addText(str_repeat('.', 148), [], [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
            ]);

            $contador++;
        }

        $section->addTextBreak(1);

        $section->addText('A T E N T A M E N T E', ['bold' => true], [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
        ]);

        $section->addTextBreak(1);

        // Estilo personalizado sin bordes
        $tableStyleName = 'FirmasSinBordes';
        $phpWord->addTableStyle($tableStyleName, [
            'borderSize' => 0,
            'borderColor' => 'ffffff',
            'cellMargin' => 50,
            'alignment'  => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);

        $tableFirmas = $section->addTable($tableStyleName);

        // Atributos de celda sin bordes
        $cellStyle = [
            'borderSize' => 0,
            'borderColor' => 'ffffff',
            'valign' => 'center',
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
        ];

        $section->addTextBreak(1);
        $section->addTextBreak(1);
        $section->addTextBreak(1);

        // Fila 1: cargos
        $tableFirmas->addRow();
        $tableFirmas->addCell(5000, $cellStyle)->addText(
            'SUBDIRECTOR DE LA UNIDAD DE ATENCIÓN A SINIESTROS.',
            ['bold' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
        $tableFirmas->addCell(5000, $cellStyle)->addText(
            'COMANDANTE DE TURNO “B”',
            ['bold' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        $section->addTextBreak(1);
        $section->addTextBreak(1);
        $section->addTextBreak(1);

        // Fila 2: nombres
        $tableFirmas->addRow();

        // Celda izquierda
        $tableFirmas->addCell(5000, $cellStyle)->addText(
            'LIC. LUIS ALBERTO NÚÑEZ RAZO.',
            ['bold' => true],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceBefore' => 200,
                'spaceAfter'  => 100,
            ]
        );

        // Celda derecha
        $cell = $tableFirmas->addCell(5000, $cellStyle);
        $textRun = $cell->addTextRun([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 200,
            'spaceAfter'  => 100,
        ]);

        $textRun->addText('OFICIAL', ['bold' => true]);
        $textRun->addTextBreak();
        $textRun->addText('LIC. JULIO ERNESTO BAUTISTA JIMENEZ.', ['bold' => true]);
        // Guardar y descargar

        $filename = "parte_novedades_{$fecha}.docx";
        $tempPath = storage_path("app/public/{$filename}");
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }

    public function miniParte(Request $request)
    {
        $fecha = $request->input('fecha') ?? now()->format('Y-m-d');

        $inicio = Carbon::parse($fecha)->setTime(18, 0)->subDay();
        $fin    = Carbon::parse($fecha)->setTime(18, 0);

        $hechos = Hechos::whereBetween('created_at', [$inicio, $fin])->get();

        return view('admin.settings.estadisticas.mini-parte', compact('hechos', 'fecha'));
    }

    public function descargarMiniParte(Request $request)
    {
        $fecha  = $request->input('fecha') ?? now()->format('Y-m-d');
        $inicio = Carbon::parse($fecha)->setTime(18, 0)->subDay();
        $fin    = Carbon::parse($fecha)->setTime(18, 0);
        $hechos = Hechos::with(['vehiculos.conductores', 'lesionados'])
            ->whereBetween('created_at', [$inicio, $fin])
            ->get();

        $resumen = [
            'CHOQUES'                    => 0,
            'ATROPELLOS'                 => 0,
            'VOLCADURAS'                 => 0,
            'SALIDA DE SUP. DE ROD.'     => 0,
            'SUBIDA AL CAMELLÓN'         => 0,
            'CAIDA A LA CUNETA'          => 0,
            'CAIDA DE MOTO'              => 0,
            'CAIDA A ZANJA'              => 0,
            'CAIDA A CPO. DE AGUA'       => 0,
            'INCIDENTE DE TTO.'          => 0,

            'LESIONADOS'                 => 0,
            'DEFUNCIONES'                => 0,
            'PENDIENTES'                 => 0,
            'RESUELTOS'                  => 0,

            'ANTECEDENTES_VEH'           => 0,
            'ANTECEDENTES_PERS'          => 0,
            'ANTECEDENTES_MOTOS'         => 0,
            'VEH_RECUPERADOS'            => 0,
            'PERS_MP_FC'                 => 0,
            'PERS_BARANDILLAS'           => 0,
            'SERV_GRUAS'                 => 0,
            'AUTOS_CORRALON'             => 0,
            'MOTOS_CORRALON'             => 0,
            'DANIOS_VIAS_COM'            => 0,
            'ARMAS_ASEGURADAS'           => 0,
            'DROGA_ASEGURADA'            => 0,
            'VICTIMAS_TOTALES'           => 0,
            'EXAMENES_MANEJO'            => 0,
            'REPORTES'                   => 0,
            'TURNO_MP'                   => 0,
            'DISPOSITIVOS'               => 0,
            'VEH_OFICIALES'              => 0,
            'VEH_INVOL_HT'               => 0,
        ];

        $resumen['LESIONADOS'] = $hechos->sum(fn($h) => $h->lesionados->count());

        foreach ($hechos as $h) {
            switch ($h->tipo_hecho) {
                case 'COLISIÓN POR ALCANCE':
                case 'COLISIÓN POR CAMBIO DE CARRIL':
                case 'COLISIÓN POR INVASIÓN DE CARRIL':
                case 'COLISIÓN POR CORTE DE CIRCULACIÓN':
                case 'COLISIÓN CONTRA OBJETO FIJO':
                case 'COLISIÓN POR MANIOBRA DE REVERSA':
                case 'COLISIÓN POR NO RESPETAR SEMÁFORO':
                    $resumen['CHOQUES']++;
                    break;
                case 'COLISIÓN CON PEATÓN':
                    $resumen['ATROPELLOS']++;
                    break;
                case 'VOLCADURA':
                    $resumen['VOLCADURAS']++;
                    break;
                case 'SALIDA DE SUPERFICIE DE RODAMIENTO':
                    $resumen['SALIDA DE SUP. DE ROD.']++;
                    break;
                case 'SUBIDA AL CAMELLÓN':
                    $resumen['SUBIDA AL CAMELLÓN']++;
                    break;
                case 'CAIDA DE MOTOCICLETA':
                    $resumen['CAIDA DE MOTO']++;
                    break;
                case 'CAIDA ACUATICA DE VEHÍCULO':
                    $resumen['CAIDA A CPO. DE AGUA']++;
                    break;
                case 'DESBARRANCAMIENTO':
                    $resumen['CAIDA A ZANJA']++;
                    break;
                case 'INCENDIO':
                case 'EXPLOSIÓN':
                case 'Otro':
                    $resumen['INCIDENTE DE TTO.']++;
                    break;
            }

            $sit = strtoupper($h->situacion);
            if ($sit === 'PENDIENTE') $resumen['PENDIENTES']++;
            if ($sit === 'RESUELTO')  $resumen['RESUELTOS']++;

            foreach ($h->vehiculos as $v) {
                if (!empty($v->antecedente_vehiculo) && (int)$v->antecedente_vehiculo === 1) {
                    $resumen['ANTECEDENTES_VEH']++;
                }

                foreach ($v->conductores as $c) {
                    if (!empty($c->antecedentes) && (int)$c->antecedentes === 1) {
                        $resumen['ANTECEDENTES_PERS']++;
                    }
                }
            }
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'pageSizeW'   => \PhpOffice\PhpWord\Shared\Converter::inchToTwip(8.5),
            'pageSizeH'   => \PhpOffice\PhpWord\Shared\Converter::inchToTwip(11),
            'marginTop'   => 234,
            'marginRight' => 1134,
            'marginBottom'=> 1134,
            'marginLeft'  => 1134,
        ]);

        $phpWord->addTableStyle('EncabezadoTabla', [
            'borderSize'  => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 0,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);
        $enc = $section->addTable('EncabezadoTabla');
        $enc->addRow();
        $enc->addCell(5000, ['valign'=>'center'])
            ->addImage(public_path('ssp.jpg'), ['width'=>120,'alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
        $enc->addCell(5000, ['valign'=>'center'])
            ->addImage(public_path('vialidad.png'), ['width'=>50, 'alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);

        $phpWord->addTableStyle('TablaTituloFecha', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 50,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);
        $fechaFmt = strtoupper(Carbon::parse($fecha)->translatedFormat('d \d\e F \d\e Y'));
        $titu = $section->addTable('TablaTituloFecha');
        $titu->addRow(null, ['exactHeight'=>true,'height'=>300]);
        $titu->addCell(null,['valign'=>'center'])
             ->addText('CONCENTRADO NOVEDADES DEL DÍA',['bold'=>true],['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER,'spaceBefore'=>0,'spaceAfter'=>0]);
        $titu->addCell(null,['valign'=>'center'])
             ->addText($fechaFmt,['bold'=>true],['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER,'spaceBefore'=>0,'spaceAfter'=>0]);

        $section->addText(' ', [], ['spaceBefore'=>0,'spaceAfter'=>0,'lineHeight'=>0.5]);

        $section->addText('HECHOS OCURRIDOS EN DIFERENTES PARTES DE LA CIUDAD.', ['bold'=>true], [
            'alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore'=>0, 'spaceAfter'=>0
        ]);
        $section->addText(' ', [], ['spaceBefore'=>0,'spaceAfter'=>0,'lineHeight'=>0.5]);

        $phpWord->addTableStyle('TablaResumenMiniParte', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 40,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);
        $tabla = $section->addTable('TablaResumenMiniParte');

        $fmt = function ($n) { return str_pad((int)$n, 2, '0', STR_PAD_LEFT); };

        $datos = [
            [$fmt($resumen['CHOQUES']),               'CHOQUES',                      '', $fmt($resumen['LESIONADOS']),          'LESIONADOS'],
            [$fmt($resumen['ATROPELLOS']),            'ATROPELLOS',                   '', $fmt($resumen['DEFUNCIONES']),         'DEFUNCIONES'],
            [$fmt($resumen['VOLCADURAS']),            'VOLCADURAS',                   '', $fmt($resumen['PENDIENTES']),          'PENDIENTES'],
            [$fmt($resumen['SALIDA DE SUP. DE ROD.']),'SALIDA DE SUP. DE ROD.',       '', $fmt($resumen['RESUELTOS']),           'RESUELTOS'],
            [$fmt($resumen['SUBIDA AL CAMELLÓN']),    'SUBIDA AL CAMELLÓN',           '', $fmt($resumen['VEH_RECUPERADOS']),     'VEHICULOS RECUPERADOS'],
            [$fmt($resumen['CAIDA A LA CUNETA']),     'CAIDA A LA CUNETA',            '', $fmt($resumen['PERS_MP_FC']),          'PERS. PRESENTADAS AL MP FC'],
            [$fmt($resumen['CAIDA DE MOTO']),         'CAIDA DE MOTO',                '', $fmt($resumen['PERS_BARANDILLAS']),    'PERS. PRESENTADAS A BARANDILLAS'],
            [$fmt($resumen['CAIDA A ZANJA']),         'CAIDA A ZANJA',                '', $fmt($resumen['SERV_GRUAS']),          'SERVICIOS DE GRÚAS'],
            [$fmt($resumen['CAIDA A CPO. DE AGUA']),  'CAIDA A CPO. DE AGUA',         '', $fmt($resumen['AUTOS_CORRALON']),      'AUTOMOVILES REMITIDOS A CORRALON'],
            [$fmt($resumen['INCIDENTE DE TTO.']),     'INCIDENTE DE TTO.',            '', $fmt($resumen['MOTOS_CORRALON']),      'MOTOCICLETAS REMITIDAS A CORRALON'],
            [$fmt($resumen['REPORTES']),              'REPORTE',                      '', $fmt($resumen['ANTECEDENTES_VEH']),    'ANTECEDENTES VEHICULOS'],
            [$fmt($resumen['PERSONAS_MP'] ?? 0),      'PERSONAS AL M.P.',             '', $fmt($resumen['ANTECEDENTES_MOTOS']),  'ANTECEDENTES MOTOCICLETAS'],
            [$fmt($resumen['TURNO_MP']),              'TURNADOS AL M.P.',             '', $fmt($resumen['ANTECEDENTES_PERS']),   'ANTECEDENTES A PERSONAS'],
            [$fmt($resumen['DISPOSITIVOS']),          'DISPOSITIVOS REALIZADOS',      '', $fmt($resumen['DANIOS_VIAS_COM']),     'DAÑOS EN VIAS DE COMUNICACIÓN'],
            [$fmt($resumen['VEH_OFICIALES']),         'VEHICULOS OFICIALES',          '', $fmt($resumen['ARMAS_ASEGURADAS']),    'ARMAS ASEGURADAS'],
            [$fmt($resumen['VEH_INVOL_HT']),          'VEHICULOS INVOLUCRADO HT',     '', $fmt($resumen['DROGA_ASEGURADA']),     'DROGA ASEGURADA'],
            ['',                                      '',                              '', $fmt($resumen['VICTIMAS_TOTALES']),    'VICTIMAS (TOTALES)'],
            ['',                                      '',                              '', $fmt($resumen['EXAMENES_MANEJO']),     'EXAMENES DE MANEJO APLICADOS'],
        ];

        foreach ($datos as $fila) {
            $tabla->addRow(null, ['exactHeight'=>true, 'height'=>300]);

            $tabla->addCell(null, ['valign'=>'center'])
                  ->addText($fila[0], null, ['spaceBefore'=>0,'spaceAfter'=>0]);

            $tabla->addCell(null, ['valign'=>'center'])
                  ->addText($fila[1], null, ['spaceBefore'=>0,'spaceAfter'=>0]);

            $tabla->addCell(null, [
                'valign'=>'center',
                'borderTopSize'=>0,
                'borderBottomSize'=>0,
                'borderLeftSize'=>8,
                'borderRightSize'=>8,
                'borderColor'=>'000000',
            ])->addText('', null, ['spaceBefore'=>0,'spaceAfter'=>0]);

            $tabla->addCell(null, ['valign'=>'center'])
                  ->addText($fila[3], null, ['spaceBefore'=>0,'spaceAfter'=>0]);

            $tabla->addCell(null, ['valign'=>'center'])
                  ->addText($fila[4], null, ['spaceBefore'=>0,'spaceAfter'=>0]);
        }

        $section->addText(' ', [], ['spaceBefore'=>0,'spaceAfter'=>0,'lineHeight'=>0.5]);

        $phpWord->addTableStyle('TablaDaniosMateriales', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 1,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);
        $tablaDanios = $section->addTable('TablaDaniosMateriales');

        $montoDanios = 0;
        foreach ($hechos as $h) {
            foreach ($h->vehiculos as $v) {
                $montoDanios += floatval($v->monto_danos ?? 0);
            }
        }

        $montoFormateado = '$ ' . number_format($montoDanios, 2, '.', ',');
        $tablaDanios->addRow(null, ['exactHeight'=>true, 'height'=>400]);
        $tablaDanios->addCell(8000, ['valign'=>'center'])->addText(
            'DAÑOS MATERIALES DE LOS HECHOS DE TTO. CANTIDAD APROX.',
            ['bold'=>true],
            ['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore'=>0, 'spaceAfter'=>0]
        );
        $tablaDanios->addCell(2000, ['valign'=>'center'])->addText(
            $montoFormateado,
            ['bold'=>true],
            ['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore'=>0, 'spaceAfter'=>0]
        );

        $section->addText(' ', [], ['spaceBefore'=>0,'spaceAfter'=>0,'lineHeight'=>0.5]);

        $phpWord->addTableStyle('TablaInfracciones', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 1,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);
        $tablaInfracciones = $section->addTable('TablaInfracciones');

        $tablaInfracciones->addRow(null, ['exactHeight'=>true, 'height'=>400]);
        $tablaInfracciones->addCell(8000, ['valign'=>'center'])->addText(
            'INFRACCIONES ELABORADAS.',
            ['bold'=>true],
            ['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore'=>0, 'spaceAfter'=>0]
        );
        $tablaInfracciones->addCell(2000, ['valign'=>'center'])->addText(
            '0',
            ['bold'=>true],
            ['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore'=>0, 'spaceAfter'=>0]
        );

        $section->addText(' ', [], ['spaceBefore'=>0,'spaceAfter'=>0,'lineHeight'=>0.5]);

        $phpWord->addTableStyle('TablaKilometros', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 1,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);
        $tablaKilometros = $section->addTable('TablaKilometros');
        $tablaKilometros->addRow(null, ['exactHeight'=>true, 'height'=>400]);
        $tablaKilometros->addCell(8000, ['valign'=>'center'])->addText(
            'KILÓMETROS RECORRIDOS POR LAS UNIDADES.',
            ['bold'=>true],
            ['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore'=>0, 'spaceAfter'=>0]
        );
        $tablaKilometros->addCell(2000, ['valign'=>'center'])->addText(
            '0000',
            ['bold'=>true],
            ['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore'=>0, 'spaceAfter'=>0]
        );


        $hechoIds = $hechos->pluck('id');
        $vehiculoIds = DB::table('hecho_vehiculo')->whereIn('hecho_id', $hechoIds)->pluck('vehiculo_id');
        $conductorIds = DB::table('vehiculo_conductor')->whereIn('vehiculo_id', $vehiculoIds)->pluck('conductor_id');
        $conductores = Conductor::whereIn('id', $conductorIds)->get();
        $section->addText(' ', [], ['spaceBefore'=>0,'spaceAfter'=>0,'lineHeight'=>0.5]);

        $phpWord->addTableStyle('TablaOcupacionConductores', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 60,
            'alignment'   => Jc::CENTER,
            'tblWidth'    => 9000,
            'unit'        => TblWidth::TWIP,
        ]);

        $tablaOcupacion = $section->addTable('TablaOcupacionConductores');

        $tablaOcupacion->addRow(null, ['exactHeight'=>true,'height'=>100]);
        $tablaOcupacion->addCell(null, ['gridSpan'=>8,'valign'=>'center'])
            ->addText(
                'OCUPACIÓN CONDUCTORES',
                ['bold'=>true],
                ['alignment'=>Jc::CENTER,'spaceBefore'=>0,'spaceAfter'=>0]
            );

        $ocupaciones = ['EMPLEADO'=>0,'CHOFER'=>0,'COMERCIANTE'=>0,'OTRO'=>0];
        foreach ($conductores as $c) {
            $o = strtoupper(trim($c->ocupacion ?? 'OTRO'));
            if (str_contains($o, 'EMPLEADO'))      $ocupaciones['EMPLEADO']++;
            elseif (str_contains($o, 'CHOFER'))     $ocupaciones['CHOFER']++;
            elseif (str_contains($o, 'COMERCIANTE'))$ocupaciones['COMERCIANTE']++;
            else                                   $ocupaciones['OTRO']++;
        }

        $tablaOcupacion->addRow(null, ['exactHeight'=>true,'height'=>100]);
        foreach ([
            ['EMPLEADOS',    $ocupaciones['EMPLEADO']],
            ['CHOFERES',     $ocupaciones['CHOFER']],
            ['COMERCIANTES', $ocupaciones['COMERCIANTE']],
            ['OTROS',        $ocupaciones['OTRO']],
        ] as [$label, $count]) {
            $tablaOcupacion->addCell(null, ['valign'=>'center'])
                ->addText($label, [], ['alignment'=>Jc::CENTER,'spaceBefore'=>0,'spaceAfter'=>0]);
            $tablaOcupacion->addCell(null, ['valign'=>'center'])
                ->addText(str_pad($count,2,'0',STR_PAD_LEFT), ['bold'=>true], ['alignment'=>Jc::CENTER,'spaceBefore'=>0,'spaceAfter'=>0]);
        }

        $phpWord->addTableStyle('TablaFirmaSubdirector', [
            'borderSize' => 0,
            'borderColor' => 'ffffff',
            'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
            'cellMargin' => 80,
        ]);

        $tableFirma = $section->addTable('TablaFirmaSubdirector');
        $tableFirma->addRow();
        $tableFirma->addCell(9000, ['valign' => 'center'])->addText(
            'SUBDIRECTOR DE LA UNIDAD DE ATENCIÓN A SINIESTROS.',
            ['bold' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        $tableFirma->addRow();
        $tableFirma->addCell(9000)->addTextBreak(2);

        $tableFirma->addRow();
        $tableFirma->addCell(9000, ['valign' => 'center'])->addText(
            'LIC. LUIS ALBERTO NÚÑEZ RAZO',
            ['bold' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        $filename = "mini_parte_{$fecha}.docx";
        $tempPath = storage_path("app/public/{$filename}");
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }

    public function bitacora(Request $request)
    {
        $fecha = $request->input('fecha') ?? now()->format('Y-m-d');

        $inicio = Carbon::parse($fecha)->setTime(18, 0)->subDay();
        $fin    = Carbon::parse($fecha)->setTime(18, 0);

        $hechos = Hechos::with(['vehiculos', 'lesionados'])
            ->whereBetween('created_at', [$inicio, $fin])
            ->orderBy('hora')
            ->get();

        return view('admin.settings.estadisticas.bitacora', compact('hechos', 'fecha'));
    }

    public function descargarBitacora(Request $request)
    {
        $fecha  = $request->input('fecha') ?? now()->format('Y-m-d');
        $inicio = Carbon::parse($fecha)->setTime(18, 0)->subDay();
        $fin    = Carbon::parse($fecha)->setTime(18, 0);

        $hechos = Hechos::with(['vehiculos', 'lesionados'])
            ->whereBetween('created_at', [$inicio, $fin])
            ->orderBy('hora')
            ->get();

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'pageSizeW'    => 18720,
            'pageSizeH'    => 12240,
            'marginTop'    => 250,
            'marginRight'  => 600,
            'marginBottom' => 250,
            'marginLeft'   => 600,
        ]);

        $pCenter0 = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0];
        $pLeft0   = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,   'spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0];
        $pRight0  = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT,  'spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0];

        $phpWord->addTableStyle('EncabezadoTablaBitacora', [
            'borderSize'  => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 0,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);

        $tEnc = $section->addTable('EncabezadoTablaBitacora');

        $tEnc->addRow(420);
        $tEnc->addCell(9000, ['valign' => 'center'])->addImage(public_path('ssp.jpg'), [
            'width'     => 140,
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
        ]);
        $tEnc->addCell(9000, ['valign' => 'center'])->addImage(public_path('vialidad.png'), [
            'width'     => 70,
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT
        ]);

        $tEnc->addRow(420);
        $cTitulo = $tEnc->addCell(18000, ['gridSpan' => 2, 'valign' => 'center']);
        $trTitulo = $cTitulo->addTextRun($pCenter0);
        $trTitulo->addText('BITÁCORA', ['bold' => true, 'size' => 12]);
        $trTitulo->addText('    ', ['size' => 12]);
        $trTitulo->addText('UNIDAD DE ATENCIÓN A SINIESTROS', ['bold' => true, 'size' => 12]);

        $dia  = Carbon::parse($fecha)->format('d');
        $mes  = strtoupper(Carbon::parse($fecha)->translatedFormat('F'));
        $anio = Carbon::parse($fecha)->format('Y');

        $phpWord->addTableStyle('FechaDerechaTabla', [
            'borderSize'  => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 0,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);

        $tFecha = $section->addTable('FechaDerechaTabla');

        $tFecha->addRow(260);
        $tFecha->addCell(12000, ['valign' => 'center'])->addText('', ['size' => 10], $pCenter0);
        $tFecha->addCell(1500,  ['valign' => 'center'])->addText($dia,  ['bold' => true, 'size' => 10], $pCenter0);
        $tFecha->addCell(2500,  ['valign' => 'center'])->addText($mes,  ['bold' => true, 'size' => 10], $pCenter0);
        $tFecha->addCell(1500,  ['valign' => 'center'])->addText($anio, ['bold' => true, 'size' => 10], $pCenter0);

        $phpWord->addTableStyle('BitacoraTabla', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 20,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);
        $table = $section->addTable('BitacoraTabla');

        $headerCell = ['bgColor' => 'D9D9D9', 'valign' => 'center'];
        $cell       = ['valign' => 'center'];

        $wNo     = 650;
        $wHora   = 1200;
        $wUnidad = 1150;
        $wPerito = 3600;
        $wLugar  = 5200;
        $wGrua   = 1500;
        $wLes    = 2000;
        $wTipo   = 2400;
        $wObs    = 2500;

        $table->addRow(320);
        $table->addCell($wNo,     $headerCell)->addText('N°', ['bold' => true, 'size' => 10], $pCenter0);
        $table->addCell($wHora,   $headerCell)->addText('HORA DE SALIDA', ['bold' => true, 'size' => 10], $pCenter0);
        $table->addCell($wUnidad, $headerCell)->addText('UNIDAD', ['bold' => true, 'size' => 10], $pCenter0);
        $table->addCell($wPerito, $headerCell)->addText('PERITO(S) NOMBRE', ['bold' => true, 'size' => 10], $pCenter0);
        $table->addCell($wLugar,  $headerCell)->addText('LUGAR DE LOS HECHOS', ['bold' => true, 'size' => 10], $pCenter0);
        $table->addCell($wGrua,   $headerCell)->addText('GRUA', ['bold' => true, 'size' => 10], $pCenter0);
        $table->addCell($wLes,    $headerCell)->addText('PERSONAS LESIONADAS', ['bold' => true, 'size' => 10], $pCenter0);
        $table->addCell($wTipo,   $headerCell)->addText('TIPO DE HECHO', ['bold' => true, 'size' => 10], $pCenter0);
        $table->addCell($wObs,    $headerCell)->addText('OBSERVACIÓN / ESTATUS', ['bold' => true, 'size' => 10], $pCenter0);

        $n = 1;
        foreach ($hechos as $hecho) {
            $hora = !empty($hecho->hora)
                ? Carbon::parse($hecho->hora)->format('H:i')
                : Carbon::parse($hecho->created_at)->format('H:i');

            $unidad = (string)($hecho->unidad ?? '');
            $perito = strtoupper((string)($hecho->perito ?? ''));

            $lugar = trim((string)($hecho->calle ?? ''));
            if (!empty($hecho->colonia))   $lugar .= ($lugar !== '' ? ', ' : '') . 'COL. ' . $hecho->colonia;
            if (!empty($hecho->municipio)) $lugar .= ($lugar !== '' ? ', ' : '') . $hecho->municipio;

            $grua = 'NO';
            if ($hecho->vehiculos && $hecho->vehiculos->count() > 0) {
                $vConGrua = $hecho->vehiculos->first(function ($v) {
                    return $v->grua !== null && trim((string)$v->grua) !== '' && strtolower(trim((string)$v->grua)) !== 'n/a';
                });
                if ($vConGrua) $grua = strtoupper(trim((string)$vConGrua->grua));
            }

            $personasLes = ($hecho->lesionados && $hecho->lesionados->count() > 0)
                ? ($hecho->lesionados->count() . ' PERSONA(S)')
                : 'NO';

            $tipoHecho  = strtoupper((string)($hecho->tipo_hecho ?? ''));
            $estatus    = strtoupper((string)($hecho->situacion ?? ''));
            $obsEstatus = trim($estatus);

            $table->addRow(300);
            $table->addCell($wNo,     $cell)->addText((string)$n, ['size' => 10], $pCenter0);
            $table->addCell($wHora,   $cell)->addText($hora !== '' ? $hora : '-', ['size' => 10], $pCenter0);
            $table->addCell($wUnidad, $cell)->addText($unidad !== '' ? $unidad : '-', ['size' => 10], $pCenter0);
            $table->addCell($wPerito, $cell)->addText($perito !== '' ? $perito : '-', ['size' => 10], $pLeft0);
            $table->addCell($wLugar,  $cell)->addText($lugar !== '' ? $lugar : '-', ['size' => 10], $pLeft0);
            $table->addCell($wGrua,   $cell)->addText($grua, ['size' => 10], $pCenter0);
            $table->addCell($wLes,    $cell)->addText($personasLes, ['size' => 10], $pCenter0);
            $table->addCell($wTipo,   $cell)->addText($tipoHecho !== '' ? $tipoHecho : '-', ['size' => 10], $pCenter0);
            $table->addCell($wObs,    $cell)->addText($obsEstatus !== '' ? $obsEstatus : '-', ['size' => 10], $pCenter0);

            $n++;
        }

        // =====================================
        // ===== BLOQUE DE FIRMAS (AL FINAL) ====
        // =====================================

        // Día seleccionado (del filtro), no de un hecho
        $diaNum = (int) Carbon::parse($fecha)->format('d');

        // IMPAR = JORGE, PAR = FERNANDO
        $nombreFirma = ($diaNum % 2 === 1)
            ? 'JORGE ARMANDO MORALES PEREZ'
            : 'FERNANDO RUBALCAVA RIVERA';

        $section->addTextBreak(3);

        $section->addText(
            'ATENTAMENTE.',
            ['bold' => true, 'size' => 10],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
        );

        $section->addText(
            'COMANDANTE DE TURNO.',
            ['bold' => true, 'size' => 10],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
        );

        $section->addTextBreak(3);

        $section->addText(
            $nombreFirma,
            ['bold' => true, 'size' => 10],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
        );


        $filename = "bitacora_{$fecha}.docx";
        $tempPath = storage_path("app/public/{$filename}");
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }

    public function dictamen(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $resultados = collect();
        $modo = null;

        if ($q !== '') {

            if (ctype_digit($q)) {
                $modo = 'id';

                $hecho = Hechos::with(['vehiculos.conductores', 'lesionados'])
                    ->find((int)$q);

                if ($hecho) {
                    $resultados = collect([$hecho]);
                }

            } else {
                $modo = 'placa';

                $placa = mb_strtoupper($q);
                $placa = str_replace([' ', '-'], '', $placa);

                $resultados = Hechos::query()
                    ->whereHas('vehiculos', function ($query) use ($placa) {
                        $query->whereRaw("REPLACE(REPLACE(UPPER(placas), ' ', ''), '-', '') LIKE ?", ["%{$placa}%"]);
                    })
                    ->with(['vehiculos.conductores', 'lesionados'])
                    ->orderByDesc('fecha')
                    ->orderByDesc('hora')
                    ->get();
            }
        }

        return view('admin.settings.estadisticas.dictamen', compact('q', 'modo', 'resultados'));
    }

    public function dictamenShow($id)
    {
        $hecho = Hechos::with(['vehiculos.conductores', 'lesionados'])
            ->findOrFail($id);

        return view('admin.settings.estadisticas.dictamen-show', compact('hecho'));
    }

    public function dictamenDocx($id)
    {

        // ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
        // ┃            PARTE INFORMATIVO              ┃
        // ┃      GENERACIÓN DE DOCUMENTO OFICIAL      ┃
        // ┃   CAMBIOS AQUÍ ROMPEN EL FORMATO LEGAL    ┃
        // ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛


        $hecho = Hechos::with(['vehiculos.conductores', 'lesionados'])->findOrFail($id);
        $fecha = $hecho->fecha ?? now()->format('Y-m-d');
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection([
            'pageSizeW'   => 12240,
            'pageSizeH'   => 20160,
            'marginTop'   => 1134,
            'marginRight' => 1134,
            'marginBottom'=> 1134,
            'marginLeft'  => 1134,
        ]);

        $footer = $section->addFooter();

        $footer->addPreserveText(
            'Página {PAGE} de {NUMPAGES}',
            ['size' => 10],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        $phpWord->addTableStyle('EncabezadoTablaDictamen', [
            'borderSize' => 0,
            'borderColor'=> 'FFFFFF',
            'cellMargin' => 0,
            'alignment'  => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);

        $table = $section->addTable('EncabezadoTablaDictamen');

        $table->addRow();
        $table->addCell(5000, ['valign' => 'center'])->addImage(public_path('michoacan.jpg'), [
            'width'     => 140,
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
        ]);
        $table->addCell(5000, ['valign' => 'center'])->addImage(public_path('vialidad.png'), [
            'width'     => 70,
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT
        ]);

        $table->addRow();
        $phpWord->setDefaultParagraphStyle([
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1,
        ]);

        $phpWord->addTableStyle('TablaOficio', [
            'borderSize'  => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 0,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\JcTable::START,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'tblWidth'    => 100 * 50,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT,
        ]);

        $tablaOficio = $section->addTable('TablaOficio');
        $tablaOficio->setWidth(100 * 50);
        $p = ['spaceBefore'=>0,'spaceAfter'=>0,'lineHeight'=>1];

        $cell = [
            'bgColor'      => 'D9D9D9',
            'valign'       => 'center',
            'borderSize'   => 0,
            'borderColor'  => 'FFFFFF',
            'cellMargin'   => 0,
            'marginTop'    => 0,
            'marginBottom' => 1134,
            'marginLeft'   => 1134,
            'marginRight'  => 1134,
        ];

        // helper
        $addFila = function ($izq, $der) use ($tablaOficio, $cell, $p) {
            $tablaOficio->addRow(null, ['exactHeight' => true, 'height' => 260]);

            $tablaOficio->addCell(3200, $cell)->addText($izq, [], $p);
            $tablaOficio->addCell(6800, $cell)->addText($der, ['bold' => true], $p);
        };

        // FILAS
        $addFila('Dependencia',   'Secretaría de Seguridad Pública');
        $addFila('',             'Del Estado de Michoacán de Ocampo');
        $addFila('Sub-dependencia','');
        $addFila('Oficina',       'Unidad de Atención a Siniestros');
        $addFila('No. de oficio', 'Parte Informativo XXX/2026');
        $addFila('Expediente',    '');
        $addFila('Asunto',        '');

        $section->addTextBreak(1);

        $fechaFormatoOficio = 'Morelia Michoacán, ' 
            . Carbon::now()->format('d') 
            . ' de ' 
            . ucfirst(Carbon::now()->translatedFormat('F')) 
            . ' de ' 
            . Carbon::now()->format('Y') 
            . '.';

        $section->addText(
            $fechaFormatoOficio,
            [
                'bold' => true
            ],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
            ]
        );

        $destinatario = [
            'DIRECCIÓN DE CARPETAS DE',
            'INVESTIGACION DE LA FISCALIA GENERAL',
            'DE JUSTICIA EN EL ESTADO.',
            'P R E S E N T E'
        ];
        foreach ($destinatario as $linea) {
            $section->addText($linea, ['bold' => true], [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
            ]);
        }

        $section->addTextBreak(1);

        $textRun = $section->addTextRun([
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH
        ]);

        $textRun->addText(
            '                 El suscrito perito en hechos de tránsito ',
            ['bold' => false]
        );

        $textRun->addText(
            $hecho->perito,
            ['bold' => true]
        );

        $textRun->addText(
            ', adscrito a la Coordinación de Agrupamientos de Seguridad Vial, de la Secretaría de Seguridad Pública del Estado, tengo a bien emitir el siguiente:',
            ['bold' => false]
        );

        $section->addTextBreak(1);

        // === Título del dictamen (ya dentro del cuerpo) ===
        $section->addText(
            'PARTE INFORMATIVO',
            [
                'bold' => true,
                'size' => 14
            ],
            [
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
            ]
        );

        $section->addTextBreak(2);

        // === Planteamiento del problema ===
        $section->addText(
            '                 I. PLANTEAMIENTO DEL PROBLEMA',
            [
                'bold' => true,
                'size' => 14
            ],
        );

        $section->addTextBreak(1);

        // === OBJETO DEL DICTAMEN ===
        $section->addTextBreak(1);

        $tipoHecho   = strtoupper($hecho->tipo_hecho);
        $fechaHecho  = Carbon::parse($hecho->fecha)->format('d/m/Y');
        $horaHecho   = Carbon::parse($hecho->hora)->format('H:i');

        $calle       = $hecho->calle;
        $colonia     = $hecho->colonia;
        $municipio   = $hecho->municipio;

        $lat = $lat ?? null;
        $lng = $lng ?? null;

        $resultado = $resultado ?? null;

        $textRun = $section->addTextRun([
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH
        ]);

        $textRun->addText(
            '                 Establecer las causas que originaron el hecho de tránsito terrestre en su modalidad de '
        );

        $textRun->addText(
            '(' . $tipoHecho . ')',
            ['bold' => true]
        );

        if ($resultado) {
            $textRun->addText(' (');
            $textRun->addText($resultado, ['bold' => true]);
            $textRun->addText(')');
        }

        $textRun->addText(
            ', ocurrido el día '
        );

        $textRun->addText(
            $fechaHecho,
            ['bold' => true]
        );

        $textRun->addText(
            ', a las '
        );

        $textRun->addText(
            $horaHecho,
            ['bold' => true]
        );

        $textRun->addText(
            ' horas en '
        );

        $textRun->addText(
            $calle,
            ['bold' => true]
        );

        if ($colonia) {
            $textRun->addText(', ');
            $textRun->addText($colonia, ['bold' => true]);
        }

        if ($lat && $lng) {
            $textRun->addText(', ');
            $textRun->addText("{$lat}, {$lng}", ['bold' => true]);
        }

        $textRun->addText(
            ', en esta ciudad.'
        );

        $section->addTextBreak(2);

        // === Planteamiento del problema ===
        $section->addText(
            '                 II. METODOLOGÍA APLICADA AL PRESENTE INFORME PERICIAL:',
            [
                'bold' => true,
                'size' => 14
            ],
        );

        $section->addTextBreak(2);

        // === METODOLOGÍA ===
        $section->addText(
            '                 La metodología propuesta por el método científico, en cuanto al planteamiento del problema, la recopilación de datos por medio de la observación metódica y directa.',
            [],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 120,
            ]
        );

        $section->addTextBreak(1);

        $section->addText(
            '                 Para realizar el presente Parte Informativo aplicaremos:',
            [],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 120,
            ]
        );

        $section->addTextBreak(2);

        // Viñetas
        $section->addListItem(
            'Método inductivo es un método del que se obtienen conclusiones generales a partir de las premisas particulares.',
            0,
            [],
            ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 60,
            ]
        );

        $section->addTextBreak(2);

        $section->addListItem(
            'Método deductivo un método el cual se utiliza para interpretar hechos particulares a través de una ley general establecida y se deriva de hechos similares, al del objeto de estudio.',
            0,
            [],
            ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 0,
            ]
        );

        $section->addTextBreak(2);

        // === III MATERIAL UTILIZADO===
        $section->addText(
            '                 III. MATERIAL UTILIZADO:',
            [
                'bold' => true,
                'size' => 14
            ],
        );

        $section->addTextBreak(2);

        // Material Utilizado
        $section->addListItem(
            'Libreta de anotaciones, lapicero de punto medio.',
            0,
            [],
            ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 60,
            ]
        );

        $section->addListItem(
            'Cámara fotográfica digital.',
            0,
            [],
            ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 0,
            ]
        );

        $section->addListItem(
            'Cinta métrica.',
            0,
            [],
            ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 0,
            ]
        );

        $section->addListItem(
            'Brújula Digital para señalar la orientación.',
            0,
            [],
            ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 0,
            ]
        );

        $section->addTextBreak(1);

        // === IV OBJETIVOS  ===
        $section->addText(
            '                 IV. OBJETIVOS:',
            [
                'bold' => true,
                'size' => 14
            ],
        );

        $section->addTextBreak(2);

        // === OBJETIVOS ===
        $section->addText(
            '                 Contribuir con información sobre los datos e indicios recabados en el lugar',
            [],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 120,
            ]
        );

        $section->addTextBreak(2);

        // === V FIJACIÓN DEL LUGAR DE LA INTERVENCIÓN  ===
        $section->addText(
            '                 V. FIJACIÓN DEL LUGAR DE LA INTERVENCIÓN:',
            [
                'bold' => true,
                'size' => 14
            ],
        );

        $section->addTextBreak(2);

        // Material Utilizado
        $section->addListItem(
            'Fotográfica.',
            0,
            [],
            ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 60,
            ]
        );

        $section->addListItem(
            'Escrita.',
            0,
            [],
            ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 0,
            ]
        );

        $section->addListItem(
            'Planimetría.',
            0,
            [],
            ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 0,
            ]
        );

        $section->addTextBreak(2);

        // === VI CONDICIONES CLIMATÓLOGICAS  ===
        $section->addText(
            '                 VI. CONDICIONES CLIMATÓLOGICAS:',
            [
                'bold' => true,
                'size' => 14
            ]
        );

        $section->addTextBreak(2);

        // ---- TIEMPO ----
        $tiempoTexto = 'De día';

        switch (strtolower(trim($hecho->tiempo))) {
            case 'noche':
                $tiempoTexto = 'De noche';
                break;
            case 'atardecer':
                $tiempoTexto = 'Al atardecer';
                break;
            case 'amanecer':
                $tiempoTexto = 'Al amanecer';
                break;
            case 'día':
            case 'dia':
            default:
                $tiempoTexto = 'De día';
                break;
        }

        // ---- CLIMA ----
        $climaTexto = 'sin alteración meteorológica';

        switch (strtolower(trim($hecho->clima))) {
            case 'nublado':
                $climaTexto = 'nublado';
                break;
            case 'lluvioso':
                $climaTexto = 'con lluvia';
                break;
            case 'bueno':
            case 'malo':
            default:
                $climaTexto = 'sin alteración meteorológica';
                break;
        }

        // ---- TEXTO FINAL ----
        $section->addText(
            "{$tiempoTexto}, {$climaTexto}.",
            [],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 120
            ]
        );

        $section->addTextBreak(2);

        // === VII CONDICIONES DE ILUMINACIÓN  ===
        $section->addText(
            '                 VII. CONDICIONES DE ILUMINACIÓN:',
            [
                'bold' => true,
                'size' => 14
            ]
        );

        $section->addTextBreak(2);

        // ---- ILUMINACIÓN SEGÚN TIEMPO ----
        $iluminacionTexto = 'Prevalecía luz natural de día.';

        switch (strtolower(trim($hecho->tiempo))) {
            case 'noche':
            case 'atardecer':
                $iluminacionTexto = 'Prevalecía luz artificial emitida por las lámparas de alumbrado público que hay en el lugar.';
                break;

            case 'día':
            case 'dia':
            case 'amanecer':
            default:
                $iluminacionTexto = 'Prevalecía luz natural de día.';
                break;
        }

        $section->addText(
            $iluminacionTexto,
            [],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 120
            ]
        );

        $section->addTextBreak(2);

        // === VIII DESCRIPCIÓN DEL LUGAR DE LOS HECHOS  ===
        $section->addText(
            '                 VIII. DESCRIPCIÓN DEL LUGAR DE LOS HECHOS:',
            [
                'bold' => true,
                'size' => 14
            ]
        );

        $section->addTextBreak(2);

        $calle = trim((string) $hecho->calle);

        $textRun = $section->addTextRun([
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH
        ]);

        $textRun->addText('                 Corresponde a la ');
        $textRun->addText("{$calle}", ['bold' => true]);
        $textRun->addText(
            ', la cual se encuentra construida por una superficie de asfalto, en buen estado de conservación, tramo a nivel, cuenta con balizamientos, tiene capacidad para dos carriles de circulación, uno para cada sentido, orientados de norponiente a suroriente y viceversa, divididos por una línea continua longitudinal divisora de carriles, a la hora de la intervención la superficie de rodamiento se encontraba limpia y seca.'
        );

        $section->addTextBreak(2);

        // === IX DESCRIPCIÓN DE VEHÍCULOS  ===
        $section->addText(
            '                 IX. DESCRIPCIÓN DE VEHÍCULOS:',
            [
                'bold' => true,
                'size' => 14
            ]
        );

        $section->addTextBreak(2);

        $letras = range('A', 'Z');

        foreach ($hecho->vehiculos as $idx => $v) {
            $letra = $letras[$idx] ?? ('V' . ($idx + 1));

            $marca     = trim((string) $v->marca);
            $modelo    = trim((string) $v->modelo);
            $tipo      = trim((string) $v->tipo);
            $linea     = trim((string) $v->linea);
            $color     = trim((string) $v->color);
            $cap       = $v->capacidad_personas ? (string)$v->capacidad_personas : 's/e';
            $placas    = trim((string) $v->placas);
            $servicio  = trim((string) $v->tipo_servicio);
            $estadoPl  = trim((string) $v->estado_placas);
            $serie     = trim((string) $v->serie);
            $tarjeta   = trim((string) $v->tarjeta_circulacion_nombre);
            $textRun = $section->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH]);
            $textRun->addText('               VEHÍCULO ');
            $textRun->addText("({$letra})", ['bold' => true]);
            $textRun->addText('.- ');
            $textRun->addText('Marca ');
            $textRun->addText($marca ?: 's/e', ['bold' => true]);
            $textRun->addText(', Modelo ');
            $textRun->addText($modelo ?: 's/e', ['bold' => true]);
            $textRun->addText(', Tipo ');
            $textRun->addText($tipo ?: 's/e', ['bold' => true]);
            $textRun->addText(', Línea ');
            $textRun->addText($linea ?: 's/e', ['bold' => true]);
            $textRun->addText(', Color ');
            $textRun->addText($color ?: 's/e', ['bold' => true]);
            $textRun->addText(', Capacidad para ');
            $textRun->addText($cap, ['bold' => true]);
            $textRun->addText(' Personas, Placas para circular ');
            $textRun->addText($placas ?: 's/e', ['bold' => true]);

            if ($servicio !== '' || $estadoPl !== '') {
                $textRun->addText(' del servicio ');
                $textRun->addText($servicio !== '' ? $servicio : 's/e', ['bold' => true]);

                if ($estadoPl !== '') {
                    $textRun->addText(' de ');
                    $textRun->addText($estadoPl, ['bold' => true]);
                }
            }

            $textRun->addText(', Serie ');
            $textRun->addText($serie ?: 's/e', ['bold' => true]);

            if ($tarjeta !== '' && strtoupper($tarjeta) !== 'N/A') {
                $textRun->addText(', tarjeta de circulación a nombre de ');
                $textRun->addText($tarjeta, ['bold' => true]);
            }

            if ($v->conductores->count() === 0) {
                $textRun->addText('. No se cuenta con datos del conductor.');
            } else {
                foreach ($v->conductores as $cIdx => $c) {
                    $nombre = trim((string) $c->nombre);
                    $edad   = $c->edad ? (string)$c->edad : 's/e';
                    $dom    = trim((string) $c->domicilio);
                    $licencia = trim((string) $c->tipo_licencia);
                    $licTxt   = ($licencia !== '' ? $licencia : 'No presentó');
                    $textRun->addText(', el C. ');
                    $textRun->addText($nombre ?: 's/e', ['bold' => true]);
                    $textRun->addText(' de ');
                    $textRun->addText($edad, ['bold' => true]);
                    $textRun->addText(' años de edad');

                    if ($dom !== '') {
                        $textRun->addText(', con domicilio en ');
                        $textRun->addText($dom, ['bold' => true]);
                    }

                    $textRun->addText(', me manifestó ir a bordo del vehículo, ');
                    if ($licTxt === 'No presentó') {
                        $textRun->addText('No presentó licencia de conducir.');
                    } else {
                        $textRun->addText('presentó licencia tipo ');
                        $textRun->addText($licTxt, ['bold' => true]);
                        $textRun->addText('.');
                    }

                    if ($cIdx < $v->conductores->count() - 1) {
                        $textRun->addText(' ');
                    }
                }
            }

            $section->addTextBreak(2);
        }

        $section->addPageBreak();

        // === X DINÁMICA DEL HECHO DE TRÁNSITO  ===
        $section->addText(
            '                 X. DINÁMICA DEL HECHO DE TRÁNSITO:',
            [
                'bold' => true,
                'size' => 14
            ]
        );

        $section->addTextBreak(2);

        $section->addText(
            '                 Por los datos e informes recabados en el lugar del hecho, mediante la inspección ocular realizada por el suscrito, así como las huellas de colisión que presentan ambos vehículos, se sabe que este hecho de tránsito ocurrió en los momentos en que el conductor del vehículo (A), circulaba sobre la Av. Cointzio, en dirección de norponiente a suroriente, al momento de llegar a la altura de las coordenadas 19.660044, -101.281010, invade el carril contrario a su circulación, impactando con su ángulo frontal izquierdo, contra el ángulo frontal izquierdo del vehículo (B), el cual circulaba sobre la misma vía, en la dirección opuesta, logrando su posición final tal y como se muestra en el diagrama ilustrativo que anexo en el presente parte informativo.',
            [],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 120
            ]
        );

        $section->addTextBreak(2);

        // === XI DIAGRAMA ILUSTRATIVO NO HECHO A ESCALA  ===
        $section->addText(
            '                 XI. DIAGRAMA ILUSTRATIVO NO HECHO A ESCALA:',
            [
                'bold' => true,
                'size' => 14
            ]
        );

        $section->addTextBreak(2);

        $section->addPageBreak();

        // === XII FIJACIÓN FOTOGRAFICA  ===
        $section->addText(
            '                 XII. FIJACIÓN FOTOGRÁFICA:',
            [
                'bold' => true,
                'size' => 14
            ]
        );

        $section->addTextBreak(2);

        $section->addPageBreak();

        // === XIII VÍCTIMAS  ===
        $section->addText(
            '                 XIII. VÍCTIMAS:',
            [
                'bold' => true,
                'size' => 14
            ]
        );

        $section->addTextBreak(1);

        if ($hecho->lesionados->count() === 0) {

            $section->addText(
                '                 No se manifestaron ante el suscrito.',
                [],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH]
            );

        } else {

            foreach ($hecho->lesionados as $i => $l) {

                $nombre = trim((string) $l->nombre);
                $edad   = $l->edad ? (string)$l->edad : 's/e';
                $hospital = trim((string) ($l->hospital ?? ''));
                $unidad   = trim((string) ($l->unidad ?? ''));
                $cargo    = trim((string) ($l->a_cargo_de ?? $l->responsable_unidad ?? ''));

                $textRun = $section->addTextRun([
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH
                ]);

                $textRun->addText('                 De este hecho de tránsito resultaron lesionados: ');
                $textRun->addText($nombre, ['bold' => true]);
                $textRun->addText(' de ' . $edad . ' años de edad');
                $textRun->addText(', el cual fue trasladado');

                if ($hospital !== '') {
                    $textRun->addText(' al ');
                    $textRun->addText($hospital);
                }

                $textRun->addText(', para su atención médica');

                if ($unidad !== '') {
                    $textRun->addText(', abordo de la unidad ');
                    $textRun->addText($unidad);
                }

                if ($cargo !== '') {
                    $textRun->addText(' a cargo de ');
                    $textRun->addText($cargo);
                }

                $textRun->addText('.');

                if ($i < $hecho->lesionados->count() - 1) {
                    $section->addText(';', [], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH]);
                }
            }
        }

        $section->addTextBreak(2);

        // === XIV DAÑOS  ===
        $section->addText(
            '                 XIV. DAÑOS:',
            [
                'bold' => true,
                'size' => 14
            ]
        );

        $section->addTextBreak(1);

        // ---- Helper: número a letras (ES) para pesos ----
        $numeroALetrasES = function (int $n) {
            $u = [
                0=>'CERO',1=>'UNO',2=>'DOS',3=>'TRES',4=>'CUATRO',5=>'CINCO',6=>'SEIS',7=>'SIETE',8=>'OCHO',9=>'NUEVE',
                10=>'DIEZ',11=>'ONCE',12=>'DOCE',13=>'TRECE',14=>'CATORCE',15=>'QUINCE',16=>'DIECISÉIS',17=>'DIECISIETE',
                18=>'DIECIOCHO',19=>'DIECINUEVE',20=>'VEINTE',21=>'VEINTIUNO',22=>'VEINTIDÓS',23=>'VEINTITRÉS',24=>'VEINTICUATRO',
                25=>'VEINTICINCO',26=>'VEINTISÉIS',27=>'VEINTISIETE',28=>'VEINTIOCHO',29=>'VEINTINUEVE'
            ];
            $d = [30=>'TREINTA',40=>'CUARENTA',50=>'CINCUENTA',60=>'SESENTA',70=>'SETENTA',80=>'OCHENTA',90=>'NOVENTA'];
            $c = [100=>'CIEN',200=>'DOSCIENTOS',300=>'TRESCIENTOS',400=>'CUATROCIENTOS',500=>'QUINIENTOS',600=>'SEISCIENTOS',
                  700=>'SETECIENTOS',800=>'OCHOCIENTOS',900=>'NOVECIENTOS'];

            $toWords = function($n) use (&$toWords,$u,$d,$c) {
                $n = (int)$n;
                if ($n < 30) return $u[$n];
                if ($n < 100) {
                    $dec = ((int)($n/10))*10;
                    $rem = $n % 10;
                    return $rem ? ($d[$dec].' Y '.$u[$rem]) : $d[$dec];
                }
                if ($n < 1000) {
                    if ($n === 100) return 'CIEN';
                    $cen = ((int)($n/100))*100;
                    $rem = $n % 100;
                    $pref = ($cen === 100) ? 'CIENTO' : $c[$cen];
                    return $rem ? ($pref.' '.$toWords($rem)) : $pref;
                }
                if ($n < 2000) {
                    $rem = $n - 1000;
                    return $rem ? ('MIL '.$toWords($rem)) : 'MIL';
                }
                if ($n < 1000000) {
                    $mil = (int)($n/1000);
                    $rem = $n % 1000;
                    $txt = $toWords($mil).' MIL';
                    return $rem ? ($txt.' '.$toWords($rem)) : $txt;
                }
                if ($n < 2000000) {
                    $rem = $n - 1000000;
                    return $rem ? ('UN MILLÓN '.$toWords($rem)) : 'UN MILLÓN';
                }
                if ($n < 1000000000) {
                    $mil = (int)($n/1000000);
                    $rem = $n % 1000000;
                    $txt = $toWords($mil).' MILLONES';
                    return $rem ? ($txt.' '.$toWords($rem)) : $txt;
                }
                return 'NÚMERO FUERA DE RANGO';
            };

            return $toWords($n);
        };

        $pesosEnLetra = function ($monto) use ($numeroALetrasES) {
            $monto = is_numeric($monto) ? (float)$monto : 0.0;
            $entero = (int) floor($monto);
            $centavos = (int) round(($monto - $entero) * 100);
            if ($centavos === 100) { $entero += 1; $centavos = 0; }

            $letras = $numeroALetrasES($entero);
            $cc = str_pad((string)$centavos, 2, '0', STR_PAD_LEFT);

            return "{$letras} PESOS {$cc}/100 M.N.";
        };

        // ---- VEHÍCULOS (A,B,C,...) ----
        $letras = range('A', 'Z');

        foreach ($hecho->vehiculos as $idx => $v) {
            $letra = $letras[$idx] ?? ('V' . ($idx + 1));

            $partes = trim((string) ($v->partes_danadas ?? ''));
            if ($partes === '') $partes = 's/e';

            $monto = is_numeric($v->monto_danos) ? (float)$v->monto_danos : 0.0;
            $montoFmt = '$ ' . number_format($monto, 2);
            $montoLetra = $pesosEnLetra($monto);

            $textRun = $section->addTextRun([
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH
            ]);

            $textRun->addText('               VEHÍCULO ');
            $textRun->addText("({$letra})", ['bold' => true]);
            $textRun->addText('.- Presenta daños en su ');
            $textRun->addText($partes, ['bold' => true]);
            $textRun->addText(', se estiman en la cantidad aproximada para su reparación de ');
            $textRun->addText($montoFmt, ['bold' => true]);
            $textRun->addText(' (');
            $textRun->addText($montoLetra, ['bold' => true]);
            $textRun->addText(').');

            $section->addTextBreak(1);
        }

        // ---- Aclaración final ----
        $section->addText(
            '                 Estos daños fueron estimados y calculados a simple vista y será salvo el presupuesto real que le sea presentado ante usted por las partes involucradas una vez que hayan sido desarmadas todas y cada una de las piezas dañadas.',
            [],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 120
            ]
        );

        $section->addTextBreak(2);

        // === XV OBSERVACIONES  ===
        $section->addText(
            '                 XV. OBSERVACIONES:',
            [
                'bold' => true,
                'size' => 14
            ]
        );

        $section->addTextBreak(1);

        $gruaNombres = $hecho->vehiculos
            ->pluck('grua')
            ->filter(fn($x) => !is_null($x) && trim((string)$x) !== '' && trim((string)$x) !== '0')
            ->map(fn($x) => strtoupper(trim((string)$x)))
            ->unique()
            ->values();

        $gruaNombre = '________________';
        $gruaDireccion = '________________';

        if ($gruaNombres->count() > 0) {

            $gruas = \App\Models\Grua::whereIn(\DB::raw('UPPER(nombre)'), $gruaNombres->toArray())->get();

            if ($gruas->count() > 0) {
                $gruaNombre = $gruas->pluck('nombre')->filter()->implode(' y ');

                $dir = $gruas->pluck('direccion')->filter()->first();
                if (!$dir) {
                    $dir = $gruas->pluck('ubicacion_corralon')->filter()->first();
                }
                $gruaDireccion = $dir ? $dir : '________________';
            } else {
                $gruaNombre = $gruaNombres->implode(' y ');
                $gruaDireccion = '________________';
            }
        }

        // ---- Texto con variables en negritas ----
        $textRun = $section->addTextRun([
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH
        ]);

        $textRun->addText('                 Para el traslado de ambos vehículos fui auxiliado por la grúa particular ');
        $textRun->addText($gruaNombre, ['bold' => true]);
        $textRun->addText(', quien los resguardó en sus propias instalaciones, garaje de apoyo a esta dependencia, ubicado en ');
        $textRun->addText($gruaDireccion, ['bold' => true]);
        $textRun->addText('.');

        $section->addTextBreak(1);

        // === XVI CAUSAS  ===
        $section->addText(
            '                 XVI. CAUSAS:',
            [
                'bold' => true,
                'size' => 14
            ]
        );

        $section->addTextBreak(1);

        // ---- PÁRRAFO ÚNICO ----
        $section->addText(
            '                 ÚNICA.- La causa que da origen al hecho de tránsito que nos ocupa se refiere a la falta de precaución y cuidado por parte del conductor del vehículo (A), no compartir adecuadamente los carriles de circulación e invadir el carril contrario a su circulación, en consecuencia ocasionar lesiones y daños materiales, violando por tal motivo el artículo 432 Fracción V, del Reglamento de la Ley de Movilidad y Seguridad Vial vigente en el Estado.',
            [],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 120
            ]
        );

        // ---- Determinar texto según cantidad de vehículos ----
        $totalVehiculos = $hecho->vehiculos->count();
        $txtVehiculos = 'los vehículos';

        if ($totalVehiculos === 1) {
            $txtVehiculos = 'el vehículo';
        } elseif ($totalVehiculos === 2) {
            $txtVehiculos = 'ambos vehículos';
        } else {
            $txtVehiculos = 'los vehículos';
        }

        // ---- OBTENER NOMBRE DE GRÚA (vehiculos.grua guarda el NOMBRE) ----
        $gruaNombres = $hecho->vehiculos
            ->pluck('grua')
            ->filter(fn($x) => !is_null($x) && trim((string)$x) !== '' && trim((string)$x) !== '0')
            ->map(fn($x) => strtoupper(trim((string)$x)))
            ->unique()
            ->values();

        $gruaNombre = $gruaNombres->count() > 0
            ? $gruaNombres->implode(' y ')
            : '________________';

        $section->addTextBreak(1);

        // ---- SEGUNDO PÁRRAFO (CONDICIONAL A LESIONADOS) ----
        $textRun = $section->addTextRun([
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH
        ]);

        $textRun->addText(
            '                 Con base en lo dispuesto en el artículo 328 Fracción XVI de la Ley de Movilidad y Seguridad Vial vigente en el Estado, '
        );

        // SOLO SI HAY LESIONADOS
        if ($hecho->lesionados->count() > 0) {
            $textRun->addText(
                'Quedan los lesionados recibiendo atención médica en el nosocomio antes mencionado y '
            );
        }

        $textRun->addText(
            'Pongo a su disposición ' . $txtVehiculos . ', en las instalaciones de '
        );

        $textRun->addText(
            'GRÚAS ' . $gruaNombre,
            ['bold' => true]
        );

        $textRun->addText(
            ', garaje de apoyo a esta dependencia, lo anterior para los fines legales a los que haya lugar.'
        );

        $section->addTextBreak(2);

        // === FIRMA ===
        $section->addTextBreak(3);

        $section->addText(
            'ATENTAMENTE.',
            ['bold' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        $section->addText(
            'PERITO DE TRÁNSITO.',
            ['bold' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        $section->addTextBreak(3);

        $section->addText(
            strtoupper($hecho->perito),
            ['bold' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        $section->addPageBreak();




        // ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
        // ┃                  IPH                      ┃
        // ┃      GENERACIÓN DE DOCUMENTO OFICIAL      ┃
        // ┃   CAMBIOS AQUÍ ROMPEN EL FORMATO LEGAL    ┃
        // ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

        $fechaEvento = !empty($hecho->created_at)
            ? \Carbon\Carbon::parse($hecho->created_at)->format('d/m/Y')
            : (!empty($hecho->fecha) ? \Carbon\Carbon::parse($hecho->fecha)->format('d/m/Y') : '');

        $horaEvento = !empty($hecho->created_at)
            ? \Carbon\Carbon::parse($hecho->created_at)->format('H:i')
            : (!empty($hecho->hora) ? substr((string)$hecho->hora, 0, 5) : '');

        $fechaInforme = now()->format('d/m/Y');
        $horaInforme  = '';

        // === INFORME POLICIAL HOMOLOGADO ===
        $section->addText(
            'INFORME POLICIAL HOMOLOGADO',
            ['bold' => true, 'size' => 14],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
        );

        $pCenter0 = [
            'alignment'   => Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0
        ];

        $table = $section->addTable([
            'alignment'   => Jc::RIGHT,
            'width'       => 100,
            'unit'        => TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 0
        ]);

        $wLabel = 1100;
        $wVal   = 2500;
        $headerCellStyle = ['bgColor' => 'EBE1D1', 'valign' => 'center'];

        $table->addRow(260);
        $table->addCell($wLabel + $wVal, array_merge($headerCellStyle, ['gridSpan' => 2]))
              ->addText('EVENTO', ['bold' => true], $pCenter0);

        $table->addCell($wLabel + $wVal, array_merge($headerCellStyle, ['gridSpan' => 2]))
              ->addText('INFORME', ['bold' => true], $pCenter0);

        $table->addRow(340);
        $table->addCell($wLabel, ['valign' => 'center'])->addText('FECHA', [], $pCenter0);
        $table->addCell($wVal,   ['valign' => 'center'])->addText($fechaEvento,  ['bold' => true, 'size' => 12], $pCenter0);
        $table->addCell($wLabel, ['valign' => 'center'])->addText('FECHA', [], $pCenter0);
        $table->addCell($wVal,   ['valign' => 'center'])->addText($fechaInforme, ['bold' => true, 'size' => 12], $pCenter0);

        $table->addRow(340);
        $table->addCell($wLabel, ['valign' => 'center'])->addText('HORA', [], $pCenter0);
        $table->addCell($wVal,   ['valign' => 'center'])->addText($horaEvento,  ['bold' => true, 'size' => 12], $pCenter0);
        $table->addCell($wLabel, ['valign' => 'center'])->addText('HORA', [], $pCenter0);
        $table->addCell($wVal,   ['valign' => 'center'])->addText($horaInforme, ['bold' => true, 'size' => 12], $pCenter0);

        // ===== SEGUNDA TABLA IPH =====

        $tituloTipoEvento = ($hecho->lesionados->count() > 0)
            ? 'LESIONES Y DAÑO A LAS COSAS'
            : 'DAÑO A LAS COSAS';

        $calle       = (string)($hecho->calle ?? '');
        $colonia     = (string)($hecho->colonia ?? '');
        $municipio   = (string)($hecho->municipio ?? '');
        $referencias = (string)($hecho->entre_calles ?? '');

        $coords = '';
        if (!empty($hecho->lat) && !empty($hecho->lng)) {
            $coords = trim($hecho->lat . ', ' . $hecho->lng);
        }

        $ubicacion = trim($calle);
        if ($colonia !== '')   $ubicacion .= ($ubicacion !== '' ? ', ' : '') . $colonia;
        if ($municipio !== '') $ubicacion .= ($ubicacion !== '' ? ', ' : '') . $municipio;
        if ($coords !== '')    $ubicacion .= ($ubicacion !== '' ? ', ' : '') . $coords;

        $peritoCompleto = (string)($hecho->perito ?? '');

        $hayDetenidos = (int)($hecho->personas_mp ?? 0) > 0;
        $hayVehiculos = isset($hecho->vehiculos)
            ? ($hecho->vehiculos->count() > 0)
            : ((int)($hecho->vehiculos_mp ?? 0) > 0);

        $vehDanado = false;
        $vehAsegurado = false;
        if (isset($hecho->vehiculos)) {
            foreach ($hecho->vehiculos as $v) {
                if (!empty($v->partes_danadas) || ((float)($v->monto_danos ?? 0) > 0)) $vehDanado = true;
                if (!empty($v->corralon) || (!empty($v->grua) && strtoupper((string)$v->grua) !== 'NO')) $vehAsegurado = true;
            }
        }

        $flagrancia  = false;
        $casoUrgente = false;
        $usoFuerzaSi = false;

        $robado     = false;
        $recuperado = false;
        $abandonado = false;

        $fontNormal7 = ['name' => 'Arial', 'size' => 8];
        $fontBold7   = ['name' => 'Arial', 'size' => 8, 'bold' => true];

        $pLeft0   = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,   'spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0];
        $pCenter0 = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0];

        $leftColCell = ['bgColor' => 'EBE1D1', 'valign' => 'center'];
        $cellMid     = ['valign' => 'center'];

        $section->addText('', [], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]);

        $wC1 = 2600;
        $wC2 = 2000;
        $wC3 = 3000;
        $wC4 = 2200;
        $tableW = 9800;

        $table = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 0,
        ]);


        $table->addRow(320);
        $table->addCell($wC1, $leftColCell)->addText('TIPO DE EVENTO', $fontNormal7, $pCenter0);
        $table->addCell($wC2, array_merge($cellMid, ['gridSpan' => 3]))->addText($tituloTipoEvento, $fontBold7, $pCenter0);
        $table->addRow(420);
        $table->addCell($wC1, $leftColCell)->addText('LUGAR DEL EVENTO', $fontNormal7, $pCenter0);

        $cellUb = $table->addCell($wC2, array_merge($cellMid, ['gridSpan' => 3]));
        $runUb = $cellUb->addTextRun($pLeft0);
        $runUb->addText($ubicacion, $fontBold7);
        if ($referencias !== '') {
            $runUb->addText('  ' . $referencias, $fontBold7);
        }

        $table->addRow(420);
        $table->addCell($wC1, $leftColCell)->addText("PERITO QUE\nLEVANTA EL ACTA", $fontNormal7, $pCenter0);
        $table->addCell($wC2, array_merge($cellMid, ['gridSpan' => 3]))->addText($peritoCompleto, $fontBold7, $pLeft0);

        $table->addRow(420);
        $table->addCell($wC1, $leftColCell)->addText("PERSONAS\nDETENIDAS", $fontNormal7, $pCenter0);

        $cellPD2 = $table->addCell($wC2, $cellMid);
        $runPD2  = $cellPD2->addTextRun($pLeft0);
        $runPD2->addText('SI [', $fontNormal7);
        $runPD2->addText($hayDetenidos ? 'X' : ' ', $hayDetenidos ? $fontBold7 : $fontNormal7);
        $runPD2->addText(']   NO [', $fontNormal7);
        $runPD2->addText(!$hayDetenidos ? 'X' : ' ', (!$hayDetenidos) ? $fontBold7 : $fontNormal7);
        $runPD2->addText(']', $fontNormal7);

        $cellPD3 = $table->addCell($wC3, $cellMid);
        $runPD3  = $cellPD3->addTextRun($pLeft0);
        $runPD3->addText('FLAGRANCIA [', $fontNormal7);
        $runPD3->addText($flagrancia ? 'X' : ' ', $flagrancia ? $fontBold7 : $fontNormal7);
        $runPD3->addText(']   CASO URGENTE [', $fontNormal7);
        $runPD3->addText($casoUrgente ? 'X' : ' ', $casoUrgente ? $fontBold7 : $fontNormal7);
        $runPD3->addText(']', $fontNormal7);

        $cellPD4 = $table->addCell($wC4, $cellMid);
        $runPD4  = $cellPD4->addTextRun($pLeft0);
        $runPD4->addText('USO DE FUERZA FISICA  ', $fontNormal7);
        $runPD4->addText('SI [', $fontNormal7);
        $runPD4->addText($usoFuerzaSi ? 'X' : ' ', $usoFuerzaSi ? $fontBold7 : $fontNormal7);
        $runPD4->addText(']  NO [', $fontNormal7);
        $runPD4->addText(!$usoFuerzaSi ? 'X' : ' ', (!$usoFuerzaSi) ? $fontBold7 : $fontNormal7);
        $runPD4->addText(']', $fontNormal7);

        $table->addRow(420);
        $table->addCell($wC1, $leftColCell)->addText("VEHÍCULOS\nINVOLUCRADOS", $fontNormal7, $pCenter0);

        $cellV2 = $table->addCell($wC2, $cellMid);
        $runV2  = $cellV2->addTextRun($pLeft0);
        $runV2->addText('SI [', $fontNormal7);
        $runV2->addText($hayVehiculos ? 'X' : ' ', $hayVehiculos ? $fontBold7 : $fontNormal7);
        $runV2->addText(']   NO [', $fontNormal7);
        $runV2->addText(!$hayVehiculos ? 'X' : ' ', (!$hayVehiculos) ? $fontBold7 : $fontNormal7);
        $runV2->addText(']', $fontNormal7);

        $cellV3 = $table->addCell($wC3, $cellMid);
        $runV3  = $cellV3->addTextRun($pLeft0);
        $runV3->addText('ROBADO [', $fontNormal7);
        $runV3->addText($robado ? 'X' : ' ', $robado ? $fontBold7 : $fontNormal7);
        $runV3->addText(']  DAÑADO [', $fontNormal7);
        $runV3->addText($vehDanado ? 'X' : ' ', $vehDanado ? $fontBold7 : $fontNormal7);
        $runV3->addText(']  ASEGURADO [', $fontNormal7);
        $runV3->addText($vehAsegurado ? 'X' : ' ', $vehAsegurado ? $fontBold7 : $fontNormal7);
        $runV3->addText(']', $fontNormal7);

        $cellV4 = $table->addCell($wC4, $cellMid);
        $runV4  = $cellV4->addTextRun($pLeft0);
        $runV4->addText('RECUPERADO [', $fontNormal7);
        $runV4->addText($recuperado ? 'X' : ' ', $recuperado ? $fontBold7 : $fontNormal7);
        $runV4->addText(']  ABANDONADO [', $fontNormal7);
        $runV4->addText($abandonado ? 'X' : ' ', $abandonado ? $fontBold7 : $fontNormal7);
        $runV4->addText(']', $fontNormal7);

        $section->addTextBreak(1);

        // ===== TERCERA TABLA: FUNDAMENTO =====
        $bgFund = 'EBE1D1';

        $tablaFund = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 0,
        ]);

        $cellFund = [
            'bgColor'      => $bgFund,
            'valign'       => 'center',
            'borderSize'   => 0,
            'borderColor'  => 'FFFFFF',
            'borderTopSize'=> 0,
            'borderLeftSize'=>0,
            'borderRightSize'=>0,
            'borderBottomSize'=>0,
            'cellMargin'   => 0,
            'marginTop'    => 0,
            'marginBottom' => 0,
            'marginLeft'   => 0,
            'marginRight'  => 0,
        ];

        $tablaFund->addRow(320, ['exactHeight' => true, 'height' => 320]);
        $tablaFund->addCell($tableW, $cellFund)->addText('FUNDAMENTO', $fontBold7, $pCenter0);

        $tablaFund->addRow(700, ['exactHeight' => true, 'height' => 700]);
        $cell = $tablaFund->addCell($tableW, $cellFund);

        $run = $cell->addTextRun($pCenter0);
        $run->addText(
            'Artículos 21 párrafo primero de la Constitución Política de los Estados Unidos Mexicanos,',
            $fontNormal7
        );
        $run->addTextBreak();
        $run->addText(
            '132 fracción XIV, 217, 221, 222 del Código Nacional de Procedimientos Penales',
            $fontNormal7
        );

        $section->addTextBreak(1);

        $bgNarr = 'EBE1D1';

        $horaBase    = \Carbon\Carbon::parse($hecho->hora);
        $horaMenos10 = $horaBase->copy()->subMinutes(10)->format('H:i');
        $horaArribo  = $horaBase->format('H:i');

        $ubiNarr = trim((string)($hecho->calle ?? ''));
        $colNarr = trim((string)($hecho->colonia ?? ''));
        if ($colNarr !== '') $ubiNarr .= ($ubiNarr !== '' ? ', ' : '') . $colNarr;

        if (!empty($hecho->lat) && !empty($hecho->lng)) {
            $coordsNarr = trim($hecho->lat . ', ' . $hecho->lng);
            $ubiNarr .= ($ubiNarr !== '' ? ', ' : '') . $coordsNarr;
        }

        $tipoHechoNarr = strtoupper((string)($hecho->tipo_hecho ?? ''));
        $unidadNarr     = (string)($hecho->unidad ?? '');

        $tablaNarr = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 80,
        ]);

        $cellHeaderNarr = ['bgColor' => $bgNarr, 'valign' => 'center'];
        $cellBodyNarr   = ['valign'  => 'top'];

        $tablaNarr->addRow(420, ['exactHeight' => true, 'height' => 420]);
        $cellH = $tablaNarr->addCell($tableW, $cellHeaderNarr);

        $run = $cellH->addTextRun($pCenter0);
        $run->addText('NARRATIVA DE LOS HECHOS', $fontBold7, $pCenter0);
        $run->addTextBreak();
        $run->addText('(Qué, Quién, Cuándo, Dónde, Cómo, Porqué, Con qué)', $fontNormal7, $pCenter0);

        $tablaNarr->addRow(1200, ['exactHeight' => false]);
        $cellB = $tablaNarr->addCell($tableW, $cellBodyNarr);

        $narrativaTxt =
            "                 Siendo las {$horaMenos10} horas me encontraba de recorrido vigilancia y disuasión del Delito sobre el Periférico Independencia # 5000, col. Sentimientos de la Nación, cuando por medio de la base de radio C-5i, reporto un hecho de tránsito: ({$tipoHechoNarr}) en {$ubiNarr}, motivo por el cual me traslade al lugar mencionado abordo de la unidad {$unidadNarr}, arribando a las {$horaArribo} horas.";

        $cellB->addText($narrativaTxt, [], [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ]);

        $cellB->addTextBreak(1);

        // === XIII VÍCTIMAS ===
        $cellB->addText('VÍCTIMAS:', ['bold' => true, 'size' => 10], [
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
        ]);

        $cellB->addTextBreak(1);

        if ($hecho->lesionados->count() === 0) {

            $cellB->addText('No se manifestaron ante el suscrito.', [], [
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH
            ]);

        } else {

            $tr = $cellB->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH]);
            $tr->addText('De este hecho de tránsito resultaron lesionados: ');

            foreach ($hecho->lesionados as $i => $l) {

                $nombre   = trim((string)($l->nombre ?? ''));
                $edad     = $l->edad ? (string)$l->edad : 's/e';

                $hospital = trim((string)($l->hospital ?? ''));
                $unidad   = trim((string)($l->unidad ?? ''));
                $cargo    = trim((string)($l->a_cargo_de ?? $l->responsable_unidad ?? ''));

                if ($i > 0) $tr->addText('; ');

                $tr->addText($nombre !== '' ? $nombre : 's/e', ['bold' => true]);
                $tr->addText(' de ' . $edad . ' años de edad, el cual fue trasladado');

                if ($hospital !== '') $tr->addText(' al ' . $hospital);

                $tr->addText(', para su atención médica');

                if ($unidad !== '') $tr->addText(', abordo de la unidad ' . $unidad);

                if ($cargo !== '') $tr->addText(' a cargo de ' . $cargo);

                $tr->addText('.');
            }
        }

        $cellB->addTextBreak(1);

        // === X DINÁMICA DEL HECHO DE TRÁNSITO ===
        $cellB->addText('DINÁMICA DEL HECHO DE TRÁNSITO:', ['bold' => true, 'size' => 10], [
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
        ]);

        $cellB->addTextBreak(1);

        $cellB->addText(
            'Por los datos e informes recabados en el lugar del hecho, mediante la inspección ocular realizada por el suscrito, así como las huellas de colisión que presentan ambos vehículos, se sabe que este hecho de tránsito ocurrió en los momentos en que el conductor del vehículo (A), circulaba sobre la Av. Cointzio, en dirección de norponiente a suroriente, al momento de llegar a la altura de las coordenadas 19.660044, -101.281010, invade el carril contrario a su circulación, impactando con su ángulo frontal izquierdo, contra el ángulo frontal izquierdo del vehículo (B), el cual circulaba sobre la misma vía, en la dirección opuesta, logrando su posición final tal y como se muestra en el diagrama ilustrativo que anexo en el presente parte informativo.',
            [],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 120
            ]
        );

        $cellB->addTextBreak(1);

        // === XV OBSERVACIONES ===
        $cellB->addText('OBSERVACIONES:', ['bold' => true, 'size' => 10], [
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
        ]);

        $cellB->addTextBreak(1);

        // En tu DB: vehiculos.grua guarda NOMBRE (ej. "DANNYS")
        $gruaNombres = $hecho->vehiculos
            ->pluck('grua')
            ->filter(fn($x) => !is_null($x) && trim((string)$x) !== '' && trim((string)$x) !== '0')
            ->map(fn($x) => strtoupper(trim((string)$x)))
            ->unique()
            ->values();

        $gruaNombre    = '________________';
        $gruaDireccion = '________________';

        if ($gruaNombres->count() > 0) {

            $gruas = \App\Models\Grua::whereIn(\DB::raw('UPPER(nombre)'), $gruaNombres->toArray())->get();

            if ($gruas->count() > 0) {

                $gruaNombre = $gruas->pluck('nombre')->filter()->implode(' y ');

                $dir = $gruas->pluck('direccion')->filter()->first();
                if (!$dir) $dir = $gruas->pluck('ubicacion_corralon')->filter()->first();

                $gruaDireccion = $dir ? $dir : '________________';

            } else {

                $gruaNombre    = $gruaNombres->implode(' y ');
                $gruaDireccion = '________________';
            }
        }

        $trObs = $cellB->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH]);
        $trObs->addText('Para el traslado de ambos vehículos fui auxiliado por la grúa particular ');
        $trObs->addText($gruaNombre, ['bold' => true]);
        $trObs->addText(', quien los resguardó en sus propias instalaciones, garaje de apoyo a esta dependencia, ubicado en ');
        $trObs->addText($gruaDireccion, ['bold' => true]);
        $trObs->addText('.');

        $cellB->addTextBreak(1);

        // === XVI CAUSAS ===
        $cellB->addText('CAUSAS:', ['bold' => true, 'size' => 10], [
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
        ]);

        $cellB->addTextBreak(1);

        $cellB->addText(
            'ÚNICA.- La causa que da origen al hecho de tránsito que nos ocupa se refiere a la falta de precaución y cuidado por parte del conductor del vehículo (A), no compartir adecuadamente los carriles de circulación e invadir el carril contrario a su circulación, en consecuencia ocasionar lesiones y daños materiales, violando por tal motivo el artículo 432 Fracción V, del Reglamento de la Ley de Movilidad y Seguridad Vial vigente en el Estado.',
            [],
            [
                'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'spaceAfter' => 120
            ]
        );

        $totalVehiculos = $hecho->vehiculos->count();
        $txtVehiculos   = 'los vehículos';
        if ($totalVehiculos === 1) $txtVehiculos = 'el vehículo';
        if ($totalVehiculos === 2) $txtVehiculos = 'ambos vehículos';

        $gruaNombres2 = $hecho->vehiculos
            ->pluck('grua')
            ->filter(fn($x) => !is_null($x) && trim((string)$x) !== '' && trim((string)$x) !== '0')
            ->map(fn($x) => strtoupper(trim((string)$x)))
            ->unique()
            ->values();

        $gruaNombre2 = $gruaNombres2->count() > 0 ? $gruaNombres2->implode(' y ') : '________________';

        $cellB->addTextBreak(1);

        $trCaus = $cellB->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH]);
        $trCaus->addText('Con base en lo dispuesto en el artículo 328 Fracción XVI de la Ley de Movilidad y Seguridad Vial vigente en el Estado, ');

        if ($hecho->lesionados->count() > 0) {
            $trCaus->addText('Quedan los lesionados recibiendo atención médica en el nosocomio antes mencionado y ');
        }

        $trCaus->addText('Pongo a su disposición ' . $txtVehiculos . ', en las instalaciones de ');
        $trCaus->addText('GRÚAS ' . $gruaNombre2, ['bold' => true]);
        $trCaus->addText(', garaje de apoyo a esta dependencia, lo anterior para los fines legales a los que haya lugar.');

        $section->addTextBreak(1);

        // ===== TABLA 2 FILAS: AUXILIO PRESTADO A =====

        $bgAux = 'EBE1D1';

        $fontAuxTitle = $fontBold7;
        $fontAuxSmall = ['name' => 'Arial', 'size' => 6];

        $pAuxCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];
        $pAuxLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaAux = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 20,
        ]);

        $tablaAux->addRow(260, ['exactHeight' => true, 'height' => 260]);
        $cellH = $tablaAux->addCell($tableW, [
            'bgColor' => $bgAux,
            'valign'  => 'center',
            'vMerge'  => null,
        ]);

        $cellH->addText('AUXILIO PRESTADO A :', $fontAuxTitle, $pAuxCenterTight);

        $tablaAux->addRow(300, ['exactHeight' => true, 'height' => 300]);
        $cellB = $tablaAux->addCell($tableW, ['valign' => 'center']);

        $textoAux =
            "VÍCTIMA(S) [   ]   OFENDIDO(S) [   ]   DENUNCIANTE(S) [   ]   TESTIGO(S) [   ]   DETENIDO(S) [   ]   NO APLICA [ X ]";

        $textoAuxFinal = $textoAux;

        $cellB->addText($textoAuxFinal, $fontAuxSmall, $pAuxLeftTight);

        $section->addTextBreak();

        $bgAux = 'EBE1D1';

        $fontAuxTitle = $fontBold7;
        $fontAuxSmall = ['name' => 'Arial', 'size' => 6];

        $pAuxCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];
        $pAuxLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaTipoAux = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 20,
        ]);

        $tablaTipoAux->addRow(260, ['exactHeight' => true, 'height' => 260]);
        $cellH = $tablaTipoAux->addCell($tableW, ['bgColor' => $bgAux, 'valign' => 'center']);
        $cellH->addText('TIPO DE AUXILIO :', $fontAuxTitle, $pAuxCenterTight);

        $tablaTipoAux->addRow(320, ['exactHeight' => true, 'height' => 320]);
        $cellB = $tablaTipoAux->addCell($tableW, ['valign' => 'center']);

        $textoTipoAux =
            "PRIMEROS AUXILIOS [   ]        TRASLADO [   ]         CUSTODIA POLICIACA [   ]          OTRO [   ]  especifique: ________";

        $cellB->addText($textoTipoAux, $fontAuxSmall, $pAuxLeftTight);

        $section->addTextBreak();

        // ===== TABLA 2 FILAS: TRASLADO o CANALIZACIONES =====

        $bgAux = 'EBE1D1';
        $fontAuxTitle = $fontBold7;
        $fontAuxSmall = ['name' => 'Arial', 'size' => 6];

        $pAuxCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];
        $pAuxLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaTraslado = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 20,
        ]);

        $tablaTraslado->addRow(260, ['exactHeight' => true, 'height' => 260]);
        $cellH = $tablaTraslado->addCell($tableW, ['bgColor' => $bgAux, 'valign' => 'center']);
        $cellH->addText('TRASLADO o CANALIZACIONES', $fontAuxTitle, $pAuxCenterTight);

        $tablaTraslado->addRow(320, ['exactHeight' => true, 'height' => 320]);
        $cellB = $tablaTraslado->addCell($tableW, ['valign' => 'center']);

        $textoTraslado =
            "HOSPITAL [   ]         DOMICILIO [   ]        CENTRO DE REHABILITACIÓN [   ]        CAVIZ [   ]       OTRO [   ]  especifique: ________";

        $cellB->addText($textoTraslado, $fontAuxSmall, $pAuxLeftTight);

        $section->addTextBreak();

        // ===== TABLA 2 FILAS: INSPECCIONES REALIZADAS =====

        $bgAux = 'EBE1D1';
        $fontAuxTitle = $fontBold7;
        $fontAuxSmall = ['name' => 'Arial', 'size' => 6];

        $pAuxCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];
        $pAuxLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaInspecciones = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 20,
        ]);

        $tablaInspecciones->addRow(260, ['exactHeight' => true, 'height' => 260]);
        $cellH = $tablaInspecciones->addCell($tableW, ['bgColor' => $bgAux, 'valign' => 'center']);
        $cellH->addText('INSPECCIONES REALIZADAS', $fontAuxTitle, $pAuxCenterTight);

        $tablaInspecciones->addRow(320, ['exactHeight' => true, 'height' => 320]);
        $cellB = $tablaInspecciones->addCell($tableW, ['valign' => 'center']);

        $textoInspecciones =
            "PERSONA(S) [   ]                       VEHÍCULO(S) [ X ]                            LUGAR(ES) [ X ]                              NINGUNA [   ]";

        $cellB->addText($textoInspecciones, $fontAuxSmall, $pAuxLeftTight);

        $section->addTextBreak();


        // ===== TABLA 1 FILA: PERSONAS INVOLUCRADAS =====

        $bgAux = 'EBE1D1';

        $tablaPersonasInvol = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 20,
        ]);

        $tablaPersonasInvol->addRow(260, ['exactHeight' => true, 'height' => 260]);
        $cellPI = $tablaPersonasInvol->addCell($tableW, ['bgColor' => $bgAux, 'valign' => 'center']);

        $cellPI->addText(
            'PERSONAS INVOLUCRADAS',
            $fontBold7,
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
                'lineHeight'  => 1.0,
            ]
        );

        $section->addTextBreak(1);

        // ===============================
        // ===== PERSONAS INVOLUCRADAS =====
        // ===============================

        $bgAux = 'EBE1D1';

        $fontLbl7   = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7   = ['name' => 'Arial', 'size' => 7];
        $fontLbl6   = ['name' => 'Arial', 'size' => 6, 'bold' => true];
        $fontRoles6 = ['name' => 'Arial', 'size' => 6];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];
        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];
        
        $conductoresUnicos = collect();

        foreach ($hecho->vehiculos as $v) {
            if (!empty($v->conductores) && $v->conductores->count() > 0) {
                foreach ($v->conductores as $c) {
                    $conductoresUnicos->push($c);
                }
            }
        }

        $conductoresUnicos = $conductoresUnicos->unique('id')->values();

        // ---------- helper para filas ¿ ----------
        $addCellTxt = function($table, $w, $txt, $font, $bg = null, $span = 1) use ($pLeftTight, $bgAux) {
            $style = ['valign' => 'center'];
            if ($bg !== null) $style['bgColor'] = $bg;
            if ($span > 1) $style['gridSpan'] = $span;

            $cell = $table->addCell($w, $style);
            $cell->addText((string)$txt, $font, $pLeftTight);
            return $cell;
        };

        $c1 = (int)round($tableW * 0.23);
        $c2 = (int)round($tableW * 0.34);
        $c3 = (int)round($tableW * 0.12);
        $c4 = (int)round($tableW * 0.095);
        $c5 = (int)round($tableW * 0.12);
        $c6 = $tableW - ($c1 + $c2 + $c3 + $c4 + $c5);

        // ---------- 1 tabla por conductor ----------
        foreach ($conductoresUnicos as $c) {

            $tCon = $section->addTable([
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
                'width'       => $tableW,
                'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
                'borderSize'  => 6,
                'borderColor' => '000000',
                'cellMargin'  => 0,
            ]);

            // ========= FILA 1: NOMBRE (label + valor a todo lo demás) =========
            $tCon->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt($tCon, $c1, 'NOMBRE', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $tableW - $c1, (string)($c->nombre ?? ''), $fontVal7, null, 5);

            // ========= FILA 2: DOMICILIO =========
            $tCon->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt($tCon, $c1, 'DOMICILIO', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $tableW - $c1, (string)($c->domicilio ?? ''), $fontVal7, null, 5);

            // ========= FILA 3: SEXO + ESTADO CIVIL (cuadro parejo) =========
            $tCon->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt($tCon, $c1, 'SEXO', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $c2, (string)($c->sexo ?? ''), $fontVal7, null, 1);
            $addCellTxt($tCon, $c3, 'ESTADO CIVIL', $fontLbl6, $bgAux, 1);
            $addCellTxt($tCon, $c4, '', $fontVal7, null, 1);
            // lo que sobra de la fila (para que siga siendo rectángulo perfecto)
            $addCellTxt($tCon, $c5 + $c6, '', $fontVal7, null, 2);

            // ========= FILA 4: ALIAS / FECHA NAC / LUGAR NAC (6 columnas completas) =========
            $tCon->addRow(300, ['exactHeight' => true, 'height' => 300]);
            $addCellTxt($tCon, $c1, 'ALIAS O APODO', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $c2, '', $fontVal7, null, 1);
            $addCellTxt($tCon, $c3, 'FECHA DE NACIMIENTO', $fontLbl6, $bgAux, 1);
            $addCellTxt($tCon, $c4, '', $fontVal7, null, 1);
            $addCellTxt($tCon, $c5, 'LUGAR DE NACIMIENTO', $fontLbl6, $bgAux, 1);
            $addCellTxt($tCon, $c6, '', $fontVal7, null, 1);

            // ========= FILA 5: NACIONALIDAD / IDIOMA (ESPAÑOL) / OCUPACIÓN =========
            $tCon->addRow(300, ['exactHeight' => true, 'height' => 300]);
            $addCellTxt($tCon, $c1, 'NACIONALIDAD', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $c2, '', $fontVal7, null, 1);
            $addCellTxt($tCon, $c3, 'IDIOMA', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $c4, 'ESPAÑOL', $fontVal7, null, 1);
            $addCellTxt($tCon, $c5, 'OCUPACIÓN', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $c6, (string)($c->ocupacion ?? ''), $fontVal7, null, 1);

            // ========= FILA 6: IDENTIFICACIÓN / FOLIO / ESCOLARIDAD =========
            $tCon->addRow(300, ['exactHeight' => true, 'height' => 300]);
            $addCellTxt($tCon, $c1, 'IDENTIFICACIÓN', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $c2, '', $fontVal7, null, 1);
            $addCellTxt($tCon, $c3, 'FOLIO', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $c4, '', $fontVal7, null, 1);
            $addCellTxt($tCon, $c5, 'ESCOLARIDAD', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $c6, '', $fontVal7, null, 1);

            // ========= FILA 7: TELÉFONOS (label + valor a todo lo demás) =========
            $tCon->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt($tCon, $c1, 'TELÉFONOS', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $tableW - $c1, (string)($c->telefono ?? ''), $fontVal7, null, 5);

            // ========= FILA 8: ROLES (1 sola celda, sin gris) =========
            $tCon->addRow(280, ['exactHeight' => true, 'height' => 280]);
            $roles = "VÍCTIMA [     ]      OFENDIDO [     ]      DENUNCIANTE [     ]      TESTIGO [     ]      IMPUTADO (A) [     ]";
            $cellRoles = $tCon->addCell($tableW, ['gridSpan' => 6, 'valign' => 'center']);
            $cellRoles->addText($roles, $fontRoles6, $pLeftTight);

            // separación mínima entre conductores (sin inflar)
            $section->addTextBreak(1);
        }

        // ===== TABLA 1 FILA: VEHICULOS INVOLUCRADAS =====

        $bgAux = 'EBE1D1';

        $tablaPersonasInvol = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 20,
        ]);

        $tablaPersonasInvol->addRow(260, ['exactHeight' => true, 'height' => 260]);
        $cellPI = $tablaPersonasInvol->addCell($tableW, ['bgColor' => $bgAux, 'valign' => 'center']);

        $cellPI->addText(
            'VEHICULOS INVOLUCRADOS',
            $fontBold7,
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
                'lineHeight'  => 1.0,
            ]
        );

        $section->addTextBreak(1);

        // ===============================
        // ===== VEHÍCULOS INVOLUCRADOS =====
        // ===============================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 9, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 12];
        $fontVal6 = ['name' => 'Arial', 'size' => 12];

        $pCenterTight = [
          'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
          'spaceAfter'  => 0,
          'spaceBefore' => 0,
          'lineHeight'  => 1.0,
        ];

        // ---- helper ----
        $addCellTxt4 = function($table, $w, $txt, $font, $bg = null, $span = 1) use ($pCenterTight) {
            $style = ['valign' => 'center'];
            if ($bg !== null) $style['bgColor'] = $bg;
            if ($span > 1)    $style['gridSpan'] = $span;

            $cell = $table->addCell($w, $style);
            $cell->addText((string)$txt, $font, $pCenterTight);
            return $cell;
        };

        $vC1 = (int)round($tableW * 0.22);
        $vC2 = (int)round($tableW * 0.28);
        $vC3 = (int)round($tableW * 0.22);
        $vC4 = $tableW - ($vC1 + $vC2 + $vC3);

        foreach ($hecho->vehiculos as $i => $v) {

            // tabla por vehículo
            $tVeh = $section->addTable([
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
                'width'       => $tableW,
                'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
                'borderSize'  => 6,
                'borderColor' => '000000',
                'cellMargin'  => 0,
            ]);

            // ===== FILA 1: MARCA / TIPO =====
            $tVeh->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt4($tVeh, $vC1, 'MARCA', $fontLbl7, $bgAux);
            $addCellTxt4($tVeh, $vC2, $v->marca ?? '', $fontVal7, null);
            $addCellTxt4($tVeh, $vC3, 'TIPO', $fontLbl7, $bgAux);
            $addCellTxt4($tVeh, $vC4, $v->tipo ?? '', $fontVal7, null);

            // ===== FILA 2: LINEA / MODELO =====
            $tVeh->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt4($tVeh, $vC1, 'LINEA', $fontLbl7, $bgAux);
            $addCellTxt4($tVeh, $vC2, $v->linea ?? '', $fontVal7, null);
            $addCellTxt4($tVeh, $vC3, 'MODELO', $fontLbl7, $bgAux);
            $addCellTxt4($tVeh, $vC4, $v->modelo ?? '', $fontVal7, null);

            // ===== FILA 3: COLOR / PLACAS =====
            $tVeh->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt4($tVeh, $vC1, 'COLOR', $fontLbl7, $bgAux, null);
            $addCellTxt4($tVeh, $vC2, $v->color ?? '', $fontVal7, null);
            $addCellTxt4($tVeh, $vC3, 'PLACAS', $fontLbl7, $bgAux);
            $addCellTxt4($tVeh, $vC4, $v->placas ?? '', $fontVal7, null);

            // ===== FILA 4: NO. SERIE / NO. MOTOR =====
            $tVeh->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt4($tVeh, $vC1, 'NO. SERIE', $fontLbl7, $bgAux);
            $addCellTxt4($tVeh, $vC2, $v->serie ?? '', $fontVal7, null);
            $addCellTxt4($tVeh, $vC3, 'NO. MOTOR', $fontLbl7, $bgAux);
            $addCellTxt4($tVeh, $vC4, '', $fontVal7, null);

            // ===== FILA 5: NO SERIE ALTERADO / NO. MOTOR ALTERADO =====
            $tVeh->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt4($tVeh, $vC1, 'NO SERIE ALTERADO', $fontLbl7, $bgAux);
            $addCellTxt4($tVeh, $vC2, '', $fontVal7, null);
            $addCellTxt4($tVeh, $vC3, 'NO. MOTOR ALTERADO', $fontLbl7, $bgAux);
            $addCellTxt4($tVeh, $vC4, '', $fontVal7, null);

            // ===== FILA 6: NO. ECONOMICO / CAPACIDAD =====
            $tVeh->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt4($tVeh, $vC1, 'NO. ECONOMICO', $fontLbl7, $bgAux);
            $addCellTxt4($tVeh, $vC2, '', $fontVal7, null);
            $addCellTxt4($tVeh, $vC3, 'CAPACIDAD', $fontLbl7, $bgAux);
            $addCellTxt4($tVeh, $vC4, $v->capacidad_personas ?? '', $fontVal7, null);

            // ===== FILA 7: PROCEDENCIA / REGISTRO =====
            $tVeh->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt4($tVeh, $vC1, 'PROCEDENCIA', $fontLbl7, $bgAux);
            $addCellTxt4($tVeh, $vC2, '', $fontVal7, null);
            $addCellTxt4($tVeh, $vC3, 'REGISTRO', $fontLbl7, $bgAux);
            $addCellTxt4($tVeh, $vC4, '', $fontVal7, null);

            // ===== FILA 8: TIPO DE SERVICIO (2 columnas) =====
            $tVeh->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt4($tVeh, $vC1, 'TIPO DE SERVICIO', $fontLbl7, $bgAux);
            $addCellTxt4($tVeh, $tableW - $vC1, $v->tipo_servicio ?? '', $fontVal7, null, 3);

            // ===== FILA 9: OBSERVACIONES (solo palabra en gris, toda la fila) =====
            $tVeh->addRow(240, ['exactHeight' => true, 'height' => 240]);
            $addCellTxt4($tVeh, $tableW, 'OBSERVACIONES', $fontLbl7, $bgAux, 4);

            // ===== FILA 10: partes_danadas (toda la fila, sin gris) =====
            $tVeh->addRow(320, ['exactHeight' => true, 'height' => 320]);
            $partes = (string)($v->partes_danadas ?? '');
            $addCellTxt4($tVeh, $tableW, $partes, $fontVal6, null, 4);

            // separación mínima entre vehículos
            $section->addTextBreak(1);
        }

        // =========================================
        // ===== TABLA FIRMAS (como tu 2da imagen)
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $fC1 = (int)round($tableW * 0.20);
        $fC2 = (int)round($tableW * 0.26);
        $fC3 = (int)round($tableW * 0.14);
        $fC4 = $tableW - ($fC1 + $fC2 + $fC3);

        $tFirm = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 0,
        ]);

        $addCell = function($table, $w, $txt, $font, $p, $style = []) {
            $base = ['valign' => 'center'];
            $cell = $table->addCell($w, array_merge($base, $style));
            if ($txt !== null) {
                $cell->addText((string)$txt, $font, $p);
            }
            return $cell;
        };

        $peritoTxt = (string)($hecho->perito ?? '');
        $unidadTxt = (string)($hecho->unidad ?? '');

        // -------------------- FILA 1 --------------------
        $tFirm->addRow(320, ['exactHeight' => true, 'height' => 320]);

        $addCell($tFirm, ($fC1 + $fC2 + $fC3), $peritoTxt, $fontVal7, $pCenterTight, [
            'gridSpan' => 3
        ]);

        $addCell($tFirm, $fC4, '', $fontVal7, $pCenterTight, [
            'vMerge' => 'restart',
            'valign' => 'top'
        ]);

        // -------------------- FILA 2 --------------------
        $tFirm->addRow(320, ['exactHeight' => true, 'height' => 320]);

        $addCell($tFirm, ($fC1 + $fC2 + $fC3), 'NOMBRE DEL AGENTE INVESTIGADOR', $fontLbl7, $pCenterTight, [
            'bgColor'  => $bgAux,
            'gridSpan' => 3
        ]);

        $addCell($tFirm, $fC4, '', $fontVal7, $pCenterTight, [
            'vMerge' => 'continue',
            'valign' => 'top'
        ]);

        // -------------------- FILA 3 --------------------
        $tFirm->addRow(520, ['exactHeight' => true, 'height' => 520]);
        $addCell($tFirm, $fC1, '', $fontVal7, $pCenterTight);
        $addCell($tFirm, $fC2, '', $fontVal7, $pCenterTight);
        $addCell($tFirm, $fC3, $unidadTxt, $fontVal7, $pCenterTight);

        $addCell($tFirm, $fC4, '', $fontVal7, $pCenterTight, [
            'vMerge' => 'continue',
            'valign' => 'top'
        ]);

        // -------------------- FILA 4 (encabezados grises) --------------------
        $tFirm->addRow(300, ['exactHeight' => true, 'height' => 300]);

        $addCell($tFirm, $fC1, 'CARGO', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
        $addCell($tFirm, $fC2, 'NÚMERO DE GAFETE', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
        $addCell($tFirm, $fC3, 'UNIDAD', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
        $addCell($tFirm, $fC4, 'FIRMA', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);

        $section->addPageBreak();





        // ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
        // ┃      REGISTRO E INSP. LUGAR DEL HECHO     ┃
        // ┃      GENERACIÓN DE DOCUMENTO OFICIAL      ┃
        // ┃   CAMBIOS AQUÍ ROMPEN EL FORMATO LEGAL    ┃
        // ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛



        // ===== TÍTULO DEL DOCUMENTO (centrado) =====
        $section->addText(
            'PRESERVACIÓN DEL LUGAR DE LOS HECHOS Y/O DEL HALLAZGO',
            ['name' => 'Arial', 'size' => 14, 'bold' => true],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
                'lineHeight'  => 1.0,
            ]
        );

        $section->addTextBreak(1);







        // Fecha (SOLO fecha de la columna hechos.fecha)
        $fechaLlegada = !empty($hecho->fecha)
            ? \Carbon\Carbon::parse($hecho->fecha)->format('d/m/Y')
            : '';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        // 4 columnas fijas (mismo ancho total que vienes usando: $tableW)
        $uC1 = (int)round($tableW * 0.30); // Unidad Administrativa
        $uC2 = (int)round($tableW * 0.22); // Entidad Federativa
        $uC3 = (int)round($tableW * 0.20); // Municipio
        $uC4 = $tableW - ($uC1 + $uC2 + $uC3); // Fecha y hora de llegada

        $tablaUA = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 0,
        ]);

        // ---------- FILA 1 (ENCABEZADOS) ----------
        $tablaUA->addRow(300, ['exactHeight' => true, 'height' => 300]);
        $tablaUA->addCell($uC1, ['valign' => 'center'])->addText('Unidad Administrativa', $fontLbl7, $pCenterTight);
        $tablaUA->addCell($uC2, ['valign' => 'center'])->addText('Entidad Federativa', $fontLbl7, $pCenterTight);
        $tablaUA->addCell($uC3, ['valign' => 'center'])->addText('Municipio', $fontLbl7, $pCenterTight);
        $tablaUA->addCell($uC4, ['valign' => 'center'])->addText('Fecha y hora de llegada.', $fontLbl7, $pCenterTight);

        // ---------- FILA 2 (VALORES) ----------
        $tablaUA->addRow(300, ['exactHeight' => true, 'height' => 300]);
        $tablaUA->addCell($uC1, ['valign' => 'center'])->addText('PERITOS', $fontVal7, $pCenterTight);
        $tablaUA->addCell($uC2, ['valign' => 'center'])->addText('MICHOACAN', $fontVal7, $pCenterTight);
        $tablaUA->addCell($uC3, ['valign' => 'center'])->addText('MORELIA', $fontVal7, $pCenterTight);
        $tablaUA->addCell($uC4, ['valign' => 'center'])->addText($fechaLlegada, $fontVal7, $pCenterTight);

        $section->addTextBreak(1);








                // ===== 1. LUGAR DE LOS HECHOS Y/O DEL HALLAZGO =====
        $section->addText(
            '1.	LUGAR DE LOS HECHOS Y/O DEL HALLAZGO',
            ['name' => 'Arial', 'size' => 12, 'bold' => true],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
                'lineHeight'  => 1.0,
            ]
        );

        $section->addTextBreak(1);





        // =========================================
        // ===== TABLA 1 COLUMNA x 4 FILAS =====
        // Lugar / Código Postal / Entre que calles / Observaciones
        // =========================================

        $lugarTxt = '';
        $calleTxt   = trim((string)($hecho->calle ?? ''));
        $coloniaTxt = trim((string)($hecho->colonia ?? ''));

        if ($calleTxt !== '' && $coloniaTxt !== '') {
            $lugarTxt = $calleTxt . ', col. ' . $coloniaTxt;
        } elseif ($calleTxt !== '') {
            $lugarTxt = $calleTxt;
        } elseif ($coloniaTxt !== '') {
            $lugarTxt = 'col. ' . $coloniaTxt;
        }

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 12];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaLugar = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
        ]);

        // ---------- FILA 1 ----------
        $tablaLugar->addRow(280, ['exactHeight' => true, 'height' => 280]);
        $cell = $tablaLugar->addCell($tableW, ['valign' => 'center']);
        $run  = $cell->addTextRun($pLeftTight);
        $run->addText('Lugar: ', $fontLbl7);
        $run->addText($lugarTxt, $fontVal7);

        // ---------- FILA 2 ----------
        $tablaLugar->addRow(280, ['exactHeight' => true, 'height' => 280]);
        $cell = $tablaLugar->addCell($tableW, ['valign' => 'center']);
        $run  = $cell->addTextRun($pLeftTight);
        $run->addText('Código Postal: ', $fontLbl7);
        // (sin valor, queda en blanco)

        // ---------- FILA 3 ----------
        $tablaLugar->addRow(280, ['exactHeight' => true, 'height' => 280]);
        $cell = $tablaLugar->addCell($tableW, ['valign' => 'center']);
        $run  = $cell->addTextRun($pLeftTight);
        $run->addText('Entre que calles: ', $fontLbl7);
        $run->addText((string)($hecho->entre_calles ?? ''), $fontVal7);

        // ---------- FILA 4 ----------
        $tablaLugar->addRow(300, ['exactHeight' => true, 'height' => 300]);
        $cell = $tablaLugar->addCell($tableW, ['valign' => 'center']);
        $run  = $cell->addTextRun($pLeftTight);
        $run->addText('Observaciones: ', $fontLbl7);
        // (contenido libre)




        // =========================================
        // ===== TABLA 1 COLUMNA x 1 FILA (ESPACIO PARA TEXTO)
        // =========================================

        $tablaEspacio = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
        ]);

        // Fila única con altura suficiente
        $tablaEspacio->addRow(900, ['exactHeight' => false]);
        $cell = $tablaEspacio->addCell($tableW, ['valign' => 'top']);

        // Simulación de saltos de línea (≈ 8)
        $run = $cell->addTextRun([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ]);

        for ($i = 0; $i < 12; $i++) {
            $run->addTextBreak();
        }

        $section->addTextBreak(1);







                        // ===== 2. PROTECCIÓN DEL LUGAR DE LOS HECHOS Y/O DEL HALLAZGO =====
        $section->addText(
            '2.	PROTECCIÓN DEL LUGAR DE LOS HECHOS Y/O DEL HALLAZGO',
            ['name' => 'Arial', 'size' => 12, 'bold' => true],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
                'lineHeight'  => 1.0,
            ]
        );

        $section->addTextBreak(1);






        // =========================================
        // ===== TABLA 1 COLUMNA x 2 FILAS (SIN FONDO)
        // Acordonamiento / Observaciones
        // =========================================

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaAcord = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
        ]);

        // ---------- FILA 1 ----------
        $tablaAcord->addRow(300, ['exactHeight' => true, 'height' => 300]);
        $cell = $tablaAcord->addCell($tableW, ['valign' => 'center']);

        $run = $cell->addTextRun($pLeftTight);
        $run->addText('Acordonamiento    ', $fontLbl7);
        $run->addText('SÍ   [     ]', $fontVal7);
        $run->addText('          ');
        $run->addText('NO    [     ]', $fontVal7);

        // ---------- FILA 2 ----------
        $tablaAcord->addRow(300, ['exactHeight' => true, 'height' => 300]);
        $cell = $tablaAcord->addCell($tableW, ['valign' => 'center']);

        $run = $cell->addTextRun($pLeftTight);
        $run->addText('Observaciones: ', $fontLbl7);

        $section->addTextBreak(1);



        
                        // ===== 3. OBSERVACIÓN DEL LUGAR DE LOS HECHOS Y/O DEL HALLAZGO =====
        $section->addText(
            '3.	OBSERVACIÓN DEL LUGAR DE LOS HECHOS Y/O DEL HALLAZGO',
            ['name' => 'Arial', 'size' => 12, 'bold' => true],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
                'lineHeight'  => 1.0,
            ]
        );

        $section->addTextBreak(1);



        // =========================================
        // ===== TABLA 1 COLUMNA x 4 FILAS (SIN FONDO)
        // Fijación fotográfica / Observaciones / Alteración del lugar / Observaciones
        // =========================================

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaFix = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
        ]);

        // ---------- FILA 1 ----------
        $tablaFix->addRow(300, ['exactHeight' => true, 'height' => 300]);
        $cell = $tablaFix->addCell($tableW, ['valign' => 'center']);
        $run  = $cell->addTextRun($pLeftTight);
        $run->addText('Fijación fotográfica y/o videograbación    ', $fontLbl7);
        $run->addText('SÍ      [     ]', $fontVal7);
        $run->addText('             ');
        $run->addText('NO      [     ]', $fontVal7);

        // ---------- FILA 2 ----------
        $tablaFix->addRow(300, ['exactHeight' => true, 'height' => 300]);
        $cell = $tablaFix->addCell($tableW, ['valign' => 'center']);
        $run  = $cell->addTextRun($pLeftTight);
        $run->addText('Observaciones: ', $fontLbl7);

        // ---------- FILA 3 ----------
        $tablaFix->addRow(300, ['exactHeight' => true, 'height' => 300]);
        $cell = $tablaFix->addCell($tableW, ['valign' => 'center']);
        $run  = $cell->addTextRun($pLeftTight);
        $run->addText('Alteración del lugar    ', $fontLbl7);
        $run->addText('SÍ      [     ]', $fontVal7);
        $run->addText('             ');
        $run->addText('NO      [     ]', $fontVal7);

        // ---------- FILA 4 ----------
        $tablaFix->addRow(300, ['exactHeight' => true, 'height' => 300]);
        $cell = $tablaFix->addCell($tableW, ['valign' => 'center']);
        $run  = $cell->addTextRun($pLeftTight);
        $run->addText('Observaciones: ', $fontLbl7);

        $section->addTextBreak(1);



                               // ===== 4. INFORMACIÓN OBTENIDA SOBRE EL LUGAR DE LOS HECHOS =====
        $section->addText(
            '4.	INFORMACIÓN OBTENIDA SOBRE EL LUGAR DE LOS HECHOS',
            ['name' => 'Arial', 'size' => 12, 'bold' => true],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
                'lineHeight'  => 1.0,
            ]
        );

        $section->addTextBreak(1);


        // =========================================
        // ===== TABLA 1 COLUMNA x 1 FILA (DESCRIPCIÓN)
        // =========================================

        $fontTxt7 = ['name' => 'Arial', 'size' => 7];

        $pJustifyTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaDesc = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
        ]);

        $tablaDesc->addRow(520, ['exactHeight' => false]);
        $cell = $tablaDesc->addCell($tableW, ['valign' => 'top']);

        $cell->addText(
            'Corresponde a la Carr. Tiripetio - Acuitzio, la cual se encuentra construida por una superficie de asfalto, en buen estado de conservación, tramo a nivel, cuenta con balizamientos, tiene capacidad para dos carriles de circulación, uno para cada sentido, orientados de norponiente a suroriente y viceversa, divididos por una línea longitudinal color amarillo, divisora de carriles, a la hora de la intervención la superficie de rodamiento se encontraba limpia y seca.',
            $fontTxt7,
            $pJustifyTight
        );

        $section->addTextBreak(1);






                                // ===== 5. DETENIDO (S) =====
        $section->addText(
            '5.	DETENIDO (S)',
            ['name' => 'Arial', 'size' => 12, 'bold' => true],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
                'lineHeight'  => 1.0,
            ]
        );



        // =========================================
        // ===== TABLA (3 FILAS) =====
        // Fila 1: SI / NUMERO / NO
        // Fila 2 (GRIS): Nombre(s) / Sexo / Edad
        // Fila 3: fila en blanco
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaDet = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
        ]);

        // 3 columnas (Nombre / Sexo / Edad)
        $dC1 = (int)round($tableW * 0.70);
        $dC2 = (int)round($tableW * 0.15);
        $dC3 = $tableW - ($dC1 + $dC2);

        // ---------- FILA 1 (texto en una sola celda) ----------
        $tablaDet->addRow(280, ['exactHeight' => true, 'height' => 280]);
        $cell = $tablaDet->addCell($tableW, ['gridSpan' => 3, 'valign' => 'center']);

        $run = $cell->addTextRun($pLeftTight);
        $run->addText('SI', $fontVal7);
        $run->addText('   [    ]    ', $fontVal7);
        $run->addText('NUMERO', $fontVal7);
        $run->addText('   [  0  ]    ', $fontVal7);
        $run->addText('NO', $fontVal7);
        $run->addText('   [  X  ]', $fontVal7);

        // ---------- FILA 2 (GRIS: encabezados) ----------
        $tablaDet->addRow(280, ['exactHeight' => true, 'height' => 280]);
        $tablaDet->addCell($dC1, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Nombre (s)', $fontLbl7, $pCenterTight);
        $tablaDet->addCell($dC2, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Sexo', $fontLbl7, $pCenterTight);
        $tablaDet->addCell($dC3, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Edad', $fontLbl7, $pCenterTight);

        // ---------- FILA 3 (en blanco) ----------
        $tablaDet->addRow(360, ['exactHeight' => true, 'height' => 360]);
        $tablaDet->addCell($dC1, ['valign' => 'center'])->addText('', $fontVal7, $pLeftTight);
        $tablaDet->addCell($dC2, ['valign' => 'center'])->addText('', $fontVal7, $pLeftTight);
        $tablaDet->addCell($dC3, ['valign' => 'center'])->addText('', $fontVal7, $pLeftTight);

        $section->addTextBreak(1);



                                        // ===== 6. VICTIMA (S) =====
        $section->addText(
            '6.	VICTIMA (S)',
            ['name' => 'Arial', 'size' => 12, 'bold' => true],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
                'lineHeight'  => 1.0,
            ]
        );



                // =========================================
        // ===== TABLA (3 FILAS) =====
        // Fila 1: SI / NUMERO / NO
        // Fila 2 (GRIS): Nombre(s) / Sexo / Edad
        // Fila 3: fila en blanco
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaDet = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
        ]);

        // 3 columnas (Nombre / Sexo / Edad)
        $dC1 = (int)round($tableW * 0.70);
        $dC2 = (int)round($tableW * 0.15);
        $dC3 = $tableW - ($dC1 + $dC2);

        // ---------- FILA 1 (texto en una sola celda) ----------
        $tablaDet->addRow(280, ['exactHeight' => true, 'height' => 280]);
        $cell = $tablaDet->addCell($tableW, ['gridSpan' => 3, 'valign' => 'center']);

        $run = $cell->addTextRun($pLeftTight);
        $run->addText('SI', $fontVal7);
        $run->addText('   [    ]    ', $fontVal7);
        $run->addText('NUMERO', $fontVal7);
        $run->addText('   [  0  ]    ', $fontVal7);
        $run->addText('NO', $fontVal7);
        $run->addText('   [  X  ]', $fontVal7);

        // ---------- FILA 2 (GRIS: encabezados) ----------
        $tablaDet->addRow(280, ['exactHeight' => true, 'height' => 280]);
        $tablaDet->addCell($dC1, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Nombre (s)', $fontLbl7, $pCenterTight);
        $tablaDet->addCell($dC2, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Sexo', $fontLbl7, $pCenterTight);
        $tablaDet->addCell($dC3, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Edad', $fontLbl7, $pCenterTight);

        // ---------- FILA 3 (en blanco) ----------
        $tablaDet->addRow(360, ['exactHeight' => true, 'height' => 360]);
        $tablaDet->addCell($dC1, ['valign' => 'center'])->addText('', $fontVal7, $pLeftTight);
        $tablaDet->addCell($dC2, ['valign' => 'center'])->addText('', $fontVal7, $pLeftTight);
        $tablaDet->addCell($dC3, ['valign' => 'center'])->addText('', $fontVal7, $pLeftTight);

        $section->addTextBreak(1);







                       // ===== 7. VEHÍCULOS IMPLICADOS =====
        $section->addText(
            '7.	VEHÍCULOS IMPLICADOS',
            ['name' => 'Arial', 'size' => 12, 'bold' => true],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
                'lineHeight'  => 1.0,
            ]
        );


        // =========================================
        // ===== TABLA: FIJACIÓN + LISTADO DE VEHÍCULOS (DINÁMICA)
        // Fila 1: (1 sola celda) "Fijación..." SI/NO
        // Fila 2 (GRIS): MARCA | TIPO | COLOR | MODELO | PLACAS
        // Filas 3..N: 1 por cada vehículo del hecho (si no hay, no se agrega ninguna)
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 12];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        // 5 columnas fijas
        $v5C1 = (int)round($tableW * 0.22); // MARCA
        $v5C2 = (int)round($tableW * 0.20); // TIPO
        $v5C3 = (int)round($tableW * 0.18); // COLOR
        $v5C4 = (int)round($tableW * 0.18); // MODELO
        $v5C5 = $tableW - ($v5C1 + $v5C2 + $v5C3 + $v5C4); // PLACAS

        $tablaFixVeh = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
        ]);

        // ---------- FILA 1 (1 sola columna / sin fondo) ----------
        $tablaFixVeh->addRow(300, ['exactHeight' => true, 'height' => 300]);
        $cell = $tablaFixVeh->addCell($tableW, ['gridSpan' => 5, 'valign' => 'center']);

        $run = $cell->addTextRun($pLeftTight);
        $run->addText('Fijación fotográfica y/o videograbación', $fontVal7);
        $run->addText('                       ', $fontVal7);
        $run->addText('SI', $fontVal7);
        $run->addText('  [  X  ]', $fontVal7);
        $run->addText('    ', $fontVal7);
        $run->addText('NO', $fontVal7);
        $run->addText('  [  X  ]', $fontVal7);

        // ---------- FILA 2 (GRIS: encabezados) ----------
        $tablaFixVeh->addRow(280, ['exactHeight' => true, 'height' => 280]);
        $tablaFixVeh->addCell($v5C1, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('MARCA',  $fontLbl7, $pCenterTight);
        $tablaFixVeh->addCell($v5C2, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('TIPO',   $fontLbl7, $pCenterTight);
        $tablaFixVeh->addCell($v5C3, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('COLOR',  $fontLbl7, $pCenterTight);
        $tablaFixVeh->addCell($v5C4, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('MODELO', $fontLbl7, $pCenterTight);
        $tablaFixVeh->addCell($v5C5, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('PLACAS', $fontLbl7, $pCenterTight);

        // ---------- FILAS DINÁMICAS (1 por vehículo) ----------
        if (isset($hecho->vehiculos) && $hecho->vehiculos->count() > 0) {

            foreach ($hecho->vehiculos as $v) {

                $marca  = (string)($v->marca ?? '');
                $tipo   = (string)($v->tipo ?? '');
                $color  = (string)($v->color ?? '');
                $modelo = (string)($v->modelo ?? '');
                $placas = (string)($v->placas ?? '');

                $tablaFixVeh->addRow(280, ['exactHeight' => true, 'height' => 280]);
                $tablaFixVeh->addCell($v5C1, ['valign' => 'center'])->addText($marca,  $fontVal7, $pCenterTight);
                $tablaFixVeh->addCell($v5C2, ['valign' => 'center'])->addText($tipo,   $fontVal7, $pCenterTight);
                $tablaFixVeh->addCell($v5C3, ['valign' => 'center'])->addText($color,  $fontVal7, $pCenterTight);
                $tablaFixVeh->addCell($v5C4, ['valign' => 'center'])->addText($modelo, $fontVal7, $pCenterTight);
                $tablaFixVeh->addCell($v5C5, ['valign' => 'center'])->addText($placas, $fontVal7, $pCenterTight);
            }
        }

        $section->addTextBreak(1);




             // ===== 8.	TESTIGOS (S): =====
        $section->addText(
            '8.	TESTIGOS (S):',
            ['name' => 'Arial', 'size' => 12, 'bold' => true],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
                'lineHeight'  => 1.0,
            ]
        );


                        // =========================================
        // ===== TABLA (3 FILAS) =====
        // Fila 1: SI / NUMERO / NO
        // Fila 2 (GRIS): Nombre(s) / Sexo / Edad
        // Fila 3: fila en blanco
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaDet = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
        ]);

        // 3 columnas (Nombre / Sexo / Edad)
        $dC1 = (int)round($tableW * 0.70);
        $dC2 = (int)round($tableW * 0.15);
        $dC3 = $tableW - ($dC1 + $dC2);

        // ---------- FILA 1 (texto en una sola celda) ----------
        $tablaDet->addRow(280, ['exactHeight' => true, 'height' => 280]);
        $cell = $tablaDet->addCell($tableW, ['gridSpan' => 3, 'valign' => 'center']);

        $run = $cell->addTextRun($pLeftTight);
        $run->addText('SI', $fontVal7);
        $run->addText('   [    ]    ', $fontVal7);
        $run->addText('NUMERO', $fontVal7);
        $run->addText('   [  0  ]    ', $fontVal7);
        $run->addText('NO', $fontVal7);
        $run->addText('   [  X  ]', $fontVal7);

        // ---------- FILA 2 (GRIS: encabezados) ----------
        $tablaDet->addRow(280, ['exactHeight' => true, 'height' => 280]);
        $tablaDet->addCell($dC1, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Nombre (s)', $fontLbl7, $pCenterTight);
        $tablaDet->addCell($dC2, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Sexo', $fontLbl7, $pCenterTight);
        $tablaDet->addCell($dC3, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Edad', $fontLbl7, $pCenterTight);

        // ---------- FILA 3 (en blanco) ----------
        $tablaDet->addRow(360, ['exactHeight' => true, 'height' => 360]);
        $tablaDet->addCell($dC1, ['valign' => 'center'])->addText('', $fontVal7, $pLeftTight);
        $tablaDet->addCell($dC2, ['valign' => 'center'])->addText('', $fontVal7, $pLeftTight);
        $tablaDet->addCell($dC3, ['valign' => 'center'])->addText('', $fontVal7, $pLeftTight);

        $section->addTextBreak(1);



        // ===== 9. OBSERVACIONES GENERALES: =====
        $section->addText(
            '9. OBSERVACIONES GENERALES:',
            ['name' => 'Arial', 'size' => 12, 'bold' => true],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
                'lineHeight'  => 1.0,
            ]
        );

        // =========================================
        // ===== TABLA 1 COLUMNA x 1 FILA (CON TEXTO)
        // (en vez de estar en blanco, mete el texto con grúa + dirección en negritas)
        // =========================================

        // ---- En tu DB: vehiculos.grua guarda NOMBRE (ej. "DANNYS") ----
        $gruaNombres = isset($hecho->vehiculos)
            ? $hecho->vehiculos
                ->pluck('grua')
                ->filter(fn($x) => !is_null($x) && trim((string)$x) !== '' && trim((string)$x) !== '0')
                ->map(fn($x) => strtoupper(trim((string)$x)))
                ->unique()
                ->values()
            : collect();

        $gruaNombre    = '________________';
        $gruaDireccion = '________________';

        if ($gruaNombres->count() > 0) {

            $gruas = \App\Models\Grua::whereIn(\DB::raw('UPPER(nombre)'), $gruaNombres->toArray())->get();

            if ($gruas->count() > 0) {

                $gruaNombre = $gruas->pluck('nombre')->filter()->implode(' y ');

                $dir = $gruas->pluck('direccion')->filter()->first();
                if (!$dir) $dir = $gruas->pluck('ubicacion_corralon')->filter()->first();

                $gruaDireccion = $dir ? $dir : '________________';

            } else {

                $gruaNombre    = $gruaNombres->implode(' y ');
                $gruaDireccion = '________________';
            }
        }

        $tablaObs = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
        ]);

        $tablaObs->addRow(900, ['exactHeight' => false]);
        $cell = $tablaObs->addCell($tableW, ['valign' => 'top']);

        // Texto dentro de la celda (justificado) con variables en negritas
        $run = $cell->addTextRun([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ]);

        $run->addText('Para el traslado de ambos vehículos fui auxiliado por la grúa particular ');
        $run->addText($gruaNombre, ['name' => 'Arial', 'size' => 12, 'bold' => true]);
        $run->addText(', quien los resguardó en sus propias instalaciones, garaje de apoyo a esta dependencia, ubicado en ');
        $run->addText($gruaDireccion, ['name' => 'Arial', 'size' => 7, 'bold' => true]);
        $run->addText('.');

        $section->addTextBreak(1);






        // ===== 10.	SERVIDORES PÚBLICOS QUE INTERVINIERON EN LA PRESERVACIÓN DEL LUGAR DE LOS HECHOS Y/O DEL HALLAZGO =====
        $section->addText(
            '10 SERVIDORES PÚBLICOS QUE INTERVINIERON EN LA PRESERVACIÓN DEL LUGAR DE LOS HECHOS Y/O DEL HALLAZGO',
            ['name' => 'Arial', 'size' => 12, 'bold' => true],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
                'lineHeight'  => 1.0,
            ]
        );


        // =========================================
        // ===== TABLA 3 COLUMNAS x 2 FILAS =====
        // Fila 1 (GRIS): Nombre(s) | Cargo | Firma
        // Fila 2: en blanco
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaFirmas = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
        ]);

        // Anchos de columnas
        $fC1 = (int)round($tableW * 0.40); // Nombre(s)
        $fC2 = (int)round($tableW * 0.25); // Cargo
        $fC3 = $tableW - ($fC1 + $fC2);    // Firma

        // ---------- FILA 1 (GRIS) ----------
        $tablaFirmas->addRow(280, ['exactHeight' => true, 'height' => 280]);
        $tablaFirmas->addCell($fC1, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Nombre (s)', $fontLbl7, $pCenterTight);
        $tablaFirmas->addCell($fC2, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Cargo',      $fontLbl7, $pCenterTight);
        $tablaFirmas->addCell($fC3, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Firma',      $fontLbl7, $pCenterTight);

        // ---------- FILA 2 (EN BLANCO) ----------
        $tablaFirmas->addRow(360, ['exactHeight' => true, 'height' => 360]);
        $tablaFirmas->addCell($fC1, ['valign' => 'center'])->addText('', $fontVal7, $pCenterTight);
        $tablaFirmas->addCell($fC2, ['valign' => 'center'])->addText('', $fontVal7, $pCenterTight);
        $tablaFirmas->addCell($fC3, ['valign' => 'center'])->addText('', $fontVal7, $pCenterTight);

        $section->addTextBreak(1);






                        // ===== 11.	SERVIDORES PÚBLICOS QUE ENTREGAN LA PRESERVACIÓN DEL LUGAR DE LOS HECHOS Y/O DEL HALLAZGO =====
        $section->addText(
            '11 SERVIDORES PÚBLICOS QUE ENTREGAN LA PRESERVACIÓN DEL LUGAR DE LOS HECHOS Y/O DEL HALLAZGO',
            ['name' => 'Arial', 'size' => 12, 'bold' => true],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
                'lineHeight'  => 1.0,
            ]
        );



        // =========================================
        // ===== TABLA 3 COLUMNAS x 2 FILAS (CASI IGUAL)
        // Fila 1 (GRIS): Nombre(s) | Cargo | Firma
        // Fila 2: perito (col1) | PERITO (col2) | blanco (col3, SIN FONDO)
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaPerito = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
        ]);

        // Anchos de columnas
        $pC1 = (int)round($tableW * 0.40); // Nombre(s)
        $pC2 = (int)round($tableW * 0.25); // Cargo
        $pC3 = $tableW - ($pC1 + $pC2);    // Firma

        $peritoTxt = (string)($hecho->perito ?? '');

        // ---------- FILA 1 (GRIS) ----------
        $tablaPerito->addRow(280, ['exactHeight' => true, 'height' => 280]);
        $tablaPerito->addCell($pC1, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Nombre (s)', $fontLbl7, $pCenterTight);
        $tablaPerito->addCell($pC2, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Cargo',      $fontLbl7, $pCenterTight);
        $tablaPerito->addCell($pC3, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Firma',      $fontLbl7, $pCenterTight);

        // ---------- FILA 2 (PERITO / SIN FONDO EN FIRMA) ----------
        $tablaPerito->addRow(360, ['exactHeight' => true, 'height' => 360]);
        $tablaPerito->addCell($pC1, ['valign' => 'center'])->addText($peritoTxt, $fontVal7, $pCenterTight);
        $tablaPerito->addCell($pC2, ['valign' => 'center'])->addText('PERITO',   $fontVal7, $pCenterTight);
        $tablaPerito->addCell($pC3, ['valign' => 'center'])->addText('',         $fontVal7, $pCenterTight);

        $section->addTextBreak(1);












                // ===== 12.	SSERVIDORES PÚBLICOS QUE RECIBEN LA PRESERVACIÓN DEL LUGAR DE LOS HECHOS Y/O DEL HALLAZGO =====
        $section->addText(
            '12 SERVIDORES PÚBLICOS QUE RECIBEN LA PRESERVACIÓN DEL LUGAR DE LOS HECHOS Y/O DEL HALLAZGO',
            ['name' => 'Arial', 'size' => 12, 'bold' => true],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
                'lineHeight'  => 1.0,
            ]
        );


        // =========================================
        // ===== TABLA 3 COLUMNAS x 2 FILAS =====
        // Fila 1 (GRIS): Nombre(s) | Cargo | Firma
        // Fila 2: en blanco
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaFirmas = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
        ]);

        // Anchos de columnas
        $fC1 = (int)round($tableW * 0.40); // Nombre(s)
        $fC2 = (int)round($tableW * 0.25); // Cargo
        $fC3 = $tableW - ($fC1 + $fC2);    // Firma

        // ---------- FILA 1 (GRIS) ----------
        $tablaFirmas->addRow(280, ['exactHeight' => true, 'height' => 280]);
        $tablaFirmas->addCell($fC1, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Nombre (s)', $fontLbl7, $pCenterTight);
        $tablaFirmas->addCell($fC2, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Cargo',      $fontLbl7, $pCenterTight);
        $tablaFirmas->addCell($fC3, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('Firma',      $fontLbl7, $pCenterTight);

        // ---------- FILA 2 (EN BLANCO) ----------
        $tablaFirmas->addRow(360, ['exactHeight' => true, 'height' => 360]);
        $tablaFirmas->addCell($fC1, ['valign' => 'center'])->addText('', $fontVal7, $pCenterTight);
        $tablaFirmas->addCell($fC2, ['valign' => 'center'])->addText('', $fontVal7, $pCenterTight);
        $tablaFirmas->addCell($fC3, ['valign' => 'center'])->addText('', $fontVal7, $pCenterTight);

        $section->addTextBreak(1);


        $section->addPageBreak();



        // ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
        // ┃ ACTA DE RGSTO. E INSP. DEL LUGAR DEL HECHO┃
        // ┃      GENERACIÓN DE DOCUMENTO OFICIAL      ┃
        // ┃   CAMBIOS AQUÍ ROMPEN EL FORMATO LEGAL    ┃
        // ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛


        // ===== TÍTULO DEL DOCUMENTO (centrado en dos líneas, como formato oficial) =====
        $runTitulo = $section->addTextRun([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ]);

        $runTitulo->addText(
            'ACTA DE REGISTRO E INSPECCIÓN',
            ['name' => 'Arial', 'size' => 14, 'bold' => true]
        );

        $runTitulo->addTextBreak();

        $runTitulo->addText(
            'DEL LUGAR DEL HECHO',
            ['name' => 'Arial', 'size' => 14, 'bold' => true]
        );

        $section->addTextBreak(1);


        // =========================================
        // ===== TABLA 4 COLUMNAS x 3 FILAS (COL 3 y 4 MÁS ANGOSTAS) =====
        // LUGAR / FECHA
        // POLICÍA INVESTIGADOR / HORA
        // UNIDAD ESPECIAL / N.U.C
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 12];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        // ----- valores dinámicos -----
        $calleTxt   = trim((string)($hecho->calle ?? ''));
        $coloniaTxt = trim((string)($hecho->colonia ?? ''));

        $lugarTxt = '';
        if ($calleTxt !== '' && $coloniaTxt !== '') {
            $lugarTxt = $calleTxt . ', col. ' . $coloniaTxt;
        } elseif ($calleTxt !== '') {
            $lugarTxt = $calleTxt;
        } elseif ($coloniaTxt !== '') {
            $lugarTxt = 'col. ' . $coloniaTxt;
        }

        $fechaTxt  = !empty($hecho->fecha)
            ? \Carbon\Carbon::parse($hecho->fecha)->format('d/m/Y')
            : '';

        $peritoTxt = (string)($hecho->perito ?? '');

        // ----- tabla -----
        $tablaDatos = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
        ]);

        // ✅ anchos: col 3 y 4 más angostas (como en tu imagen)
        $c1 = (int)round($tableW * 0.18); // etiqueta izquierda
        $c3 = (int)round($tableW * 0.16); // etiqueta derecha (más angosta)
        $c4 = (int)round($tableW * 0.16); // valor derecha (más angosta)
        $c2 = $tableW - ($c1 + $c3 + $c4); // valor izquierda (se queda con lo demás)

        // ---------- FILA 1 ----------
        $tablaDatos->addRow(300, ['exactHeight' => true, 'height' => 300]);
        $tablaDatos->addCell($c1, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('LUGAR', $fontLbl7, $pCenterTight);
        $tablaDatos->addCell($c2, ['valign' => 'center'])->addText($lugarTxt, $fontVal7, $pLeftTight);
        $tablaDatos->addCell($c3, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('FECHA', $fontLbl7, $pCenterTight);
        $tablaDatos->addCell($c4, ['valign' => 'center'])->addText($fechaTxt, $fontVal7, $pCenterTight);

        // ---------- FILA 2 ----------
        $tablaDatos->addRow(300, ['exactHeight' => true, 'height' => 300]);
        $tablaDatos->addCell($c1, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('POLICÍA INVESTIGADOR', $fontLbl7, $pCenterTight);
        $tablaDatos->addCell($c2, ['valign' => 'center'])->addText($peritoTxt, $fontVal7, $pLeftTight);
        $tablaDatos->addCell($c3, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('HORA', $fontLbl7, $pCenterTight);
        $tablaDatos->addCell($c4, ['valign' => 'center'])->addText('', $fontVal7, $pCenterTight);

        // ---------- FILA 3 ----------
        $tablaDatos->addRow(300, ['exactHeight' => true, 'height' => 300]);
        $tablaDatos->addCell($c1, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('UNIDAD ESPECIAL', $fontLbl7, $pCenterTight);
        $tablaDatos->addCell($c2, ['valign' => 'center'])->addText('', $fontVal7, $pCenterTight);
        $tablaDatos->addCell($c3, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('N.U.C', $fontLbl7, $pCenterTight);
        $tablaDatos->addCell($c4, ['valign' => 'center'])->addText('', $fontVal7, $pCenterTight);

        $section->addTextBreak(1);




        // =========================================
        // ===== TABLA 1 COLUMNA x 1 FILA (FONDO GRIS, SIN MARCOS VISIBLES)
        // =========================================

        $bgAux = 'EBE1D1';

        $fontTxt7 = ['name' => 'Arial', 'size' => 7];

        $pJustifyTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaLegal = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 0,          // 👈 sin bordes
            'borderColor' => 'FFFFFF',   // respaldo visual
            'cellMargin'  => 60,
        ]);

        $tablaLegal->addRow(320, ['exactHeight' => true, 'height' => 320]);
        $cell = $tablaLegal->addCell($tableW, [
            'bgColor' => $bgAux,
            'valign'  => 'center'
        ]);

        $cell->addText(
            'Con base en lo previsto por los artículos 132 fracción VII, 214, 217, 251 fracciones I y II del Código Nacional de Procedimientos Penales.',
            $fontTxt7,
            $pJustifyTight
        );

        $section->addTextBreak(1);






        // =========================================
        // ===== TABLA 1 COLUMNA x 2 FILAS =====
        // Fila 1 (GRIS): Título + Descripción (en la MISMA fila)
        // Fila 2: Texto descriptivo
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontTxt7 = ['name' => 'Arial', 'size' => 12];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $pJustifyTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaDescLugar = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 50,
        ]);

        // ---------- FILA 1 (GRIS: TÍTULO + DESCRIPCIÓN EN UNA SOLA FILA) ----------
        $tablaDescLugar->addRow(300, ['exactHeight' => true, 'height' => 300]);
        $cell = $tablaDescLugar->addCell($tableW, [
            'bgColor' => $bgAux,
            'valign'  => 'center'
        ]);

        $run = $cell->addTextRun($pLeftTight);
        $run->addText('DESCRIPCIÓN DEL LUGAR', $fontLbl7);
        $run->addText('    ');
        $run->addText(
            'Descripción: (Qué, Quién, Cuándo, Cómo, Porqué, Dónde, Con qué)',
            $fontTxt7
        );

        // ---------- FILA 2 (TEXTO DESCRIPTIVO) ----------
        $tablaDescLugar->addRow(520, ['exactHeight' => false]);
        $cell = $tablaDescLugar->addCell($tableW, ['valign' => 'top']);

        $cell->addText(
            'Corresponde a la Carr. Tiripetio - Acuitzio, la cual se encuentra construida por una superficie de asfalto, en buen estado de conservación, tramo a nivel, cuenta con balizamientos, tiene capacidad para dos carriles de circulación, uno para cada sentido, orientados de norponiente a suroriente y viceversa, divididos por una línea longitudinal color amarillo, divisora de carriles, a la hora de la intervención la superficie de rodamiento se encontraba limpia y seca.',
            $fontTxt7,
            $pJustifyTight
        );

        $section->addTextBreak(1);




        // =========================================
        // ===== TABLA 1 COLUMNA x 1 FILA (FONDO GRIS, SIN MARCOS VISIBLES)
        // TEXTO CENTRADO Y EN NEGRITAS
        // =========================================

        $bgAux = 'EBE1D1';

        $fontTxt7Bold = ['name' => 'Arial', 'size' => 7, 'bold' => true];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaLegal = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 0,        // sin bordes visibles
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 60,
        ]);

        $tablaLegal->addRow(320, ['exactHeight' => true, 'height' => 320]);
        $cell = $tablaLegal->addCell($tableW, [
            'bgColor' => $bgAux,
            'valign'  => 'center'
        ]);

        $cell->addText(
            'PERSONAS ENCONTRADAS EN EL LUGAR.',
            $fontTxt7Bold,
            $pCenterTight
        );

        $section->addTextBreak();



        // ===============================
        // ===== PERSONAS INVOLUCRADAS (CONDUCTORES) — CUADRO PERFECTO =====
        // - 1 tabla por conductor (únicos) ligado a los vehículos del hecho
        // - 6 columnas FIJAS en TODAS las filas (con gridSpan donde aplique)
        // ===============================

        $bgAux = 'EBE1D1';

        $fontLbl7   = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7   = ['name' => 'Arial', 'size' => 7];
        $fontLbl6   = ['name' => 'Arial', 'size' => 6, 'bold' => true];   // para etiquetas largas del lado derecho
        $fontRoles6 = ['name' => 'Arial', 'size' => 6];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];
        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        // ---------- conductores únicos del hecho ----------
        $conductoresUnicos = collect();

        foreach ($hecho->vehiculos as $v) {
            if (!empty($v->conductores) && $v->conductores->count() > 0) {
                foreach ($v->conductores as $c) {
                    $conductoresUnicos->push($c);
                }
            }
        }

        $conductoresUnicos = $conductoresUnicos->unique('id')->values();

        // ---------- helper rápido para filas (evita espacios feos) ----------
        $addCellTxt = function($table, $w, $txt, $font, $bg = null, $span = 1) use ($pLeftTight, $bgAux) {
            $style = ['valign' => 'center'];
            if ($bg !== null) $style['bgColor'] = $bg;
            if ($span > 1) $style['gridSpan'] = $span;

            $cell = $table->addCell($w, $style);
            $cell->addText((string)$txt, $font, $pLeftTight);
            return $cell;
        };

        // ---------- medidas para 6 columnas (proporción “bonita” y estable) ----------
        // Pensadas para que el cuadro quede parejo y el lado derecho NO quede enano
        $c1 = (int)round($tableW * 0.23);   // etiqueta izquierda
        $c2 = (int)round($tableW * 0.34);   // valor izquierdo
        $c3 = (int)round($tableW * 0.12);   // etiqueta derecha 1
        $c4 = (int)round($tableW * 0.095);  // valor derecha 1
        $c5 = (int)round($tableW * 0.12);   // etiqueta derecha 2
        $c6 = $tableW - ($c1 + $c2 + $c3 + $c4 + $c5); // valor derecha 2 (resto)

        // ---------- 1 tabla por conductor ----------
        foreach ($conductoresUnicos as $c) {

            $tCon = $section->addTable([
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
                'width'       => $tableW,
                'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
                'borderSize'  => 6,
                'borderColor' => '000000',
                'cellMargin'  => 0,   // <-- clave para que NO quede “inflado” arriba/abajo
            ]);

            // ========= FILA 1: NOMBRE (label + valor a todo lo demás) =========
            $tCon->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt($tCon, $c1, 'NOMBRE', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $tableW - $c1, (string)($c->nombre ?? ''), $fontVal7, null, 5);

            // ========= FILA 2: DOMICILIO =========
            $tCon->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt($tCon, $c1, 'DOMICILIO', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $tableW - $c1, (string)($c->domicilio ?? ''), $fontVal7, null, 5);

            // ========= FILA 3: SEXO + ESTADO CIVIL (cuadro parejo) =========
            $tCon->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt($tCon, $c1, 'SEXO', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $c2, (string)($c->sexo ?? ''), $fontVal7, null, 1);
            $addCellTxt($tCon, $c3, 'ESTADO CIVIL', $fontLbl6, $bgAux, 1);
            $addCellTxt($tCon, $c4, '', $fontVal7, null, 1);
            // lo que sobra de la fila (para que siga siendo rectángulo perfecto)
            $addCellTxt($tCon, $c5 + $c6, '', $fontVal7, null, 2);

            // ========= FILA 4: ALIAS / FECHA NAC / LUGAR NAC (6 columnas completas) =========
            $tCon->addRow(300, ['exactHeight' => true, 'height' => 300]);
            $addCellTxt($tCon, $c1, 'ALIAS O APODO', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $c2, '', $fontVal7, null, 1);
            $addCellTxt($tCon, $c3, 'FECHA DE NACIMIENTO', $fontLbl6, $bgAux, 1);
            $addCellTxt($tCon, $c4, '', $fontVal7, null, 1);
            $addCellTxt($tCon, $c5, 'LUGAR DE NACIMIENTO', $fontLbl6, $bgAux, 1);
            $addCellTxt($tCon, $c6, '', $fontVal7, null, 1);

            // ========= FILA 5: NACIONALIDAD / IDIOMA (ESPAÑOL) / OCUPACIÓN =========
            $tCon->addRow(300, ['exactHeight' => true, 'height' => 300]);
            $addCellTxt($tCon, $c1, 'NACIONALIDAD', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $c2, '', $fontVal7, null, 1);
            $addCellTxt($tCon, $c3, 'IDIOMA', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $c4, 'ESPAÑOL', $fontVal7, null, 1);
            $addCellTxt($tCon, $c5, 'OCUPACIÓN', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $c6, (string)($c->ocupacion ?? ''), $fontVal7, null, 1);

            // ========= FILA 6: IDENTIFICACIÓN / FOLIO / ESCOLARIDAD =========
            $tCon->addRow(300, ['exactHeight' => true, 'height' => 300]);
            $addCellTxt($tCon, $c1, 'IDENTIFICACIÓN', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $c2, '', $fontVal7, null, 1);
            $addCellTxt($tCon, $c3, 'FOLIO', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $c4, '', $fontVal7, null, 1);
            $addCellTxt($tCon, $c5, 'ESCOLARIDAD', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $c6, '', $fontVal7, null, 1);

            // ========= FILA 7: TELÉFONOS (label + valor a todo lo demás) =========
            $tCon->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt($tCon, $c1, 'TELÉFONOS', $fontLbl7, $bgAux, 1);
            $addCellTxt($tCon, $tableW - $c1, (string)($c->telefono ?? ''), $fontVal7, null, 5);

            // ========= FILA 8: ROLES (1 sola celda, sin gris) =========
            $tCon->addRow(280, ['exactHeight' => true, 'height' => 280]);
            $roles = "VÍCTIMA [     ]      OFENDIDO [     ]      DENUNCIANTE [     ]      TESTIGO [     ]      IMPUTADO (A) [     ]";
            $cellRoles = $tCon->addCell($tableW, ['gridSpan' => 6, 'valign' => 'center']);
            $cellRoles->addText($roles, $fontRoles6, $pLeftTight);

            // separación mínima entre conductores (sin inflar)
            $section->addTextBreak(1);
        }


        // =========================================
        // ===== TABLA 1 COLUMNA x 1 FILA (FONDO GRIS, SIN MARCOS VISIBLES)
        // TEXTO CENTRADO Y EN NEGRITAS
        // =========================================

        $bgAux = 'EBE1D1';

        $fontTxt7Bold = ['name' => 'Arial', 'size' => 7, 'bold' => true];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaLegal = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 0,        // sin bordes visibles
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 60,
        ]);

        $tablaLegal->addRow(320, ['exactHeight' => true, 'height' => 320]);
        $cell = $tablaLegal->addCell($tableW, [
            'bgColor' => $bgAux,
            'valign'  => 'center'
        ]);

        $cell->addText(
            'OBJETOS ENCONTRADOS EN EL LUGAR.',
            $fontTxt7Bold,
            $pCenterTight
        );

        $section->addTextBreak();


        // =========================================
        // ===== TABLA 3 COLUMNAS: OBJETOS ASEGURADOS (1 fila por vehículo) =====
        // - Encabezado gris
        // - Filas dinámicas: una por cada vehículo del hecho
        // - Si un dato es NULL / vacío: se OMITE el fragmento completo (no "Serie: " en blanco)
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaObj = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
        ]);

        // Anchos (3 columnas)
        $oC1 = (int)round($tableW * 0.12);  // NUMERO
        $oC2 = (int)round($tableW * 0.20);  // OBJETO
        $oC3 = $tableW - ($oC1 + $oC2);     // DESCRIPCIÓN

        // ---------- ENCABEZADO ----------
        $tablaObj->addRow(300, ['exactHeight' => true, 'height' => 300]);
        $tablaObj->addCell($oC1, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('NUMERO', $fontLbl7, $pCenterTight);
        $tablaObj->addCell($oC2, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('OBJETO', $fontLbl7, $pCenterTight);
        $tablaObj->addCell($oC3, ['bgColor' => $bgAux, 'valign' => 'center'])->addText('DESCRIPCIÓN', $fontLbl7, $pCenterTight);

        // ---------- FILAS DINÁMICAS (1 por vehículo) ----------
        if (isset($hecho->vehiculos) && $hecho->vehiculos->count() > 0) {

            foreach ($hecho->vehiculos as $idx => $v) {

                $num = $idx + 1;
                $letra = chr(65 + $idx); // A, B, C...

                // helper: agrega fragmento SOLO si hay valor
                $addFrag = function (&$parts, $label, $value) {
                    $val = trim((string)($value ?? ''));
                    if ($val !== '' && strtoupper($val) !== 'NULL' && $val !== '0') {
                        $parts[] = $label . ' ' . $val;
                    }
                };

                $parts = [];

                $addFrag($parts, 'Marca', $v->marca);
                $addFrag($parts, 'Tipo', $v->tipo);
                $addFrag($parts, 'Línea', $v->linea);
                $addFrag($parts, 'Color', $v->color);

                // Capacidad (si es numérica y > 0)
                $cap = $v->capacidad_personas;
                if (!is_null($cap) && (string)$cap !== '' && (int)$cap > 0) {
                    $parts[] = 'Capacidad para ' . (int)$cap . ' Personas';
                }

                // Placas + (opcional) servicio + (opcional) estado
                $placas = trim((string)($v->placas ?? ''));
                if ($placas !== '' && strtoupper($placas) !== 'NULL' && $placas !== '0') {
                    $frag = 'Placas para circular ' . $placas;

                    $tipoServicio = trim((string)($v->tipo_servicio ?? ''));
                    if ($tipoServicio !== '' && strtoupper($tipoServicio) !== 'NULL') {
                        $frag .= ' del servicio ' . $tipoServicio;
                    }

                    $estadoPlacas = trim((string)($v->estado_placas ?? ''));
                    if ($estadoPlacas !== '' && strtoupper($estadoPlacas) !== 'NULL') {
                        $frag .= ' de ' . $estadoPlacas;
                    }

                    $parts[] = $frag;
                }

                $addFrag($parts, 'Serie', $v->serie);

                // Tarjeta a nombre de...
                $tarj = trim((string)($v->tarjeta_circulacion_nombre ?? ''));
                if ($tarj !== '' && strtoupper($tarj) !== 'NULL' && strtoupper($tarj) !== 'N/A') {
                    $parts[] = 'Tarjeta de circulación a nombre de ' . $tarj;
                }

                $desc = 'VEHICULO (' . $letra . ').- ' . implode(', ', $parts) . '.';

                // Fila
                $tablaObj->addRow(320, ['exactHeight' => false]);
                $tablaObj->addCell($oC1, ['valign' => 'center'])->addText((string)$num, $fontVal7, $pCenterTight);
                $tablaObj->addCell($oC2, ['valign' => 'center'])->addText('VEHICULO (' . $letra . ')', $fontVal7, $pCenterTight);
                $tablaObj->addCell($oC3, ['valign' => 'top'])->addText($desc, $fontVal7, $pLeftTight);
            }
        }

        $section->addTextBreak(1);
        $section->addPageBreak();



        // =========================================
        // ===== TABLA 1 COLUMNA x 2 FILAS =====
        // Fila 1 (GRIS): CROQUIS DEL LUGAR
        // Fila 2: espacio en blanco con ~12 saltos de línea
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7Bold = ['name' => 'Arial', 'size' => 7, 'bold' => true];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaCroquis = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 50,
        ]);

        // ---------- FILA 1 (GRIS) ----------
        $tablaCroquis->addRow(280, ['exactHeight' => true, 'height' => 280]);
        $cell = $tablaCroquis->addCell($tableW, [
            'bgColor' => $bgAux,
            'valign'  => 'center',
        ]);
        $cell->addText('CROQUIS DEL LUGAR', $fontLbl7Bold, $pCenterTight);

        // ---------- FILA 2 (VACÍA CON 12 SALTOS) ----------
        $tablaCroquis->addRow(1200, ['exactHeight' => false]);
        $cell = $tablaCroquis->addCell($tableW, ['valign' => 'top']);

        $run = $cell->addTextRun($pLeftTight);
        for ($i = 0; $i < 26; $i++) {
            $run->addTextBreak();
        }

        $section->addTextBreak(1);

        // =========================================
        // ===== TABLA FIRMAS (como tu 2da imagen)
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        // 4 columnas fijas (las 3 primeras son el bloque izquierdo, la 4ta es FIRMA grande)
        $fC1 = (int)round($tableW * 0.20);  // CARGO
        $fC2 = (int)round($tableW * 0.26);  // NÚMERO DE GAFETE
        $fC3 = (int)round($tableW * 0.14);  // UNIDAD (valor "3190" arriba)
        $fC4 = $tableW - ($fC1 + $fC2 + $fC3); // FIRMA (columna grande sin divisiones internas)

        $tFirm = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 0,
        ]);

        $addCell = function($table, $w, $txt, $font, $p, $style = []) {
            $base = ['valign' => 'center'];
            $cell = $table->addCell($w, array_merge($base, $style));
            if ($txt !== null) {
                $cell->addText((string)$txt, $font, $p);
            }
            return $cell;
        };

        $peritoTxt = (string)($hecho->perito ?? '');
        $unidadTxt = (string)($hecho->unidad ?? '');

        // -------------------- FILA 1 --------------------
        $tFirm->addRow(320, ['exactHeight' => true, 'height' => 320]);

        // (c1..c3) Perito centrado en bloque izquierdo
        $addCell($tFirm, ($fC1 + $fC2 + $fC3), $peritoTxt, $fontVal7, $pCenterTight, [
            'gridSpan' => 3
        ]);

        // (c4) FIRMA: celda grande que se “estira” hacia abajo SIN divisiones internas
        $addCell($tFirm, $fC4, '', $fontVal7, $pCenterTight, [
            'vMerge' => 'restart',
            'valign' => 'top'
        ]);

        // -------------------- FILA 2 --------------------
        $tFirm->addRow(320, ['exactHeight' => true, 'height' => 320]);

        // (c1..c3) Título gris
        $addCell($tFirm, ($fC1 + $fC2 + $fC3), 'NOMBRE DEL AGENTE INVESTIGADOR', $fontLbl7, $pCenterTight, [
            'bgColor'  => $bgAux,
            'gridSpan' => 3
        ]);

        // (c4) continúa la celda grande de FIRMA (sin línea intermedia)
        $addCell($tFirm, $fC4, '', $fontVal7, $pCenterTight, [
            'vMerge' => 'continue',
            'valign' => 'top'
        ]);

        // -------------------- FILA 3 --------------------
        $tFirm->addRow(520, ['exactHeight' => true, 'height' => 520]);

        // (c1) vacío
        $addCell($tFirm, $fC1, '', $fontVal7, $pCenterTight);

        // (c2) vacío
        $addCell($tFirm, $fC2, '', $fontVal7, $pCenterTight);

        // (c3) unidad (3190) centrada
        $addCell($tFirm, $fC3, $unidadTxt, $fontVal7, $pCenterTight);

        // (c4) sigue FIRMA grande (sin línea intermedia)
        $addCell($tFirm, $fC4, '', $fontVal7, $pCenterTight, [
            'vMerge' => 'continue',
            'valign' => 'top'
        ]);

        // -------------------- FILA 4 (encabezados grises) --------------------
        $tFirm->addRow(300, ['exactHeight' => true, 'height' => 300]);

        $addCell($tFirm, $fC1, 'CARGO', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
        $addCell($tFirm, $fC2, 'NÚMERO DE GAFETE', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
        $addCell($tFirm, $fC3, 'UNIDAD', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
        $addCell($tFirm, $fC4, 'FIRMA', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);

        $section->addTextBreak(1);


        $section->addPageBreak();









        // ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
        // ┃      ACTA DE INSPECCIÓN DE VEHÍCULOS      ┃
        // ┃      GENERACIÓN DE DOCUMENTO OFICIAL      ┃
        // ┃   CAMBIOS AQUÍ ROMPEN EL FORMATO LEGAL    ┃
        // ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛


        // ===== TÍTULO DEL DOCUMENTO (centrado) =====
        $section->addText(
            'ACTA DE INSPECCIÓN DE VEHÍCULOS',
            ['name' => 'Arial', 'size' => 14, 'bold' => true],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
                'lineHeight'  => 1.0,
            ]
        );

        $section->addTextBreak(1);


        // =========================================
        // ===== TABLA 4 COLUMNAS x 2 FILAS =====
        // Fila 1: LUGAR (gris) | calle+colonia | FECHA (gris) | fecha hecho
        // Fila 2: AGENTE (gris) | perito       | HORA (gris)  | vacío
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        // ---- valores dinámicos ----
        $calleTbl   = trim((string)($hecho->calle ?? ''));
        $coloniaTbl = trim((string)($hecho->colonia ?? ''));

        $lugarTbl = $calleTbl;
        if ($coloniaTbl !== '') {
            $lugarTbl .= ($lugarTbl !== '' ? ', ' : '') . 'col. ' . $coloniaTbl;
        }

        $fechaTbl = '';
        if (!empty($hecho->fecha)) {
            $fechaTbl = \Carbon\Carbon::parse($hecho->fecha)->format('d/m/Y');
        }

        $peritoTbl = (string)($hecho->perito ?? '');

        // ---- tabla ----
        $tablaLH = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
        ]);

        // anchos (4 columnas)
        $lhC1 = (int)round($tableW * 0.18); // etiqueta
        $lhC2 = (int)round($tableW * 0.44); // valor largo
        $lhC3 = (int)round($tableW * 0.14); // etiqueta corta
        $lhC4 = $tableW - ($lhC1 + $lhC2 + $lhC3); // valor

        // ---------- FILA 1 ----------
        $tablaLH->addRow(300, ['exactHeight' => true, 'height' => 300]);

        $tablaLH->addCell($lhC1, ['bgColor' => $bgAux, 'valign' => 'center'])
                ->addText('LUGAR', $fontLbl7, $pCenterTight);

        $tablaLH->addCell($lhC2, ['valign' => 'center'])
                ->addText($lugarTbl, $fontVal7, $pLeftTight);

        $tablaLH->addCell($lhC3, ['bgColor' => $bgAux, 'valign' => 'center'])
                ->addText('FECHA', $fontLbl7, $pCenterTight);

        $tablaLH->addCell($lhC4, ['valign' => 'center'])
                ->addText($fechaTbl, $fontVal7, $pCenterTight);

        // ---------- FILA 2 ----------
        $tablaLH->addRow(300, ['exactHeight' => true, 'height' => 300]);

        $tablaLH->addCell($lhC1, ['bgColor' => $bgAux, 'valign' => 'center'])
                ->addText('AGENTE', $fontLbl7, $pCenterTight);

        $tablaLH->addCell($lhC2, ['valign' => 'center'])
                ->addText($peritoTbl, $fontVal7, $pLeftTight);

        $tablaLH->addCell($lhC3, ['bgColor' => $bgAux, 'valign' => 'center'])
                ->addText('HORA', $fontLbl7, $pCenterTight);

        $tablaLH->addCell($lhC4, ['valign' => 'center'])
                ->addText('', $fontVal7, $pCenterTight);

        $section->addTextBreak(1);

        // =========================================
        // ===== TABLA 1 COLUMNA x 1 FILA (GRIS)
        // Título + texto legal en la MISMA celda
        // =========================================

        $bgAux = 'EBE1D1';

        $fontTitle10 = ['name' => 'Arial', 'size' => 10, 'bold' => true];
        $fontText7   = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaRevision = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 0,        // sin marcos visibles
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 60,
        ]);

        $tablaRevision->addRow(420, ['exactHeight' => false]);
        $cell = $tablaRevision->addCell($tableW, [
            'bgColor' => $bgAux,
            'valign'  => 'center'
        ]);

        $run = $cell->addTextRun($pCenterTight);
        $run->addText('REVISIÓN E INSPECCIÓN DEL VEHÍCULO', $fontTitle10);
        $run->addTextBreak();
        $run->addText(
            'Con fundamento en los artículos 16 de la Constitución Política de los Estados Unidos Mexicanos, '
            . '132 fracción VII, 217, 251 fracción V, 267 y 268 del Código Nacional de Procedimientos Penales.',
            $fontText7
        );

        $section->addTextBreak(1);


        // =========================================
        // ===== TABLA 1 COLUMNA x 2 FILAS =====
        // Fila 1 (GRIS): CAUSAS DE LA INSPECCIÓN
        // Fila 2: opciones (sin fondo)
        // =========================================

        $bgAux = 'EBE1D1';

        $fontTitle8 = ['name' => 'Arial', 'size' => 8, 'bold' => true];
        $fontText7  = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaCausas = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 50,
        ]);

        // ---------- FILA 1 (GRIS) ----------
        $tablaCausas->addRow(280, ['exactHeight' => true, 'height' => 280]);
        $cell = $tablaCausas->addCell($tableW, [
            'bgColor' => $bgAux,
            'valign'  => 'center',
        ]);
        $cell->addText('CAUSAS DE LA INSPECCIÓN', $fontTitle8, $pCenterTight);

        // ---------- FILA 2 ----------
        $tablaCausas->addRow(320, ['exactHeight' => false]);
        $cell = $tablaCausas->addCell($tableW, ['valign' => 'center']);

        $cell->addText(
            '(   ) Flagrancia.    (   ) Indicios que hagan presumir la existencia de instrumentos, objetos o productos relacionados con el hecho.',
            $fontText7,
            $pLeftTight
        );

        $section->addTextBreak(1);

        
        // =========================================
        // ===== TABLA 1 COLUMNA x 1 FILA (FONDO GRIS, SIN MARCOS VISIBLES)
        // TEXTO CENTRADO Y EN NEGRITAS
        // =========================================

        $bgAux = 'EBE1D1';

        $fontTxt7Bold = ['name' => 'Arial', 'size' => 7, 'bold' => true];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaLegal = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 0,        // sin bordes visibles
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 60,
        ]);

        $tablaLegal->addRow(320, ['exactHeight' => true, 'height' => 320]);
        $cell = $tablaLegal->addCell($tableW, [
            'bgColor' => $bgAux,
            'valign'  => 'center'
        ]);

        $cell->addText(
            'DATOS DEL VEHÍCULO.',
            $fontTxt7Bold,
            $pCenterTight
        );

        $section->addTextBreak();

        // =========================================
        // ===== TABLA POR VEHÍCULO (DINÁMICA) =====
        // - 4 columnas, 7 filas
        // - Etiquetas en gris (EBE1D1)
        // - Fila 5: CONDUCTOR/PROPIETARIO + NOMBRE (conductor asociado al vehículo)
        // - Fila 6: OBSERVACIONES (gris) + subtítulo (gris)
        // - Fila 7: partes_danadas / características del vehículo (texto)
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];
        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        // Anchos (4 columnas) -> “cuadro perfecto”
        $vC1 = (int)round($tableW * 0.20); // etiqueta izq
        $vC2 = (int)round($tableW * 0.30); // valor izq
        $vC3 = (int)round($tableW * 0.18); // etiqueta der
        $vC4 = $tableW - ($vC1 + $vC2 + $vC3); // valor der

        $addCellTxt = function($table, $w, $txt, $font, $p, $style = []) {
            $base = ['valign' => 'center'];
            $cell = $table->addCell($w, array_merge($base, $style));
            $cell->addText((string)$txt, $font, $p);
            return $cell;
        };

        if (isset($hecho->vehiculos) && $hecho->vehiculos->count() > 0) {

            foreach ($hecho->vehiculos as $v) {

                // ---------- conductor asociado (si existe) ----------
                $conductorNombre = '';
                if (!empty($v->conductores) && $v->conductores->count() > 0) {
                    // toma el primero asociado a ese vehículo en ESTE hecho
                    $conductorNombre = trim((string)($v->conductores->first()->nombre ?? ''));
                }

                // ---------- observaciones (daños / características) ----------
                $daniosVeh = trim((string)($v->partes_danadas ?? ''));
                if ($daniosVeh === '') $daniosVeh = ' '; // para que no colapse visualmente

                // ---------- tabla por vehículo ----------
                $t = $section->addTable([
                    'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                    'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
                    'width'       => $tableW,
                    'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
                    'borderSize'  => 6,
                    'borderColor' => '000000',
                    'cellMargin'  => 0,
                ]);

                // ===== FILA 1 =====
                $t->addRow(280, ['exactHeight' => true, 'height' => 280]);
                $addCellTxt($t, $vC1, 'MARCA', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
                $addCellTxt($t, $vC2, (string)($v->marca ?? ''), $fontVal7, $pCenterTight);
                $addCellTxt($t, $vC3, 'TIPO', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
                $addCellTxt($t, $vC4, (string)($v->tipo ?? ''), $fontVal7, $pCenterTight);

                // ===== FILA 2 =====
                $t->addRow(280, ['exactHeight' => true, 'height' => 280]);
                $addCellTxt($t, $vC1, 'LÍNEA', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
                $addCellTxt($t, $vC2, (string)($v->linea ?? ''), $fontVal7, $pCenterTight);
                $addCellTxt($t, $vC3, 'MODELO', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
                $addCellTxt($t, $vC4, (string)($v->modelo ?? ''), $fontVal7, $pCenterTight);

                // ===== FILA 3 =====
                $t->addRow(280, ['exactHeight' => true, 'height' => 280]);
                $addCellTxt($t, $vC1, 'COLOR', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
                $addCellTxt($t, $vC2, (string)($v->color ?? ''), $fontVal7, $pCenterTight);
                $addCellTxt($t, $vC3, 'NO. SERIE', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
                $addCellTxt($t, $vC4, (string)($v->serie ?? ''), $fontVal7, $pCenterTight);

                // ===== FILA 4 =====
                $t->addRow(280, ['exactHeight' => true, 'height' => 280]);
                $addCellTxt($t, $vC1, 'PLACAS', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
                $addCellTxt($t, $vC2, (string)($v->placas ?? ''), $fontVal7, $pCenterTight);
                $addCellTxt($t, $vC3, 'NO. MOTOR', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
                $addCellTxt($t, $vC4, '', $fontVal7, $pCenterTight);

                // ===== FILA 5 =====
                // Izquierda: checks, Derecha: NOMBRE del conductor (si hay)
                $t->addRow(340, ['exactHeight' => true, 'height' => 340]);

                $checks = '(    ) CONDUCTOR (POSEEDOR)' . "\t" . '(    ) PROPIETARIO';
                $addCellTxt($t, $vC1 + $vC2, $checks, $fontVal7, $pLeftTight);

                $addCellTxt($t, $vC3, 'NOMBRE', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
                $addCellTxt($t, $vC4, $conductorNombre, $fontVal7, $pCenterTight);

                // ===== FILA 6 (GRIS, título + subtítulo) =====
                $t->addRow(420, ['exactHeight' => true, 'height' => 420]);

                $cellObs = $t->addCell($tableW, [
                    'gridSpan' => 4,
                    'bgColor'  => $bgAux,
                    'valign'   => 'center',
                ]);

                $runObs = $cellObs->addTextRun($pCenterTight);
                $runObs->addText('OBSERVACIONES', $fontLbl7);
                $runObs->addTextBreak();
                $runObs->addText(
                    'Características particulares del vehículo (daños, injertos, calcas o aditamentos distintivos)',
                    $fontVal7
                );

                // ===== FILA 7 (daños / características) =====
                $t->addRow(380, ['exactHeight' => false]);
                $cellDan = $t->addCell($tableW, ['gridSpan' => 4, 'valign' => 'top']);
                $cellDan->addText($daniosVeh, $fontVal7, $pLeftTight);

                $section->addTextBreak(1);
            }
        }





                // =========================================
        // ===== TABLA 1 COLUMNA x 2 FILAS =====
        // Fila 1 (GRIS): OBSERVACIONES
        // Fila 2: espacio en blanco con ~12 saltos de línea
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7Bold = ['name' => 'Arial', 'size' => 7, 'bold' => true];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0,
        ];

        $tablaCroquis = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 50,
        ]);

        // ---------- FILA 1 (GRIS) ----------
        $tablaCroquis->addRow(280, ['exactHeight' => true, 'height' => 280]);
        $cell = $tablaCroquis->addCell($tableW, [
            'bgColor' => $bgAux,
            'valign'  => 'center',
        ]);
        $cell->addText('OBSERVACIONES', $fontLbl7Bold, $pCenterTight);

        // ---------- FILA 2 (VACÍA CON 12 SALTOS) ----------
        $tablaCroquis->addRow(1200, ['exactHeight' => false]);
        $cell = $tablaCroquis->addCell($tableW, ['valign' => 'top']);

        $run = $cell->addTextRun($pLeftTight);
        for ($i = 0; $i < 4; $i++) {
            $run->addTextBreak();
        }

        $section->addTextBreak(1);


        // =========================================
        // ===== TABLA FIRMAS (como tu 2da imagen)
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        // 4 columnas fijas (las 3 primeras son el bloque izquierdo, la 4ta es FIRMA grande)
        $fC1 = (int)round($tableW * 0.20);  // CARGO
        $fC2 = (int)round($tableW * 0.26);  // NÚMERO DE GAFETE
        $fC3 = (int)round($tableW * 0.14);  // UNIDAD (valor "3190" arriba)
        $fC4 = $tableW - ($fC1 + $fC2 + $fC3); // FIRMA (columna grande sin divisiones internas)

        $tFirm = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 0,
        ]);

        $addCell = function($table, $w, $txt, $font, $p, $style = []) {
            $base = ['valign' => 'center'];
            $cell = $table->addCell($w, array_merge($base, $style));
            if ($txt !== null) {
                $cell->addText((string)$txt, $font, $p);
            }
            return $cell;
        };

        $peritoTxt = (string)($hecho->perito ?? '');
        $unidadTxt = (string)($hecho->unidad ?? '');

        // -------------------- FILA 1 --------------------
        $tFirm->addRow(320, ['exactHeight' => true, 'height' => 320]);

        // (c1..c3) Perito centrado en bloque izquierdo
        $addCell($tFirm, ($fC1 + $fC2 + $fC3), $peritoTxt, $fontVal7, $pCenterTight, [
            'gridSpan' => 3
        ]);

        // (c4) FIRMA: celda grande que se “estira” hacia abajo SIN divisiones internas
        $addCell($tFirm, $fC4, '', $fontVal7, $pCenterTight, [
            'vMerge' => 'restart',
            'valign' => 'top'
        ]);

        // -------------------- FILA 2 --------------------
        $tFirm->addRow(320, ['exactHeight' => true, 'height' => 320]);

        // (c1..c3) Título gris
        $addCell($tFirm, ($fC1 + $fC2 + $fC3), 'NOMBRE DEL AGENTE INVESTIGADOR', $fontLbl7, $pCenterTight, [
            'bgColor'  => $bgAux,
            'gridSpan' => 3
        ]);

        // (c4) continúa la celda grande de FIRMA (sin línea intermedia)
        $addCell($tFirm, $fC4, '', $fontVal7, $pCenterTight, [
            'vMerge' => 'continue',
            'valign' => 'top'
        ]);

        // -------------------- FILA 3 --------------------
        $tFirm->addRow(520, ['exactHeight' => true, 'height' => 520]);

        // (c1) vacío
        $addCell($tFirm, $fC1, '', $fontVal7, $pCenterTight);

        // (c2) vacío
        $addCell($tFirm, $fC2, '', $fontVal7, $pCenterTight);

        // (c3) unidad (3190) centrada
        $addCell($tFirm, $fC3, $unidadTxt, $fontVal7, $pCenterTight);

        // (c4) sigue FIRMA grande (sin línea intermedia)
        $addCell($tFirm, $fC4, '', $fontVal7, $pCenterTight, [
            'vMerge' => 'continue',
            'valign' => 'top'
        ]);

        // -------------------- FILA 4 (encabezados grises) --------------------
        $tFirm->addRow(300, ['exactHeight' => true, 'height' => 300]);

        $addCell($tFirm, $fC1, 'CARGO', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
        $addCell($tFirm, $fC2, 'NÚMERO DE GAFETE', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
        $addCell($tFirm, $fC3, 'UNIDAD', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
        $addCell($tFirm, $fC4, 'FIRMA', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);

        $section->addTextBreak(0);


        $section->addPageBreak();







        // ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
        // ┃               ASEGURAMIENTO               ┃
        // ┃      GENERACIÓN DE DOCUMENTO OFICIAL      ┃
        // ┃   CAMBIOS AQUÍ ROMPEN EL FORMATO LEGAL    ┃
        // ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛



        // ===== ASEGURAMIENTO =====
        $section->addText(
            'ACTA DE INSPECCIÓN DE VEHÍCULOS',
            ['name' => 'Arial', 'size' => 14, 'bold' => true],
            [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
                'lineHeight'  => 1.0,
            ]
        );

        $section->addTextBreak(1);


        // =========================================
        // ===== TABLA 2 FILAS (LUGAR/FECHA/HORA + PERITO/CARGO/UNIDAD)
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 8, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 8];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];
        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        // --- datos ---
        $calle   = trim((string)($hecho->calle ?? ''));
        $colonia = trim((string)($hecho->colonia ?? ''));
        $lugarAseg = trim($calle);
        if ($colonia !== '') $lugarAseg .= ($lugarAseg !== '' ? ', ' : '') . 'col. ' . $colonia;

        $fechaTxt = !empty($hecho->fecha) ? \Carbon\Carbon::parse($hecho->fecha)->format('d/m/Y') : '';
        $horaTxt  = ''; // debe ir vacío según tu formato

        $peritoTxt = (string)($hecho->perito ?? '');
        $cargoTxt  = ''; // vacío
        $unidadTxt = (string)($hecho->unidad ?? '');

        // --- medidas (desiguales pero tabla pareja) ---
        $tableW = 9800;

        // 6 columnas: (label+valor) x3
        // Ajusta si quieres más aire en "LUGAR DE ASEGURAMIENTO"
        $c1 = 1900; // label LUGAR
        $c2 = 3600; // valor LUGAR
        $c3 = 900;  // label FECHA
        $c4 = 1200; // valor FECHA
        $c5 = 800;  // label HORA
        $c6 = $tableW - ($c1 + $c2 + $c3 + $c4 + $c5); // valor HORA (vacío)

        // --- tabla ---
        $tDG = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 0,
        ]);

        $cellLbl = ['bgColor' => $bgAux, 'valign' => 'center'];
        $cellVal = ['valign' => 'center'];

        // ===== FILA 1: LUGAR / FECHA / HORA =====
        $tDG->addRow(320, ['exactHeight' => true, 'height' => 320]);

        $tDG->addCell($c1, $cellLbl)->addText('LUGAR DE ASEGURAMIENTO', $fontLbl7, $pCenterTight);
        $tDG->addCell($c2, $cellVal)->addText($lugarAseg, $fontVal7, $pLeftTight);

        $tDG->addCell($c3, $cellLbl)->addText('FECHA', $fontLbl7, $pCenterTight);
        $tDG->addCell($c4, $cellVal)->addText($fechaTxt, $fontVal7, $pCenterTight);

        $tDG->addCell($c5, $cellLbl)->addText('HORA', $fontLbl7, $pCenterTight);
        $tDG->addCell($c6, $cellVal)->addText($horaTxt, $fontVal7, $pCenterTight);

        // ===== FILA 2: PERITO / CARGO / UNIDAD =====
        $tDG->addRow(320, ['exactHeight' => true, 'height' => 320]);

        $tDG->addCell($c1, $cellLbl)->addText('PERITO DE TRÁNSITO', $fontLbl7, $pCenterTight);
        $tDG->addCell($c2, $cellVal)->addText($peritoTxt, $fontVal7, $pLeftTight);

        $tDG->addCell($c3, $cellLbl)->addText('CARGO', $fontLbl7, $pCenterTight);
        $tDG->addCell($c4, $cellVal)->addText($cargoTxt, $fontVal7, $pCenterTight);

        $tDG->addCell($c5, $cellLbl)->addText('UNIDAD', $fontLbl7, $pCenterTight);
        $tDG->addCell($c6, $cellVal)->addText($unidadTxt, $fontVal7, $pCenterTight);

        $section->addTextBreak(1);




        // =========================================
        // ===== TABLA 1 FILA / 1 COLUMNA (GRIS) =====
        // ===== ESPACIADO ULTRA CONTROLADO ========
        // =========================================

        $bgAux = 'EBE1D1';

        $fontBold7   = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontNormal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterUltra = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $tableW = 9800;

        $tAviso = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 0,
        ]);

        // FILA MUY BAJA, SIN INFLADO
        $tAviso->addRow(320, [
            'exactHeight' => true,
            'height'      => 320,
        ]);

        $cell = $tAviso->addCell($tableW, [
            'bgColor'          => $bgAux,
            'valign'           => 'center',
            'borderTopSize'    => 6,
            'borderBottomSize' => 6,
            'borderLeftSize'   => 6,
            'borderRightSize'  => 6,
            'marginTop'        => 0,
            'marginBottom'     => 0,
            'marginLeft'       => 0,
            'marginRight'      => 0,
        ]);

        $run = $cell->addTextRun($pCenterUltra);
        $run->addText(
            'SE ANEXA AL FORMATO DE CADENA Y ESLABONES DE CUSTODIA Y/O INVENTARIO DE VEHICULO',
            $fontBold7
        );
        $run->addTextBreak(1);
        $run->addText(
            'EN CASO DE QUE EL ESPACIO SEA INSUFICIENTE  LLENAR FORMATO DE CONTINUACION  Y ANEXARLO',
            $fontNormal7
        );

        // separación mínima, no inflar documento
        $section->addTextBreak(1);



        // =========================================
        // ===== TABLA 1 FILA / 1 COLUMNA (GRIS) =====
        // ===== ESPACIADO ULTRA CONTROLADO ========
        // =========================================

        $bgAux = 'EBE1D1';

        $fontBold7   = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontNormal7 = ['name' => 'Arial', 'size' => 6];

        $pCenterUltra = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $tableW = 9800;

        $tAviso = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 0,
        ]);

        // FILA MUY BAJA, SIN INFLADO
        $tAviso->addRow(320, [
            'exactHeight' => true,
            'height'      => 320,
        ]);

        $cell = $tAviso->addCell($tableW, [
            'bgColor'          => $bgAux,
            'valign'           => 'center',
            'borderTopSize'    => 6,
            'borderBottomSize' => 6,
            'borderLeftSize'   => 6,
            'borderRightSize'  => 6,
            'marginTop'        => 0,
            'marginBottom'     => 0,
            'marginLeft'       => 0,
            'marginRight'      => 0,
        ]);

        $run = $cell->addTextRun($pCenterUltra);
        $run->addText(
            'OBJETO(S)',
            $fontBold7
        );
        $run->addTextBreak(1);
        $run->addText(
            'Con fundamento en el artículo 132 fracción V y XV, 214, 217, 229, 230, 251 fracción XI  del Código Nacional de Procedimientos Penales.',
            $fontNormal7
        );
        

        // =========================================
        // ===== TABLA: CAUSA / RELACIÓN / DECOMISO / PRUEBA / OTRO
        // ===== + ENCABEZADOS 3 COLS (CANTIDAD / OBJETOS / DESCRIPCION)
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];
        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $tableW = 9800;

        // 9 columnas (parejas pero desiguales)
        $c1 = 1550; // CAUSA (gris)
        $c2 = 1500; // RELACIÓN (label)
        $c3 = 900;  // RELACIÓN (vacío)
        $c4 = 1350; // SUJETOS (label)
        $c5 = 900;  // SUJETOS (vacío)
        $c6 = 1650; // PRUEBA (label)
        $c7 = 900;  // PRUEBA (vacío)
        $c8 = 1100; // OTRO (label)
        $c9 = $tableW - ($c1+$c2+$c3+$c4+$c5+$c6+$c7+$c8); // OTRO (vacío)

        // spans para 3 columnas grandes (3+3+3)
        $wA = $c1 + $c2 + $c3;
        $wB = $c4 + $c5 + $c6;
        $wC = $c7 + $c8 + $c9;

        $tCad = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 0,
        ]);

        $cellLblGray = ['bgColor' => $bgAux, 'valign' => 'center'];
        $cellLbl     = ['valign' => 'center'];
        $cellVal     = ['valign' => 'center'];

        // ================== FILA 1 ==================
        $tCad->addRow(320, ['exactHeight' => true, 'height' => 320]);

        $tCad->addCell($c1, $cellLblGray)->addText('CAUSA DE ASEGURAMIENTO', $fontLbl7, $pCenterTight);

        $tCad->addCell($c2, $cellLbl)->addText('RELACIÓN CON EL DELITO', $fontLbl7, $pCenterTight);
        $tCad->addCell($c3, $cellVal)->addText('', $fontVal7, $pCenterTight);

        $tCad->addCell($c4, $cellLbl)->addText('SUJETOS DE DECOMISO', $fontLbl7, $pCenterTight);
        $tCad->addCell($c5, $cellVal)->addText('', $fontVal7, $pCenterTight);

        $tCad->addCell($c6, $cellLbl)->addText('SIRVE COMO MEDIO DE PRUEBA', $fontLbl7, $pCenterTight);
        $tCad->addCell($c7, $cellVal)->addText('', $fontVal7, $pCenterTight);

        $tCad->addCell($c8, $cellLbl)->addText('OTRO ESPECIFIQUE:', $fontLbl7, $pCenterTight);
        $tCad->addCell($c9, $cellVal)->addText('', $fontVal7, $pCenterTight);

        // ================== FILA 2 (ENCABEZADOS 3 COLS) ==================
        $tCad->addRow(280, ['exactHeight' => true, 'height' => 280]);

        $tCad->addCell($wA, array_merge($cellLblGray, ['gridSpan' => 3]))
             ->addText('CANTIDAD Y/O PESO', $fontLbl7, $pCenterTight);

        $tCad->addCell($wB, array_merge($cellLblGray, ['gridSpan' => 3]))
             ->addText('OBJETO(S) ASEGURADO(S)', $fontLbl7, $pCenterTight);

        $tCad->addCell($wC, array_merge($cellLblGray, ['gridSpan' => 3]))
             ->addText('DESCRIPCION', $fontLbl7, $pCenterTight);

        // ================== FILA 3 (VALORES 3 COLS VACÍOS) ==================
        $tCad->addRow(360, ['exactHeight' => true, 'height' => 360]);

        $tCad->addCell($wA, array_merge($cellVal, ['gridSpan' => 3]))->addText('', $fontVal7, $pLeftTight);
        $tCad->addCell($wB, array_merge($cellVal, ['gridSpan' => 3]))->addText('', $fontVal7, $pLeftTight);
        $tCad->addCell($wC, array_merge($cellVal, ['gridSpan' => 3]))->addText('', $fontVal7, $pLeftTight);

        $section->addTextBreak(1);



        // =========================================
        // ===== TABLA 1 FILA / 1 COLUMNA (GRIS) =====
        // ===== ESPACIADO ULTRA CONTROLADO ========
        // =========================================

        $bgAux = 'EBE1D1';

        $fontBold7   = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontNormal7 = ['name' => 'Arial', 'size' => 6];

        $pCenterUltra = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $tableW = 9800;

        $tAviso = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 0,
        ]);

        // FILA MUY BAJA, SIN INFLADO
        $tAviso->addRow(320, [
            'exactHeight' => true,
            'height'      => 320,
        ]);

        $cell = $tAviso->addCell($tableW, [
            'bgColor'          => $bgAux,
            'valign'           => 'center',
            'borderTopSize'    => 6,
            'borderBottomSize' => 6,
            'borderLeftSize'   => 6,
            'borderRightSize'  => 6,
            'marginTop'        => 0,
            'marginBottom'     => 0,
            'marginLeft'       => 0,
            'marginRight'      => 0,
        ]);

        $run = $cell->addTextRun($pCenterUltra);
        $run->addText(
            'VEHICULO(S)',
            $fontBold7
        );
        $run->addTextBreak(1);
        $run->addText(
            'Con base en los artículos 229, 230, 233,  237, 239 y 240 del Código Nacional de Procedimientos Penales.',
            $fontNormal7
        );




        // =========================================
        // ===== TABLA: CAUSA / RELACIÓN / DECOMISO / PRUEBA / OTRO
        // ===== + ENCABEZADOS 3 COLS (CANTIDAD / OBJETOS / DESCRIPCION)
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];
        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $tableW = 9800;

        // 9 columnas (parejas pero desiguales)
        $c1 = 1550; // CAUSA (gris)
        $c2 = 1500; // RELACIÓN (label)
        $c3 = 900;  // RELACIÓN (vacío)
        $c4 = 1350; // SUJETOS (label)
        $c5 = 900;  // SUJETOS (vacío)
        $c6 = 1650; // PRUEBA (label)
        $c7 = 900;  // PRUEBA (vacío)
        $c8 = 1100; // OTRO (label)
        $c9 = $tableW - ($c1+$c2+$c3+$c4+$c5+$c6+$c7+$c8); // OTRO (vacío)

        // spans para 3 columnas grandes (3+3+3)
        $wA = $c1 + $c2 + $c3;
        $wB = $c4 + $c5 + $c6;
        $wC = $c7 + $c8 + $c9;

        $tCad = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 0,
        ]);

        $cellLblGray = ['bgColor' => $bgAux, 'valign' => 'center'];
        $cellLbl     = ['valign' => 'center'];
        $cellVal     = ['valign' => 'center'];

        // ================== FILA 1 ==================
        $tCad->addRow(320, ['exactHeight' => true, 'height' => 320]);

        $tCad->addCell($c1, $cellLblGray)->addText('CAUSA DE ASEGURAMIENTO', $fontLbl7, $pCenterTight);

        $tCad->addCell($c2, $cellLbl)->addText('RELACIÓN CON EL DELITO', $fontLbl7, $pCenterTight);
        $tCad->addCell($c3, $cellVal)->addText('', $fontVal7, $pCenterTight);

        $tCad->addCell($c4, $cellLbl)->addText('SUJETOS DE DECOMISO', $fontLbl7, $pCenterTight);
        $tCad->addCell($c5, $cellVal)->addText('', $fontVal7, $pCenterTight);

        $tCad->addCell($c6, $cellLbl)->addText('SIRVE COMO MEDIO DE PRUEBA', $fontLbl7, $pCenterTight);
        $tCad->addCell($c7, $cellVal)->addText('', $fontVal7, $pCenterTight);

        $tCad->addCell($c8, $cellLbl)->addText('OTRO ESPECIFIQUE:', $fontLbl7, $pCenterTight);
        $tCad->addCell($c9, $cellVal)->addText('', $fontVal7, $pCenterTight);

        $section->addTextBreak(1);




        // =========================================
        // ===== TABLA 1 COL / 1 FILA (GRIS) =======
        // ===== TEXTO: VEHICULOS ASEGURADOS =======
        // =========================================

        $bgAux = 'EBE1D1';

        $fontBold7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];

        $pCenterUltra = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $tableW = 9800;

        $tVehAseg = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 0,
        ]);

        $tVehAseg->addRow(260, [
            'exactHeight' => true,
            'height'      => 260,
        ]);

        $cell = $tVehAseg->addCell($tableW, [
            'bgColor'      => $bgAux,
            'valign'       => 'center',
            'marginTop'    => 0,
            'marginBottom' => 0,
            'marginLeft'   => 0,
            'marginRight'  => 0,
        ]);

        $cell->addText('VEHICULOS ASEGURADOS', $fontBold7, $pCenterUltra);

        $section->addTextBreak(1);





        // =========================================
        // ===== TABLA DINÁMICA (1 POR VEHÍCULO) =====
        // ===== ARRIBA: 8 COLUMNAS (4 PARES) ========
        // ===== ABAJO: 4 COLUMNAS (2+2) = MÁS ANCHO
        // ===== PARA CAMPOS A LÁPIZ + FILA 10 CON
        // ===== TELEFONO + FECHA/HORA (CON SU BLANCO)
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7     = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7     = ['name' => 'Arial', 'size' => 7];
        $fontValBold7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];
        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $tableW = 9800;

        // 8 columnas (arriba) = 4 pares label/valor
        $w1 = 1050; // label
        $w2 = 1450; // valor
        $w3 = 900;  // label
        $w4 = 1650; // valor
        $w5 = 900;  // label
        $w6 = 1200; // valor
        $w7 = 900;  // label derecha (CARGO/IDENT/FIRMA)
        $w8 = $tableW - ($w1+$w2+$w3+$w4+$w5+$w6+$w7); // valor derecha

        $cellGray = ['bgColor' => $bgAux, 'valign' => 'center'];
        $cellVal  = ['valign' => 'center'];

        $addCellTxt = function($table, $w, $txt, $font, $p, $style = []) {
            $cell = $table->addCell($w, array_merge(['valign' => 'center'], $style));
            if ($txt !== null) $cell->addText((string)$txt, $font, $p);
            return $cell;
        };

        // Dirección de grúa por nombre (ajusta el modelo/campos si en tu proyecto se llaman distinto)
        $resolveGruaDireccion = function($v) {
            $gruaNombre = trim((string)($v->grua ?? ''));
            if ($gruaNombre === '' || $gruaNombre === '0') return '';

            $grua = \App\Models\Grua::where(\DB::raw('UPPER(nombre)'), strtoupper($gruaNombre))->first();
            if (!$grua) return '';

            $dir = trim((string)($grua->direccion ?? ''));
            if ($dir === '') $dir = trim((string)($grua->ubicacion_corralon ?? ''));
            return $dir;
        };

        foreach ($hecho->vehiculos as $v) {

            $conductor = ($v->conductores && $v->conductores->count() > 0) ? $v->conductores->first() : null;

            $linea  = (string)($v->linea ?? '');
            $marca  = (string)($v->marca ?? '');
            $modelo = (string)($v->modelo ?? '');
            $color  = (string)($v->color ?? '');

            $placas = (string)($v->placas ?? '');
            $estado = (string)($v->estado_placas ?? '');
            $tipo   = (string)($v->tipo ?? '');
            $serie  = (string)($v->serie ?? '');

            $plazas = (string)($v->capacidad_personas ?? '');

            $nomCon = $conductor ? (string)($conductor->nombre ?? '') : '';
            $domCon = $conductor ? (string)($conductor->domicilio ?? '') : '';

            $dirGrua = $resolveGruaDireccion($v);

            $t = $section->addTable([
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
                'width'       => $tableW,
                'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
                'borderSize'  => 6,
                'borderColor' => '000000',
                'cellMargin'  => 0,
            ]);

            // =========================
            // FILA 1 (8 columnas)
            // =========================
            $t->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt($t, $w1, 'LINEA',  $fontLbl7, $pCenterTight, $cellGray);
            $addCellTxt($t, $w2, $linea,  $fontValBold7, $pCenterTight, $cellVal);
            $addCellTxt($t, $w3, 'MARCA',  $fontLbl7, $pCenterTight, $cellGray);
            $addCellTxt($t, $w4, $marca,  $fontValBold7, $pCenterTight, $cellVal);
            $addCellTxt($t, $w5, 'MODELO', $fontLbl7, $pCenterTight, $cellGray);
            $addCellTxt($t, $w6, $modelo, $fontValBold7, $pCenterTight, $cellVal);
            $addCellTxt($t, $w7, 'COLOR',  $fontLbl7, $pCenterTight, $cellGray);
            $addCellTxt($t, $w8, $color,  $fontValBold7, $pCenterTight, $cellVal);

            // =========================
            // FILA 2 (8 columnas)
            // =========================
            $t->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt($t, $w1, 'PLACA',     $fontLbl7, $pCenterTight, $cellGray);
            $addCellTxt($t, $w2, $placas,    $fontValBold7, $pCenterTight, $cellVal);
            $addCellTxt($t, $w3, 'ESTADO',    $fontLbl7, $pCenterTight, $cellGray);
            $addCellTxt($t, $w4, $estado,    $fontValBold7, $pCenterTight, $cellVal);
            $addCellTxt($t, $w5, 'TIPO',      $fontLbl7, $pCenterTight, $cellGray);
            $addCellTxt($t, $w6, $tipo,      $fontValBold7, $pCenterTight, $cellVal);
            $addCellTxt($t, $w7, 'SERIE/NIV', $fontLbl7, $pCenterTight, $cellGray);
            $addCellTxt($t, $w8, $serie,     $fontValBold7, $pCenterTight, $cellVal);

            // =========================
            // FILA 3 (8 columnas)
            // (los blancos ya quedan bien para lápiz)
            // =========================
            $t->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt($t, $w1, 'N° MOTOR',     $fontLbl7, $pCenterTight, $cellGray);
            $addCellTxt($t, $w2, '',             $fontVal7,  $pCenterTight, $cellVal);
            $addCellTxt($t, $w3, 'N° ECONOMICO', $fontLbl7, $pCenterTight, $cellGray);
            $addCellTxt($t, $w4, '',             $fontVal7,  $pCenterTight, $cellVal);
            $addCellTxt($t, $w5, 'N° DE PLAZAS', $fontLbl7, $pCenterTight, $cellGray);
            $addCellTxt($t, $w6, $plazas,        $fontValBold7, $pCenterTight, $cellVal);
            $addCellTxt($t, $w7, 'OTROS',        $fontLbl7, $pCenterTight, $cellGray);
            $addCellTxt($t, $w8, '',             $fontVal7,  $pCenterTight, $cellVal);

            // =========================================================
            // FILA 4 (CONDUCTOR / FIRMA)
            // =========================================================
            $t->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt($t, $w1, 'CONDUCTOR', $fontLbl7, $pCenterTight, $cellGray);
            $addCellTxt($t, $w2+$w3+$w4+$w5, $nomCon, $fontValBold7, $pCenterTight, [
                'gridSpan' => 4,
                'valign'   => 'center',
            ]);
            $addCellTxt($t, $w6+$w7, 'FIRMA DEL CONDUCTOR', $fontLbl7, $pCenterTight, [
                'bgColor'  => $bgAux,
                'gridSpan' => 2,
                'valign'   => 'center',
            ]);
            $addCellTxt($t, $w8, '', $fontVal7, $pCenterTight, $cellVal);

            // =========================================================
            // FILA 5 (DOMICILIO)
            // =========================================================
            $t->addRow(260, ['exactHeight' => true, 'height' => 260]);
            $addCellTxt($t, $w1, 'DOMICILIO', $fontLbl7, $pCenterTight, $cellGray);
            $addCellTxt($t, $w2+$w3+$w4+$w5+$w6+$w7+$w8, $domCon, $fontValBold7, $pLeftTight, [
                'gridSpan' => 7,
                'valign'   => 'center',
            ]);

            // =========================================================
            // ABAJO: HACEMOS MÁS ANCHO EL BLANCO (col2)
            // L1 (label izq) = w1+w2+w3  (más chico)
            // L2 (BLANCO/valor) = w4+w5+w6  (más ancho para lápiz)
            // R1 (label der) = w7
            // R2 (BLANCO/valor der) = w8
            // =========================================================
            $L1 = $w1+$w2+$w3;
            $L2 = $w4+$w5+$w6;
            $R1 = $w7;
            $R2 = $w8;

            // FILA 6
            $t->addRow(300, ['exactHeight' => true, 'height' => 300]);
            $addCellTxt($t, $L1, 'SE PONEN BAJO CUSTODIO DE:', $fontLbl7, $pLeftTight, [
                'bgColor'  => $bgAux,
                'gridSpan' => 3,
            ]);
            $addCellTxt($t, $L2, '', $fontVal7, $pLeftTight, [
                'gridSpan' => 3,
            ]);
            $addCellTxt($t, $R1, 'CARGO', $fontLbl7, $pCenterTight, [
                'bgColor' => $bgAux,
                'vMerge'  => 'restart',
            ]);
            $addCellTxt($t, $R2, '', $fontVal7, $pCenterTight, [
                'vMerge'  => 'restart',
            ]);

            // FILA 7
            $t->addRow(300, ['exactHeight' => true, 'height' => 300]);
            $addCellTxt($t, $L1, 'SE PONEN BAJO CUSTODIO EN:', $fontLbl7, $pLeftTight, [
                'bgColor'  => $bgAux,
                'gridSpan' => 3,
            ]);
            $addCellTxt($t, $L2, $dirGrua, $fontValBold7, $pLeftTight, [
                'gridSpan' => 3,
            ]);
            $addCellTxt($t, $R1, null, $fontVal7, $pCenterTight, [
                'vMerge' => 'continue',
            ]);
            $addCellTxt($t, $R2, null, $fontVal7, $pCenterTight, [
                'vMerge' => 'continue',
            ]);

            // FILA 8
            $t->addRow(300, ['exactHeight' => true, 'height' => 300]);
            $addCellTxt($t, $L1, 'SE ASEGURARON A:', $fontLbl7, $pLeftTight, [
                'bgColor'  => $bgAux,
                'gridSpan' => 3,
            ]);
            $addCellTxt($t, $L2, '', $fontVal7, $pLeftTight, [
                'gridSpan' => 3,
            ]);
            $addCellTxt($t, $R1, 'IDENTIFICACIÓN', $fontLbl7, $pCenterTight, [
                'bgColor' => $bgAux,
            ]);
            $addCellTxt($t, $R2, '', $fontVal7, $pCenterTight);

            // FILA 9
            $t->addRow(300, ['exactHeight' => true, 'height' => 300]);
            $addCellTxt($t, $L1, 'CON DOMICILIO EN:', $fontLbl7, $pLeftTight, [
                'bgColor'  => $bgAux,
                'gridSpan' => 3,
            ]);
            $addCellTxt($t, $L2, '', $fontVal7, $pLeftTight, [
                'gridSpan' => 3,
            ]);
            $addCellTxt($t, $R1, 'FIRMA', $fontLbl7, $pCenterTight, [
                'bgColor' => $bgAux,
                'vMerge'  => 'restart',
            ]);
            $addCellTxt($t, $R2, '', $fontVal7, $pCenterTight, [
                'vMerge' => 'restart',
            ]);

            // =========================================================
            // FILA 10 (AQUÍ VA TU ESTRUCTURA COMPLETA):
            // TELEFONO (gris) | blanco | FECHA Y HORA (gris) | blanco | FIRMA (continúa) | (continúa)
            //
            // Mapeo exacto a las 8 columnas sin “comerte” nada:
            //  - TEL label  = w1
            //  - TEL blanco = w2+w3  (ancho para lápiz)
            //  - FECHA label= w4+w5  (gris)
            //  - FECHA blanco= w6    (ancho para lápiz)
            //  - FIRMA      = w7 (vMerge continue)
            //  - FIRMA      = w8 (vMerge continue)
            // =========================================================
            $t->addRow(320, ['exactHeight' => true, 'height' => 320]);

            $addCellTxt($t, $w1, 'TELÉFONO:', $fontLbl7, $pCenterTight, [
                'bgColor' => $bgAux,
            ]);
            $addCellTxt($t, $w2+$w3, '', $fontVal7, $pCenterTight, [
                'gridSpan' => 2,
            ]);

            $addCellTxt($t, $w4+$w5, 'FECHA Y HORA DE LA ENTREGA:', $fontLbl7, $pCenterTight, [
                'bgColor'  => $bgAux,
                'gridSpan' => 2,
            ]);
            $addCellTxt($t, $w6, '', $fontVal7, $pCenterTight);

            $addCellTxt($t, $w7, null, $fontVal7, $pCenterTight, [
                'vMerge' => 'continue',
            ]);
            $addCellTxt($t, $w8, null, $fontVal7, $pCenterTight, [
                'vMerge' => 'continue',
            ]);

            $section->addTextBreak(1);
        }

        // =========================================
        // ===== TABLA FIRMAS (como tu 2da imagen)
        // =========================================

        $bgAux = 'EBE1D1';

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $fC1 = (int)round($tableW * 0.20);
        $fC2 = (int)round($tableW * 0.26);
        $fC3 = (int)round($tableW * 0.14);
        $fC4 = $tableW - ($fC1 + $fC2 + $fC3);

        $tFirm = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 0,
        ]);

        $addCell = function($table, $w, $txt, $font, $p, $style = []) {
            $base = ['valign' => 'center'];
            $cell = $table->addCell($w, array_merge($base, $style));
            if ($txt !== null) {
                $cell->addText((string)$txt, $font, $p);
            }
            return $cell;
        };

        $peritoTxt = (string)($hecho->perito ?? '');
        $unidadTxt = (string)($hecho->unidad ?? '');

        // -------------------- FILA 1 --------------------
        $tFirm->addRow(320, ['exactHeight' => true, 'height' => 320]);

        $addCell($tFirm, ($fC1 + $fC2 + $fC3), $peritoTxt, $fontVal7, $pCenterTight, [
            'gridSpan' => 3
        ]);

        $addCell($tFirm, $fC4, '', $fontVal7, $pCenterTight, [
            'vMerge' => 'restart',
            'valign' => 'top'
        ]);

        // -------------------- FILA 2 --------------------
        $tFirm->addRow(320, ['exactHeight' => true, 'height' => 320]);

        $addCell($tFirm, ($fC1 + $fC2 + $fC3), 'NOMBRE DEL AGENTE INVESTIGADOR', $fontLbl7, $pCenterTight, [
            'bgColor'  => $bgAux,
            'gridSpan' => 3
        ]);

        $addCell($tFirm, $fC4, '', $fontVal7, $pCenterTight, [
            'vMerge' => 'continue',
            'valign' => 'top'
        ]);

        // -------------------- FILA 3 --------------------
        $tFirm->addRow(520, ['exactHeight' => true, 'height' => 520]);
        $addCell($tFirm, $fC1, '', $fontVal7, $pCenterTight);
        $addCell($tFirm, $fC2, '', $fontVal7, $pCenterTight);
        $addCell($tFirm, $fC3, $unidadTxt, $fontVal7, $pCenterTight);

        $addCell($tFirm, $fC4, '', $fontVal7, $pCenterTight, [
            'vMerge' => 'continue',
            'valign' => 'top'
        ]);

        // -------------------- FILA 4 (encabezados grises) --------------------
        $tFirm->addRow(300, ['exactHeight' => true, 'height' => 300]);

        $addCell($tFirm, $fC1, 'CARGO', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
        $addCell($tFirm, $fC2, 'NÚMERO DE GAFETE', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
        $addCell($tFirm, $fC3, 'UNIDAD', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
        $addCell($tFirm, $fC4, 'FIRMA', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);

        $section->addPageBreak();




        // ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
        // ┃ ACTA D CADENA y ESLAB. DE CTDIA. D EDCIA. ┃
        // ┃      GENERACIÓN DE DOCUMENTO OFICIAL      ┃
        // ┃   CAMBIOS AQUÍ ROMPEN EL FORMATO LEGAL    ┃
        // ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

        // =========================================
        // ===== CONFIG BASE (FUERA DEL BUCLE) ======
        // =========================================
        $bgAux  = 'EBE1D1';
        $tableW = 9800;

        // Fuentes
        $fontBold10  = ['name' => 'Arial', 'size' => 10, 'bold' => true];
        $fontNormal9 = ['name' => 'Arial', 'size' => 9];

        $fontLbl7 = ['name' => 'Arial', 'size' => 7, 'bold' => true];
        $fontVal7 = ['name' => 'Arial', 'size' => 7];

        $fontLbl8 = ['name' => 'Arial', 'size' => 8, 'bold' => true];
        $fontVal8 = ['name' => 'Arial', 'size' => 8];

        $fontHdr10  = ['name' => 'Arial', 'size' => 10, 'bold' => true];
        $fontCell10 = ['name' => 'Arial', 'size' => 10];

        // Párrafos
        $pCenterTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        $pLeftTight = [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ];

        // Helper addCell (AQUÍ estaba el error: cuando $style llega como string, array_merge truena)
        // Lo blindamos para que SIEMPRE sea array.
        $addCell = function($table, $w, $txt, $font, $p, $style = []) {
            $base = ['valign' => 'center'];
            if (!is_array($style)) { $style = []; } // <- FIX
            $cell = $table->addCell($w, array_merge($base, $style));
            if ($txt !== null) {
                $cell->addText((string)$txt, $font, $p);
            }
            return $cell;
        };

        // Letras A, B, C...
        $getLetter = function($idx) {
            $letters = range('A', 'Z');
            return $letters[$idx] ?? (string)($idx + 1);
        };

        // ===== Lista de vehículos segura
        $vehiculos = (isset($hecho->vehiculos) && $hecho->vehiculos) ? $hecho->vehiculos : collect();
        $totalVeh  = $vehiculos->count();

        // ===== Datos del hecho reutilizables
        $fechaTxt = '';
        if (!empty($hecho->fecha)) {
            try { $fechaTxt = \Carbon\Carbon::parse($hecho->fecha)->format('d/m/Y'); }
            catch (\Throwable $e) { $fechaTxt = (string)$hecho->fecha; }
        }

        $calleTxt   = trim((string)($hecho->calle ?? ''));
        $coloniaTxt = trim((string)($hecho->colonia ?? ''));
        $lugarTxt   = trim($calleTxt);
        if ($coloniaTxt !== '') {
            $lugarTxt .= ($lugarTxt !== '' ? ', col. ' : '') . $coloniaTxt;
        }

        $peritoTxt = (string)($hecho->perito ?? '');
        $unidadTxt = (string)($hecho->unidad ?? '');

        foreach ($vehiculos as $i => $v) {

            // ============================
            // ===== HOJA POR VEHÍCULO =====
            // ============================

            // ===== ASEGURAMIENTO =====
            $section->addText(
                'ACTA DE CADENA y ESLABONES DE CUSTODIA DE EVIDENCIA',
                ['name' => 'Arial', 'size' => 14, 'bold' => true],
                [
                    'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                    'spaceBefore' => 0,
                    'spaceAfter'  => 0,
                    'lineHeight'  => 1.0,
                ]
            );

            $section->addTextBreak(1);

            // =========================================
            // ===== TABLA 1 COLUMNA / 1 FILA (GRIS) ====
            // =========================================
            $tCadena = $section->addTable([
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
                'width'       => $tableW,
                'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
                'borderSize'  => 6,
                'borderColor' => '000000',
                'cellMargin'  => 0,
            ]);

            $tCadena->addRow(520, ['exactHeight' => true, 'height' => 520]);

            $cell = $tCadena->addCell($tableW, [
                'bgColor' => $bgAux,
                'valign'  => 'center',
            ]);

            $run = $cell->addTextRun($pCenterTight);
            $run->addText('CADENA DE CUSTODIA', $fontBold10);
            $run->addTextBreak();
            $run->addText(
                'Artículos 132 fracciones VIII y IX, 217, 227 y 228 del Código Nacional de Procedimientos Penales',
                $fontNormal9
            );

            $section->addTextBreak(1);

            // =========================================
            // ===== TABLA: OFICIO/FECHA/HORA + RESPONSABLES
            // =========================================
            $c1 = (int)round($tableW * 0.34);
            $c2 = (int)round($tableW * 0.33);
            $c3 = $tableW - ($c1 + $c2);

            $tLev = $section->addTable([
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
                'width'       => $tableW,
                'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
                'borderSize'  => 6,
                'borderColor' => '000000',
                'cellMargin'  => 0,
            ]);

            // FILA 1
            $tLev->addRow(320, ['exactHeight' => true, 'height' => 320]);
            $addCell($tLev, $c1, 'No. DE OFICIO:', $fontLbl8, $pLeftTight);
            $addCell($tLev, $c2, 'FECHA: ' . $fechaTxt, $fontLbl8, $pLeftTight);
            $addCell($tLev, $c3, 'HORA:', $fontLbl8, $pLeftTight);

            // FILA 2
            $tLev->addRow(420, ['exactHeight' => true, 'height' => 420]);
            $addCell($tLev, $c1, 'LUGAR DE LEVANTAMIENTO', $fontLbl8, $pLeftTight, ['bgColor' => $bgAux]);
            $addCell($tLev, ($c2 + $c3), $lugarTxt, $fontVal8, $pLeftTight, ['gridSpan' => 2]);

            // FILA 3
            $tLev->addRow(420, ['exactHeight' => true, 'height' => 420]);
            $addCell($tLev, $c1, 'AGENTE RESPONSABLE LEVANTAMIENTO', $fontLbl8, $pLeftTight, ['bgColor' => $bgAux]);
            $addCell($tLev, ($c2 + $c3), $peritoTxt, $fontVal8, $pLeftTight, ['gridSpan' => 2]);

            // FILA 4
            $tLev->addRow(420, ['exactHeight' => true, 'height' => 420]);
            $addCell($tLev, $c1, 'RESPONSABLE DE EMBALAR', $fontLbl8, $pLeftTight, ['bgColor' => $bgAux]);
            $addCell($tLev, ($c2 + $c3), '', $fontVal8, $pLeftTight, ['gridSpan' => 2]);

            // FILA 5
            $tLev->addRow(420, ['exactHeight' => true, 'height' => 420]);
            $addCell($tLev, $c1, 'RESPONSABLE DE TRASLADO', $fontLbl8, $pLeftTight, ['bgColor' => $bgAux]);
            $addCell($tLev, ($c2 + $c3), '', $fontVal8, $pLeftTight, ['gridSpan' => 2]);

            $section->addTextBreak(1);

            // =========================================
            // ===== TABLA: DESCRIPCIÓN DE EVIDENCIAS ===
            // =========================================

            // Datos del vehículo (SIN VARIABLES SUELTAS)
            $marca             = (string)($v->marca ?? '');
            $tipo              = (string)($v->tipo ?? '');
            $linea             = (string)($v->linea ?? '');
            $color             = (string)($v->color ?? '');
            $capacidadPersonas = (string)($v->capacidad_personas ?? '');
            $placas            = (string)($v->placas ?? '');
            $tipoServicio      = (string)($v->tipo_servicio ?? '');
            $estadoPlacas      = (string)($v->estado_placas ?? '');

            $num   = $i + 1;         // 1,2,3
            $letra = $getLetter($i); // A,B,C

            // Anchos columnas (4 cols)
            $eC1 = (int)round($tableW * 0.08);   // NO.
            $eC4 = (int)round($tableW * 0.17);   // EXAMEN
            $eC3 = (int)round($tableW * 0.22);   // LUGAR
            $eC2 = $tableW - ($eC1 + $eC3 + $eC4);

            $tEvi = $section->addTable([
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
                'width'       => $tableW,
                'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
                'borderSize'  => 6,
                'borderColor' => '000000',
                'cellMargin'  => 0,
            ]);

            // FILA 1 (título) - IMPORTANTE: gridSpan SIN addCell para evitar estilos raros
            $tEvi->addRow(360, ['exactHeight' => true, 'height' => 360]);

            $cellTitle = $tEvi->addCell($tableW, ['bgColor' => $bgAux, 'valign' => 'center', 'gridSpan' => 4]);
            $cellTitle->addText('DESCIPCIÓN DE EVIDENCIAS', $fontHdr10, $pCenterTight);

            // FILA 2 (encabezados)
            $tEvi->addRow(520, ['exactHeight' => true, 'height' => 520]);
            $addCell($tEvi, $eC1, 'NO.', $fontHdr10, $pCenterTight, ['bgColor' => $bgAux]);
            $addCell($tEvi, $eC2, 'DESCRIPCIÓN DE LA EVIDENCIA', $fontHdr10, $pCenterTight, ['bgColor' => $bgAux]);
            $addCell($tEvi, $eC3, 'LUGAR DEL HECHO', $fontHdr10, $pCenterTight, ['bgColor' => $bgAux]);

            $cellEx = $tEvi->addCell($eC4, ['bgColor' => $bgAux, 'valign' => 'center']);
            $runEx  = $cellEx->addTextRun($pCenterTight);
            $runEx->addText('EXAMEN(ES)', $fontHdr10);
            $runEx->addTextBreak();
            $runEx->addText('SOLICITADO(S)', $fontHdr10);

            // FILA 3 (vehículo)
            $tEvi->addRow(820, ['exactHeight' => false]);

            $addCell($tEvi, $eC1, $num, $fontCell10, $pCenterTight);

            $cellDesc = $tEvi->addCell($eC2, ['valign' => 'center']);
            $runDesc  = $cellDesc->addTextRun($pLeftTight);
            $runDesc->addText("VEHICULO ($letra).-", $fontHdr10);
            $runDesc->addText(
                " Marca $marca, Tipo $tipo, Línea $linea, Color $color, Capacidad para $capacidadPersonas Personas, Placas para circular $placas del servicio $tipoServicio de $estadoPlacas",
                $fontCell10
            );

            $addCell($tEvi, $eC3, $lugarTxt, $fontCell10, $pLeftTight);
            $addCell($tEvi, $eC4, '', $fontCell10, $pCenterTight);

            $section->addTextBreak(1);


            // =========================================
            // ===== TABLA: ESLABONES / ENTREGA-RECIBE ==
            // ===== CUADRADO PERFECTO (2 COLS SIEMPRE) =
            // ===== Fila 1: ESLABONES (colSpan 2) ======
            // ===== Fila 2: ENTREGA | RECIBE ===========
            // ===== Arial 10 ==========================
            // =========================================

            $bgAux  = 'EBE1D1';
            $tableW = 9800;

            $fontBold10 = ['name' => 'Arial', 'size' => 10, 'bold' => true];

            $pCenterTight10 = [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
                'lineHeight'  => 1.0,
            ];

            $w1 = (int)round($tableW * 0.50);
            $w2 = $tableW - $w1;

            $tEsl = $section->addTable([
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
                'width'       => $tableW,
                'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
                'borderSize'  => 6,
                'borderColor' => '000000',
                'cellMargin'  => 0,
            ]);

            // Helper (evita repeats)
            $addCellT = function($table, $w, $txt, $style = []) use ($fontBold10, $pCenterTight10) {
                $cell = $table->addCell($w, array_merge(['valign' => 'center'], $style));
                $cell->addText((string)$txt, $fontBold10, $pCenterTight10);
                return $cell;
            };

            // -------------------- FILA 1 (2 columnas, pero celda combinada) --------------------
            $tEsl->addRow(360, ['exactHeight' => true, 'height' => 360]);

            $addCellT($tEsl, $w1, 'ESLABONES', [
                'bgColor'  => $bgAux,
                'gridSpan' => 2
            ]);
            // (NO se agrega la segunda celda porque gridSpan=2 ya cubre las 2 columnas)

            // -------------------- FILA 2 (2 columnas normales) --------------------
            $tEsl->addRow(360, ['exactHeight' => true, 'height' => 360]);

            $addCellT($tEsl, $w1, 'ENTREGA', ['bgColor' => $bgAux]);
            $addCellT($tEsl, $w2, 'RECIBE',  ['bgColor' => $bgAux]);

            $section->addTextBreak(1);


            // =========================================
            // ===== 5 TABLAS (DOBLE BLOQUE) ============
            /*
                - Fila 1: "Apellido Paterno ... Apellido Materno ... Nombre(s)" (Arial 5, superíndice)
                          + salto de línea y espacio en blanco (Arial 12) para escribir
                - Fila 2: FECHA / HORA / FIRMA (cada una con Arial 5 superíndice arriba y abajo Arial 12 en blanco)
                - Repetido a la derecha (como la imagen)
            */
            // =========================================

            $miniTop5Sup = ['name' => 'Arial', 'size' => 7, 'superScript' => true];
            $miniBot12   = ['name' => 'Arial', 'size' => 12];

            $pMiniTop = [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
                'lineHeight'  => 1.0,
            ];

            $nbsp12 = str_repeat("\xC2\xA0", 12); // 12 espacios "duros" (no colapsan)
            $apellidoLinea = "Apellido Paterno{$nbsp12}Apellido Materno{$nbsp12}Nombre(s)";

            $addTopBottom = function($cell, $topText) use ($miniTop5Sup, $miniBot12, $pMiniTop) {
                $run = $cell->addTextRun($pMiniTop);
                $run->addText((string)$topText, $miniTop5Sup);
                $run->addTextBreak();
                // línea en blanco (pero con altura) para escribir
                $run->addText("\xC2\xA0", $miniBot12);
            };

            $tblBase = [
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
                'width'       => $tableW,
                'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
                'borderSize'  => 6,
                'borderColor' => '000000',
                'cellMargin'  => 0,
            ];

            // 6 columnas totales: (FECHA, HORA, FIRMA) izquierda + (FECHA, HORA, FIRMA) derecha
            $halfW = (int)floor($tableW / 2);

            $fechaW = (int)round($halfW * 0.33);
            $horaW  = (int)round($halfW * 0.11);
            $firmaW = $halfW - ($fechaW + $horaW);

            // Repetir 5 tablas
            for ($k = 0; $k < 5; $k++) {

                $tMini = $section->addTable($tblBase);

                // -------- FILA 1: Apellidos / Nombre(s) (izq y der) --------
                $tMini->addRow(520, ['exactHeight' => true, 'height' => 520]);

                $cellL = $tMini->addCell($halfW, ['gridSpan' => 3, 'valign' => 'top']);
                $addTopBottom($cellL, $apellidoLinea);

                $cellR = $tMini->addCell($halfW, ['gridSpan' => 3, 'valign' => 'top']);
                $addTopBottom($cellR, $apellidoLinea);

                // -------- FILA 2: FECHA / HORA / FIRMA (izq y der) --------
                $tMini->addRow(650, ['exactHeight' => true, 'height' => 650]);

                // Izquierda
                $cFechaL = $tMini->addCell($fechaW, ['valign' => 'top']);
                $addTopBottom($cFechaL, 'FECHA: día/mes/año');

                $cHoraL = $tMini->addCell($horaW, ['valign' => 'top']);
                $addTopBottom($cHoraL, 'HORA');

                $cFirmaL = $tMini->addCell($firmaW, ['valign' => 'top']);
                $addTopBottom($cFirmaL, 'FIRMA');

                // Derecha
                $cFechaR = $tMini->addCell($fechaW, ['valign' => 'top']);
                $addTopBottom($cFechaR, 'FECHA: día/mes/año');

                $cHoraR = $tMini->addCell($horaW, ['valign' => 'top']);
                $addTopBottom($cHoraR, 'HORA');

                $cFirmaR = $tMini->addCell($firmaW, ['valign' => 'top']);
                $addTopBottom($cFirmaR, 'FIRMA');

                // SIN addTextBreak() para que no meta “vergero” de espacio entre tablas
            }




            // =========================================
            // ===== TABLA FIRMAS (como tu 2da imagen) ==
            // =========================================
            $fC1 = (int)round($tableW * 0.20);
            $fC2 = (int)round($tableW * 0.26);
            $fC3 = (int)round($tableW * 0.14);
            $fC4 = $tableW - ($fC1 + $fC2 + $fC3);

            $tFirm = $section->addTable([
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
                'width'       => $tableW,
                'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
                'borderSize'  => 6,
                'borderColor' => '000000',
                'cellMargin'  => 0,
            ]);

            // FILA 1
            $tFirm->addRow(320, ['exactHeight' => true, 'height' => 320]);
            $addCell($tFirm, ($fC1 + $fC2 + $fC3), $peritoTxt, $fontVal7, $pCenterTight, ['gridSpan' => 3]);
            $addCell($tFirm, $fC4, '', $fontVal7, $pCenterTight, ['vMerge' => 'restart', 'valign' => 'top']);

            // FILA 2
            $tFirm->addRow(320, ['exactHeight' => true, 'height' => 320]);
            $addCell($tFirm, ($fC1 + $fC2 + $fC3), 'NOMBRE DEL AGENTE INVESTIGADOR', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux, 'gridSpan' => 3]);
            $addCell($tFirm, $fC4, '', $fontVal7, $pCenterTight, ['vMerge' => 'continue', 'valign' => 'top']);

            // FILA 3
            $tFirm->addRow(520, ['exactHeight' => true, 'height' => 520]);
            $addCell($tFirm, $fC1, '', $fontVal7, $pCenterTight);
            $addCell($tFirm, $fC2, '', $fontVal7, $pCenterTight);
            $addCell($tFirm, $fC3, $unidadTxt, $fontVal7, $pCenterTight);
            $addCell($tFirm, $fC4, '', $fontVal7, $pCenterTight, ['vMerge' => 'continue', 'valign' => 'top']);

            // FILA 4 (encabezados)
            $tFirm->addRow(300, ['exactHeight' => true, 'height' => 300]);
            $addCell($tFirm, $fC1, 'CARGO', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
            $addCell($tFirm, $fC2, 'NÚMERO DE GAFETE', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
            $addCell($tFirm, $fC3, 'UNIDAD', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);
            $addCell($tFirm, $fC4, 'FIRMA', $fontLbl7, $pCenterTight, ['bgColor' => $bgAux]);

            // =========================
            // PAGE BREAK ENTRE HOJAS
            // =========================
            if ($i < $totalVeh - 1) {
                $section->addPageBreak();
            }
        }

















        $section->addPageBreak();
        // ===== CONTENIDO =====

        // DATOS GENERALES
        $section->addText("DATOS GENERALES", ['bold' => true]);
        $section->addText("Folio C5i: {$hecho->folio_c5i}");
        $section->addText("Fecha: " . Carbon::parse($hecho->fecha)->format('d/m/Y') . "   Hora: " . Carbon::parse($hecho->hora)->format('H:i'));
        $section->addText("Tipo de hecho: {$hecho->tipo_hecho}");
        $section->addText("Sector: {$hecho->sector}");
        $section->addText("Perito: {$hecho->perito}");
        $section->addText("Situación: {$hecho->situacion}");
        $section->addText("Municipio: {$hecho->municipio}");
        $section->addTextBreak(1);

        // UBICACIÓN
        $section->addText("UBICACIÓN", ['bold' => true]);
        $section->addText("Calle: {$hecho->calle}");
        $section->addText("Colonia: {$hecho->colonia}");
        $section->addText("Entre calles: {$hecho->entre_calles}");
        $section->addText("Superficie: {$hecho->superficie_via}");
        $section->addText("Clima: {$hecho->clima}   Tiempo: {$hecho->tiempo}   Condiciones: {$hecho->condiciones}");
        $section->addText("Control de tránsito: {$hecho->control_transito}");
        $section->addTextBreak(1);

        // VEHÍCULOS + CONDUCTORES
        $section->addText("VEHÍCULOS INVOLUCRADOS", ['bold' => true]);

        foreach ($hecho->vehiculos as $idx => $v) {
            $num = $idx + 1;

            $section->addText("Vehículo {$num}", ['bold' => true]);
            $section->addText("Placas: {$v->placas}");
            $section->addText("Marca: {$v->marca}   Línea: {$v->linea}   Modelo: {$v->modelo}   Color: {$v->color}");
            $section->addText("Tipo: {$v->tipo}   Servicio: {$v->tipo_servicio}");
            $section->addText("Serie: {$v->serie}");
            $section->addText("Tarjeta a nombre de: {$v->tarjeta_circulacion_nombre}");
            $section->addText("Grúa: {$v->grua}   Corralón: {$v->corralon}");
            $section->addText("Monto de daños: $ " . number_format($v->monto_danos ?? 0, 2));
            $section->addText("Partes dañadas: {$v->partes_danadas}");

            $section->addText("Conductores:", ['bold' => true]);
            if ($v->conductores->count() === 0) {
                $section->addText("- Sin conductores asociados");
            } else {
                foreach ($v->conductores as $c) {
                    $lic  = $c->tipo_licencia ? $c->tipo_licencia : 'No presentó';
                    $edad = $c->edad ? $c->edad . " años" : "s/e";
                    $ocup = $c->ocupacion ? $c->ocupacion : "s/e";
                    $section->addText("- {$c->nombre} ({$edad}) | {$ocup} | Licencia: {$lic}");
                }
            }

            $section->addTextBreak(1);
        }

        // LESIONADOS
        $section->addText("LESIONADOS", ['bold' => true]);

        if ($hecho->lesionados->count() === 0) {
            $section->addText("No hubo lesionados.");
        } else {
            foreach ($hecho->lesionados as $i => $l) {
                $n = $i + 1;
                $section->addText("Lesionado {$n}", ['bold' => true]);
                $section->addText("Nombre: {$l->nombre}");
                $section->addText("Edad: {$l->edad}   Sexo: {$l->sexo}");
                $section->addText("Tipo de lesión: {$l->tipo_lesion}");
                $section->addText("Hospitalizado: " . ($l->hospitalizado ? 'Sí' : 'No'));
                $section->addText("Hospital: {$l->hospital}");
                $section->addText("Observaciones: {$l->observaciones}");
                $section->addTextBreak(1);
            }
        }

        // CAUSAS Y DAÑOS PATRIMONIALES
        $section->addText("CAUSAS Y DAÑOS PATRIMONIALES", ['bold' => true]);
        $section->addText("Causas: {$hecho->causas}");
        $section->addText("Daños patrimoniales: {$hecho->danos_patrimoniales}");
        $section->addText("Monto daños patrimoniales: $ " . number_format($hecho->monto_danos_patrimoniales ?? 0, 2));
        $section->addText("Propiedades afectadas: {$hecho->propiedades_afectadas}");

        // Guardar y descargar
        $filename = "dictamen_hecho_{$hecho->id}.docx";
        $tempPath = storage_path("app/public/{$filename}");
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }
}
