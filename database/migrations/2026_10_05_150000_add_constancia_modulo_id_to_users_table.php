<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'constancia_modulo_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('constancia_modulo_id')->nullable()->after('delegacion_id');
                $table->foreign('constancia_modulo_id', 'users_constancia_modulo_fk')
                    ->references('id')
                    ->on('constancia_modulos')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'constancia_modulo_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign('users_constancia_modulo_fk');
                $table->dropColumn('constancia_modulo_id');
            });
        }
    }
};
