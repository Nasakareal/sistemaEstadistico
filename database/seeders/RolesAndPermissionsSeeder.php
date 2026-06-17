<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Si usas cache de spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Todos los permisos
        $permissions = [
            // Configuraciones y usuarios
            'ver configuraciones',
            'ver usuarios',
            'crear usuarios',
            'editar usuarios',
            'eliminar usuarios',

            // Roles
            'ver roles',
            'crear roles',
            'editar roles',
            'eliminar roles',

            // Hechos de tránsito
            'ver hechos',
            'crear hechos',
            'editar hechos',
            'eliminar hechos',

            // Hechos de Vehículos
            'ver vehiculos',
            'crear vehiculos',
            'editar vehiculos',
            'eliminar vehiculos',

            // Hechos de Lesionados
            'ver lesionados',
            'crear lesionados',
            'editar lesionados',
            'eliminar lesionados',

            // Actividades
            'ver actividades',
            'crear actividades',
            'editar actividades',
            'eliminar actividades',

            // Operativos de carreteras
            'ver operativos carreteras',
            'crear operativos carreteras',
            'editar operativos carreteras',
            'eliminar operativos carreteras',
            'ver estadisticas carreteras',

            // Operativos de vialidades urbanas
            'ver operativos vialidades',
            'crear operativos vialidades',
            'editar operativos vialidades',
            'eliminar operativos vialidades',

            // Grúas
            'ver gruas',
            'crear gruas',
            'editar gruas',
            'eliminar gruas',
            'subir liberacion grua',

            // Dictamenes
            'ver dictamenes',
            'crear dictamenes',
            'editar dictamenes',
            'eliminar dictamenes',

            // Formatos
            'ver formatos',
            'crear formatos',
            'editar formatos',
            'eliminar formatos',

            // Listas
            'ver listas',
            'crear listas',
            'editar listas',
            'eliminar listas',

            // Oficios
            'ver oficios',
            'crear oficios',
            'editar oficios',
            'eliminar oficios',

            // Delegaciones
            'ver delegaciones',
            'crear delegaciones',
            'editar delegaciones',
            'eliminar delegaciones',

            // Destacamentos
            'ver destacamentos',
            'crear destacamentos',
            'editar destacamentos',
            'eliminar destacamentos',
            'ver mapa destacamentos',

            // Directorio red de apoyo
            'ver directorio red apoyo',
            'crear directorio red apoyo',
            'editar directorio red apoyo',
            'eliminar directorio red apoyo',

            // Ver Estadisticas
            'ver estadisticas',
            'crear estadisticas',
            'editar estadisticas',
            'eliminar estadisticas',
            'ver estadisticas globales',
            'ver estadisticas actividades',
            'ver mapa',

            // Modulo de examenes y constancias
            'ver modulo examenes',
            'crear modulo examenes',
            'editar modulo examenes',
            'eliminar modulo examenes',

            // Sistema de puntos de licencia
            'ver puntos licencias',
            'crear puntos licencias',
            'editar puntos licencias',
            'registrar infracciones puntos licencias',
            'acreditar capacitacion puntos licencias',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Definición de roles y permisos asignados
        $operacionHechos = [
            'ver hechos',
            'crear hechos',
            'editar hechos',
            'ver vehiculos',
            'crear vehiculos',
            'editar vehiculos',
            'ver lesionados',
            'crear lesionados',
            'editar lesionados',
            'ver actividades',
            'crear actividades',
            'editar actividades',
        ];

        $delegadoPermissions = [
            'ver hechos',
            'crear hechos',
            'ver vehiculos',
            'crear vehiculos',
            'ver lesionados',
            'crear lesionados',
            'ver actividades',
            'crear actividades',
            'ver modulo examenes',
            'crear modulo examenes',
            'editar modulo examenes',
        ];

        $permissionsSinPuntosPrueba = array_values(array_diff($permissions, [
            'ver puntos licencias',
            'crear puntos licencias',
            'editar puntos licencias',
            'registrar infracciones puntos licencias',
            'acreditar capacitacion puntos licencias',
        ]));

        $roles = [
            // Superadmin: SIEMPRE TODO
            'Superadmin' => $permissions,

            'Administrador' => $permissionsSinPuntosPrueba,

            'Subdirector' => array_merge($operacionHechos, [
                'ver configuraciones',
                'eliminar hechos',
                'ver dictamenes',
                'crear dictamenes',
                'editar dictamenes',
                'ver directorio red apoyo',
                'ver operativos carreteras',
                'ver estadisticas carreteras',
                'ver estadisticas globales',
                'ver estadisticas actividades',
                'ver modulo examenes',
                'crear modulo examenes',
                'editar modulo examenes',
                'eliminar modulo examenes',
            ]),
            'Administrativo' => array_merge($operacionHechos, [
                'ver directorio red apoyo',
                'ver operativos carreteras',
                'ver estadisticas carreteras',
                'ver estadisticas globales',
                'ver estadisticas actividades',
                'ver modulo examenes',
                'crear modulo examenes',
                'editar modulo examenes',
            ]),
            'Agente Upec' => [
                'ver operativos carreteras',
                'crear operativos carreteras',
                'ver directorio red apoyo',
            ],
            'RT' => [
                'ver operativos carreteras',
                'editar operativos carreteras',
                'ver estadisticas carreteras',
                'ver directorio red apoyo',
            ],
            'Encargado de Destacamento' => [
                'ver operativos carreteras',
                'editar operativos carreteras',
                'ver estadisticas carreteras',
                'ver directorio red apoyo',
            ],
            'Delegado' => array_merge($delegadoPermissions, [
                'ver delegaciones',
                'ver directorio red apoyo',
            ]),
            'Empleado' => $operacionHechos,
            'Observador' => [
                'ver hechos',
                'ver actividades',
                'ver estadisticas globales',
                'ver estadisticas actividades',
            ],
            'Agente Vial' => [
                'ver operativos vialidades',
                'crear operativos vialidades',
            ],
            'Responsable de Turno' => [
                'ver operativos vialidades',
                'editar operativos vialidades',
            ],
        ];

        $roleUnidadIds = [
            'Delegado' => 2,
            'Agente Upec' => 4,
            'RT' => 4,
            'Encargado de Destacamento' => 4,
            'Agente Vial' => 5,
            'Responsable de Turno' => 5,
        ];

        DB::transaction(function () use ($roles, $roleUnidadIds) {

            foreach ($roles as $roleName => $rolePermissions) {
                $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

                if (Schema::hasColumn('roles', 'unidad_id') && array_key_exists($roleName, $roleUnidadIds)) {
                    $role->unidad_id = $roleUnidadIds[$roleName];
                    $role->save();
                }

                $permissionsToAssign = Permission::whereIn('name', $rolePermissions)->get();
                $role->syncPermissions($permissionsToAssign);
            }

            // ====== HARD RULE: el sistema no puede quedarse sin superadmins ======
            // Si ya existe al menos 1 usuario con rol Superadmin, ok.
            // Si NO existe, promovemos al primer usuario (por id) a Superadmin.
            $superadminRole = Role::where('name', 'Superadmin')->first();
            if ($superadminRole) {
                $count = User::role('Superadmin')->count();

                if ($count === 0) {
                    $firstUser = User::orderBy('id')->first();

                    if ($firstUser) {
                        $firstUser->assignRole('Superadmin');
                    }
                }
            }
        });
    }
}
