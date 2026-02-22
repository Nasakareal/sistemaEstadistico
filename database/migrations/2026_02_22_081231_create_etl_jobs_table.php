<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etl_jobs', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 190)->unique();

            $table->string('schedule', 120)->nullable(); 

            $table->timestamp('last_run_at')->nullable()->index();

            $table->enum('status', ['OK','ERROR','PAUSADO'])
                ->default('OK')
                ->index();

            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('failed')->default(0);

            $table->longText('ultimo_error')->nullable();
            $table->json('ultimo_resumen_json')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etl_jobs');
    }
};
