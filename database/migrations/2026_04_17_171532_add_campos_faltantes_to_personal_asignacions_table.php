<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_asignacions', function (Blueprint $table) {
            if (!Schema::hasColumn('personal_asignacions', 'personal_id')) {
                $table->unsignedBigInteger('personal_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('personal_asignacions', 'comisionado_a')) {
                $table->string('comisionado_a')->nullable()->after('personal_id');
            }

            if (!Schema::hasColumn('personal_asignacions', 'ubicacion_interna')) {
                $table->string('ubicacion_interna')->nullable()->after('comisionado_a');
            }

            if (!Schema::hasColumn('personal_asignacions', 'municipio_localidad_servicio')) {
                $table->string('municipio_localidad_servicio')->nullable()->after('ubicacion_interna');
            }

            if (!Schema::hasColumn('personal_asignacions', 'funciones')) {
                $table->string('funciones')->nullable()->after('municipio_localidad_servicio');
            }

            if (!Schema::hasColumn('personal_asignacions', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('funciones');
            }

            if (!Schema::hasColumn('personal_asignacions', 'actividades')) {
                $table->text('actividades')->nullable()->after('observaciones');
            }

            if (!Schema::hasColumn('personal_asignacions', 'horario')) {
                $table->string('horario')->nullable()->after('actividades');
            }

            if (!Schema::hasColumn('personal_asignacions', 'tipo_contratacion')) {
                $table->enum('tipo_contratacion', ['BASE', 'INTERINATO'])->nullable()->after('horario');
            }

            if (!Schema::hasColumn('personal_asignacions', 'dpc')) {
                $table->string('dpc')->nullable()->after('tipo_contratacion');
            }

            if (!Schema::hasColumn('personal_asignacions', 'oficina_pago')) {
                $table->string('oficina_pago')->nullable()->after('dpc');
            }
        });

        $foreignExists = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'personal_asignacions'
              AND COLUMN_NAME = 'personal_id'
              AND REFERENCED_TABLE_NAME = 'personals'
            LIMIT 1
        ");

        if (empty($foreignExists) && Schema::hasColumn('personal_asignacions', 'personal_id')) {
            Schema::table('personal_asignacions', function (Blueprint $table) {
                $table->foreign('personal_id')->references('id')->on('personals')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        $foreignExists = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'personal_asignacions'
              AND COLUMN_NAME = 'personal_id'
              AND REFERENCED_TABLE_NAME = 'personals'
            LIMIT 1
        ");

        Schema::table('personal_asignacions', function (Blueprint $table) use ($foreignExists) {
            if (!empty($foreignExists)) {
                $table->dropForeign(['personal_id']);
            }

            $columns = [];

            if (Schema::hasColumn('personal_asignacions', 'oficina_pago')) {
                $columns[] = 'oficina_pago';
            }

            if (Schema::hasColumn('personal_asignacions', 'dpc')) {
                $columns[] = 'dpc';
            }

            if (Schema::hasColumn('personal_asignacions', 'tipo_contratacion')) {
                $columns[] = 'tipo_contratacion';
            }

            if (Schema::hasColumn('personal_asignacions', 'horario')) {
                $columns[] = 'horario';
            }

            if (Schema::hasColumn('personal_asignacions', 'actividades')) {
                $columns[] = 'actividades';
            }

            if (Schema::hasColumn('personal_asignacions', 'observaciones')) {
                $columns[] = 'observaciones';
            }

            if (Schema::hasColumn('personal_asignacions', 'funciones')) {
                $columns[] = 'funciones';
            }

            if (Schema::hasColumn('personal_asignacions', 'municipio_localidad_servicio')) {
                $columns[] = 'municipio_localidad_servicio';
            }

            if (Schema::hasColumn('personal_asignacions', 'ubicacion_interna')) {
                $columns[] = 'ubicacion_interna';
            }

            if (Schema::hasColumn('personal_asignacions', 'comisionado_a')) {
                $columns[] = 'comisionado_a';
            }

            if (Schema::hasColumn('personal_asignacions', 'personal_id')) {
                $columns[] = 'personal_id';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
