<?php

namespace App\Console\Commands\vialidades_urbanas;

use App\Services\VialidadesUrbanas\ExcelVialidadesUrbanasGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerarExcelVialidadesUrbanasDiario extends Command
{
    protected $signature = 'vialidades-urbanas:generar-excel-diario {--fecha=}';
    protected $description = 'Genera y almacena el excel diario de Vialidades Urbanas';

    public function handle(): int
    {
        $tz = 'America/Mexico_City';
        $fecha = $this->option('fecha');

        $fechaCorte = $fecha
            ? Carbon::parse($fecha, $tz)->format('Y-m-d')
            : Carbon::now($tz)->format('Y-m-d');

        $tempPath = app(ExcelVialidadesUrbanasGenerator::class)->generar($fechaCorte);

        $directorioDestino = storage_path('app/cortes/excel_vialidades_urbanas');

        if (!File::exists($directorioDestino)) {
            File::makeDirectory($directorioDestino, 0775, true);
        }

        if (!is_writable($directorioDestino)) {
            $this->error('No se puede escribir en el directorio destino: ' . $directorioDestino);

            return Command::FAILURE;
        }

        $nombreArchivo = 'excel_vialidades_urbanas_' . $fechaCorte . '.xlsx';
        $rutaDestino = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;

        if (File::exists($rutaDestino) && !is_writable($rutaDestino)) {
            $nombreArchivo = 'excel_vialidades_urbanas_' . $fechaCorte . '_' . Carbon::now($tz)->format('His') . '.xlsx';
            $rutaDestino = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;

            $this->warn('El archivo diario ya existe pero no se puede sobrescribir. Se guardara una copia nueva.');
        }

        try {
            File::copy($tempPath, $rutaDestino);
            @chmod($rutaDestino, 0664);
            File::delete($tempPath);
        } catch (\Throwable $e) {
            $this->error('No se pudo copiar el Excel al destino: ' . $rutaDestino);
            $this->line($e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Excel generado correctamente: ' . $rutaDestino);

        return Command::SUCCESS;
    }
}
