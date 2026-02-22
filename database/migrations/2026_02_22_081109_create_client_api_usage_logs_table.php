<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_api_usage_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            $table->date('fecha')->index();

            $table->string('endpoint', 190)->index();
            $table->unsignedInteger('request_count')->default(0);

            $table->json('metadata_json')->nullable();

            $table->timestamps();

            $table->unique(
                ['client_id', 'fecha', 'endpoint'],
                'uniq_client_usage_day_endpoint'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_api_usage_logs');
    }
};
