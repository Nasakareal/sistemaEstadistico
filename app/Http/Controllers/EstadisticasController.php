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

        // Hechos + relaciones necesarias
        $hechos = Hechos::with(['vehiculos.conductores', 'lesionados'])
            ->whereBetween('created_at', [$inicio, $fin])
            ->get();

        // ===== CONTADORES =====
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

        // Contar lesionados (directo de la relación)
        $resumen['LESIONADOS'] = $hechos->sum(fn($h) => $h->lesionados->count());

        foreach ($hechos as $h) {
            // Tipo de hecho -> lado izquierdo
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

            // Situación
            $sit = strtoupper($h->situacion);
            if ($sit === 'PENDIENTE') $resumen['PENDIENTES']++;
            if ($sit === 'RESUELTO')  $resumen['RESUELTOS']++;

            // Antecedentes en vehículos y conductores
            foreach ($h->vehiculos as $v) {
                if (!empty($v->antecedente_vehiculo) && (int)$v->antecedente_vehiculo === 1) {
                    $resumen['ANTECEDENTES_VEH']++;
                }

                // si quisieras distinguir motos:
                // if (Str::contains(strtolower($v->tipo), 'moto') && $v->antecedente_vehiculo) { $resumen['ANTECEDENTES_MOTOS']++; }

                foreach ($v->conductores as $c) {
                    if (!empty($c->antecedentes) && (int)$c->antecedentes === 1) {
                        $resumen['ANTECEDENTES_PERS']++;
                    }
                }
            }
        }

        // ===== DOCUMENTO =====
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

        // Encabezado
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

        // Título/fecha
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

        // Espacio fino
        $section->addText(' ', [], ['spaceBefore'=>0,'spaceAfter'=>0,'lineHeight'=>0.5]);

        // Subtítulo
        $section->addText('HECHOS OCURRIDOS EN DIFERENTES PARTES DE LA CIUDAD.', ['bold'=>true], [
            'alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore'=>0, 'spaceAfter'=>0
        ]);
        $section->addText(' ', [], ['spaceBefore'=>0,'spaceAfter'=>0,'lineHeight'=>0.5]);

        // Tabla central
        $phpWord->addTableStyle('TablaResumenMiniParte', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 40,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);
        $tabla = $section->addTable('TablaResumenMiniParte');

        // Helper formateo
        $fmt = function ($n) { return str_pad((int)$n, 2, '0', STR_PAD_LEFT); };

        // Filas (todas, siempre)
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

        // Espacio antes de la tabla de daños
        $section->addText(' ', [], ['spaceBefore'=>0,'spaceAfter'=>0,'lineHeight'=>0.5]);

        // Nueva tabla para daños materiales
        $phpWord->addTableStyle('TablaDaniosMateriales', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 1,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);
        $tablaDanios = $section->addTable('TablaDaniosMateriales');

        // Calcular monto total de daños (sumar monto_danos de todos los vehículos involucrados en los hechos del día)
        $montoDanios = 0;
        foreach ($hechos as $h) {
            foreach ($h->vehiculos as $v) {
                $montoDanios += floatval($v->monto_danos ?? 0);
            }
        }

        // Formatear monto 
        $montoFormateado = '$ ' . number_format($montoDanios, 2, '.', ',');

        // Fila de la tabla
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

        // Espacio antes de la tabla de infracciones
        $section->addText(' ', [], ['spaceBefore'=>0,'spaceAfter'=>0,'lineHeight'=>0.5]);

        // Estilo y tabla de infracciones
        $phpWord->addTableStyle('TablaInfracciones', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 1,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);
        $tablaInfracciones = $section->addTable('TablaInfracciones');

        // Fila de la tabla (valor fijo en cero)
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

        // Espacio antes de la tabla de kilómetros
        $section->addText(' ', [], ['spaceBefore'=>0,'spaceAfter'=>0,'lineHeight'=>0.5]);

        // Estilo y tabla de kilómetros recorridos
        $phpWord->addTableStyle('TablaKilometros', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 1,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);
        $tablaKilometros = $section->addTable('TablaKilometros');

        // Fila de la tabla (valor fijo en 0000)
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


        // Obtener los IDs de hechos
        $hechoIds = $hechos->pluck('id');

        // Obtener vehículos de los hechos
        $vehiculoIds = DB::table('hecho_vehiculo')
            ->whereIn('hecho_id', $hechoIds)
            ->pluck('vehiculo_id');

        // Obtener conductores de esos vehículos
        $conductorIds = DB::table('vehiculo_conductor')
            ->whereIn('vehiculo_id', $vehiculoIds)
            ->pluck('conductor_id');

        // Obtener los objetos Conductor
        $conductores = Conductor::whereIn('id', $conductorIds)->get();

        // Espacio fino antes de la tabla
        $section->addText(' ', [], ['spaceBefore'=>0,'spaceAfter'=>0,'lineHeight'=>0.5]);

        // Estilo de tabla auto‑ajustada
        $phpWord->addTableStyle('TablaOcupacionConductores', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 60,
            'alignment'   => Jc::CENTER,
            'tblWidth'    => 9000,
            'unit'        => TblWidth::TWIP,
        ]);

        $tablaOcupacion = $section->addTable('TablaOcupacionConductores');

        // Fila de título (span de 8 celdas, ancho automático)
        $tablaOcupacion->addRow(null, ['exactHeight'=>true,'height'=>100]);
        $tablaOcupacion->addCell(null, ['gridSpan'=>8,'valign'=>'center'])
            ->addText(
                'OCUPACIÓN CONDUCTORES',
                ['bold'=>true],
                ['alignment'=>Jc::CENTER,'spaceBefore'=>0,'spaceAfter'=>0]
            );

        // Inicializar contadores
        $ocupaciones = ['EMPLEADO'=>0,'CHOFER'=>0,'COMERCIANTE'=>0,'OTRO'=>0];
        foreach ($conductores as $c) {
            $o = strtoupper(trim($c->ocupacion ?? 'OTRO'));
            if (str_contains($o, 'EMPLEADO'))      $ocupaciones['EMPLEADO']++;
            elseif (str_contains($o, 'CHOFER'))     $ocupaciones['CHOFER']++;
            elseif (str_contains($o, 'COMERCIANTE'))$ocupaciones['COMERCIANTE']++;
            else                                   $ocupaciones['OTRO']++;
        }

        // Fila con etiquetas y valores, todas celdas auto‑ancho
        $tablaOcupacion->addRow(null, ['exactHeight'=>true,'height'=>100]);
        foreach ([
            ['EMPLEADOS',    $ocupaciones['EMPLEADO']],
            ['CHOFERES',     $ocupaciones['CHOFER']],
            ['COMERCIANTES', $ocupaciones['COMERCIANTE']],
            ['OTROS',        $ocupaciones['OTRO']],
        ] as [$label, $count]) {
            // etiqueta
            $tablaOcupacion->addCell(null, ['valign'=>'center'])
                ->addText($label, [], ['alignment'=>Jc::CENTER,'spaceBefore'=>0,'spaceAfter'=>0]);
            // valor
            $tablaOcupacion->addCell(null, ['valign'=>'center'])
                ->addText(str_pad($count,2,'0',STR_PAD_LEFT), ['bold'=>true], ['alignment'=>Jc::CENTER,'spaceBefore'=>0,'spaceAfter'=>0]);
        }


        // Estilo para tabla central
        // Tabla sin bordes, centrada
        $phpWord->addTableStyle('TablaFirmaSubdirector', [
            'borderSize' => 0,
            'borderColor' => 'ffffff',
            'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
            'cellMargin' => 80,
        ]);

        $tableFirma = $section->addTable('TablaFirmaSubdirector');

        // Fila 1: cargo
        $tableFirma->addRow();
        $tableFirma->addCell(9000, ['valign' => 'center'])->addText(
            'SUBDIRECTOR DE LA UNIDAD DE ATENCIÓN A SINIESTROS.',
            ['bold' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        // Fila 2: espacio visual
        $tableFirma->addRow();
        $tableFirma->addCell(9000)->addTextBreak(2);

        // Fila 3: nombre
        $tableFirma->addRow();
        $tableFirma->addCell(9000, ['valign' => 'center'])->addText(
            'LIC. LUIS ALBERTO NÚÑEZ RAZO',
            ['bold' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );


        // Guardar y descargar
        $filename = "mini_parte_{$fecha}.docx";
        $tempPath = storage_path("app/public/{$filename}");
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }



}
