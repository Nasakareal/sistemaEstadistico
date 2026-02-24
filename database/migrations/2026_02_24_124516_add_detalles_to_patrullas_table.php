<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $dbName = DB::getDatabaseName();

        $rows = DB::select("
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
            LIMIT 1
        ", [$dbName, $table, $indexName]);

        return !empty($rows);
    }

    public function up(): void
    {
        Schema::table('patrullas', function (Blueprint $table) {
            if (!Schema::hasColumn('patrullas', 'tipo')) {
                $table->string('tipo', 30)->nullable()->after('numero_economico');
            }

            if (!Schema::hasColumn('patrullas', 'marca')) {
                $table->string('marca', 80)->nullable()->after('tipo');
            }

            if (!Schema::hasColumn('patrullas', 'linea')) {
                $table->string('linea', 120)->nullable()->after('marca');
            }

            if (!Schema::hasColumn('patrullas', 'modelo')) {
                $table->unsignedSmallInteger('modelo')->nullable()->after('linea');
            }

            if (!Schema::hasColumn('patrullas', 'placas')) {
                $table->string('placas', 20)->nullable()->after('modelo');
            }

            if (!Schema::hasColumn('patrullas', 'serie')) {
                $table->string('serie', 60)->nullable()->after('placas');
            }

            if (!Schema::hasColumn('patrullas', 'color')) {
                $table->string('color', 50)->nullable()->after('serie');
            }

            if (!Schema::hasColumn('patrullas', 'no_motor')) {
                $table->string('no_motor', 60)->nullable()->after('color');
            }

            if (!Schema::hasColumn('patrullas', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('no_motor');
            }
        });

        // Índices UNIQUE (solo si NO existen con esos nombres)
        Schema::table('patrullas', function (Blueprint $table) {
            // Nota: aquí NO podemos llamar $this dentro del closure directamente en PHP viejo,
            // pero en PHP moderno sí funciona. Para evitar broncas, los agrego afuera.
        });

        if (!$this->indexExists('patrullas', 'patrullas_numero_economico_unique_v2')) {
            Schema::table('patrullas', function (Blueprint $table) {
                $table->unique('numero_economico', 'patrullas_numero_economico_unique_v2');
            });
        }

        if (!$this->indexExists('patrullas', 'patrullas_placas_unique_v2')) {
            Schema::table('patrullas', function (Blueprint $table) {
                $table->unique('placas', 'patrullas_placas_unique_v2');
            });
        }

        if (!$this->indexExists('patrullas', 'patrullas_serie_unique_v2')) {
            Schema::table('patrullas', function (Blueprint $table) {
                $table->unique('serie', 'patrullas_serie_unique_v2');
            });
        }
    }

    public function down(): void
    {
        // Quitar índices si existen
        if ($this->indexExists('patrullas', 'patrullas_numero_economico_unique_v2')) {
            Schema::table('patrullas', function (Blueprint $table) {
                $table->dropUnique('patrullas_numero_economico_unique_v2');
            });
        }

        if ($this->indexExists('patrullas', 'patrullas_placas_unique_v2')) {
            Schema::table('patrullas', function (Blueprint $table) {
                $table->dropUnique('patrullas_placas_unique_v2');
            });
        }

        if ($this->indexExists('patrullas', 'patrullas_serie_unique_v2')) {
            Schema::table('patrullas', function (Blueprint $table) {
                $table->dropUnique('patrullas_serie_unique_v2');
            });
        }

        // Quitar columnas SOLO si existen
        Schema::table('patrullas', function (Blueprint $table) {
            $cols = [];

            foreach ([
                'tipo','marca','linea','modelo','placas','serie','color','no_motor','observaciones'
            ] as $col) {
                if (Schema::hasColumn('patrullas', $col)) {
                    $cols[] = $col;
                }
            }

            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
