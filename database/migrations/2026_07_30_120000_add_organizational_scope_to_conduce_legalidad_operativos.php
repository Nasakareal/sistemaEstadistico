<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conduce_legalidad_operativos')) {
            return;
        }

        Schema::table('conduce_legalidad_operativos', function (Blueprint $table) {
            if (!Schema::hasColumn('conduce_legalidad_operativos', 'unidad_id')) {
                $table->foreignId('unidad_id')
                    ->nullable()
                    ->after('estado')
                    ->constrained('unidades')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('conduce_legalidad_operativos', 'delegacion_id')) {
                $table->foreignId('delegacion_id')
                    ->nullable()
                    ->after('unidad_id')
                    ->constrained('delegaciones')
                    ->nullOnDelete();
            }
        });

        DB::table('conduce_legalidad_operativos')
            ->whereNotNull('created_by')
            ->where(function ($query) {
                $query->whereNull('unidad_id')
                    ->orWhereNull('delegacion_id');
            })
            ->orderBy('id')
            ->chunkById(200, function ($operativos) {
                $users = DB::table('users')
                    ->whereIn('id', $operativos->pluck('created_by')->filter()->unique()->values())
                    ->get(['id', 'unidad_id', 'delegacion_id'])
                    ->keyBy('id');

                foreach ($operativos as $operativo) {
                    $user = $users->get($operativo->created_by);
                    if (!$user) {
                        continue;
                    }

                    DB::table('conduce_legalidad_operativos')
                        ->where('id', $operativo->id)
                        ->update([
                            'unidad_id' => $operativo->unidad_id ?: $user->unidad_id,
                            'delegacion_id' => $operativo->delegacion_id ?: $user->delegacion_id,
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conduce_legalidad_operativos')) {
            return;
        }

        Schema::table('conduce_legalidad_operativos', function (Blueprint $table) {
            if (Schema::hasColumn('conduce_legalidad_operativos', 'delegacion_id')) {
                $table->dropConstrainedForeignId('delegacion_id');
            }
            if (Schema::hasColumn('conduce_legalidad_operativos', 'unidad_id')) {
                $table->dropConstrainedForeignId('unidad_id');
            }
        });
    }
};
