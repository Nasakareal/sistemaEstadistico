<?php

namespace App\Console\Commands\delegaciones;

use App\Services\Delegaciones\ExcelDelegacionesGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerarExcelDelegacionesDiario extends Command
{
    protected $signature = 'delegaciones:generar-excel-diario {--fecha=}';
    protected $description = 'Genera y almacena el excel diario de delegaciones';

    public function handle(): int
    {
        $tz = 'America/Mexico_City';

        $fecha = $this->option('fecha');

        if ($fecha) {
            $fechaCorte = Carbon::parse($fecha, $tz)->format('Y-m-d');
        } else {
            $fechaCorte = Carbon::now($tz)->format('Y-m-d');
        }

        $generator = app(ExcelDelegacionesGenerator::class);
        $tempPath = $generator->generar($fechaCorte);

        $directorioDestino = storage_path('app/cortes/excel_delegaciones');

        if (!File::exists($directorioDestino)) {
            File::makeDirectory($directorioDestino, 0775, true);
        }

        $nombreArchivo = 'excel_delegaciones_' . $fechaCorte . '.xlsx';
        $rutaDestino = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;

        File::copy($tempPath, $rutaDestino);

        $this->info('Excel generado correctamente: ' . $rutaDestino);

        return Command::SUCCESS;
    }
}
