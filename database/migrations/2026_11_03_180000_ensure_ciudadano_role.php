<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class EnsureCiudadanoRole extends Migration
{
    public function up()
    {
        $exists = DB::table('roles')
            ->where('name', 'Ciudadano')
            ->where('guard_name', 'web')
            ->exists();

        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'Ciudadano',
                'guard_name' => 'web',
                'unidad_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        // Accounts may already depend on this role. Never remove it during a
        // rollback because that would silently orphan public users.
    }
}
