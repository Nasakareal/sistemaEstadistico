<?php

namespace Tests\Unit;

use App\Services\CroquisPreviewService;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class CroquisPreviewServiceTest extends TestCase
{
    public function test_convierte_curvas_anteriores_a_puntos_bezier(): void
    {
        $element = $this->normalize([
            'tipo' => 'curva',
            'x' => 300,
            'y' => 200,
            'radioInterno' => 45,
            'anchoCarril' => 28,
            'carriles' => 2,
            'angulo' => 90,
        ]);

        foreach (['inicioX', 'inicioY', 'control1X', 'control1Y', 'control2X', 'control2Y', 'finX', 'finY'] as $key) {
            $this->assertArrayHasKey($key, $element);
            $this->assertIsFloat($element[$key]);
        }

        $this->assertEqualsWithDelta(73, $element['inicioX'], 0.001);
        $this->assertEqualsWithDelta(0, $element['finX'], 0.001);
        $this->assertEqualsWithDelta(73, $element['finY'], 0.001);
        $this->assertArrayNotHasKey('radioInterno', $element);
        $this->assertArrayNotHasKey('angulo', $element);
    }

    public function test_conserva_curva_deformada_y_dimensiona_camellon_y_banqueta(): void
    {
        $raw = [
            'tipo' => 'curva',
            'inicioX' => -120,
            'inicioY' => 40,
            'control1X' => -70,
            'control1Y' => -90,
            'control2X' => 110,
            'control2Y' => 20,
            'finX' => 145,
            'finY' => 70,
            'anchoCarril' => 30,
            'carriles' => 1,
            'bordeIzquierdo' => 'banqueta',
            'bordeDerecho' => 'camellon',
        ];

        $curve = $this->normalize($raw);
        $this->assertSame(-70.0, $curve['control1X']);
        $this->assertSame(110.0, $curve['control2X']);
        $this->assertSame('banqueta', $curve['bordeIzquierdo']);
        $this->assertSame('camellon', $curve['bordeDerecho']);

        $bounds = $this->invoke('bounds', $curve);
        $this->assertGreaterThan(290, $bounds[0]);
        $this->assertGreaterThan(100, $bounds[1]);

        $this->assertSame([320.0, 42.0], $this->invoke('bounds', $this->normalize([
            'tipo' => 'camellon',
            'largo' => 320,
            'ancho' => 42,
        ])));
        $this->assertSame([180.0, 24.0], $this->invoke('bounds', $this->normalize([
            'tipo' => 'banqueta',
            'largo' => 180,
            'ancho' => 24,
        ])));
    }

    public function test_renderiza_los_nuevos_elementos_con_gd(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('La extensión GD no está disponible.');
        }

        $image = imagecreatetruecolor(500, 300);
        $elements = [
            $this->normalize([
                'tipo' => 'curva',
                'inicioX' => -120,
                'inicioY' => 50,
                'control1X' => -80,
                'control1Y' => -100,
                'control2X' => 70,
                'control2Y' => -80,
                'finX' => 130,
                'finY' => 40,
                'anchoCarril' => 24,
                'carriles' => 2,
                'bordeIzquierdo' => 'banqueta',
                'bordeDerecho' => 'camellon',
            ]),
            $this->normalize(['tipo' => 'calle', 'largo' => 220, 'bordeIzquierdo' => 'banqueta']),
            $this->normalize(['tipo' => 'cruce', 'largo' => 180, 'bordeDerecho' => 'camellon']),
            $this->normalize(['tipo' => 'entronque', 'largoBase' => 180, 'largoBrazo' => 100, 'bordeIzquierdo' => 'banqueta']),
            $this->normalize(['tipo' => 'glorieta', 'radioIsla' => 45, 'bordeIzquierdo' => 'camellon', 'bordeDerecho' => 'banqueta']),
            $this->normalize(['tipo' => 'camellon', 'largo' => 200, 'ancho' => 32]),
            $this->normalize(['tipo' => 'banqueta', 'largo' => 200, 'ancho' => 24]),
        ];

        foreach ($elements as $element) {
            $this->invoke('drawLocalElement', $image, $element, 250.0, 150.0);
        }

        $this->assertSame(500, imagesx($image));
        $this->assertSame(300, imagesy($image));
        imagedestroy($image);
    }

    private function normalize(array $raw): array
    {
        return $this->invoke('normalizeElement', $raw);
    }

    private function invoke(string $method, ...$arguments)
    {
        $reflection = new ReflectionClass(CroquisPreviewService::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $call = new ReflectionMethod($service, $method);
        $call->setAccessible(true);

        return $call->invoke($service, ...$arguments);
    }
}
