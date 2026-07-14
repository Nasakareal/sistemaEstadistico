<?php

namespace App\Services;

use App\Models\Croquis;
use App\Models\Hechos;
use App\Services\Croquis\CroquisArchivoStorage;

class CroquisPreviewService
{
    private const WIDTH = 1200;
    private const HEIGHT = 700;
    private const PREVIEW_DIR = 'previews';

    private $storage;

    public function __construct(CroquisArchivoStorage $storage)
    {
        $this->storage = $storage;
    }

    public function ensure(Croquis $croquis, ?Hechos $hecho = null): ?string
    {
        if ($this->previewExists($croquis->imagen_preview)) {
            return $croquis->imagen_preview;
        }

        return $this->renderAndSave($croquis, $hecho);
    }

    public function renderAndSave(Croquis $croquis, ?Hechos $hecho = null): ?string
    {
        if (!extension_loaded('gd')) {
            return $croquis->imagen_preview;
        }

        $elementos = $this->decodeElements($croquis->json_dibujo);

        if (empty($elementos)) {
            return $croquis->imagen_preview;
        }

        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        $white = $this->color($canvas, '#FFFFFF');
        imagefilledrectangle($canvas, 0, 0, self::WIDTH, self::HEIGHT, $white);
        $this->drawGrid($canvas);

        foreach ($elementos as $elemento) {
            $this->drawElement($canvas, $elemento);
        }

        $hechoId = $hecho ? $hecho->id : $croquis->hecho_id;
        $nombreArchivo = 'hecho_' . $hechoId . '_croquis.png';
        $path = self::PREVIEW_DIR . '/' . $nombreArchivo;

        ob_start();
        imagepng($canvas);
        $contenido = ob_get_clean();
        imagedestroy($canvas);

        if ($contenido === false || $contenido === '') {
            return $croquis->imagen_preview;
        }

        $anterior = $croquis->imagen_preview;
        $this->storage->putContent($contenido, $path, 'image/png');

        if ($anterior !== $path) {
            $croquis->imagen_preview = $path;
            $croquis->save();

            if ($anterior) {
                if ($this->storage->normalizePath($anterior) === $path) {
                    $this->storage->deleteLocal($anterior);
                } else {
                    $this->storage->delete($anterior);
                }
            }
        }

        return $path;
    }

    private function previewExists(?string $path): bool
    {
        $path = trim((string) $path);

        if ($path === '') {
            return false;
        }

        if (preg_match('/^(data:image|https?:\/\/)/i', $path)) {
            return true;
        }

        return $this->storage->exists($path);
    }

    private function decodeElements($raw): array
    {
        $data = $raw;

        for ($i = 0; $i < 4; $i++) {
            if (is_string($data)) {
                $data = trim($data);

                if ($data === '') {
                    return [];
                }

                $decoded = json_decode($data, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return [];
                }

                $data = $decoded;
                continue;
            }

            if (is_array($data) && array_key_exists('json_dibujo', $data)) {
                $data = $data['json_dibujo'];
                continue;
            }

            break;
        }

        if (!is_array($data)) {
            return [];
        }

        if (isset($data['tipo'])) {
            $data = [$data];
        }

        $elementos = [];

        foreach ($data as $item) {
            if (!is_array($item) || empty($item['tipo'])) {
                continue;
            }

            $elementos[] = $this->normalizeElement($item);
        }

        return $elementos;
    }

