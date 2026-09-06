<?php

namespace Tests\Unit;

use Tests\TestCase;

class ReconstructorTransitoModuleTest extends TestCase
{
    public function test_reconstructor_route_is_available_from_settings(): void
    {
        $route = app('router')->getRoutes()->getByName('settings.reconstructor_transito.index');

        $this->assertNotNull($route);
        $this->assertSame('admin/settings/reconstructor-transito', $route->uri());
        $this->assertContains('can:ver configuraciones', $route->gatherMiddleware());
    }

    public function test_editor_includes_technical_trajectories_and_video_export(): void
    {
        $source = file_get_contents(public_path('js/reconstructor-transito.js'));

        $this->assertStringContainsString('TRAYECTORIA INICIAL', $source);
        $this->assertStringContainsString('POSTERIOR AL IMPACTO', $source);
        $this->assertStringContainsString("code: 'PMC'", $source);
        $this->assertStringContainsString('canvas.captureStream', $source);
        $this->assertStringContainsString('MediaRecorder', $source);
        $this->assertStringContainsString('drawRotationHandle', $source);
        $this->assertStringContainsString('manualRotation', $source);
        $this->assertStringContainsString('drawRoadMarkings', $source);
        $this->assertStringContainsString('road.lanes', $source);
        $this->assertStringContainsString("road.direction === 'two_way'", $source);
        $this->assertStringContainsString("'#facc15'", $source);
        $this->assertStringContainsString("edge.type === 'median'", $source);
        $this->assertStringContainsString('roadCurvePointLocal', $source);
        $this->assertStringContainsString('curve-handle', $source);
        $this->assertStringContainsString('drawCurveRoadMarkings', $source);
        $this->assertStringContainsString('roadSurfacePattern', $source);
        $this->assertStringContainsString("type === 'cobblestone'", $source);
        $this->assertStringContainsString("type === 'natural'", $source);
        $this->assertStringContainsString('toggleFullscreen', $source);
        $this->assertStringContainsString('cameraWorldCenter', $source);
        $this->assertStringContainsString('fitScene', $source);
        $this->assertStringContainsString('data-add-road', file_get_contents(resource_path('views/admin/settings/reconstructor_transito/index.blade.php')));
    }

    public function test_editor_includes_physics_bridges_water_and_rollover_controls(): void
    {
        $script = file_get_contents(public_path('js/reconstructor-transito.js'));
        $view = file_get_contents(resource_path('views/admin/settings/reconstructor_transito/index.blade.php'));

        $this->assertStringContainsString('buildPhysicsCache', $script);
        $this->assertStringContainsString("status = 'en caída'", $script);
        $this->assertStringContainsString("status = 'sumergido'", $script);
        $this->assertStringContainsString("status = 'volcado'", $script);
        $this->assertStringContainsString('actorA.massKg', $script);
        $this->assertStringContainsString('rolloverLimit', $script);
        $this->assertStringContainsString('data-add-road="puente"', $view);
        $this->assertStringContainsString('data-add-zone="water"', $view);
        $this->assertStringContainsString('id="rtPhysicsEnabled"', $view);
        $this->assertStringContainsString('id="rtActorCgHeight"', $view);
    }
}
