<?php

namespace App\Services\VialidadesUrbanas;

use App\Services\VialidadesUrbanas\Hojas\ArmamentoSheetService;
use App\Services\VialidadesUrbanas\Hojas\CampanasSheetService;
use App\Services\VialidadesUrbanas\Hojas\CarruselSheetService;
use App\Services\VialidadesUrbanas\Hojas\EstadoFuerzaSheetService;
use App\Services\VialidadesUrbanas\Hojas\EstadoVehicularSheetService;
use App\Services\VialidadesUrbanas\Hojas\NovRelSheetService;
use App\Services\VialidadesUrbanas\Hojas\OperativosSheetService;
use App\Services\VialidadesUrbanas\Hojas\TotalSheetService;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelVialidadesUrbanasGenerator
{
    public const HOJAS_DIARIAS = [
        'EST. FUR' => EstadoFuerzaSheetService::class,
        'EST. VEH' => EstadoVehicularSheetService::class,
        'ARM.' => ArmamentoSheetService::class,
        'CARRUSEL' => CarruselSheetService::class,
        'TOTAL' => TotalSheetService::class,
        'NOV. REL' => NovRelSheetService::class,
        'OPERATIVOS' => OperativosSheetService::class,
        'CAMPAÑAS' => CampanasSheetService::class,
    ];

    public function generar(string $fecha): string
    {
        $fecha = Carbon::parse($fecha, 'America/Mexico_City')->format('Y-m-d');
        [$inicio, $fin] = $this->rangoCorte($fecha);

        $spreadsheet = new Spreadsheet();
        $index = 0;

        foreach (self::HOJAS_DIARIAS as $nombreHoja => $sheetService) {
            $sheet = $index === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet();

            $sheet->setTitle($nombreHoja);
            app($sheetService)->generar($sheet, $fecha, $inicio, $fin);
            $index++;
        }

        $spreadsheet->getProperties()
            ->setCreator('Sistema Estadistico')
            ->setTitle('Excel Diario Vialidades Urbanas')
            ->setSubject('Corte ' . $inicio->format('Y-m-d H:i') . ' a ' . $fin->format('Y-m-d H:i'));

        $spreadsheet->setActiveSheetIndex(0);

        $tempPath = storage_path('app/temp_excel_vialidades_urbanas_' . $fecha . '.xlsx');

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        return $tempPath;
    }

    public function rangoCorte(string $fecha): array
    {
        $horaCorte = config('cortes.hora_corte_vialidades_urbanas', '18:00:00');
        $fin = Carbon::parse($fecha . ' ' . $horaCorte, 'America/Mexico_City');

        return [$fin->copy()->subDay(), $fin];
    }
}
