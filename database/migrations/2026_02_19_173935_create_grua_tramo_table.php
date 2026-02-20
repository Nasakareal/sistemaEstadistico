<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grua_tramo', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('grua_id');
            $table->unsignedBigInteger('tramo_id');

            $table->date('desde')->nullable();
            $table->date('hasta')->nullable();

            $table->unsignedSmallInteger('prioridad')->default(100);
            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->foreign('grua_id')->references('id')->on('gruas')->onDelete('cascade');
            $table->foreign('tramo_id')->references('id')->on('tramos')->onDelete('cascade');

            $table->unique(['grua_id', 'tramo_id']);
            $table->index(['tramo_id', 'activo', 'prioridad']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grua_tramo');
    }
};
