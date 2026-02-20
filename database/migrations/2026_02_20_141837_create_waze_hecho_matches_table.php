<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('waze_hecho_matches', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('hecho_id');
            $table->unsignedBigInteger('waze_accident_id')->nullable();
            $table->unsignedBigInteger('waze_first_jam_id')->nullable();

            $table->string('cell_key', 32)->index();

            $table->date('fecha');
            $table->time('hora');

            $table->dateTime('hecho_at')->index();
            $table->dateTime('waze_accident_at')->nullable()->index();
            $table->dateTime('waze_first_jam_at')->nullable()->index();

            $table->integer('min_accident_to_hecho')->nullable();
            $table->integer('min_hecho_to_jam')->nullable();

            $table->string('calle_norm', 255)->nullable();
            $table->string('street_norm', 255)->nullable();

            $table->timestamps();

            $table->foreign('hecho_id')->references('id')->on('hechos')->onDelete('cascade');

            $table->unique(['hecho_id', 'cell_key'], 'match_hecho_cell_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waze_hecho_matches');
    }
};
