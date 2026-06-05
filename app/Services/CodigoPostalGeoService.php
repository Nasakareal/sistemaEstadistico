<?php

namespace App\Services;

class CodigoPostalGeoService
{
    private const SHAPE_TYPE_NULL = 0;
    private const SHAPE_TYPE_POLYGON = 5;
    private const SHAPE_TYPE_POLYGON_Z = 15;

    private const EARTH_SEMI_MAJOR = 6378137.0;
    private const INVERSE_FLATTENING = 298.257223563;
    private const STANDARD_PARALLEL_1 = 17.5;
    private const STANDARD_PARALLEL_2 = 29.5;
    private const LATITUDE_OF_ORIGIN = 12.0;
    private const CENTRAL_MERIDIAN = -102.0;
    private const FALSE_EASTING = 2500000.0;
    private const FALSE_NORTHING = 0.0;

    private $shpPath;
    private $dbfPath;

    public function __construct(?string $shpPath = null, ?string $dbfPath = null)
    {
        $basePath = $this->defaultBasePath();

        $this->shpPath = $shpPath ?: $basePath . DIRECTORY_SEPARATOR . 'CP_Mich.shp';
        $this->dbfPath = $dbfPath ?: $basePath . DIRECTORY_SEPARATOR . 'CP_Mich.dbf';
    }

    public function resolver($lat, $lng): ?string
    {
        if (!$this->coordenadasValidas($lat, $lng)) {
            return null;
        }

        if (!is_file($this->shpPath) || !is_file($this->dbfPath)) {
            return null;
        }

        $codigosPostales = $this->leerCodigosPostales();

        if (empty($codigosPostales)) {
            return null;
        }

        [$x, $y] = $this->proyectarALambert((float) $lat, (float) $lng);

        return $this->buscarEnShapefile($x, $y, $codigosPostales);
    }

