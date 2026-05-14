<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fomento_cultura_vial_programas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actividad_subcategoria_id');
            $table->string('nombre', 180);
            $table->string('slug', 200);
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('actividad_subcategoria_id', 'fcv_prog_sub_fk')
                ->references('id')
                ->on('actividad_subcategorias')
                ->onDelete('cascade');

            $table->unique(['actividad_subcategoria_id', 'slug'], 'fcv_prog_sub_slug_unique');
            $table->index(['actividad_subcategoria_id', 'activo'], 'fcv_prog_sub_activo_idx');
        });

        Schema::table('fomento_cultura_vial_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('fomento_cultura_vial_detalles', 'fomento_cultura_vial_programa_id')) {
                $table->unsignedBigInteger('fomento_cultura_vial_programa_id')->nullable()->after('actividad_id');
            }

            if (!Schema::hasColumn('fomento_cultura_vial_detalles', 'programa_nombre')) {
                $table->string('programa_nombre', 180)->nullable()->after('sector');
            }
        });

        Schema::table('fomento_cultura_vial_detalles', function (Blueprint $table) {
            $table->foreign('fomento_cultura_vial_programa_id', 'fcv_det_prog_fk')
                ->references('id')
                ->on('fomento_cultura_vial_programas')
                ->onDelete('set null');
        });

        $this->seedProgramas();
    }

    public function down(): void
    {
        Schema::table('fomento_cultura_vial_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('fomento_cultura_vial_detalles', 'fomento_cultura_vial_programa_id')) {
                $table->dropForeign('fcv_det_prog_fk');
                $table->dropColumn('fomento_cultura_vial_programa_id');
            }

            if (Schema::hasColumn('fomento_cultura_vial_detalles', 'programa_nombre')) {
                $table->dropColumn('programa_nombre');
            }
        });

        Schema::dropIfExists('fomento_cultura_vial_programas');
    }

    private function seedProgramas(): void
    {
        $data = [
            'TALLER EDUCACIÓN SEGURIDAD VIAL' => [
                'Taller Educación Vial',
                'Taller de Manejo Defensivo',
                'Taller de Gestion de Emociones en la Conducción',
                'Taller de Violencia de Genero',
                'Taller de movilidad segura en la vía pública',
                'Taller de Ley de Movilidad y Seguridad Vial del Estado de Michoacán',
                'Taller de alcohol y conducción',
                'Taller de proximidad social',
                'Taller de Promotores Escolares',
                'Taller de Seguridad Vial Laboral',
            ],
            'CAMPAÑA EDUCACIÓN SEGURIDAD VIAL' => [
                'Campaña de Sensibilización',
                'Prevención de violencia de género en el transporte y en la vía pública',
                'Infancias seguras en la vía pública',
                'Primero el Peatón',
                'Uso del cinturon de Seguridad',
                'Uso del Casco',
            ],
            'CAPACITACIONES EDUCACIÓN SEGURIDAD VIAL' => [
                'Lic. Seguridad Publica, (En UMSNH)',
                'Capacitaciones para elementos de nuevo ingreso',
                'Actualizacion para elementos de la Coordinación de Seguridad Vial',
            ],
            'MÓDULOS EDUCACIÓN SEGURIDAD VIAL' => [
                'Modulo de Lúdico',
                'Simulacro de hecho de tránsito',
            ],
        ];

        foreach ($data as $subcategoriaNombre => $programas) {
            $subcategoria = DB::table('actividad_subcategorias')
                ->where('slug', Str::slug($subcategoriaNombre))
                ->first(['id']);

            if (!$subcategoria) {
                continue;
            }

            foreach ($programas as $index => $programaNombre) {
                DB::table('fomento_cultura_vial_programas')->updateOrInsert(
                    [
                        'actividad_subcategoria_id' => $subcategoria->id,
                        'slug' => Str::slug($programaNombre),
                    ],
                    [
                        'nombre' => $programaNombre,
                        'orden' => $index + 1,
                        'activo' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
};
