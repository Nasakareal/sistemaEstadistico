<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_contracts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            $table->string('plan', 120)->index(); 

            $table->date('inicio')->index();
            $table->date('fin')->nullable()->index();

            $table->decimal('monto', 12, 2)->default(0);

            $table->enum('frecuencia_pago', ['MENSUAL','TRIMESTRAL','ANUAL'])
                ->default('TRIMESTRAL')
                ->index();

            $table->json('sla_json')->nullable();

            $table->enum('status', ['BORRADOR','ACTIVO','SUSPENDIDO','CANCELADO'])
                ->default('BORRADOR')
                ->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_contracts');
    }
};
