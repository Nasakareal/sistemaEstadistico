<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ConduceLegalidadController;
use App\Models\ConduceLegalidadCaptura;
use App\Models\ConduceLegalidadOperativo;
use App\Models\ConduceLegalidadPersona;
use App\Models\ConduceLegalidadVehiculo;
use App\Models\Delegacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class ConduceLegalidadBoletaWhatsappTest extends TestCase
{
    public function test_prepara_y_renderiza_la_boleta_que_se_envia_por_whatsapp(): void
    {
        $operativo = new ConduceLegalidadOperativo([
            'unidad_id' => 1,
            'municipio' => 'Morelia',
            'lugar' => 'Avenida Madero',
            'numero' => '100',
            'tipo_operativo' => 'conduce_legalidad',
        ]);
        $operativo->id = 25;

        $captura = new ConduceLegalidadCaptura([
            'unidad_id' => 1,
            'municipio' => 'Morelia',
            'lugar' => 'Avenida Madero',
            'fecha' => '2026-09-23',
            'hora' => '18:05',
            'narrativa' => 'Conducía sin licencia vigente.',
            'fundamento_legal' => 'Artículo 402.',
            'agente_nombre' => 'Ana María Pérez López',
            'agente_numero_placa' => 'PLACA-7788',
            'agente_adscripcion' => 'Unidad de Protección en Vialidades Urbanas',
        ]);
        $captura->id = 81;

        $persona = new ConduceLegalidadPersona([
            'nombre' => 'Mario Bautista R.',
            'telefono' => '443 123 4567',
            'domicilio' => 'Morelia, Michoacán',
            'tipo_licencia' => 'Motociclista',
            'numero_licencia' => 'LIC-123',
            'estado_licencia' => 'No vigente',
        ]);
        $persona->id = 9;

        $vehiculo = new ConduceLegalidadVehiculo([
            'marca' => 'KTM',
            'linea' => 'Adventure 250',
            'modelo' => '2021',
            'color' => 'Negro',
            'placas' => '99PKF8',
            'estado_placas' => 'Michoacán',
            'serie' => 'SERIE123',
            'numero_inventario' => 'INV-0042',
            'corralon' => 'Corralón Morelia',
            'retencion_vehiculo' => true,
        ]);
        $vehiculo->setRelation('infraccion', null);

        $captura->setRelation('creador', null);
        $captura->setRelation('infraccion', null);
        $captura->setRelation('fundamentos', new Collection());
        $captura->setRelation('vehiculos', new Collection([$vehiculo]));
        $captura->setRelation('personas', new Collection([$persona]));

        $metodo = new ReflectionMethod(
            ConduceLegalidadController::class,
            'boletaWhatsAppData'
        );
        $metodo->setAccessible(true);
        $boleta = $metodo->invoke(
            new ConduceLegalidadController(),
            $operativo,
            $captura,
            $persona,
            null
        );

        $this->assertSame('CL-25-81', $boleta['folio']);
        $this->assertSame('Mario Bautista R.', $boleta['persona_nombre']);
        $this->assertSame('INV-0042', $boleta['numero_inventario']);
        $this->assertSame('Corralón Morelia', $boleta['corralon']);
        $this->assertStringContainsString('Unidad de Atención a Siniestros', $boleta['liberacion']);
        $this->assertStringContainsString('4:00 p. m.', $boleta['liberacion']);
        $this->assertStringContainsString('Corralón Morelia, previa autorización.', $boleta['liberacion']);
        $this->assertStringNotContainsString('3:00 p. m.', $boleta['liberacion']);
        $this->assertStringNotContainsString('18:00', $boleta['liberacion']);
        $this->assertStringContainsString('KTM', $boleta['vehiculo_resumen']);
        $this->assertSame('Ana María Pérez López', $boleta['agente_nombre']);
        $this->assertSame('PLACA-7788', $boleta['agente_placa']);
        $this->assertStringStartsWith(
            'Unidad de Protección en Vialidades Urbanas',
            $boleta['adscripcion']
        );

        $pdf = Pdf::loadView('pdf.conduce_legalidad_boleta', [
            'boleta' => $boleta,
        ])->output();

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function test_boleta_de_delegaciones_usa_el_domicilio_de_la_delegacion(): void
    {
        $delegacion = new Delegacion([
            'nombre' => 'Uruapan',
            'direccion_completa' => 'Av. Latinoamericana 1500, Uruapan, Michoacán',
        ]);
        $delegacion->id = 14;

        $operativo = new ConduceLegalidadOperativo([
            'unidad_id' => 2,
            'delegacion_id' => 14,
        ]);
        $operativo->setRelation('delegacion', $delegacion);

        $metodo = new ReflectionMethod(
            ConduceLegalidadController::class,
            'liberacionTicket'
        );
        $metodo->setAccessible(true);
        $texto = $metodo->invoke(
            new ConduceLegalidadController(),
            $operativo,
            ['unidad_id' => 2, 'delegacion_id' => 14],
            'La entrega física se realizará en el depósito autorizado.'
        );

        $this->assertStringContainsString('Delegación de Uruapan', $texto);
        $this->assertStringContainsString('Av. Latinoamericana 1500', $texto);
        $this->assertStringContainsString('9:00 a. m. a 3:00 p. m.', $texto);
        $this->assertStringNotContainsString('4:00 p. m.', $texto);
        $this->assertStringNotContainsString('Unidad de Atención a Siniestros', $texto);
        $this->assertStringNotContainsString('Periférico Independencia', $texto);
    }
}
