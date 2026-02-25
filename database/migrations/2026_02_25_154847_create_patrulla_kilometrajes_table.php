<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrulla_kilometrajes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('patrulla_id');
            $table->date('fecha');
            $table->unsignedInteger('kilometraje_reportado');
            $table->unsignedInteger('kilometros_recorridos')->nullable();

            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->foreign('patrulla_id')
                ->references('id')
                ->on('patrullas')
                ->onDelete('cascade');

            $table->foreign('usuario_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(['patrulla_id', 'fecha']);
            $table->index(['fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrulla_kilometrajes');
    }
};
