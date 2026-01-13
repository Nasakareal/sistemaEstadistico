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

        // Relaciones necesarias
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
        // Traer SOLO el hecho
        $hecho = Hechos::with(['vehiculos.conductores', 'lesionados'])->findOrFail($id);

        // Fecha para el encabezado (usa la fecha real del hecho)
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

        // === Numeración de páginas en el pie ===
        $footer = $section->addFooter();

        $footer->addPreserveText(
            'Página {PAGE} de {NUMPAGES}',
            ['size' => 10],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        // === Encabezado con imágenes (igual al Parte) ===
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
        // Estilo de párrafo pegado (cero espacio)
        $phpWord->setDefaultParagraphStyle([
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1,
        ]);

        // === TABLA OFICIO (GRIS, SIN BORDES, SIN AIRE, FULL ANCHO) ===
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

        // Párrafo sin aire (por si Word se pone mamón)
        $p = ['spaceBefore'=>0,'spaceAfter'=>0,'lineHeight'=>1];

        // Celda gris SIN bordes y SIN padding
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
            // altura exacta para que abrace el texto
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



        // === Fecha a la derecha ===
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



        // === Destinatario (igual al Parte) ===
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



        // === Quién lo realiza ===
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
        $tiempoTexto = 'De día'; // default seguro

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

            // Encabezado del vehículo
            $textRun = $section->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH]);

            $textRun->addText('               VEHÍCULO ');
            $textRun->addText("({$letra})", ['bold' => true]);
            $textRun->addText('.- ');

            // Parte del vehículo (variables en negrita)
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

            // Servicio + Estado (si existe)
            if ($servicio !== '' || $estadoPl !== '') {
                $textRun->addText(' del servicio ');
                $textRun->addText($servicio !== '' ? $servicio : 's/e', ['bold' => true]);

                if ($estadoPl !== '') {
                    $textRun->addText(' de ');
                    $textRun->addText($estadoPl, ['bold' => true]);
                }
            }

            // Serie
            $textRun->addText(', Serie ');
            $textRun->addText($serie ?: 's/e', ['bold' => true]);

            // Tarjeta de circulación
            if ($tarjeta !== '' && strtoupper($tarjeta) !== 'N/A') {
                $textRun->addText(', tarjeta de circulación a nombre de ');
                $textRun->addText($tarjeta, ['bold' => true]);
            }

            // --- Conductores ---
            if ($v->conductores->count() === 0) {
                $textRun->addText('. No se cuenta con datos del conductor.');
            } else {
                foreach ($v->conductores as $cIdx => $c) {
                    $nombre = trim((string) $c->nombre);
                    $edad   = $c->edad ? (string)$c->edad : 's/e';
                    $dom    = trim((string) $c->domicilio);

                    // licencia: si no hay tipo_licencia, "No presentó licencia de conducir."
                    $licencia = trim((string) $c->tipo_licencia);
                    $licTxt   = ($licencia !== '' ? $licencia : 'No presentó');

                    // Si hay varios conductores, separarlos
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

                    // Si hay más de un conductor, terminamos con punto y seguimos
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

                // Estos campos deben existir en tu tabla lesionados; si no, cámbialos al nombre real:
                $hospital = trim((string) ($l->hospital ?? ''));
                $unidad   = trim((string) ($l->unidad ?? ''));
                $cargo    = trim((string) ($l->a_cargo_de ?? $l->responsable_unidad ?? ''));

                $textRun = $section->addTextRun([
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH
                ]);

                $textRun->addText('                 De este hecho de tránsito resultaron lesionados: ');

                // Nombre en negritas (solo esto)
                $textRun->addText($nombre, ['bold' => true]);

                $textRun->addText(' de ' . $edad . ' años de edad');

                // Redacción singular/plural: aquí queda singular como en tu ejemplo ("el cual fue...")
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

                // Si hay más lesionados, separa con "; " y sigue en otro párrafo/oración
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
                if ($n < 2000) { // 1000-1999
                    $rem = $n - 1000;
                    return $rem ? ('MIL '.$toWords($rem)) : 'MIL';
                }
                if ($n < 1000000) { // miles
                    $mil = (int)($n/1000);
                    $rem = $n % 1000;
                    $txt = $toWords($mil).' MIL';
                    return $rem ? ($txt.' '.$toWords($rem)) : $txt;
                }
                if ($n < 2000000) { // 1,000,000-1,999,999
                    $rem = $n - 1000000;
                    return $rem ? ('UN MILLÓN '.$toWords($rem)) : 'UN MILLÓN';
                }
                if ($n < 1000000000) { // millones
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

        // ---- En tu DB: vehiculos.grua guarda NOMBRE (ej. "DANNYS") ----
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







        $fechaEvento = !empty($hecho->created_at)
            ? \Carbon\Carbon::parse($hecho->created_at)->format('d/m/Y')
            : (!empty($hecho->fecha) ? \Carbon\Carbon::parse($hecho->fecha)->format('d/m/Y') : '');

        $horaEvento = !empty($hecho->created_at)
            ? \Carbon\Carbon::parse($hecho->created_at)->format('H:i')
            : (!empty($hecho->hora) ? substr((string)$hecho->hora, 0, 5) : '');

        // INFORME: fecha de descarga (hoy) y hora en blanco
        $fechaInforme = now()->format('d/m/Y');
        $horaInforme  = '';

        // === INFORME POLICIAL HOMOLOGADO ===
        $section->addText(
            'INFORME POLICIAL HOMOLOGADO',
            ['bold' => true, 'size' => 14],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
        );

        // Párrafo sin espacios (para TODAS las celdas)
        $pCenter0 = [
            'alignment'   => Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter'  => 0,
            'lineHeight'  => 1.0
        ];

        // Tabla: a la DERECHA y más compacta
        $table = $section->addTable([
            'alignment'   => Jc::RIGHT,
            'width'       => 100,
            'unit'        => TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 0
        ]);

        // Anchos (4 columnas) que suman 7200
        $wLabel = 1100;
        $wVal   = 2500;

        // Estilos
        $headerCellStyle = ['bgColor' => 'EBE1D1', 'valign' => 'center'];

        // ===== Fila 1: EVENTO / INFORME =====
        $table->addRow(260);
        $table->addCell($wLabel + $wVal, array_merge($headerCellStyle, ['gridSpan' => 2]))
              ->addText('EVENTO', ['bold' => true], $pCenter0);

        $table->addCell($wLabel + $wVal, array_merge($headerCellStyle, ['gridSpan' => 2]))
              ->addText('INFORME', ['bold' => true], $pCenter0);

        // ===== Fila 2: FECHA =====
        $table->addRow(340);
        $table->addCell($wLabel, ['valign' => 'center'])->addText('FECHA', [], $pCenter0);
        $table->addCell($wVal,   ['valign' => 'center'])->addText($fechaEvento,  ['bold' => true, 'size' => 12], $pCenter0);
        $table->addCell($wLabel, ['valign' => 'center'])->addText('FECHA', [], $pCenter0);
        $table->addCell($wVal,   ['valign' => 'center'])->addText($fechaInforme, ['bold' => true, 'size' => 12], $pCenter0);

        // ===== Fila 3: HORA =====
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


        // ===== FILA 1: TIPO DE EVENTO | (col 2-4 combinadas) =====
        $table->addRow(320);
        $table->addCell($wC1, $leftColCell)->addText('TIPO DE EVENTO', $fontNormal7, $pCenter0);
        $table->addCell($wC2, array_merge($cellMid, ['gridSpan' => 3]))->addText($tituloTipoEvento, $fontBold7, $pCenter0);

        // ===== FILA 2: LUGAR DEL EVENTO | (col 2-4 combinadas) =====
        $table->addRow(420);
        $table->addCell($wC1, $leftColCell)->addText('LUGAR DEL EVENTO', $fontNormal7, $pCenter0);

        $cellUb = $table->addCell($wC2, array_merge($cellMid, ['gridSpan' => 3]));
        $runUb = $cellUb->addTextRun($pLeft0);
        $runUb->addText($ubicacion, $fontBold7);
        if ($referencias !== '') {
            $runUb->addText('  ' . $referencias, $fontBold7);
        }

        // ===== FILA 3: PERITO... | (col 2-4 combinadas) =====
        $table->addRow(420);
        $table->addCell($wC1, $leftColCell)->addText("PERITO QUE\nLEVANTA EL ACTA", $fontNormal7, $pCenter0);
        $table->addCell($wC2, array_merge($cellMid, ['gridSpan' => 3]))->addText($peritoCompleto, $fontBold7, $pLeft0);

        // ===== FILA 4: PERSONAS DETENIDAS (4 columnas reales) =====
        $table->addRow(420);
        $table->addCell($wC1, $leftColCell)->addText("PERSONAS\nDETENIDAS", $fontNormal7, $pCenter0);

        // Col 2: SI / NO
        $cellPD2 = $table->addCell($wC2, $cellMid);
        $runPD2  = $cellPD2->addTextRun($pLeft0);
        $runPD2->addText('SI [', $fontNormal7);
        $runPD2->addText($hayDetenidos ? 'X' : ' ', $hayDetenidos ? $fontBold7 : $fontNormal7);
        $runPD2->addText(']   NO [', $fontNormal7);
        $runPD2->addText(!$hayDetenidos ? 'X' : ' ', (!$hayDetenidos) ? $fontBold7 : $fontNormal7);
        $runPD2->addText(']', $fontNormal7);

        // Col 3: FLAGRANCIA / CASO URGENTE
        $cellPD3 = $table->addCell($wC3, $cellMid);
        $runPD3  = $cellPD3->addTextRun($pLeft0);
        $runPD3->addText('FLAGRANCIA [', $fontNormal7);
        $runPD3->addText($flagrancia ? 'X' : ' ', $flagrancia ? $fontBold7 : $fontNormal7);
        $runPD3->addText(']   CASO URGENTE [', $fontNormal7);
        $runPD3->addText($casoUrgente ? 'X' : ' ', $casoUrgente ? $fontBold7 : $fontNormal7);
        $runPD3->addText(']', $fontNormal7);

        // Col 4: USO DE FUERZA FISICA SI / NO
        $cellPD4 = $table->addCell($wC4, $cellMid);
        $runPD4  = $cellPD4->addTextRun($pLeft0);
        $runPD4->addText('USO DE FUERZA FISICA  ', $fontNormal7);
        $runPD4->addText('SI [', $fontNormal7);
        $runPD4->addText($usoFuerzaSi ? 'X' : ' ', $usoFuerzaSi ? $fontBold7 : $fontNormal7);
        $runPD4->addText(']  NO [', $fontNormal7);
        $runPD4->addText(!$usoFuerzaSi ? 'X' : ' ', (!$usoFuerzaSi) ? $fontBold7 : $fontNormal7);
        $runPD4->addText(']', $fontNormal7);

        // ===== FILA 5: VEHÍCULOS INVOLUCRADOS (4 columnas) =====
        $table->addRow(420);
        $table->addCell($wC1, $leftColCell)->addText("VEHÍCULOS\nINVOLUCRADOS", $fontNormal7, $pCenter0);

        // Col 2: SI / NO
        $cellV2 = $table->addCell($wC2, $cellMid);
        $runV2  = $cellV2->addTextRun($pLeft0);
        $runV2->addText('SI [', $fontNormal7);
        $runV2->addText($hayVehiculos ? 'X' : ' ', $hayVehiculos ? $fontBold7 : $fontNormal7);
        $runV2->addText(']   NO [', $fontNormal7);
        $runV2->addText(!$hayVehiculos ? 'X' : ' ', (!$hayVehiculos) ? $fontBold7 : $fontNormal7);
        $runV2->addText(']', $fontNormal7);

        // Col 3: ROBADO / DAÑADO / ASEGURADO
        $cellV3 = $table->addCell($wC3, $cellMid);
        $runV3  = $cellV3->addTextRun($pLeft0);
        $runV3->addText('ROBADO [', $fontNormal7);
        $runV3->addText($robado ? 'X' : ' ', $robado ? $fontBold7 : $fontNormal7);
        $runV3->addText(']  DAÑADO [', $fontNormal7);
        $runV3->addText($vehDanado ? 'X' : ' ', $vehDanado ? $fontBold7 : $fontNormal7);
        $runV3->addText(']  ASEGURADO [', $fontNormal7);
        $runV3->addText($vehAsegurado ? 'X' : ' ', $vehAsegurado ? $fontBold7 : $fontNormal7);
        $runV3->addText(']', $fontNormal7);

        // Col 4: RECUPERADO / ABANDONADO
        $cellV4 = $table->addCell($wC4, $cellMid);
        $runV4  = $cellV4->addTextRun($pLeft0);
        $runV4->addText('RECUPERADO [', $fontNormal7);
        $runV4->addText($recuperado ? 'X' : ' ', $recuperado ? $fontBold7 : $fontNormal7);
        $runV4->addText(']  ABANDONADO [', $fontNormal7);
        $runV4->addText($abandonado ? 'X' : ' ', $abandonado ? $fontBold7 : $fontNormal7);
        $runV4->addText(']', $fontNormal7);


        $section->addTextBreak(1);

        // ===== TERCERA TABLA: FUNDAMENTO (centrado, MISMO COLOR, SIN BORDES / SIN MARCOS / SIN AIRE) =====
        $bgFund = 'EBE1D1';

        // TABLA sin bordes, centrada, layout fijo, mismo ancho que tu tabla anterior
        $tablaFund = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW, // ej. 9800 (mismo que tu tabla IPH)
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 0,
        ]);

        // CELDA sin bordes y sin padding
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

        // ===== Fila 1: FUNDAMENTO =====
        $tablaFund->addRow(320, ['exactHeight' => true, 'height' => 320]);
        $tablaFund->addCell($tableW, $cellFund)->addText('FUNDAMENTO', $fontBold7, $pCenter0);

        // ===== Fila 2: texto fundamento (2 líneas) =====
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

        // ===== CUARTA TABLA DE 2 FILAS: (1) ENCABEZADO GRIS  (2) TODO EL CONTENIDO ADENTRO =====

        $bgNarr = 'EBE1D1';

        // Hora (restar 10 min) y hora de arribo (tal cual)
        $horaBase    = \Carbon\Carbon::parse($hecho->hora);
        $horaMenos10 = $horaBase->copy()->subMinutes(10)->format('H:i');
        $horaArribo  = $horaBase->format('H:i');

        // Ubicación (calle + colonia + coords si hay)
        $ubiNarr = trim((string)($hecho->calle ?? ''));
        $colNarr = trim((string)($hecho->colonia ?? ''));
        if ($colNarr !== '') $ubiNarr .= ($ubiNarr !== '' ? ', ' : '') . $colNarr;

        if (!empty($hecho->lat) && !empty($hecho->lng)) {
            $coordsNarr = trim($hecho->lat . ', ' . $hecho->lng);
            $ubiNarr .= ($ubiNarr !== '' ? ', ' : '') . $coordsNarr;
        }

        $tipoHechoNarr = strtoupper((string)($hecho->tipo_hecho ?? ''));
        $unidadNarr     = (string)($hecho->unidad ?? '');

        // Tabla exterior (con bordes)
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

        // ---------- FILA 1 (GRIS) ----------
        $tablaNarr->addRow(420, ['exactHeight' => true, 'height' => 420]);
        $cellH = $tablaNarr->addCell($tableW, $cellHeaderNarr);

        $run = $cellH->addTextRun($pCenter0);
        $run->addText('NARRATIVA DE LOS HECHOS', $fontBold7, $pCenter0);
        $run->addTextBreak();
        $run->addText('(Qué, Quién, Cuándo, Dónde, Cómo, Porqué, Con qué)', $fontNormal7, $pCenter0);

        // ---------- FILA 2 (AQUÍ VA TODO) ----------
        $tablaNarr->addRow(1200, ['exactHeight' => false]);
        $cellB = $tablaNarr->addCell($tableW, $cellBodyNarr);

        // ====== NARRATIVA (PÁRRAFO) ======
        $narrativaTxt =
            "                 Siendo las {$horaMenos10} horas me encontraba de recorrido vigilancia y disuasión del Delito sobre el Periférico Independencia # 5000, col. Sentimientos de la Nación, cuando por medio de la base de radio C-5i, reporto un hecho de tránsito: ({$tipoHechoNarr}) en {$ubiNarr}, motivo por el cual me traslade al lugar mencionado abordo de la unidad {$unidadNarr}, arribando a las {$horaArribo} horas.";

        $cellB->addText($narrativaTxt, [], [
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
        ]);

        $cellB->addTextBreak(1);


        // ===================== TODO LO DEMÁS VA ADENTRO DE ESTA MISMA CELDA =====================


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

        // Fuente SOLO para esta tabla
        $fontAuxTitle = $fontBold7;                       // TÍTULO (fila 1)
        $fontAuxSmall = ['name' => 'Arial', 'size' => 6]; // texto (fila 2)

        // Párrafos “apretados” (sin aire arriba/abajo)
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

        // OJO: baja cellMargin para que no “infle” la tabla
        $tablaAux = $section->addTable([
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'width'       => $tableW,
            'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 20,   // <-- antes 80, esto es lo que más mete “aire”
        ]);

        // ---------- FILA 1 (GRIS) ----------
        $tablaAux->addRow(260, ['exactHeight' => true, 'height' => 260]); // <-- más bajita
        $cellH = $tablaAux->addCell($tableW, [
            'bgColor' => $bgAux,
            'valign'  => 'center',
            'vMerge'  => null,
        ]);

        $cellH->addText('AUXILIO PRESTADO A :', $fontAuxTitle, $pAuxCenterTight);

        // ---------- FILA 2 (BLANCA) ----------
        $tablaAux->addRow(300, ['exactHeight' => true, 'height' => 300]); // <-- exacta para que quede “justo”
        $cellB = $tablaAux->addCell($tableW, ['valign' => 'center']);

        $textoAux =
            "VÍCTIMA(S) [   ]   OFENDIDO(S) [   ]   DENUNCIANTE(S) [   ]   TESTIGO(S) [   ]   DETENIDO(S) [   ]   NO APLICA [ X ]";

        // sangría mínima (si aún quieres): 1 tab o quítalo
        $textoAuxFinal = $textoAux; // <-- sin tab para que no se vaya a la derecha

        $cellB->addText($textoAuxFinal, $fontAuxSmall, $pAuxLeftTight);

        $section->addTextBreak();


        // ===== TABLA 2 FILAS: TIPO DE AUXILIO =====

        $bgAux = 'EBE1D1';

        // Fuente SOLO para esta tabla
        $fontAuxTitle = $fontBold7;                       // TÍTULO (fila 1)
        $fontAuxSmall = ['name' => 'Arial', 'size' => 6]; // texto (fila 2)

        // Párrafos “apretados”
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

        // ---------- FILA 1 (GRIS) ----------
        $tablaTipoAux->addRow(260, ['exactHeight' => true, 'height' => 260]);
        $cellH = $tablaTipoAux->addCell($tableW, ['bgColor' => $bgAux, 'valign' => 'center']);
        $cellH->addText('TIPO DE AUXILIO :', $fontAuxTitle, $pAuxCenterTight);

        // ---------- FILA 2 (BLANCA) ----------
        $tablaTipoAux->addRow(320, ['exactHeight' => true, 'height' => 320]);
        $cellB = $tablaTipoAux->addCell($tableW, ['valign' => 'center']);

        $textoTipoAux =
            "PRIMEROS AUXILIOS [   ]        TRASLADO [   ]         CUSTODIA POLICIACA [   ]          OTRO [   ]  especifique: ________";

        $cellB->addText($textoTipoAux, $fontAuxSmall, $pAuxLeftTight);

        $section->addTextBreak();


        // ===== TABLA 2 FILAS: TRASLADO o CANALIZACIONES =====

        $bgAux = 'EBE1D1';

        // Fuente SOLO para esta tabla
        $fontAuxTitle = $fontBold7;                       // TÍTULO (fila 1)
        $fontAuxSmall = ['name' => 'Arial', 'size' => 6]; // texto (fila 2)

        // Párrafos “apretados”
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

        // ---------- FILA 1 (GRIS) ----------
        $tablaTraslado->addRow(260, ['exactHeight' => true, 'height' => 260]);
        $cellH = $tablaTraslado->addCell($tableW, ['bgColor' => $bgAux, 'valign' => 'center']);
        $cellH->addText('TRASLADO o CANALIZACIONES', $fontAuxTitle, $pAuxCenterTight);

        // ---------- FILA 2 (BLANCA) ----------
        $tablaTraslado->addRow(320, ['exactHeight' => true, 'height' => 320]);
        $cellB = $tablaTraslado->addCell($tableW, ['valign' => 'center']);

        $textoTraslado =
            "HOSPITAL [   ]         DOMICILIO [   ]        CENTRO DE REHABILITACIÓN [   ]        CAVIZ [   ]       OTRO [   ]  especifique: ________";

        $cellB->addText($textoTraslado, $fontAuxSmall, $pAuxLeftTight);

        $section->addTextBreak();

        // ===== TABLA 2 FILAS: INSPECCIONES REALIZADAS =====

        $bgAux = 'EBE1D1';

        // Fuente SOLO para esta tabla
        $fontAuxTitle = $fontBold7;                       // TÍTULO (fila 1)
        $fontAuxSmall = ['name' => 'Arial', 'size' => 6]; // texto (fila 2)

        // Párrafos “apretados”
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

        // ---------- FILA 1 (GRIS) ----------
        $tablaInspecciones->addRow(260, ['exactHeight' => true, 'height' => 260]);
        $cellH = $tablaInspecciones->addCell($tableW, ['bgColor' => $bgAux, 'valign' => 'center']);
        $cellH->addText('INSPECCIONES REALIZADAS', $fontAuxTitle, $pAuxCenterTight);

        // ---------- FILA 2 (BLANCA) ----------
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

        // Fila única (GRIS) apretada
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

        // Fila única (GRIS) apretada
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
        // ===== VEHÍCULOS INVOLUCRADOS — CUADRO PERFECTO (1 tabla por vehículo) =====
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

        // ---- helper: celda con texto (soporta gridSpan y bgColor) ----
        $addCellTxt4 = function($table, $w, $txt, $font, $bg = null, $span = 1) use ($pCenterTight) {
            $style = ['valign' => 'center'];
            if ($bg !== null) $style['bgColor'] = $bg;
            if ($span > 1)    $style['gridSpan'] = $span;

            $cell = $table->addCell($w, $style);
            $cell->addText((string)$txt, $font, $pCenterTight);
            return $cell;
        };

        // ---- 4 columnas fijas (para que SIEMPRE sea “cuadro” parejo) ----
        $vC1 = (int)round($tableW * 0.22);            // etiqueta izq
        $vC2 = (int)round($tableW * 0.28);            // valor izq
        $vC3 = (int)round($tableW * 0.22);            // etiqueta der
        $vC4 = $tableW - ($vC1 + $vC2 + $vC3);        // valor der

        foreach ($hecho->vehiculos as $i => $v) {

            // tabla por vehículo
            $tVeh = $section->addTable([
                'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
                'width'       => $tableW,
                'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
                'borderSize'  => 6,
                'borderColor' => '000000',
                'cellMargin'  => 0, // <- para que no tenga “aire” arriba/abajo
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
            // OJO: aquí pediste COLOR sin gris en la etiqueta (así lo dejo)
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
            $tVeh->addRow(320, ['exactHeight' => true, 'height' => 320]); // un poco más alta para texto largo
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
