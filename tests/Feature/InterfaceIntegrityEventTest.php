<?php

namespace Tests\Feature;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InterfaceIntegrityEventTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost']);
    }

    public function test_authorized_user_can_report_and_record_restored_siniestros_menu(): void
    {
        $user = User::factory()->create(['unidad_id' => 2]);
        $user->givePermissionTo(Permission::findByName('ver hechos', 'web'));

        $response = $this->actingAs($user)->postJson('http://localhost/security/interface-integrity', [
            'element' => 'menu_siniestros',
            'detected_state' => 'element_absent',
            'restored' => true,
            'page_path' => 'https://example.test/hechos?dato=no-guardar',
        ]);

        $response->assertCreated()->assertJson(['recorded' => true]);

        $event = SecurityEvent::query()
            ->where('event_code', 'dom_element_removed_or_modified')
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->assertSame('interface_integrity', $event->category);
        $this->assertSame('menu_siniestros', $event->metadata['element']);
        $this->assertSame('element_absent', $event->metadata['detected_value']);
        $this->assertSame('/hechos', $event->metadata['page_path']);
        $this->assertTrue($event->metadata['restored']);
    }

    public function test_unit_five_cannot_submit_siniestros_integrity_reports(): void
    {
        $user = User::factory()->create(['unidad_id' => 5]);
        $user->givePermissionTo(Permission::findByName('ver hechos', 'web'));

        $this->actingAs($user)->postJson('http://localhost/security/interface-integrity', [
            'element' => 'menu_siniestros',
            'detected_state' => 'element_absent',
            'restored' => true,
        ])->assertForbidden();
    }
}
