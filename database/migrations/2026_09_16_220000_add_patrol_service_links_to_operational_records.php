<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['actividades', 'hechos'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            $this->addColumnsIfMissing($tableName);
            $this->addLogIndexIfMissing($tableName);
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            $indexName = "{$tableName}_bitacora_patrulla_idx";

            if ($this->indexExists($tableName, $indexName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            }

            foreach ([
                'bitacora_servicio_patrulla_id',
                'patrulla_id',
            ] as $columnName) {
                $constraintName = "{$tableName}_{$columnName}_foreign";

                if ($this->foreignKeyExists($tableName, $constraintName)) {
                    Schema::table($tableName, function (Blueprint $table) use ($constraintName) {
                        $table->dropForeign($constraintName);
                    });
                }
            }

            $columns = collect([
                'bitacora_servicio_patrulla_id',
                'patrulla_id',
            ])->filter(fn (string $column) => Schema::hasColumn($tableName, $column));

            if ($columns->isNotEmpty()) {
                Schema::table($tableName, function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns->all());
                });
            }
        }
    }

    private function addColumnsIfMissing(string $tableName): void
    {
        $missing = collect([
            'patrulla_id',
            'bitacora_servicio_patrulla_id',
        ])->reject(fn (string $column) => Schema::hasColumn($tableName, $column));

        if ($missing->isEmpty()) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($missing) {
            if ($missing->contains('patrulla_id')) {
                $table->unsignedBigInteger('patrulla_id')->nullable();
            }

            if ($missing->contains('bitacora_servicio_patrulla_id')) {
                $table->unsignedBigInteger('bitacora_servicio_patrulla_id')->nullable();
            }
        });
    }

    private function addLogIndexIfMissing(string $tableName): void
    {
        $indexName = "{$tableName}_bitacora_patrulla_idx";

        if (
            $this->indexExists($tableName, $indexName)
            || $this->columnHasIndex(
                $tableName,
                'bitacora_servicio_patrulla_id'
            )
        ) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->index('bitacora_servicio_patrulla_id', $indexName);
        });
    }

    private function foreignKeyExists(
        string $tableName,
        string $constraintName
    ): bool {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->whereRaw('CONSTRAINT_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $tableName)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $tableName)
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }

    private function columnHasIndex(string $tableName, string $columnName): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->exists();
    }
};
