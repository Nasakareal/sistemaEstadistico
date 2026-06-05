<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('actividad_subcategorias', 'unidad_id')) {
            Schema::table('actividad_subcategorias', function (Blueprint $table) {
                $table->unsignedBigInteger('unidad_id')->nullable()->after('actividad_categoria_id')->index();
            });
        }
    }

    public function down(): void
    {
        // Intentionally kept: this column is part of the activity catalog scope.
    }
};
