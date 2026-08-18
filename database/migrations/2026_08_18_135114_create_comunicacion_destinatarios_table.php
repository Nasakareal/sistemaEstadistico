<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComunicacionDestinatariosTable extends Migration
{
    public function up()
    {
        Schema::create('comunicacion_destinatarios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('comunicacion_id')
                ->constrained('comunicaciones')
                ->onDelete('cascade');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->timestamp('leido_at')->nullable();
            $table->timestamp('enterado_at')->nullable();

            $table->timestamps();

            $table->unique([
                'comunicacion_id',
                'user_id'
            ]);

            $table->index('leido_at');
            $table->index('enterado_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('comunicacion_destinatarios');
    }
}
