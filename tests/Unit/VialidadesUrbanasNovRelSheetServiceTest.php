<?php

namespace Tests\Unit;

use App\Models\PuestaDisposicion;
use App\Models\PuestaDisposicionObjeto;
use App\Models\PuestaDisposicionPersona;
use App\Models\PuestaDisposicionVehiculo;
use App\Services\VialidadesUrbanas\Hojas\NovRelSheetService;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use ReflectionMethod;
use Tests\TestCase;

class VialidadesUrbanasNovRelSheetServiceTest extends TestCase
{
    public function test_renderiza_novedades_con_narrativa_y_hasta_tres_fotos(): void
    {
        $imagePath = $this->fakePngPath();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $rows = [[
            'hora' => "17:05\nhrs.",
            'lugar' => 'Morelia',
            'asunto' => "ABANDERAMIENTOS\n(OBRAS PÚBLICAS)",
            'resolucion' => 'Continúa el apoyo en avenida Francisco I. Madero.',
            'generales' => 'Sin novedad',
            'fotos' => [$imagePath, $imagePath, $imagePath, $imagePath],
        ]];

        $method = new ReflectionMethod(NovRelSheetService::class, 'render');
        $method->setAccessible(true);
        $method->invoke(new NovRelSheetService(), $sheet, $rows);

        $this->assertSame('No.', $sheet->getCell('A1')->getValue());
        $this->assertSame('HORA', $sheet->getCell('B1')->getValue());
        $this->assertSame('LUGAR', $sheet->getCell('C1')->getValue());
        $this->assertSame('ASUNTO', $sheet->getCell('D1')->getValue());
        $this->assertSame('RESOLUCIÓN', $sheet->getCell('E1')->getValue());
        $this->assertSame("VEHÍCULOS TURNADOS, PERSONAS\nDETENIDAS, VEHÍCULOS RECUPERADOS\n(CANTIDAD Y DATOS GENERALES)", $sheet->getCell('F1')->getValue());
        $this->assertSame('GRAFICA 1', $sheet->getCell('G1')->getValue());
        $this->assertSame('GRAFICA 2', $sheet->getCell('H1')->getValue());
        $this->assertSame('Grafica 3', $sheet->getCell('I1')->getValue());

        $this->assertSame(1, $sheet->getCell('A2')->getValue());
        $this->assertSame("17:05\nhrs.", $sheet->getCell('B2')->getValue());
        $this->assertSame('Morelia', $sheet->getCell('C2')->getValue());
        $this->assertSame("ABANDERAMIENTOS\n(OBRAS PÚBLICAS)", $sheet->getCell('D2')->getValue());
        $this->assertSame('Continúa el apoyo en avenida Francisco I. Madero.', $sheet->getCell('E2')->getValue());
        $this->assertSame('Sin novedad', $sheet->getCell('F2')->getValue());
        $this->assertSame(262, $sheet->getRowDimension(2)->getRowHeight());
        $this->assertSame('A2', $sheet->getFreezePane());

        $drawings = iterator_to_array($sheet->getDrawingCollection());

        $this->assertCount(3, $drawings);
        $this->assertSame('G2', $drawings[0]->getCoordinates());
        $this->assertSame('H2', $drawings[1]->getCoordinates());
        $this->assertSame('I2', $drawings[2]->getCoordinates());
    }

    public function test_renderiza_mensaje_cuando_no_hay_novedades(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $method = new ReflectionMethod(NovRelSheetService::class, 'render');
        $method->setAccessible(true);
        $method->invoke(new NovRelSheetService(), $sheet, []);

        $this->assertSame('-', $sheet->getCell('A2')->getValue());
        $this->assertSame('SIN NOVEDADES RELEVANTES EN EL PERIODO.', $sheet->getCell('B2')->getValue());
        $this->assertSame('B2:I2', $sheet->getCell('B2')->getMergeRange());
    }

    public function test_arma_fila_de_puesta_a_disposicion_para_novedades_relevantes(): void
    {
        $puesta = new PuestaDisposicion([
            'numero_puesta' => 12,
            'anio' => 2026,
            'tipo_puesta' => 'Hecho de tránsito',
            'motivo' => 'Lesiones',
            'fecha_puesta' => '2026-06-02',
            'hora_puesta' => '18:45',
            'lugar_puesta' => 'Morelia',
            'narrativa' => 'Se pone a disposición a una persona.',
            'observaciones' => 'Se turna vehículo.',
            'autoridad_receptora' => 'Fiscalía',
            'nombre_mp' => 'Lic. MP',
            'carpeta_investigacion' => 'ABC/123/2026',
            'oficio' => 'VU/55/2026',
        ]);
        $puesta->setRelation('personas', new Collection([
            new PuestaDisposicionPersona([
                'nombre_completo' => 'Juan Pérez',
                'alias' => 'El Juan',
                'calidad' => 'Detenido',
            ]),
        ]));
        $puesta->setRelation('vehiculos', new Collection([
            new PuestaDisposicionVehiculo([
                'tipo' => 'Automóvil',
                'marca' => 'Nissan',
                'submarca' => 'Tsuru',
                'placas' => 'ABC123',
                'calidad' => 'Turnado',
            ]),
        ]));
        $puesta->setRelation('objetos', new Collection([
            new PuestaDisposicionObjeto([
                'cantidad' => 1,
                'unidad_medida' => 'pieza',
                'tipo_objeto' => 'Llave',
                'descripcion' => 'Llave de vehículo',
            ]),
        ]));

        $method = new ReflectionMethod(NovRelSheetService::class, 'filaPuestaDisposicion');
        $method->setAccessible(true);

        $row = $method->invoke(new NovRelSheetService(), $puesta);

        $this->assertSame('2026-06-02 18:45:00', $row['fecha_hora']);
        $this->assertSame(3, $row['orden_tipo']);
        $this->assertTrue($row['forzar']);
        $this->assertSame("18:45\nhrs.", $row['hora']);
        $this->assertSame('Morelia', $row['lugar']);
        $this->assertSame("PUESTA A DISPOSICIÓN\n12/2026\nHECHO DE TRÁNSITO\n(LESIONES)", $row['asunto']);
        $this->assertSame("Se pone a disposición a una persona.\n\nSe turna vehículo.", $row['resolucion']);
        $this->assertStringContainsString('1 PERSONA DETENIDA: JUAN PÉREZ ALIAS EL JUAN DETENIDO', $row['generales']);
        $this->assertStringContainsString('1 VEHÍCULO TURNADO: AUTOMÓVIL NISSAN TSURU ABC123 TURNADO', $row['generales']);
        $this->assertStringContainsString('1 OBJETO ASEGURADO: 1.00 PIEZA LLAVE LLAVE DE VEHÍCULO', $row['generales']);
        $this->assertStringContainsString('AUTORIDAD RECEPTORA: FISCALÍA', $row['generales']);
        $this->assertSame([], $row['fotos']);
    }

    private function fakePngPath(): string
    {
        $directory = storage_path('app/testing');

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $path = $directory . DIRECTORY_SEPARATOR . 'nov_rel_fake.png';

        if (!is_file($path)) {
            file_put_contents($path, base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAFgwJ/luzj2wAAAABJRU5ErkJggg=='
            ));
        }

        return $path;
    }
}
