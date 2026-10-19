<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ConduceLegalidadController;
use App\Models\ConduceLegalidadCaptura;
use App\Models\ConduceLegalidadOperativo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class ConduceLegalidadInventarioFolioReservationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('inventario_vehiculo_folio_reservas', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_uuid')->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('conduce_legalidad_vehiculos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('captura_id');
            $table->string('numero_inventario')->nullable();
        });
    }

    public function test_inventory_series_starts_at_00001_and_retry_keeps_the_same_folio(): void
    {
        $method = new ReflectionMethod(
            ConduceLegalidadController::class,
            'reservarNumeroInventario'
        );
        $method->setAccessible(true);
        $controller = new ConduceLegalidadController();

        $first = $method->invoke($controller, '11111111-1111-4111-8111-111111111111', 9);
        $retry = $method->invoke($controller, '11111111-1111-4111-8111-111111111111', 9);
        $second = $method->invoke($controller, '22222222-2222-4222-8222-222222222222', 10);

        $this->assertSame('00001', $first);
        $this->assertSame($first, $retry);
        $this->assertSame('00002', $second);
        $this->assertSame(2, DB::table('inventario_vehiculo_folio_reservas')->count());
    }

    public function test_editing_a_historical_vehicle_does_not_assign_or_consume_a_folio(): void
    {
        DB::table('conduce_legalidad_vehiculos')->insert([
            'captura_id' => 77,
            'numero_inventario' => null,
        ]);
        $captura = new ConduceLegalidadCaptura();
        $captura->id = 77;
        $captura->client_uuid = '11111111-1111-4111-8111-111111111111';
        $operativo = new ConduceLegalidadOperativo(['tipo_operativo' => 'conduce_legalidad']);
        $request = Request::create('/api/conduce-legalidad', 'PUT', [
            'client_uuid' => $captura->client_uuid,
            'vehiculos' => [['numero_inventario' => '99999']],
        ]);

        $prepare = new ReflectionMethod(
            ConduceLegalidadController::class,
            'prepararNumeroInventarioAutomatico'
        );
        $prepare->setAccessible(true);
        $controller = new ConduceLegalidadController();
        $prepare->invoke($controller, $request, $operativo, $captura);

        $rules = new ReflectionMethod(ConduceLegalidadController::class, 'capturaRules');
        $rules->setAccessible(true);
        $historicalRules = $rules->invoke($controller, $operativo, $captura);

        $this->assertNull($request->input('vehiculos.0.numero_inventario'));
        $this->assertContains('nullable', $historicalRules['vehiculos.*.numero_inventario']);
        $this->assertSame(0, DB::table('inventario_vehiculo_folio_reservas')->count());
    }
}
