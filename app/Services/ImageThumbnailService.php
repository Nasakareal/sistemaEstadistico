<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ImageThumbnailService
{
    public function createPublicThumbnail(
        string $sourcePath,
        string $targetDirectory,
        string $prefix = 'thumb',
        int $maxWidth = 480,
        int $quality = 42
    ): ?string {
        $sourcePath = trim(str_replace('\\', '/', $sourcePath), '/');

        if ($sourcePath === '') {
            return null;
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($sourcePath)) {
            return null;
        }

        $sourceAbsolute = $disk->path($sourcePath);

        if (!is_file($sourceAbsolute)) {
            return null;
        }

        $info = @getimagesize($sourceAbsolute);

        if (!$info || empty($info[0]) || empty($info[1]) || empty($info['mime'])) {
            return null;
        }

        $targetDirectory = trim(str_replace('\\', '/', $targetDirectory), '/');
        if ($targetDirectory === '') {
            $targetDirectory = 'thumbnails';
        }

        if (!$disk->exists($targetDirectory)) {
            $disk->makeDirectory($targetDirectory);
        }

        // Apache y el scheduler pueden ejecutarse con usuarios distintos.
        // El bit setgid conserva el grupo y g+w permite que el proceso
        // programado elimine la copia local después de respaldarla en Blob.
        @chmod($disk->path($targetDirectory), 02775);

        $baseName = pathinfo($sourcePath, PATHINFO_FILENAME);
        $baseName = $this->sanitizeFileName($baseName !== '' ? $baseName : 'foto');
        $prefix = $this->sanitizeFileName($prefix !== '' ? $prefix : 'thumb');
        $targetPath = $targetDirectory . '/' . $prefix . '_' . $baseName . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.jpg';

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        $tmpOut = $tmpDir . DIRECTORY_SEPARATOR . uniqid('thumb_', true) . '.jpg';
        $ok = $this->resizeToJpeg($sourceAbsolute, $tmpOut, $maxWidth, $quality);

        if (!$ok || !is_file($tmpOut)) {
            @unlink($tmpOut);
            return null;
        }

        $content = @file_get_contents($tmpOut);
        @unlink($tmpOut);

        if ($content === false) {
            return null;
        }

        $disk->put($targetPath, $content);
        @chmod($disk->path($targetPath), 0664);

        return $targetPath;
    }

    private function resizeToJpeg(string $sourceAbsolute, string $targetAbsolute, int $maxWidth, int $quality): bool
    {
        $info = @getimagesize($sourceAbsolute);

        if (!$info || empty($info[0]) || empty($info[1]) || empty($info['mime'])) {
            return false;
        }

        $width = (int) $info[0];
        $height = (int) $info[1];
        $mime = (string) $info['mime'];

        if ($width <= 0 || $height <= 0) {
            return false;
        }

        $create = null;
        if ($mime === 'image/jpeg') {
            $create = 'imagecreatefromjpeg';
        } elseif ($mime === 'image/png') {
            $create = 'imagecreatefrompng';
        } elseif ($mime === 'image/webp') {
            $create = 'imagecreatefromwebp';
        } elseif ($mime === 'image/gif') {
            $create = 'imagecreatefromgif';
        }

        if (!$create || !function_exists($create)) {
            return false;
        }

        $source = @$create($sourceAbsolute);
        if (!$source) {
            return false;
        }

        $newWidth = min($width, max(1, $maxWidth));
        $newHeight = (int) round($height * ($newWidth / $width));

        if ($newWidth <= 0 || $newHeight <= 0) {
            imagedestroy($source);
            return false;
        }

        $target = imagecreatetruecolor($newWidth, $newHeight);
        if (!$target) {
            imagedestroy($source);
            return false;
        }

        $white = imagecolorallocate($target, 255, 255, 255);
        imagefilledrectangle($target, 0, 0, $newWidth, $newHeight, $white);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $saved = imagejpeg($target, $targetAbsolute, max(1, min(100, $quality)));

        imagedestroy($source);
        imagedestroy($target);

        return (bool) $saved;
    }

    private function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
        $name = trim((string) $name, "._-\t\n\r\0\x0B");

        return $name !== '' ? substr($name, 0, 70) : 'foto';
    }
}
