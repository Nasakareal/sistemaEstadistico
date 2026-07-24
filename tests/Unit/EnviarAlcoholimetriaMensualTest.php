<?php

namespace Tests\Unit;

use App\Console\Commands\EnviarAlcoholimetriaMensual;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class EnviarAlcoholimetriaMensualTest extends TestCase
{
    public function test_conserva_los_dos_destinatarios_obligatorios(): void
    {
        config([
            'services.alcoholimetria_mensual.required_mail_to' => [
                'michpreviene@gmail.com',
                'dr.bernier26@hotmail.com',
            ],
            'services.alcoholimetria_mensual.mail_to' => 'adicional@example.com',
        ]);

        $method = new ReflectionMethod(
            EnviarAlcoholimetriaMensual::class,
            'destinatarios'
        );
        $method->setAccessible(true);
        $destinatarios = $method->invoke(new EnviarAlcoholimetriaMensual());

        $this->assertContains('michpreviene@gmail.com', $destinatarios);
        $this->assertContains('dr.bernier26@hotmail.com', $destinatarios);
        $this->assertContains('adicional@example.com', $destinatarios);
        $this->assertCount(3, $destinatarios);
    }

    public function test_omite_el_mes_anterior_cuando_el_inicio_es_julio_de_2026(): void
    {
        config([
            'services.alcoholimetria_mensual.start_month' => '2026-07',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-07-24 12:00:00'));

        try {
            $this->artisan('alcoholimetria:enviar-reporte-mensual')
                ->expectsOutput(
                    'No se genera 2026-06: los reportes de alcoholimetría comienzan en 2026-07.'
                )
                ->assertExitCode(0);
        } finally {
            Carbon::setTestNow();
        }
    }
}
