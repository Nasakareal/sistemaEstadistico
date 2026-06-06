<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vialidad_dispositivo_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('vialidad_dispositivo_detalles', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('vialidad_dispositivo_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        Schema::table('vialidad_dispositivo_fotos', function (Blueprint $table) {
            if (!Schema::hasColumn('vialidad_dispositivo_fotos', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('vialidad_dispositivo_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vialidad_dispositivo_fotos', function (Blueprint $table) {
            if (Schema::hasColumn('vialidad_dispositivo_fotos', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });

        Schema::table('vialidad_dispositivo_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('vialidad_dispositivo_detalles', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};
