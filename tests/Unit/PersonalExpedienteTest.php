<?php

namespace Tests\Unit;

use App\Models\Personal;
use Carbon\Carbon;
use Tests\TestCase;

class PersonalExpedienteTest extends TestCase
{
    public function test_expone_etiquetas_de_catalogos_y_fechas_del_expediente(): void
    {
        $personal = new Personal([
            'fecha_nacimiento' => '1990-05-12',
            'tipo_sangre' => 'O_POSITIVO',
            'ultimo_grado_estudios' => 'LICENCIATURA',
            'alergias_estado' => 'SI',
            'alergias' => 'Penicilina',
            'correo_electronico' => 'elemento@example.com',
            'fecha_ingreso' => '2015-01-10',
            'fecha_ingreso_unidad' => '2022-03-20',
        ]);

        $this->assertSame('O+', $personal->tipoSangreLabel());
        $this->assertSame('Licenciatura', $personal->ultimoGradoEstudiosLabel());
        $this->assertSame('Sí presenta alergias', $personal->alergiasEstadoLabel());
        $this->assertSame('elemento@example.com', $personal->correo_electronico);
        $this->assertInstanceOf(Carbon::class, $personal->fecha_nacimiento);
        $this->assertSame('2015-01-10', $personal->fecha_ingreso->toDateString());
        $this->assertSame('2022-03-20', $personal->fecha_ingreso_unidad->toDateString());
    }

    public function test_catalogos_incluyen_opciones_cerradas_para_campos_repetibles(): void
    {
        $this->assertSame('Desconocido', Personal::TIPOS_SANGRE['DESCONOCIDO']);
        $this->assertSame('Doctorado', Personal::GRADOS_ESTUDIO['DOCTORADO']);
        $this->assertSame('Ninguna conocida', Personal::ESTADOS_ALERGIAS['NINGUNA']);
    }
}
