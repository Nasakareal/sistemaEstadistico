<?php

namespace App\Services\Exports;

use App\Services\Exports\Sheets\EstadoFuerzaSheet;
use App\Services\Exports\Sheets\EstadoFuerzaVehicularSheet;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EstadoFuerzaExcelService
{
    protected EstadoFuerzaSheet $estadoFuerzaSheet;
    protected EstadoFuerzaVehicularSheet $estadoFuerzaVehicularSheet;

    public function __construct(
        EstadoFuerzaSheet $estadoFuerzaSheet,
        EstadoFuerzaVehicularSheet $estadoFuerzaVehicularSheet
    ) {
        $this->estadoFuerzaSheet = $estadoFuerzaSheet;
        $this->estadoFuerzaVehicularSheet = $estadoFuerzaVehicularSheet;
    }

    public function generar(?Carbon $corte = null): string
    {
        $corte = $corte ? $corte->copy() : now('America/Mexico_City');

        $dir = storage_path('app/exports');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $spreadsheet = new Spreadsheet();

        $this->estadoFuerzaSheet->build($spreadsheet, $corte);
        $this->estadoFuerzaVehicularSheet->build($spreadsheet, $corte);

        $spreadsheet->setActiveSheetIndex(0);

        $nombre = 'estado_fuerza_' . $corte->format('Y-m-d_His') . '.xlsx';
        $ruta = $dir . DIRECTORY_SEPARATOR . $nombre;

        $writer = new Xlsx($spreadsheet);
        $writer->save($ruta);

        return $ruta;
    }
}
