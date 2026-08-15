<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160)->nullable();
            $table->longText('content')->nullable();
            $table->string('color', 24)->default('neutral');
            $table->json('highlights')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_pinned', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notes');
    }
};
