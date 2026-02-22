<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_predictions', function (Blueprint $table) {
            $table->id();

            $table->enum('zona_tipo', ['COLONIA','TRAMO','CELL'])->index();
            $table->unsignedBigInteger('zona_id')->index();

            $table->enum('ventana', ['6H','12H','24H','48H'])->index();

            $table->decimal('probabilidad_0_1', 5, 4)->index();
            $table->unsignedTinyInteger('score_0_100')->index();
            $table->enum('nivel', ['BAJO','MEDIO','ALTO'])->index();

            $table->foreignId('ml_model_id')
                ->nullable()
                ->constrained('ml_models')
                ->nullOnDelete();

            $table->timestamp('generado_at')->index();

            $table->json('explicacion_json')->nullable();

            $table->timestamps();

            $table->index(['zona_tipo','zona_id','ventana','generado_at'], 'idx_ml_predictions_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_predictions');
    }
};
