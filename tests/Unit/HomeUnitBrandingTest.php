<?php

namespace Tests\Unit;

use App\Http\Controllers\HomeController;
use ReflectionMethod;
use Tests\TestCase;

class HomeUnitBrandingTest extends TestCase
{
    public function test_usa_las_mismas_imagenes_por_unidad_que_flutter(): void
    {
        $controller = new HomeController();
        $resolver = new ReflectionMethod($controller, 'unidadBrandingAsset');
        $resolver->setAccessible(true);

        $this->assertSame('img/unidades/siniestros.png', $resolver->invoke($controller, (object) ['unidad_id' => 1]));
        $this->assertSame('img/unidades/delegaciones.png', $resolver->invoke($controller, (object) ['unidad_id' => 2]));
        $this->assertSame('img/unidades/vialidades.png', $resolver->invoke($controller, (object) ['unidad_id' => 5]));
        $this->assertSame('img/unidades/fomento.png', $resolver->invoke($controller, (object) ['unidad_id' => 6]));
        $this->assertNull($resolver->invoke($controller, (object) ['unidad_id' => 3]));
    }
}
