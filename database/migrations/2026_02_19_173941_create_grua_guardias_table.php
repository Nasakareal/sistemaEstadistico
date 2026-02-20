<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grua_guardias', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('grua_id');

            $table->date('week_start');
            $table->date('week_end');

            $table->boolean('activo')->default(true);
            $table->string('notas')->nullable();

            $table->timestamps();

            $table->foreign('grua_id')->references('id')->on('gruas')->onDelete('cascade');
            $table->unique(['week_start', 'week_end']);
            $table->index(['week_start', 'week_end', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grua_guardias');
    }
};
