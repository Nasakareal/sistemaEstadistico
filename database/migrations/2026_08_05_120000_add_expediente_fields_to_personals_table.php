<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->date('fecha_nacimiento')->nullable()->after('ap_materno');
            $table->string('tipo_sangre', 20)->nullable()->after('fecha_nacimiento');
            $table->string('numero_seguro_social', 20)->nullable()->after('rfc');
            $table->string('ultimo_grado_estudios', 40)->nullable()->after('area');
            $table->string('alergias_estado', 20)->nullable()->after('ultimo_grado_estudios');
            $table->text('alergias')->nullable()->after('alergias_estado');
            $table->date('fecha_ingreso_unidad')->nullable()->after('fecha_ingreso');
        });

        if (Schema::hasTable('documento_tipos')) {
            $now = now();
            $existe = DB::table('documento_tipos')
                ->where('clave', 'COMPROBANTE_ESTUDIOS')
                ->exists();

            DB::table('documento_tipos')->updateOrInsert(
                ['clave' => 'COMPROBANTE_ESTUDIOS'],
                array_merge([
                    'nombre' => 'Comprobante de estudios',
                    'requiere_vigencia' => false,
                    'dias_vigencia' => null,
                    'sensible' => true,
                    'activo' => true,
                    'updated_at' => $now,
                ], $existe ? [] : ['created_at' => $now])
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('documento_tipos')) {
            $tipo = DB::table('documento_tipos')->where('clave', 'COMPROBANTE_ESTUDIOS');

            if (Schema::hasTable('personal_documentos')) {
                $tipo->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('personal_documentos')
                        ->whereColumn('personal_documentos.documento_tipo_id', 'documento_tipos.id');
                });
            }

            $tipo->delete();
        }

        Schema::table('personals', function (Blueprint $table) {
            $table->dropColumn([
                'fecha_nacimiento',
                'tipo_sangre',
                'numero_seguro_social',
                'ultimo_grado_estudios',
                'alergias_estado',
                'alergias',
                'fecha_ingreso_unidad',
            ]);
        });
    }
};