    private function normalizeElement(array $raw): array
    {
        $tipo = (string) ($raw['tipo'] ?? '');
        $elemento = [
            'tipo' => $tipo,
            'x' => $this->number($raw['x'] ?? null, 200),
            'y' => $this->number($raw['y'] ?? null, 200),
            'rotacion' => $this->number($raw['rotacion'] ?? ($raw['r'] ?? null), 0),
        ];

        if ($tipo === 'carro') {
            $elemento['ancho'] = $this->number($raw['ancho'] ?? ($raw['w'] ?? null), 60);
            $elemento['alto'] = $this->number($raw['alto'] ?? ($raw['h'] ?? null), 30);
        } elseif ($tipo === 'vehiculo') {
            $elemento['categoria'] = (string) ($raw['categoria'] ?? 'automovil');
            $elemento['subtipo'] = (string) ($raw['subtipo'] ?? 'sedan');
            $elemento['src'] = (string) ($raw['src'] ?? '');
            $elemento['ancho'] = $this->number($raw['ancho'] ?? ($raw['w'] ?? null), 90);
            $elemento['alto'] = $this->number($raw['alto'] ?? ($raw['h'] ?? null), 50);
        } elseif ($tipo === 'icono') {
            $elemento['clave'] = (string) ($raw['clave'] ?? ($raw['nombre'] ?? ''));
            $elemento['src'] = (string) ($raw['src'] ?? '');
            $elemento['ancho'] = $this->number($raw['ancho'] ?? ($raw['w'] ?? null), 36);
            $elemento['alto'] = $this->number($raw['alto'] ?? ($raw['h'] ?? null), 36);
        } elseif ($tipo === 'texto') {
            $contenido = trim((string) ($raw['contenido'] ?? ($raw['texto'] ?? 'Texto')));
            $elemento['contenido'] = $contenido === '' ? 'Texto' : $contenido;
            $elemento['fontSize'] = $this->number($raw['fontSize'] ?? null, 20);
            $elemento['fontFamily'] = (string) ($raw['fontFamily'] ?? 'Arial');
            $elemento['ancho'] = $this->number($raw['ancho'] ?? ($raw['w'] ?? null), 120);
            $elemento['alto'] = $this->number($raw['alto'] ?? ($raw['h'] ?? null), 24);
        } elseif ($tipo === 'calle') {
            $elemento['largo'] = $this->number($raw['largo'] ?? ($raw['w'] ?? null), 260);
            $elemento['anchoCarril'] = $this->number($raw['anchoCarril'] ?? null, 28);
            $elemento['carriles'] = max(1, $this->integer($raw['carriles'] ?? null, 1));
        } elseif ($tipo === 'curva') {
            $anchoCarril = $this->number($raw['anchoCarril'] ?? null, 28);
            $carriles = max(1, $this->integer($raw['carriles'] ?? null, 1));
            $elemento['anchoCarril'] = $anchoCarril;
            $elemento['carriles'] = $carriles;
            $keys = ['inicioX', 'inicioY', 'control1X', 'control1Y', 'control2X', 'control2Y', 'finX', 'finY'];
            $hasBezier = true;

            foreach ($keys as $key) {
                if (!array_key_exists($key, $raw) || !is_numeric($raw[$key])) {
                    $hasBezier = false;
                    break;
                }
            }

            $points = $hasBezier
                ? array_combine($keys, array_map(fn ($key) => (float) $raw[$key], $keys))
                : $this->legacyCurvePoints($raw, $anchoCarril, $carriles);
            $elemento = array_merge($elemento, $points);
        } elseif ($tipo === 'camellon' || $tipo === 'banqueta') {
            $elemento['largo'] = max(20, $this->number($raw['largo'] ?? ($raw['w'] ?? null), 240));
            $elemento['ancho'] = max(8, $this->number($raw['ancho'] ?? ($raw['h'] ?? null), $tipo === 'camellon' ? 34 : 26));
        } elseif ($tipo === 'cruce') {
            $largo = $this->number($raw['largo'] ?? ($raw['size'] ?? ($raw['largoHorizontal'] ?? ($raw['largoVertical'] ?? null))), 220);
            $largoHorizontal = $this->number($raw['largoHorizontal'] ?? ($raw['w'] ?? null), $largo);
            $largoVertical = $this->number($raw['largoVertical'] ?? ($raw['h'] ?? null), $largo);
            $elemento['largo'] = max($largo, $largoHorizontal, $largoVertical);
            $elemento['largoHorizontal'] = $largoHorizontal;
            $elemento['largoVertical'] = $largoVertical;
            $elemento['anchoCarril'] = $this->number($raw['anchoCarril'] ?? null, 28);
            $elemento['carriles'] = max(1, $this->integer($raw['carriles'] ?? null, 1));
        } elseif ($tipo === 'entronque') {
            $elemento['largoBase'] = $this->number($raw['largoBase'] ?? ($raw['size'] ?? null), 220);
            $elemento['largoBrazo'] = $this->number($raw['largoBrazo'] ?? null, 140);
            $elemento['anchoCarril'] = $this->number($raw['anchoCarril'] ?? null, 28);
            $elemento['carriles'] = max(1, $this->integer($raw['carriles'] ?? null, 1));
        } elseif ($tipo === 'glorieta') {
            $elemento['radioIsla'] = $this->number($raw['radioIsla'] ?? null, 40);
            $elemento['anchoCarril'] = $this->number($raw['anchoCarril'] ?? null, 24);
            $elemento['carriles'] = max(1, $this->integer($raw['carriles'] ?? null, 1));
            $elemento['largoAcceso'] = $this->number($raw['largoAcceso'] ?? null, 140);
        }

        if (in_array($tipo, ['calle', 'curva', 'cruce', 'entronque', 'glorieta'], true)) {
            $elemento['bordeIzquierdo'] = $this->normalizeRoadEdge($raw['bordeIzquierdo'] ?? null);
            $elemento['bordeDerecho'] = $this->normalizeRoadEdge($raw['bordeDerecho'] ?? null);
        }

        return $elemento;
    }

    private function drawGrid($canvas): void
    {
        $grid = $this->color($canvas, '#E9EDF3');

        for ($x = 0; $x <= self::WIDTH; $x += 50) {
            imageline($canvas, $x, 0, $x, self::HEIGHT, $grid);
        }

        for ($y = 0; $y <= self::HEIGHT; $y += 50) {
            imageline($canvas, 0, $y, self::WIDTH, $y, $grid);
        }
    }

