<?php

namespace Tests\Unit;

use App\Services\OpenAIService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class OpenAIServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_interpreta_expediente_personal_localmente(): void
    {
        $service = new OpenAIService();

        $resultado = $service->interpretar('Dame el expediente del elemento Juan Pérez');

        $this->assertSame('detalle_personal', $resultado['accion']);
        $this->assertSame('Juan Pérez', $resultado['persona']);
    }

    public function test_interpreta_lesionados_como_estadistica_rapida(): void
    {
        Carbon::setTestNow('2026-04-23 10:30:00');

        $service = new OpenAIService();

        $resultado = $service->interpretar('¿Cuántos lesionados hubo hoy?');

        $this->assertSame('estadistica_lesionados', $resultado['accion']);
        $this->assertSame('2026-04-23', $resultado['filtros']['fecha']);
    }

    public function test_interpreta_motocicletas_como_estadistica_rapida(): void
    {
        Carbon::setTestNow('2026-04-23 10:30:00');

        $service = new OpenAIService();

        $resultado = $service->interpretar('Necesito la estadística de motocicletas este mes');

        $this->assertSame('estadistica_motocicletas', $resultado['accion']);
        $this->assertSame('2026-04-01', $resultado['filtros']['fecha_inicio']);
        $this->assertSame('2026-04-30', $resultado['filtros']['fecha_fin']);
    }
}
