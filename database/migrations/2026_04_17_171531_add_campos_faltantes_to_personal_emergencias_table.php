<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_emergencias', function (Blueprint $table) {
            if (!Schema::hasColumn('personal_emergencias', 'personal_id')) {
                $table->unsignedBigInteger('personal_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('personal_emergencias', 'telefono_emergencia')) {
                $table->string('telefono_emergencia', 20)->nullable()->after('personal_id');
            }

            if (!Schema::hasColumn('personal_emergencias', 'nombre_contacto')) {
                $table->string('nombre_contacto')->nullable()->after('telefono_emergencia');
            }

            if (!Schema::hasColumn('personal_emergencias', 'parentesco')) {
                $table->string('parentesco', 100)->nullable()->after('nombre_contacto');
            }
        });

        $foreignExists = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'personal_emergencias'
              AND COLUMN_NAME = 'personal_id'
              AND REFERENCED_TABLE_NAME = 'personals'
            LIMIT 1
        ");

        if (empty($foreignExists) && Schema::hasColumn('personal_emergencias', 'personal_id')) {
            Schema::table('personal_emergencias', function (Blueprint $table) {
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
              AND TABLE_NAME = 'personal_emergencias'
              AND COLUMN_NAME = 'personal_id'
              AND REFERENCED_TABLE_NAME = 'personals'
            LIMIT 1
        ");

        Schema::table('personal_emergencias', function (Blueprint $table) use ($foreignExists) {
            if (!empty($foreignExists)) {
                $table->dropForeign(['personal_id']);
            }

            $columns = [];

            if (Schema::hasColumn('personal_emergencias', 'parentesco')) {
                $columns[] = 'parentesco';
            }

            if (Schema::hasColumn('personal_emergencias', 'nombre_contacto')) {
                $columns[] = 'nombre_contacto';
            }

            if (Schema::hasColumn('personal_emergencias', 'telefono_emergencia')) {
                $columns[] = 'telefono_emergencia';
            }

            if (Schema::hasColumn('personal_emergencias', 'personal_id')) {
                $columns[] = 'personal_id';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
