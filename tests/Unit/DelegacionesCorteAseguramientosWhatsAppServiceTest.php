<?php

namespace Tests\Unit;

use App\Models\Hechos;
use App\Models\Lesionado;
use App\Services\DelegacionesCorteAseguramientosWhatsAppService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DelegacionesCorteAseguramientosWhatsAppServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.schedule_timezone' => 'America/Mexico_City',
            'app.timezone' => 'America/Mexico_City',
        ]);
    }

    public function test_rango_del_primer_corte_va_de_22_a_15_horas(): void
    {
        [$inicio, $fin] = $this->service()->rango(Carbon::parse('2026-06-19 15:01', 'America/Mexico_City'));

        $this->assertSame('2026-06-18 22:00:00', $inicio->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-19 15:00:00', $fin->format('Y-m-d H:i:s'));
    }

    public function test_rango_del_segundo_corte_va_de_15_a_20_horas(): void
    {
        [$inicio, $fin] = $this->service()->rango(Carbon::parse('2026-06-19 20:01', 'America/Mexico_City'));

        $this->assertSame('2026-06-19 15:00:00', $inicio->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-19 20:00:00', $fin->format('Y-m-d H:i:s'));
    }

    public function test_rango_del_tercer_corte_va_de_20_a_22_horas(): void
    {
        [$inicio, $fin] = $this->service()->rango(Carbon::parse('2026-06-19 22:01', 'America/Mexico_City'));

        $this->assertSame('2026-06-19 20:00:00', $inicio->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-19 22:00:00', $fin->format('Y-m-d H:i:s'));
    }

    public function test_no_incluye_choque_comun_con_dos_lesionados(): void
    {
        $hecho = $this->hecho('CHOQUE', [
            new Lesionado(['tipo_lesion' => 'LESIONADO']),
            new Lesionado(['tipo_lesion' => 'LESIONADO']),
        ]);

        $this->assertFalse($this->service()->debeIncluirSiniestroRelevante($hecho));
    }

    public function test_incluye_siniestro_con_tres_lesionados(): void
    {
        $hecho = $this->hecho('CHOQUE', [
            new Lesionado(['tipo_lesion' => 'LESIONADO']),
            new Lesionado(['tipo_lesion' => 'LESIONADO']),
            new Lesionado(['tipo_lesion' => 'LESIONADO']),
        ]);

        $this->assertTrue($this->service()->debeIncluirSiniestroRelevante($hecho));
    }

    public function test_incluye_siniestro_con_fallecido(): void
    {
        $hecho = $this->hecho('CHOQUE', [
            new Lesionado(['tipo_lesion' => 'FALLECIDO']),
        ]);

        $this->assertTrue($this->service()->debeIncluirSiniestroRelevante($hecho));
    }

    public function test_incluye_siniestro_con_tren(): void
    {
        $hecho = $this->hecho('CHOQUE CONTRA TREN', []);

        $this->assertTrue($this->service()->debeIncluirSiniestroRelevante($hecho));
    }

    private function hecho(string $tipo, array $lesionados): Hechos
    {
        $hecho = new Hechos([
            'tipo_hecho' => $tipo,
        ]);

        $hecho->setRelation('lesionados', new Collection($lesionados));

        return $hecho;
    }

    private function service(): DelegacionesCorteAseguramientosWhatsAppService
    {
        return new DelegacionesCorteAseguramientosWhatsAppService();
    }
}
