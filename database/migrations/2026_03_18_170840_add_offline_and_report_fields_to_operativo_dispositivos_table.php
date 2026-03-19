<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operativo_dispositivos', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique()->after('id');

            $table->string('sync_status', 20)->default('synced')->after('client_uuid');
            $table->text('sync_error')->nullable()->after('sync_status');
            $table->timestamp('synced_at')->nullable()->after('sync_error');

            $table->string('tipo_reporte', 100)->nullable()->after('operativo_dispositivo_catalogo_id');
            $table->string('asunto', 255)->nullable()->after('tipo_reporte');

            $table->time('hora_inicio')->nullable()->after('hora');
            $table->time('hora_fin')->nullable()->after('hora_inicio');

            $table->string('carretera', 255)->nullable()->after('lugar');
            $table->string('tramo', 255)->nullable()->after('carretera');
            $table->string('kilometro', 100)->nullable()->after('tramo');

            $table->decimal('lat', 10, 7)->nullable()->after('kilometro');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
            $table->string('coordenadas_texto', 100)->nullable()->after('lng');

            $table->text('narrativa')->nullable()->after('descripcion');
            $table->text('acciones_realizadas')->nullable()->after('narrativa');
            $table->text('frase_institucional')->nullable()->after('acciones_realizadas');

            $table->string('nombre_conductor', 255)->nullable()->after('frase_institucional');
            $table->string('ocupacion_conductor', 255)->nullable()->after('nombre_conductor');
            $table->unsignedInteger('acompanantes_cantidad')->nullable()->after('ocupacion_conductor');
            $table->text('vehiculo_descripcion')->nullable()->after('acompanantes_cantidad');
            $table->string('placas_apoyado', 50)->nullable()->after('vehiculo_descripcion');
            $table->string('procedencia', 255)->nullable()->after('placas_apoyado');
            $table->string('destino', 255)->nullable()->after('procedencia');
            $table->text('motivo_apoyo')->nullable()->after('destino');

            $table->text('elementos_participantes_texto')->nullable()->after('crps_participantes');
            $table->string('cargo_responsable', 255)->nullable()->after('elementos_participantes_texto');
            $table->string('nombre_responsable', 255)->nullable()->after('cargo_responsable');
            $table->string('destacamento_nombre_snapshot', 255)->nullable()->after('nombre_responsable');

            $table->boolean('requiere_evidencia')->default(false)->after('destacamento_nombre_snapshot');
            $table->boolean('compartido_whatsapp')->default(false)->after('requiere_evidencia');
            $table->timestamp('compartido_whatsapp_at')->nullable()->after('compartido_whatsapp');

            $table->index(['fecha', 'unidad_org_id'], 'opd_fecha_unidad_idx');
            $table->index(['delegacion_id', 'destacamento_id'], 'opd_delegacion_destacamento_idx');
            $table->index(['sync_status'], 'opd_sync_status_idx');
            $table->index(['operativo_id', 'fecha'], 'opd_operativo_fecha_idx');
        });
    }

    public function down(): void
    {
        Schema::table('operativo_dispositivos', function (Blueprint $table) {
            $table->dropIndex('opd_fecha_unidad_idx');
            $table->dropIndex('opd_delegacion_destacamento_idx');
            $table->dropIndex('opd_sync_status_idx');
            $table->dropIndex('opd_operativo_fecha_idx');

            $table->dropColumn([
                'client_uuid',
                'sync_status',
                'sync_error',
                'synced_at',
                'tipo_reporte',
                'asunto',
                'hora_inicio',
                'hora_fin',
                'carretera',
                'tramo',
                'kilometro',
                'lat',
                'lng',
                'coordenadas_texto',
                'narrativa',
                'acciones_realizadas',
                'frase_institucional',
                'nombre_conductor',
                'ocupacion_conductor',
                'acompanantes_cantidad',
                'vehiculo_descripcion',
                'placas_apoyado',
                'procedencia',
                'destino',
                'motivo_apoyo',
                'elementos_participantes_texto',
                'cargo_responsable',
                'nombre_responsable',
                'destacamento_nombre_snapshot',
                'requiere_evidencia',
                'compartido_whatsapp',
                'compartido_whatsapp_at',
            ]);
        });
    }
};
