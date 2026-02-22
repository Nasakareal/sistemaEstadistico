<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waze_zone_aggregates', function (Blueprint $table) {
            $table->id();

            $table->enum('zona_tipo', ['COLONIA','TRAMO','CELL'])->index();
            $table->unsignedBigInteger('zona_id')->index();

            $table->timestamp('bucket_at')->index();
            $table->unsignedSmallInteger('bucket_minutes')->default(15)->index();

            $table->unsignedInteger('alerts_total')->default(0);
            $table->unsignedInteger('jams_total')->default(0);
            $table->unsignedInteger('hazards_total')->default(0);
            $table->unsignedInteger('accidents_total')->default(0);

            $table->decimal('severidad_prom', 6, 2)->nullable();

            $table->json('detalle_json')->nullable();

            $table->timestamps();

            $table->index(['zona_tipo','zona_id','bucket_at'], 'idx_waze_zone_bucket');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waze_zone_aggregates');
    }
};
