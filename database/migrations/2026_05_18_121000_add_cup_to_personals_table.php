<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            if (!Schema::hasColumn('personals', 'cup')) {
                $table->string('cup', 30)->nullable()->unique()->after('cuip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            if (Schema::hasColumn('personals', 'cup')) {
                $table->dropColumn('cup');
            }
        });
    }
};
