<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('licencia_punto_cursos')) {
            return;
        }

        Schema::table('licencia_punto_cursos', function (Blueprint $table) {
            if (!Schema::hasColumn('licencia_punto_cursos', 'clase_en_vivo')) {
                $table->boolean('clase_en_vivo')->default(false)->after('puntos_recuperacion');
            }

            if (!Schema::hasColumn('licencia_punto_cursos', 'materiales_pdf')) {
                $table->boolean('materiales_pdf')->default(false)->after('clase_en_vivo');
            }

            if (!Schema::hasColumn('licencia_punto_cursos', 'examen_habilitado')) {
                $table->boolean('examen_habilitado')->default(false)->after('materiales_pdf');
            }

            if (!Schema::hasColumn('licencia_punto_cursos', 'calificacion_por_instructor')) {
                $table->boolean('calificacion_por_instructor')->default(true)->after('examen_habilitado');
            }

            if (!Schema::hasColumn('licencia_punto_cursos', 'calificacion_minima')) {
                $table->unsignedTinyInteger('calificacion_minima')->default(80)->after('calificacion_por_instructor');
            }

            if (!Schema::hasColumn('licencia_punto_cursos', 'bbb_meeting_id')) {
                $table->string('bbb_meeting_id', 190)->nullable()->after('observaciones')->unique();
            }

            if (!Schema::hasColumn('licencia_punto_cursos', 'bbb_moderator_password')) {
                $table->string('bbb_moderator_password', 64)->nullable()->after('bbb_meeting_id');
            }

            if (!Schema::hasColumn('licencia_punto_cursos', 'bbb_attendee_password')) {
                $table->string('bbb_attendee_password', 64)->nullable()->after('bbb_moderator_password');
            }

            if (!Schema::hasColumn('licencia_punto_cursos', 'bbb_create_time')) {
                $table->string('bbb_create_time', 64)->nullable()->after('bbb_attendee_password');
            }

            if (!Schema::hasColumn('licencia_punto_cursos', 'bbb_record')) {
                $table->boolean('bbb_record')->default(true)->after('bbb_create_time');
            }

            if (!Schema::hasColumn('licencia_punto_cursos', 'bbb_mute_on_start')) {
                $table->boolean('bbb_mute_on_start')->default(true)->after('bbb_record');
            }

            if (!Schema::hasColumn('licencia_punto_cursos', 'bbb_lock_viewers_microphone')) {
                $table->boolean('bbb_lock_viewers_microphone')->default(false)->after('bbb_mute_on_start');
            }

            if (!Schema::hasColumn('licencia_punto_cursos', 'bbb_anyone_can_talk')) {
                $table->boolean('bbb_anyone_can_talk')->default(false)->after('bbb_lock_viewers_microphone');
            }

            if (!Schema::hasColumn('licencia_punto_cursos', 'bbb_last_started_at')) {
                $table->dateTime('bbb_last_started_at')->nullable()->after('bbb_anyone_can_talk');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('licencia_punto_cursos')) {
            return;
        }

        if (Schema::hasColumn('licencia_punto_cursos', 'bbb_meeting_id')) {
            Schema::table('licencia_punto_cursos', function (Blueprint $table) {
                $table->dropUnique('licencia_punto_cursos_bbb_meeting_id_unique');
            });
        }

        Schema::table('licencia_punto_cursos', function (Blueprint $table) {
            foreach ([
                'clase_en_vivo',
                'materiales_pdf',
                'examen_habilitado',
                'calificacion_por_instructor',
                'calificacion_minima',
                'bbb_meeting_id',
                'bbb_moderator_password',
                'bbb_attendee_password',
                'bbb_create_time',
                'bbb_record',
                'bbb_mute_on_start',
                'bbb_lock_viewers_microphone',
                'bbb_anyone_can_talk',
                'bbb_last_started_at',
            ] as $column) {
                if (Schema::hasColumn('licencia_punto_cursos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
