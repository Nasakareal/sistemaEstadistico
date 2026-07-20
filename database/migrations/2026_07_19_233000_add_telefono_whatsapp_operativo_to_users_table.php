<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTelefonoWhatsappOperativoToUsersTable extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telefono_whatsapp_operativo', 20)
                ->nullable()
                ->unique('users_telefono_whatsapp_operativo_unique')
                ->after('telefono');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_telefono_whatsapp_operativo_unique');
            $table->dropColumn('telefono_whatsapp_operativo');
        });
    }
}
