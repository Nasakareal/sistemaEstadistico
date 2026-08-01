<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ConduceLegalidadController;
use App\Models\ConduceLegalidadCaptura;
use App\Models\ConduceLegalidadCapturaFundamento;
use App\Models\LicenciaPuntoInfraccion;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class ConduceLegalidadMultipleFundamentosTest extends TestCase
{
    public function test_validation_accepts_distinct_fundamento_ids(): void
    {
        $method = new ReflectionMethod(
            ConduceLegalidadController::class,
            'capturaRules'
        );
        $method->setAccessible(true);

        $rules = $method->invoke(new ConduceLegalidadController());

        $this->assertArrayHasKey('fundamentos', $rules);
        $this->assertArrayHasKey(
            'fundamentos.*.licencia_punto_infraccion_id',
            $rules
        );
        $this->assertArrayHasKey('fundamentos.*.infraccion_codigo', $rules);
        $this->assertArrayHasKey('fundamentos.*.fundamento_legal', $rules);
        $this->assertArrayHasKey('fundamento_ids', $rules);
        $this->assertArrayHasKey('fundamento_ids.*', $rules);
        $this->assertContains('distinct', $rules['fundamento_ids.*']);
        $this->assertContains(
            'exists:licencia_punto_infracciones,id',
            $rules['fundamento_ids.*']
        );
        $this->assertContains('max:1', $rules['vehiculos']);
    }

    public function test_iph_includes_every_capture_legal_ground(): void
    {
        $primero = new LicenciaPuntoInfraccion();
        $primero->forceFill([
            'id' => 101,
            'articulo' => '345',
            'fundamento_legal' => 'Fundamento primero',
        ]);

        $segundo = new LicenciaPuntoInfraccion();
        $segundo->forceFill([
            'id' => 102,
            'articulo' => '508',
            'fundamento_legal' => 'Fundamento segundo',
        ]);

        $filaPrimera = new ConduceLegalidadCapturaFundamento();
        $filaPrimera->forceFill([
            'licencia_punto_infraccion_id' => 101,
            'orden' => 0,
            'fundamento_legal' => 'Fundamento primero',
        ]);
        $filaPrimera->setRelation('infraccion', $primero);

        $filaSegunda = new ConduceLegalidadCapturaFundamento();
        $filaSegunda->forceFill([
            'licencia_punto_infraccion_id' => 102,
            'orden' => 1,
            'fundamento_legal' => 'Fundamento segundo',
        ]);
        $filaSegunda->setRelation('infraccion', $segundo);

        $captura = new ConduceLegalidadCaptura();
        $captura->setRelation(
            'fundamentos',
            new Collection([$filaPrimera, $filaSegunda])
        );

        $method = new ReflectionMethod(
            ConduceLegalidadController::class,
            'fundamentosIphCaptura'
        );
        $method->setAccessible(true);
        $texto = $method->invoke(new ConduceLegalidadController(), $captura);

        $this->assertStringContainsString('Art. 345', $texto);
        $this->assertStringContainsString('Art. 508', $texto);
        $this->assertStringContainsString('Fundamento primero', $texto);
        $this->assertStringContainsString('Fundamento segundo', $texto);
    }
}
