<?php

namespace Tests\Unit;

use App\Http\Controllers\PuestaDisposicionController;
use App\Models\PuestaDisposicion;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PuestaDisposicionWebIndexFilterTest extends TestCase
{
    use DatabaseTransactions;

    public function test_index_separa_las_puestas_por_unidad_para_usuarios_con_vista_global(): void
    {
        $this->assertTrue(Unidad::query()->whereKey(1)->exists());
        $this->assertTrue(Unidad::query()->whereKey(3)->exists());
        $this->assertTrue(Unidad::query()->whereKey(5)->exists());

        $anio = (int)now()->year;
        $numero = (int)PuestaDisposicion::query()
            ->where('anio', $anio)
            ->max('numero_puesta') + 200;

        $siniestros = $this->crearPuesta($numero, $anio, 1, 'SINIESTROS');
        $vialidades = $this->crearPuesta($numero + 1, $anio, 5, 'VIALIDADES');

        $usuario = User::factory()->create(['unidad_id' => 3]);
        Auth::login($usuario);

        $request = Request::create('/puestas-disposicion', 'GET', [
            'anio' => $anio,
            'unidad_id' => 5,
        ]);

        $response = (new PuestaDisposicionController())->index($request);
        $puestas = $response->getData()['puestas'];

        $this->assertTrue($response->getData()['puedeFiltrarUnidad']);
        $this->assertSame(5, $response->getData()['unidadSeleccionadaId']);
        $this->assertTrue($response->getData()['unidadesFiltro']->contains('id', 5));
        $this->assertTrue($puestas->contains('id', $vialidades->id));
        $this->assertFalse($puestas->contains('id', $siniestros->id));
        $this->assertTrue($puestas->every(function (PuestaDisposicion $puesta) {
            return (int)$puesta->unidad_id === 5;
        }));
    }

    public function test_usuario_de_unidad_no_puede_ampliar_su_alcance_con_el_filtro(): void
    {
        $anio = (int)now()->year;
        $numero = (int)PuestaDisposicion::query()
            ->where('anio', $anio)
            ->max('numero_puesta') + 300;

        $propia = $this->crearPuesta($numero, $anio, 1, 'SINIESTROS');
        $ajena = $this->crearPuesta($numero + 1, $anio, 5, 'VIALIDADES');

        $usuario = User::factory()->create(['unidad_id' => 1]);
        Auth::login($usuario);

        $request = Request::create('/puestas-disposicion', 'GET', [
            'anio' => $anio,
            'unidad_id' => 5,
        ]);

        $response = (new PuestaDisposicionController())->index($request);
        $puestas = $response->getData()['puestas'];

        $this->assertFalse($response->getData()['puedeFiltrarUnidad']);
        $this->assertNull($response->getData()['unidadSeleccionadaId']);
        $this->assertTrue($puestas->contains('id', $propia->id));
        $this->assertFalse($puestas->contains('id', $ajena->id));
        $this->assertTrue($puestas->every(function (PuestaDisposicion $puesta) {
            return (int)$puesta->unidad_id === 1;
        }));
    }

    public function test_index_busca_por_carpeta_y_oficio_y_filtra_por_mes(): void
    {
        $anio = (int)now()->year;
        $numero = (int)PuestaDisposicion::query()
            ->where('anio', $anio)
            ->max('numero_puesta') + 400;

        $carpeta = 'CI-FILTRO-' . $numero;
        $oficio = 'OF-FILTRO-' . $numero;
        $coincidente = $this->crearPuesta($numero, $anio, 5, 'VIALIDADES', [
            'carpeta_investigacion' => $carpeta,
            'oficio' => $oficio,
            'fecha_puesta' => now()->toDateString(),
        ]);
        $fueraDelMes = $this->crearPuesta($numero + 1, $anio, 5, 'VIALIDADES', [
            'carpeta_investigacion' => $carpeta,
            'oficio' => $oficio,
            'fecha_puesta' => now()->subMonthNoOverflow()->toDateString(),
        ]);
        $otra = $this->crearPuesta($numero + 2, $anio, 5, 'VIALIDADES', [
            'carpeta_investigacion' => 'CI-DISTINTA-' . $numero,
            'oficio' => 'OF-DISTINTO-' . $numero,
            'fecha_puesta' => now()->toDateString(),
        ]);

        Auth::login(User::factory()->create(['unidad_id' => 3]));

        $request = Request::create('/puestas-disposicion', 'GET', [
            'anio' => 'TODOS',
            'mes' => now()->format('Y-m'),
            'carpeta_investigacion' => $carpeta,
            'oficio' => $oficio,
        ]);

        $response = (new PuestaDisposicionController())->index($request);
        $puestas = $response->getData()['puestas'];

        $this->assertTrue($puestas->contains('id', $coincidente->id));
        $this->assertFalse($puestas->contains('id', $fueraDelMes->id));
        $this->assertFalse($puestas->contains('id', $otra->id));
    }

    public function test_lupita_busca_en_los_datos_de_las_personas_relacionadas(): void
    {
        $anio = (int)now()->year;
        $numero = (int)PuestaDisposicion::query()
            ->where('anio', $anio)
            ->max('numero_puesta') + 500;

        $puesta = $this->crearPuesta($numero, $anio, 5, 'VIALIDADES');
        $puesta->personas()->create([
            'nombre_completo' => 'PERSONA BUSQUEDA ' . $numero,
            'edad' => 31,
            'calidad' => 'DETENIDA',
        ]);
        $otra = $this->crearPuesta($numero + 1, $anio, 5, 'VIALIDADES');

        Auth::login(User::factory()->create(['unidad_id' => 3]));

        $request = Request::create('/puestas-disposicion', 'GET', [
            'anio' => $anio,
            'q' => 'PERSONA BUSQUEDA ' . $numero,
        ]);

        $response = (new PuestaDisposicionController())->index($request);
        $puestas = $response->getData()['puestas'];

        $this->assertTrue($puestas->contains('id', $puesta->id));
        $this->assertFalse($puestas->contains('id', $otra->id));
    }

    public function test_index_ordena_por_encabezado_en_ambas_direcciones_y_conserva_filtros(): void
    {
        $anio = (int)now()->year;
        $numero = (int)PuestaDisposicion::query()
            ->where('anio', $anio)
            ->max('numero_puesta') + 600;
        $marca = 'ORDEN-' . $numero;

        $primera = $this->crearPuesta($numero, $anio, 1, 'SINIESTROS', [
            'nombre_policia' => 'ALFA ' . $marca,
        ]);
        $segunda = $this->crearPuesta($numero + 1, $anio, 3, 'COORDINACION', [
            'nombre_policia' => 'BRAVO ' . $marca,
        ]);
        $tercera = $this->crearPuesta($numero + 2, $anio, 5, 'VIALIDADES', [
            'nombre_policia' => 'CHARLIE ' . $marca,
        ]);

        Auth::login(User::factory()->create(['unidad_id' => 3]));

        $ascendente = (new PuestaDisposicionController())->index(Request::create(
            '/puestas-disposicion',
            'GET',
            ['anio' => $anio, 'q' => $marca, 'orden' => 'policia', 'direccion' => 'asc']
        ));

        $this->assertSame('policia', $ascendente->getData()['orden']);
        $this->assertSame('asc', $ascendente->getData()['direccion']);
        $this->assertSame(
            [$primera->id, $segunda->id, $tercera->id],
            $ascendente->getData()['puestas']->pluck('id')->all()
        );

        $descendente = (new PuestaDisposicionController())->index(Request::create(
            '/puestas-disposicion',
            'GET',
            ['anio' => $anio, 'q' => $marca, 'orden' => 'policia', 'direccion' => 'desc']
        ));

        $this->assertSame(
            [$tercera->id, $segunda->id, $primera->id],
            $descendente->getData()['puestas']->pluck('id')->all()
        );

        $porUnidad = (new PuestaDisposicionController())->index(Request::create(
            '/puestas-disposicion',
            'GET',
            ['anio' => $anio, 'q' => $marca, 'orden' => 'unidad', 'direccion' => 'asc']
        ));
        $nombresUnidad = $porUnidad->getData()['puestas']
            ->map(fn (PuestaDisposicion $puesta) => optional($puesta->unidad)->nombre)
            ->values();

        $this->assertSame(
            $nombresUnidad->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all(),
            $nombresUnidad->all()
        );
    }

    public function test_filtra_por_numero_exacto_del_concentrado_de_carreteras(): void
    {
        $anio = (int)now()->year;
        $numero = (int)PuestaDisposicion::query()
            ->where('anio', $anio)
            ->max('numero_puesta') + 700;

        $folioSiete = $this->crearPuesta($numero, $anio, 4, 'CARRETERAS', [
            'fuente_importacion' => 'IPH_CARRETERAS_2026',
            'secuencia_origen' => 8,
            'numero_origen' => '7',
            'folio_origen' => '2677195',
        ]);
        $folioDiecisiete = $this->crearPuesta($numero + 1, $anio, 4, 'CARRETERAS', [
            'fuente_importacion' => 'IPH_CARRETERAS_2026',
            'secuencia_origen' => 18,
            'numero_origen' => '17',
            'folio_origen' => 'OTRO-FOLIO',
        ]);

        Auth::login(User::factory()->create(['unidad_id' => 3]));

        $response = (new PuestaDisposicionController())->index(Request::create(
            '/puestas-disposicion',
            'GET',
            ['anio' => $anio, 'unidad_id' => 4, 'numero_origen' => '7']
        ));

        $this->assertSame('7', $response->getData()['numeroOrigenSeleccionado']);
        $this->assertTrue($response->getData()['puestas']->contains('id', $folioSiete->id));
        $this->assertFalse($response->getData()['puestas']->contains('id', $folioDiecisiete->id));
    }

    public function test_no_elimina_fisicamente_una_puesta_importada_de_carreteras(): void
    {
        $anio = (int)now()->year;
        $numero = (int)PuestaDisposicion::query()
            ->where('anio', $anio)
            ->max('numero_puesta') + 800;
        $puesta = $this->crearPuesta($numero, $anio, 4, 'CARRETERAS', [
            'fuente_importacion' => 'IPH_CARRETERAS_2026',
            'secuencia_origen' => 8,
            'numero_origen' => '7',
        ]);

        Auth::login(User::factory()->create(['unidad_id' => 3]));

        $response = (new PuestaDisposicionController())->destroy($puesta);

        $this->assertDatabaseHas('puestas_disposicion', ['id' => $puesta->id]);
        $this->assertTrue($response->getSession()->has('error'));
    }

    private function crearPuesta(
        int $numero,
        int $anio,
        int $unidadId,
        string $area,
        array $overrides = []
    ): PuestaDisposicion
    {
        return PuestaDisposicion::query()->create(array_merge([
            'numero_puesta' => $numero,
            'anio' => $anio,
            'tipo_puesta' => 'PERSONA',
            'motivo' => 'PERSONA DETENIDA',
            'estatus' => 'ACTIVA',
            'nombre_policia' => 'AGENTE DE PRUEBA',
            'area' => $area,
            'fecha_puesta' => now()->toDateString(),
            'unidad_id' => $unidadId,
        ], $overrides));
    }
}
