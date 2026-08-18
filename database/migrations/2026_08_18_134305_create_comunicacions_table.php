<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComunicacionsTable extends Migration
{
    public function up()
    {
        Schema::create('comunicaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('remitente_user_id')
                ->constrained('users')
                ->onDelete('restrict');

            $table->string('tipo', 30);
            $table->string('asunto', 180);
            $table->text('contenido');

            $table->string('alcance', 30);

            $table->foreignId('unidad_id')
                ->nullable()
                ->constrained('unidades')
                ->onDelete('set null');

            $table->foreignId('turno_id')
                ->nullable()
                ->constrained('turnos')
                ->onDelete('set null');

            $table->foreignId('role_id')
                ->nullable()
                ->constrained('roles')
                ->onDelete('set null');

            $table->foreignId('destinatario_user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->boolean('requiere_enterado')->default(false);

            $table->timestamp('enviado_at')->nullable();

            $table->timestamps();

            $table->index('tipo');
            $table->index('alcance');
            $table->index('enviado_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('comunicaciones');
    }
}
