<?php

namespace Tests\Unit;

use App\Models\Hechos;
use App\Models\User;
use App\Support\HechoAccess;
use Tests\TestCase;

class HechoFotoSituacionUnidadTest extends TestCase
{
    public function test_foto_situacion_es_obligatoria_unicamente_para_unidad_uno(): void
    {
        $this->assertTrue(HechoAccess::requiresSituacionPhoto(new User(['unidad_id' => 1])));
        $this->assertFalse(HechoAccess::requiresSituacionPhoto(new User(['unidad_id' => 2])));
        $this->assertFalse(HechoAccess::requiresSituacionPhoto(new User(['unidad_id' => 3])));
    }

    public function test_en_edicion_la_regla_se_toma_de_la_unidad_del_hecho(): void
    {
        $actorUnidadUno = new User(['unidad_id' => 1]);

        $this->assertFalse(HechoAccess::requiresSituacionPhoto(
            $actorUnidadUno,
            new Hechos(['unidad_org_id' => 2])
        ));

        $this->assertTrue(HechoAccess::requiresSituacionPhoto(
            new User(['unidad_id' => 2]),
            new Hechos(['unidad_org_id' => 1])
        ));
    }

    public function test_formularios_respetan_la_bandera_de_foto_situacion(): void
    {
        foreach (['create', 'edit'] as $formulario) {
            $source = file_get_contents(resource_path("views/hechos/{$formulario}.blade.php"));

            $this->assertStringContainsString('const requiereFotoSituacion = @json', $source);
            $this->assertStringContainsString('fotoSituacionInput.required = requiereFotoSituacion', $source);
            $this->assertStringContainsString('Opcional para esta unidad.', $source);
        }
    }
}
