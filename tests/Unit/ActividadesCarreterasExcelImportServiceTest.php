<?php

namespace Tests\Unit;

use App\Models\Actividad;
use App\Models\ActividadCategoria;
use App\Models\ActividadSubcategoria;
use App\Models\Unidad;
use App\Services\Carreteras\ActividadesCarreterasExcelImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ActividadesCarreterasExcelImportServiceTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = (string) config('database.default');
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
        $this->seedCatalog();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        config()->set('database.default', $this->originalConnection);
        parent::tearDown();
    }

    public function test_importa_solo_el_faltante_y_excluye_puestas_sin_modificar_existentes(): void
    {
        $unit = Unidad::query()->where('slug', 'carreteras')->firstOrFail();
        $category = ActividadCategoria::query()->where('nombre', 'ABANDERAMIENTOS')->firstOrFail();
        $subcategory = ActividadSubcategoria::query()
            ->where('actividad_categoria_id', $category->id)
            ->firstOrFail();

        $existing = Actividad::query()->create([
            'client_uuid' => 'existente-1',
            'sync_status' => 'local',
            'actividad_categoria_id' => $category->id,
            'actividad_subcategoria_id' => $subcategory->id,
            'nombre' => 'CAPTURA EXISTENTE',
            'cantidad' => 2,
            'unidad_org_id' => $unit->id,
            'fecha' => '2026-04-01',
            'observaciones' => 'NO DEBE CAMBIAR',
            'estado_revision' => 'pendiente',
        ]);

        $path = $this->createWorkbook();

        try {
            $service = new ActividadesCarreterasExcelImportService();
            $plan = $service->planificar($path);

            $this->assertSame(4, $plan['analisis']['puestas_excluidas']);
            $this->assertSame(5, $plan['cantidades']['origen']);
            $this->assertSame(2, $plan['cantidades']['existente']);
            $this->assertSame(3, $plan['cantidades']['crear']);
            $this->assertSame(1, $plan['conteos']['crear']);
            $this->assertEmpty($plan['analisis']['errores']);

            $result = $service->ejecutar($plan);
            $this->assertSame(1, $result['registros_creados']);
            $this->assertSame(3, $result['actividades_agregadas']);

            $existing->refresh();
            $this->assertSame(2, $existing->cantidad);
            $this->assertSame('NO DEBE CAMBIAR', $existing->observaciones);
            $this->assertSame(5, (int) Actividad::query()
                ->whereDate('fecha', '2026-04-01')
                ->where('actividad_categoria_id', $category->id)
                ->sum('cantidad'));

            $secondPlan = $service->planificar($path);
            $this->assertSame(0, $secondPlan['cantidades']['crear']);
            $this->assertSame(1, $secondPlan['conteos']['omitido']);
        } finally {
            @unlink($path);
        }
    }

    private function createWorkbook(): string
    {
        $workbook = new Spreadsheet();
        $sheet = $workbook->getActiveSheet();
        $sheet->setTitle('ABR');
        $sheet->setCellValue('A1', 'OPERATIVIDAD DEL 01 AL 30 DE ABRIL DE 2026');
        $sheet->fromArray(['CATEGORÍA', 1, 'TOTAL'], null, 'A3');
        $sheet->fromArray(['ABANDERAMIENTOS', 5, 5], null, 'A4');
        $sheet->fromArray(['PUESTA A DISPOSICIÓN AL MP', 4, 4], null, 'A5');

        $path = tempnam(sys_get_temp_dir(), 'actividades_carreteras_') . '.xlsx';
        (new Xlsx($workbook))->save($path);
        return $path;
    }

    private function seedCatalog(): void
    {
        $unit = Unidad::query()->create([
            'nombre' => 'PROTECCIÓN A CARRETERAS',
            'slug' => 'carreteras',
        ]);
        $category = ActividadCategoria::query()->create([
            'nombre' => 'ABANDERAMIENTOS',
            'slug' => 'abanderamientos',
            'activo' => true,
        ]);
        ActividadSubcategoria::query()->create([
            'actividad_categoria_id' => $category->id,
            'unidad_id' => $unit->id,
            'nombre' => 'OTROS ABANDERAMIENTOS (Especificar en las novedades relevantes)',
            'slug' => 'otros-abanderamientos',
            'activo' => true,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('unidades', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->timestamps();
        });
        Schema::create('actividad_categorias', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('actividad_subcategorias', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actividad_categoria_id');
            $table->unsignedBigInteger('unidad_id')->nullable();
            $table->string('nombre');
            $table->string('slug');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('actividades', function (Blueprint $table): void {
            $table->id();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->string('submission_fingerprint', 64)->nullable()->unique();
            $table->string('fuente_importacion', 80)->nullable();
            $table->string('clave_importacion', 64)->nullable();
            $table->string('sync_status')->default('local');
            $table->unsignedBigInteger('actividad_categoria_id');
            $table->unsignedBigInteger('actividad_subcategoria_id')->nullable();
            $table->string('nombre');
            $table->unsignedInteger('cantidad')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('unidad_org_id')->nullable();
            $table->date('fecha')->nullable();
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado_revision')->default('pendiente');
            $table->timestamps();
            $table->unique(['fuente_importacion', 'clave_importacion']);
        });
    }
}
