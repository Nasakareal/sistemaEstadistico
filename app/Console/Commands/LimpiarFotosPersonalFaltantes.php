<?php

namespace App\Console\Commands;

use App\Models\Personal;
use App\Models\PersonalFoto;
use App\Services\Fotos\PersonalFotoStorage;
use Illuminate\Console\Command;

class LimpiarFotosPersonalFaltantes extends Command
{
    protected $signature = 'personal:fotos-limpiar-faltantes
        {--force : Borra las referencias faltantes; sin esta opcion solo reporta}
        {--limit=0 : Maximo de registros de fotos a revisar; 0 revisa todos}
        {--mostrar=20 : Maximo de rutas faltantes a mostrar en pantalla}';

    protected $description = 'Elimina referencias de fotos de personal que ya no existen en almacenamiento.';

    private array $existsCache = [];

    public function handle(PersonalFotoStorage $storage): int
    {
        @set_time_limit(0);

        $force = (bool) $this->option('force');
        $limit = max(0, (int) $this->option('limit'));
        $mostrar = max(0, (int) $this->option('mostrar'));

        $stats = [
            'fotos_revisadas' => 0,
            'fotos_existentes' => 0,
            'fotos_faltantes' => 0,
            'fotos_eliminadas' => 0,
            'principales_revisadas' => 0,
            'principales_faltantes' => 0,
            'principales_actualizadas' => 0,
            'errores' => [],
        ];

        $rutasMostradas = 0;
        $procesados = 0;

        PersonalFoto::query()
            ->whereNotNull('ruta')
            ->where('ruta', '<>', '')
            ->orderBy('id')
            ->chunkById(100, function ($fotos) use ($storage, $force, $limit, $mostrar, &$stats, &$rutasMostradas, &$procesados) {
                foreach ($fotos as $foto) {
                    if ($limit > 0 && $procesados >= $limit) {
                        return false;
                    }

                    $procesados++;
                    $stats['fotos_revisadas']++;

                    try {
                        if ($this->exists($storage, $foto->ruta)) {
                            $stats['fotos_existentes']++;
                            continue;
                        }

                        $stats['fotos_faltantes']++;

                        if ($rutasMostradas < $mostrar) {
                            $this->line('Falta foto #' . $foto->id . ': ' . $foto->ruta);
                            $rutasMostradas++;
                        }

                        if ($force) {
                            $foto->delete();
                            $stats['fotos_eliminadas']++;
                        }
                    } catch (\Throwable $e) {
                        $stats['errores'][] = 'Foto #' . $foto->id . ' (' . $foto->ruta . '): ' . $e->getMessage();
                    }
                }

                return true;
            });

        $this->revisarPrincipales($storage, $force, $stats);

        $this->info('Limpieza de fotos faltantes de personal');
        $this->line('Modo: ' . ($force ? 'BORRAR' : 'REPORTE'));
        $this->line('Fotos revisadas: ' . $stats['fotos_revisadas']);
        $this->line('Fotos existentes: ' . $stats['fotos_existentes']);
        $this->line('Fotos faltantes: ' . $stats['fotos_faltantes']);
        $this->line('Fotos eliminadas: ' . $stats['fotos_eliminadas']);
        $this->line('Principales revisadas: ' . $stats['principales_revisadas']);
        $this->line('Principales faltantes: ' . $stats['principales_faltantes']);
        $this->line('Principales actualizadas: ' . $stats['principales_actualizadas']);
        $this->line('Errores: ' . count($stats['errores']));

        foreach ($stats['errores'] as $error) {
            $this->error($error);
        }

        if (!$force) {
            $this->warn('No se borro nada. Ejecuta con --force cuando el reporte sea correcto.');
        }

        return empty($stats['errores']) ? self::SUCCESS : self::FAILURE;
    }

    private function revisarPrincipales(PersonalFotoStorage $storage, bool $force, array &$stats): void
    {
        Personal::withTrashed()
            ->whereNotNull('foto')
            ->where('foto', '<>', '')
            ->orderBy('id')
            ->chunkById(100, function ($personales) use ($storage, $force, &$stats) {
                foreach ($personales as $personal) {
                    $stats['principales_revisadas']++;

                    try {
                        if ($this->exists($storage, $personal->foto)) {
                            continue;
                        }

                        $stats['principales_faltantes']++;
                        $nuevaPrincipal = $this->buscarFotoExistente($storage, $personal);
                        $nuevaRuta = $nuevaPrincipal ? $nuevaPrincipal->ruta : null;

                        if ($force && $personal->foto !== $nuevaRuta) {
                            $personal->foto = $nuevaRuta;
                            $personal->save();
                            $stats['principales_actualizadas']++;
                        } elseif (!$force) {
                            $stats['principales_actualizadas']++;
                        }
                    } catch (\Throwable $e) {
                        $stats['errores'][] = 'Personal #' . $personal->id . ' (' . $personal->foto . '): ' . $e->getMessage();
                    }
                }

                return true;
            });
    }

    private function buscarFotoExistente(PersonalFotoStorage $storage, Personal $personal): ?PersonalFoto
    {
        foreach ($personal->fotos()->latest('id')->get() as $foto) {
            if ($this->exists($storage, $foto->ruta)) {
                return $foto;
            }
        }

        return null;
    }

    private function exists(PersonalFotoStorage $storage, ?string $path): bool
    {
        $normalized = $storage->normalizePath($path);

        if ($normalized === '') {
            return false;
        }

        if (!array_key_exists($normalized, $this->existsCache)) {
            $this->existsCache[$normalized] = $storage->exists($normalized);
        }

        return $this->existsCache[$normalized];
    }
}
