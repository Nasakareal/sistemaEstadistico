<?php

namespace Tests\Unit;

use App\Models\WazeAlert;
use App\Services\Waze\WazeAlertDeduplicationService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WazeAlertDeduplicationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'waze_dedup_testing');
        config()->set('database.connections.waze_dedup_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        config()->set('services.waze.dedup_radius_km', 2);
        config()->set('services.waze.dedup_window_minutes', 120);
        DB::purge('waze_dedup_testing');
        DB::setDefaultConnection('waze_dedup_testing');

        Schema::create('waze_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('type')->nullable();
            $table->string('subtype')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('notified')->default(false);
            $table->timestamps();
        });
    }

    public function test_groups_same_kind_within_two_kilometers_and_recent_window(): void
    {
        $publishedAt = Carbon::parse('2026-09-14 10:00:00', 'America/Mexico_City');
        WazeAlert::query()->create([
            'uuid' => 'alerta-original',
            'type' => 'ACCIDENT',
            'lat' => 19.7029,
            'lng' => -101.1920,
            'published_at' => $publishedAt,
        ]);

        $service = new WazeAlertDeduplicationService();

        $this->assertTrue($service->hasEquivalent(
            'ACCIDENT',
            null,
            19.7110,
            -101.1920,
            $publishedAt->copy()->addMinutes(20)
        ));
        $this->assertFalse($service->hasEquivalent(
            'ROAD_CLOSED',
            null,
            19.7110,
            -101.1920,
            $publishedAt->copy()->addMinutes(20)
        ));
        $this->assertFalse($service->hasEquivalent(
            'ACCIDENT',
            null,
            19.7300,
            -101.1920,
            $publishedAt->copy()->addMinutes(20)
        ));
        $this->assertFalse($service->hasEquivalent(
            'ACCIDENT',
            null,
            19.7110,
            -101.1920,
            $publishedAt->copy()->addMinutes(121)
        ));
    }
}
