<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\FeedController;
use App\Models\Hechos;
use App\Support\HechoAccess;
use ReflectionMethod;
use Tests\TestCase;

class HechoAccessVisibilityTest extends TestCase
{
    public function test_subdirector_siniestros_scope_solo_incluye_su_unidad(): void
    {
        $usuario = new HechoAccessVisibilityFakeUser(['Subdirector'], 1);
        $query = Hechos::query();

        HechoAccess::applyVisibilityScope($query, $usuario);

        $bindings = $query->getBindings();

        $this->assertContains(1, $bindings);
        $this->assertNotContains(2, $bindings);
    }

    public function test_feed_de_siniestros_no_agrega_delegaciones_para_subdirector(): void
    {
        $controller = new FeedController();
        $method = new ReflectionMethod($controller, 'unidadIdsHechosParaFeed');
        $method->setAccessible(true);

        $ids = $method->invoke($controller, new HechoAccessVisibilityFakeUser(['Subdirector'], 1), [1]);

        $this->assertSame([1], $ids);
    }
}

class HechoAccessVisibilityFakeUser
{
    public int $unidad_id;
    public int $delegacion_id;
    private array $roles;

    public function __construct(array $roles, int $unidadId, int $delegacionId = 0)
    {
        $this->roles = $roles;
        $this->unidad_id = $unidadId;
        $this->delegacion_id = $delegacionId;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function hasAnyRole(array $roles): bool
    {
        return count(array_intersect($roles, $this->roles)) > 0;
    }
}