    private function drawElement($canvas, array $elemento): void
    {
        $bounds = $this->bounds($elemento);
        $padding = 90;
        $width = max(1, (int) ceil($bounds[0] + ($padding * 2)));
        $height = max(1, (int) ceil($bounds[1] + ($padding * 2)));
        $local = $this->transparentCanvas($width, $height);
        $cx = $width / 2;
        $cy = $height / 2;

        $this->drawLocalElement($local, $elemento, $cx, $cy);

        $rotacion = $this->number($elemento['rotacion'] ?? null, 0);
        if (abs($rotacion) > 0.01) {
            $transparent = imagecolorallocatealpha($local, 0, 0, 0, 127);
            $rotated = imagerotate($local, -$rotacion, $transparent);
            imagesavealpha($rotated, true);
            imagealphablending($rotated, true);
            imagedestroy($local);
        } else {
            $rotated = $local;
        }

        imagecopy(
            $canvas,
            $rotated,
            (int) round($elemento['x'] - (imagesx($rotated) / 2)),
            (int) round($elemento['y'] - (imagesy($rotated) / 2)),
            0,
            0,
            imagesx($rotated),
            imagesy($rotated)
        );

        imagedestroy($rotated);
    }

    private function drawLocalElement($img, array $el, float $cx, float $cy): void
    {
        switch ($el['tipo']) {
            case 'carro':
                $this->drawCar($img, $el, $cx, $cy);
                break;
            case 'vehiculo':
                if (!$this->drawAsset($img, $el, $cx, $cy)) {
                    $this->drawFallback($img, $el, $cx, $cy, '#6C757D');
                }
                break;
            case 'icono':
                if (!$this->drawAsset($img, $el, $cx, $cy)) {
                    $this->drawFallback($img, $el, $cx, $cy, '#17A2B8');
                }
                break;
            case 'texto':
                $this->drawText($img, $el, $cx, $cy);
                break;
            case 'calle':
                $this->drawStreet($img, $el, $cx, $cy);
                break;
            case 'curva':
                $this->drawCurve($img, $el, $cx, $cy);
                break;
            case 'camellon':
                $this->drawMedian($img, $el, $cx, $cy);
                break;
            case 'banqueta':
                $this->drawSidewalk($img, $el, $cx, $cy);
                break;
            case 'cruce':
                $this->drawCross($img, $el, $cx, $cy);
                break;
            case 'entronque':
                $this->drawTJunction($img, $el, $cx, $cy);
                break;
            case 'glorieta':
                $this->drawRoundabout($img, $el, $cx, $cy);
                break;
        }
    }

    private function drawCar($img, array $el, float $cx, float $cy): void
    {
        $w = $this->number($el['ancho'] ?? null, 60);
        $h = $this->number($el['alto'] ?? null, 30);
        $red = $this->color($img, '#D9534F');
        $black = $this->color($img, '#202020');

        imagefilledrectangle($img, (int) round($cx - ($w / 2)), (int) round($cy - ($h / 2)), (int) round($cx + ($w / 2)), (int) round($cy + ($h / 2)), $red);
        imagefilledrectangle($img, (int) round($cx - ($w * .25)), (int) round($cy - ($h * .28)), (int) round($cx + ($w * .25)), (int) round($cy + ($h * .28)), $black);
    }

    private function drawAsset($img, array $el, float $cx, float $cy): bool
    {
        $src = trim((string) ($el['src'] ?? ''));
        $path = $this->publicAssetPath($src);

        if (!$path || !is_file($path)) {
            return false;
        }

        $asset = $this->loadImage($path);

        if (!$asset) {
            return false;
        }

        $w = $this->number($el['ancho'] ?? null, 40);
        $h = $this->number($el['alto'] ?? null, 40);

        imagecopyresampled(
            $img,
            $asset,
            (int) round($cx - ($w / 2)),
            (int) round($cy - ($h / 2)),
            0,
            0,
            (int) round($w),
            (int) round($h),
            imagesx($asset),
            imagesy($asset)
        );

        imagedestroy($asset);
        return true;
    }

    private function drawFallback($img, array $el, float $cx, float $cy, string $hex): void
    {
        $w = $this->number($el['ancho'] ?? null, 40);
        $h = $this->number($el['alto'] ?? null, 40);
        $fill = $this->color($img, $hex, 22);
        $stroke = $this->color($img, '#222222');

        imagefilledrectangle($img, (int) round($cx - ($w / 2)), (int) round($cy - ($h / 2)), (int) round($cx + ($w / 2)), (int) round($cy + ($h / 2)), $fill);
        imagerectangle($img, (int) round($cx - ($w / 2)), (int) round($cy - ($h / 2)), (int) round($cx + ($w / 2)), (int) round($cy + ($h / 2)), $stroke);
        imageline($img, (int) round($cx - ($w / 2)), (int) round($cy - ($h / 2)), (int) round($cx + ($w / 2)), (int) round($cy + ($h / 2)), $stroke);
        imageline($img, (int) round($cx + ($w / 2)), (int) round($cy - ($h / 2)), (int) round($cx - ($w / 2)), (int) round($cy + ($h / 2)), $stroke);
    }

