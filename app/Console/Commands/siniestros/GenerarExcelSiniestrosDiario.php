<?php

namespace App\Console\Commands\siniestros;

use App\Services\Siniestros\ExcelSiniestrosGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerarExcelSiniestrosDiario extends Command
{
    protected $signature = 'siniestros:generar-excel-diario {--fecha=}';
    protected $description = 'Genera y almacena el Excel diario de la Unidad de Atención a Siniestros';

    public function handle(): int
    {
        $tz = 'America/Mexico_City';
        $fecha = $this->option('fecha');

        $fechaCorte = $fecha
            ? Carbon::parse($fecha, $tz)->format('Y-m-d')
            : Carbon::now($tz)->format('Y-m-d');

        try {
            $tempPath = app(ExcelSiniestrosGenerator::class)->generar($fechaCorte);
        } catch (\Throwable $e) {
            $this->error('No se pudo generar el Excel diario de Siniestros.');
            $this->line($e->getMessage());

            return Command::FAILURE;
        }

        $directorioDestino = storage_path('app/cortes/excel_diario_siniestros');

        if (!File::exists($directorioDestino)) {
            File::makeDirectory($directorioDestino, 0775, true);
        }

        if (!is_writable($directorioDestino)) {
            $this->error('No se puede escribir en el directorio destino: ' . $directorioDestino);

            if (File::exists($tempPath)) {
                File::delete($tempPath);
            }

            return Command::FAILURE;
        }

        $nombreArchivo = 'excel_diario_siniestros_' . $fechaCorte . '.xlsx';
        $rutaDestino = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;

        if (File::exists($rutaDestino) && !is_writable($rutaDestino)) {
            $this->error('El Excel diario ya existe y no se puede sobrescribir: ' . $rutaDestino);

            if (File::exists($tempPath)) {
                File::delete($tempPath);
            }

            return Command::FAILURE;
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

        $this->info('Excel diario de Siniestros generado correctamente: ' . $rutaDestino);

        return Command::SUCCESS;
    }
}
