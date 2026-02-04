<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->timestamp('notificado_48_at')->nullable()->after('updated_at');
            $table->timestamp('notificado_72_at')->nullable()->after('notificado_48_at');
            $table->timestamp('ultimo_recordatorio_72_at')->nullable()->after('notificado_72_at');

            $table->index(['situacion', 'created_at']);
            $table->index(['created_by', 'situacion', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->dropIndex(['situacion', 'created_at']);
            $table->dropIndex(['created_by', 'situacion', 'created_at']);
            $table->dropColumn(['notificado_48_at', 'notificado_72_at', 'ultimo_recordatorio_72_at']);
        });
    }
};
