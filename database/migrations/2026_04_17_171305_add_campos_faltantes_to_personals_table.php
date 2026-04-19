<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            if (!Schema::hasColumn('personals', 'numero_empleado')) {
                $table->string('numero_empleado')->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('personals', 'foto')) {
                $table->string('foto')->nullable()->after('categoria');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('personals', 'numero_empleado')) {
                $columns[] = 'numero_empleado';
            }

            if (Schema::hasColumn('personals', 'foto')) {
                $columns[] = 'foto';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
