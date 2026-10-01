<?php

namespace Tests\Unit;

use App\Http\Controllers\HechosController;
use App\Models\Hechos;
use ReflectionMethod;
use Tests\TestCase;

class HechosIndexSearchTest extends TestCase
{
    public function test_index_search_covers_hechos_vehicles_and_drivers(): void
    {
        $query = Hechos::query();
        $method = new ReflectionMethod(HechosController::class, 'applyIndexSearch');
        $method->setAccessible(true);
        $method->invoke(new HechosController(), $query, 'ABC-123');

        $sql = strtolower($query->toSql());
        $bindings = $query->getBindings();

        $this->assertStringContainsString('folio_c5i', $sql);
        $this->assertStringContainsString('calle', $sql);
        $this->assertStringContainsString('vehiculos', $sql);
        $this->assertStringContainsString('conductores', $sql);
        $this->assertContains('%ABC-123%', $bindings);
        $this->assertContains('%ABC123%', $bindings);
    }

    public function test_numeric_index_search_includes_exact_hecho_id(): void
    {
        $query = Hechos::query();
        $method = new ReflectionMethod(HechosController::class, 'applyIndexSearch');
        $method->setAccessible(true);
        $method->invoke(new HechosController(), $query, '#456');

        $this->assertContains(456, $query->getBindings());
    }
}
