<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destacamento_red_apoyos', function (Blueprint $table) {
            if (Schema::hasColumn('destacamento_red_apoyos', 'destacamento_id')) {
                try {
                    $table->dropForeign(['destacamento_id']);
                } catch (\Throwable $e) {
                    //
                }
            }
        });

        Schema::table('destacamento_red_apoyos', function (Blueprint $table) {
            if (Schema::hasColumn('destacamento_red_apoyos', 'destacamento_id')) {
                $table->unsignedBigInteger('destacamento_id')->nullable()->change();
            }

            if (!Schema::hasColumn('destacamento_red_apoyos', 'delegacion_id')) {
                $table->foreignId('delegacion_id')
                    ->nullable()
                    ->after('destacamento_id')
                    ->constrained('delegaciones')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }

            if (!Schema::hasColumn('destacamento_red_apoyos', 'region')) {
                $table->string('region', 150)->nullable()->after('delegacion_id')->index();
            }

            if (!Schema::hasColumn('destacamento_red_apoyos', 'nivel_gobierno')) {
                $table->string('nivel_gobierno', 50)->nullable()->after('tipo_apoyo')->index();
            }

            if (!Schema::hasColumn('destacamento_red_apoyos', 'orden')) {
                $table->unsignedSmallInteger('orden')->default(0)->after('activo');
            }
        });

        Schema::table('destacamento_red_apoyos', function (Blueprint $table) {
            if (Schema::hasColumn('destacamento_red_apoyos', 'destacamento_id')) {
                $table->foreign('destacamento_id')
                    ->references('id')
                    ->on('destacamentos')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }
        });
    }

    public function down(): void
    {
        Schema::table('destacamento_red_apoyos', function (Blueprint $table) {
            if (Schema::hasColumn('destacamento_red_apoyos', 'delegacion_id')) {
                try {
                    $table->dropForeign(['delegacion_id']);
                } catch (\Throwable $e) {
                    //
                }
            }

            if (Schema::hasColumn('destacamento_red_apoyos', 'destacamento_id')) {
                try {
                    $table->dropForeign(['destacamento_id']);
                } catch (\Throwable $e) {
                    //
                }
            }
        });

        Schema::table('destacamento_red_apoyos', function (Blueprint $table) {
            foreach (['delegacion_id', 'region', 'nivel_gobierno', 'orden'] as $column) {
                if (Schema::hasColumn('destacamento_red_apoyos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('destacamento_red_apoyos', 'destacamento_id')) {
            $fallbackId = DB::table('destacamentos')->orderBy('id')->value('id');

            if ($fallbackId) {
                DB::table('destacamento_red_apoyos')
                    ->whereNull('destacamento_id')
                    ->update(['destacamento_id' => $fallbackId]);
            }

            Schema::table('destacamento_red_apoyos', function (Blueprint $table) {
                $table->unsignedBigInteger('destacamento_id')->nullable(false)->change();

                $table->foreign('destacamento_id')
                    ->references('id')
                    ->on('destacamentos')
                    ->onDelete('cascade');
            });
        }
    }
};
