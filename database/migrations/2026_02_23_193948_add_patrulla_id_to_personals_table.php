<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->foreignId('patrulla_id')->nullable()->after('turno_id')->constrained('patrullas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('patrulla_id');
        });
    }
};
