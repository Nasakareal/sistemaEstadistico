<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_score_cells', function (Blueprint $table) {
            $table->id();

            $table->foreignId('geo_grid_cell_id')
                ->constrained('geo_grid_cells')
                ->cascadeOnDelete();

            $table->enum('ventana', ['6H','12H','24H','48H'])->index();

            $table->unsignedTinyInteger('score_0_100')->index();
            $table->decimal('probabilidad_0_1', 5, 4)->nullable()->index();
            $table->enum('nivel', ['BAJO','MEDIO','ALTO'])->index();

            $table->unsignedBigInteger('ml_model_id')->nullable()->index();

            $table->timestamp('generado_at')->index();

            $table->json('resumen_json')->nullable();

            $table->timestamps();

            $table->index(['geo_grid_cell_id', 'ventana', 'generado_at'], 'idx_risk_score_cells_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_score_cells');
    }
};
