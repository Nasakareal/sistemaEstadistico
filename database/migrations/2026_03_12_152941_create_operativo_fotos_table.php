<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operativo_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operativo_id')->constrained('operativos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('foto_path');
            $table->string('foto_nombre_original')->nullable();
            $table->string('foto_hash', 191)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index('operativo_id');
            $table->index('foto_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operativo_fotos');
    }
};
