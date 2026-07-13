<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('puestas_disposicion_fotos')) {
            Schema::create('puestas_disposicion_fotos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('puesta_disposicion_id')
                    ->constrained('puestas_disposicion')
                    ->cascadeOnDelete();
                $table->string('ruta');
                $table->unsignedSmallInteger('orden')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['puesta_disposicion_id', 'orden']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('puestas_disposicion_fotos');
    }
};
