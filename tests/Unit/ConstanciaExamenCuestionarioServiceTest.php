<?php

namespace Tests\Unit;

use App\Services\ConstanciaExamenCuestionarioService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConstanciaExamenCuestionarioServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('constancia_preguntas', function (Blueprint $table) {
            $table->id();
            $table->string('pregunta');
            $table->string('tipo_licencia');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('constancia_respuestas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pregunta_id');
            $table->string('respuesta');
            $table->boolean('es_correcta')->default(false);
            $table->timestamps();
        });

        foreach (range(1, 25) as $numero) {
            $this->crearPregunta('AUTOMOVILISTA', 'Pregunta automóvil ' . $numero);
        }

        foreach (range(1, 5) as $numero) {
            $this->crearPregunta('GENERAL', 'Pregunta general ' . $numero);
        }

        DB::table('constancia_preguntas')->insert([
            'pregunta' => 'Pregunta inactiva',
            'tipo_licencia' => 'AUTOMOVILISTA',
            'activo' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_genera_veinte_preguntas_estables_por_folio(): void
    {
        $service = app(ConstanciaExamenCuestionarioService::class);

        $primera = $service->generar('AUTOMOVILISTA', 'token-del-examen');
        $segunda = $service->generar('AUTOMOVILISTA', 'token-del-examen');
        $otroExamen = $service->generar('AUTOMOVILISTA', 'otro-token');

        $this->assertCount(20, $primera);
        $this->assertSame($primera->pluck('id')->all(), $segunda->pluck('id')->all());
        $this->assertNotSame($primera->pluck('id')->all(), $otroExamen->pluck('id')->all());
        $this->assertFalse($primera->contains('pregunta', 'Pregunta inactiva'));
        $this->assertTrue($primera->every(fn ($pregunta) => $pregunta->respuestas->count() === 3));
    }

    private function crearPregunta(string $tipoLicencia, string $texto): void
    {
        $preguntaId = DB::table('constancia_preguntas')->insertGetId([
            'pregunta' => $texto,
            'tipo_licencia' => $tipoLicencia,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['Respuesta A', 'Respuesta B', 'Respuesta C'] as $indice => $respuesta) {
            DB::table('constancia_respuestas')->insert([
                'pregunta_id' => $preguntaId,
                'respuesta' => $respuesta,
                'es_correcta' => $indice === 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
