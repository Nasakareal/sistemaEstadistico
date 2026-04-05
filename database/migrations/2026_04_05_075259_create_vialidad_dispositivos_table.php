<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVialidadDispositivosTable extends Migration
{
    public function up()
    {
        Schema::create('vialidad_dispositivos', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->string('sync_status')->nullable();
            $table->text('sync_error')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->unsignedBigInteger('vialidad_dispositivo_catalogo_id');
            $table->unsignedBigInteger('unidad_id');
            $table->unsignedBigInteger('delegacion_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->string('asunto');
            $table->date('fecha');
            $table->time('hora');

            $table->string('municipio')->nullable();
            $table->string('lugar')->nullable();
            $table->string('evento')->nullable();

            $table->text('objetivo')->nullable();
            $table->longText('descripcion')->nullable();
            $table->longText('narrativa')->nullable();
            $table->longText('acciones_realizadas')->nullable();
            $table->longText('observaciones')->nullable();

            $table->unsignedInteger('elementos')->default(0);
            $table->unsignedInteger('crp')->default(0);
            $table->unsignedInteger('motopatrullas')->default(0);
            $table->unsignedInteger('fenix')->default(0);
            $table->unsignedInteger('unidades_motorizadas')->default(0);
            $table->unsignedInteger('patrullas')->default(0);
            $table->unsignedInteger('gruas')->default(0);
            $table->unsignedInteger('otros_apoyos')->default(0);

            $table->string('supervision')->nullable();
            $table->string('responsable_nombre')->nullable();
            $table->string('responsable_cargo')->nullable();

            $table->boolean('revisado')->default(false);
            $table->unsignedBigInteger('revisado_por')->nullable();
            $table->timestamp('revisado_en')->nullable();

            $table->timestamps();

            $table->foreign('vialidad_dispositivo_catalogo_id', 'fk_vd_catalogo')
                ->references('id')
                ->on('vialidad_dispositivo_catalogos')
                ->onDelete('cascade');

            $table->foreign('unidad_id', 'fk_vd_unidad')
                ->references('id')
                ->on('unidades')
                ->onDelete('cascade');

            $table->foreign('delegacion_id', 'fk_vd_delegacion')
                ->references('id')
                ->on('delegaciones')
                ->nullOnDelete();

            $table->foreign('user_id', 'fk_vd_user')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('created_by', 'fk_vd_created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by', 'fk_vd_updated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('revisado_por', 'fk_vd_revisado_por')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['unidad_id', 'fecha'], 'idx_vd_unidad_fecha');
            $table->index(['delegacion_id', 'fecha'], 'idx_vd_delegacion_fecha');
            $table->index(['vialidad_dispositivo_catalogo_id', 'fecha'], 'idx_vd_catalogo_fecha');
            $table->index('asunto', 'idx_vd_asunto');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vialidad_dispositivos');
    }
}
