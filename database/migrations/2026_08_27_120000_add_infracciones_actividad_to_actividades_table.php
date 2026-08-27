<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('actividades', 'infracciones_actividad')) {
            Schema::table('actividades', function (Blueprint $table) {
                $table->json('infracciones_actividad')->nullable()->after('observaciones');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('actividades', 'infracciones_actividad')) {
            Schema::table('actividades', function (Blueprint $table) {
                $table->dropColumn('infracciones_actividad');
            });
        }
    }
};
