<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conduce_legalidad_personas')) {
            return;
        }

        Schema::table('conduce_legalidad_personas', function (Blueprint $table) {
            if (!Schema::hasColumn('conduce_legalidad_personas', 'nombres')) {
                $table->string('nombres', 120)->nullable()->after('nombre');
            }
            if (!Schema::hasColumn('conduce_legalidad_personas', 'apellido_paterno')) {
                $table->string('apellido_paterno', 100)->nullable()->after('nombres');
            }
            if (!Schema::hasColumn('conduce_legalidad_personas', 'apellido_materno')) {
                $table->string('apellido_materno', 100)->nullable()->after('apellido_paterno');
            }
            if (!Schema::hasColumn('conduce_legalidad_personas', 'edad_texto')) {
                $table->string('edad_texto', 40)->nullable()->after('edad');
            }
            if (!Schema::hasColumn('conduce_legalidad_personas', 'estado_civil')) {
                $table->string('estado_civil', 30)->nullable()->after('edad_texto');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conduce_legalidad_personas')) {
            return;
        }

        Schema::table('conduce_legalidad_personas', function (Blueprint $table) {
            foreach (['estado_civil', 'edad_texto', 'apellido_materno', 'apellido_paterno', 'nombres'] as $column) {
                if (Schema::hasColumn('conduce_legalidad_personas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
