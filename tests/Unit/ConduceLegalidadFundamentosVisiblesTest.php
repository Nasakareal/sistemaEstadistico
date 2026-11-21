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

    public function test_no_confunde_pasajero_menor_del_articulo_420_con_menor_conduciendo(): void
    {
        $infraccion = new LicenciaPuntoInfraccion([
            'codigo' => 'ART420_FIII_I_MOTO_PASAJERO_MENOR',
            'nombre' => 'Motocicleta con pasajero entre conductor y manubrio o menor de doce años',
            'ambito_vehiculo' => 'motocicleta',
            'retencion_vehiculo' => true,
            'deposito_si_sin_persona_habilitada' => false,
        ]);

        $this->assertFalse($this->aplica($infraccion));
    }

    public function test_usa_el_compuesto_operativo_y_excluye_el_421_simple(): void
    {
        $simple = new LicenciaPuntoInfraccion([
            'codigo' => 'ART421_FI_I_PARTICULAR_MENOR_SIN_PERMISO',
            'nombre' => 'Menor de edad sin permiso vigente o conduciendo motocicleta',
            'ambito_vehiculo' => 'automovil',
            'retencion_vehiculo' => false,
        ]);
        $compuesto = new LicenciaPuntoInfraccion([
            'codigo' => 'OP_CL_MENOR_CONDUCIENDO_SIN_HABILITADO',
            'nombre' => 'Persona menor de edad conduciendo motocicleta y sin persona habilitada inmediata',
            'ambito_vehiculo' => 'motocicleta',
            'retencion_vehiculo' => true,
        ]);

        $this->assertTrue($this->aplica($simple));
        $this->assertTrue($this->aplica($compuesto));
        $this->assertTrue($this->esMenorSimple($simple));
        $this->assertFalse($this->esMenorSimple($compuesto));
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

    public function test_excluye_el_fundamento_de_puente_peatonal(): void
    {
        $infraccion = new LicenciaPuntoInfraccion([
            'codigo' => 'ART440_FIV_MOTO_PUENTE_PEATONAL',
            'nombre' => 'Motocicleta circula sobre puentes peatonales',
            'ambito_vehiculo' => 'motocicleta',
            'retencion_vehiculo' => false,
            'deposito_si_sin_persona_habilitada' => false,
        ]);

        $this->assertFalse($this->aplica($infraccion));
    }

    public function test_excluye_los_fundamentos_retirados_del_catalogo_operativo(): void
    {
        $codigos = [
            'ART402_LICENCIA_FORANEA_REINCIDENCIA',
            'ART432_FVII_REBASAR_IZQUIERDA_SEPARACION',
            'ART432_FVIII_ALINEARSE_DERECHA',
            'ART432_FIX_DISTANCIA_RAZONABLE',
            'ART432_FX_DIRECCIONALES',
            'ART432_FXI_PERMITIR_INCORPORACION',
            'ART432_FXII_CEDER_SERVICIO_SOCIAL',
            'ART432_FXV_VIAS_FERREAS',
            'ART432_FXVI_ASCENSO_CARRIL_ACERA',
            'ART432_FXVII_PORTAR_REGLAMENTO',
            'ART432_FXIX_CARAVANA_ESPACIO',
            'ART437_SERVICIO_SOCIAL_USO_INDEBIDO_SENALES',
            'ART439_FII_MOTO_REBASE_IZQUIERDA',
            'ART459_FII_I_MOTORIZADO_CINTURONES_SEGURIDAD',
            'ART459_FII_I_MOTORIZADO_LIMPIAPARABRISAS',
            'ART459_FII_I_MOTORIZADO_HERRAMIENTA_LLANTAS',
        ];

        foreach ($codigos as $codigo) {
            $infraccion = new LicenciaPuntoInfraccion([
                'codigo' => $codigo,
                'nombre' => $codigo,
                'ambito_vehiculo' => 'motocicleta',
                'retencion_vehiculo' => true,
                'deposito_si_sin_persona_habilitada' => false,
            ]);

            $this->assertFalse($this->aplica($infraccion), $codigo);
        }
    }

    public function test_conserva_no_portar_licencia_correspondiente(): void
    {
        $infraccion = new LicenciaPuntoInfraccion([
            'codigo' => 'ART432_FI_PORTAR_LICENCIA',
            'nombre' => 'No portar licencia correspondiente',
            'ambito_vehiculo' => 'general',
            'retencion_vehiculo' => true,
            'deposito_si_sin_persona_habilitada' => false,
        ]);

        $this->assertTrue($this->aplica($infraccion));
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

    private function esMenorSimple(LicenciaPuntoInfraccion $infraccion): bool
    {
        $method = new ReflectionMethod(
            ConduceLegalidadController::class,
            'esFundamentoMenorSimple'
        );
        $method->setAccessible(true);

        return (bool) $method->invoke(new ConduceLegalidadController(), $infraccion);
    }
}
