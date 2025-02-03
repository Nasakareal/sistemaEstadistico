<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run()
    {
        $user = User::firstOrCreate([
            'email' => 'admin@admin.com',
        ], [
            'name' => 'Mario Bautista',
            'password' => Hash::make('123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->assignRole('Administrador');
    }
}
