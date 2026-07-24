<?php

namespace Tests\Unit;

use App\Models\BoquillaPerdida;
use Tests\TestCase;

class BoquillaPerdidaTest extends TestCase
{
    public function test_modelo_conserva_fecha_cantidad_y_borrado_logico(): void
    {
        $perdida = new BoquillaPerdida([
            'fecha_perdida' => '2026-07-15',
            'cantidad' => '4',
            'observaciones' => 'Extravío durante operativo',
        ]);

        $this->assertSame('boquilla_perdidas', $perdida->getTable());
        $this->assertSame(4, $perdida->cantidad);
        $this->assertSame('2026-07-15', $perdida->fecha_perdida->toDateString());
        $this->assertTrue(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($perdida), true));
    }
}
