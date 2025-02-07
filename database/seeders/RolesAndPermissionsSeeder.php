<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
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

            // Grúas
            'ver gruas',
            'crear gruas',
            'editar gruas',
            'eliminar gruas',

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
        ];

        // Crear permisos si no existen
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Definición de roles y permisos asignados
        $roles = [
            'Administrador' => $permissions,
            'Subdirector' => [
                'ver configuraciones',
                'ver hechos',
                'crear hechos',
                'editar hechos',
                'eliminar hechos',
                'ver dictamenes',
                'crear dictamenes',
                'editar dictamenes',
            ],
            'Empleado' => [
                'ver hechos',
                'crear hechos',
                'editar hechos',
            ],
            'Observador' => [
                'ver hechos',
            ],
        ];

        // Crear roles y asignar permisos
        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            // Obtener permisos y sincronizarlos con el rol
            $permissionsToAssign = Permission::whereIn('name', $rolePermissions)->get();
            $role->syncPermissions($permissionsToAssign);
        }
    }
}
