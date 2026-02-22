<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_models', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ml_model_run_id')
                ->constrained('ml_model_runs')
                ->cascadeOnDelete();

            $table->string('nombre', 190)->index();
            $table->string('version', 50)->index();

            $table->string('endpoint', 255)->nullable();
            $table->timestamp('deployed_at')->nullable();

            $table->boolean('activo')->default(false)->index();

            $table->timestamps();

            $table->unique(['nombre', 'version'], 'uniq_ml_models_nombre_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_models');
    }
};
