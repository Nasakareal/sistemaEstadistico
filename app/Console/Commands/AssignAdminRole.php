<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class AssignAdminRole extends Command
{
    protected $signature = 'assign:admin-role';
    protected $description = 'Asigna el rol de Administrador a un usuario existente';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $user = User::where('email', 'admin@admin.com')->first();

        if (!$user) {
            $this->error('El usuario con el correo admin@admin.com no existe.');
            return;
        }

        $user->assignRole('Administrador');
        $this->info('Rol de Administrador asignado correctamente al usuario.');
    }
}
