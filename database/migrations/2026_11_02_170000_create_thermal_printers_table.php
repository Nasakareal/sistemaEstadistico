<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thermal_printers', function (Blueprint $table) {
            $table->id();
            $table->string('display_name', 80)->unique();
            $table->string('mac_address', 17)->unique();
            $table->string('hardware_name', 80)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('thermal_printers')->insert([
            'display_name' => 'UAS-001',
            'mac_address' => '86:67:7A:62:D8:C3',
            'hardware_name' => 'BT-802',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('thermal_printers');
    }
};
