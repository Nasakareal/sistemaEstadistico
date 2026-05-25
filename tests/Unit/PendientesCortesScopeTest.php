<?php

namespace Tests\Unit;

use App\Models\Hechos;
use App\Services\PendientesCortesService;
use Tests\TestCase;

class PendientesCortesScopeTest extends TestCase
{
    public function test_scope_de_cortes_pendientes_excluye_peritos_legacy(): void
    {
        $query = Hechos::query();

        (new PendientesCortesService())->applyHechosUnidadesScope($query, [
            PendientesCortesService::UNIDAD_SINIESTROS_ID,
        ]);

        $this->assertStringContainsString("LOWER(TRIM(COALESCE(fuente_ubicacion, ''))) <> ?", $query->toSql());
        $this->assertContains(PendientesCortesService::FUENTE_LEGACY_PERITOS, $query->getBindings());
    }
}
