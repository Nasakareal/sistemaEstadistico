<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conduce_legalidad_capturas')
            || Schema::hasTable('conduce_legalidad_captura_fundamentos')) {
            return;
        }

        Schema::create('conduce_legalidad_captura_fundamentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('captura_id');
            $table->unsignedBigInteger('licencia_punto_infraccion_id')->nullable();
            $table->foreign('captura_id', 'clcf_captura_fk')
                ->references('id')
                ->on('conduce_legalidad_capturas')
                ->onDelete('cascade');
            $table->foreign(
                'licencia_punto_infraccion_id',
                'clcf_infraccion_fk'
            )
                ->references('id')
                ->on('licencia_punto_infracciones')
                ->onDelete('set null');
            $table->unsignedInteger('orden')->default(0);
            $table->string('infraccion_codigo', 80)->nullable();
            $table->longText('fundamento_legal')->nullable();
            $table->timestamps();

            $table->unique(
                ['captura_id', 'licencia_punto_infraccion_id', 'infraccion_codigo'],
                'clcf_captura_infraccion_codigo_unique'
            );
            $table->index(['captura_id', 'orden'], 'clcf_captura_orden_idx');
        });

        $now = now();
        DB::table('conduce_legalidad_capturas')
            ->whereNotNull('licencia_punto_infraccion_id')
            ->orderBy('id')
            ->chunkById(500, function ($capturas) use ($now) {
                DB::table('conduce_legalidad_captura_fundamentos')->insert(
                    $capturas->map(fn ($captura) => [
                        'captura_id' => $captura->id,
                        'licencia_punto_infraccion_id' => $captura->licencia_punto_infraccion_id,
                        'orden' => 0,
                        'infraccion_codigo' => $captura->infraccion_codigo,
                        'fundamento_legal' => $captura->fundamento_legal,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('conduce_legalidad_captura_fundamentos');
    }
};
