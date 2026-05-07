<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SiniestrosReportScopeTest extends TestCase
{
    public function test_generadores_diarios_de_siniestros_filtran_solo_unidad_uno(): void
    {
        foreach ([
            'app/Services/ParteNovedadesGenerator.php',
            'app/Services/MiniParteGenerator.php',
            'app/Services/BitacoraGenerator.php',
            'app/Services/ExcelNovedadesGenerator.php',
        ] as $path) {
            $source = $this->source($path);

            $this->assertStringContainsString('UNIDAD_SINIESTROS_ID = 1', $source, $path);
            $this->assertStringContainsString("->where('unidad_org_id', self::UNIDAD_SINIESTROS_ID)", $source, $path);
        }

        $actividadSource = $this->source('app/Services/ActividadInformeService.php');

        $this->assertStringContainsString('UNIDAD_SINIESTROS_ID = 1', $actividadSource);
        $this->assertStringContainsString("->where('unidad_org_id', self::UNIDAD_SINIESTROS_ID)", $actividadSource);
    }

    public function test_excel_multihoja_de_siniestros_filtra_hechos_personal_patrullas_armamento_y_dictamenes(): void
    {
        $totalSource = $this->source('app/Services/Exports/Sheets/TotalSheet.php');
        $this->assertStringContainsString("->where('unidad_org_id', self::UNIDAD_SINIESTROS_ID)", $totalSource);
        $this->assertStringContainsString("->where('hechos.unidad_org_id', self::UNIDAD_SINIESTROS_ID)", $totalSource);
        $this->assertStringContainsString("->where('p.unidad_id', self::UNIDAD_SINIESTROS_ID)", $totalSource);
        $this->assertStringContainsString("leftJoin('lesionados'", $totalSource);
        $this->assertStringContainsString('lesionados.tipo_lesion', $totalSource);
        $this->assertStringContainsString('FALLECIDO', $totalSource);

        $this->assertStringContainsString(
            "->where('unidad_id', self::UNIDAD_SINIESTROS_ID)",
            $this->source('app/Services/Exports/Sheets/EstadoFuerzaSheet.php')
        );

        $this->assertStringContainsString(
            "->where('unidad_id', self::UNIDAD_SINIESTROS_ID)",
            $this->source('app/Services/Exports/Sheets/EstadoFuerzaVehicularSheet.php')
        );

        $armamentoSource = $this->source('app/Services/Exports/Sheets/EstadoFuerzaArmamentoSheet.php');
        $this->assertStringContainsString("->where('unidad_id', self::UNIDAD_SINIESTROS_ID)", $armamentoSource);
        $this->assertStringContainsString("->where('a.unidad_id', self::UNIDAD_SINIESTROS_ID)", $armamentoSource);

        $novRelSource = $this->source('app/Services/Exports/Sheets/NovRelSheet.php');
        $this->assertStringContainsString("->whereHas('hecho'", $novRelSource);
        $this->assertStringContainsString("->where('unidad_org_id', self::UNIDAD_SINIESTROS_ID)", $novRelSource);
    }

    public function test_reportes_de_siniestros_y_whatsapp_no_cuentan_otras_unidades(): void
    {
        $settingsSource = $this->source('app/Http/Controllers/EstadisticasSiniestrosSettingsController.php');
        $this->assertStringContainsString("->where('unidad_id', self::UNIDAD_SINIESTROS_ID)", $settingsSource);

        $legacySource = $this->source('app/Http/Controllers/EstadisticasController.php');
        $this->assertStringContainsString(
            "Hechos::where('unidad_org_id', self::UNIDAD_SINIESTROS_ID)",
            $legacySource
        );
        $this->assertGreaterThanOrEqual(
            4,
            substr_count($legacySource, "->where('unidad_org_id', self::UNIDAD_SINIESTROS_ID)")
        );

        foreach ([
            'app/Console/Commands/EnviarResumenSiniesrosWhatsApp.php',
            'app/Console/Commands/EnviarTarjetaHechosWhatsApp.php',
        ] as $path) {
            $source = $this->source($path);

            $this->assertStringContainsString("->where('unidad_org_id', self::UNIDAD_SINIESTROS_ID)", $source, $path);
            $this->assertStringContainsString("->where('hechos.unidad_org_id', self::UNIDAD_SINIESTROS_ID)", $source, $path);
            $this->assertStringContainsString('whereLesionadoNoFallecido', $source, $path);
            $this->assertStringContainsString('whereLesionadoFallecido', $source, $path);
        }
    }

    public function test_resumen_whatsapp_todas_unidades_usa_corte_de_1900_y_no_filtra_por_unidad(): void
    {
        $serviceSource = $this->source('app/Services/ResumenTodasUnidadesWhatsAppService.php');
        $commandSource = $this->source('app/Console/Commands/EnviarResumenTodasUnidadesWhatsApp.php');
        $kernelSource = $this->source('app/Console/Kernel.php');
        $configSource = $this->source('config/services.php');

        $this->assertStringContainsString('setTime(19, 0, 0)', $serviceSource);
        $this->assertStringNotContainsString('UNIDAD_SINIESTROS_ID', $serviceSource);
        $this->assertStringContainsString('whatsapp:resumen-todas-unidades', $commandSource);
        $this->assertStringContainsString("->dailyAt('19:00')", $kernelSource);
        $this->assertStringContainsString('WHATSAPP_TODAS_UNIDADES_TO', $configSource);
        $this->assertStringContainsString('WHATSAPP_TODAS_UNIDADES_TEMPLATE', $configSource);
    }

    private function source(string $path): string
    {
        $fullPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        $this->assertFileExists($fullPath);

        return (string) file_get_contents($fullPath);
    }
}
