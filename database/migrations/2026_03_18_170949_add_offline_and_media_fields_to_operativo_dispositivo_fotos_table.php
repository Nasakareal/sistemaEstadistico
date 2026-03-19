<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operativo_dispositivo_fotos', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique()->after('id');

            $table->string('sync_status', 20)->default('synced')->after('client_uuid');
            $table->text('sync_error')->nullable()->after('sync_status');
            $table->timestamp('synced_at')->nullable()->after('sync_error');

            $table->unsignedInteger('orden')->default(0)->after('observaciones');
            $table->boolean('es_portada')->default(false)->after('orden');
            $table->string('caption', 255)->nullable()->after('es_portada');

            $table->decimal('lat', 10, 7)->nullable()->after('caption');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
            $table->timestamp('tomada_en')->nullable()->after('lng');

            $table->boolean('incluida_en_compartido')->default(true)->after('tomada_en');

            $table->index(['operativo_dispositivo_id', 'orden'], 'opdf_dispositivo_orden_idx');
            $table->index(['sync_status'], 'opdf_sync_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('operativo_dispositivo_fotos', function (Blueprint $table) {
            $table->dropIndex('opdf_dispositivo_orden_idx');
            $table->dropIndex('opdf_sync_status_idx');

            $table->dropColumn([
                'client_uuid',
                'sync_status',
                'sync_error',
                'synced_at',
                'orden',
                'es_portada',
                'caption',
                'lat',
                'lng',
                'tomada_en',
                'incluida_en_compartido',
            ]);
        });
    }
};
