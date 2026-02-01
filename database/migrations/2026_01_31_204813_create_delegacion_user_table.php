<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delegacion_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('delegacion_id')
                ->constrained('delegaciones')
                ->cascadeOnDelete();

            $table->boolean('principal')->default(false);

            $table->timestamps();

            $table->unique(['user_id', 'delegacion_id']);
            $table->index(['delegacion_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegacion_user');
    }
};
