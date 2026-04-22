<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operativo_dispositivos', function (Blueprint $table) {
            if (!Schema::hasColumn('operativo_dispositivos', 'folio')) {
                $table->string('folio')->nullable()->after('id');
            }

            if (!Schema::hasColumn('operativo_dispositivos', 'fecha')) {
                $table->date('fecha')->nullable()->after('folio');
            }

            if (!Schema::hasColumn('operativo_dispositivos', 'hora')) {
                $table->time('hora')->nullable()->after('fecha');
            }

            if (!Schema::hasColumn('operativo_dispositivos', 'lugar')) {
                $table->string('lugar')->nullable()->after('hora');
            }

            if (!Schema::hasColumn('operativo_dispositivos', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('lugar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('operativo_dispositivos', function (Blueprint $table) {
            if (Schema::hasColumn('operativo_dispositivos', 'observaciones')) {
                $table->dropColumn('observaciones');
            }

            if (Schema::hasColumn('operativo_dispositivos', 'lugar')) {
                $table->dropColumn('lugar');
            }

            if (Schema::hasColumn('operativo_dispositivos', 'hora')) {
                $table->dropColumn('hora');
            }

            if (Schema::hasColumn('operativo_dispositivos', 'fecha')) {
                $table->dropColumn('fecha');
            }

            if (Schema::hasColumn('operativo_dispositivos', 'folio')) {
                $table->dropColumn('folio');
            }
        });
    }
};
