<?php

namespace Tests\Unit;

use App\Http\Controllers\EstadisticasSeguridadVialController;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class EstadisticasSeguridadVialControllerTest extends TestCase
{
    public function test_zonas_conflictivas_no_excluye_zonas_con_fallecidos_por_bajo_volumen(): void
    {
        $zonasSinVictimas = collect(range(1, 12))->map(function (int $index) {
            $total = 40 - $index;

            return [
                'lat' => 19.60 + ($index / 1000),
                'lng' => -101.20 - ($index / 1000),
                'total' => $total,
                'fallecidos' => 0,
                'lesionados' => 0,
                'choques' => $total,
                'categoria' => 'choques',
            ];
        });

        $zonaConFallecido = [
            'lat' => 19.72,
            'lng' => -101.18,
            'total' => 1,
            'fallecidos' => 1,
            'lesionados' => 0,
            'choques' => 0,
            'categoria' => 'fallecidos',
        ];

        $puntos = $this->zonasConflictivas($zonasSinVictimas->push($zonaConFallecido));

        $this->assertCount(12, $puntos);
        $this->assertSame(1, (int) $puntos->first()['fallecidos']);
        $this->assertTrue($puntos->contains(fn ($zona) => (int) $zona['fallecidos'] === 1));
    }

    private function zonasConflictivas(Collection $zonas, int $limit = 12): Collection
    {
        $controller = app(EstadisticasSeguridadVialController::class);
        $method = new ReflectionMethod($controller, 'zonasConflictivas');
        $method->setAccessible(true);

        return $method->invoke($controller, $zonas, $limit);
    }
}
