<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->string('calle_norm', 255)->nullable()->index()->after('calle');
        });
    }

    public function down(): void
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->dropIndex(['calle_norm']);
            $table->dropColumn('calle_norm');
        });
    }
};
