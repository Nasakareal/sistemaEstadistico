<?php

namespace Tests\Unit;

use App\Http\Controllers\EstadisticasCarreterasController;
use App\Models\PuestaDisposicion;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

class EstadisticasCarreterasElementosTest extends TestCase
{
    use DatabaseTransactions;

    public function test_ranking_agrupa_elementos_y_permite_consultar_sus_puestas(): void
    {
        $unidad = Unidad::query()->where('slug', 'carreteras')->firstOrFail();
        $usuario = User::factory()->create(['unidad_id' => $unidad->id]);
        $fecha = now()->toDateString();
        $anio = (int) now()->year;
        $numero = (int) PuestaDisposicion::query()
            ->where('anio', $anio)
            ->where('unidad_id', $unidad->id)
            ->max('numero_puesta') + 100;

        $this->crearPuesta($numero, $anio, $unidad->id, $fecha, 'Elemento Ranking Prueba Uno');
        $this->crearPuesta($numero + 1, $anio, $unidad->id, $fecha, ' elemento ranking prueba uno ');
        $this->crearPuesta($numero + 2, $anio, $unidad->id, $fecha, 'Elemento Ranking Prueba Dos');

        $controller = new EstadisticasCarreterasController();
        $kpisRequest = Request::create('/estadisticas-carreteras/kpis', 'GET', [
            'desde' => $fecha,
            'hasta' => $fecha,
            'q' => 'ELEMENTO RANKING PRUEBA',
            'cache_ttl' => 0,
        ]);
        $kpisRequest->setUserResolver(fn () => $usuario);

        $ranking = $controller->kpis($kpisRequest)->getData(true)['top']['elemento'];

        $this->assertSame('ELEMENTO RANKING PRUEBA UNO', $ranking[0]['label']);
        $this->assertSame(2, (int) $ranking[0]['total']);

        $listRequest = Request::create('/estadisticas-carreteras/puestas-disposicion', 'GET', [
            'desde' => $fecha,
            'hasta' => $fecha,
            'q' => 'ELEMENTO RANKING PRUEBA',
            'elemento' => 'ELEMENTO RANKING PRUEBA UNO',
            'cache_ttl' => 0,
        ]);
        $listRequest->setUserResolver(fn () => $usuario);

        $puestas = $controller->puestasDisposicion($listRequest)->getData(true);

        $this->assertSame(2, $puestas['total']);
        $this->assertCount(2, $puestas['data']);
    }

    private function crearPuesta(
        int $numero,
        int $anio,
        int $unidadId,
        string $fecha,
        string $nombrePolicia
    ): PuestaDisposicion {
        return PuestaDisposicion::query()->create([
            'numero_puesta' => $numero,
            'anio' => $anio,
            'tipo_puesta' => 'PERSONA',
            'motivo' => 'PERSONA DETENIDA',
            'estatus' => 'ACTIVA',
            'nombre_policia' => $nombrePolicia,
            'area' => 'CARRETERAS',
            'fecha_puesta' => $fecha,
            'unidad_id' => $unidadId,
        ]);
    }
}
