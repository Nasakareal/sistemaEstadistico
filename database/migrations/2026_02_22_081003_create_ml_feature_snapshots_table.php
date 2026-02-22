<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_feature_snapshots', function (Blueprint $table) {
            $table->id();

            $table->enum('zona_tipo', ['COLONIA','TRAMO','CELL'])->index();
            $table->unsignedBigInteger('zona_id')->index();

            $table->timestamp('snapshot_at')->index();

            $table->json('features_json');

            $table->boolean('label_ocurrio')->nullable()->index();

            $table->string('fuente', 120)->nullable()->index();
            $table->string('feature_set_version', 50)->default('v1')->index();

            $table->timestamps();

            $table->index(['zona_tipo', 'zona_id', 'snapshot_at'], 'idx_ml_feature_zona_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_feature_snapshots');
    }
};
