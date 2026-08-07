<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->foreignId('destacamento_id')
                ->nullable()
                ->after('unidad_id')
                ->constrained('destacamentos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destacamento_id');
        });
    }
};
