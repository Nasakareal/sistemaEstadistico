<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ChoquesDiariosController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ChoquesDiariosControllerCoordinatesTest extends TestCase
{
    public function test_usa_coordenadas_legacy_en_formato_lat_lng(): void
    {
        $coordenadas = $this->coordenadasGeograficas((object) [
            'lat' => null,
            'lng' => null,
            'legacy_lat_punto' => null,
            'legacy_lng_punto' => null,
            'legacy_coordenadas' => '19.71467547180954,-101.22285839170216',
        ]);

        $this->assertSame(19.71467547180954, $coordenadas['lat']);
        $this->assertSame(-101.22285839170216, $coordenadas['lng']);
    }

    public function test_prefiere_lat_lng_del_hecho(): void
    {
        $coordenadas = $this->coordenadasGeograficas((object) [
            'lat' => '19.7000000',
            'lng' => '-101.2000000',
            'legacy_lat_punto' => '19.8000000',
            'legacy_lng_punto' => '-101.3000000',
            'legacy_coordenadas' => '19.9000000,-101.4000000',
        ]);

        $this->assertSame(19.7, $coordenadas['lat']);
        $this->assertSame(-101.2, $coordenadas['lng']);
    }

    public function test_corrige_par_invertido_si_llega_como_lng_lat(): void
    {
        $coordenadas = $this->coordenadasGeograficas((object) [
            'lat' => null,
            'lng' => null,
            'legacy_lat_punto' => null,
            'legacy_lng_punto' => null,
            'legacy_coordenadas' => '-101.22285839170216,19.71467547180954',
        ]);

        $this->assertSame(19.71467547180954, $coordenadas['lat']);
        $this->assertSame(-101.22285839170216, $coordenadas['lng']);
    }

    public function test_regresa_nulls_cuando_no_hay_coordenadas_validas(): void
    {
        $this->assertSame(
            ['lat' => null, 'lng' => null],
            $this->coordenadasGeograficas((object) [
                'lat' => '0',
                'lng' => '0',
                'legacy_lat_punto' => null,
                'legacy_lng_punto' => null,
                'legacy_coordenadas' => 'sin dato',
            ])
        );
    }

    private function coordenadasGeograficas(object $hecho): array
    {
        $controller = new ChoquesDiariosController();
        $method = new ReflectionMethod($controller, 'coordenadasGeograficas');
        $method->setAccessible(true);

        return $method->invoke($controller, $hecho);
    }
}
