<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operativo_dispositivo_persona', function (Blueprint $table) {
            if (!Schema::hasColumn('operativo_dispositivo_persona', 'domicilio')) {
                $table->string('domicilio')->nullable();
            }
            if (!Schema::hasColumn('operativo_dispositivo_persona', 'sexo')) {
                $table->string('sexo', 20)->nullable();
            }
            if (!Schema::hasColumn('operativo_dispositivo_persona', 'ocupacion')) {
                $table->string('ocupacion')->nullable();
            }
            if (!Schema::hasColumn('operativo_dispositivo_persona', 'edad')) {
                $table->unsignedTinyInteger('edad')->nullable();
            }
            if (!Schema::hasColumn('operativo_dispositivo_persona', 'tipo_licencia')) {
                $table->string('tipo_licencia', 50)->nullable();
            }
            if (!Schema::hasColumn('operativo_dispositivo_persona', 'estado_licencia')) {
                $table->string('estado_licencia', 100)->nullable();
            }
            if (!Schema::hasColumn('operativo_dispositivo_persona', 'vigencia_licencia')) {
                $table->date('vigencia_licencia')->nullable();
            }
            if (!Schema::hasColumn('operativo_dispositivo_persona', 'numero_licencia')) {
                $table->string('numero_licencia', 50)->nullable();
            }
            if (!Schema::hasColumn('operativo_dispositivo_persona', 'permanente')) {
                $table->boolean('permanente')->default(false);
            }
            if (!Schema::hasColumn('operativo_dispositivo_persona', 'cinturon')) {
                $table->boolean('cinturon')->default(false);
            }
            if (!Schema::hasColumn('operativo_dispositivo_persona', 'antecedentes')) {
                $table->boolean('antecedentes')->default(false);
            }
            if (!Schema::hasColumn('operativo_dispositivo_persona', 'certificado_lesiones')) {
                $table->boolean('certificado_lesiones')->default(false);
            }
            if (!Schema::hasColumn('operativo_dispositivo_persona', 'certificado_alcoholemia')) {
                $table->boolean('certificado_alcoholemia')->default(false);
            }
            if (!Schema::hasColumn('operativo_dispositivo_persona', 'aliento_etilico')) {
                $table->boolean('aliento_etilico')->default(false);
            }
        });
    }

    public function down(): void
    {
        $columns = array_values(array_filter([
            'domicilio',
            'sexo',
            'ocupacion',
            'edad',
            'tipo_licencia',
            'estado_licencia',
            'vigencia_licencia',
            'numero_licencia',
            'permanente',
            'cinturon',
            'antecedentes',
            'certificado_lesiones',
            'certificado_alcoholemia',
            'aliento_etilico',
        ], fn ($column) => Schema::hasColumn('operativo_dispositivo_persona', $column)));

        if (!empty($columns)) {
            Schema::table('operativo_dispositivo_persona', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};