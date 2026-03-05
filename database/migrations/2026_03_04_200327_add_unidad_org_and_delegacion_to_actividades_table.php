<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            if (!Schema::hasColumn('actividades', 'unidad_org_id')) {
                $table->unsignedBigInteger('unidad_org_id')->nullable()->index()->after('updated_by');
            }

            if (!Schema::hasColumn('actividades', 'delegacion_id')) {
                $table->unsignedBigInteger('delegacion_id')->nullable()->index()->after('unidad_org_id');
            }
        });

    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {

            if (Schema::hasColumn('actividades', 'delegacion_id')) {
                $table->dropColumn('delegacion_id');
            }

            if (Schema::hasColumn('actividades', 'unidad_org_id')) {
                $table->dropColumn('unidad_org_id');
            }
        });
    }
};
