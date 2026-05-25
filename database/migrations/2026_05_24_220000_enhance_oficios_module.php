<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('oficios')) {
            return;
        }

        $this->dropLegacyNumeroUniqueIndex();

        Schema::table('oficios', function (Blueprint $table) {
            if (Schema::hasColumn('oficios', 'numero_oficio')) {
                $table->string('numero_oficio', 500)->change();
            }

            if (Schema::hasColumn('oficios', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->change();
            }

            if (!Schema::hasColumn('oficios', 'tipo')) {
                $table->string('tipo', 30)->default('oficio')->index();
            }

            if (!Schema::hasColumn('oficios', 'sentido')) {
                $table->string('sentido', 20)->default('entrada')->index();
            }

            if (!Schema::hasColumn('oficios', 'unidad_id')) {
                $table->foreignId('unidad_id')
                    ->nullable()
                    ->constrained('unidades')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('oficios', 'fecha_documento')) {
                $table->date('fecha_documento')->nullable()->index();
            }

            if (!Schema::hasColumn('oficios', 'remitente')) {
                $table->string('remitente', 255)->nullable();
            }

            if (!Schema::hasColumn('oficios', 'destinatario')) {
                $table->string('destinatario', 255)->nullable();
            }

            if (!Schema::hasColumn('oficios', 'asunto')) {
                $table->string('asunto', 500)->nullable();
            }

            if (!Schema::hasColumn('oficios', 'contesta_a_id')) {
                $table->foreignId('contesta_a_id')
                    ->nullable()
                    ->constrained('oficios')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('oficios', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('oficios', 'updated_by')) {
                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        DB::table('oficios')->whereNull('tipo')->update(['tipo' => 'oficio']);
        DB::table('oficios')->whereNull('sentido')->update(['sentido' => 'entrada']);

        $this->ensurePermissions();
    }

    public function down(): void
    {
        if (!Schema::hasTable('oficios')) {
            return;
        }

        Schema::table('oficios', function (Blueprint $table) {
            foreach (['updated_by', 'created_by', 'contesta_a_id', 'unidad_id'] as $column) {
                if (Schema::hasColumn('oficios', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach ([
                'tipo',
                'sentido',
                'fecha_documento',
                'remitente',
                'destinatario',
                'asunto',
            ] as $column) {
                if (Schema::hasColumn('oficios', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('oficios', 'numero_oficio')) {
                $table->string('numero_oficio')->change();
            }

            if (Schema::hasColumn('oficios', 'pdf_path')) {
                $table->string('pdf_path')->nullable(false)->change();
            }
        });
    }

    private function dropLegacyNumeroUniqueIndex(): void
    {
        try {
            DB::statement('ALTER TABLE oficios DROP INDEX oficios_numero_oficio_unique');
        } catch (Throwable $e) {
            // El indice puede no existir en instalaciones que ya fueron ajustadas.
        }
    }

    private function ensurePermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $now = now();

        foreach ([
            'ver oficios',
            'crear oficios',
            'editar oficios',
            'eliminar oficios',
        ] as $permission) {
            $exists = DB::table('permissions')
                ->where('name', $permission)
                ->where('guard_name', 'web')
                ->exists();

            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                array_merge(['updated_at' => $now], $exists ? [] : ['created_at' => $now])
            );
        }

        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
