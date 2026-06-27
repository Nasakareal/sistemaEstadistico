<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conduce_legalidad_capturas') && !Schema::hasColumn('conduce_legalidad_capturas', 'client_uuid')) {
            Schema::table('conduce_legalidad_capturas', function (Blueprint $table) {
                $table->string('client_uuid', 80)->nullable()->unique()->after('id');
            });
        }

        if (!Schema::hasTable('conduce_legalidad_vehiculos')) {
            return;
        }

        $addServicioUnidad = !Schema::hasColumn('conduce_legalidad_vehiculos', 'servicio_unidad_id');
        $addServicioDelegacion = !Schema::hasColumn('conduce_legalidad_vehiculos', 'servicio_delegacion_id');
        $addServicioCreatedBy = !Schema::hasColumn('conduce_legalidad_vehiculos', 'servicio_created_by');

        if ($addServicioUnidad || $addServicioDelegacion || $addServicioCreatedBy) {
            Schema::table('conduce_legalidad_vehiculos', function (Blueprint $table) use ($addServicioUnidad, $addServicioDelegacion, $addServicioCreatedBy) {
                if ($addServicioUnidad) {
                    $table->foreignId('servicio_unidad_id')->nullable()->after('corralon')->constrained('unidades')->nullOnDelete();
                }
                if ($addServicioDelegacion) {
                    $table->foreignId('servicio_delegacion_id')->nullable()->after('servicio_unidad_id')->constrained('delegaciones')->nullOnDelete();
                }
                if ($addServicioCreatedBy) {
                    $table->foreignId('servicio_created_by')->nullable()->after('servicio_delegacion_id')->constrained('users')->nullOnDelete();
                }
            });
        }

        if ($addServicioUnidad) {
            Schema::table('conduce_legalidad_vehiculos', function (Blueprint $table) {
                $table->index(['servicio_unidad_id', 'created_at'], 'clv_servicio_unidad_created_idx');
            });
        }

        if ($addServicioDelegacion) {
            Schema::table('conduce_legalidad_vehiculos', function (Blueprint $table) {
                $table->index(['servicio_delegacion_id', 'created_at'], 'clv_servicio_delegacion_created_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('conduce_legalidad_vehiculos')) {
            Schema::table('conduce_legalidad_vehiculos', function (Blueprint $table) {
                try {
                    $table->dropIndex('clv_servicio_unidad_created_idx');
                } catch (\Throwable $e) {
                }

                try {
                    $table->dropIndex('clv_servicio_delegacion_created_idx');
                } catch (\Throwable $e) {
                }

                if (Schema::hasColumn('conduce_legalidad_vehiculos', 'servicio_created_by')) {
                    $table->dropConstrainedForeignId('servicio_created_by');
                }
                if (Schema::hasColumn('conduce_legalidad_vehiculos', 'servicio_delegacion_id')) {
                    $table->dropConstrainedForeignId('servicio_delegacion_id');
                }
                if (Schema::hasColumn('conduce_legalidad_vehiculos', 'servicio_unidad_id')) {
                    $table->dropConstrainedForeignId('servicio_unidad_id');
                }
            });
        }

        if (Schema::hasTable('conduce_legalidad_capturas') && Schema::hasColumn('conduce_legalidad_capturas', 'client_uuid')) {
            Schema::table('conduce_legalidad_capturas', function (Blueprint $table) {
                $table->dropUnique('conduce_legalidad_capturas_client_uuid_unique');
                $table->dropColumn('client_uuid');
            });
        }
    }
};
