<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            if (!Schema::hasColumn('vehiculos', 'permiso_circular')) {
                $table->string('permiso_circular', 60)
                    ->nullable()
                    ->after('estado_placas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            if (Schema::hasColumn('vehiculos', 'permiso_circular')) {
                $table->dropColumn('permiso_circular');
            }
        });
    }
};
