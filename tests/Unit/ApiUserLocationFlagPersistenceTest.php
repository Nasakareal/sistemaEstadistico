<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ApiUserLocationFlagPersistenceTest extends TestCase
{
    public function test_user_update_preserves_location_flag_when_old_client_omits_it(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/app/Http/Controllers/Api/UserController.php'
        );

        $this->assertStringContainsString(
            "array_key_exists('compartir_ubicacion', \$validated)",
            $source
        );
        $this->assertStringNotContainsString(
            "'compartir_ubicacion' => (bool) (\$validated['compartir_ubicacion'] ?? false)",
            $source
        );
    }
}
