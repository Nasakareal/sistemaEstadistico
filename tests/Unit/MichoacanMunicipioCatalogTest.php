<?php

namespace Tests\Unit;

use App\Services\Inegi\MichoacanMunicipioCatalog;
use PHPUnit\Framework\TestCase;

class MichoacanMunicipioCatalogTest extends TestCase
{
    public function test_contiene_las_113_claves_del_catalogo_inegi(): void
    {
        $catalogo = MichoacanMunicipioCatalog::todos();

        $this->assertCount(113, $catalogo);
        $this->assertSame(range(1, 113), array_keys($catalogo));
        $this->assertSame('Lázaro Cárdenas', $catalogo[52]);
        $this->assertSame('Morelia', $catalogo[53]);
        $this->assertSame('José Sixto Verduzco', $catalogo[113]);
    }

    public function test_resuelve_nombres_sin_importar_mayusculas_acentos_o_sufijos(): void
    {
        $this->assertSame(3, MichoacanMunicipioCatalog::codigo('ALVARO OBREGON'));
        $this->assertSame(52, MichoacanMunicipioCatalog::codigo('Lázaro Cárdenas, Michoacán'));
        $this->assertSame(66, MichoacanMunicipioCatalog::codigo('PATZCUARO'));
        $this->assertSame(88, MichoacanMunicipioCatalog::codigo('Municipio Tarímbaro'));
    }

    public function test_resuelve_cabeceras_municipales_comunes_sin_adivinar_localidades(): void
    {
        $this->assertSame(34, MichoacanMunicipioCatalog::codigo('Ciudad Hidalgo'));
        $this->assertSame(55, MichoacanMunicipioCatalog::codigo('Nueva Italia de Ruiz'));
        $this->assertSame(75, MichoacanMunicipioCatalog::codigo('Los Reyes de Salgado'));
        $this->assertSame(113, MichoacanMunicipioCatalog::codigo('Pastor Ortiz'));
        $this->assertNull(MichoacanMunicipioCatalog::codigo('Colonia sin municipio verificable'));
    }
}
