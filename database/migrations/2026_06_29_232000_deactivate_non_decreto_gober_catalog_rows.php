<?php

use App\Support\DecretoGoberLicenciaPuntoCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'licencia_punto_infracciones';
    private const OPERATIVO_SIN_LICENCIA = 'OP_CL_SIN_LICENCIA_SIN_HABILITADO';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        $codigosVigentes = array_merge(
            [self::OPERATIVO_SIN_LICENCIA],
            array_column(DecretoGoberLicenciaPuntoCatalog::rows(), 'codigo')
        );

        DB::table(self::TABLE)
            ->whereNotIn('codigo', $codigosVigentes)
            ->update([
                'activa' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No se reactivan filas anteriores porque el catalogo vigente debe venir del Decreto Gober.docx.
    }
};
