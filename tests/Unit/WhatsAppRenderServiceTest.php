<?php

namespace Tests\Unit;

use App\Models\DocumentoTipo;
use App\Models\Personal;
use App\Models\PersonalDocumento;
use App\Services\WhatsApp\WhatsAppRenderService;
use Carbon\Carbon;
use Tests\TestCase;

class WhatsAppRenderServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_render_detalle_personal_incluye_documentos_descargables(): void
    {
        Carbon::setTestNow('2026-07-01 10:00:00');

        $tipo = new DocumentoTipo([
            'nombre' => 'INE',
        ]);

        $documento = new PersonalDocumento([
            'numero' => 'ABC123',
            'fecha_emision' => '2026-06-15',
            'archivo_path' => 'personals/5/documentos/ine.pdf',
            'archivo_nombre' => 'ine-frente.pdf',
            'activo' => true,
        ]);
        $documento->id = 25;
        $documento->setRelation('documentoTipo', $tipo);

        $personal = new Personal([
            'unidad_id' => 3,
            'nombre' => 'JUAN',
            'ap_paterno' => 'PEREZ',
            'ap_materno' => 'LOPEZ',
            'estatus' => 'ACTIVO',
        ]);
        $personal->id = 5;

        foreach (['unidad', 'turno', 'patrulla', 'user', 'fotoPrincipal'] as $relation) {
            $personal->setRelation($relation, null);
        }

        foreach (['fotos', 'asignaciones'] as $relation) {
            $personal->setRelation($relation, collect());
        }

        $personal->setRelation('documentos', collect([$documento]));

        $packet = (new WhatsAppRenderService())->renderDetallePersonal($personal);

        $this->assertStringContainsString('Documentos subidos:', $packet['text']);
        $this->assertStringContainsString('INE - Archivo principal', $packet['text']);
        $this->assertStringContainsString('Folio ABC123', $packet['text']);
        $this->assertStringContainsString('ine-frente.pdf', $packet['text']);
        $this->assertStringContainsString('/personal-documentos/25/general/archivo-temporal?', $packet['text']);
        $this->assertStringContainsString('signature=', $packet['text']);
    }
}
