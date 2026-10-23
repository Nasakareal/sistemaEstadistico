<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\HechoController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HechoPatrullasSiniestrosCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');

        Schema::create('patrullas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unidad_id');
            $table->string('numero_economico')->nullable();
            $table->boolean('activa')->default(true);
        });
    }

    public function test_catalog_lists_only_active_patrol_numbers_from_siniestros(): void
    {
        DB::table('patrullas')->insert([
            ['id' => 1, 'unidad_id' => 1, 'numero_economico' => '04-20', 'activa' => true],
            ['id' => 2, 'unidad_id' => 1, 'numero_economico' => '04-3', 'activa' => true],
            ['id' => 3, 'unidad_id' => 1, 'numero_economico' => 'INACTIVA', 'activa' => false],
            ['id' => 4, 'unidad_id' => 2, 'numero_economico' => 'DELEGACION', 'activa' => true],
            ['id' => 5, 'unidad_id' => 1, 'numero_economico' => '   ', 'activa' => true],
        ]);

        $response = (new HechoController())->patrullasSiniestros(
            $this->requestForUnit(1)
        );

        $this->assertSame([
            ['id' => 2, 'numero_economico' => '04-3'],
            ['id' => 1, 'numero_economico' => '04-20'],
        ], $response->getData(true)['data']);
    }

    public function test_catalog_rejects_users_outside_siniestros(): void
    {
        try {
            (new HechoController())->patrullasSiniestros($this->requestForUnit(2));
            $this->fail('El catálogo aceptó a un usuario ajeno a Siniestros.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    private function requestForUnit(int $unidadId): Request
    {
        $user = new User();
        $user->forceFill(['unidad_id' => $unidadId]);

        $request = Request::create(
            '/api/hechos/catalogos/patrullas-siniestros',
            'GET'
        );
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
