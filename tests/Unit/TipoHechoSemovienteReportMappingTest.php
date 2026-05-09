<?php

namespace Tests\Unit;

use App\Services\Delegaciones\Hojas\RegionalSheetService;
use App\Services\ExcelNovedadesGenerator;
use App\Services\HechoNovedadesFormatter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TipoHechoSemovienteReportMappingTest extends TestCase
{
    public function test_excel_novedades_cuenta_colision_contra_semoviente_en_corte_de_circulacion(): void
    {
        $ref = new ReflectionClass(ExcelNovedadesGenerator::class);
        $service = $ref->newInstanceWithoutConstructor();

        $formatter = $ref->getProperty('hechoFormatter');
        $formatter->setAccessible(true);
        $formatter->setValue($service, new HechoNovedadesFormatter());

        $method = $ref->getMethod('filaTipoHecho');
        $method->setAccessible(true);

        $this->assertSame(138, $method->invoke($service, 'COLISIÓN CONTRA SEMOVIENTE'));
    }

    public function test_excel_delegaciones_normaliza_colision_contra_semoviente_como_corte_de_circulacion(): void
    {
        $service = new RegionalSheetService();
        $method = (new ReflectionClass($service))->getMethod('normalizarTipoHechoDelegaciones');
        $method->setAccessible(true);

        $this->assertSame(
            'COLISIÓN POR CORTE DE CIRCULACIÓN',
            $method->invoke($service, 'COLISIÓN CONTRA SEMOVIENTE')
        );
    }

    public function test_excel_multihoja_de_siniestros_mapea_semoviente_a_corte_de_circulacion(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2) . '/app/Services/Exports/Sheets/TotalSheet.php'
        );

        $this->assertStringContainsString(
            "\$this->norm('COLISIÓN CONTRA SEMOVIENTE') => 'COLISION_CORTE_CIRCULACION'",
            $source
        );
    }
}
