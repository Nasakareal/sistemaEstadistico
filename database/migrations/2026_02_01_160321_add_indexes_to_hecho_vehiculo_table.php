<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hecho_vehiculo', function (Blueprint $table) {

            $table->index('hecho_id', 'hecho_vehiculo_hecho_id_idx');
            $table->index('vehiculo_id', 'hecho_vehiculo_vehiculo_id_idx');
            $table->unique(['hecho_id', 'vehiculo_id'], 'hecho_vehiculo_unique_pair');
        });
    }

    public function down(): void
    {
        Schema::table('hecho_vehiculo', function (Blueprint $table) {
            $table->dropUnique('hecho_vehiculo_unique_pair');
            $table->dropIndex('hecho_vehiculo_hecho_id_idx');
            $table->dropIndex('hecho_vehiculo_vehiculo_id_idx');
        });
    }
};
