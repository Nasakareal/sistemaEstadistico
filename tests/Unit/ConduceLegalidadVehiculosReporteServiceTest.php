<?php

namespace Tests\Unit;

use App\Services\ConduceLegalidadVehiculosReporteService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConduceLegalidadVehiculosReporteServiceTest extends TestCase
{
    private string $conexionOriginal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->conexionOriginal = (string) config('database.default');
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('conduce_legalidad_operativos', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_operativo');
            $table->date('fecha');
            $table->string('municipio')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
        Schema::create('conduce_legalidad_capturas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('operativo_id');
            $table->date('fecha')->nullable();
            $table->string('municipio')->nullable();
        });
        Schema::create('conduce_legalidad_vehiculos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('captura_id');
            $table->boolean('retencion_vehiculo')->default(false);
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        config()->set('database.default', $this->conexionOriginal);
        parent::tearDown();
    }

    public function test_construye_la_ficha_del_vehiculo_sin_campos_vacios(): void
    {
        $service = new ConduceLegalidadVehiculosReporteService();
        $vehiculo = (object) [
            'marca' => 'ITALIKA',
            'linea' => 'FT150',
            'modelo' => '2026',
            'tipo' => '',
            'tipo_general' => 'MOTOCICLETA',
            'color' => 'ROJO',
            'placas' => null,
            'estado_placas' => null,
            'serie' => 'ABC123',
            'numero_inventario' => '0863',
            'tipo_servicio' => 'PARTICULAR',
        ];

        $resultado = $service->datosVehiculo($vehiculo);

        $this->assertStringContainsString('Marca: ITALIKA', $resultado);
        $this->assertStringContainsString('Tipo: MOTOCICLETA', $resultado);
        $this->assertStringContainsString('No. inventario: 0863', $resultado);
        $this->assertStringNotContainsString('Placas:', $resultado);
    }

    public function test_combina_motivos_sin_duplicados_y_conserva_el_orden(): void
    {
        $service = new ConduceLegalidadVehiculosReporteService();

        $resultado = $service->combinarMotivos([
            'Art. 328 - Sin placas',
            '  Art. 419 - Sin casco  ',
            'art. 328 - sin placas',
            '',
        ]);

        $this->assertSame('Art. 328 - Sin placas; Art. 419 - Sin casco', $resultado);
    }

    public function test_muestra_un_texto_claro_cuando_no_hay_motivo(): void
    {
        $service = new ConduceLegalidadVehiculosReporteService();

        $this->assertSame('Sin motivo especificado', $service->combinarMotivos(['', '   ']));
    }

    public function test_cuenta_una_jornada_por_fecha_y_municipio_y_excluye_borrados(): void
    {
        DB::table('conduce_legalidad_operativos')->insert([
            ['id' => 1, 'tipo_operativo' => 'conduce_legalidad', 'fecha' => '2026-10-27', 'municipio' => 'Uruapan', 'deleted_at' => null],
            ['id' => 2, 'tipo_operativo' => 'conduce_legalidad', 'fecha' => '2026-10-27', 'municipio' => ' uruapan ', 'deleted_at' => null],
            ['id' => 3, 'tipo_operativo' => 'conduce_legalidad', 'fecha' => '2026-10-27', 'municipio' => 'URUAPAN', 'deleted_at' => '2026-10-28 08:00:00'],
            ['id' => 4, 'tipo_operativo' => 'conduce_legalidad', 'fecha' => '2026-10-23', 'municipio' => 'Morelia', 'deleted_at' => null],
            ['id' => 5, 'tipo_operativo' => 'conduce_legalidad', 'fecha' => '2026-10-23', 'municipio' => 'MORELIA', 'deleted_at' => null],
            ['id' => 6, 'tipo_operativo' => 'conduce_legalidad', 'fecha' => '2026-10-24', 'municipio' => 'Morelia', 'deleted_at' => null],
        ]);

        foreach (range(1, 6) as $id) {
            DB::table('conduce_legalidad_capturas')->insert([
                'id' => $id,
                'operativo_id' => $id,
                'fecha' => null,
                'municipio' => null,
            ]);
            DB::table('conduce_legalidad_vehiculos')->insert([
                'id' => $id,
                'captura_id' => $id,
                'retencion_vehiculo' => false,
            ]);
        }

        $resumen = (new ConduceLegalidadVehiculosReporteService())->resumen(Request::create('/'));

        $this->assertSame(5, $resumen['vehiculos']);
        $this->assertSame(3, $resumen['operativos']);
        $this->assertSame(2, $resumen['municipios']);
    }
}
