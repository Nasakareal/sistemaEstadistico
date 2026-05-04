<?php

namespace App\Services;

use App\Models\Hechos;
use Carbon\Carbon;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;

class BitacoraGenerator
{
    private const UNIDAD_SINIESTROS_ID = 1;

    public function generar(string $fecha): string
    {
        $tz = 'America/Mexico_City';

        $inicio = Carbon::parse($fecha, $tz)->setTime(18, 0)->subDay();
        $fin    = Carbon::parse($fecha, $tz)->setTime(18, 0);

        $hechos = Hechos::with(['vehiculos', 'lesionados'])
            ->where('unidad_org_id', self::UNIDAD_SINIESTROS_ID)
            ->whereBetween('created_at', [$inicio, $fin])
            ->orderByRaw("COALESCE(fecha, DATE(created_at)) asc")
            ->orderByRaw("COALESCE(hora, TIME(created_at)) asc")
            ->orderBy('created_at', 'asc')
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

        $pCenter0 = ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0];
        $pLeft0   = ['alignment' => Jc::LEFT,   'spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0];

        $phpWord->addTableStyle('EncabezadoTablaBitacora', [
            'borderSize'  => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 0,
            'alignment'   => JcTable::CENTER,
        ]);

        $tEnc = $section->addTable('EncabezadoTablaBitacora');

        $tEnc->addRow(420);
        $tEnc->addCell(9000, ['valign' => 'center'])->addImage(public_path('ssp.jpg'), [
            'width'     => 140,
            'alignment' => Jc::LEFT
        ]);
        $tEnc->addCell(9000, ['valign' => 'center'])->addImage(public_path('vialidad.png'), [
            'width'     => 70,
            'alignment' => Jc::RIGHT
        ]);

        $tEnc->addRow(420);
        $cTitulo = $tEnc->addCell(18000, ['gridSpan' => 2, 'valign' => 'center']);
        $trTitulo = $cTitulo->addTextRun($pCenter0);
        $trTitulo->addText('BITÁCORA', ['bold' => true, 'size' => 12]);
        $trTitulo->addText('    ', ['size' => 12]);
        $trTitulo->addText('UNIDAD DE ATENCIÓN A SINIESTROS', ['bold' => true, 'size' => 12]);

        $dia  = Carbon::parse($fecha, $tz)->format('d');
        $mes  = strtoupper(Carbon::parse($fecha, $tz)->translatedFormat('F'));
        $anio = Carbon::parse($fecha, $tz)->format('Y');

        $phpWord->addTableStyle('FechaDerechaTabla', [
            'borderSize'  => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 0,
            'alignment'   => JcTable::CENTER,
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
            'alignment'   => JcTable::CENTER,
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
            $hora = '';
            if (!empty($hecho->hora)) {
                $hora = Carbon::parse($hecho->hora, $tz)->format('H:i');
            } else {
                $hora = Carbon::parse($hecho->created_at, $tz)->format('H:i');
            }

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
                if ($vConGrua) {
                    $grua = strtoupper(trim((string)$vConGrua->grua));
                }
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

        $turnoSvc = app(TurnoService::class);
        $momentoTurno = $fin->copy()->subMinute();
        $turnoActivo = $turnoSvc->turnoActivoEn($momentoTurno);

        $turnoLetra = 'B';
        if ($turnoActivo) {
            $nombreTurno = strtoupper(trim((string)($turnoActivo->nombre ?? '')));
            $slugTurno = strtoupper(trim((string)($turnoActivo->slug ?? '')));
            if (str_contains($nombreTurno, ' A') || $nombreTurno === 'A' || str_contains($slugTurno, 'A')) {
                $turnoLetra = 'A';
            }
        }

        if ($turnoLetra === 'A') {
            $nombreFirma = 'FERNANDO RUBALCAVA RIVERA';
        } else {
            $nombreFirma = 'JORGE ARMANDO MORALES PEREZ';
        }

        $section->addTextBreak(3);

        $section->addText('ATENTAMENTE.', ['bold' => true, 'size' => 10], [
            'alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0
        ]);

        $section->addText('COMANDANTE DE TURNO.', ['bold' => true, 'size' => 10], [
            'alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0
        ]);

        $section->addTextBreak(3);

        $section->addText($nombreFirma, ['bold' => true, 'size' => 10], [
            'alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0
        ]);

        $filename = "bitacora_{$fecha}.docx";
        $tempPath = storage_path("app/tmp/{$filename}");

        if (!is_dir(dirname($tempPath))) {
            @mkdir(dirname($tempPath), 0775, true);
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        return $tempPath;
    }
}
