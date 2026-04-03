<?php

namespace App\Console\Commands\siniestros;

use App\Services\ParteNovedadesGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerarParteNovedadesDiario extends Command
{
    protected $signature = 'siniestros:generar-parte-novedades-diario {--fecha=}';
    protected $description = 'Genera y almacena el parte de novedades diario al corte configurado';

    public function handle(): int
    {
        $tz = 'America/Mexico_City';
        $horaCorte = config('cortes.hora_corte', '18:00:00');

        $fecha = $this->option('fecha');

        if ($fecha) {
            $fechaCorte = Carbon::parse($fecha, $tz)->format('Y-m-d');
        } else {
            $fechaCorte = Carbon::now($tz)->format('Y-m-d');
        }

        $generator = app(ParteNovedadesGenerator::class);
        $tempPath = $generator->generar($fechaCorte);

        $directorioDestino = storage_path('app/cortes/parte_novedades');
        if (!File::exists($directorioDestino)) {
            File::makeDirectory($directorioDestino, 0775, true);
        }

        $nombreArchivo = 'parte_novedades_' . $fechaCorte . '.docx';
        $rutaDestino = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;

        File::copy($tempPath, $rutaDestino);

        $this->info('Parte generado correctamente: ' . $rutaDestino);
        $this->info('Hora de corte configurada: ' . $horaCorte);

        return Command::SUCCESS;
    }
}
