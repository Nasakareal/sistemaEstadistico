<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            if (!Schema::hasColumn('personals', 'numero_placa')) {
                $table->string('numero_placa', 50)->nullable()->after('numero_empleado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            if (Schema::hasColumn('personals', 'numero_placa')) {
                $table->dropColumn('numero_placa');
            }
        });
    }
};
