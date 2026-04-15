<?php

namespace App\Services\WhatsApp;

use App\Models\Hechos;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppRenderService
{
    public function renderDetalleHecho(Hechos $hecho): array
    {
        $detalle = $this->obtenerDetalleHecho($hecho);

        $bloques = [];

        $bloques[] = 'GUARDIA CIVIL';
        $bloques[] = $detalle['coordinacion'] ?? '';
        $bloques[] = $detalle['unidad'] ?? '';
        $bloques[] = $detalle['municipio'] ?? '';

        if (!empty($detalle['sector'])) {
            $bloques[] = $detalle['sector'];
        }

        $bloques[] = 'TEMA: ' . ($detalle['tema'] ?? 'HECHO DE TRÁNSITO');
        $bloques[] = $detalle['descripcion'] ?? '';

        if (!empty($detalle['vehiculos_texto'])) {
            $bloques[] = 'Lugar donde se encuentran:';
            $bloques[] = $detalle['vehiculos_texto'];
        }

        if (!empty($detalle['estado'])) {
            $bloques[] = 'Hecho ' . $detalle['estado'] . '.';
        }

        $ubicacionExtra = [];
        if (!empty($detalle['ubicacion'])) {
            $ubicacionExtra[] = 'Ubicación: ' . $detalle['ubicacion'];
        }
        if (!empty($detalle['google_maps'])) {
            $ubicacionExtra[] = 'Google Maps: ' . $detalle['google_maps'];
        }
        if (!empty($ubicacionExtra)) {
            $bloques[] = implode("\n", $ubicacionExtra);
        }

        if (!empty($detalle['informa'])) {
            $bloques[] = 'INFORMA ' . $detalle['informa'];
        }

        $bloques = array_values(array_filter($bloques, fn ($item) => $item !== null && trim((string) $item) !== ''));

        return [
            'text' => implode("\n\n", $bloques),
            'images' => $detalle['fotos'] ?? [],
        ];
    }

    protected function obtenerDetalleHecho(Hechos $hecho): array
    {
        $hecho->loadMissing(['vehiculos']);

        $ubicacionPartes = array_filter([
            $hecho->calle,
            $hecho->colonia ? 'col. ' . $hecho->colonia : null,
        ]);

        $descripcion = trim(implode(' ', array_filter([
            optional($hecho->fecha)->format('Y-m-d') ?: (string) $hecho->fecha,
            $this->formatearHora((string) $hecho->hora),
            'Hrs. Guardia Civil toma conocimiento en',
            implode(', ', $ubicacionPartes) . '.',
        ])));

        $lat = $hecho->lat;
        $lng = $hecho->lng;
        $googleMaps = null;
        $ubicacion = null;

        if (!is_null($lat) && !is_null($lng) && $lat !== '' && $lng !== '') {
            $ubicacion = "{$lat}, {$lng}";
            $googleMaps = "https://www.google.com/maps?q={$lat},{$lng}";
        }

        $vehiculosTexto = [];
        $fotosVehiculos = [];

        foreach (($hecho->vehiculos ?? []) as $index => $vehiculo) {
            $etiqueta = chr(65 + $index) . ')';

            $lineasVehiculo = [];
            $lineasVehiculo[] = 'VEHÍCULO ' . $etiqueta;
            $lineasVehiculo[] = $this->buildVehiculoDescripcion($vehiculo);

            $ocupantes = $this->buildVehiculoOcupantes($vehiculo);
            if ($ocupantes !== '') {
                $lineasVehiculo[] = $ocupantes;
            }

            $vehiculosTexto[] = implode("\n", array_filter($lineasVehiculo, fn ($item) => trim((string) $item) !== ''));
            $fotosVehiculos = array_merge($fotosVehiculos, $this->extraerUrlsDesdeCampo($vehiculo->fotos ?? null));
        }

        $fotos = array_values(array_unique(array_filter(array_merge(
            $this->extraerUrlsDesdeCampo($hecho->foto_lugar),
            $this->extraerUrlsDesdeCampo($hecho->foto_situacion),
            $fotosVehiculos
        ))));

        return [
            'coordinacion' => 'COORDINACION DEL AGRUPAMIENTO DE SEGURIDAD VIAL',
            'unidad' => 'UNIDAD DE ATENCIÓN A SINIESTROS',
            'municipio' => (string) ($hecho->municipio ?: 'MORELIA'),
            'sector' => $hecho->sector ? 'SECTOR ' . $hecho->sector : null,
            'tema' => 'HECHO DE TRÁNSITO CLASIFICADO COMO ' . mb_strtoupper((string) ($hecho->tipo_hecho ?: 'SIN CLASIFICACIÓN'), 'UTF-8'),
            'descripcion' => $descripcion,
            'vehiculos_texto' => implode("\n\n", $vehiculosTexto),
            'estado' => mb_strtoupper((string) ($hecho->situacion ?: 'SIN ESTADO'), 'UTF-8'),
            'ubicacion' => $ubicacion,
            'google_maps' => $googleMaps,
            'informa' => $hecho->unidad ? 'UNIDAD ' . $hecho->unidad : ($hecho->perito ?: null),
            'fotos' => $fotos,
        ];
    }

    protected function buildVehiculoDescripcion($vehiculo): string
    {
        $partes = [];

        $partes[] = 'De la marca ' . $this->valorONoEspecificado($vehiculo->marca ?? null);
        $partes[] = 'tipo ' . $this->valorONoEspecificado($vehiculo->tipo ?? null);

        if (!empty($vehiculo->linea)) {
            $partes[] = 'línea ' . trim((string) $vehiculo->linea);
        }

        if (!empty($vehiculo->color)) {
            $partes[] = 'color ' . trim((string) $vehiculo->color);
        }

        if (!empty($vehiculo->placas)) {
            $partes[] = 'placas ' . trim((string) $vehiculo->placas);
        }

        if (!empty($vehiculo->serie)) {
            $partes[] = 'NIV ' . trim((string) $vehiculo->serie);
        }

        return implode(', ', $partes) . '.';
    }

    protected function buildVehiculoOcupantes($vehiculo): string
    {
        $nombre = $this->firstFilled([
            $vehiculo->nombre_conductor ?? null,
            $vehiculo->conductor_nombre ?? null,
            $vehiculo->nombre_persona ?? null,
            $vehiculo->responsable ?? null,
            $vehiculo->propietario ?? null,
        ]);

        $edad = $this->firstFilled([
            $vehiculo->edad_conductor ?? null,
            $vehiculo->conductor_edad ?? null,
            $vehiculo->edad_persona ?? null,
            $vehiculo->edad ?? null,
        ]);

        if ($nombre === '') {
            return '';
        }

        $texto = 'Manifiesta viajar a bordo el C. ' . $nombre;

        if ($edad !== '') {
            $texto .= ' de ' . $edad . ' años';
        }

        return $texto . '.';
    }

    protected function firstFilled(array $values): string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    protected function formatearHora(string $hora): string
    {
        if ($hora === '') {
            return '';
        }

        return substr($hora, 0, 5);
    }

    protected function valorONoEspecificado(?string $valor): string
    {
        $valor = trim((string) $valor);

        return $valor !== '' ? $valor : 'NO ESPECIFICADO';
    }

    protected function extraerUrlsDesdeCampo($valor): array
    {
        if (empty($valor)) {
            return [];
        }

        if (is_array($valor)) {
            return collect($valor)
                ->flatMap(fn ($item) => $this->extraerUrlsDesdeCampo($item))
                ->filter()
                ->values()
                ->all();
        }

        if (is_string($valor)) {
            $trim = trim($valor);

            if ($trim === '') {
                return [];
            }

            $json = json_decode($trim, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->extraerUrlsDesdeCampo($json);
            }

            if (str_contains($trim, ',')) {
                return collect(explode(',', $trim))
                    ->map(fn ($item) => $this->pathToUrl($item))
                    ->filter()
                    ->values()
                    ->all();
            }

            return array_filter([$this->pathToUrl($trim)]);
        }

        return [];
    }

    protected function pathToUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        try {
            return url(Storage::url($path));
        } catch (\Throwable $e) {
            Log::warning('WA pathToUrl error', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
