<?php

namespace Tests\Feature;

use App\Models\Grua;
use App\Models\GruaUsuario;
use App\Models\Servicio;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GruaCorralonApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_grua_can_list_and_deliver_only_its_current_vehicle(): void
    {
        Storage::fake('local');

        $grua = Grua::create(['nombre' => 'GRUA API CORRALON ' . uniqid()]);
        $otraGrua = Grua::create(['nombre' => 'OTRA GRUA API ' . uniqid()]);
        $password = 'PruebaGrua_2026';
        $user = GruaUsuario::create([
            'grua_id' => $grua->id,
            'nombre' => 'Operador Corralon',
            'email' => 'operador-corralon-' . uniqid() . '@example.test',
            'password' => Hash::make($password),
            'activo' => true,
        ]);

        $vehiculo = $this->crearVehiculo($grua->id, 'API' . random_int(1000, 9999));
        Servicio::create([
            'grua_id' => $grua->id,
            'vehiculo_id' => $vehiculo->id,
            'tipo_vehiculo' => 'AUTOMOVIL',
            'descripcion' => 'Servicio de prueba',
        ]);

        $ajeno = $this->crearVehiculo($otraGrua->id, 'OTR' . random_int(1000, 9999));
        Servicio::create([
            'grua_id' => $otraGrua->id,
            'vehiculo_id' => $ajeno->id,
            'tipo_vehiculo' => 'AUTOMOVIL',
        ]);

        $token = (string) $this->postJson('http://localhost/api/login', [
            'email' => $user->email,
            'password' => $password,
        ])->json('token');

        $list = $this->withToken($token)
            ->getJson('http://localhost/api/grua/corralon')
            ->assertOk();
        $ids = collect($list->json('items'))->pluck('id');
        $this->assertTrue($ids->contains($vehiculo->id));
        $this->assertFalse($ids->contains($ajeno->id));

        $this->withToken($token)
            ->post('http://localhost/api/grua/corralon/' . $ajeno->id . '/entregar', [
                'persona_recibe' => 'Persona Ajena',
                'identificacion_recibe' => 'INE 000',
                'sierse_folio' => 'SIERSE-AJENO',
                'condicion_entrega' => 'CONFORME_INVENTARIO',
                'foto_entrega' => UploadedFile::fake()->image('ajeno.jpg'),
                'conformidad_memoria' => '1',
                'confirmar_entrega' => '1',
            ], ['Accept' => 'application/json'])
            ->assertForbidden();

        $this->withToken($token)
            ->post('http://localhost/api/grua/corralon/' . $vehiculo->id . '/entregar', [
                'persona_recibe' => 'María Receptora',
                'identificacion_recibe' => 'INE 123456',
                'telefono_recibe' => '4431234567',
                'sierse_folio' => 'SIERSE-PRUEBA-1',
                'condicion_entrega' => 'CONFORME_INVENTARIO',
                'foto_entrega' => UploadedFile::fake()->image('entrega.jpg'),
                'conformidad_memoria' => '1',
                'confirmar_entrega' => '1',
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('item.estado', 'ENTREGADO');

        $this->assertDatabaseHas('liberaciones_corralon', [
            'vehiculo_id' => $vehiculo->id,
            'grua_id' => $grua->id,
            'grua_usuario_id' => $user->id,
            'estado' => 'ENTREGADO',
            'sierse_folio' => 'SIERSE-PRUEBA-1',
        ]);
        $this->assertNull($vehiculo->fresh()->corralon);
    }

    private function crearVehiculo(int $gruaId, string $placas): Vehiculo
    {
        return Vehiculo::create([
            'marca' => 'NISSAN',
            'modelo' => '2020',
            'tipo' => 'SEDAN',
            'linea' => 'VERSA',
            'color' => 'BLANCO',
            'placas' => $placas,
            'estado_placas' => 'MICHOACAN',
            'serie' => strtoupper(substr(uniqid('VIN'), 0, 17)),
            'capacidad_personas' => 5,
            'tipo_servicio' => 'PARTICULAR',
            'tarjeta_circulacion_nombre' => 'PRUEBA',
            'grua_id' => $gruaId,
            'grua' => 'PRUEBA',
            'corralon' => 'CORRALON PRUEBA',
        ]);
    }
}
