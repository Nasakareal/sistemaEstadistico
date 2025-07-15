<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('liberaciones', function (Blueprint $table) {
            $table->string('folio_anual', 10)->nullable()->after('id');
            $table->string('autoriza')->nullable()->after('fecha_liberacion');
            $table->string('motivo_liberacion')->nullable()->after('autoriza');
            $table->dropColumn('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('liberaciones', function (Blueprint $table) {
            $table->text('observaciones')->nullable()->after('personas_autorizadas');
            $table->dropColumn(['folio_anual', 'autoriza', 'motivo_liberacion']);
        });
    }
};
