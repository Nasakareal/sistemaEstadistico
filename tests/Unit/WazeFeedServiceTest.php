<?php

namespace Tests\Unit;

use App\Services\Waze\WazeFeedService;
use App\Services\Waze\WazeReverseGeocodingService;
use Tests\TestCase;

class WazeFeedServiceTest extends TestCase
{
    public function test_no_inventa_polyline_si_no_hay_geometria_vial(): void
    {
        $polyline = (new TestableWazeFeedService())->buildPointPolylinePublic(19.7028915, -101.2006836);

        $this->assertNull($polyline);
    }

    public function test_accidente_prefiere_polyline_real_cuando_existe(): void
    {
        $hecho = (object) [
            'polyline' => '19.7028915 -101.2006836 19.7031200 -101.2010400',
        ];

        $polyline = (new TestableWazeFeedService())->buildPolylinePublic(
            19.7028915,
            -101.2006836,
            $hecho,
            'ACCIDENT'
        );

        $this->assertSame(
            '19.7028915 -101.2006836 19.7031200 -101.2010400',
            $polyline
        );
    }

    public function test_accidente_con_polyline_real_duplicada_se_descarta_sin_geometria_vial(): void
    {
        $hecho = (object) [
            'polyline' => '19.7028915 -101.2006836 19.7028915 -101.2006836',
        ];

        $polyline = (new TestableWazeFeedService())->buildPolylinePublic(
            19.7028915,
            -101.2006836,
            $hecho,
            'ACCIDENT'
        );

        $this->assertNull($polyline);
    }

    public function test_cierre_con_polyline_duplicada_se_descarta(): void
    {
        $hecho = (object) [
            'polyline' => '19.7028915 -101.2006836 19.7028915 -101.2006836',
        ];

        $polyline = (new TestableWazeFeedService())->buildPolylinePublic(
            19.7028915,
            -101.2006836,
            $hecho,
            'ROAD_CLOSED'
        );

        $this->assertNull($polyline);
    }

    public function test_selecciona_dos_puntos_ajustados_a_la_misma_vialidad(): void
    {
        $destinations = [
            ['location' => [-101.173560, 19.686072], 'name' => 'Avenida Solidaridad', 'distance' => 3.9],
            ['location' => [-101.173518, 19.686478], 'name' => 'Avenida Solidaridad', 'distance' => 11.6],
            ['location' => [-101.173771, 19.685884], 'name' => 'Calle Luis Mora Tovar', 'distance' => 23.9],
            ['location' => [-101.173196, 19.686092], 'name' => 'Boulevard Sansón Flores', 'distance' => 7.1],
            ['location' => [-101.173825, 19.686144], 'name' => 'Avenida Solidaridad', 'distance' => 4.4],
            ['location' => [-101.173311, 19.686421], 'name' => 'Avenida Solidaridad', 'distance' => 12.9],
        ];

        $polyline = (new TestableWazeFeedService())->selectRoadPolylinePublic(
            $destinations,
            19.6861063,
            -101.1735494
        );

        $numbers = $this->numbers($polyline);
        $this->assertCount(4, $numbers);
        $this->assertNotSame($numbers[0].' '.$numbers[1], $numbers[2].' '.$numbers[3]);
    }

    private function numbers(string $polyline): array
    {
        preg_match_all('/-?\d+(?:\.\d+)?/', $polyline, $matches);

        return $matches[0] ?? [];
    }
}

class TestableWazeFeedService extends WazeFeedService
{
    public function __construct()
    {
        parent::__construct(new WazeReverseGeocodingService());
    }

    public function buildPointPolylinePublic(float $lat, float $lng): ?string
    {
        return $this->buildPointPolyline($lat, $lng);
    }

    public function buildPolylinePublic(float $lat, float $lng, $hecho, string $type): ?string
    {
        return $this->buildPolyline($lat, $lng, $hecho, $type);
    }

    public function selectRoadPolylinePublic(array $destinations, float $lat, float $lng): ?string
    {
        return $this->selectRoadPolyline($destinations, $lat, $lng);
    }

    protected function buildPolylineFromNearbyTramo(float $lat, float $lng): ?string
    {
        return null;
    }
}
