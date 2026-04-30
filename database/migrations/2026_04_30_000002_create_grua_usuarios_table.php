<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGruaUsuariosTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('grua_usuarios')) {
            return;
        }

        Schema::create('grua_usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grua_id')->constrained('gruas')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password');
            $table->boolean('activo')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('grua_usuarios');
    }
}
