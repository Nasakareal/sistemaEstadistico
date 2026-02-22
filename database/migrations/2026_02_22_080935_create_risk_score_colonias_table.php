<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_score_colonias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('geo_colonia_id')
                ->constrained('geo_colonias')
                ->cascadeOnDelete();

            $table->date('periodo_inicio')->index();
            $table->date('periodo_fin')->index();

            $table->unsignedTinyInteger('score_0_100')->index();

            $table->enum('nivel', ['BAJO', 'MEDIO', 'ALTO'])->index();

            $table->decimal('tendencia_pct', 6, 2)->nullable();

            $table->unsignedInteger('hechos_count')->default(0);

            $table->json('resumen_json')->nullable();

            $table->timestamps();

            $table->unique(
                ['geo_colonia_id', 'periodo_inicio', 'periodo_fin'],
                'uniq_risk_score_colonias_periodo'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_score_colonias');
    }
};
