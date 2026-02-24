<?php

namespace App\Services\Exports;

use App\Services\Exports\Sheets\EstadoFuerzaSheet;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EstadoFuerzaExcelService
{
    protected EstadoFuerzaSheet $estadoFuerzaSheet;

    public function __construct(EstadoFuerzaSheet $estadoFuerzaSheet)
    {
        $this->estadoFuerzaSheet = $estadoFuerzaSheet;
    }

    /**
     * Genera el Excel (por ahora solo 1 hoja: EST. FUR) y lo guarda en storage/app/exports
     * Regresa la ruta absoluta del archivo para descargarlo.
     */
    public function generar(?Carbon $corte = null): string
    {
        $corte = $corte ? $corte->copy() : now('America/Mexico_City');

        $dir = storage_path('app/exports');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $spreadsheet = new Spreadsheet();

        $this->estadoFuerzaSheet->build($spreadsheet, $corte);

        $nombre = 'estado_fuerza_' . $corte->format('Y-m-d_His') . '.xlsx';
        $ruta = $dir . DIRECTORY_SEPARATOR . $nombre;

        $writer = new Xlsx($spreadsheet);
        $writer->save($ruta);

        return $ruta;
    }
}
