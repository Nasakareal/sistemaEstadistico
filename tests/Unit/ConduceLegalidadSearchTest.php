<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ConduceLegalidadController;
use App\Models\ConduceLegalidadCaptura;
use App\Models\ConduceLegalidadOperativo;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ConduceLegalidadSearchTest extends TestCase
{
    public function test_parses_conduce_ticket_folio(): void
    {
        $parsed = $this->invokePrivate('parseFolioBusqueda', 'cl-18-44-2');

        $this->assertSame([
            'tipo_operativo' => 'conduce_legalidad',
            'operativo_id' => 18,
            'captura_id' => 44,
            'ticket_index' => 2,
        ], $parsed);
    }

    public function test_parses_accident_prevention_ticket_folio(): void
    {
        $parsed = $this->invokePrivate('parseFolioBusqueda', 'PA-7-31');

        $this->assertSame([
            'tipo_operativo' => 'alcoholimetria',
            'operativo_id' => 7,
            'captura_id' => 31,
            'ticket_index' => null,
        ], $parsed);
    }

    public function test_rejects_an_unrelated_folio(): void
    {
        $this->assertNull($this->invokePrivate('parseFolioBusqueda', 'C5I-123'));
    }

    public function test_uses_a_distinct_prefix_for_each_operation_type(): void
    {
        $controller = new ConduceLegalidadController();
        $method = new ReflectionMethod($controller, 'folioCaptura');
        $method->setAccessible(true);

        $captura = new ConduceLegalidadCaptura();
        $captura->id = 12;

        $conduce = new ConduceLegalidadOperativo(['tipo_operativo' => 'conduce_legalidad']);
        $conduce->id = 5;
        $prevencion = new ConduceLegalidadOperativo(['tipo_operativo' => 'alcoholimetria']);
        $prevencion->id = 6;

        $this->assertSame('CL-5-12', $method->invoke($controller, $conduce, $captura));
        $this->assertSame('PA-6-12', $method->invoke($controller, $prevencion, $captura));
    }

    private function invokePrivate(string $methodName, string $value)
    {
        $controller = new ConduceLegalidadController();
        $method = new ReflectionMethod($controller, $methodName);
        $method->setAccessible(true);

        return $method->invoke($controller, $value);
    }
}
