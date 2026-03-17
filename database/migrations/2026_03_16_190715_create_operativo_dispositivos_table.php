<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperativoDispositivosTable extends Migration
{
    public function up()
    {
        Schema::create('operativo_dispositivos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('operativo_id')->index();
            $table->unsignedBigInteger('operativo_dispositivo_catalogo_id')->index();

            $table->date('fecha')->index();
            $table->time('hora')->nullable();

            $table->unsignedBigInteger('unidad_org_id')->index();
            $table->unsignedBigInteger('delegacion_id')->nullable()->index();
            $table->unsignedBigInteger('destacamento_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->string('lugar')->nullable();
            $table->text('descripcion')->nullable();

            $table->unsignedInteger('cantidad')->default(1);

            $table->unsignedInteger('vehiculos_inspeccionados')->default(0);
            $table->unsignedInteger('personas_inspeccionadas')->default(0);

            $table->unsignedInteger('vehiculos_impactados')->default(0);
            $table->unsignedInteger('personas_impactadas')->default(0);

            $table->unsignedInteger('estado_fuerza_participante')->default(0);
            $table->decimal('kilometros_recorridos', 10, 2)->default(0);

            $table->text('crps_participantes')->nullable();

            $table->unsignedInteger('acompanamientos')->default(0);
            $table->unsignedInteger('abanderamientos')->default(0);
            $table->unsignedInteger('auxilios_viales')->default(0);

            $table->unsignedInteger('prox_empresas')->default(0);
            $table->unsignedInteger('prox_tiendas_conveniencia')->default(0);
            $table->unsignedInteger('prox_escuelas')->default(0);
            $table->unsignedInteger('prox_hospitales')->default(0);

            $table->unsignedInteger('antecedentes_personas')->default(0);
            $table->unsignedInteger('antecedentes_vehiculos')->default(0);
            $table->unsignedInteger('antecedentes_motos')->default(0);
            $table->unsignedInteger('antecedentes_camiones')->default(0);

            $table->unsignedInteger('puestas_disposicion')->default(0);
            $table->unsignedInteger('vehiculos_recuperados')->default(0);
            $table->unsignedInteger('armas_aseguradas')->default(0);
            $table->unsignedInteger('mercancia_recuperada')->default(0);
            $table->unsignedInteger('decomiso_drogas')->default(0);

            $table->text('observaciones')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            $table->timestamps();

            $table->foreign('operativo_id')
                ->references('id')
                ->on('operativos')
                ->onDelete('cascade');

            $table->foreign('operativo_dispositivo_catalogo_id', 'operativo_dispositivos_catalogo_fk')
                ->references('id')
                ->on('operativo_dispositivo_catalogos')
                ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('operativo_dispositivos');
    }
}
