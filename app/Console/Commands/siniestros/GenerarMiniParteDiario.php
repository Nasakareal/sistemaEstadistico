<?php

namespace App\Console\Commands\siniestros;

use App\Services\MiniParteGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerarMiniParteDiario extends Command
{
    protected $signature = 'siniestros:generar-mini-parte-diario {--fecha=}';
    protected $description = 'Genera y almacena el mini parte diario al corte configurado';

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

        $generator = app(MiniParteGenerator::class);
        $tempPath = $generator->generar($fechaCorte);

        $directorioDestino = storage_path('app/cortes/mini_parte');
        if (!File::exists($directorioDestino)) {
            File::makeDirectory($directorioDestino, 0775, true);
        }

        $nombreArchivo = 'mini_parte_' . $fechaCorte . '.docx';
        $rutaDestino = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;

        File::copy($tempPath, $rutaDestino);

        $this->info('Mini parte generado correctamente: ' . $rutaDestino);
        $this->info('Hora de corte configurada: ' . $horaCorte);

        return Command::SUCCESS;
    }
}
