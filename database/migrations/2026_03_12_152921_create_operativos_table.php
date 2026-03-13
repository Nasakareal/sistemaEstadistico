<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operativos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('operativo_catalogo_id')->constrained('operativo_catalogos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('unidad_org_id')->nullable()->constrained('unidades')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('delegacion_id')->nullable()->constrained('delegaciones')->nullOnDelete()->cascadeOnUpdate();

            $table->string('lugar')->nullable();
            $table->text('descripcion')->nullable();

            $table->unsignedInteger('dispositivos_realizados')->default(0);
            $table->unsignedInteger('vehiculos_inspeccionados')->default(0);
            $table->unsignedInteger('personas_inspeccionadas')->default(0);
            $table->unsignedInteger('vehiculos_impactados')->default(0);
            $table->unsignedInteger('personas_impactadas')->default(0);

            $table->unsignedInteger('antecedentes_personas')->default(0);
            $table->unsignedInteger('antecedentes_vehiculos')->default(0);
            $table->unsignedInteger('antecedentes_motos')->default(0);
            $table->unsignedInteger('antecedentes_camiones')->default(0);

            $table->unsignedInteger('estado_fuerza_participante')->default(0);
            $table->decimal('kilometros_recorridos', 10, 2)->default(0);

            $table->unsignedInteger('acompanamientos')->default(0);
            $table->unsignedInteger('abanderamientos')->default(0);
            $table->unsignedInteger('auxilios_viales')->default(0);

            $table->unsignedInteger('puestas_disposicion')->default(0);
            $table->unsignedInteger('vehiculos_recuperados')->default(0);
            $table->unsignedInteger('armas_aseguradas')->default(0);
            $table->unsignedInteger('mercancia_recuperada')->default(0);
            $table->unsignedInteger('decomiso_drogas')->default(0);

            $table->text('crps_participantes')->nullable();
            $table->text('observaciones')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();

            $table->timestamps();

            $table->index('fecha');
            $table->index(['fecha', 'unidad_org_id']);
            $table->index(['fecha', 'delegacion_id']);
            $table->index(['unidad_org_id', 'operativo_catalogo_id']);
            $table->index(['delegacion_id', 'operativo_catalogo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operativos');
    }
};
