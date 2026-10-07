<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('puestas_disposicion', function (Blueprint $table) {
            $table->string('fuente_importacion', 100)->nullable()->after('archivo_uso_fuerza');
            $table->unsignedInteger('secuencia_origen')->nullable()->after('fuente_importacion');
            $table->string('folio_origen', 100)->nullable()->after('secuencia_origen');
            $table->string('numero_origen', 50)->nullable()->after('folio_origen');
            $table->text('descripcion_origen')->nullable()->after('numero_origen');
            $table->text('personal_participante')->nullable()->after('descripcion_origen');
            $table->text('detenidos_descripcion')->nullable()->after('personal_participante');
            $table->text('rnd')->nullable()->after('detenidos_descripcion');
            $table->unsignedSmallInteger('numero_faltas_administrativas')->nullable()->after('rnd');
            $table->unsignedSmallInteger('numero_detenidos')->nullable()->after('numero_faltas_administrativas');
            $table->unsignedSmallInteger('numero_aseguramientos')->nullable()->after('numero_detenidos');
            $table->unsignedSmallInteger('numero_menores')->nullable()->after('numero_aseguramientos');
            $table->string('sexo_resumen', 30)->nullable()->after('numero_menores');

            $table->unique(
                ['fuente_importacion', 'secuencia_origen'],
                'uniq_puestas_fuente_secuencia'
            );
            $table->index('folio_origen', 'idx_puestas_folio_origen');
        });
    }

    public function down(): void
    {
        Schema::table('puestas_disposicion', function (Blueprint $table) {
            $table->dropUnique('uniq_puestas_fuente_secuencia');
            $table->dropIndex('idx_puestas_folio_origen');
            $table->dropColumn([
                'fuente_importacion',
                'secuencia_origen',
                'folio_origen',
                'numero_origen',
                'descripcion_origen',
                'personal_participante',
                'detenidos_descripcion',
                'rnd',
                'numero_faltas_administrativas',
                'numero_detenidos',
                'numero_aseguramientos',
                'numero_menores',
                'sexo_resumen',
            ]);
        });
    }
};
