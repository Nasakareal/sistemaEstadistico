<?php

namespace Tests\Unit;

use App\Http\Controllers\EstadisticasCarreterasController;
use App\Models\Destacamento;
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

    public function test_concentrado_calcula_totales_por_destacamento_y_conserva_sexo_no_especificado(): void
    {
        $unidad = Unidad::query()->where('slug', 'carreteras')->firstOrFail();
        $usuario = User::factory()->create(['unidad_id' => $unidad->id]);
        $destacamento = Destacamento::query()->create([
            'unidad_id' => $unidad->id,
            'clave' => 'PRUEBA-CONCENTRADO-' . uniqid(),
            'nombre' => 'DESTACAMENTO PRUEBA CONCENTRADO',
            'activo' => true,
        ]);
        $fecha = now()->toDateString();
        $anio = (int) now()->year;
        $numero = $this->siguienteNumero($anio, $unidad->id);

        $this->crearPuesta($numero, $anio, $unidad->id, $fecha, 'PRIMER ELEMENTO', [
            'destacamento_id' => $destacamento->id,
            'numero_aseguramientos' => 2,
            'numero_faltas_administrativas' => 1,
            'numero_detenidos' => 2,
            'sexo_resumen' => 'H',
        ]);
        $this->crearPuesta($numero + 1, $anio, $unidad->id, $fecha, 'SEGUNDO ELEMENTO', [
            'destacamento_id' => $destacamento->id,
            'numero_aseguramientos' => 3,
            'numero_detenidos' => 1,
            'sexo_resumen' => null,
        ]);

        $request = Request::create('/estadisticas-carreteras/concentrado', 'GET', [
            'desde' => $fecha,
            'hasta' => $fecha,
            'destacamento_id' => $destacamento->id,
        ]);
        $request->setUserResolver(fn () => $usuario);

        $data = (new EstadisticasCarreterasController())->concentrado($request)->getData();

        $this->assertSame(2, $data['totales']['puestas']);
        $this->assertSame(5, $data['totales']['aseguramientos']);
        $this->assertSame(4, $data['totales']['detenciones']);
        $this->assertSame(3, $data['totales']['hombres']);
        $this->assertSame(1, $data['totales']['no_especificado']);
        $this->assertSame('DESTACAMENTO PRUEBA CONCENTRADO', $data['filas']->first()->destacamento);
    }

    public function test_vista_elementos_suma_primer_respondiente_y_participaciones_sin_duplicar_una_puesta(): void
    {
        $unidad = Unidad::query()->where('slug', 'carreteras')->firstOrFail();
        $usuario = User::factory()->create(['unidad_id' => $unidad->id]);
        $destacamento = Destacamento::query()->create([
            'unidad_id' => $unidad->id,
            'clave' => 'PRUEBA-ELEMENTOS-' . uniqid(),
            'nombre' => 'DESTACAMENTO PRUEBA ELEMENTOS',
            'activo' => true,
        ]);
        $fecha = now()->toDateString();
        $anio = (int) now()->year;
        $numero = $this->siguienteNumero($anio, $unidad->id);

        $this->crearPuesta($numero, $anio, $unidad->id, $fecha, 'ANA MARIA LOPEZ', [
            'destacamento_id' => $destacamento->id,
            'personal_participante' => 'JUAN PEREZ',
        ]);
        $this->crearPuesta($numero + 1, $anio, $unidad->id, $fecha, 'LOPEZ ANA MARIA, JUAN PEREZ', [
            'destacamento_id' => $destacamento->id,
            'personal_participante' => 'JUAN PEREZ',
        ]);

        $request = Request::create('/estadisticas-carreteras/elementos', 'GET', [
            'desde' => $fecha,
            'hasta' => $fecha,
            'destacamento_id' => $destacamento->id,
        ]);
        $request->setUserResolver(fn () => $usuario);

        $data = (new EstadisticasCarreterasController())->elementos($request)->getData();
        $ana = $data['ranking']->firstWhere('nombre', 'ANA MARIA LOPEZ');
        $juan = $data['ranking']->firstWhere('nombre', 'JUAN PEREZ');

        $this->assertSame(2, $ana['total']);
        $this->assertSame(2, $ana['primer_respondiente']);
        $this->assertSame(2, $juan['total']);
        $this->assertSame(2, $juan['participaciones']);
        $this->assertSame(2, $data['totalPuestas']);
    }

    private function crearPuesta(
        int $numero,
        int $anio,
        int $unidadId,
        string $fecha,
        string $nombrePolicia,
        array $extra = []
    ): PuestaDisposicion {
        return PuestaDisposicion::query()->create(array_merge([
            'numero_puesta' => $numero,
            'anio' => $anio,
            'tipo_puesta' => 'PERSONA',
            'motivo' => 'PERSONA DETENIDA',
            'estatus' => 'ACTIVA',
            'nombre_policia' => $nombrePolicia,
            'area' => 'CARRETERAS',
            'fecha_puesta' => $fecha,
            'unidad_id' => $unidadId,
        ], $extra));
    }

    private function siguienteNumero(int $anio, int $unidadId): int
    {
        return (int) PuestaDisposicion::query()
            ->where('anio', $anio)
            ->where('unidad_id', $unidadId)
            ->max('numero_puesta') + 100;
    }
}
