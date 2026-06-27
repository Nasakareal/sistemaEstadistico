<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conduce_legalidad_fotos') || !Schema::hasTable('conduce_legalidad_capturas')) {
            return;
        }

        Schema::create('conduce_legalidad_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('captura_id')->constrained('conduce_legalidad_capturas')->cascadeOnDelete();
            $table->string('foto_path');
            $table->string('foto_thumbnail_path')->nullable();
            $table->string('foto_nombre_original')->nullable();
            $table->string('foto_hash', 191)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('captura_id');
            $table->index(['captura_id', 'orden']);
            $table->index('foto_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conduce_legalidad_fotos');
    }
};
