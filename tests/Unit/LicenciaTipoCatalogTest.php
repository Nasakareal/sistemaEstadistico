<?php

namespace Tests\Unit;

use App\Support\LicenciaTipoCatalog;
use Tests\TestCase;

class LicenciaTipoCatalogTest extends TestCase
{
    public function test_normaliza_tipos_y_aliases_de_licencia(): void
    {
        $this->assertSame('SERVICIO_PUBLICO', LicenciaTipoCatalog::normalize('Servicio público'));
        $this->assertSame('AUTOMOVILISTA', LicenciaTipoCatalog::normalize('Particular'));
        $this->assertSame('CHOFER', LicenciaTipoCatalog::normalize('Operador'));
        $this->assertSame('MOTOCICLISTA', LicenciaTipoCatalog::normalize('C'));
        $this->assertSame('PERMISO', LicenciaTipoCatalog::normalize('permiso'));
        $this->assertNull(LicenciaTipoCatalog::normalize('Tipo inventado'));
    }

    public function test_request_value_conserva_invalidos_para_que_validacion_los_rechace(): void
    {
        $this->assertSame('AUTOMOVILISTA', LicenciaTipoCatalog::requestValue('Automovilista'));
        $this->assertSame('Tipo inventado', LicenciaTipoCatalog::requestValue('Tipo inventado'));
        $this->assertNull(LicenciaTipoCatalog::requestValue(''));
    }
}
