<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tutoriales', 'unidad_id')) {
            Schema::table('tutoriales', function (Blueprint $table) {
                $table->foreignId('unidad_id')
                    ->nullable()
                    ->after('tutorial_categoria_id')
                    ->constrained('unidades')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tutoriales', 'unidad_id')) {
            Schema::table('tutoriales', function (Blueprint $table) {
                $table->dropForeign(['unidad_id']);
                $table->dropColumn('unidad_id');
            });
        }
    }
};
