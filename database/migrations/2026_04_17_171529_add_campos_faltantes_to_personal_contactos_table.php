<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_contactos', function (Blueprint $table) {
            if (!Schema::hasColumn('personal_contactos', 'personal_id')) {
                $table->unsignedBigInteger('personal_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('personal_contactos', 'telefono_personal')) {
                $table->string('telefono_personal', 20)->nullable()->after('personal_id');
            }

            if (!Schema::hasColumn('personal_contactos', 'telefono_secundario')) {
                $table->string('telefono_secundario', 20)->nullable()->after('telefono_personal');
            }

            if (!Schema::hasColumn('personal_contactos', 'correo_electronico')) {
                $table->string('correo_electronico')->nullable()->after('telefono_secundario');
            }
        });

        $foreignExists = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'personal_contactos'
              AND COLUMN_NAME = 'personal_id'
              AND REFERENCED_TABLE_NAME = 'personals'
            LIMIT 1
        ");

        if (empty($foreignExists) && Schema::hasColumn('personal_contactos', 'personal_id')) {
            Schema::table('personal_contactos', function (Blueprint $table) {
                $table->foreign('personal_id')->references('id')->on('personals')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        $foreignExists = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'personal_contactos'
              AND COLUMN_NAME = 'personal_id'
              AND REFERENCED_TABLE_NAME = 'personals'
            LIMIT 1
        ");

        Schema::table('personal_contactos', function (Blueprint $table) use ($foreignExists) {
            if (!empty($foreignExists)) {
                $table->dropForeign(['personal_id']);
            }

            $columns = [];

            if (Schema::hasColumn('personal_contactos', 'correo_electronico')) {
                $columns[] = 'correo_electronico';
            }

            if (Schema::hasColumn('personal_contactos', 'telefono_secundario')) {
                $columns[] = 'telefono_secundario';
            }

            if (Schema::hasColumn('personal_contactos', 'telefono_personal')) {
                $columns[] = 'telefono_personal';
            }

            if (Schema::hasColumn('personal_contactos', 'personal_id')) {
                $columns[] = 'personal_id';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
