<?php

namespace Tests\Unit;

use App\Models\Actividad;
use PHPUnit\Framework\TestCase;

class ActividadFolioC5iTest extends TestCase
{
    public function test_el_folio_c5i_es_asignable_en_una_actividad(): void
    {
        $actividad = new Actividad(['folio_c5i' => 'C5I-2026-001']);

        $this->assertSame('C5I-2026-001', $actividad->folio_c5i);
    }
}
