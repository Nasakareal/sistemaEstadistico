<?php

namespace Tests\Unit;

use App\Models\Actividad;
use App\Models\PuestaDisposicion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\TestCase;

class PuestaDisposicionActividadRelationTest extends TestCase
{
    public function test_puesta_accepts_actividad_id_and_defines_activity_relation(): void
    {
        $puesta = new PuestaDisposicion();

        $this->assertContains('actividad_id', $puesta->getFillable());
        $this->assertSame('integer', $puesta->getCasts()['actividad_id']);
        $this->assertInstanceOf(BelongsTo::class, $puesta->actividad());
        $this->assertSame('actividad_id', $puesta->actividad()->getForeignKeyName());
    }

    public function test_activity_defines_single_disposition_relation(): void
    {
        $actividad = new Actividad();
        $relation = $actividad->puestaDisposicion();

        $this->assertInstanceOf(HasOne::class, $relation);
        $this->assertSame('actividad_id', $relation->getForeignKeyName());
    }
}
