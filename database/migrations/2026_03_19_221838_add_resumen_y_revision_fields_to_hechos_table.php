<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hechos', function (Blueprint $table) {
            /**
             * RESUMEN EJECUTIVO
             */
            $table->boolean('es_relevante')
                ->default(false)
                ->after('situacion');

            $table->unsignedBigInteger('marcado_relevante_por')
                ->nullable()
                ->after('es_relevante');

            $table->timestamp('marcado_relevante_at')
                ->nullable()
                ->after('marcado_relevante_por');

            /**
             * REVISION INTERNA
             */
            $table->string('estado_revision')
                ->default('pendiente')
                ->after('marcado_relevante_at');
                // pendiente | aprobado | rechazado

            $table->unsignedBigInteger('revisado_por')
                ->nullable()
                ->after('estado_revision');

            $table->timestamp('revisado_at')
                ->nullable()
                ->after('revisado_por');

            $table->text('observacion_revision')
                ->nullable()
                ->after('revisado_at');

            /**
             * FOREIGN KEYS
             */
            $table->foreign('marcado_relevante_por')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('revisado_por')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            /**
             * INDICES
             */
            $table->index(['es_relevante'], 'hechos_es_relevante_idx');
            $table->index(['estado_revision'], 'hechos_estado_revision_idx');
            $table->index(['unidad_org_id', 'estado_revision'], 'hechos_unidad_revision_idx');
            $table->index(['fecha', 'es_relevante'], 'hechos_fecha_relevante_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->dropIndex('hechos_es_relevante_idx');
            $table->dropIndex('hechos_estado_revision_idx');
            $table->dropIndex('hechos_unidad_revision_idx');
            $table->dropIndex('hechos_fecha_relevante_idx');

            $table->dropForeign(['marcado_relevante_por']);
            $table->dropForeign(['revisado_por']);

            $table->dropColumn([
                'es_relevante',
                'marcado_relevante_por',
                'marcado_relevante_at',
                'estado_revision',
                'revisado_por',
                'revisado_at',
                'observacion_revision',
            ]);
        });
    }
};
