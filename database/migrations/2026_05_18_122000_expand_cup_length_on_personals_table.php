<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('personals', 'cup')) {
            DB::statement('ALTER TABLE personals MODIFY cup VARCHAR(100) NULL');
        }
    }

    public function down(): void
    {
        // No se reduce para evitar truncar CUP ya capturados.
    }
};
