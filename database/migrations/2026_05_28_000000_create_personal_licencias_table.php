<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('personal_licencias')) {
            return;
        }

        Schema::create('personal_licencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->string('tipo', 50);
            $table->string('numero', 80)->nullable()->index();
            $table->date('vigencia');
            $table->boolean('permanente')->default(false)->index();
            $table->boolean('activo')->default(true)->index();
            $table->timestamp('vencimiento_notificado_at')->nullable()->index();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('personal_id')
                ->references('id')
                ->on('personals')
                ->cascadeOnDelete();

            $table->index(['personal_id', 'tipo']);
            $table->index(['vigencia', 'permanente']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_licencias');
    }
};
