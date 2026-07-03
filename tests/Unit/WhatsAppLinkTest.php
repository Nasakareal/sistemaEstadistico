<?php

namespace Tests\Unit;

use App\Models\Conductor;
use App\Models\Hechos;
use App\Models\PuestaDisposicion;
use App\Models\PuestaDisposicionPersona;
use App\Models\Vehiculo;
use App\Services\WhatsApp\WhatsAppLink;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class WhatsAppLinkTest extends TestCase
{
    public function test_hecho_card_includes_id_and_detained_people_from_linked_puesta(): void
    {
        $hecho = new Hechos();
        $hecho->id = 777;
        $hecho->unidad_org_id = 1;
        $hecho->tipo_hecho = 'COLISION POR ALCANCE';
        $hecho->fecha = '2026-05-25';
        $hecho->hora = '12:41:00';
        $hecho->calle = 'AVENIDA PRUEBA';
        $hecho->colonia = 'CENTRO';
        $hecho->situacion = 'TURNADO';
        $hecho->unidad = '224926';

        $persona = new PuestaDisposicionPersona();
        $persona->nombre_completo = 'JUAN PEREZ LOPEZ';
        $persona->alias = 'EL JP';
        $persona->edad = 34;
        $persona->sexo = 'MASCULINO';
        $persona->calidad = 'DETENIDO';
        $persona->delito_o_motivo = 'HECHO DE TRANSITO TURNADO';
        $persona->orden_aprehension = true;
        $persona->mandamiento_judicial = 'OFICIO 99';
        $persona->observaciones = 'SIN NOVEDAD';

        $puesta = new PuestaDisposicion();
        $puesta->numero_puesta = 12;
        $puesta->anio = 2026;
        $puesta->setRelation('personas', new Collection([$persona]));

        $hecho->setRelation('vehiculos', new Collection());
        $hecho->setRelation('lesionados', new Collection());
        $hecho->setRelation('puestaDisposicion', $puesta);

        $texto = WhatsAppLink::textForHecho($hecho);

        $this->assertStringContainsString('ID DEL HECHO: 777', $texto);
        $this->assertStringContainsString('De este hecho de tránsito resultan personas detenidas:', $texto);
        $this->assertStringContainsString('Puesta a disposición: 12/2026.', $texto);
        $this->assertStringContainsString(
            '- JUAN PEREZ LOPEZ, de 34 años, sexo MASCULINO. Alias: EL JP. Calidad: DETENIDO. Motivo: HECHO DE TRANSITO TURNADO. Cuenta con orden de aprehensión. Mandamiento judicial: OFICIO 99. Observaciones: SIN NOVEDAD.',
            $texto
        );
    }

    public function test_hecho_card_includes_driver_phone_when_available(): void
    {
        $hecho = new Hechos();
        $hecho->id = 778;
        $hecho->unidad_org_id = 1;
        $hecho->tipo_hecho = 'COLISION POR ALCANCE';
        $hecho->fecha = '2026-05-25';
        $hecho->hora = '12:41:00';
        $hecho->calle = 'AVENIDA PRUEBA';
        $hecho->colonia = 'CENTRO';
        $hecho->situacion = 'TURNADO';

        $conductor = new Conductor();
        $conductor->nombre = 'MARIA PEREZ';
        $conductor->edad = 29;
        $conductor->telefono = '4431234567';

        $vehiculo = new Vehiculo();
        $vehiculo->marca = 'NISSAN';
        $vehiculo->tipo = 'SEDAN';
        $vehiculo->linea = 'VERSA';
        $vehiculo->color = 'BLANCO';
        $vehiculo->placas = 'ABC123A';
        $vehiculo->serie = '3N1CN7AD0KL000000';
        $vehiculo->setRelation('conductores', new Collection([$conductor]));

        $hecho->setRelation('vehiculos', new Collection([$vehiculo]));
        $hecho->setRelation('lesionados', new Collection());
        $hecho->setRelation('puestaDisposicion', null);

        $texto = WhatsAppLink::textForHecho($hecho);

        $this->assertStringContainsString(
            'Manifiesta viajar a bordo el C. MARIA PEREZ de 29 años. Teléfono: 4431234567.',
            $texto
        );
    }
}
