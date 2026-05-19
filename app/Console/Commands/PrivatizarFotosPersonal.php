<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PrivatizarFotosPersonal extends Command
{
    protected $signature = 'personal:fotos-privatizar {--dry-run : Muestra los archivos que se moverian sin modificarlos}';

    protected $description = 'Mueve las fotos de personal del disco publico al almacenamiento privado.';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $local = Storage::disk('local');
        $dryRun = (bool) $this->option('dry-run');

        $paths = collect($public->allFiles('personals/fotos'))
            ->map(fn ($path) => str_replace('\\', '/', $path))
            ->filter(fn ($path) => str_starts_with($path, 'personals/fotos/'))
            ->values();

        if ($paths->isEmpty()) {
            $this->info('No hay fotos de personal en el disco publico.');

            return self::SUCCESS;
        }

        $movidas = 0;
        $errores = 0;

        foreach ($paths as $path) {
            if ($dryRun) {
                $this->line("Se moveria: {$path}");
                continue;
            }

            $stream = $public->readStream($path);

            if ($stream === false) {
                $this->error("No se pudo leer: {$path}");
                $errores++;
                continue;
            }

            $copiado = $local->put($path, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            if (!$copiado || !$local->exists($path)) {
                $this->error("No se pudo guardar en privado: {$path}");
                $errores++;
                continue;
            }

            $public->delete($path);
            $movidas++;
            $this->line("Privatizada: {$path}");
        }

        if ($dryRun) {
            $this->info("Dry run terminado. Archivos detectados: {$paths->count()}.");

            return self::SUCCESS;
        }

        if ($errores > 0) {
            $this->warn("Proceso terminado con errores. Movidas: {$movidas}. Errores: {$errores}.");

            return self::FAILURE;
        }

        $this->info("Fotos de personal privatizadas: {$movidas}.");

        return self::SUCCESS;
    }
}
