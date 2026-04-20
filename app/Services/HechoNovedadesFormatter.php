<?php

namespace App\Services;

use Illuminate\Support\Collection;

class HechoNovedadesFormatter
{
    public function contarVictimas($hecho): array
    {
        $lesionados = 0;
        $fallecidos = 0;

        foreach (($hecho->lesionados ?? collect()) as $lesionado) {
            if ($this->esFallecido($lesionado)) {
                $fallecidos++;
            } else {
                $lesionados++;
            }
        }

        return [
            'lesionados' => $lesionados,
            'fallecidos' => $fallecidos,
            'total' => $lesionados + $fallecidos,
        ];
    }

    public function lesionadosVivos($hecho): Collection
    {
        return collect($hecho->lesionados ?? [])->filter(function ($lesionado) {
            return !$this->esFallecido($lesionado);
        })->values();
    }

    public function fallecidos($hecho): Collection
    {
        return collect($hecho->lesionados ?? [])->filter(function ($lesionado) {
            return $this->esFallecido($lesionado);
        })->values();
    }

    public function esFallecido($lesionado): bool
    {
        return $this->normalizar((string) ($lesionado->tipo_lesion ?? '')) === 'FALLECIDO';
    }

    public function resultadoVictimasTexto($hecho): string
    {
        $conteo = $this->contarVictimas($hecho);
        $partes = [];

        if ($conteo['lesionados'] > 0) {
            $partes[] = $this->frasePersonas($conteo['lesionados'], 'LESIONADA', 'LESIONADAS');
        }

        if ($conteo['fallecidos'] > 0) {
            $partes[] = $this->frasePersonas($conteo['fallecidos'], 'FALLECIDA', 'FALLECIDAS');
        }

        if (empty($partes)) {
            return 'SIN LESIONADOS';
        }

        return 'CON RESULTADO DE ' . implode(' Y ', $partes);
    }

    public function tituloTipoHecho(?string $tipo): string
    {
        $tipo = mb_strtoupper(trim((string) $tipo), 'UTF-8');

        if ($tipo === '') {
            return 'HECHO DE TRÁNSITO';
        }

        $tipo = preg_replace('/^COLISI[ÓO]N\s+/u', 'CHOQUE ', $tipo);

        return trim((string) $tipo);
    }

    public function montoDanos($hecho): float
    {
        return $this->montoDanosVehiculos($hecho) + $this->montoDanosPatrimoniales($hecho);
    }

    public function montoDanosVehiculos($hecho): float
    {
        $total = 0.0;

        foreach (($hecho->vehiculos ?? collect()) as $vehiculo) {
            $total += (float) ($vehiculo->monto_danos ?? 0);
        }

        return $total;
    }

    public function montoDanosPatrimoniales($hecho): float
    {
        return (float) ($hecho->monto_danos_patrimoniales ?? 0);
    }

    public function descripcionHecho($hecho): string
    {
        $vehiculos = $hecho->vehiculos ?? collect();
        $texto = 'Se atiende un hecho de tránsito el día '
            . $this->valorFecha($hecho->fecha ?? null)
            . ' clasificado como '
            . $this->tituloTipoHecho($hecho->tipo_hecho ?? null)
            . ' (' . $this->resultadoVictimasTexto($hecho) . ')'
            . ' SECTOR ' . mb_strtoupper(trim((string) ($hecho->sector ?? 'SIN SECTOR')), 'UTF-8')
            . '.- ';

        $texto .= 'A las ' . $this->valorHora($hecho->hora ?? null)
            . ' horas en ' . trim((string) ($hecho->calle ?? 'SIN CALLE'));

        if (!empty($hecho->colonia)) {
            $texto .= ', de la colonia ' . trim((string) $hecho->colonia);
        }

        $texto .= ', lugar donde ';

        if ($vehiculos->count() > 0) {
            $texto .= 'participaron: ' . $this->vehiculosTexto($hecho) . ' ';
        } else {
            $texto .= 'no se encontró información de vehículos. ';
        }

        foreach ($this->lineasVictimas($hecho) as $linea) {
            $texto .= $linea . ' ';
        }

        if (!empty($hecho->perito)) {
            $texto .= 'Intervino el perito ' . trim((string) $hecho->perito) . '. ';
        }

        $texto .= 'DAÑOS APROXIMADOS $ ' . number_format($this->montoDanos($hecho), 2, '.', ',') . '.';

        return trim($texto);
    }

