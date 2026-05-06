<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatrullaFotosTable extends Migration
{
    public function up()
    {
        Schema::create('patrulla_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrulla_id')->constrained('patrullas')->onDelete('cascade');
            $table->string('foto');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('patrulla_fotos');
    }
}
