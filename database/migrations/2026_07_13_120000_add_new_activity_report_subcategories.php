<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const SUBCATEGORIES = [
        'MONITOREOS' => [
            'CARRETERAS',
            'CASETAS',
        ],
        'ABANDERAMIENTOS' => [
            'BLOQUEO CARRETERO',
        ],
        'DISPOSITIVOS DE SEGURIDAD VIAL' => [
            'RESGUARDO DE VEHÍCULO POR OBSTRUCCIÓN O ABANDONO',
        ],
        'OPERATIVOS' => [
            'ALCOHOLIMETRÍA',
            'CONDUCE CON LEGALIDAD',
        ],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('actividad_categorias') || !Schema::hasTable('actividad_subcategorias')) {
            return;
        }

        DB::transaction(function (): void {
            foreach (self::SUBCATEGORIES as $categoryName => $subcategories) {
                $category = DB::table('actividad_categorias')
                    ->where('slug', Str::slug($categoryName))
                    ->first(['id']);

                if (!$category) {
                    continue;
                }

                foreach ($subcategories as $subcategoryName) {
                    DB::table('actividad_subcategorias')->updateOrInsert(
                        [
                            'actividad_categoria_id' => $category->id,
                            'slug' => Str::slug($subcategoryName),
                        ],
                        [
                            'nombre' => $subcategoryName,
                            'activo' => true,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }
        });
    }

    public function down(): void
    {
        // Se conservan para no invalidar actividades que ya las hayan utilizado.
    }
};
