<?php

namespace App\Services\Fotos;

use App\Models\Actividad;
use App\Models\ActividadFoto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActividadFotoBlobStorage
{
    private $client;

    public function usesAzure(): bool
    {
        return (bool) config('services.azure_storage.fotos_enabled', false);
    }

    public function exists(?string $path): bool
    {
        $path = $this->normalizeBlobPath($path);

        if ($path === '') {
            return false;
        }

        try {
            $this->client()->getBlobProperties($this->container(), $path);
            return true;
        } catch (\Throwable $e) {
            if (!$this->isNotFound($e)) {
                throw $e;
            }
        }

        return false;
    }

    public function response(?string $blobPath, ?string $localPath, ?string $downloadName = null)
    {
        $blobPath = $this->normalizeBlobPath($blobPath);
        $localPath = $this->normalizeLocalPath($localPath);

        if ($this->usesAzure() && $blobPath !== '') {
            try {
                return $this->azureResponse($blobPath, $downloadName ?: basename($blobPath));
            } catch (\Throwable $e) {
                if (!$this->isNotFound($e)) {
                    throw $e;
                }
            }
        }

        if ($localPath !== '') {
            $disk = Storage::disk('public');

            if ($disk->exists($localPath)) {
                return $disk->response(
                    $localPath,
                    $downloadName ?: basename($localPath),
                    [
                        'Content-Type' => $this->mimeTypeForPath($localPath),
                        'Cache-Control' => 'public, max-age=86400',
                        'X-Content-Type-Options' => 'nosniff',
                    ],
                    'inline'
                );
            }
        }

        abort(404);
    }

    public function putPublicFile(string $sourcePath, string $targetPath): void
    {
        $sourcePath = $this->normalizeLocalPath($sourcePath);
        $targetPath = $this->normalizeBlobPath($targetPath);

        if ($sourcePath === '' || $targetPath === '') {
            throw new RuntimeException('Ruta de foto de actividad vacia.');
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($sourcePath)) {
            throw new RuntimeException('No existe la foto local: ' . $sourcePath);
        }

        $stream = fopen($disk->path($sourcePath), 'rb');

        if ($stream === false) {
            throw new RuntimeException('No se pudo abrir la foto local: ' . $sourcePath);
        }

        try {
            $this->putStream($targetPath, $stream, $this->mimeTypeForPath($sourcePath));
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function putStream(string $targetPath, $stream, string $contentType): void
    {
        $targetPath = $this->normalizeBlobPath($targetPath);

        if ($targetPath === '') {
            throw new RuntimeException('Ruta destino de Blob vacia.');
        }

        $optionsClass = '\\MicrosoftAzure\\Storage\\Blob\\Models\\CreateBlockBlobOptions';
        $options = new $optionsClass();
        $options->setContentType($contentType ?: 'application/octet-stream');

        $this->client()->createBlockBlob($this->container(), $targetPath, $stream, $options);
    }

    private function azureResponse(string $path, string $downloadName): StreamedResponse
    {
        $blob = $this->client()->getBlob($this->container(), $path);
        $properties = $blob->getProperties();
        $stream = $blob->getContentStream();

        $headers = [
            'Content-Type' => $properties && $properties->getContentType()
                ? $properties->getContentType()
                : $this->mimeTypeForPath($path),
            'Content-Disposition' => 'inline; filename="' . $this->safeDownloadName($downloadName) . '"',
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($properties && $properties->getContentLength()) {
            $headers['Content-Length'] = $properties->getContentLength();
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, $headers);
    }

    public function makeBlobPath(Actividad $actividad, ?ActividadFoto $foto, string $sourcePath, string $kind): string
    {
        $kind = $kind === 'thumbnail' ? 'thumbnails' : 'originales';
        $sourcePath = $this->normalizeLocalPath($sourcePath);
        $fecha = $actividad->fecha ?: $actividad->created_at;

        try {
            $year = $fecha ? $fecha->format('Y') : now('America/Mexico_City')->format('Y');
        } catch (\Throwable $e) {
            $year = now('America/Mexico_City')->format('Y');
        }

        $fotoId = $foto ? ('foto_' . $foto->id) : 'principal';
        $fileName = $this->sanitizeFileName(basename($sourcePath));

        return 'actividades/' . $kind . '/' . $year
            . '/actividad_' . $actividad->id
            . '/' . $fotoId . '_' . $fileName;
    }

    public function normalizeLocalPath(?string $path): string
    {
        $path = str_replace('\\', '/', trim((string) $path));
        $path = preg_replace('#^https?://[^/]+/storage/#i', '', (string) $path);
        $path = preg_replace('#^/?storage/#', '', (string) $path);
        $path = preg_replace('#^/?public/#', '', (string) $path);

        return ltrim((string) $path, '/');
    }

    public function normalizeBlobPath(?string $path): string
    {
        $path = str_replace('\\', '/', trim((string) $path));
        $path = preg_replace('#^https?://[^/]+/fotos/#i', '', (string) $path);

        if (Str::startsWith($path, 'fotos/actividades/')) {
            $path = substr($path, strlen('fotos/'));
        }

        return ltrim((string) $path, '/');
    }

    public function mimeTypeForPath(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
        ][$extension] ?? 'application/octet-stream';
    }

    private function client()
    {
        if ($this->client) {
            return $this->client;
        }

        $clientClass = '\\MicrosoftAzure\\Storage\\Blob\\BlobRestProxy';

        if (!class_exists($clientClass)) {
            throw new RuntimeException('Instala microsoft/azure-storage-blob para guardar fotos de actividades en Azure.');
        }

        $accountName = trim((string) config('services.azure_storage.account_name'));
        $accountKey = trim((string) config('services.azure_storage.account_key'));

        if ($accountName === '' || $accountKey === '' || Str::startsWith($accountKey, 'PEGA_AQUI')) {
            throw new RuntimeException('Configura AZURE_STORAGE_NAME y AZURE_STORAGE_KEY para guardar fotos de actividades en Azure.');
        }

        $this->client = $clientClass::createBlobService($this->connectionString($accountName, $accountKey));

        return $this->client;
    }

    private function container(): string
    {
        $container = trim((string) config('services.azure_storage.fotos_container', 'fotos'));

        if ($container === '') {
            throw new RuntimeException('Configura AZURE_STORAGE_FOTOS_CONTAINER para guardar fotos de actividades en Azure.');
        }

        return $container;
    }

    private function connectionString(string $accountName, string $accountKey): string
    {
        $blobUrl = rtrim(trim((string) config('services.azure_storage.url')), '/');
        $parts = [
            'DefaultEndpointsProtocol=https',
            'AccountName=' . $accountName,
            'AccountKey=' . $accountKey,
        ];

        $parts[] = $blobUrl !== ''
            ? 'BlobEndpoint=' . $blobUrl
            : 'EndpointSuffix=core.windows.net';

        return implode(';', $parts);
    }

    private function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
        $name = trim((string) $name, "._-\t\n\r\0\x0B");

        return $name !== '' ? substr($name, 0, 140) : 'foto.jpg';
    }

    private function safeDownloadName(string $name): string
    {
        $name = str_replace(['"', '\\'], '', $name);

        return $name !== '' ? $name : 'foto.jpg';
    }

    private function isNotFound(\Throwable $e): bool
    {
        $errorCode = method_exists($e, 'getErrorCode') ? (string) $e->getErrorCode() : '';

        return (int) $e->getCode() === 404
            || in_array($errorCode, ['BlobNotFound', 'ContainerNotFound'], true);
    }
}
