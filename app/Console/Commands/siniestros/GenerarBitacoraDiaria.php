<?php

namespace App\Console\Commands\siniestros;

use App\Services\BitacoraGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerarBitacoraDiaria extends Command
{
    protected $signature = 'siniestros:generar-bitacora-diaria {--fecha=}';
    protected $description = 'Genera y almacena la bitácora diaria al corte configurado';

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

        $generator = app(BitacoraGenerator::class);
        $tempPath = $generator->generar($fechaCorte);

        $directorioDestino = storage_path('app/cortes/bitacora');
        if (!File::exists($directorioDestino)) {
            File::makeDirectory($directorioDestino, 0775, true);
        }

        $nombreArchivo = 'bitacora_' . $fechaCorte . '.docx';
        $rutaDestino = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;

        File::copy($tempPath, $rutaDestino);

        $this->info('Bitácora generada correctamente: ' . $rutaDestino);
        $this->info('Hora de corte configurada: ' . $horaCorte);

        return Command::SUCCESS;
    }
}
