<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('oficios')) {
            return;
        }

        Schema::table('oficios', function (Blueprint $table) {
            if (!Schema::hasColumn('oficios', 'termino_horas')) {
                $table->unsignedSmallInteger('termino_horas')->nullable()->after('fecha_documento')->index();
            }

            if (!Schema::hasColumn('oficios', 'termino_notificado_at')) {
                $table->timestamp('termino_notificado_at')->nullable()->after('termino_horas');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('oficios')) {
            return;
        }

        Schema::table('oficios', function (Blueprint $table) {
            foreach (['termino_notificado_at', 'termino_horas'] as $column) {
                if (Schema::hasColumn('oficios', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
