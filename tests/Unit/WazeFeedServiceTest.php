<?php

namespace Tests\Unit;

use App\Services\Waze\WazeFeedService;
use PHPUnit\Framework\TestCase;

class WazeFeedServiceTest extends TestCase
{
    public function test_genera_polyline_de_punto_con_dos_coordenadas_distintas(): void
    {
        $polyline = (new TestableWazeFeedService())->buildPointPolylinePublic(19.7028915, -101.2006836);

        $this->assertSame(
            '19.7028915 -101.2006836 19.7030415 -101.2005336',
            $polyline
        );

        $numbers = $this->numbers($polyline);

        $this->assertCount(4, $numbers);
        $this->assertNotSame($numbers[0] . ',' . $numbers[1], $numbers[2] . ',' . $numbers[3]);
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

    public function test_accidente_con_polyline_real_duplicada_usa_linea_artificial_valida(): void
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

        $this->assertSame(
            '19.7028915 -101.2006836 19.7030415 -101.2005336',
            $polyline
        );
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

    private function numbers(string $polyline): array
    {
        preg_match_all('/-?\d+(?:\.\d+)?/', $polyline, $matches);

        return $matches[0] ?? [];
    }
}

class TestableWazeFeedService extends WazeFeedService
{
    public function buildPointPolylinePublic(float $lat, float $lng): string
    {
        return $this->buildPointPolyline($lat, $lng);
    }

    public function buildPolylinePublic(float $lat, float $lng, $hecho, string $type): ?string
    {
        return $this->buildPolyline($lat, $lng, $hecho, $type);
    }
}
