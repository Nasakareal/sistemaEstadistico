<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ActividadFotoBlobScheduleTest extends TestCase
{
    public function test_limpieza_local_se_programa_despues_de_migrar_y_solo_con_confirmacion_blob(): void
    {
        $kernel = file_get_contents(__DIR__ . '/../../app/Console/Kernel.php');
        $migracion = "actividades:fotos-migrar-blob";
        $limpieza = "actividades:fotos-limpiar-locales-blob --force --limpiar-zips --limpiar-cache-pdf";

        $this->assertStringContainsString($migracion, $kernel);
        $this->assertStringContainsString($limpieza, $kernel);
        $this->assertLessThan(strpos($kernel, $limpieza), strpos($kernel, $migracion));
    }

    public function test_comando_de_limpieza_verifica_blob_antes_de_borrar_archivos_locales(): void
    {
        $command = file_get_contents(
            __DIR__ . '/../../app/Console/Commands/LimpiarFotosActividadesLocalesBlob.php'
        );

        $this->assertStringContainsString('if (!$this->blobExists($blobStorage, $blobPath))', $command);
        $this->assertStringContainsString('if ($force)', $command);
        $this->assertStringContainsString('$disk->delete($localPath)', $command);
    }

    public function test_directorios_de_miniaturas_permiten_limpieza_por_el_scheduler(): void
    {
        $service = file_get_contents(__DIR__ . '/../../app/Services/ImageThumbnailService.php');

        $this->assertStringContainsString('@chmod($disk->path($targetDirectory), 02775);', $service);
        $this->assertStringContainsString('@chmod($disk->path($targetPath), 0664);', $service);
    }
}
