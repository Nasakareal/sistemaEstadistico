<?php

namespace App\Services;

use App\Models\Croquis;
use App\Models\Hechos;

class CroquisPreviewService
{
    private const WIDTH = 1200;
    private const HEIGHT = 700;
    private const PREVIEW_DIR = 'img/croquis/previews';

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

        $directorio = public_path(self::PREVIEW_DIR);

        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        $hechoId = $hecho ? $hecho->id : $croquis->hecho_id;
        $nombreArchivo = 'hecho_' . $hechoId . '_croquis.png';
        $ruta = $directorio . DIRECTORY_SEPARATOR . $nombreArchivo;

        imagepng($canvas, $ruta);
        imagedestroy($canvas);

        $path = self::PREVIEW_DIR . '/' . $nombreArchivo;

        if ($croquis->imagen_preview !== $path) {
            $croquis->imagen_preview = $path;
            $croquis->save();
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

        return is_file(public_path(ltrim($path, '/\\')));
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
            $elemento['radioInterno'] = $this->number($raw['radioInterno'] ?? ($raw['radio'] ?? null), 45);
            $elemento['anchoCarril'] = $this->number($raw['anchoCarril'] ?? null, 28);
            $elemento['carriles'] = max(1, $this->integer($raw['carriles'] ?? null, 1));
            $elemento['angulo'] = $this->clamp($this->number($raw['angulo'] ?? null, 90), 30, 180);
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
        $road = $this->color($img, '#2F2F2F');
        imagefilledrectangle($img, (int) round($cx - ($width / 2)), (int) round($cy - ($height / 2)), (int) round($cx + ($width / 2)), (int) round($cy + ($height / 2)), $road);

        foreach ($this->laneDividers($el) as $y) {
            $this->dashedLine($img, $cx - ($width / 2), $cy + $y, $cx + ($width / 2), $cy + $y);
        }
    }

    private function drawCurve($img, array $el, float $cx, float $cy): void
    {
        $inner = $this->number($el['radioInterno'] ?? null, 45);
        $outer = $inner + $this->totalRoadWidth($el);
        $angle = $this->number($el['angulo'] ?? null, 90);
        $points = [];

        for ($a = 0; $a <= $angle; $a += 3) {
            $rad = deg2rad($a);
            $points[] = (int) round($cx + (cos($rad) * $outer));
            $points[] = (int) round($cy + (sin($rad) * $outer));
        }

        for ($a = $angle; $a >= 0; $a -= 3) {
            $rad = deg2rad($a);
            $points[] = (int) round($cx + (cos($rad) * $inner));
            $points[] = (int) round($cy + (sin($rad) * $inner));
        }

        if (count($points) >= 6) {
            imagefilledpolygon($img, $points, (int) (count($points) / 2), $this->color($img, '#2F2F2F'));
        }

        $carriles = max(1, $this->integer($el['carriles'] ?? null, 1));
        for ($i = 1; $i < $carriles; $i++) {
            $radius = $inner + ($i * $this->number($el['anchoCarril'] ?? null, 28));
            $this->dashedArc($img, $cx, $cy, $radius, 0, $angle);
        }
    }

    private function drawCross($img, array $el, float $cx, float $cy): void
    {
        $roadW = $this->totalRoadWidth($el);
        $armH = $this->number($el['largoHorizontal'] ?? ($el['largo'] ?? null), 220);
        $armV = $this->number($el['largoVertical'] ?? ($el['largo'] ?? null), 220);
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
        imagefilledellipse($img, (int) round($cx), (int) round($cy), (int) round(max(6, $inner - 4) * 2), (int) round(max(6, $inner - 4) * 2), $this->color($img, '#5CB85C'));

        $carriles = max(1, $this->integer($el['carriles'] ?? null, 1));
        for ($i = 1; $i < $carriles; $i++) {
            $radius = $inner + ($i * $this->number($el['anchoCarril'] ?? null, 24));
            $this->dashedArc($img, $cx, $cy, $radius, 0, 360);
        }
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
            return [$this->number($el['largo'] ?? null, 260), $this->totalRoadWidth($el)];
        }

        if ($el['tipo'] === 'curva') {
            $outer = $this->number($el['radioInterno'] ?? null, 45) + $this->totalRoadWidth($el);
            return [$outer * 2, $outer * 2];
        }

        if ($el['tipo'] === 'cruce') {
            $roadW = $this->totalRoadWidth($el);
            return [
                max($this->number($el['largoHorizontal'] ?? ($el['largo'] ?? null), 220), $roadW),
                max($this->number($el['largoVertical'] ?? ($el['largo'] ?? null), 220), $roadW),
            ];
        }

        if ($el['tipo'] === 'entronque') {
            $roadW = $this->totalRoadWidth($el);
            return [
                max($this->number($el['largoBase'] ?? null, 220), $roadW),
                $roadW + $this->number($el['largoBrazo'] ?? null, 140),
            ];
        }

        if ($el['tipo'] === 'glorieta') {
            $outer = $this->number($el['radioIsla'] ?? null, 40) + $this->totalRoadWidth($el);
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
