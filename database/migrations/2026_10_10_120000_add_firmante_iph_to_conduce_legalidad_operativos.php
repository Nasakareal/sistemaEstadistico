<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conduce_legalidad_operativos', function (Blueprint $table) {
            $table->string('firmante_iph_nombres', 120)->nullable()->after('observaciones');
            $table->string('firmante_iph_apellido_paterno', 80)->nullable()->after('firmante_iph_nombres');
            $table->string('firmante_iph_apellido_materno', 80)->nullable()->after('firmante_iph_apellido_paterno');
        });
    }

    public function down(): void
    {
        Schema::table('conduce_legalidad_operativos', function (Blueprint $table) {
            $table->dropColumn([
                'firmante_iph_nombres',
                'firmante_iph_apellido_paterno',
                'firmante_iph_apellido_materno',
            ]);
        });
    }
};
