<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->char('submission_fingerprint', 64)
                ->nullable()
                ->after('client_uuid')
                ->unique('actividades_submission_fingerprint_unique');
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropUnique('actividades_submission_fingerprint_unique');
            $table->dropColumn('submission_fingerprint');
        });
    }
};
