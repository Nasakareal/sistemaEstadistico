<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\SettingsStatisticsFilesController;
use App\Models\Patrulla;
use App\Models\Role;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

class SettingsStatisticsFilesControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_siniestros_module_lists_every_user_assigned_to_a_patrol_by_shift(): void
    {
        $suffix = (string) random_int(100000, 999999);
        $turnoA = Turno::query()->firstOrCreate(
            ['slug' => 'a'],
            ['nombre' => 'A', 'activo' => true]
        );
        $turnoB = Turno::query()->firstOrCreate(
            ['slug' => 'b'],
            ['nombre' => 'B', 'activo' => true]
        );
        $patrulla = Patrulla::query()->create([
            'numero_economico' => 'TEST-' . $suffix,
            'unidad_id' => 1,
            'activa' => true,
            'tipo' => 'SEDAN',
            'marca' => 'DODGE',
            'linea' => 'CHARGER',
        ]);

        $firstUser = User::factory()->create([
            'name' => 'AGENTE UNO ' . $suffix,
            'unidad_id' => 1,
            'turno_id' => $turnoA->id,
            'patrulla_id' => $patrulla->id,
            'estado' => 'Activo',
        ]);
        $secondUser = User::factory()->create([
            'name' => 'AGENTE DOS ' . $suffix,
            'unidad_id' => 1,
            'turno_id' => $turnoA->id,
            'patrulla_id' => $patrulla->id,
            'estado' => 'Activo',
        ]);
        $thirdUser = User::factory()->create([
            'name' => 'AGENTE TRES ' . $suffix,
            'unidad_id' => 1,
            'turno_id' => $turnoB->id,
            'patrulla_id' => $patrulla->id,
            'estado' => 'Activo',
        ]);

        $actor = User::factory()->create(['unidad_id' => 1]);
        $role = Role::query()->firstOrCreate([
            'name' => 'Subdirector',
            'guard_name' => 'web',
        ]);
        $actor->assignRole($role);

        $request = Request::create('/api/settings/statistics-files', 'GET');
        $request->setUserResolver(fn () => $actor);

        $response = (new SettingsStatisticsFilesController())->index($request);
        $payload = $response->getData(true);
        $module = collect($payload['modules'])->firstWhere('id', 'siniestros');
        $fleetItem = collect($module['patrullas'])->firstWhere('id', $patrulla->id);

        $this->assertNotNull($fleetItem);
        $this->assertCount(3, $fleetItem['usuarios']);
        $this->assertEqualsCanonicalizing(
            [$firstUser->nombre_completo, $secondUser->nombre_completo],
            collect($fleetItem['usuarios'])
                ->where('turno', 'A')
                ->pluck('nombre')
                ->all()
        );
        $this->assertSame(
            [$thirdUser->nombre_completo],
            collect($fleetItem['usuarios'])
                ->where('turno', 'B')
                ->pluck('nombre')
                ->values()
                ->all()
        );
    }
}
