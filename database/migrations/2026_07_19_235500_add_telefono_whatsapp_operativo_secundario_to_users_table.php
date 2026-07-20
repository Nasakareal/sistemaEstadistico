<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTelefonoWhatsappOperativoSecundarioToUsersTable extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telefono_whatsapp_operativo_secundario', 20)
                ->nullable()
                ->unique('users_telefono_whatsapp_operativo_sec_unique')
                ->after('telefono_whatsapp_operativo');
        });

        // Conserva cualquier dato del ajuste intermedio si alcanzó a desplegarse.
        if (Schema::hasColumn('users', 'telefono_whatsapp_secundario')) {
            DB::table('users')
                ->whereNull('telefono_whatsapp_operativo_secundario')
                ->whereNotNull('telefono_whatsapp_secundario')
                ->update([
                    'telefono_whatsapp_operativo_secundario' => DB::raw('telefono_whatsapp_secundario'),
                ]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_telefono_whatsapp_secundario_unique');
                $table->dropColumn('telefono_whatsapp_secundario');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_telefono_whatsapp_operativo_sec_unique');
            $table->dropColumn('telefono_whatsapp_operativo_secundario');
        });
    }
}
