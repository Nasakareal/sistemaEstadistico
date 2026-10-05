<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportesCiudadanosSiniestrosTable extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_ciudadanos_siniestros', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 30)->unique();
            $table->string('telefono_reportante', 20)->index();
            $table->string('ubicacion', 500);
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->string('tipo_siniestro', 100);
            $table->text('vehiculos');
            $table->text('lesionados');
            $table->text('riesgos_observaciones');
            $table->string('estatus', 30)->default('pendiente_validacion')->index();
            $table->timestamp('reportado_at');
            $table->timestamp('notificado_at')->nullable();
            $table->json('resultados_notificacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_ciudadanos_siniestros');
    }
}
