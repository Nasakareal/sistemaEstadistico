<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operativo_dispositivos', function (Blueprint $table) {
            $table->string('estado_revision')->default('pendiente')->after('compartido_whatsapp_at');
            $table->unsignedBigInteger('revisado_por')->nullable()->after('estado_revision');
            $table->timestamp('revisado_at')->nullable()->after('revisado_por');
            $table->text('observacion_revision')->nullable()->after('revisado_at');

            $table->index('estado_revision');
            $table->index('revisado_por');

            $table->foreign('revisado_por')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('operativo_dispositivos', function (Blueprint $table) {
            $table->dropForeign(['revisado_por']);
            $table->dropIndex(['estado_revision']);
            $table->dropIndex(['revisado_por']);

            $table->dropColumn([
                'estado_revision',
                'revisado_por',
                'revisado_at',
                'observacion_revision',
            ]);
        });
    }
};
