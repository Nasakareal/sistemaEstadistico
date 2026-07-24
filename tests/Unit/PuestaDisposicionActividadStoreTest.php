<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\PuestaDisposicionController;
use App\Models\Actividad;
use App\Models\ActividadCategoria;
use App\Models\User;
use App\Services\DelegacionesWhatsAppAlertService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PuestaDisposicionActividadStoreTest extends TestCase
{
    use DatabaseTransactions;

    public function test_store_rejects_activity_and_hecho_at_the_same_time(): void
    {
        Auth::login(User::factory()->create(['unidad_id' => 3]));

        try {
            (new PuestaDisposicionController())->store(
                Request::create('/api/puestas-disposicion', 'POST', [
                    'actividad_id' => 1,
                    'hecho_id' => 1,
                ])
            );
            $this->fail('Se esperaba una validación por origen incompatible.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('actividad_id', $exception->errors());
        }
    }

    public function test_store_links_disposition_to_visible_activity_and_rejects_duplicate(): void
    {
        $categoria = ActividadCategoria::query()->firstOrFail();
        $usuario = User::factory()->create(['unidad_id' => 3]);
        $actividad = Actividad::query()->create([
            'actividad_categoria_id' => $categoria->id,
            'nombre' => 'ACTIVIDAD PRUEBA PUESTA ' . uniqid(),
            'cantidad' => 1,
            'unidad_org_id' => 3,
            'created_by' => $usuario->id,
            'fecha' => now()->toDateString(),
        ]);

        Auth::login($usuario);
        $this->mock(DelegacionesWhatsAppAlertService::class)
            ->shouldReceive('notificarPuestaDisposicion')
            ->once();

        $payload = [
            'actividad_id' => $actividad->id,
            'tipo_puesta' => 'VEHICULO',
            'motivo' => 'VEHICULO CON REPORTE DE ROBO',
            'nombre_policia' => 'AGENTE DE PRUEBA',
            'fecha_puesta' => now()->toDateString(),
        ];

        $response = (new PuestaDisposicionController())->store(
            Request::create('/api/puestas-disposicion', 'POST', $payload)
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame($actividad->id, (int) $response->getData(true)['actividad_id']);
        $this->assertDatabaseHas('puestas_disposicion', [
            'actividad_id' => $actividad->id,
            'hecho_id' => null,
            'unidad_id' => 3,
        ]);

        $this->expectException(ValidationException::class);
        (new PuestaDisposicionController())->store(
            Request::create('/api/puestas-disposicion', 'POST', $payload)
        );
    }
}
