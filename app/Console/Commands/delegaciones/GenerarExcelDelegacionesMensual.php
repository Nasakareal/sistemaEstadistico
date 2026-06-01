<?php

namespace App\Console\Commands\delegaciones;

use App\Services\Delegaciones\ExcelDelegacionesMensualGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerarExcelDelegacionesMensual extends Command
{
    protected $signature = 'delegaciones:generar-excel-mensual {--fecha=}';
    protected $description = 'Genera y almacena el excel mensual de delegaciones';

    public function handle(): int
    {
        $tz = 'America/Mexico_City';

        $fecha = $this->option('fecha');

        if ($fecha) {
            $fechaCorte = Carbon::parse($fecha, $tz)->format('Y-m');
        } else {
            $fechaCorte = Carbon::now($tz)->subMonthNoOverflow()->format('Y-m');
        }

        $generator = app(ExcelDelegacionesMensualGenerator::class);
        $tempPath = $generator->generar($fechaCorte);

        $directorioDestino = storage_path('app/cortes/excel_delegaciones_mensual');

        if (!File::exists($directorioDestino)) {
            File::makeDirectory($directorioDestino, 0775, true);
        }

        if (!is_writable($directorioDestino)) {
            $this->error('No se puede escribir en el directorio destino: ' . $directorioDestino);
            $this->line('Corrige permisos en el servidor, por ejemplo: sudo chown -R www-data:www-data storage bootstrap/cache && sudo chmod -R ug+rwX storage bootstrap/cache');

            return Command::FAILURE;
        }

        $nombreArchivo = 'excel_delegaciones_' . $fechaCorte . '.xlsx';
        $rutaDestino = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;

        if (File::exists($rutaDestino) && !is_writable($rutaDestino)) {
            $nombreArchivo = 'excel_delegaciones_' . $fechaCorte . '_' . Carbon::now($tz)->format('His') . '.xlsx';
            $rutaDestino = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;

            $this->warn('El archivo mensual ya existe pero no se puede sobrescribir. Se guardara una copia nueva.');
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
