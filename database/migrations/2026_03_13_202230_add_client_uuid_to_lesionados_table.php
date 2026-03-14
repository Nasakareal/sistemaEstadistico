<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesionados', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->after('id');
            $table->unique('client_uuid', 'lesionados_client_uuid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('lesionados', function (Blueprint $table) {
            $table->dropUnique('lesionados_client_uuid_unique');
            $table->dropColumn('client_uuid');
        });
    }
};
