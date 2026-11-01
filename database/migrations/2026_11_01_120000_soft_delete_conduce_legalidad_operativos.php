<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conduce_legalidad_operativos', function (Blueprint $table) {
            $table->softDeletes();
        });

        DB::table('conduce_legalidad_operativos')
            ->where('estado', 'standby')
            ->update([
                'estado' => 'cancelado',
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('conduce_legalidad_operativos')
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);

        Schema::table('conduce_legalidad_operativos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
