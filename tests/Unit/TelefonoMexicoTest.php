<?php

namespace Tests\Unit;

use App\Support\TelefonoMexico;
use PHPUnit\Framework\TestCase;

class TelefonoMexicoTest extends TestCase
{
    /**
     * @dataProvider telefonosProvider
     */
    public function test_normaliza_telefonos_mexicanos($entrada, ?string $esperado): void
    {
        $this->assertSame($esperado, TelefonoMexico::normalize($entrada));
    }

    public function telefonosProvider(): array
    {
        return [
            'morelia' => ['4434765057', '4434765057'],
            'otra lada' => ['3541234567', '3541234567'],
            'codigo pais' => ['+52 354 123 4567', '3541234567'],
            'codigo pais movil anterior' => ['5213541234567', '3541234567'],
            'prefijo 044 anterior' => ['0443541234567', '3541234567'],
            'prefijo 045 anterior' => ['0453541234567', '3541234567'],
            'prefijo 01 anterior' => ['013541234567', '3541234567'],
            'vacio' => ['', null],
            'nulo' => [null, null],
        ];
    }
}