    private function coordenadasValidas($lat, $lng): bool
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return false;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        return $lat >= -90.0 && $lat <= 90.0 && $lng >= -180.0 && $lng <= 180.0;
    }

    private function defaultBasePath(): string
    {
        if (function_exists('storage_path')) {
            try {
                return storage_path('app/geodata');
            } catch (\Throwable $e) {
                // Las pruebas unitarias pueden cargar helpers de Laravel sin bootstrapping de la app.
            }
        }

        return dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'app'
            . DIRECTORY_SEPARATOR . 'geodata';
    }

    private function leerCodigosPostales(): array
    {
        $contenido = @file_get_contents($this->dbfPath);

        if ($contenido === false || strlen($contenido) < 65) {
            return [];
        }

        $totalRegistros = $this->uint32Le($contenido, 4);
        $headerLength = $this->uint16Le($contenido, 8);
        $recordLength = $this->uint16Le($contenido, 10);
        $fieldLength = ord($contenido[48] ?? "\0");

        if ($totalRegistros <= 0 || $headerLength <= 0 || $recordLength <= 1 || $fieldLength <= 0) {
            return [];
        }

        $codigos = [];

        for ($i = 0; $i < $totalRegistros; $i++) {
            $offset = $headerLength + ($i * $recordLength);

            if ($offset + $recordLength > strlen($contenido)) {
                break;
            }

            if (($contenido[$offset] ?? '*') === '*') {
                $codigos[$i] = null;
                continue;
            }

            $codigos[$i] = trim(substr($contenido, $offset + 1, $fieldLength)) ?: null;
        }

        return $codigos;
    }

    private function buscarEnShapefile(float $x, float $y, array $codigosPostales): ?string
    {
        $handle = @fopen($this->shpPath, 'rb');

        if (!$handle) {
            return null;
        }

        fseek($handle, 100);
        $indice = 0;

        while (!feof($handle)) {
            $recordHeader = fread($handle, 8);

            if ($recordHeader === false || strlen($recordHeader) < 8) {
                break;
            }

            $contentLength = $this->uint32Be($recordHeader, 4) * 2;

            if ($contentLength < 4) {
                break;
            }

            $shapeHeaderLength = min(44, $contentLength);
            $shapeHeader = fread($handle, $shapeHeaderLength);

            if ($shapeHeader === false || strlen($shapeHeader) < $shapeHeaderLength) {
                break;
            }

            $shapeType = $this->int32Le($shapeHeader, 0);

            if ($shapeType === self::SHAPE_TYPE_NULL) {
                $this->skipBytes($handle, $contentLength - $shapeHeaderLength);
                $indice++;
                continue;
            }

            if (!in_array($shapeType, [self::SHAPE_TYPE_POLYGON, self::SHAPE_TYPE_POLYGON_Z], true) || strlen($shapeHeader) < 44) {
                $this->skipBytes($handle, $contentLength - $shapeHeaderLength);
                $indice++;
                continue;
            }

            $xmin = $this->doubleLe($shapeHeader, 4);
            $ymin = $this->doubleLe($shapeHeader, 12);
            $xmax = $this->doubleLe($shapeHeader, 20);
            $ymax = $this->doubleLe($shapeHeader, 28);

            if ($x < $xmin || $x > $xmax || $y < $ymin || $y > $ymax) {
                $this->skipBytes($handle, $contentLength - $shapeHeaderLength);
                $indice++;
                continue;
            }

            $restante = $contentLength - $shapeHeaderLength;
            $contenido = $shapeHeader . ($restante > 0 ? fread($handle, $restante) : '');

            if ($this->puntoDentroDePoligono($contenido, $x, $y)) {
                fclose($handle);
                return $codigosPostales[$indice] ?? null;
            }

            $indice++;
        }

        fclose($handle);

        return null;
    }

    private function skipBytes($handle, int $bytes): void
    {
        if ($bytes > 0) {
            fseek($handle, $bytes, SEEK_CUR);
        }
    }

    private function puntoDentroDePoligono(string $contenido, float $x, float $y): bool
    {
        if (strlen($contenido) < 44) {
            return false;
        }

        $numParts = $this->int32Le($contenido, 36);
        $numPoints = $this->int32Le($contenido, 40);

        if ($numParts <= 0 || $numPoints <= 0) {
            return false;
        }

        $partsOffset = 44;
        $pointsOffset = $partsOffset + ($numParts * 4);

        if (strlen($contenido) < $pointsOffset + ($numPoints * 16)) {
            return false;
        }

        $inside = false;

        for ($partIndex = 0; $partIndex < $numParts; $partIndex++) {
            $start = $this->int32Le($contenido, $partsOffset + ($partIndex * 4));
            $end = $partIndex + 1 < $numParts
                ? $this->int32Le($contenido, $partsOffset + (($partIndex + 1) * 4))
                : $numPoints;

            if ($end - $start < 3) {
                continue;
            }

            if ($this->puntoEnAnillo($contenido, $pointsOffset, $start, $end, $x, $y, $inside)) {
                return true;
            }
        }

        return $inside;
    }

    private function puntoEnAnillo(string $contenido, int $pointsOffset, int $start, int $end, float $x, float $y, bool &$inside): bool
    {
        $prevIndex = $end - 1;
        $prevX = $this->pointX($contenido, $pointsOffset, $prevIndex);
        $prevY = $this->pointY($contenido, $pointsOffset, $prevIndex);

        for ($pointIndex = $start; $pointIndex < $end; $pointIndex++) {
            $currentX = $this->pointX($contenido, $pointsOffset, $pointIndex);
            $currentY = $this->pointY($contenido, $pointsOffset, $pointIndex);

            if ($this->puntoSobreSegmento($x, $y, $prevX, $prevY, $currentX, $currentY)) {
                return true;
            }

            $intersects = (($currentY > $y) !== ($prevY > $y))
                && ($x < (($prevX - $currentX) * ($y - $currentY) / ($prevY - $currentY)) + $currentX);

            if ($intersects) {
                $inside = !$inside;
            }

            $prevX = $currentX;
            $prevY = $currentY;
        }

        return false;
    }

    private function puntoSobreSegmento(float $x, float $y, float $x1, float $y1, float $x2, float $y2): bool
    {
        $epsilon = 0.000001;
        $cross = (($x - $x1) * ($y2 - $y1)) - (($y - $y1) * ($x2 - $x1));

        if (abs($cross) > $epsilon) {
            return false;
        }

        return $x >= min($x1, $x2) - $epsilon
            && $x <= max($x1, $x2) + $epsilon
            && $y >= min($y1, $y2) - $epsilon
            && $y <= max($y1, $y2) + $epsilon;
    }

    private function proyectarALambert(float $lat, float $lng): array
    {
        $degToRad = M_PI / 180.0;
        $flattening = 1.0 / self::INVERSE_FLATTENING;
        $e = sqrt((2.0 * $flattening) - ($flattening * $flattening));

        $phi = $lat * $degToRad;
        $lambda = $lng * $degToRad;
        $phi1 = self::STANDARD_PARALLEL_1 * $degToRad;
        $phi2 = self::STANDARD_PARALLEL_2 * $degToRad;
        $phi0 = self::LATITUDE_OF_ORIGIN * $degToRad;
        $lambda0 = self::CENTRAL_MERIDIAN * $degToRad;

        $m1 = $this->lccM($phi1, $e);
        $m2 = $this->lccM($phi2, $e);
        $t1 = $this->lccT($phi1, $e);
        $t2 = $this->lccT($phi2, $e);
        $t0 = $this->lccT($phi0, $e);
        $t = $this->lccT($phi, $e);

        $n = (log($m1) - log($m2)) / (log($t1) - log($t2));
        $f = $m1 / ($n * pow($t1, $n));
        $rho0 = self::EARTH_SEMI_MAJOR * $f * pow($t0, $n);
        $rho = self::EARTH_SEMI_MAJOR * $f * pow($t, $n);
        $theta = $n * ($lambda - $lambda0);

        return [
            self::FALSE_EASTING + ($rho * sin($theta)),
            self::FALSE_NORTHING + $rho0 - ($rho * cos($theta)),
        ];
    }

    private function lccM(float $phi, float $e): float
    {
        $sin = sin($phi);

        return cos($phi) / sqrt(1.0 - ($e * $e * $sin * $sin));
    }

    private function lccT(float $phi, float $e): float
    {
        $sin = sin($phi);

        return tan((M_PI / 4.0) - ($phi / 2.0))
            / pow((1.0 - ($e * $sin)) / (1.0 + ($e * $sin)), $e / 2.0);
    }

    private function pointX(string $contenido, int $pointsOffset, int $index): float
    {
        return $this->doubleLe($contenido, $pointsOffset + ($index * 16));
    }

    private function pointY(string $contenido, int $pointsOffset, int $index): float
    {
        return $this->doubleLe($contenido, $pointsOffset + ($index * 16) + 8);
    }

    private function uint16Le(string $bytes, int $offset): int
    {
        return unpack('v', substr($bytes, $offset, 2))[1];
    }

    private function uint32Le(string $bytes, int $offset): int
    {
        return unpack('V', substr($bytes, $offset, 4))[1];
    }

    private function uint32Be(string $bytes, int $offset): int
    {
        return unpack('N', substr($bytes, $offset, 4))[1];
    }

    private function int32Le(string $bytes, int $offset): int
    {
        $value = $this->uint32Le($bytes, $offset);

        return $value >= 0x80000000 ? $value - 0x100000000 : $value;
    }

    private function doubleLe(string $bytes, int $offset): float
    {
        return unpack('e', substr($bytes, $offset, 8))[1];
    }
}