    private function drawText($img, array $el, float $cx, float $cy): void
    {
        $text = trim((string) ($el['contenido'] ?? 'Texto'));
        $text = $text === '' ? 'Texto' : $text;
        $fontSize = $this->number($el['fontSize'] ?? null, 20);
        $black = $this->color($img, '#111111');
        $font = $this->fontPath();

        if ($font && function_exists('imagettftext')) {
            $box = imagettfbbox($fontSize, 0, $font, $text);
            $minX = min($box[0], $box[2], $box[4], $box[6]);
            $maxX = max($box[0], $box[2], $box[4], $box[6]);
            $minY = min($box[1], $box[3], $box[5], $box[7]);
            $maxY = max($box[1], $box[3], $box[5], $box[7]);
            $x = $cx - (($maxX - $minX) / 2) - $minX;
            $y = $cy - (($maxY - $minY) / 2) - $minY;
            imagettftext($img, $fontSize, 0, (int) round($x), (int) round($y), $black, $font, $text);
            return;
        }

        $fontId = 5;
        $x = $cx - (imagefontwidth($fontId) * strlen($text) / 2);
        $y = $cy - (imagefontheight($fontId) / 2);
        imagestring($img, $fontId, (int) round($x), (int) round($y), $text, $black);
    }

    private function drawStreet($img, array $el, float $cx, float $cy): void
    {
        $width = $this->number($el['largo'] ?? null, 260);
        $height = $this->totalRoadWidth($el);
        $this->drawAttachedRoadEdges($img, $el, $cx, $cy);
        $road = $this->color($img, '#2F2F2F');
        imagefilledrectangle($img, (int) round($cx - ($width / 2)), (int) round($cy - ($height / 2)), (int) round($cx + ($width / 2)), (int) round($cy + ($height / 2)), $road);

        foreach ($this->laneDividers($el) as $y) {
            $this->dashedLine($img, $cx - ($width / 2), $cy + $y, $cx + ($width / 2), $cy + $y);
        }
    }

    private function drawCurve($img, array $el, float $cx, float $cy): void
    {
        $this->drawAttachedRoadEdges($img, $el, $cx, $cy);
        $points = $this->curvePolyline($el);
        $roadWidth = max(1, (int) round($this->totalRoadWidth($el)));
        $road = $this->color($img, '#2F2F2F');
        imagesetthickness($img, $roadWidth);

        for ($i = 1; $i < count($points); $i++) {
            imageline(
                $img,
                (int) round($cx + $points[$i - 1]['x']),
                (int) round($cy + $points[$i - 1]['y']),
                (int) round($cx + $points[$i]['x']),
                (int) round($cy + $points[$i]['y']),
                $road
            );
        }

        foreach ($points as $point) {
            imagefilledellipse(
                $img,
                (int) round($cx + $point['x']),
                (int) round($cy + $point['y']),
                $roadWidth,
                $roadWidth,
                $road
            );
        }

        imagesetthickness($img, 1);

        foreach ($this->laneDividers($el) as $offset) {
            $this->dashedPolyline($img, $this->curvePolyline($el, $offset), $cx, $cy);
        }
    }

    private function drawMedian($img, array $el, float $cx, float $cy): void
    {
        $length = $this->number($el['largo'] ?? null, 240);
        $width = $this->number($el['ancho'] ?? null, 34);
        imagefilledrectangle($img, (int) round($cx - ($length / 2)), (int) round($cy - ($width / 2)), (int) round($cx + ($length / 2)), (int) round($cy + ($width / 2)), $this->color($img, '#D7D7D7'));
        imagefilledrectangle($img, (int) round($cx - ($length / 2) + 3), (int) round($cy - ($width / 2) + 3), (int) round($cx + ($length / 2) - 3), (int) round($cy + ($width / 2) - 3), $this->color($img, '#70A95B'));
    }

    private function drawSidewalk($img, array $el, float $cx, float $cy): void
    {
        $length = $this->number($el['largo'] ?? null, 240);
        $width = $this->number($el['ancho'] ?? null, 26);
        $left = $cx - ($length / 2);
        $top = $cy - ($width / 2);
        $right = $cx + ($length / 2);
        $bottom = $cy + ($width / 2);
        imagefilledrectangle($img, (int) round($left), (int) round($top), (int) round($right), (int) round($bottom), $this->color($img, '#C9C9C9'));
        imagerectangle($img, (int) round($left), (int) round($top), (int) round($right), (int) round($bottom), $this->color($img, '#858585'));

        $joint = $left + 28;
        while ($joint < $right) {
            imageline($img, (int) round($joint), (int) round($top), (int) round($joint), (int) round($bottom), $this->color($img, '#A2A2A2'));
            $joint += 28;
        }
    }

