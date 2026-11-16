<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('liberaciones_corralon', function (Blueprint $table) {
            $table->boolean('sierse_reported_released')->default(false)->after('sierse_folio');
            $table->dateTime('sierse_reported_at')->nullable()->after('sierse_reported_released');
            $table->foreignId('sierse_event_id')->nullable()->after('sierse_reported_at')
                ->constrained('sierse_integration_events')->nullOnDelete();
            $table->json('sierse_requirements')->nullable()->after('sierse_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('liberaciones_corralon', function (Blueprint $table) {
            $table->dropForeign(['sierse_event_id']);
            $table->dropColumn([
                'sierse_reported_released',
                'sierse_reported_at',
                'sierse_event_id',
                'sierse_requirements',
            ]);
        });
    }
};
