<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 190)->unique();

            $table->enum('tipo', ['INTERNO','EXTERNO'])
                ->default('EXTERNO')
                ->index();

            $table->enum('status', ['OK','DEGRADADO','CAIDO'])
                ->default('OK')
                ->index();

            $table->json('config_json')->nullable();

            $table->longText('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_sources');
    }
};