    private function drawCross($img, array $el, float $cx, float $cy): void
    {
        $roadW = $this->totalRoadWidth($el);
        $armH = $this->number($el['largoHorizontal'] ?? ($el['largo'] ?? null), 220);
        $armV = $this->number($el['largoVertical'] ?? ($el['largo'] ?? null), 220);
        $this->drawAttachedRoadEdges($img, $el, $cx, $cy);
        $road = $this->color($img, '#2F2F2F');

        imagefilledrectangle($img, (int) round($cx - ($armH / 2)), (int) round($cy - ($roadW / 2)), (int) round($cx + ($armH / 2)), (int) round($cy + ($roadW / 2)), $road);
        imagefilledrectangle($img, (int) round($cx - ($roadW / 2)), (int) round($cy - ($armV / 2)), (int) round($cx + ($roadW / 2)), (int) round($cy + ($armV / 2)), $road);

        foreach ($this->laneDividers($el) as $y) {
            $this->dashedLine($img, $cx - ($armH / 2), $cy + $y, $cx + ($armH / 2), $cy + $y);
            $this->dashedLine($img, $cx + $y, $cy - ($armV / 2), $cx + $y, $cy + ($armV / 2));
        }
    }

    private function drawTJunction($img, array $el, float $cx, float $cy): void
    {
        $roadW = $this->totalRoadWidth($el);
        $base = $this->number($el['largoBase'] ?? null, 220);
        $arm = $this->number($el['largoBrazo'] ?? null, 140);
        $this->drawAttachedRoadEdges($img, $el, $cx, $cy);
        $road = $this->color($img, '#2F2F2F');

        imagefilledrectangle($img, (int) round($cx - ($base / 2)), (int) round($cy - ($roadW / 2)), (int) round($cx + ($base / 2)), (int) round($cy + ($roadW / 2)), $road);
        imagefilledrectangle($img, (int) round($cx - ($roadW / 2)), (int) round($cy - $arm), (int) round($cx + ($roadW / 2)), (int) round($cy), $road);

        foreach ($this->laneDividers($el) as $y) {
            $this->dashedLine($img, $cx - ($base / 2), $cy + $y, $cx + ($base / 2), $cy + $y);
            $this->dashedLine($img, $cx + $y, $cy, $cx + $y, $cy - $arm);
        }
    }

    private function drawRoundabout($img, array $el, float $cx, float $cy): void
    {
        $inner = $this->number($el['radioIsla'] ?? null, 40);
        $outer = $inner + $this->totalRoadWidth($el);
        imagefilledellipse($img, (int) round($cx), (int) round($cy), (int) round($outer * 2), (int) round($outer * 2), $this->color($img, '#2F2F2F'));
        $islandRadius = max(6, $inner - $this->attachedWidth($el['bordeIzquierdo'] ?? null) - 4);
        imagefilledellipse($img, (int) round($cx), (int) round($cy), (int) round($islandRadius * 2), (int) round($islandRadius * 2), $this->color($img, '#5CB85C'));
        $this->drawAttachedRoadEdges($img, $el, $cx, $cy);

        $carriles = max(1, $this->integer($el['carriles'] ?? null, 1));
        for ($i = 1; $i < $carriles; $i++) {
            $radius = $inner + ($i * $this->number($el['anchoCarril'] ?? null, 24));
            $this->dashedArc($img, $cx, $cy, $radius, 0, 360);
        }
    }

    private function drawAttachedRoadEdges($img, array $el, float $cx, float $cy): void
    {
        $roadWidth = $this->totalRoadWidth($el);
        $sides = [
            ['type' => $el['bordeIzquierdo'] ?? null, 'sign' => -1],
            ['type' => $el['bordeDerecho'] ?? null, 'sign' => 1],
        ];

        foreach ($sides as $side) {
            $width = $this->attachedWidth($side['type']);
            if ($width <= 0) {
                continue;
            }

            $offset = $side['sign'] * (($roadWidth / 2) + ($width / 2));

            if ($el['tipo'] === 'calle') {
                $length = $this->number($el['largo'] ?? null, 260);
                $this->strokeAttachedLine($img, $side['type'], $cx - ($length / 2), $cy + $offset, $cx + ($length / 2), $cy + $offset);
            } elseif ($el['tipo'] === 'curva') {
                $this->strokeAttachedPolyline($img, $side['type'], $this->curvePolyline($el, $offset), $cx, $cy);
            } elseif ($el['tipo'] === 'cruce') {
                $horizontal = $this->number($el['largoHorizontal'] ?? ($el['largo'] ?? null), 220);
                $vertical = $this->number($el['largoVertical'] ?? ($el['largo'] ?? null), 220);
                $this->strokeAttachedLine($img, $side['type'], $cx - ($horizontal / 2), $cy + $offset, $cx + ($horizontal / 2), $cy + $offset);
                $this->strokeAttachedLine($img, $side['type'], $cx + $offset, $cy - ($vertical / 2), $cx + $offset, $cy + ($vertical / 2));
            } elseif ($el['tipo'] === 'entronque') {
                $base = $this->number($el['largoBase'] ?? null, 220);
                $arm = $this->number($el['largoBrazo'] ?? null, 140);
                $this->strokeAttachedLine($img, $side['type'], $cx - ($base / 2), $cy + $offset, $cx + ($base / 2), $cy + $offset);
                $this->strokeAttachedLine($img, $side['type'], $cx + $offset, $cy - $arm, $cx + $offset, $cy);
            } elseif ($el['tipo'] === 'glorieta') {
                $inner = $this->number($el['radioIsla'] ?? null, 40);
                $radius = $side['sign'] < 0
                    ? max($width / 2, $inner - ($width / 2))
                    : $inner + $roadWidth + ($width / 2);
                $this->strokeAttachedEllipse($img, $side['type'], $cx, $cy, $radius);
            }
        }
    }

