<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('personal_asignacions', function (Blueprint $table) {
            if (!Schema::hasColumn('personal_asignacions', 'armamento_id')) {
                $table->unsignedBigInteger('armamento_id')->nullable()->after('parque_vehicular_id');
                $table->index('armamento_id', 'personal_asignacions_armamento_id_idx');
            }
        });

        Schema::table('personal_asignacions', function (Blueprint $table) {
            try {
                $table->foreign('armamento_id', 'personal_asignacions_armamento_id_fk')
                    ->references('id')->on('armamentos');
            } catch (\Throwable $e) {
            }
        });
    }

    public function down()
    {
        Schema::table('personal_asignacions', function (Blueprint $table) {
            try { $table->dropForeign('personal_asignacions_armamento_id_fk'); } catch (\Throwable $e) {}
            try { $table->dropIndex('personal_asignacions_armamento_id_idx'); } catch (\Throwable $e) {}

            if (Schema::hasColumn('personal_asignacions', 'armamento_id')) {
                $table->dropColumn('armamento_id');
            }
        });
    }
};
