<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ConduceLegalidadController;
use App\Models\ConduceLegalidadCaptura;
use App\Models\ConduceLegalidadOperativo;
use App\Models\User;
use ReflectionMethod;
use Tests\TestCase;

class ConduceLegalidadTicketSupervisorTest extends TestCase
{
    public function test_delegaciones_ticket_uses_delegate_from_specific_delegation(): void
    {
        $controller = $this->controllerWithDelegate();
        $lines = $this->appendSupervisor($controller, 2, 6);

        $this->assertContains('Supervisó: Ángel Peralta Hernández', $lines);
        $this->assertContains('Delegado de la Delegación de Pátzcuaro', $lines);
        $this->assertNotContains('Supervisó: Luis Eduardo Lugo Ordorica', $lines);
    }

    public function test_vialidades_ticket_keeps_subdirector_signature(): void
    {
        $controller = $this->controllerWithDelegate();
        $lines = $this->appendSupervisor($controller, 5, null);

        $this->assertContains('Supervisó: Luis Eduardo Lugo Ordorica', $lines);
        $this->assertContains('Subdirector de Vialidades Urbanas', $lines);
    }

    public function test_ticket_uses_operativo_scope_before_sharing_user_or_capture(): void
    {
        $operativo = new ConduceLegalidadOperativo();
        $operativo->forceFill([
            'unidad_id' => 2,
            'delegacion_id' => 6,
        ]);
        $captura = new ConduceLegalidadCaptura();
        $captura->forceFill([
            'unidad_id' => 5,
            'delegacion_id' => null,
        ]);
        $sharingUser = (object) [
            'unidad_id' => 5,
            'delegacion_id' => null,
        ];

        $method = new ReflectionMethod(ConduceLegalidadController::class, 'adscripcionTicket');
        $method->setAccessible(true);
        $scope = $method->invoke(
            new ConduceLegalidadController(),
            $operativo,
            $sharingUser,
            $captura
        );

        $this->assertSame(2, $scope['unidad_id']);
        $this->assertSame(6, $scope['delegacion_id']);
    }

    public function test_legacy_prevention_name_prints_as_operativo_de_alcoholimetria(): void
    {
        $operativo = new ConduceLegalidadOperativo();
        $operativo->forceFill([
            'tipo_operativo' => null,
            'nombre' => 'Operativo de Prevención de Accidentes',
            'objetivo' => null,
        ]);

        $method = new ReflectionMethod(
            ConduceLegalidadController::class,
            'nombreTicketOperativo'
        );
        $method->setAccessible(true);
        $nombre = $method->invoke(new ConduceLegalidadController(), $operativo);

        $this->assertSame('Operativo de Alcoholimetría', $nombre);
        $this->assertStringNotContainsString('Prevención', $nombre);
    }

    private function appendSupervisor(
        ConduceLegalidadController $controller,
        int $unidadId,
        ?int $delegacionId
    ): array {
        $method = new ReflectionMethod(ConduceLegalidadController::class, 'appendSupervisorTicket');
        $method->setAccessible(true);
        $lines = [];
        $arguments = [&$lines, $unidadId, $delegacionId];
        $method->invokeArgs($controller, $arguments);

        return $lines;
    }

    private function controllerWithDelegate(): ConduceLegalidadController
    {
        return new class extends ConduceLegalidadController {
            protected function delegadoSupervisor(int $delegacionId): ?User
            {
                if ($delegacionId !== 6) {
                    return null;
                }

                $user = new User();
                $user->forceFill(['name' => 'Ángel Peralta Hernández']);

                return $user;
            }

            protected function nombreDelegacionTicket(int $delegacionId): ?string
            {
                return $delegacionId === 6 ? 'Pátzcuaro' : null;
            }
        };
    }
}
