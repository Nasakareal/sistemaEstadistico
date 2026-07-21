<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\PuestaDisposicionController;
use App\Models\PuestaDisposicion;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PuestaDisposicionControllerFilterTest extends TestCase
{
    use DatabaseTransactions;

    public function test_index_filters_dispositions_by_unit_without_mixing_results(): void
    {
        $this->assertTrue(Unidad::query()->whereKey(1)->exists());
        $this->assertTrue(Unidad::query()->whereKey(3)->exists());
        $this->assertTrue(Unidad::query()->whereKey(5)->exists());

        $year = (int) now()->year;
        $nextNumber = (int) PuestaDisposicion::query()
            ->where('anio', $year)
            ->max('numero_puesta') + 100;

        $siniestros = $this->createPuesta($nextNumber, $year, 1, 'SINIESTROS');
        $vialidades = $this->createPuesta($nextNumber + 1, $year, 5, 'VIALIDADES');

        $actor = User::factory()->create(['unidad_id' => 3]);
        Auth::login($actor);

        $request = Request::create('/api/puestas-disposicion', 'GET', [
            'anio' => $year,
            'unidad_id' => 5,
        ]);
        $response = (new PuestaDisposicionController())->index($request);
        $items = $response->getData(true);

        $this->assertContains($vialidades->id, collect($items)->pluck('id')->all());
        $this->assertNotContains($siniestros->id, collect($items)->pluck('id')->all());
        $this->assertTrue(collect($items)->every(function (array $item) {
            return (int) $item['unidad_id'] === 5;
        }));
    }

    private function createPuesta(int $number, int $year, int $unitId, string $area): PuestaDisposicion
    {
        return PuestaDisposicion::query()->create([
            'numero_puesta' => $number,
            'anio' => $year,
            'tipo_puesta' => 'PERSONA',
            'motivo' => 'PERSONA DETENIDA',
            'estatus' => 'ACTIVA',
            'nombre_policia' => 'AGENTE DE PRUEBA',
            'area' => $area,
            'fecha_puesta' => now()->toDateString(),
            'unidad_id' => $unitId,
        ]);
    }
}
