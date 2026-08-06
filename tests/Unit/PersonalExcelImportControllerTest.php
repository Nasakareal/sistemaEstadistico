<?php

namespace Tests\Unit;

use App\Http\Controllers\PersonalController;
use App\Models\Unidad;
use App\Models\User;
use App\Services\Personal\PersonalExcelImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PersonalExcelImportControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_importacion_usa_la_unidad_del_usuario_y_no_una_unidad_del_archivo(): void
    {
        $unidad = Unidad::query()->create([
            'nombre' => 'Unidad del usuario ' . uniqid(),
            'slug' => 'unidad-usuario-' . uniqid(),
            'activa' => true,
        ]);
        $usuario = User::factory()->create(['unidad_id' => $unidad->id]);
        Auth::login($usuario);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'NOMBRE COMPLETO');
        $base = tempnam(sys_get_temp_dir(), 'personal_controller_');
        $archivo = $base . '.xlsx';
        @unlink($base);
        (new Xlsx($spreadsheet))->save($archivo);
        $spreadsheet->disconnectWorksheets();

        try {
            $uploaded = new UploadedFile(
                $archivo,
                'personal.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            );
            $request = Request::create('/admin/settings/personal/importar', 'POST', [], [], [
                'archivo_personal' => $uploaded,
            ]);
            $request->setLaravelSession(app('session')->driver());
            $importador = Mockery::mock(PersonalExcelImportService::class);
            $importador->shouldReceive('importar')
                ->once()
                ->with(Mockery::type('string'), $unidad->id)
                ->andReturn([
                    'total' => 1,
                    'importados' => 1,
                    'omitidos' => 0,
                    'errores' => [],
                    'advertencias' => [],
                ]);

            $response = (new PersonalController())->importar($request, $importador);

            $this->assertSame(route('personal.index'), $response->getTargetUrl());
            $this->assertSame($unidad->nombre, session('import_result.unidad'));
        } finally {
            if (is_file($archivo)) {
                @unlink($archivo);
            }
        }
    }
}
