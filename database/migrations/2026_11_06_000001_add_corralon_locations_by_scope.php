<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCorralonLocationsByScope extends Migration
{
    public function up()
    {
        Schema::table('unidad_grua', function (Blueprint $table) {
            $table->text('ubicacion_corralon')->nullable()->after('grua_id');
        });

        Schema::table('delegacion_grua', function (Blueprint $table) {
            $table->text('ubicacion_corralon')->nullable()->after('grua_id');
        });

        Schema::table('conduce_legalidad_vehiculos', function (Blueprint $table) {
            $table->text('corralon_direccion')->nullable()->after('corralon');
        });

        DB::table('gruas')
            ->whereNotNull('ubicacion_corralon')
            ->orderBy('id')
            ->select(['id', 'ubicacion_corralon'])
            ->chunkById(200, function ($gruas) {
                foreach ($gruas as $grua) {
                    DB::table('unidad_grua')
                        ->where('grua_id', $grua->id)
                        ->whereNull('ubicacion_corralon')
                        ->update(['ubicacion_corralon' => $grua->ubicacion_corralon]);

                    DB::table('delegacion_grua')
                        ->where('grua_id', $grua->id)
                        ->whereNull('ubicacion_corralon')
                        ->update(['ubicacion_corralon' => $grua->ubicacion_corralon]);
                }
            });

        DB::table('conduce_legalidad_vehiculos')
            ->where(function ($query) {
                $query->whereNotNull('corralon_id')->orWhereNotNull('grua_id');
            })
            ->orderBy('id')
            ->select([
                'id',
                'grua_id',
                'corralon_id',
                'servicio_unidad_id',
                'servicio_delegacion_id',
            ])
            ->chunkById(200, function ($vehiculos) {
                foreach ($vehiculos as $vehiculo) {
                    $gruaId = (int) ($vehiculo->corralon_id ?: $vehiculo->grua_id);
                    $unidadId = (int) $vehiculo->servicio_unidad_id;
                    $delegacionId = (int) $vehiculo->servicio_delegacion_id;
                    $ubicacion = null;

                    if ($unidadId === 2 && $delegacionId > 0) {
                        $ubicacion = DB::table('delegacion_grua')
                            ->where('grua_id', $gruaId)
                            ->where('delegacion_id', $delegacionId)
                            ->value('ubicacion_corralon');
                    } else {
                        $unidadConsulta = $unidadId === 5 ? 1 : $unidadId;
                        if ($unidadConsulta > 0) {
                            $ubicacion = DB::table('unidad_grua')
                                ->where('grua_id', $gruaId)
                                ->where('unidad_id', $unidadConsulta)
                                ->value('ubicacion_corralon');
                        }
                    }

                    if (!$ubicacion) {
                        $grua = DB::table('gruas')->where('id', $gruaId)->first([
                            'ubicacion_corralon',
                            'direccion',
                        ]);
                        $ubicacion = $grua
                            ? ($grua->ubicacion_corralon ?: $grua->direccion)
                            : null;
                    }

                    if ($ubicacion) {
                        DB::table('conduce_legalidad_vehiculos')
                            ->where('id', $vehiculo->id)
                            ->update(['corralon_direccion' => $ubicacion]);
                    }
                }
            });
    }

    public function down()
    {
        Schema::table('conduce_legalidad_vehiculos', function (Blueprint $table) {
            $table->dropColumn('corralon_direccion');
        });

        Schema::table('delegacion_grua', function (Blueprint $table) {
            $table->dropColumn('ubicacion_corralon');
        });

        Schema::table('unidad_grua', function (Blueprint $table) {
            $table->dropColumn('ubicacion_corralon');
        });
    }
}
