<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('croquis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hecho_id')->constrained('hechos')->cascadeOnDelete();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->string('titulo')->nullable();
            $table->string('plantilla')->nullable();
            $table->string('orientacion')->nullable();
            $table->decimal('escala', 10, 2)->nullable();
            $table->json('json_dibujo')->nullable();
            $table->string('imagen_preview')->nullable();
            $table->string('pdf_path')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('hecho_id');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('croquis');
    }
};
