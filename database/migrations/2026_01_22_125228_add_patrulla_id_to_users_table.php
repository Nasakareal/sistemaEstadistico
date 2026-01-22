<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {

            if (!Schema::hasColumn('users', 'patrulla_id')) {
                $table->unsignedBigInteger('patrulla_id')->nullable()->after('turno_id');
            }

            $table->index('patrulla_id');

            $table->foreign('patrulla_id', 'users_patrulla_id_fk')
                ->references('id')
                ->on('patrullas')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropForeign('users_patrulla_id_fk');
            } catch (\Throwable $e) {
            }

            try {
                $table->dropIndex(['patrulla_id']);
            } catch (\Throwable $e) {
            }

            if (Schema::hasColumn('users', 'patrulla_id')) {
                $table->dropColumn('patrulla_id');
            }
        });
    }
};