    public function vehiculosTexto($hecho): string
    {
        $lineas = [];
        $letra = 'A';

        foreach (($hecho->vehiculos ?? collect()) as $vehiculo) {
            $partes = ["AUTOMÓVIL ({$letra})"];

            foreach ([
                'marca' => 'Marca',
                'modelo' => 'Modelo',
                'tipo' => 'Tipo',
                'linea' => 'Línea',
                'color' => 'Color',
                'placas' => 'Placas',
                'serie' => 'Serie',
            ] as $campo => $etiqueta) {
                if (!empty($vehiculo->{$campo})) {
                    $partes[] = $etiqueta . ' ' . trim((string) $vehiculo->{$campo});
                }
            }

            $lineas[] = implode(', ', $partes);
            $letra++;
        }

        return implode('; ', $lineas);
    }

    public function lineasVictimas($hecho): array
    {
        $lineas = [];

        foreach ($this->lesionadosVivos($hecho) as $index => $lesionado) {
            $lineas[] = $this->lineaVictima($lesionado, 'Lesionado ' . ($index + 1));
        }

        foreach ($this->fallecidos($hecho) as $index => $lesionado) {
            $lineas[] = $this->lineaVictima($lesionado, 'Fallecido ' . ($index + 1));
        }

        return $lineas;
    }

    public function normalizar(?string $value): string
    {
        $value = mb_strtoupper(trim((string) $value), 'UTF-8');
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        if ($ascii !== false) {
            $value = $ascii;
        }

        return preg_replace('/[^A-Z0-9]+/', '', $value) ?: '';
    }

    protected function lineaVictima($lesionado, string $prefijo): string
    {
        $linea = "{$prefijo}: ";

        if (!empty($lesionado->nombre)) {
            $linea .= 'C. ' . trim((string) $lesionado->nombre);
        } else {
            $linea .= 'SIN NOMBRE';
        }

        if (!empty($lesionado->edad)) {
            $linea .= ', de ' . trim((string) $lesionado->edad) . ' años';
        }

        if (!empty($lesionado->sexo)) {
            $linea .= ', sexo ' . trim((string) $lesionado->sexo);
        }

        if (!empty($lesionado->tipo_lesion)) {
            $linea .= ', tipo ' . trim((string) $lesionado->tipo_lesion);
        }

        if (!empty($lesionado->hospitalizado)) {
            $linea .= ', fue hospitalizado';

            if (!empty($lesionado->hospital)) {
                $linea .= ' en ' . trim((string) $lesionado->hospital);
            }
        }

        if (!empty($lesionado->ambulancia)) {
            $linea .= ', trasladado por la unidad ' . trim((string) $lesionado->ambulancia);
        }

        if (!empty($lesionado->paramedico)) {
            $linea .= ', atendido por el paramédico ' . trim((string) $lesionado->paramedico);
        }

        if (!empty($lesionado->observaciones)) {
            $linea .= ', observaciones: ' . trim((string) $lesionado->observaciones);
        }

        return $linea . '.';
    }

    protected function frasePersonas(int $cantidad, string $singular, string $plural): string
    {
        if ($cantidad === 1) {
            return 'UNA PERSONA ' . $singular;
        }

        return $cantidad . ' PERSONAS ' . $plural;
    }

    protected function valorFecha($fecha): string
    {
        if ($fecha instanceof \DateTimeInterface) {
            return $fecha->format('d/m/Y');
        }

        $fecha = trim((string) $fecha);

        return $fecha !== '' ? substr($fecha, 0, 10) : 'SIN FECHA';
    }

    protected function valorHora($hora): string
    {
        if ($hora instanceof \DateTimeInterface) {
            return $hora->format('H:i');
        }

        $hora = trim((string) $hora);

        return $hora !== '' ? substr($hora, 0, 5) : 'SIN HORA';
    }
}
