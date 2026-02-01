<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hechos', function (Blueprint $table) {
            if (!Schema::hasColumn('hechos', 'delegacion_id')) {
                $table->foreignId('delegacion_id')
                    ->nullable()
                    ->after('municipio')
                    ->constrained('delegaciones')
                    ->nullOnDelete();

                $table->index(['delegacion_id', 'fecha']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('hechos', function (Blueprint $table) {
            if (Schema::hasColumn('hechos', 'delegacion_id')) {
                $table->dropForeign(['delegacion_id']);
                $table->dropIndex(['delegacion_id', 'fecha']);
                $table->dropColumn('delegacion_id');
            }
        });
    }
};
