<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_api_keys', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            $table->string('api_key_hash', 64)->unique();

            $table->json('scopes')->nullable();

            $table->unsignedInteger('rate_limit_per_min')->default(60);

            $table->boolean('activo')->default(true)->index();

            $table->timestamp('last_used_at')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_api_keys');
    }
};
