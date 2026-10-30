<?php

namespace Tests\Unit;

use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\TestCase;

class ActividadVehiculoLiberacionWebTest extends TestCase
{
    public function test_vehiculo_expone_su_liberacion_y_las_rutas_web_de_conduce(): void
    {
        $relation = (new Vehiculo())->liberacion();

        $this->assertInstanceOf(HasOne::class, $relation);
        $this->assertSame('vehiculo_id', $relation->getForeignKeyName());
        $this->assertNotNull(app('router')->getRoutes()->getByName('conduce_legalidad.index'));
        $this->assertNotNull(app('router')->getRoutes()->getByName('conduce_legalidad.show'));
    }

    public function test_actividad_ofrece_liberacion_y_acuse_tolera_vehiculo_sin_hecho(): void
    {
        $actividad = file_get_contents(resource_path('views/actividades/show.blade.php'));
        $acuse = file_get_contents(resource_path('views/liberaciones/acuse_pdf.blade.php'));

        $this->assertStringContainsString("route('liberacion.create'", $actividad);
        $this->assertStringContainsString('$fechaResguardo', $acuse);
        $this->assertStringContainsString('$hecho?->oficio_mp', $acuse);
    }
}
