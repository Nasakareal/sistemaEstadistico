<?php

namespace App\Console\Commands\siniestros;

use App\Services\ActividadInformeService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class GenerarActividadInformeDiario extends Command
{
    protected $signature = 'siniestros:generar-actividad-informe-diario {--fecha=}';
    protected $description = 'Genera el informe diario de actividades';

    public function handle(): int
    {
        $tz = 'America/Mexico_City';

        $fecha = $this->option('fecha');

        if ($fecha) {
            $fechaCorte = Carbon::parse($fecha, $tz)->format('Y-m-d');
        } else {
            $fechaCorte = Carbon::now($tz)->format('Y-m-d');
        }

        $service = app(ActividadInformeService::class);

        $archivo = $service->generarYGuardarEnCortes(
            $fechaCorte,
            new Request()
        );

        $this->info('Informe generado: ' . $archivo);

        return Command::SUCCESS;
    }
}
