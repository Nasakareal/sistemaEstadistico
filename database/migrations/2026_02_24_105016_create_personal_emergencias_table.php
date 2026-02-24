<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_emergencias', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('personal_id');

            $table->string('nombre', 191);
            $table->string('parentesco', 80)->nullable();

            $table->string('telefono', 30);
            $table->string('telefono_2', 30)->nullable();

            $table->string('direccion', 255)->nullable();

            $table->string('observaciones', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('personal_id')
                ->references('id')
                ->on('personals')
                ->onDelete('cascade');

            $table->index(['personal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_emergencias');
    }
};
