<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operativos', function (Blueprint $table) {
            $table->foreignId('destacamento_id')->nullable()->after('delegacion_id')
                ->constrained('destacamentos')->nullOnDelete()->cascadeOnUpdate();

            $table->index(['fecha', 'destacamento_id']);
        });
    }

    public function down(): void
    {
        Schema::table('operativos', function (Blueprint $table) {
            $table->dropIndex(['fecha', 'destacamento_id']);
            $table->dropConstrainedForeignId('destacamento_id');
        });
    }
};
