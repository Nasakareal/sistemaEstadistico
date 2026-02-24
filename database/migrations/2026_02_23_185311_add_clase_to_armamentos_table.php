<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('armamentos', function (Blueprint $table) {
            $table->string('clase', 10)->nullable()->after('tipo')->index();
        });
    }

    public function down(): void
    {
        Schema::table('armamentos', function (Blueprint $table) {
            $table->dropColumn('clase');
        });
    }
};
