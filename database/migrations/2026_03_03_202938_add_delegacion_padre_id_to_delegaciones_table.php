<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delegaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('delegacion_padre_id')->nullable()->after('id');
            $table->index('delegacion_padre_id');

            $table->foreign('delegacion_padre_id')
                ->references('id')
                ->on('delegaciones')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('delegaciones', function (Blueprint $table) {
            $table->dropForeign(['delegacion_padre_id']);
            $table->dropIndex(['delegacion_padre_id']);
            $table->dropColumn('delegacion_padre_id');
        });
    }
};
