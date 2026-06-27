<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        'ver conduce legalidad',
        'crear conduce legalidad',
        'editar conduce legalidad',
        'eliminar conduce legalidad',
    ];

    public function up(): void
    {
        Schema::create('conduce_legalidad_operativos', function (Blueprint $table) {
            $table->id();
            $table->string('client_uuid', 80)->nullable()->unique();
            $table->string('nombre')->default('Operativo conduce con legalidad');
            $table->date('fecha')->index();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_cierre')->nullable();
            $table->string('municipio', 120)->nullable();
            $table->string('lugar')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('coordenadas_texto')->nullable();
            $table->longText('objetivo')->nullable();
            $table->longText('narrativa')->nullable();
            $table->longText('observaciones')->nullable();
            $table->string('estado', 30)->default('activo')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['fecha', 'estado']);
            $table->index(['created_by', 'fecha']);
        });

        Schema::create('conduce_legalidad_capturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operativo_id')->constrained('conduce_legalidad_operativos')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('unidad_id')->nullable()->constrained('unidades')->nullOnDelete();
            $table->foreignId('delegacion_id')->nullable()->constrained('delegaciones')->nullOnDelete();
            $table->date('fecha')->nullable();
            $table->time('hora')->nullable();
            $table->string('municipio', 120)->nullable();
            $table->string('lugar')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('coordenadas_texto')->nullable();
            $table->longText('narrativa')->nullable();
            $table->longText('observaciones')->nullable();
            $table->timestamps();

            $table->index(['operativo_id', 'created_by']);
            $table->index(['created_by', 'created_at']);
            $table->index(['unidad_id', 'created_at']);
        });

        Schema::create('conduce_legalidad_vehiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('captura_id')->constrained('conduce_legalidad_capturas')->cascadeOnDelete();
            $table->string('marca', 80)->nullable();
            $table->string('modelo', 20)->nullable();
            $table->string('tipo_general', 80)->nullable();
            $table->string('tipo', 80)->nullable();
            $table->string('linea', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->string('placas', 20)->nullable()->index();
            $table->string('estado_placas', 80)->nullable();
            $table->string('serie', 30)->nullable()->index();
            $table->unsignedInteger('capacidad_personas')->default(0);
            $table->string('tipo_servicio', 80)->nullable();
            $table->string('tarjeta_circulacion_nombre')->nullable();
            $table->foreignId('grua_id')->nullable()->constrained('gruas')->nullOnDelete();
            $table->foreignId('corralon_id')->nullable()->constrained('gruas')->nullOnDelete();
            $table->string('grua')->nullable();
            $table->string('corralon')->nullable();
            $table->string('aseguradora')->nullable();
            $table->decimal('monto_danos', 12, 2)->nullable();
            $table->longText('partes_danadas')->nullable();
            $table->boolean('antecedente_vehiculo')->default(false);
            $table->longText('raw_tarjeta_qr')->nullable();
            $table->foreignId('licencia_punto_infraccion_id')->nullable()->constrained('licencia_punto_infracciones')->nullOnDelete();
            $table->string('infraccion_codigo', 80)->nullable();
            $table->longText('fundamento_legal')->nullable();
            $table->boolean('retencion_vehiculo')->default(false)->index();
            $table->longText('motivo_retencion')->nullable();
            $table->longText('observaciones')->nullable();
            $table->timestamps();

            $table->index(['captura_id', 'retencion_vehiculo']);
        });

        Schema::create('conduce_legalidad_personas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('captura_id')->constrained('conduce_legalidad_capturas')->cascadeOnDelete();
            $table->string('nombre')->nullable()->index();
            $table->string('telefono', 30)->nullable();
            $table->string('domicilio')->nullable();
            $table->string('sexo', 30)->nullable();
            $table->string('ocupacion')->nullable();
            $table->unsignedTinyInteger('edad')->nullable();
            $table->string('tipo_licencia', 80)->nullable();
            $table->string('estado_licencia', 120)->nullable();
            $table->string('numero_licencia', 80)->nullable()->index();
            $table->date('vigencia_licencia')->nullable();
            $table->boolean('permanente')->default(false);
            $table->longText('raw_licencia_qr')->nullable();
            $table->longText('observaciones')->nullable();
            $table->timestamps();

            $table->index(['captura_id', 'nombre']);
        });

        $this->ensurePermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('conduce_legalidad_personas');
        Schema::dropIfExists('conduce_legalidad_vehiculos');
        Schema::dropIfExists('conduce_legalidad_capturas');
        Schema::dropIfExists('conduce_legalidad_operativos');
    }

    private function ensurePermissions(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles') || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        $now = now();

        foreach ($this->permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }

        $assignments = [
            'Superadmin' => $this->permissions,
            'Responsable de Turno' => [
                'ver conduce legalidad',
                'crear conduce legalidad',
                'editar conduce legalidad',
            ],
            'Subdirector' => $this->permissions,
        ];

        foreach ($assignments as $roleName => $permissions) {
            $payload = ['updated_at' => $now, 'created_at' => $now];
            if (Schema::hasColumn('roles', 'unidad_id') && $roleName === 'Responsable de Turno') {
                $payload['unidad_id'] = 5;
            }

            DB::table('roles')->updateOrInsert(
                ['name' => $roleName, 'guard_name' => 'web'],
                $payload
            );

            $roleQuery = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web');

            if (Schema::hasColumn('roles', 'unidad_id') && $roleName === 'Responsable de Turno') {
                $roleQuery->where(function ($query) {
                    $query->where('unidad_id', 5)->orWhereNull('unidad_id');
                });
            }

            $roleId = $roleQuery->value('id');
            if (!$roleId) {
                continue;
            }

            $permissionIds = DB::table('permissions')
                ->whereIn('name', $permissions)
                ->where('guard_name', 'web')
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }
};
