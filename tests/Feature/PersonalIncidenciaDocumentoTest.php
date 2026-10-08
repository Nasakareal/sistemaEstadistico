<?php

namespace Tests\Feature;

use App\Http\Controllers\PersonalIncidenciaController;
use App\Models\IncidenciaTipo;
use App\Models\Personal;
use App\Models\PersonalDocumento;
use App\Models\PersonalIncidencia;
use App\Models\Unidad;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonalIncidenciaDocumentoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guarda_el_comprobante_y_lo_vincula_con_la_incidencia(): void
    {
        config(['services.azure_storage.documentos_enabled' => false]);
        Storage::fake('public');

        $unidad = Unidad::query()->where('slug', 'carreteras')->firstOrFail();
        $tipo = IncidenciaTipo::query()->firstOrCreate(
            ['clave' => 'INCAPACIDAD'],
            ['nombre' => 'INCAPACIDAD', 'categoria' => 'PERSONAL', 'activo' => true]
        );
        $personal = Personal::query()->create([
            'unidad_id' => $unidad->id,
            'nombre' => 'DOCUMENTO',
            'ap_paterno' => 'PRUEBA',
            'estatus' => 'ACTIVO',
        ]);
        $archivo = UploadedFile::fake()->create('incapacidad.jpg', 80, 'image/jpeg');
        $request = Request::create(
            '/personal/' . $personal->id . '/incidencias',
            'POST',
            [
                'tipo' => $tipo->clave,
                'fecha_inicio' => '2026-10-01',
                'fecha_fin' => '2026-10-05',
                'folio' => 'INC-PRUEBA-001',
            ],
            [],
            ['archivo_incidencia' => $archivo]
        );

        (new PersonalIncidenciaController())->store($request, $personal);

        $incidencia = PersonalIncidencia::query()
            ->where('personal_id', $personal->id)
            ->where('folio', 'INC-PRUEBA-001')
            ->firstOrFail();
        $documento = PersonalDocumento::query()->findOrFail($incidencia->documento_id);

        $this->assertSame('incapacidad.jpg', $documento->archivo_nombre);
        $this->assertSame($personal->id, $documento->personal_id);
        Storage::disk('public')->assertExists($documento->archivo_path);
    }
}
