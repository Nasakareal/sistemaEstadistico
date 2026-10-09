<?php

namespace Tests\Unit;

use App\Http\Controllers\PuestaDisposicionController;
use App\Models\Destacamento;
use App\Models\PuestaDisposicion;
use App\Models\User;
use App\Services\DelegacionesWhatsAppAlertService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PuestaDisposicionDestacamentoCarreterasTest extends TestCase
{
    use DatabaseTransactions;

    public function test_selector_de_destacamento_usa_el_mismo_tema_oscuro_que_los_demais_catalogos(): void
    {
        foreach (['create', 'edit'] as $view) {
            $source = file_get_contents(resource_path('views/puestas_disposicion/' . $view . '.blade.php'));

            $this->assertStringContainsString('#destacamento_id option', $source);
            $this->assertStringContainsString('#destacamento_id option:checked', $source);
            $this->assertStringContainsString('background-color: #12263c !important;', $source);
        }
    }

    public function test_creacion_de_carreteras_muestra_y_guarda_el_destacamento_seleccionado(): void
    {
        [$usuario, $destacamentoActual, $destacamentoSeleccionado] = $this->escenarioCarreteras();
        Auth::login($usuario);

        $vista = (new PuestaDisposicionController())->create(Request::create('/puestas-disposicion/create'));

        $this->assertTrue($vista->getData()['puedeSeleccionarDestacamento']);
        $this->assertTrue($vista->getData()['destacamentos']->contains('id', $destacamentoActual->id));
        $this->assertTrue($vista->getData()['destacamentos']->contains('id', $destacamentoSeleccionado->id));

        $this->mock(DelegacionesWhatsAppAlertService::class)
            ->shouldReceive('notificarPuestaDisposicion')
            ->once();

        (new PuestaDisposicionController())->store(Request::create(
            '/puestas-disposicion',
            'POST',
            $this->payload($destacamentoSeleccionado->id)
        ));

        $this->assertDatabaseHas('puestas_disposicion', [
            'created_by' => $usuario->id,
            'unidad_id' => 4,
            'destacamento_id' => $destacamentoSeleccionado->id,
        ]);
    }

    public function test_edicion_de_carreteras_permite_cambiar_el_destacamento(): void
    {
        [$usuario, $destacamentoActual, $destacamentoSeleccionado] = $this->escenarioCarreteras();
        Auth::login($usuario);
        $puesta = $this->crearPuesta($usuario, $destacamentoActual);

        $vista = (new PuestaDisposicionController())->edit($puesta);
        $this->assertTrue($vista->getData()['puedeSeleccionarDestacamento']);

        (new PuestaDisposicionController())->update(
            Request::create(
                '/puestas-disposicion/' . $puesta->id,
                'PUT',
                array_merge($this->payload($destacamentoSeleccionado->id), [
                    'numero_puesta' => $puesta->numero_puesta,
                    'anio' => $puesta->anio,
                ])
            ),
            $puesta
        );

        $this->assertSame($destacamentoSeleccionado->id, (int)$puesta->fresh()->destacamento_id);
    }

    public function test_carreteras_no_puede_asignar_un_destacamento_de_otra_unidad(): void
    {
        [$usuario] = $this->escenarioCarreteras();
        $ajeno = Destacamento::query()->create([
            'unidad_id' => 1,
            'clave' => 'AJENO-' . uniqid(),
            'nombre' => 'DESTACAMENTO AJENO ' . uniqid(),
            'activo' => true,
        ]);
        Auth::login($usuario);

        $this->expectException(ValidationException::class);
        (new PuestaDisposicionController())->store(Request::create(
            '/puestas-disposicion',
            'POST',
            $this->payload($ajeno->id)
        ));
    }

    private function escenarioCarreteras(): array
    {
        $actual = Destacamento::query()->create([
            'unidad_id' => 4,
            'clave' => 'ACT-' . uniqid(),
            'nombre' => 'DESTACAMENTO ACTUAL ' . uniqid(),
            'activo' => true,
        ]);
        $seleccionado = Destacamento::query()->create([
            'unidad_id' => 4,
            'clave' => 'SEL-' . uniqid(),
            'nombre' => 'DESTACAMENTO SELECCIONADO ' . uniqid(),
            'activo' => true,
        ]);
        $usuario = User::factory()->create([
            'unidad_id' => 4,
            'destacamento_id' => $actual->id,
        ]);

        return [$usuario, $actual, $seleccionado];
    }

    private function payload(int $destacamentoId): array
    {
        return [
            'tipo_puesta' => 'PERSONA',
            'motivo' => 'PERSONA DETENIDA',
            'nombre_policia' => 'AGENTE DE PRUEBA',
            'fecha_puesta' => now()->toDateString(),
            'destacamento_id' => $destacamentoId,
        ];
    }

    private function crearPuesta(User $usuario, Destacamento $destacamento): PuestaDisposicion
    {
        $anio = (int)now()->year;
        $numero = (int)PuestaDisposicion::query()
            ->where('anio', $anio)
            ->where('unidad_id', 4)
            ->max('numero_puesta') + 100;

        return PuestaDisposicion::query()->create([
            'numero_puesta' => $numero,
            'anio' => $anio,
            'tipo_puesta' => 'PERSONA',
            'motivo' => 'PERSONA DETENIDA',
            'estatus' => 'ACTIVA',
            'nombre_policia' => 'AGENTE DE PRUEBA',
            'area' => 'PROTECCIÓN A CARRETERAS',
            'fecha_puesta' => now()->toDateString(),
            'unidad_id' => 4,
            'destacamento_id' => $destacamento->id,
            'created_by' => $usuario->id,
        ]);
    }
}
