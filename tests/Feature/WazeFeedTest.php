<?php

namespace Tests\Feature;

use App\Services\Waze\WazeFeedService;
use App\Services\Waze\WazeReverseGeocodingService;
use Mockery;
use Tests\TestCase;

class WazeFeedTest extends TestCase
{
    public function test_endpoint_conserva_contrato_legado_y_geometria_cifs_util(): void
    {
        config()->set('waze.feed_token', 'test-feed-token');
        config()->set('waze.require_reverse_geocoding_match', false);
        config()->set('waze.publish_accidents_as_closures', false);
        config()->set('waze.road_snap_enabled', false);
        config()->set('waze.excluded_hecho_ids', []);

        $geocoder = Mockery::mock(WazeReverseGeocodingService::class);
        $geocoder->shouldReceive('nearestStreet')->andReturn(null);
        $service = Mockery::mock(WazeFeedService::class, [$geocoder])
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('buildPolylineFromNearbyTramo')->andReturn(null);
        $service->shouldReceive('buildPointPolyline')
            ->andReturn('19.7027000 -101.2009000 19.7031000 -101.2005000');
        $hechos = collect(range(1, 55))->map(function ($id) {
            return (object) [
                'id' => $id,
                'tipo_hecho' => 'CHOQUE',
                'situacion' => 'PENDIENTE',
                'fecha' => '2026-09-15',
                'hora' => '14:46:00',
                'lat' => '19.7028915',
                'lng' => '-101.2006836',
                'calle' => 'CALLE DE PRUEBA',
            ];
        });
        $service->shouldReceive('queryHechos')->once()->andReturn($hechos);
        $this->app->instance(WazeFeedService::class, $service);

        $response = $this->getJson('http://localhost/api/waze/incidents?token=test-feed-token');
        $response->assertOk()->assertJsonCount(55, 'incidents');
        foreach ($response->json('incidents') as $incident) {
            $this->assertSame('ACCIDENT', $incident['type']);
            $this->assertSame(['x' => -101.2006836, 'y' => 19.7028915], $incident['location']);
            $this->assertSame('MX', $incident['country']);
            $this->assertSame('MORELIA', $incident['city']);
            $this->assertSame(0.9, $incident['confidence']);
            $this->assertSame(6, $incident['reliability']);
            preg_match_all('/-?\d+(?:\.\d+)?/', $incident['polyline'], $matches);
            $this->assertCount(4, $matches[0]);
            $this->assertSame(
                '19.7027000 -101.2009000 19.7031000 -101.2005000',
                $incident['polyline']
            );
            $this->assertSame('BOTH_DIRECTIONS', $incident['direction']);
            $this->assertSame('2026-09-15T14:46:00-06:00', $incident['starttime']);
            $this->assertNotEmpty($incident['street']);
            $this->assertNotEmpty($incident['description']);
            $this->assertGreaterThan(strtotime($incident['starttime']), strtotime($incident['endtime']));
        }
    }

    public function test_endpoint_descarta_incidente_con_geometria_equivalente_a_un_punto(): void
    {
        config()->set('waze.feed_token', 'test-feed-token');
        config()->set('waze.require_reverse_geocoding_match', false);
        config()->set('waze.road_snap_enabled', false);
        config()->set('waze.excluded_hecho_ids', []);

        $geocoder = Mockery::mock(WazeReverseGeocodingService::class);
        $geocoder->shouldReceive('nearestStreet')->andReturn(null);
        $service = Mockery::mock(WazeFeedService::class, [$geocoder])
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('buildPolylineFromNearbyTramo')->andReturn(null);
        $service->shouldReceive('buildPointPolyline')
            ->andReturn('19.7028915 -101.2006836 19.7028916 -101.2006836');
        $service->shouldReceive('queryHechos')->once()->andReturn(collect([
            (object) [
                'id' => 63602,
                'tipo_hecho' => 'CHOQUE',
                'situacion' => 'PENDIENTE',
                'fecha' => '2026-09-18',
                'hora' => '16:53:00',
                'lat' => '19.7407818',
                'lng' => '-101.2056256',
                'calle' => 'AV. GERTRUDIS SANCHEZ',
            ],
        ]));
        $this->app->instance(WazeFeedService::class, $service);

        $this->getJson('http://localhost/api/waze/incidents?token=test-feed-token')
            ->assertOk()
            ->assertJsonCount(0, 'incidents');
    }

    public function test_endpoint_omite_hechos_rechazados_por_waze(): void
    {
        config()->set('waze.feed_token', 'test-feed-token');
        config()->set('waze.require_reverse_geocoding_match', false);
        config()->set('waze.road_snap_enabled', false);
        config()->set('waze.excluded_hecho_ids', [63602]);

        $geocoder = Mockery::mock(WazeReverseGeocodingService::class);
        $geocoder->shouldNotReceive('nearestStreet');
        $service = Mockery::mock(WazeFeedService::class, [$geocoder])
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('queryHechos')->once()->andReturn(collect([
            (object) [
                'id' => 63602,
                'tipo_hecho' => 'CHOQUE',
                'situacion' => 'PENDIENTE',
                'fecha' => '2026-09-18',
                'hora' => '16:53:00',
                'lat' => '19.7407818',
                'lng' => '-101.2056256',
                'calle' => 'AV. GERTRUDIS SANCHEZ',
            ],
        ]));
        $this->app->instance(WazeFeedService::class, $service);

        $this->getJson('http://localhost/api/waze/incidents?token=test-feed-token')
            ->assertOk()
            ->assertJsonCount(0, 'incidents');
    }
}
