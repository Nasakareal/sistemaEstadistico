<?php

namespace App\Services\WhatsApp;

use App\Models\Hechos;
use Illuminate\Support\Str;

class WhatsAppLink
{
    public static function textForHecho(Hechos $hecho): string
    {
        return self::buildCard($hecho);
    }

    private static function buildCard(Hechos $hecho): string
    {
        $lines = [];

        $lines[] = "GUARDIA CIVIL";
        $lines[] = "";
        $lines[] = "COORDINACION DEL AGRUPAMIENTO DE SEGURIDAD VIAL";
        $lines[] = "";
        $lines[] = "UNIDAD DE ATENCIÓN A SINIESTROS";
        $lines[] = "";
        $lines[] = "MORELIA";
        $lines[] = "";
        $lines[] = "SECTOR " . self::upper($hecho->sector);
        $lines[] = "";

        $tema = "TEMA: HECHO DE TRÁNSITO CLASIFICADO COMO " . self::upper($hecho->tipo_hecho);
        if (!empty($hecho->causas)) $tema .= " POR " . self::upper($hecho->causas);
        $lines[] = $tema;
        $lines[] = "";

        $fechaHora = trim(($hecho->fecha ?? '') . ' ' . ($hecho->hora ?? ''));
        $ubic = $hecho->ubicacion_formateada ?: trim(($hecho->calle ?? '') . " " . ($hecho->entre_calles ?? ''));
        $lines[] = "{$fechaHora} Hrs. Guardia Civil toma conocimiento en {$ubic}.";
        $lines[] = "";
        $lines[] = "Lugar donde se encuentran:";

        $vehiculos = $hecho->vehiculos()->with('conductores')->get();

        if ($vehiculos->count() > 0) {
            $letter = 'A';

            foreach ($vehiculos as $v) {
                $lines[] = "";
                $lines[] = "VEHÍCULO {$letter})";

                $marca = $v->marca ?: "NO VISIBLE";
                $tipo  = $v->tipo ?: "SIN DATO";
                $linea = $v->linea ?: "SIN DATO";
                $color = $v->color ?: "SIN DATO";
                $placas = $v->placas ?: "SIN PLACAS";
                $serie  = $v->serie ?: "SIN DATO";

                $lines[] = "De la marca {$marca}, tipo {$tipo}, línea {$linea}, color {$color}, placas {$placas}, NIV {$serie}.";

                if ($v->conductores && $v->conductores->count() > 0) {
                    $c = $v->conductores->first();
                    $nombre = $c->nombre ?: "SIN DATO";
                    $edad   = $c->edad ?: "S/E";
                    $lines[] = "Manifiesta viajar a bordo el C. {$nombre} de {$edad} años.";
                }

                $letter++;
            }
        } else {
            $lines[] = "";
            $lines[] = "SIN DATOS DE VEHÍCULOS CAPTURADOS.";
        }

        $situacion = $hecho->situacion ?: "SIN DATO";
        $lines[] = "";
        $lines[] = "Hecho " . self::upper($situacion) . ".";

        if (method_exists($hecho, 'lesionados')) {
            $lesionados = $hecho->lesionados()->get();
            if ($lesionados->count() > 0) {
                $lines[] = "";
                $lines[] = "De este hecho de tránsito resultan lesionados:";
                foreach ($lesionados as $l) {
                    $nombreL = $l->nombre ?: "SIN DATO";
                    $edadL   = $l->edad ?: "S/E";
                    $lines[] = "- {$nombreL} de {$edadL} años.";
                }
            }
        }

        if (!is_null($hecho->lat) && !is_null($hecho->lng)) {
            $lat = $hecho->lat;
            $lng = $hecho->lng;
            $lines[] = "";
            $lines[] = "Ubicación: {$lat}, {$lng}";
            $lines[] = "Google Maps: https://www.google.com/maps?q={$lat},{$lng}";
        }

        if (!empty($hecho->unidad)) {
            $lines[] = "";
            $lines[] = "INFORMA UNIDAD {$hecho->unidad}";
        }

        return implode("\n", $lines);
    }

    private static function upper(?string $s): string
    {
        $s = trim((string) $s);
        if ($s === '') return 'SIN DATO';
        return Str::upper($s);
    }
}
