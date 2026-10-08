<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table): void {
            $table->string('fuente_importacion', 80)->nullable()->after('submission_fingerprint');
            $table->string('clave_importacion', 64)->nullable()->after('fuente_importacion');

            $table->unique(
                ['fuente_importacion', 'clave_importacion'],
                'actividades_fuente_clave_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table): void {
            $table->dropUnique('actividades_fuente_clave_unique');
            $table->dropColumn(['fuente_importacion', 'clave_importacion']);
        });
    }
};
