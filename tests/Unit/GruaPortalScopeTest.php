<?php

namespace Tests\Unit;

use App\Http\Controllers\LiberacionCorralonController;
use App\Models\GruaUsuario;
use App\Models\LiberacionCorralon;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class GruaPortalScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grua_id')->nullable();
            $table->string('corralon')->nullable();
            $table->dateTime('fecha_inventario_grua')->nullable();
            $table->timestamps();
        });
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehiculo_id');
            $table->unsignedBigInteger('grua_id')->nullable();
            $table->timestamps();
        });
        Schema::create('liberaciones_corralon', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehiculo_id');
            $table->string('estado')->default('PENDIENTE');
            $table->timestamps();
        });
    }

    public function test_portal_uses_the_latest_service_instead_of_any_historical_service(): void
    {
        foreach ([1, 2, 3] as $vehiculoId) {
            DB::table('vehiculos')->insert([
                'id' => $vehiculoId,
                'grua_id' => $vehiculoId === 3 ? 1 : 2,
                'corralon' => 'CORRALON CENTRAL',
                'updated_at' => '2026-10-14 12:00:00',
            ]);
        }

        DB::table('servicios')->insert([
            ['id' => 1, 'vehiculo_id' => 1, 'grua_id' => 1, 'created_at' => '2026-10-14 10:00:00'],
            ['id' => 2, 'vehiculo_id' => 1, 'grua_id' => 2, 'created_at' => '2026-10-14 11:00:00'],
            ['id' => 3, 'vehiculo_id' => 2, 'grua_id' => 2, 'created_at' => '2026-10-14 10:00:00'],
            ['id' => 4, 'vehiculo_id' => 2, 'grua_id' => 1, 'created_at' => '2026-10-14 11:00:00'],
        ]);

        $method = new ReflectionMethod(LiberacionCorralonController::class, 'vehiculosEnResguardoQuery');
        $method->setAccessible(true);
        $query = $method->invoke(new LiberacionCorralonController(), 1);

        $this->assertSame([2, 3], $query->orderBy('vehiculos.id')->pluck('vehiculos.id')->all());
    }

    public function test_release_model_keeps_sierse_closure_and_audit_fields(): void
    {
        $model = new LiberacionCorralon();
        $fillable = $model->getFillable();

        foreach ([
            'sierse_folio',
            'memoria_descriptiva',
            'comprobante_pago',
            'condicion_entrega',
            'conformidad_memoria',
            'fecha_conformidad',
            'ip_entrega',
        ] as $field) {
            $this->assertContains($field, $fillable);
        }
    }

    public function test_internal_release_keeps_the_previous_validation_flow_without_sierse_fields(): void
    {
        Auth::forgetGuards();

        $request = Request::create('/liberaciones-corralon/vehiculo/1', 'POST', [
            'confirmar_entrega' => '1',
        ]);

        $method = new ReflectionMethod(LiberacionCorralonController::class, 'validarEntrega');
        $method->setAccessible(true);
        $validated = $method->invoke(new LiberacionCorralonController(), $request, true);

        $this->assertArrayNotHasKey('sierse_folio', $validated);
        $this->assertArrayNotHasKey('memoria_descriptiva', $validated);
        $this->assertArrayNotHasKey('comprobante_pago', $validated);
        $this->assertArrayNotHasKey('conformidad_memoria', $validated);
        $this->assertArrayNotHasKey('ip_entrega', $validated);
    }

    public function test_grua_portal_still_requires_the_new_sierse_delivery_fields(): void
    {
        Auth::guard('grua')->setUser(new GruaUsuario([
            'grua_id' => 1,
            'activo' => true,
        ]));

        $request = Request::create('/grua/corralon/vehiculo/1', 'POST', [
            'confirmar_entrega' => '1',
        ]);

        $method = new ReflectionMethod(LiberacionCorralonController::class, 'validarEntrega');
        $method->setAccessible(true);

        try {
            $method->invoke(new LiberacionCorralonController(), $request, true);
            $this->fail('El portal de gruas debe exigir los campos nuevos de SIERSE.');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();

            foreach ([
                'sierse_folio',
                'condicion_entrega',
                'memoria_descriptiva',
                'comprobante_pago',
                'conformidad_memoria',
            ] as $field) {
                $this->assertArrayHasKey($field, $errors);
            }
        } finally {
            Auth::forgetGuards();
        }
    }
}
