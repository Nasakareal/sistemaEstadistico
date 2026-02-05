<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendientes_cortes', function (Blueprint $table) {

            $table->id();
            $table->date('corte_fecha')->unique();
            $table->unsignedBigInteger('generado_by')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->foreign('generado_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendientes_cortes');
    }
};
