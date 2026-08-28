<?php

namespace Tests\Unit;

use App\Http\Controllers\EstadisticasGlobalesController;
use App\Models\PuestaDisposicion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class EstadisticasGlobalesPuestasPersonasEdadTest extends TestCase
{
    use DatabaseTransactions;

    public function test_clasifica_las_personas_de_puestas_por_rangos_de_edad(): void
    {
        Auth::login(User::factory()->create(['unidad_id' => 5]));

        $request = Request::create('/estadisticas-globales/series/personas-puestas-edades', 'GET', [
            'desde' => now()->subDay()->toDateString(),
            'hasta' => now()->addDay()->toDateString(),
            'cache_ttl' => 0,
        ]);

        $controller = new EstadisticasGlobalesController();
        $antes = $this->totalesPorRango($controller->seriesPersonasPuestasEdad($request)->getData(true));

        $anio = (int)now()->year;
        $numero = (int)PuestaDisposicion::query()
            ->where('anio', $anio)
            ->max('numero_puesta') + 600;

        $puesta = PuestaDisposicion::query()->create([
            'numero_puesta' => $numero,
            'anio' => $anio,
            'tipo_puesta' => 'PERSONA',
            'motivo' => 'PERSONA DETENIDA',
            'estatus' => 'ACTIVA',
            'nombre_policia' => 'AGENTE DE PRUEBA',
            'area' => 'VIALIDADES',
            'fecha_puesta' => now()->toDateString(),
            'unidad_id' => 5,
        ]);

        foreach ([10, 16, 25, 36, 52, 67, null] as $indice => $edad) {
            $puesta->personas()->create([
                'nombre_completo' => 'PERSONA RANGO ' . $indice,
                'edad' => $edad,
                'calidad' => 'DETENIDA',
            ]);
        }

        $despues = $this->totalesPorRango($controller->seriesPersonasPuestasEdad($request)->getData(true));

        foreach (['0-11', '12-17', '18-29', '30-44', '45-59', '60+', 'SIN EDAD'] as $rango) {
            $this->assertSame(($antes[$rango] ?? 0) + 1, $despues[$rango] ?? 0, $rango);
        }

        $kpis = $controller->kpis($request)->getData(true);
        $this->assertSame(array_sum($despues), $kpis['totales']['personas_puestas']);
        $this->assertNotNull(app('router')->getRoutes()->getByName('estadisticas_globales.series.personas_puestas_edades'));
    }

    private function totalesPorRango(array $respuesta): array
    {
        return collect($respuesta['series'] ?? [])->pluck('total', 'label')->all();
    }
}
