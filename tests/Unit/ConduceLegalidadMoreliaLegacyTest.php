<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ConduceLegalidadController;
use App\Models\ConduceLegalidadOperativo;
use App\Models\Delegacion;
use App\Models\Grua;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use ReflectionMethod;
use Tests\TestCase;

class ConduceLegalidadMoreliaLegacyTest extends TestCase
{
    public function test_morelia_delegation_uses_siniestros_crane_catalog_like_vialidades(): void
    {
        $query = $this->invoke('gruasOperativoQuery', $this->moreliaOperativo());

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertStringContainsString('unidad_grua', $query->toSql());
        $this->assertSame([1], $query->getBindings());
    }

    public function test_morelia_cranes_are_tagged_for_the_installed_mobile_filter(): void
    {
        $operativo = $this->moreliaOperativo();
        $grua = new Grua(['nombre' => 'MUÑOZ']);
        $grua->id = 10;
        $grua->setRelation('unidades', new Collection());
        $grua->setRelation('delegaciones', new Collection());
        $gruas = new Collection([$grua]);

        $this->invoke('marcarGruasComoVialidadesUrbanas', $gruas, $operativo);

        $this->assertTrue($grua->unidades->contains('id', 2));
        $this->assertTrue($grua->unidades->contains('id', 5));
        $this->assertTrue($grua->delegaciones->contains('id', 5));
    }

    public function test_morelia_delegation_uses_justicia_civica_release_address(): void
    {
        $texto = $this->invoke(
            'liberacionTicket',
            $this->moreliaOperativo(),
            ['unidad_id' => 2, 'delegacion_id' => 5],
            'La entrega física se realizará en el depósito autorizado.'
        );

        $this->assertStringContainsString(
            'Dirección de Justicia Cívica y Mediación Administrativa',
            $texto
        );
        $this->assertStringContainsString('Periférico Paseo de la República #5000', $texto);
        $this->assertStringNotContainsString('Delegación de Morelia', $texto);
    }

    public function test_morelia_ticket_uses_adolfo_millan_as_operational_lead(): void
    {
        $controller = new class extends ConduceLegalidadController {
            protected function esDelegacionMorelia(
                int $delegacionId,
                ?Delegacion $delegacion = null
            ): bool
            {
                return $delegacionId === 5;
            }
        };
        $method = new ReflectionMethod(ConduceLegalidadController::class, 'supervisorTicket');
        $method->setAccessible(true);
        $supervisor = $method->invoke($controller, 2, 5);

        $this->assertSame('Adolfo Millan Montes', $supervisor['nombre']);
        $this->assertSame(
            'Encargado del operativo Conduce con Legalidad',
            $supervisor['cargo']
        );
        $this->assertStringNotContainsString('Luis Eduardo Lugo Ordorica', $supervisor['nombre']);
    }

    public function test_other_delegations_keep_their_own_crane_catalog(): void
    {
        $delegacion = new Delegacion(['nombre' => 'Pátzcuaro']);
        $delegacion->id = 6;
        $operativo = new ConduceLegalidadOperativo([
            'unidad_id' => 2,
            'delegacion_id' => 6,
        ]);
        $operativo->setRelation('delegacion', $delegacion);

        $query = $this->invoke('gruasOperativoQuery', $operativo);

        $this->assertStringContainsString('delegacion_grua', $query->toSql());
        $this->assertSame([6], $query->getBindings());
    }

    private function moreliaOperativo(): ConduceLegalidadOperativo
    {
        $delegacion = new Delegacion(['nombre' => 'Morelia']);
        $delegacion->id = 5;
        $operativo = new ConduceLegalidadOperativo([
            'unidad_id' => 2,
            'delegacion_id' => 5,
        ]);
        $operativo->setRelation('delegacion', $delegacion);

        return $operativo;
    }

    private function invoke(string $methodName, ...$arguments)
    {
        $method = new ReflectionMethod(ConduceLegalidadController::class, $methodName);
        $method->setAccessible(true);

        return $method->invoke(new ConduceLegalidadController(), ...$arguments);
    }
}
