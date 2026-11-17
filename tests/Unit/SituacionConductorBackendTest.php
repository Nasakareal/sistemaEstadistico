<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SituacionConductorBackendTest extends TestCase
{
    public function test_backend_persists_and_counts_driver_situations(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/Http/Controllers/Api/VehiculoController.php');
        $hechos = file_get_contents(__DIR__ . '/../../app/Models/Hechos.php');
        $vehiculo = file_get_contents(__DIR__ . '/../../app/Models/Vehiculo.php');

        $this->assertStringContainsString("'situacion_conductor' => 'sometimes|string|in:PRESENTE", $controller);
        $this->assertStringContainsString("'situacion_conductor'         => \$v['situacion_conductor'] ?? 'PRESENTE'", $controller);
        $this->assertStringContainsString("array_key_exists('situacion_conductor', \$v)", $controller);
        $this->assertStringContainsString("'situacion_conductor'", $vehiculo);
        $this->assertStringContainsString("where('vehiculos.situacion_conductor', '!=', 'PRESENTE')", $hechos);
    }
}
