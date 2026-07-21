<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conduce_legalidad_operativos')) {
            return;
        }

        if (!Schema::hasColumn('conduce_legalidad_operativos', 'tipo_operativo')) {
            Schema::table('conduce_legalidad_operativos', function (Blueprint $table) {
                $table->string('tipo_operativo', 30)
                    ->default('conduce_legalidad')
                    ->after('nombre')
                    ->index();
            });
        }

        DB::table('conduce_legalidad_operativos')
            ->where(function ($query) {
                $query->whereRaw('UPPER(COALESCE(nombre, ?)) LIKE ?', ['', '%ALCOHOL%'])
                    ->orWhereRaw('UPPER(COALESCE(objetivo, ?)) LIKE ?', ['', '%ALCOHOL%'])
                    ->orWhereRaw('UPPER(COALESCE(narrativa, ?)) LIKE ?', ['', '%ALCOHOL%'])
                    ->orWhereRaw('UPPER(COALESCE(observaciones, ?)) LIKE ?', ['', '%ALCOHOL%']);
            })
            ->update(['tipo_operativo' => 'alcoholimetria']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('conduce_legalidad_operativos')
            || !Schema::hasColumn('conduce_legalidad_operativos', 'tipo_operativo')) {
            return;
        }

        Schema::table('conduce_legalidad_operativos', function (Blueprint $table) {
            $table->dropIndex(['tipo_operativo']);
            $table->dropColumn('tipo_operativo');
        });
    }
};
