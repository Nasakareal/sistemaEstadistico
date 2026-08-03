<?php

namespace Tests\Unit;

use App\Mail\FormatoInegiChoquesMail;
use Carbon\Carbon;
use Tests\TestCase;

class FormatoInegiChoquesMailTest extends TestCase
{
    public function test_reenvio_incluye_motivo_disculpa_y_asunto_corregido(): void
    {
        $mail = new FormatoInegiChoquesMail(
            Carbon::parse('2026-07-01'),
            'FORMATO_INEGI_CHOQUES_2026-07.xlsx',
            'contenido',
            302,
            Carbon::parse('2026-07-31'),
            'Se corrigieron las claves de municipio del campo MPIO.'
        );

        $html = $mail->render();

        $this->assertSame(
            'Reenvío corregido - Formato INEGI Choques - 2026-07-01 a 2026-07-31',
            $mail->subject
        );
        $this->assertStringContainsString('Se corrigieron las claves de municipio del campo MPIO.', $html);
        $this->assertStringContainsString('Ofrecemos una disculpa por el inconveniente', $html);
        $this->assertStringContainsString('en sustitución del enviado anteriormente', $html);
    }
}
