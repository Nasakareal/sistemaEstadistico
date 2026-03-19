<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operativo_consolidados', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_uuid')->nullable()->unique();

            $table->unsignedBigInteger('operativo_id')->nullable();
            $table->date('fecha');
            $table->unsignedBigInteger('unidad_org_id')->nullable();
            $table->unsignedBigInteger('delegacion_id')->nullable();
            $table->unsignedBigInteger('destacamento_id')->nullable();

            $table->string('destacamento_nombre_snapshot', 255)->nullable();
            $table->string('asunto', 255)->default('CONSOLIDADO DE NOVEDADES DE ACTIVIDADES DIARIAS');
            $table->text('descripcion_general')->nullable();
            $table->text('municipios_tramos')->nullable();

            $table->unsignedInteger('total_dispositivos')->default(0);
            $table->unsignedInteger('total_vehiculos_inspeccionados')->default(0);
            $table->unsignedInteger('total_personas_inspeccionadas')->default(0);
            $table->unsignedInteger('total_vehiculos_impactados')->default(0);
            $table->unsignedInteger('total_personas_impactadas')->default(0);
            $table->unsignedInteger('total_estado_fuerza')->default(0);
            $table->decimal('total_kilometros_recorridos', 10, 2)->default(0);

            $table->unsignedInteger('total_acompanamientos')->default(0);
            $table->unsignedInteger('total_abanderamientos')->default(0);
            $table->unsignedInteger('total_auxilios_viales')->default(0);

            $table->unsignedInteger('total_prox_empresas')->default(0);
            $table->unsignedInteger('total_prox_tiendas_conveniencia')->default(0);
            $table->unsignedInteger('total_prox_escuelas')->default(0);
            $table->unsignedInteger('total_prox_hospitales')->default(0);

            $table->unsignedInteger('total_antecedentes_personas')->default(0);
            $table->unsignedInteger('total_antecedentes_vehiculos')->default(0);
            $table->unsignedInteger('total_antecedentes_motos')->default(0);
            $table->unsignedInteger('total_antecedentes_camiones')->default(0);

            $table->unsignedInteger('total_puestas_disposicion')->default(0);
            $table->unsignedInteger('total_vehiculos_recuperados')->default(0);
            $table->unsignedInteger('total_armas_aseguradas')->default(0);
            $table->unsignedInteger('total_mercancia_recuperada')->default(0);
            $table->unsignedInteger('total_decomiso_drogas')->default(0);

            $table->text('crps_consolidados')->nullable();
            $table->longText('texto_generado')->nullable();
            $table->json('json_resumen')->nullable();

            $table->string('estatus', 30)->default('abierto');
            $table->unsignedBigInteger('cerrado_por')->nullable();
            $table->timestamp('cerrado_at')->nullable();

            $table->boolean('compartido_whatsapp')->default(false);
            $table->timestamp('compartido_whatsapp_at')->nullable();

            $table->string('sync_status', 20)->default('synced');
            $table->text('sync_error')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index(['fecha', 'unidad_org_id'], 'opc_fecha_unidad_idx');
            $table->index(['delegacion_id', 'destacamento_id'], 'opc_delegacion_destacamento_idx');
            $table->index(['operativo_id', 'fecha'], 'opc_operativo_fecha_idx');
            $table->index(['estatus'], 'opc_estatus_idx');
            $table->index(['sync_status'], 'opc_sync_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operativo_consolidados');
    }
};
