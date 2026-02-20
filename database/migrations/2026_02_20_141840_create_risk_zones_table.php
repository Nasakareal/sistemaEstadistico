<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('risk_zones', function (Blueprint $table) {
            $table->id();

            $table->string('cell_key', 32)->index();
            $table->string('corredor', 64)->nullable()->index();

            $table->unsignedSmallInteger('window_min')->default(30)->index();

            $table->unsignedInteger('hechos_hist')->default(0);
            $table->unsignedInteger('jams_window')->default(0);

            $table->decimal('score', 10, 2)->default(0);

            $table->dateTime('calculated_at')->index();

            $table->timestamps();

            $table->unique(['cell_key', 'window_min', 'corredor'], 'risk_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_zones');
    }
};
