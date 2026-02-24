<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_domicilios', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('personal_id');

            $table->string('calle', 191);
            $table->string('numero_ext', 30);
            $table->string('numero_int', 30)->nullable();

            $table->string('colonia', 191);
            $table->string('municipio', 191);
            $table->string('estado', 191);

            $table->string('cp', 10);

            $table->string('referencias', 255)->nullable();

            $table->boolean('es_actual')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('personal_id')
                ->references('id')
                ->on('personals')
                ->onDelete('cascade');

            $table->index(['personal_id']);
            $table->index(['personal_id', 'es_actual']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_domicilios');
    }
};
