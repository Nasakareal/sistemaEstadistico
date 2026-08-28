<?php

namespace Tests\Unit;

use App\Http\Controllers\EstadisticasActividadesController;
use App\Models\PuestaDisposicion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class EstadisticasActividadesPuestasEdadTest extends TestCase
{
    use DatabaseTransactions;

    public function test_muestra_puestas_y_filtra_sus_personas_por_edad_y_unidad(): void
    {
        Auth::login(User::factory()->create(['unidad_id' => 5]));

        $anio = (int)now()->year;
        $numero = (int)PuestaDisposicion::query()
            ->where('anio', $anio)
            ->max('numero_puesta') + 700;

        $coincidente = $this->crearPuesta($numero, 5);
        $coincidente->personas()->createMany([
            ['nombre_completo' => 'PERSONA JOVEN', 'edad' => 25, 'calidad' => 'DETENIDA'],
            ['nombre_completo' => 'PERSONA ADULTA', 'edad' => 40, 'calidad' => 'DETENIDA'],
        ]);

        $fueraDeEdad = $this->crearPuesta($numero + 1, 5);
        $fueraDeEdad->personas()->create([
            'nombre_completo' => 'PERSONA MAYOR',
            'edad' => 45,
            'calidad' => 'DETENIDA',
        ]);

        $otraUnidad = $this->crearPuesta($numero + 2, 1);
        $otraUnidad->personas()->create([
            'nombre_completo' => 'PERSONA OTRA UNIDAD',
            'edad' => 24,
            'calidad' => 'DETENIDA',
        ]);

        $request = Request::create('/estadisticas-actividades/puestas-disposicion', 'GET', [
            'desde' => now()->subDay()->toDateString(),
            'hasta' => now()->addDay()->toDateString(),
            'edad_min' => 18,
            'edad_max' => 29,
            'cache_ttl' => 0,
        ]);

        $controller = new EstadisticasActividadesController();
        $puestas = $controller->puestasDisposicion($request)->getData(true);
        $edades = $controller->seriesPuestasPersonasEdad($request)->getData(true);
        $kpis = $controller->kpis($request)->getData(true);

        $this->assertSame(1, $puestas['total']);
        $this->assertSame($coincidente->id, $puestas['data'][0]['id']);
        $this->assertSame(1, (int)$puestas['data'][0]['personas_count']);
        $this->assertSame(1, $edades['total']);
        $this->assertSame(1, collect($edades['series'])->firstWhere('label', '18-29')['total']);
        $this->assertSame(1, $kpis['totales']['puestas_disposicion']);
        $this->assertSame(1, $kpis['totales']['personas_en_puestas']);
        $this->assertNotNull(app('router')->getRoutes()->getByName('estadisticas_actividades.series.puestas_personas_edades'));
        $this->assertNotSame($fueraDeEdad->id, $puestas['data'][0]['id']);
        $this->assertNotSame($otraUnidad->id, $puestas['data'][0]['id']);
    }

    private function crearPuesta(int $numero, int $unidadId): PuestaDisposicion
    {
        return PuestaDisposicion::query()->create([
            'numero_puesta' => $numero,
            'anio' => (int)now()->year,
            'tipo_puesta' => 'PERSONA',
            'motivo' => 'PERSONA DETENIDA',
            'estatus' => 'ACTIVA',
            'nombre_policia' => 'AGENTE DE PRUEBA',
            'area' => 'PRUEBA',
            'fecha_puesta' => now()->toDateString(),
            'unidad_id' => $unidadId,
        ]);
    }
}
