<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ScopeExclusiveRolesToUnidades extends Migration
{
    private const ROLE_UNITS = [
        'Perito' => 1,
        'Jefe de Grupo' => 1,
        'Policía' => 2,
        'Policia' => 2,
        'Delegado' => 2,
        'Agente Upec' => 4,
        'Agente Vial' => 5,
        'Responsable de Turno' => 5,
    ];

    public function up()
    {
        foreach (self::ROLE_UNITS as $roleName => $unidadId) {
            DB::table('roles')
                ->where('name', $roleName)
                ->update(['unidad_id' => $unidadId]);
        }
    }

    public function down()
    {
        // No revertimos datos para no convertir en globales roles ya corregidos.
    }
}
