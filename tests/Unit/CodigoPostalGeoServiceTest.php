<?php

namespace Tests\Unit;

use App\Services\CodigoPostalGeoService;
use PHPUnit\Framework\TestCase;

class CodigoPostalGeoServiceTest extends TestCase
{
    public function test_resuelve_codigo_postal_para_punto_en_morelia(): void
    {
        $this->assertSame('58000', $this->service()->resolver(19.7008, -101.1844));
    }

    public function test_devuelve_null_para_punto_fuera_de_michoacan(): void
    {
        $this->assertNull($this->service()->resolver(19.4326, -99.1332));
    }

    public function test_devuelve_null_para_coordenadas_invalidas(): void
    {
        $service = new CodigoPostalGeoService();

        $this->assertNull($service->resolver(null, -101.1844));
        $this->assertNull($service->resolver(95, -101.1844));
    }

    private function service(): CodigoPostalGeoService
    {
        $basePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'geodata';
        $shpPath = $basePath . DIRECTORY_SEPARATOR . 'CP_Mich.shp';
        $dbfPath = $basePath . DIRECTORY_SEPARATOR . 'CP_Mich.dbf';

        if (!is_file($shpPath) || !is_file($dbfPath)) {
            $this->markTestSkipped('No estan disponibles CP_Mich.shp y CP_Mich.dbf.');
        }

        return new CodigoPostalGeoService($shpPath, $dbfPath);
    }
}
