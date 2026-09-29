<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLicenciasConducirTable extends Migration
{
    public function up()
    {
        Schema::create('licencias_conducir', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('constancia_id')->unique();
            $table->unsignedBigInteger('examen_solicitud_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('numero', 40)->unique();
            $table->string('qr_token', 100)->unique();
            $table->string('curp', 18)->index();
            $table->string('apellido_paterno', 100);
            $table->string('apellido_materno', 100)->nullable();
            $table->string('nombres', 150);
            $table->date('fecha_nacimiento');
            $table->date('fecha_expedicion');
            $table->date('fecha_vencimiento');
            $table->date('fecha_antiguedad')->nullable();
            $table->string('tipo_licencia', 40);
            $table->string('genero', 20);
            $table->string('tipo_sangre', 10);
            $table->boolean('donador_organos')->default(false);
            $table->string('restricciones', 255)->nullable();
            $table->string('oficina_emisora', 150);
            $table->text('vehiculos_autorizados');
            $table->string('foto_path');
            $table->string('estatus', 20)->default('VIGENTE')->index();
            $table->timestamps();

            $table->foreign('constancia_id')
                ->references('id')->on('constancias_manejo')
                ->onDelete('restrict');
            $table->foreign('examen_solicitud_id')
                ->references('id')->on('constancia_examen_solicitudes')
                ->onDelete('set null');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('licencias_conducir');
    }
}
