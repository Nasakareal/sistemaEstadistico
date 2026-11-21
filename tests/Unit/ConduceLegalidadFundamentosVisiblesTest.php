<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ConduceLegalidadController;
use App\Models\LicenciaPuntoInfraccion;
use ReflectionMethod;
use Tests\TestCase;

class ConduceLegalidadFundamentosVisiblesTest extends TestCase
{
    public function test_admite_menor_conduciendo_aunque_el_catalogo_heredado_diga_automovil(): void
    {
        $infraccion = new LicenciaPuntoInfraccion([
            'codigo' => 'ART421_FI_IA_PARTICULAR_MENOR_SIN_PERMISO',
            'nombre' => 'Menor de edad sin permiso vigente o conduciendo motocicleta',
            'ambito_vehiculo' => 'automovil',
            'retencion_vehiculo' => false,
            'deposito_si_sin_persona_habilitada' => false,
        ]);

        $this->assertTrue($this->aplica($infraccion));
    }

    public function test_no_agrega_una_infraccion_ajena_que_no_es_visible_ni_implica_retiro(): void
    {
        $infraccion = new LicenciaPuntoInfraccion([
            'codigo' => 'ART999_OTRA_CONDUCTA',
            'nombre' => 'Otra conducta administrativa',
            'ambito_vehiculo' => 'general',
            'retencion_vehiculo' => false,
            'deposito_si_sin_persona_habilitada' => false,
        ]);

        $this->assertFalse($this->aplica($infraccion));
    }

    public function test_no_acepta_una_causa_visible_de_vehiculo_de_carga(): void
    {
        $infraccion = new LicenciaPuntoInfraccion([
            'codigo' => 'ART420_CARGA_PASAJEROS_AREA',
            'nombre' => 'Vehiculo de carga con pasajeros en area de carga',
            'ambito_vehiculo' => 'carga',
            'retencion_vehiculo' => true,
            'deposito_si_sin_persona_habilitada' => false,
        ]);

        $this->assertFalse($this->aplica($infraccion));
    }

    public function test_incluye_todo_fundamento_de_motocicleta_y_ruido_general(): void
    {
        $fundamentoMoto = new LicenciaPuntoInfraccion([
            'codigo' => 'ART439_FI_MOTO_CENTRO_CARRIL_DERECHA',
            'nombre' => 'Regla propia de motocicleta',
            'ambito_vehiculo' => 'motocicleta',
            'retencion_vehiculo' => false,
            'deposito_si_sin_persona_habilitada' => false,
        ]);
        $ruido = new LicenciaPuntoInfraccion([
            'codigo' => 'ART465_FVII_ESCAPE_RUIDO',
            'nombre' => 'Modificar sistema de escape para provocar ruido excesivo',
            'ambito_vehiculo' => 'general',
            'retencion_vehiculo' => false,
            'deposito_si_sin_persona_habilitada' => false,
        ]);

        $this->assertTrue($this->aplica($fundamentoMoto));
        $this->assertTrue($this->aplica($ruido));
    }

    public function test_no_incluye_causa_visible_de_automovil(): void
    {
        $infraccion = new LicenciaPuntoInfraccion([
            'codigo' => 'ART436_CONDUCTOR_ACERAS',
            'nombre' => 'Conductor de automovil circula sobre aceras',
            'ambito_vehiculo' => 'automovil',
            'retencion_vehiculo' => true,
            'deposito_si_sin_persona_habilitada' => false,
        ]);

        $this->assertFalse($this->aplica($infraccion));
    }

    private function aplica(LicenciaPuntoInfraccion $infraccion): bool
    {
        $method = new ReflectionMethod(
            ConduceLegalidadController::class,
            'infraccionAplicaConduceLegalidad'
        );
        $method->setAccessible(true);

        return (bool) $method->invoke(new ConduceLegalidadController(), $infraccion);
    }
}
