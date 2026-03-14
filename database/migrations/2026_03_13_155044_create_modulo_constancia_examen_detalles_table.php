<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modulo_constancia_examenes_detalles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('modulo_constancia_examen_id');

            $table->string('folio', 50);
            $table->enum('tipo_licencia', [
                'SERVICIO_PUBLICO',
                'AUTOMOVILISTA',
                'CHOFER',
                'MOTOCICLISTA',
                'PERMISO'
            ]);

            $table->enum('estatus', ['GENERADA', 'IMPRESA', 'REIMPRESA', 'CANCELADA'])->default('GENERADA');
            $table->text('observaciones')->nullable();

            $table->foreign(
                'modulo_constancia_examen_id',
                'mced_mce_id_fk'
            )->references('id')
             ->on('modulo_constancia_examenes')
             ->cascadeOnDelete();

            $table->index('folio');
            $table->index('tipo_licencia');
            $table->index('estatus');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('modulo_constancia_examenes_detalles', function (Blueprint $table) {
            $table->dropForeign('mced_mce_id_fk');
        });

        Schema::dropIfExists('modulo_constancia_examenes_detalles');
    }
};
