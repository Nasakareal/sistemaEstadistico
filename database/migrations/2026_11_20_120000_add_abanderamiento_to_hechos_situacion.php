<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddAbanderamientoToHechosSituacion extends Migration
{
    public function up()
    {
        $this->withLegacyDateCompatibleSqlMode(function () {
            DB::statement(
                "ALTER TABLE hechos MODIFY COLUMN situacion " .
                "ENUM('RESUELTO','PENDIENTE','TURNADO','REPORTE','ABANDERAMIENTO') " .
                "NOT NULL DEFAULT 'PENDIENTE'"
            );
        });
    }

    public function down()
    {
        DB::statement("UPDATE hechos SET situacion = 'REPORTE' WHERE situacion = 'ABANDERAMIENTO'");
        $this->withLegacyDateCompatibleSqlMode(function () {
            DB::statement(
                "ALTER TABLE hechos MODIFY COLUMN situacion " .
                "ENUM('RESUELTO','PENDIENTE','TURNADO','REPORTE') " .
                "NOT NULL DEFAULT 'PENDIENTE'"
            );
        });
    }

    private function withLegacyDateCompatibleSqlMode(callable $callback): void
    {
        $original = (string) (DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode ?? '');
        $modes = array_filter(explode(',', $original), function ($mode) {
            return !in_array($mode, [
                'STRICT_TRANS_TABLES',
                'STRICT_ALL_TABLES',
                'NO_ZERO_DATE',
                'NO_ZERO_IN_DATE',
            ], true);
        });

        DB::statement('SET SESSION sql_mode = ?', [implode(',', $modes)]);

        try {
            $callback();
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$original]);
        }
    }
}
