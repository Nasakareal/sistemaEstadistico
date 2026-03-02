<?php

namespace App\Services;

use App\Models\Conductor;
use App\Models\Hechos;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\TblWidth;

class MiniParteGenerator
{
    public function generar(string $fecha): string
    {
        $tz = 'America/Mexico_City';

        $inicio = Carbon::parse($fecha, $tz)->setTime(18, 0)->subDay();
        $fin    = Carbon::parse($fecha, $tz)->setTime(18, 0);

        $hechos = Hechos::with(['vehiculos.conductores', 'lesionados'])
            ->whereBetween('created_at', [$inicio, $fin])
            ->orderByRaw("COALESCE(fecha, DATE(created_at)) asc")
            ->orderByRaw("COALESCE(hora, TIME(created_at)) asc")
            ->orderBy('created_at', 'asc')
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

        $resumen['LESIONADOS'] = $hechos->sum(fn ($h) => $h->lesionados->count());

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

            $sit = strtoupper((string)($h->situacion ?? ''));
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

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'pageSizeW'    => Converter::inchToTwip(8.5),
            'pageSizeH'    => Converter::inchToTwip(11),
            'marginTop'    => 234,
            'marginRight'  => 1134,
            'marginBottom' => 1134,
            'marginLeft'   => 1134,
        ]);

        $phpWord->addTableStyle('EncabezadoTabla', [
            'borderSize'  => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 0,
            'alignment'   => JcTable::CENTER,
        ]);

        $enc = $section->addTable('EncabezadoTabla');
        $enc->addRow();
        $enc->addCell(5000, ['valign' => 'center'])
            ->addImage(public_path('ssp.jpg'), ['width' => 120, 'alignment' => Jc::LEFT]);
        $enc->addCell(5000, ['valign' => 'center'])
            ->addImage(public_path('vialidad.png'), ['width' => 50, 'alignment' => Jc::RIGHT]);

        $phpWord->addTableStyle('TablaTituloFecha', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 50,
            'alignment'   => JcTable::CENTER,
        ]);

        $fechaFmt = strtoupper(Carbon::parse($fecha, $tz)->translatedFormat('d \d\e F \d\e Y'));

        $titu = $section->addTable('TablaTituloFecha');
        $titu->addRow(null, ['exactHeight' => true, 'height' => 300]);
        $titu->addCell(null, ['valign' => 'center'])
            ->addText('CONCENTRADO NOVEDADES DEL DÍA', ['bold' => true], ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]);
        $titu->addCell(null, ['valign' => 'center'])
            ->addText($fechaFmt, ['bold' => true], ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]);

        $section->addText(' ', [], ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 0.5]);

        $section->addText('HECHOS OCURRIDOS EN DIFERENTES PARTES DE LA CIUDAD.', ['bold' => true], [
            'alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0
        ]);

        $section->addText(' ', [], ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 0.5]);

        $phpWord->addTableStyle('TablaResumenMiniParte', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 40,
            'alignment'   => JcTable::CENTER,
        ]);

        $tabla = $section->addTable('TablaResumenMiniParte');

        $fmt = function ($n) { return str_pad((int)$n, 2, '0', STR_PAD_LEFT); };

        $datos = [
            [$fmt($resumen['CHOQUES']),                'CHOQUES',                   '', $fmt($resumen['LESIONADOS']),        'LESIONADOS'],
            [$fmt($resumen['ATROPELLOS']),             'ATROPELLOS',                '', $fmt($resumen['DEFUNCIONES']),       'DEFUNCIONES'],
            [$fmt($resumen['VOLCADURAS']),             'VOLCADURAS',                '', $fmt($resumen['PENDIENTES']),        'PENDIENTES'],
            [$fmt($resumen['SALIDA DE SUP. DE ROD.']), 'SALIDA DE SUP. DE ROD.',    '', $fmt($resumen['RESUELTOS']),         'RESUELTOS'],
            [$fmt($resumen['SUBIDA AL CAMELLÓN']),     'SUBIDA AL CAMELLÓN',        '', $fmt($resumen['VEH_RECUPERADOS']),   'VEHICULOS RECUPERADOS'],
            [$fmt($resumen['CAIDA A LA CUNETA']),      'CAIDA A LA CUNETA',         '', $fmt($resumen['PERS_MP_FC']),        'PERS. PRESENTADAS AL MP FC'],
            [$fmt($resumen['CAIDA DE MOTO']),          'CAIDA DE MOTO',             '', $fmt($resumen['PERS_BARANDILLAS']),  'PERS. PRESENTADAS A BARANDILLAS'],
            [$fmt($resumen['CAIDA A ZANJA']),          'CAIDA A ZANJA',             '', $fmt($resumen['SERV_GRUAS']),        'SERVICIOS DE GRÚAS'],
            [$fmt($resumen['CAIDA A CPO. DE AGUA']),   'CAIDA A CPO. DE AGUA',      '', $fmt($resumen['AUTOS_CORRALON']),    'AUTOMOVILES REMITIDOS A CORRALON'],
            [$fmt($resumen['INCIDENTE DE TTO.']),      'INCIDENTE DE TTO.',         '', $fmt($resumen['MOTOS_CORRALON']),    'MOTOCICLETAS REMITIDAS A CORRALON'],
            [$fmt($resumen['REPORTES']),               'REPORTE',                   '', $fmt($resumen['ANTECEDENTES_VEH']),  'ANTECEDENTES VEHICULOS'],
            [$fmt($resumen['PERSONAS_MP'] ?? 0),       'PERSONAS AL M.P.',          '', $fmt($resumen['ANTECEDENTES_MOTOS']), 'ANTECEDENTES MOTOCICLETAS'],
            [$fmt($resumen['TURNO_MP']),               'TURNADOS AL M.P.',          '', $fmt($resumen['ANTECEDENTES_PERS']), 'ANTECEDENTES A PERSONAS'],
            [$fmt($resumen['DISPOSITIVOS']),           'DISPOSITIVOS REALIZADOS',   '', $fmt($resumen['DANIOS_VIAS_COM']),   'DAÑOS EN VIAS DE COMUNICACIÓN'],
            [$fmt($resumen['VEH_OFICIALES']),          'VEHICULOS OFICIALES',       '', $fmt($resumen['ARMAS_ASEGURADAS']),  'ARMAS ASEGURADAS'],
            [$fmt($resumen['VEH_INVOL_HT']),           'VEHICULOS INVOLUCRADO HT',  '', $fmt($resumen['DROGA_ASEGURADA']),   'DROGA ASEGURADA'],
            ['',                                       '',                           '', $fmt($resumen['VICTIMAS_TOTALES']),  'VICTIMAS (TOTALES)'],
            ['',                                       '',                           '', $fmt($resumen['EXAMENES_MANEJO']),   'EXAMENES DE MANEJO APLICADOS'],
        ];

        foreach ($datos as $fila) {
            $tabla->addRow(null, ['exactHeight' => true, 'height' => 300]);

            $tabla->addCell(null, ['valign' => 'center'])->addText($fila[0], null, ['spaceBefore' => 0, 'spaceAfter' => 0]);
            $tabla->addCell(null, ['valign' => 'center'])->addText($fila[1], null, ['spaceBefore' => 0, 'spaceAfter' => 0]);

            $tabla->addCell(null, [
                'valign' => 'center',
                'borderTopSize' => 0,
                'borderBottomSize' => 0,
                'borderLeftSize' => 8,
                'borderRightSize' => 8,
                'borderColor' => '000000',
            ])->addText('', null, ['spaceBefore' => 0, 'spaceAfter' => 0]);

            $tabla->addCell(null, ['valign' => 'center'])->addText($fila[3], null, ['spaceBefore' => 0, 'spaceAfter' => 0]);
            $tabla->addCell(null, ['valign' => 'center'])->addText($fila[4], null, ['spaceBefore' => 0, 'spaceAfter' => 0]);
        }

        $section->addText(' ', [], ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 0.5]);

        $phpWord->addTableStyle('TablaDaniosMateriales', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 1,
            'alignment'   => JcTable::CENTER,
        ]);

        $tablaDanios = $section->addTable('TablaDaniosMateriales');

        $montoDanios = 0.0;
        foreach ($hechos as $h) {
            foreach ($h->vehiculos as $v) {
                $montoDanios += (float)($v->monto_danos ?? 0);
            }
        }

        $montoFormateado = '$ ' . number_format($montoDanios, 2, '.', ',');

        $tablaDanios->addRow(null, ['exactHeight' => true, 'height' => 400]);
        $tablaDanios->addCell(8000, ['valign' => 'center'])->addText(
            'DAÑOS MATERIALES DE LOS HECHOS DE TTO. CANTIDAD APROX.',
            ['bold' => true],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
        );
        $tablaDanios->addCell(2000, ['valign' => 'center'])->addText(
            $montoFormateado,
            ['bold' => true],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
        );

        $section->addText(' ', [], ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 0.5]);

        $phpWord->addTableStyle('TablaInfracciones', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 1,
            'alignment'   => JcTable::CENTER,
        ]);

        $tablaInfracciones = $section->addTable('TablaInfracciones');
        $tablaInfracciones->addRow(null, ['exactHeight' => true, 'height' => 400]);
        $tablaInfracciones->addCell(8000, ['valign' => 'center'])->addText(
            'INFRACCIONES ELABORADAS.',
            ['bold' => true],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
        );
        $tablaInfracciones->addCell(2000, ['valign' => 'center'])->addText(
            '0',
            ['bold' => true],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
        );

        $section->addText(' ', [], ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 0.5]);

        $phpWord->addTableStyle('TablaKilometros', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 1,
            'alignment'   => JcTable::CENTER,
        ]);

        $tablaKilometros = $section->addTable('TablaKilometros');
        $tablaKilometros->addRow(null, ['exactHeight' => true, 'height' => 400]);
        $tablaKilometros->addCell(8000, ['valign' => 'center'])->addText(
            'KILÓMETROS RECORRIDOS POR LAS UNIDADES.',
            ['bold' => true],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
        );
        $tablaKilometros->addCell(2000, ['valign' => 'center'])->addText(
            '0000',
            ['bold' => true],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
        );

        $hechoIds = $hechos->pluck('id');

        $vehiculoIds = DB::table('hecho_vehiculo')
            ->whereIn('hecho_id', $hechoIds)
            ->pluck('vehiculo_id');

        $conductorIds = DB::table('vehiculo_conductor')
            ->whereIn('vehiculo_id', $vehiculoIds)
            ->pluck('conductor_id');

        $conductores = Conductor::whereIn('id', $conductorIds)->get();

        $section->addText(' ', [], ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 0.5]);

        $phpWord->addTableStyle('TablaOcupacionConductores', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 60,
            'alignment'   => JcTable::CENTER,
            'tblWidth'    => 9000,
            'unit'        => TblWidth::TWIP,
        ]);

        $tablaOcupacion = $section->addTable('TablaOcupacionConductores');

        $tablaOcupacion->addRow(null, ['exactHeight' => true, 'height' => 100]);
        $tablaOcupacion->addCell(null, ['gridSpan' => 8, 'valign' => 'center'])
            ->addText('OCUPACIÓN CONDUCTORES', ['bold' => true], ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]);

        $ocupaciones = ['EMPLEADO' => 0, 'CHOFER' => 0, 'COMERCIANTE' => 0, 'OTRO' => 0];

        foreach ($conductores as $c) {
            $o = strtoupper(trim((string)($c->ocupacion ?? 'OTRO')));
            if (str_contains($o, 'EMPLEADO')) $ocupaciones['EMPLEADO']++;
            elseif (str_contains($o, 'CHOFER')) $ocupaciones['CHOFER']++;
            elseif (str_contains($o, 'COMERCIANTE')) $ocupaciones['COMERCIANTE']++;
            else $ocupaciones['OTRO']++;
        }

        $tablaOcupacion->addRow(null, ['exactHeight' => true, 'height' => 100]);

        foreach ([
            ['EMPLEADOS',    $ocupaciones['EMPLEADO']],
            ['CHOFERES',     $ocupaciones['CHOFER']],
            ['COMERCIANTES', $ocupaciones['COMERCIANTE']],
            ['OTROS',        $ocupaciones['OTRO']],
        ] as [$label, $count]) {
            $tablaOcupacion->addCell(null, ['valign' => 'center'])
                ->addText($label, [], ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]);
            $tablaOcupacion->addCell(null, ['valign' => 'center'])
                ->addText(str_pad((int)$count, 2, '0', STR_PAD_LEFT), ['bold' => true], ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]);
        }

        $phpWord->addTableStyle('TablaFirmaSubdirector', [
            'borderSize'  => 0,
            'borderColor' => 'ffffff',
            'alignment'   => JcTable::CENTER,
            'cellMargin'  => 80,
        ]);

        $tableFirma = $section->addTable('TablaFirmaSubdirector');
        $tableFirma->addRow();
        $tableFirma->addCell(9000, ['valign' => 'center'])->addText(
            'SUBDIRECTOR DE LA UNIDAD DE ATENCIÓN A SINIESTROS.',
            ['bold' => true],
            ['alignment' => Jc::CENTER]
        );

        $tableFirma->addRow();
        $tableFirma->addCell(9000)->addTextBreak(2);

        $tableFirma->addRow();
        $tableFirma->addCell(9000, ['valign' => 'center'])->addText(
            'LIC. JULIO ERNESTO BAUTISTA JIMENEZ',
            ['bold' => true],
            ['alignment' => Jc::CENTER]
        );

        $filename = "mini_parte_{$fecha}.docx";
        $tempPath = storage_path("app/tmp/{$filename}");

        if (!is_dir(dirname($tempPath))) {
            @mkdir(dirname($tempPath), 0775, true);
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        return $tempPath;
    }
}
