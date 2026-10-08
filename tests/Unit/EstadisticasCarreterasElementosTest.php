<?php

namespace Tests\Unit;

use App\Http\Controllers\EstadisticasCarreterasController;
use App\Models\Destacamento;
use App\Models\IncidenciaTipo;
use App\Models\Personal;
use App\Models\PersonalIncidencia;
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

    public function test_incapacidades_ordena_por_dias_y_recorta_los_periodos_al_rango_consultado(): void
    {
        $unidad = Unidad::query()->where('slug', 'carreteras')->firstOrFail();
        $usuario = User::factory()->create(['unidad_id' => $unidad->id]);
        $tipo = IncidenciaTipo::query()->firstOrCreate(
            ['clave' => 'INCAPACIDAD'],
            ['nombre' => 'INCAPACIDAD', 'categoria' => 'PERSONAL', 'activo' => true]
        );
        $primero = Personal::query()->create([
            'unidad_id' => $unidad->id,
            'nombre' => 'ANA',
            'ap_paterno' => 'PRUEBA',
            'estatus' => 'ACTIVO',
        ]);
        $segundo = Personal::query()->create([
            'unidad_id' => $unidad->id,
            'nombre' => 'JUAN',
            'ap_paterno' => 'PRUEBA',
            'estatus' => 'ACTIVO',
        ]);

        PersonalIncidencia::query()->create([
            'personal_id' => $primero->id,
            'incidencia_tipo_id' => $tipo->id,
            'fecha_inicio' => '2025-12-20',
            'fecha_fin' => '2026-01-10',
            'activo' => true,
        ]);
        PersonalIncidencia::query()->create([
            'personal_id' => $primero->id,
            'incidencia_tipo_id' => $tipo->id,
            'fecha_inicio' => '2026-03-01',
            'fecha_fin' => null,
            'activo' => true,
        ]);
        PersonalIncidencia::query()->create([
            'personal_id' => $segundo->id,
            'incidencia_tipo_id' => $tipo->id,
            'fecha_inicio' => '2026-02-01',
            'fecha_fin' => '2026-02-20',
            'activo' => true,
        ]);

        $request = Request::create('/estadisticas-carreteras/incapacidades', 'GET', [
            'desde' => '2026-01-01',
            'hasta' => '2026-03-31',
        ]);
        $request->setUserResolver(fn () => $usuario);

        $data = (new EstadisticasCarreterasController())->incapacidades($request)->getData();

        $this->assertSame($primero->id, $data['ranking']->first()['personal_id']);
        $this->assertSame(41, $data['ranking']->first()['dias']);
        $this->assertSame(2, $data['ranking']->first()['incapacidades']);
        $this->assertSame(1, $data['ranking']->first()['sin_fecha_fin']);
        $this->assertSame(61, $data['totales']['dias']);
    }

    public function test_rendimiento_compara_elementos_del_mismo_destacamento_y_atribuye_las_puestas_por_nombre(): void
    {
        $unidad = Unidad::query()->where('slug', 'carreteras')->firstOrFail();
        $usuario = User::factory()->create(['unidad_id' => $unidad->id]);
        $destacamento = Destacamento::query()->create([
            'unidad_id' => $unidad->id,
            'clave' => 'PRUEBA-RENDIMIENTO-' . uniqid(),
            'nombre' => 'DESTACAMENTO PRUEBA RENDIMIENTO',
            'activo' => true,
        ]);
        $ana = Personal::query()->create(['unidad_id' => $unidad->id, 'destacamento_id' => $destacamento->id, 'nombre' => 'ANA', 'ap_paterno' => 'PRUEBA', 'estatus' => 'ACTIVO']);
        Personal::query()->create(['unidad_id' => $unidad->id, 'destacamento_id' => $destacamento->id, 'nombre' => 'JUAN', 'ap_paterno' => 'PRUEBA', 'estatus' => 'ACTIVO']);
        Personal::query()->create(['unidad_id' => $unidad->id, 'destacamento_id' => $destacamento->id, 'nombre' => 'LUIS', 'ap_paterno' => 'PRUEBA', 'estatus' => 'ACTIVO']);
        $anio = 2026;
        $numero = $this->siguienteNumero($anio, $unidad->id);

        foreach ([
            ['2026-10-01', 'ANA PRUEBA'], ['2026-10-02', 'ANA PRUEBA'], ['2026-10-03', 'ANA PRUEBA'],
            ['2026-10-01', 'JUAN PRUEBA'], ['2026-10-02', 'JUAN PRUEBA'],
            ['2026-10-01', 'LUIS PRUEBA'], ['2026-10-02', 'LUIS PRUEBA'],
        ] as $indice => [$fecha, $nombre]) {
            $this->crearPuesta($numero + $indice, $anio, $unidad->id, $fecha, $nombre, [
                'destacamento_id' => $destacamento->id,
                'numero_aseguramientos' => 1,
                'numero_detenidos' => 1,
            ]);
        }

        $request = Request::create('/estadisticas-carreteras/rendimiento', 'GET', [
            'desde' => '2026-10-01',
            'hasta' => '2026-10-07',
            'destacamento_id' => $destacamento->id,
        ]);
        $request->setUserResolver(fn () => $usuario);

        $data = (new EstadisticasCarreterasController())->rendimiento($request)->getData();
        $lider = $data['porElemento']->first();

        $this->assertSame($ana->id, $lider->id);
        $this->assertSame(3, $lider->primer_respondiente);
        $this->assertNotNull($lider->calificacion);
        $this->assertSame(7, $data['kpis']['puestas']);
        $this->assertSame(7, $data['kpis']['aseguramientos']);
        $this->assertSame(7, $data['kpis']['detenciones']);
        $this->assertSame(100.0, $data['kpis']['cobertura_identificacion']);
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
