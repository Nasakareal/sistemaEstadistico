<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Collection;

class ChoquesDiariosInegiController extends ChoquesDiariosController
{
    protected function formatearHecho($hecho)
    {
        $base = parent::formatearHecho($hecho);
        $vehiculos = $this->vehiculosDelHecho($hecho->id);
        $lesionados = $this->lesionadosDelHecho($hecho->id);

        return array_merge($base, [
            'CODIGO_POSTAL' => $this->valorLimpio($hecho->codigo_postal ?? null),
            'CONDMUERTO' => $this->conteoVictimas($lesionados, 'COND', true),
            'CONDHERIDO' => $this->conteoVictimas($lesionados, 'COND', false),
            'PASAMUERTO' => $this->conteoVictimas($lesionados, 'PASA', true),
            'PASAHERIDO' => $this->conteoVictimas($lesionados, 'PASA', false),
            'PEATMUERTO' => $this->conteoVictimas($lesionados, 'PEAT', true),
            'PEATHERIDO' => $this->conteoVictimas($lesionados, 'PEAT', false),
            'CICLMUERTO' => $this->conteoVictimas($lesionados, 'CICL', true),
            'CICLHERIDO' => $this->conteoVictimas($lesionados, 'CICL', false),
            'OTROMUERTO' => $this->conteoVictimas($lesionados, 'OTRO', true),
            'OTROHERIDO' => $this->conteoVictimas($lesionados, 'OTRO', false),
            'VEHICULOS' => $this->montoVehiculos($vehiculos),
            'PROPPARTIC' => $this->montoPropiedadParticular($hecho),
        ]);
    }

    private function conteoVictimas(Collection $lesionados, string $categoria, bool $fallecido): int
    {
        return $lesionados
            ->filter(function ($lesionado) use ($categoria, $fallecido) {
                return $this->categoriaVictimaInegi($lesionado->tipo_victima ?? null) === $categoria
                    && $this->esFallecido($lesionado->tipo_lesion ?? null) === $fallecido;
            })
            ->count();
    }

    private function categoriaVictimaInegi($tipoVictima): string
    {
        $tipo = $this->normalizarTexto($tipoVictima);

        if ($tipo === 'CONDUCTOR' || $tipo === 'MOTOCICLISTA') {
            return 'COND';
        }

        if ($tipo === 'PASAJERO') {
            return 'PASA';
        }

        if ($tipo === 'PEATON') {
            return 'PEAT';
        }

        if ($tipo === 'CICLISTA') {
            return 'CICL';
        }

        return 'OTRO';
    }

    private function esFallecido($tipoLesion): bool
    {
        return $this->normalizarTexto($tipoLesion) === 'FALLECIDO';
    }

    private function montoVehiculos(Collection $vehiculos): float
    {
        return round($vehiculos->sum(function ($vehiculo) {
            return $this->monto($vehiculo->monto_danos ?? null);
        }), 2);
    }

    private function montoPropiedadParticular($hecho): float
    {
        return round($this->monto($hecho->monto_danos_patrimoniales ?? null), 2);
    }

    private function monto($valor): float
    {
        return is_numeric($valor) ? (float) $valor : 0.0;
    }

    private function normalizarTexto($valor): string
    {
        $texto = mb_strtoupper(trim((string) $valor), 'UTF-8');

        return strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
        ]);
    }
}
