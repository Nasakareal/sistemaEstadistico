<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->after('id');
            $table->string('sync_status', 30)->default('local')->after('client_uuid');
            $table->text('sync_error')->nullable()->after('sync_status');
            $table->timestamp('synced_at')->nullable()->after('sync_error');

            $table->date('fecha')->nullable()->after('delegacion_id');
            $table->time('hora')->nullable()->after('fecha');

            $table->string('lugar')->nullable()->after('hora');
            $table->string('municipio')->nullable()->after('lugar');
            $table->string('carretera')->nullable()->after('municipio');
            $table->string('tramo')->nullable()->after('carretera');
            $table->string('kilometro', 50)->nullable()->after('tramo');

            $table->decimal('lat', 10, 7)->nullable()->after('kilometro');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
            $table->text('coordenadas_texto')->nullable()->after('lng');
            $table->string('fuente_ubicacion', 50)->nullable()->after('coordenadas_texto');
            $table->string('nota_geo')->nullable()->after('fuente_ubicacion');

            $table->text('motivo')->nullable()->after('nota_geo');
            $table->longText('narrativa')->nullable()->after('motivo');
            $table->longText('acciones_realizadas')->nullable()->after('narrativa');
            $table->longText('observaciones')->nullable()->after('acciones_realizadas');

            $table->unsignedInteger('personas_alcanzadas')->default(0)->after('observaciones');
            $table->unsignedInteger('personas_participantes')->default(0)->after('personas_alcanzadas');
            $table->unsignedInteger('personas_detenidas')->default(0)->after('personas_participantes');

            $table->longText('elementos_participantes_texto')->nullable()->after('personas_detenidas');
            $table->longText('patrullas_participantes_texto')->nullable()->after('elementos_participantes_texto');

            $table->unsignedBigInteger('destacamento_id')->nullable()->after('delegacion_id');

            $table->string('estado_revision', 30)->default('pendiente')->after('updated_by');
            $table->unsignedBigInteger('revisado_por')->nullable()->after('estado_revision');
            $table->timestamp('revisado_at')->nullable()->after('revisado_por');
            $table->text('observacion_revision')->nullable()->after('revisado_at');

            $table->unique('client_uuid');
            $table->index('sync_status');
            $table->index('fecha');
            $table->index('hora');
            $table->index('destacamento_id');
            $table->index('estado_revision');
            $table->index('revisado_por');
            $table->index(['fecha', 'actividad_categoria_id']);
            $table->index(['fecha', 'actividad_subcategoria_id']);
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropIndex(['fecha', 'actividad_subcategoria_id']);
            $table->dropIndex(['fecha', 'actividad_categoria_id']);
            $table->dropIndex(['revisado_por']);
            $table->dropIndex(['estado_revision']);
            $table->dropIndex(['destacamento_id']);
            $table->dropIndex(['hora']);
            $table->dropIndex(['fecha']);
            $table->dropIndex(['sync_status']);
            $table->dropUnique(['client_uuid']);

            $table->dropColumn([
                'client_uuid',
                'sync_status',
                'sync_error',
                'synced_at',
                'fecha',
                'hora',
                'lugar',
                'municipio',
                'carretera',
                'tramo',
                'kilometro',
                'lat',
                'lng',
                'coordenadas_texto',
                'fuente_ubicacion',
                'nota_geo',
                'motivo',
                'narrativa',
                'acciones_realizadas',
                'observaciones',
                'personas_alcanzadas',
                'personas_participantes',
                'personas_detenidas',
                'elementos_participantes_texto',
                'patrullas_participantes_texto',
                'destacamento_id',
                'estado_revision',
                'revisado_por',
                'revisado_at',
                'observacion_revision',
            ]);
        });
    }
};
