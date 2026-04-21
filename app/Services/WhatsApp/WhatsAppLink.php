<?php

namespace App\Services\WhatsApp;

use App\Models\Hechos;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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

        $unidadId = (int) $hecho->unidad_org_id;

        $unidadTexto = match ($unidadId) {
            1 => 'UNIDAD DE ATENCIÓN A SINIESTROS',
            2 => 'UNIDAD DE DELEGACIONES',
            4 => 'UNIDAD DE PROTECCIÓN A CARRETERAS',
            default => DB::table('unidades')
                ->where('id', $hecho->unidad_org_id)
                ->value('nombre') ?: 'SIN DATO',
        };

        $lines[] = self::upper($unidadTexto);
        $lines[] = "";

        if ($unidadId === 1) {
            $lines[] = "MORELIA";
            $lines[] = "";

            if (!empty($hecho->sector)) {
                $lines[] = "SECTOR " . self::upper($hecho->sector);
                $lines[] = "";
            }
        } elseif ($unidadId === 2) {
            $delegacionNombre = null;

            if (!empty($hecho->delegacion_id)) {
                $delegacionNombre = DB::table('delegaciones')
                    ->where('id', $hecho->delegacion_id)
                    ->value('nombre');
            }

            if (!empty($delegacionNombre)) {
                $lines[] = "DELEGACIÓN " . self::upper($delegacionNombre);
                $lines[] = "";
            }
        } elseif ($unidadId === 4) {
            $destacamentoNombre = DB::table('destacamentos')
                ->where('clave', (string) $hecho->unidad)
                ->where('unidad_id', 4)
                ->value('nombre');

            if (!empty($destacamentoNombre)) {
                $lines[] = "DESTACAMENTO " . self::upper($destacamentoNombre);
                $lines[] = "";
            }
        }

        $tema = "TEMA: HECHO DE TRÁNSITO CLASIFICADO COMO " . self::upper($hecho->tipo_hecho);
        $lines[] = $tema;
        $lines[] = "";

        $fecha = !empty($hecho->fecha) ? \Carbon\Carbon::parse($hecho->fecha)->format('Y-m-d') : '';
        $hora = !empty($hecho->hora) ? substr((string) $hecho->hora, 0, 5) : '';
        $fechaHora = trim($fecha . ' ' . $hora);
        $ubic = trim((string) ($hecho->calle ?? ''));

        if (!empty($hecho->colonia)) {
            $ubic .= ', col. ' . trim((string) $hecho->colonia);
        }

        if ($ubic === '') {
            $ubic = $hecho->ubicacion_formateada ?: trim((string) ($hecho->entre_calles ?? ''));
        }

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
                $tipo = $v->tipo ?: "SIN DATO";
                $linea = $v->linea ?: "SIN DATO";
                $color = $v->color ?: "SIN DATO";
                $placas = $v->placas ?: "SIN PLACAS";
                $serie = $v->serie ?: "SIN DATO";

                $lines[] = "De la marca {$marca}, tipo {$tipo}, línea {$linea}, color {$color}, placas {$placas}, NIV {$serie}.";

                if ($v->conductores && $v->conductores->count() > 0) {
                    $c = $v->conductores->first();
                    $nombre = $c->nombre ?: "SIN DATO";
                    $edad = $c->edad ?: "S/E";
                    $lines[] = "Manifiesta viajar a bordo el C. {$nombre} de {$edad} años.";
                }

                $gruaVeh = isset($v->grua) ? trim((string) $v->grua) : '';
                $corralonVeh = isset($v->corralon) ? trim((string) $v->corralon) : '';

                if (self::hasValue($gruaVeh) && !self::isNA($gruaVeh)) {
                    $lines[] = "Grúa: " . self::upper($gruaVeh) . ".";
                }

                if (self::hasValue($corralonVeh) && !self::isNA($corralonVeh)) {
                    $lines[] = "Corralón: " . self::upper($corralonVeh) . ".";
                }

                $serv = DB::table('servicios')
                    ->where('vehiculo_id', $v->id)
                    ->orderByDesc('id')
                    ->first();

                if ($serv) {
                    $extra = [];

                    if (!empty($serv->grua_id)) {
                        $extra[] = "grua_id " . $serv->grua_id;
                    }

                    if (!empty($serv->tipo_vehiculo) && !self::isNA($serv->tipo_vehiculo)) {
                        $extra[] = "tipo " . self::upper((string) $serv->tipo_vehiculo);
                    }

                    if (!empty($serv->aseguradora) && !self::isNA($serv->aseguradora)) {
                        $extra[] = "aseguradora " . self::upper((string) $serv->aseguradora);
                    }

                    if (!empty($serv->descripcion) && !self::isNA($serv->descripcion)) {
                        $extra[] = "detalle " . self::upper((string) $serv->descripcion);
                    }

                    if (count($extra) > 0) {
                        $lines[] = "Servicio: " . implode(", ", $extra) . ".";
                    }
                }

                $letter++;
            }
        } else {
            $lines[] = "";
            $lines[] = "SIN DATOS DE VEHÍCULOS CAPTURADOS.";
        }

        if (!empty($hecho->responsable)) {
            $lines[] = "";
            $lines[] = "RESPONSABLE: " . self::upper($hecho->responsable) . ".";
        }

        $situacion = $hecho->situacion ?: "SIN DATO";
        $lines[] = "";
        $lines[] = "Hecho " . self::upper($situacion) . ".";

        if (method_exists($hecho, 'lesionados')) {
            $lesionados = $hecho->lesionados()->get();

            if ($lesionados->count() > 0) {
                $fallecidos = $lesionados->filter(function ($l) {
                    return self::upper($l->tipo_lesion) === 'FALLECIDO';
                });

                $lesionadosVivos = $lesionados->filter(function ($l) {
                    return self::upper($l->tipo_lesion) !== 'FALLECIDO';
                });

                if ($fallecidos->count() > 0) {
                    $lines[] = "";
                    $lines[] = "De este hecho de tránsito resultan fallecidos:";

                    foreach ($fallecidos as $l) {
                        $nombreL = trim((string) ($l->nombre ?: 'SIN DATO'));
                        $edadL = $l->edad ?: 'S/E';
                        $sexoL = self::upper($l->sexo ?: 'SIN DATO');
                        $paramedicoL = trim((string) ($l->paramedico ?: 'SIN DATO'));
                        $ambulanciaL = trim((string) ($l->ambulancia ?: 'SIN DATO'));
                        $observacionesL = trim((string) ($l->observaciones ?: ''));

                        $texto = "- {$nombreL}, de {$edadL} años, sexo {$sexoL}, el cual falleció en el lugar.";

                        if ($paramedicoL !== 'SIN DATO') {
                            $texto .= " Confirma el deceso el paramédico {$paramedicoL}";
                        }

                        if ($ambulanciaL !== 'SIN DATO') {
                            $texto .= ", a bordo de {$ambulanciaL}";
                        }

                        $texto .= ".";

                        if ($observacionesL !== '') {
                            $texto .= " Observaciones: {$observacionesL}.";
                        }

                        $lines[] = $texto;
                    }
                }

                if ($lesionadosVivos->count() > 0) {
                    $lines[] = "";
                    $lines[] = "De este hecho de tránsito resultan lesionados:";

                    foreach ($lesionadosVivos as $l) {
                        $nombreL = trim((string) ($l->nombre ?: 'SIN DATO'));
                        $edadL = $l->edad ?: 'S/E';
                        $sexoL = self::upper($l->sexo ?: 'SIN DATO');
                        $tipoLesionL = self::upper($l->tipo_lesion ?: 'SIN DATO');
                        $hospitalL = trim((string) ($l->hospital ?: ''));
                        $ambulanciaL = trim((string) ($l->ambulancia ?: ''));
                        $paramedicoL = trim((string) ($l->paramedico ?: ''));
                        $atencionSitioL = (int) ($l->atencion_en_sitio ?? 0);
                        $hospitalizadoL = (int) ($l->hospitalizado ?? 0);
                        $observacionesL = trim((string) ($l->observaciones ?: ''));

                        $texto = "- {$nombreL}, de {$edadL} años, sexo {$sexoL}, presenta lesión {$tipoLesionL}.";

                        if ($atencionSitioL === 1) {
                            $texto .= " Recibió atención en el sitio.";
                        }

                        if ($hospitalizadoL === 1 && $hospitalL !== '') {
                            $texto .= " Fue trasladado(a) al hospital {$hospitalL}";
                        } elseif ($hospitalizadoL === 1) {
                            $texto .= " Fue trasladado(a) a hospital";
                        }

                        if ($ambulanciaL !== '') {
                            if ($hospitalizadoL === 1) {
                                $texto .= " a bordo de la ambulancia {$ambulanciaL}";
                            } else {
                                $texto .= " Ambulancia: {$ambulanciaL}.";
                            }
                        }

                        if ($hospitalizadoL === 1) {
                            $texto .= ".";
                        }

                        if ($paramedicoL !== '') {
                            $texto .= " Paramédico: {$paramedicoL}.";
                        }

                        if ($observacionesL !== '') {
                            $texto .= " Observaciones: {$observacionesL}.";
                        }

                        $lines[] = $texto;
                    }
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

        if ($s === '') {
            return 'SIN DATO';
        }

        return Str::upper($s);
    }

    private static function hasValue(?string $s): bool
    {
        return trim((string) $s) !== '';
    }

    private static function isNA(?string $s): bool
    {
        $s = Str::upper(trim((string) $s));

        return $s === 'N/A' || $s === 'NA' || $s === 'NO' || $s === 'NO SE UTILIZA' || $s === 'SIN DATO';
    }
}
