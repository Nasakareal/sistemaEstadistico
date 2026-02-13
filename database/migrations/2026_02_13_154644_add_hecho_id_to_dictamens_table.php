<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dictamens', function (Blueprint $table) {
            $table->unsignedBigInteger('hecho_id')->nullable()->after('id');
            $table->unique('hecho_id');
            $table->foreign('hecho_id')->references('id')->on('hechos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dictamens', function (Blueprint $table) {
            $table->dropForeign(['hecho_id']);
            $table->dropUnique(['hecho_id']);
            $table->dropColumn('hecho_id');
        });
    }
};