    private function strokeAttachedLine($img, ?string $type, float $x1, float $y1, float $x2, float $y2): void
    {
        $width = $this->attachedWidth($type);
        $this->solidLine($img, $x1, $y1, $x2, $y2, $width + 2, $type === 'camellon' ? '#D7D7D7' : '#858585');
        $this->solidLine($img, $x1, $y1, $x2, $y2, max(2, $width - ($type === 'camellon' ? 6 : 3)), $type === 'camellon' ? '#70A95B' : '#C9C9C9');
    }

    private function solidLine($img, float $x1, float $y1, float $x2, float $y2, int $width, string $hex): void
    {
        $color = $this->color($img, $hex);
        imagesetthickness($img, $width);
        imageline($img, (int) round($x1), (int) round($y1), (int) round($x2), (int) round($y2), $color);
        imagefilledellipse($img, (int) round($x1), (int) round($y1), $width, $width, $color);
        imagefilledellipse($img, (int) round($x2), (int) round($y2), $width, $width, $color);
        imagesetthickness($img, 1);
    }

    private function strokeAttachedPolyline($img, ?string $type, array $points, float $cx, float $cy): void
    {
        $width = $this->attachedWidth($type);
        $this->solidPolyline($img, $points, $cx, $cy, $width + 2, $type === 'camellon' ? '#D7D7D7' : '#858585');
        $this->solidPolyline($img, $points, $cx, $cy, max(2, $width - ($type === 'camellon' ? 6 : 3)), $type === 'camellon' ? '#70A95B' : '#C9C9C9');
    }

    private function solidPolyline($img, array $points, float $cx, float $cy, int $width, string $hex): void
    {
        if (count($points) < 2) {
            return;
        }

        $color = $this->color($img, $hex);
        imagesetthickness($img, $width);
        for ($i = 1; $i < count($points); $i++) {
            imageline($img, (int) round($cx + $points[$i - 1]['x']), (int) round($cy + $points[$i - 1]['y']), (int) round($cx + $points[$i]['x']), (int) round($cy + $points[$i]['y']), $color);
        }

        foreach ($points as $point) {
            imagefilledellipse($img, (int) round($cx + $point['x']), (int) round($cy + $point['y']), $width, $width, $color);
        }
        imagesetthickness($img, 1);
    }

    private function strokeAttachedEllipse($img, ?string $type, float $cx, float $cy, float $radius): void
    {
        $width = $this->attachedWidth($type);
        $passes = [
            [$width + 2, $type === 'camellon' ? '#D7D7D7' : '#858585'],
            [max(2, $width - ($type === 'camellon' ? 6 : 3)), $type === 'camellon' ? '#70A95B' : '#C9C9C9'],
        ];

        foreach ($passes as [$lineWidth, $hex]) {
            imagesetthickness($img, $lineWidth);
            imageellipse($img, (int) round($cx), (int) round($cy), (int) round($radius * 2), (int) round($radius * 2), $this->color($img, $hex));
        }
        imagesetthickness($img, 1);
    }

    private function legacyCurvePoints(array $raw, float $anchoCarril, int $carriles): array
    {
        $inner = $this->number($raw['radioInterno'] ?? ($raw['radio'] ?? null), 45);
        $angle = deg2rad($this->clamp($this->number($raw['angulo'] ?? null, 90), 5, 180));
        $radius = $inner + (($anchoCarril * $carriles) / 2);
        $tangent = (4 / 3) * tan($angle / 4) * $radius;
        $endX = cos($angle) * $radius;
        $endY = sin($angle) * $radius;

        return [
            'inicioX' => $radius,
            'inicioY' => 0.0,
            'control1X' => $radius,
            'control1Y' => $tangent,
            'control2X' => $endX + (sin($angle) * $tangent),
            'control2Y' => $endY - (cos($angle) * $tangent),
            'finX' => $endX,
            'finY' => $endY,
        ];
    }

