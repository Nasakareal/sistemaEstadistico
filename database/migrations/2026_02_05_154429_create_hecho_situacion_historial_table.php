<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hecho_situacion_historial', function (Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger('hecho_id');
            $table->string('situacion_anterior', 30)->nullable();
            $table->string('situacion_nueva', 30);
            $table->timestamp('cambio_at')->useCurrent();
            $table->unsignedBigInteger('cambio_by')->nullable();
            $table->text('nota')->nullable();
            $table->timestamps();
            $table->foreign('hecho_id')->references('id')->on('hechos')->cascadeOnDelete();
            $table->foreign('cambio_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['hecho_id', 'cambio_at']);
            $table->index(['situacion_nueva', 'cambio_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hecho_situacion_historial');
    }
};
