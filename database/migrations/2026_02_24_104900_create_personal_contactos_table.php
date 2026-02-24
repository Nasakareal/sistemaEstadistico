<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_contactos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('personal_id');

            $table->string('tipo', 30);
            $table->string('valor', 191);

            $table->boolean('es_principal')->default(false);

            $table->string('observaciones', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('personal_id')
                ->references('id')
                ->on('personals')
                ->onDelete('cascade');

            $table->index(['personal_id', 'tipo']);
            $table->index(['personal_id', 'es_principal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_contactos');
    }
};
