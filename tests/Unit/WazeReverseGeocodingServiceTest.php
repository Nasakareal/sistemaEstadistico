<?php

namespace Tests\Unit;

use App\Services\Waze\WazeReverseGeocodingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WazeReverseGeocodingServiceTest extends TestCase
{
    public function test_lee_names_de_la_respuesta_actual_de_waze(): void
    {
        config()->set('waze.reverse_geocoding_enabled', true);
        config()->set('waze.reverse_geocoding_token', 'test-token');
        config()->set('waze.reverse_geocoding_max_distance_meters', 50);
        Cache::flush();
        Http::fake([
            '*' => Http::response([
                'result' => [[
                    'names' => ['MEX-15 / Morelia - Jiquilpan'],
                    'distance' => 12.59,
                ]],
            ]),
        ]);

        $result = (new WazeReverseGeocodingService())->nearestStreet(19.6900833, -101.2843617);

        $this->assertSame('MEX-15 / Morelia - Jiquilpan', $result['street']);
        $this->assertSame(12.59, $result['distance']);
    }
}
