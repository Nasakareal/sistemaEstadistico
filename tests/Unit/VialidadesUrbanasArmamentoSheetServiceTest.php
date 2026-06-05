<?php

namespace Tests\Unit;

use App\Services\VialidadesUrbanas\Hojas\ArmamentoSheetService;
use ReflectionMethod;
use Tests\TestCase;

class VialidadesUrbanasArmamentoSheetServiceTest extends TestCase
{
    public function test_agrupa_calibres_de_armamento_para_la_hoja(): void
    {
        $this->assertSame('9mm', $this->grupoCalibre('9mm'));
        $this->assertSame('223_556', $this->grupoCalibre('.223'));
        $this->assertSame('223_556', $this->grupoCalibre('5.56'));
        $this->assertSame('038', $this->grupoCalibre('0.38'));
        $this->assertNull($this->grupoCalibre('12'));
    }

    private function grupoCalibre(string $calibre): ?string
    {
        $method = new ReflectionMethod(ArmamentoSheetService::class, 'grupoCalibre');
        $method->setAccessible(true);

        return $method->invoke(new ArmamentoSheetService(), $calibre);
    }
}
