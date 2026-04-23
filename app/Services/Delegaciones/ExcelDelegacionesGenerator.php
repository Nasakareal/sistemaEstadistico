<?php

namespace App\Services\Delegaciones;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Services\Delegaciones\Hojas\MoreliaRpSheetService;
use App\Services\Delegaciones\Hojas\NovRelSheetService;
use App\Services\Delegaciones\Hojas\RegionalSheetService;

class ExcelDelegacionesGenerator
{
    public function generar(string $fecha): string
    {
        $fecha = Carbon::parse($fecha, 'America/Mexico_City')->format('Y-m-d');

        $spreadsheet = new Spreadsheet();

        // HOJA 1
        $hojaMoreliaRp = $spreadsheet->getActiveSheet();
        $hojaMoreliaRp->setTitle('MORELIA_RP');
        app(MoreliaRpSheetService::class)->generar($hojaMoreliaRp, $fecha);

        // HOJA 2
        $hojaNovRel = $spreadsheet->createSheet();
        $hojaNovRel->setTitle('NOV_REL');
        app(NovRelSheetService::class)->generar($hojaNovRel, $fecha);

        // HOJA 3 (TOTAL)
        $hojaTotal = $spreadsheet->createSheet();
        $hojaTotal->setTitle('TOTAL');
        app(RegionalSheetService::class)->generar($hojaTotal, $fecha, 'TOTAL');

        // HOJAS REGIONALES
        $hojasRegionales = [
            'MORELIA',
            'JIQUILPAN',
            'ZAMORA',
            'LA PIEDAD',
            'URUAPAN',
            'APATZINGAN',
            'HUETAMO',
            'COALCOMAN',
            'ZITACUARO',
            'LAZARO CARDENAS',
        ];

        foreach ($hojasRegionales as $nombreHoja) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($nombreHoja);
            app(RegionalSheetService::class)->generar($sheet, $fecha, $nombreHoja);
        }

        $tempPath = storage_path('app/temp_excel_delegaciones_' . $fecha . '.xlsx');

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        return $tempPath;
    }
}
