<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsor_campaigns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            $table->string('nombre', 190)->index();

            $table->date('inicio')->index();
            $table->date('fin')->nullable()->index();

            $table->json('reglas_json')->nullable();

            $table->unsignedInteger('impresiones')->default(0);
            $table->unsignedInteger('clics')->default(0);

            $table->boolean('activo')->default(true)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsor_campaigns');
    }
};
