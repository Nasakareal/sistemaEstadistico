<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\PatrullaServicioController;
use App\Http\Controllers\BitacoraServicioPatrullaController;
use App\Models\Actividad;
use App\Models\BitacoraServicioPatrulla;
use App\Models\Hechos;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class PatrullaBitacoraRangeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'patrulla_range_testing');
        config()->set('database.connections.patrulla_range_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('patrulla_range_testing');
        DB::setDefaultConnection('patrulla_range_testing');

        Schema::create('bitacora_servicio_patrullas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patrulla_id');
            $table->unsignedBigInteger('turno_id')->nullable();
            $table->date('fecha');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->unsignedBigInteger('capturado_por_user_id')->nullable();
            $table->string('capturado_por_nombre')->nullable();
            $table->unsignedBigInteger('kilometraje_inicio')->nullable();
            $table->unsignedBigInteger('kilometraje_fin')->nullable();
            $table->decimal('combustible_inicio')->nullable();
            $table->decimal('combustible_fin')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estatus')->default('abierta');
            $table->timestamp('cerrada_at')->nullable();
            $table->timestamps();
        });

        foreach (['actividades', 'hechos'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('patrulla_id')->nullable();
                $table->unsignedBigInteger('bitacora_servicio_patrulla_id')->nullable();
                $table->date('fecha')->nullable();
                $table->time('hora')->nullable();
                $table->timestamps();
            });
        }

        Carbon::setTestNow(Carbon::parse('2026-09-16 22:00:00', 'America/Mexico_City'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_first_log_of_day_includes_services_captured_before_reception(): void
    {
        $bitacora = $this->bitacora('20:36:00');

        foreach ($this->controllers() as $controller) {
            [$inicio] = $this->rango($controller, $bitacora);
            $this->assertSame('2026-09-16 00:00:00', $inicio->format('Y-m-d H:i:s'));
        }
    }

    public function test_later_log_of_same_day_keeps_its_real_start_time(): void
    {
        $this->bitacora('08:00:00');
        $bitacora = $this->bitacora('20:36:00', 18);

        foreach ($this->controllers() as $controller) {
            [$inicio] = $this->rango($controller, $bitacora);
            $this->assertSame('2026-09-16 20:36:00', $inicio->format('Y-m-d H:i:s'));
        }
    }

    public function test_new_operational_records_are_linked_to_the_open_patrol_log(): void
    {
        $bitacora = $this->bitacora('08:00:00');

        $actividad = Actividad::query()->create([
            'created_by' => 42,
            'fecha' => '2026-09-16',
            'hora' => '10:15:00',
        ]);
        $hecho = Hechos::query()->create([
            'created_by' => 42,
            'fecha' => '2026-09-16',
            'hora' => '11:30:00',
        ]);

        foreach ([$actividad, $hecho] as $record) {
            $this->assertSame($bitacora->id, $record->bitacora_servicio_patrulla_id);
            $this->assertSame($bitacora->patrulla_id, $record->patrulla_id);
        }
    }

    private function bitacora(string $hora, int $patrullaId = 17): BitacoraServicioPatrulla
    {
        return BitacoraServicioPatrulla::query()->create([
            'patrulla_id' => $patrullaId,
            'fecha' => '2026-09-16',
            'hora_inicio' => $hora,
            'capturado_por_user_id' => 42,
            'capturado_por_nombre' => 'Prueba',
            'kilometraje_inicio' => 92,
            'combustible_inicio' => 100,
            'estatus' => 'abierta',
        ]);
    }

    private function controllers(): array
    {
        return [
            new PatrullaServicioController(),
            new BitacoraServicioPatrullaController(),
        ];
    }

    private function rango(object $controller, BitacoraServicioPatrulla $bitacora): array
    {
        $method = new ReflectionMethod($controller, 'rangoBitacora');
        $method->setAccessible(true);

        return $method->invoke($controller, $bitacora);
    }
}
