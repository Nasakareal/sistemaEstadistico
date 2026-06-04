<?php

namespace App\Console\Commands;

use App\Services\ActividadFotoRetentionService;
use Illuminate\Console\Command;

class DepurarFotosActividades extends Command
{
    protected $signature = 'actividades:depurar-fotos
        {--dry-run : Solo muestra lo que haria, sin borrar ni comprimir}
        {--unidad-siniestros=1 : Unidad cuyas fotos se borran despues de 10 dias}
        {--dias-borrar=10 : Dias de retencion para la unidad de siniestros}
        {--dias-archivar=7 : Dias antes de comprimir fotos de las demas unidades}
        {--dias-borrar-zips=30 : Dias de retencion de los ZIPs archivados}
        {--marcar-faltantes : Marca en base de datos los paths viejos cuyo archivo fisico ya no exista}
        {--permitir-borrado-y-zip : Ejecuta la depuracion anterior que borra originales y crea ZIPs}';

    protected $description = 'Depuracion antigua de fotos de actividades; requiere confirmacion para borrar o crear ZIPs.';

    public function handle(ActividadFotoRetentionService $service)
    {
        if (!(bool) $this->option('permitir-borrado-y-zip')) {
            $this->warn('Depuracion antigua desactivada: no se borraron fotos ni se crearon ZIPs.');
            $this->line('Usa actividades:fotos-migrar-blob para copiar fotos al contenedor fotos/actividades.');
            $this->line('Si realmente necesitas la depuracion antigua, agrega --permitir-borrado-y-zip.');

            return 0;
        }

        $stats = $service->procesar([
            'dry_run' => (bool) $this->option('dry-run'),
            'unidad_siniestros_id' => (int) $this->option('unidad-siniestros'),
            'dias_borrar' => (int) $this->option('dias-borrar'),
            'dias_archivar' => (int) $this->option('dias-archivar'),
            'dias_borrar_zips' => (int) $this->option('dias-borrar-zips'),
            'marcar_faltantes' => (bool) $this->option('marcar-faltantes'),
        ]);

        $this->info('Depuracion de fotos de actividades');
        $this->line('Corte borrar unidad 1: ' . $stats['corte_borrar']);
        $this->line('Corte archivar otras unidades: ' . $stats['corte_archivar']);
        $this->line('Corte borrar ZIPs archivados: ' . $stats['corte_borrar_zips']);
        $this->line('Fotos para borrar: ' . $stats['fotos_para_borrar']);
        $this->line('Fotos borradas: ' . $stats['fotos_borradas']);
        $this->line('Fotos para archivar: ' . $stats['fotos_para_archivar']);
        $this->line('Fotos archivadas: ' . $stats['fotos_archivadas']);
        $this->line('ZIPs creados: ' . $stats['zips_creados']);
        $this->line('ZIPs para borrar: ' . $stats['zips_para_borrar']);
        $this->line('ZIPs borrados: ' . $stats['zips_borrados']);
        $this->line('ZIPs faltantes detectados: ' . $stats['zips_faltantes']);
        $this->line('Thumbnails creados: ' . $stats['thumbnails_creados']);
        $this->line('Thumbnails fallidos: ' . $stats['thumbnails_fallidos']);
        $this->line('Fotos faltantes detectadas: ' . $stats['fotos_faltantes']);
        $this->line('Bytes originales archivados: ' . $stats['bytes_originales_archivados']);
        $this->line('Bytes ZIP creados: ' . $stats['bytes_zip_creados']);
        $this->line('Bytes ZIP borrados: ' . $stats['bytes_zips_borrados']);
        $this->line('Bytes thumbnails creados: ' . $stats['bytes_thumbnails_creados']);

        foreach ($stats['errores'] as $error) {
            $this->error($error);
        }

        if ($stats['dry_run']) {
            $this->warn('Dry-run activo: no se cambio nada.');
        }

        return empty($stats['errores']) ? 0 : 1;
    }
}
