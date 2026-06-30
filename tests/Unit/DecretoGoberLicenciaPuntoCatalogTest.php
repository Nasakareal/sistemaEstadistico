<?php

namespace Tests\Unit;

use App\Support\DecretoGoberLicenciaPuntoCatalog;
use Tests\TestCase;

class DecretoGoberLicenciaPuntoCatalogTest extends TestCase
{
    public function test_catalogo_no_usa_referencias_combinadas(): void
    {
        $codigos = [];

        foreach (DecretoGoberLicenciaPuntoCatalog::rows() as $row) {
            $this->assertArrayNotHasKey($row['codigo'], $codigos, 'Codigo duplicado: ' . $row['codigo']);
            $codigos[$row['codigo']] = true;

            foreach (['articulo', 'fraccion', 'inciso'] as $field) {
                $value = (string) ($row[$field] ?? '');

                $this->assertDoesNotMatchRegularExpression('/[,;]/', $value, $row['codigo'] . ' combina ' . $field);
                $this->assertDoesNotMatchRegularExpression('/[A-Z]+-[A-Z]+/i', $value, $row['codigo'] . ' usa rango en ' . $field);
            }
        }
    }

    public function test_documento_word_contiene_todos_los_articulos_del_catalogo(): void
    {
        DecretoGoberLicenciaPuntoCatalog::assertSourceCoversRows(
            public_path(DecretoGoberLicenciaPuntoCatalog::SOURCE_FILENAME)
        );

        $this->assertTrue(true);
    }

    public function test_articulo_419_motocicleta_esta_separado_por_inciso(): void
    {
        $rows = collect(DecretoGoberLicenciaPuntoCatalog::rows())
            ->where('articulo', '419')
            ->where('fraccion', 'II')
            ->keyBy('inciso');

        $this->assertSame(
            ['a', 'b', 'c', 'd'],
            $rows->keys()->sort()->values()->all()
        );

        $this->assertSame(1, $rows['a']['puntos']);
        $this->assertTrue($rows['a']['amonestacion']);
        $this->assertFalse($rows['a']['arresto_persona']);

        $this->assertSame(3, $rows['b']['puntos']);
        $this->assertFalse($rows['b']['amonestacion']);
        $this->assertTrue($rows['b']['arresto_persona']);
        $this->assertTrue($rows['b']['retencion_vehiculo']);

        $this->assertSame(1, $rows['c']['puntos']);
        $this->assertStringContainsString('reflejantes', strtolower($rows['c']['nombre']));

        $this->assertSame(3, $rows['d']['puntos']);
        $this->assertStringContainsString('casco', strtolower($rows['d']['nombre']));
        $this->assertTrue($rows['d']['retencion_vehiculo']);
    }
}
