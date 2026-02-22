<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_model_runs', function (Blueprint $table) {
            $table->id();

            $table->string('algoritmo', 120)->index();
            $table->json('parametros_json')->nullable();

            $table->string('feature_set_version', 50)->index();

            $table->date('train_inicio')->nullable()->index();
            $table->date('train_fin')->nullable()->index();

            $table->json('metrics_json')->nullable();
            $table->enum('status', ['CREADO','ENTRENANDO','OK','ERROR'])->default('CREADO')->index();
            $table->longText('error')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_model_runs');
    }
};