    private function curvePoint(array $el, float $t, float $offset = 0): array
    {
        $u = 1 - $t;
        $x = ($u ** 3 * $el['inicioX'])
            + (3 * ($u ** 2) * $t * $el['control1X'])
            + (3 * $u * ($t ** 2) * $el['control2X'])
            + ($t ** 3 * $el['finX']);
        $y = ($u ** 3 * $el['inicioY'])
            + (3 * ($u ** 2) * $t * $el['control1Y'])
            + (3 * $u * ($t ** 2) * $el['control2Y'])
            + ($t ** 3 * $el['finY']);
        $dx = (3 * ($u ** 2) * ($el['control1X'] - $el['inicioX']))
            + (6 * $u * $t * ($el['control2X'] - $el['control1X']))
            + (3 * ($t ** 2) * ($el['finX'] - $el['control2X']));
        $dy = (3 * ($u ** 2) * ($el['control1Y'] - $el['inicioY']))
            + (6 * $u * $t * ($el['control2Y'] - $el['control1Y']))
            + (3 * ($t ** 2) * ($el['finY'] - $el['control2Y']));
        $length = sqrt(($dx * $dx) + ($dy * $dy));
        $length = $length > 0.0001 ? $length : 1;

        return [
            'x' => $x + ((-$dy / $length) * $offset),
            'y' => $y + (($dx / $length) * $offset),
        ];
    }

    private function curvePolyline(array $el, float $offset = 0, int $steps = 56): array
    {
        $points = [];
        for ($i = 0; $i <= $steps; $i++) {
            $points[] = $this->curvePoint($el, $i / $steps, $offset);
        }

        return $points;
    }

    private function dashedPolyline($img, array $points, float $cx, float $cy): void
    {
        $dash = 12.0;
        $gap = 10.0;
        $period = $dash + $gap;
        $progress = 0.0;
        $color = $this->color($img, '#FFFFFF');
        imagesetthickness($img, 2);

        for ($i = 1; $i < count($points); $i++) {
            $start = $points[$i - 1];
            $end = $points[$i];
            $dx = $end['x'] - $start['x'];
            $dy = $end['y'] - $start['y'];
            $length = sqrt(($dx * $dx) + ($dy * $dy));
            if ($length <= 0.0001) {
                continue;
            }

            $position = 0.0;
            while ($position < $length) {
                $phase = fmod($progress, $period);
                $drawing = $phase < $dash;
                $remaining = $drawing ? $dash - $phase : $period - $phase;
                $take = min($remaining, $length - $position);

                if ($drawing && $take > 0) {
                    $from = $position / $length;
                    $to = ($position + $take) / $length;
                    imageline(
                        $img,
                        (int) round($cx + $start['x'] + ($dx * $from)),
                        (int) round($cy + $start['y'] + ($dy * $from)),
                        (int) round($cx + $start['x'] + ($dx * $to)),
                        (int) round($cy + $start['y'] + ($dy * $to)),
                        $color
                    );
                }

                $position += $take;
                $progress += $take;
            }
        }

        imagesetthickness($img, 1);
    }

    private function dashedLine($img, float $x1, float $y1, float $x2, float $y2): void
    {
        $dash = 12.0;
        $gap = 10.0;
        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $length = sqrt(($dx * $dx) + ($dy * $dy));

        if ($length <= 0) {
            return;
        }

        $ux = $dx / $length;
        $uy = $dy / $length;
        $color = $this->color($img, '#FFFFFF');
        imagesetthickness($img, 2);

        for ($distance = 0; $distance < $length; $distance += $dash + $gap) {
            $end = min($distance + $dash, $length);
            imageline(
                $img,
                (int) round($x1 + ($ux * $distance)),
                (int) round($y1 + ($uy * $distance)),
                (int) round($x1 + ($ux * $end)),
                (int) round($y1 + ($uy * $end)),
                $color
            );
        }

        imagesetthickness($img, 1);
    }

    private function dashedArc($img, float $cx, float $cy, float $radius, float $start, float $end): void
    {
        $color = $this->color($img, '#FFFFFF');
        imagesetthickness($img, 2);

        for ($angle = $start; $angle < $end; $angle += 12) {
            $segmentEnd = min($angle + 7, $end);
            imagearc($img, (int) round($cx), (int) round($cy), (int) round($radius * 2), (int) round($radius * 2), (int) round($angle), (int) round($segmentEnd), $color);
        }

        imagesetthickness($img, 1);
    }

