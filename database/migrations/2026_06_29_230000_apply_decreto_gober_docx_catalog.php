<?php

use App\Support\DecretoGoberLicenciaPuntoCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'licencia_punto_infracciones';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        $this->ensureColumns();
        DecretoGoberLicenciaPuntoCatalog::assertSourceCoversRows(public_path(DecretoGoberLicenciaPuntoCatalog::SOURCE_FILENAME));

        $now = now();
        $rows = DecretoGoberLicenciaPuntoCatalog::rows();
        $codigosDecreto = array_column($rows, 'codigo');

        DB::table(self::TABLE)
            ->whereIn('articulo', DecretoGoberLicenciaPuntoCatalog::affectedArticles())
            ->whereNotIn('codigo', $codigosDecreto)
            ->update([
                'activa' => false,
                'updated_at' => $now,
            ]);

        DB::table(self::TABLE)
            ->whereIn('codigo', [
                'EXCESO_VELOCIDAD',
                'CELULAR_CONDUCIR',
                'SEMAFORO_ROJO',
                'ART419_I_ABDE_SEGURIDAD',
                'ART419_FII_I_MOTO_LUCES_CASCO_DECRETO',
                'ART419_FII_I_MOTO_PASAJEROS_CASCO_DECRETO',
                'ART419_FII_IA_C_MOTO_LUCES_CASCO_DECRETO',
                'ART419_FII_IB_D_MOTO_PASAJEROS_CASCO_DECRETO',
            ])
            ->update([
                'activa' => false,
                'updated_at' => $now,
            ]);

        foreach ($rows as $row) {
            $exists = DB::table(self::TABLE)->where('codigo', $row['codigo'])->exists();

            DB::table(self::TABLE)->updateOrInsert(
                ['codigo' => $row['codigo']],
                array_merge($row, [
                    'updated_at' => $now,
                ], $exists ? [] : ['created_at' => $now])
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        DB::table(self::TABLE)
            ->whereIn('codigo', array_column(DecretoGoberLicenciaPuntoCatalog::rows(), 'codigo'))
            ->update([
                'activa' => false,
                'updated_at' => now(),
            ]);
    }

    private function ensureColumns(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (!Schema::hasColumn(self::TABLE, 'articulo')) {
                $table->string('articulo', 20)->nullable()->after('nombre')->index();
            }

            if (!Schema::hasColumn(self::TABLE, 'fraccion')) {
                $table->string('fraccion', 20)->nullable()->after('articulo')->index();
            }

            if (!Schema::hasColumn(self::TABLE, 'inciso')) {
                $table->string('inciso', 20)->nullable()->after('fraccion')->index();
            }

            if (!Schema::hasColumn(self::TABLE, 'ambito_vehiculo')) {
                $table->string('ambito_vehiculo', 50)->nullable()->after('inciso')->index();
            }

            if (!Schema::hasColumn(self::TABLE, 'multa_uma_min')) {
                $table->unsignedSmallInteger('multa_uma_min')->nullable()->after('puntos');
            }

            if (!Schema::hasColumn(self::TABLE, 'multa_uma_max')) {
                $table->unsignedSmallInteger('multa_uma_max')->nullable()->after('multa_uma_min');
            }

            if (!Schema::hasColumn(self::TABLE, 'amonestacion')) {
                $table->boolean('amonestacion')->default(false)->after('multa_uma_max')->index();
            }

            if (!Schema::hasColumn(self::TABLE, 'arresto_persona')) {
                $table->boolean('arresto_persona')->default(false)->after('amonestacion')->index();
            }

            if (!Schema::hasColumn(self::TABLE, 'suspension_licencia')) {
                $table->boolean('suspension_licencia')->default(false)->after('arresto_persona')->index();
            }

            if (!Schema::hasColumn(self::TABLE, 'cancelacion_licencia')) {
                $table->boolean('cancelacion_licencia')->default(false)->after('suspension_licencia')->index();
            }

            if (!Schema::hasColumn(self::TABLE, 'deposito_si_sin_persona_habilitada')) {
                $table->boolean('deposito_si_sin_persona_habilitada')->default(false)->after('cancelacion_licencia');
            }

            if (!Schema::hasColumn(self::TABLE, 'retencion_vehiculo')) {
                $table->boolean('retencion_vehiculo')->default(false)->after('deposito_si_sin_persona_habilitada');
            }
        });
    }
};
