<?php

namespace App\Console\Commands\siniestros;

use App\Services\ExcelNovedadesGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerarExcelNovedadesDiario extends Command
{
    protected $signature = 'siniestros:generar-excel-novedades {--fecha=}';
    protected $description = 'Genera el Excel de novedades usando plantilla';

    public function handle(): int
    {
        $tz = 'America/Mexico_City';

        $fecha = $this->option('fecha');

        if ($fecha) {
            $corte = Carbon::parse($fecha, $tz);
        } else {
            $corte = Carbon::now($tz);
        }

        $generator = app(ExcelNovedadesGenerator::class);

        $ruta = $generator->generar($corte);

        $this->info('Excel generado correctamente: ' . $ruta);

        return Command::SUCCESS;
    }
}