    private function bounds(array $el): array
    {
        if ($el['tipo'] === 'carro') {
            return [$this->number($el['ancho'] ?? null, 60), $this->number($el['alto'] ?? null, 30)];
        }

        if ($el['tipo'] === 'vehiculo') {
            return [$this->number($el['ancho'] ?? null, 90), $this->number($el['alto'] ?? null, 50)];
        }

        if ($el['tipo'] === 'icono') {
            return [$this->number($el['ancho'] ?? null, 36), $this->number($el['alto'] ?? null, 36)];
        }

        if ($el['tipo'] === 'texto') {
            return [$this->number($el['ancho'] ?? null, 120), $this->number($el['alto'] ?? null, 24)];
        }

        if ($el['tipo'] === 'calle') {
            return [
                $this->number($el['largo'] ?? null, 260) + 4,
                $this->totalRoadWidth($el) + ($this->maxAttachedWidth($el) * 2),
            ];
        }

        if ($el['tipo'] === 'curva') {
            $margin = ($this->totalRoadWidth($el) / 2) + $this->maxAttachedWidth($el) + 4;
            $maxX = 0.0;
            $maxY = 0.0;

            foreach ($this->curvePolyline($el) as $point) {
                $maxX = max($maxX, abs($point['x']));
                $maxY = max($maxY, abs($point['y']));
            }

            return [($maxX + $margin) * 2, ($maxY + $margin) * 2];
        }

        if ($el['tipo'] === 'camellon' || $el['tipo'] === 'banqueta') {
            return [
                $this->number($el['largo'] ?? null, 240),
                $this->number($el['ancho'] ?? null, $el['tipo'] === 'camellon' ? 34 : 26),
            ];
        }

        if ($el['tipo'] === 'cruce') {
            $roadW = $this->totalRoadWidth($el);
            $attached = $this->maxAttachedWidth($el) * 2;
            return [
                max($this->number($el['largoHorizontal'] ?? ($el['largo'] ?? null), 220), $roadW) + $attached,
                max($this->number($el['largoVertical'] ?? ($el['largo'] ?? null), 220), $roadW) + $attached,
            ];
        }

        if ($el['tipo'] === 'entronque') {
            $roadW = $this->totalRoadWidth($el);
            $attached = $this->maxAttachedWidth($el) * 2;
            return [
                max($this->number($el['largoBase'] ?? null, 220), $roadW) + $attached,
                $roadW + $this->number($el['largoBrazo'] ?? null, 140) + $attached,
            ];
        }

        if ($el['tipo'] === 'glorieta') {
            $outer = $this->number($el['radioIsla'] ?? null, 40)
                + $this->totalRoadWidth($el)
                + $this->attachedWidth($el['bordeDerecho'] ?? null);
            return [$outer * 2, $outer * 2];
        }

        return [100, 100];
    }

    private function laneDividers(array $el): array
    {
        $carriles = max(1, $this->integer($el['carriles'] ?? null, 1));
        $ancho = max(1, $this->number($el['anchoCarril'] ?? null, 1));
        $total = $carriles * $ancho;
        $start = -$total / 2;
        $dividers = [];

        for ($i = 1; $i < $carriles; $i++) {
            $dividers[] = $start + ($i * $ancho);
        }

        return $dividers;
    }

    private function totalRoadWidth(array $el): float
    {
        return max(1, $this->integer($el['carriles'] ?? null, 1)) * max(1, $this->number($el['anchoCarril'] ?? null, 1));
    }

    private function normalizeRoadEdge($value): ?string
    {
        $type = strtolower(trim((string) $value));

        return in_array($type, ['banqueta', 'camellon'], true) ? $type : null;
    }

    private function attachedWidth(?string $type): int
    {
        if ($type === 'banqueta') {
            return 26;
        }

        if ($type === 'camellon') {
            return 34;
        }

        return 0;
    }

    private function maxAttachedWidth(array $el): int
    {
        return max(
            $this->attachedWidth($el['bordeIzquierdo'] ?? null),
            $this->attachedWidth($el['bordeDerecho'] ?? null)
        );
    }

    private function transparentCanvas(int $width, int $height)
    {
        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefilledrectangle($img, 0, 0, $width, $height, $transparent);
        imagealphablending($img, true);

        return $img;
    }

    private function publicAssetPath(string $src): ?string
    {
        if ($src === '') {
            return null;
        }

        if (preg_match('/^[A-Z]:[\/\\\\]/i', $src) && is_file($src)) {
            return $src;
        }

        $path = parse_url($src, PHP_URL_PATH);
        $path = $path ?: $src;
        $path = rawurldecode(ltrim($path, '/\\'));
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $fullPath = public_path($path);

        return is_file($fullPath) ? $fullPath : null;
    }

    private function loadImage(string $path)
    {
        $type = @exif_imagetype($path);

        if ($type === IMAGETYPE_PNG) {
            $img = @imagecreatefrompng($path);
        } elseif ($type === IMAGETYPE_JPEG) {
            $img = @imagecreatefromjpeg($path);
        } elseif ($type === IMAGETYPE_GIF) {
            $img = @imagecreatefromgif($path);
        } elseif ($type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
            $img = @imagecreatefromwebp($path);
        } else {
            $img = null;
        }

        if ($img) {
            imagealphablending($img, true);
            imagesavealpha($img, true);
        }

        return $img ?: null;
    }

    private function fontPath(): ?string
    {
        $paths = [
            public_path('fonts/arial.ttf'),
            resource_path('fonts/arial.ttf'),
            'C:\\Windows\\Fonts\\arial.ttf',
            'C:\\Windows\\Fonts\\segoeui.ttf',
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function color($img, string $hex, int $alpha = 0): int
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return imagecolorallocatealpha($img, $r, $g, $b, $alpha);
    }

    private function number($value, float $fallback): float
    {
        return is_numeric($value) ? (float) $value : $fallback;
    }

    private function integer($value, int $fallback): int
    {
        return is_numeric($value) ? (int) round((float) $value) : $fallback;
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}
