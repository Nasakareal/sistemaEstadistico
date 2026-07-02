<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ConduceLegalidadController;
use App\Models\ConduceLegalidadCaptura;
use App\Models\ConduceLegalidadOperativo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class ConduceLegalidadIphMappingTest extends TestCase
{
    public function test_mapeo_iph_usa_numero_y_codigo_postal_del_operativo(): void
    {
        $operativo = new ConduceLegalidadOperativo();
        $operativo->forceFill([
            'id' => 10,
            'fecha' => Carbon::parse('2026-07-02'),
            'hora_inicio' => '10:15:00',
            'municipio' => 'Morelia',
            'lugar' => 'Avenida Test',
            'numero' => '123',
            'colonia' => 'Centro',
            'codigo_postal' => '58000',
            'lat' => 19.7008,
            'lng' => -101.1844,
        ]);

        $captura = new ConduceLegalidadCaptura();
        $captura->forceFill([
            'id' => 25,
            'operativo_id' => 10,
            'fecha' => Carbon::parse('2026-07-02'),
            'hora' => '10:30:00',
            'municipio' => null,
            'lugar' => null,
            'lat' => null,
            'lng' => null,
            'observaciones' => null,
        ]);
        $captura->setRelation('vehiculos', new Collection());
        $captura->setRelation('personas', new Collection());
        $captura->setRelation('fotos', new Collection());
        $captura->setRelation('unidad', null);
        $captura->setRelation('delegacion', null);
        $captura->setRelation('creador', null);

        $method = new ReflectionMethod(ConduceLegalidadController::class, 'mapearIphDesdeCaptura');
        $method->setAccessible(true);

        $mapeo = $method->invoke(new ConduceLegalidadController(), $operativo, $captura, (object) [
            'name' => 'Agente Test',
        ]);

        $this->assertSame('Avenida Test 123', $mapeo['hecho']['ubicacion']['calle']);
        $this->assertSame('58000', $mapeo['hecho']['ubicacion']['codigo_postal']);
        $this->assertStringContainsString('CP 58000', $mapeo['hecho']['ubicacion']['ubicacion_formateada']);
    }
}
