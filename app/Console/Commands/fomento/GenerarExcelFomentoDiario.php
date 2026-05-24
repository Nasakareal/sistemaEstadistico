<?php

namespace App\Console\Commands\fomento;

use App\Services\Fomento\ExcelFomentoGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerarExcelFomentoDiario extends Command
{
    protected $signature = 'fomento:generar-excel-diario {--fecha=}';
    protected $description = 'Genera y almacena el excel diario de Fomento a la Cultura Vial';

    public function handle(): int
    {
        $tz = 'America/Mexico_City';
        $fecha = $this->option('fecha');

        $fechaCorte = $fecha
            ? Carbon::parse($fecha, $tz)->format('Y-m-d')
            : Carbon::now($tz)->format('Y-m-d');

        $tempPath = app(ExcelFomentoGenerator::class)->generar($fechaCorte);

        $directorioDestino = storage_path('app/cortes/excel_fomento');

        if (!File::exists($directorioDestino)) {
            File::makeDirectory($directorioDestino, 0775, true);
        }

        if (!is_writable($directorioDestino)) {
            $this->error('No se puede escribir en el directorio destino: ' . $directorioDestino);

            return Command::FAILURE;
        }

        $nombreArchivo = 'excel_fomento_' . $fechaCorte . '.xlsx';
        $rutaDestino = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;

        if (File::exists($rutaDestino) && !is_writable($rutaDestino)) {
            $nombreArchivo = 'excel_fomento_' . $fechaCorte . '_' . Carbon::now($tz)->format('His') . '.xlsx';
            $rutaDestino = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;

            $this->warn('El archivo diario ya existe pero no se puede sobrescribir. Se guardara una copia nueva.');
        }

        try {
            File::copy($tempPath, $rutaDestino);
            @chmod($rutaDestino, 0664);
        } catch (\Throwable $e) {
            $this->error('No se pudo copiar el Excel al destino: ' . $rutaDestino);
            $this->line($e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Excel generado correctamente: ' . $rutaDestino);

        return Command::SUCCESS;
    }
}
