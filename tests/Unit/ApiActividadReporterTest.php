<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ActividadController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ApiActividadReporterTest extends TestCase
{
    public function test_administrativo_sin_nombre_en_payload_usa_el_usuario_autenticado(): void
    {
        $this->assertSame(
            'JORGE ANTONIO AGUILAR AGUILAR',
            $this->resolver('Jorge Antonio Aguilar Aguilar', [], true)
        );
    }

    public function test_administrativo_puede_indicar_otro_reportante(): void
    {
        $this->assertSame(
            'ELEMENTO DE APOYO',
            $this->resolver('Jorge Antonio Aguilar Aguilar', ['nombre' => 'Elemento de apoyo'], true)
        );
    }

    public function test_usuario_no_administrativo_no_puede_suplantar_el_reportante(): void
    {
        $this->assertSame(
            'JORGE ANTONIO AGUILAR AGUILAR',
            $this->resolver('Jorge Antonio Aguilar Aguilar', ['nombre' => 'Otro nombre'], false)
        );
    }

    public function test_actualizacion_administrativa_sin_nombre_conserva_el_reportante_actual(): void
    {
        $this->assertSame(
            'REPORTANTE ORIGINAL',
            $this->resolver('Jorge Antonio Aguilar Aguilar', [], true, 'Reportante original')
        );
    }

    private function resolver(
        string $nombreUsuario,
        array $validated,
        bool $puedeEscribirNombre,
        ?string $nombreActual = null
    ): string {
        $method = new ReflectionMethod(ActividadController::class, 'resolveActivityReporter');
        $method->setAccessible(true);

        $usuario = new class($nombreUsuario) {
            public string $name;

            public function __construct(string $name)
            {
                $this->name = $name;
            }
        };

        return $method->invoke(
            new ActividadController(),
            $usuario,
            $validated,
            $puedeEscribirNombre,
            $nombreActual
        );
    }
}
