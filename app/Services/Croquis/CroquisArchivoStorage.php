<?php

namespace App\Services\Croquis;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CroquisArchivoStorage
{
    private $client;

    public function putContent(string $content, string $path, string $contentType = 'image/png'): string
    {
        $path = $this->normalizePath($path);

        if ($path === '') {
            throw new RuntimeException('La ruta del croquis esta vacia.');
        }

        if ($this->usesAzure()) {
            $stream = fopen('php://temp', 'r+');

            if ($stream === false) {
                throw new RuntimeException('No se pudo crear el stream temporal del croquis.');
            }

            try {
                fwrite($stream, $content);
                rewind($stream);
                $this->putAzureStream($path, $stream, $contentType);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            return $path;
        }

        $local = public_path('img/croquis/' . $path);
        File::ensureDirectoryExists(dirname($local));
        file_put_contents($local, $content);

        return $path;
    }

    public function migrateLocalFile(string $absolutePath, string $targetPath, bool $deleteSource = true): array
    {
        $targetPath = $this->normalizePath($targetPath);

        if (!is_file($absolutePath)) {
            return [
                'status' => $this->exists($targetPath) ? 'already_migrated' : 'missing_source',
                'path' => $targetPath,
            ];
        }

        if ($this->usesAzure()) {
            $stream = fopen($absolutePath, 'rb');

            if ($stream === false) {
                throw new RuntimeException('No se pudo abrir el croquis local: ' . $absolutePath);
            }

            try {
                $this->putAzureStream($targetPath, $stream, $this->mimeType($absolutePath));
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if ($deleteSource) {
                @unlink($absolutePath);
            }

            return ['status' => 'migrated', 'path' => $targetPath];
        }

        $local = public_path('img/croquis/' . $targetPath);
        File::ensureDirectoryExists(dirname($local));

        if (realpath($absolutePath) !== realpath($local)) {
            copy($absolutePath, $local);

            if ($deleteSource) {
                @unlink($absolutePath);
            }
        }

        return ['status' => 'migrated_local', 'path' => $targetPath];
    }

    public function delete(?string $path): void
    {
        $path = $this->normalizePath($path);

        if ($path === '') {
            return;
        }

        if ($this->usesAzure()) {
            try {
                $this->client()->deleteBlob($this->container(), $path);
            } catch (\Throwable $e) {
                if (!$this->isNotFound($e)) {
                    throw $e;
                }
            }
        }

        foreach ($this->localCandidates($path) as $local) {
            if (is_file($local)) {
                @unlink($local);
            }
        }
    }

    public function deleteLocal(?string $path): int
    {
        $deleted = 0;

        foreach ($this->localCandidates($path) as $local) {
            if (is_file($local)) {
                @unlink($local);
                $deleted++;
            }
        }

        return $deleted;
    }

    public function response(string $path, ?string $downloadName = null, string $disposition = 'inline')
    {
        $path = $this->normalizePath($path);

        abort_if($path === '', 404);

        if ($this->usesAzure()) {
            try {
                return $this->azureResponse($path, $downloadName ?: basename($path), $disposition);
            } catch (\Throwable $e) {
                if (!$this->isNotFound($e)) {
                    throw $e;
                }
            }
        }

        $local = $this->localPath($path);

        abort_unless($local, 404);

        return response()->file($local, [
            'Content-Type' => $this->mimeType($local),
            'Content-Disposition' => $this->contentDisposition($downloadName ?: basename($path), $disposition),
        ]);
    }

    public function exists(?string $path): bool
    {
        $path = $this->normalizePath($path);

        if ($path === '') {
            return false;
        }

        if ($this->usesAzure()) {
            try {
                $this->client()->getBlobProperties($this->container(), $path);
                return true;
            } catch (\Throwable $e) {
                if (!$this->isNotFound($e)) {
                    throw $e;
                }
            }
        }

        return (bool) $this->localPath($path);
    }

    public function dataUri(?string $path): ?string
    {
        $path = $this->normalizePath($path);

        if ($path === '') {
            return null;
        }

        $content = null;
        $mime = 'image/png';

        if ($this->usesAzure()) {
            try {
                $blob = $this->client()->getBlob($this->container(), $path);
                $stream = $blob->getContentStream();
                $content = stream_get_contents($stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }

                $properties = $blob->getProperties();
                $mime = $properties && $properties->getContentType()
                    ? $properties->getContentType()
                    : $mime;
            } catch (\Throwable $e) {
                if (!$this->isNotFound($e)) {
                    throw $e;
                }
            }
        }

        if ($content === null) {
            $local = $this->localPath($path);

            if (!$local) {
                return null;
            }

            $content = file_get_contents($local);
            $mime = $this->mimeType($local);
        }

        return $content === false || $content === null
            ? null
            : 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    public function temporaryLocalPath(?string $path): ?string
    {
        $path = $this->normalizePath($path);

        if ($path === '') {
            return null;
        }

        $local = $this->localPath($path);

        if ($local) {
            return $local;
        }

        if (!$this->usesAzure()) {
            return null;
        }

        try {
            $blob = $this->client()->getBlob($this->container(), $path);
            $stream = $blob->getContentStream();
            $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'png';
            $temp = storage_path('app/temp/croquis_blob_' . sha1($path . microtime(true)) . '.' . $extension);

            File::ensureDirectoryExists(dirname($temp));
            file_put_contents($temp, stream_get_contents($stream));

            if (is_resource($stream)) {
                fclose($stream);
            }

            return is_file($temp) ? $temp : null;
        } catch (\Throwable $e) {
            if (!$this->isNotFound($e)) {
                throw $e;
            }
        }

        return null;
    }

    public function localPath(?string $path): ?string
    {
        foreach ($this->localCandidates($path) as $local) {
            if (is_file($local)) {
                return $local;
            }
        }

        return null;
    }

    public function normalizePath(?string $path): string
    {
        $path = str_replace('\\', '/', trim((string) $path));
        $path = preg_replace('#^https?://[^/]+/storage/#i', '', (string) $path);
        $path = preg_replace('#^/?storage/#', '', (string) $path);
        $path = preg_replace('#^/?public/#', '', (string) $path);
        $path = ltrim((string) $path, '/');

        if (Str::startsWith($path, 'img/croquis/')) {
            $path = substr($path, strlen('img/croquis/'));
        }

        if (Str::startsWith($path, 'croquis/')) {
            $path = substr($path, strlen('croquis/'));
        }

        return ltrim($path, '/');
    }

    public function localCandidates(?string $path): array
    {
        $normalized = $this->normalizePath($path);

        if ($normalized === '') {
            return [];
        }

        $candidates = [
            public_path($normalized),
            public_path('img/croquis/' . $normalized),
            storage_path('app/public/' . $normalized),
        ];

        return array_values(array_unique($candidates));
    }

    public function usesAzure(): bool
    {
        return (bool) config('services.azure_storage.croquis_enabled', false);
    }

    private function putAzureStream(string $path, $stream, string $contentType): void
    {
        $optionsClass = '\\MicrosoftAzure\\Storage\\Blob\\Models\\CreateBlockBlobOptions';
        $options = new $optionsClass();
        $options->setContentType($contentType);

        $this->client()->createBlockBlob($this->container(), $path, $stream, $options);
    }

    private function azureResponse(string $path, string $downloadName, string $disposition): StreamedResponse
    {
        $blob = $this->client()->getBlob($this->container(), $path);
        $properties = $blob->getProperties();
        $stream = $blob->getContentStream();

        $headers = [
            'Content-Type' => $properties && $properties->getContentType()
                ? $properties->getContentType()
                : 'application/octet-stream',
            'Content-Disposition' => $this->contentDisposition($downloadName, $disposition),
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

    private function client()
    {
        if ($this->client) {
            return $this->client;
        }

        $clientClass = '\\MicrosoftAzure\\Storage\\Blob\\BlobRestProxy';

        if (!class_exists($clientClass)) {
            throw new RuntimeException('Instala microsoft/azure-storage-blob para guardar croquis en Azure.');
        }

        $accountName = trim((string) config('services.azure_storage.account_name'));
        $accountKey = trim((string) config('services.azure_storage.account_key'));

        if ($accountName === '' || $accountKey === '' || Str::startsWith($accountKey, 'PEGA_AQUI')) {
            throw new RuntimeException('Configura AZURE_STORAGE_NAME y AZURE_STORAGE_KEY para guardar croquis en Azure.');
        }

        $this->client = $clientClass::createBlobService($this->connectionString($accountName, $accountKey));

        return $this->client;
    }

    private function container(): string
    {
        $container = trim((string) config('services.azure_storage.croquis_container', 'croquis'));

        if ($container === '') {
            throw new RuntimeException('Configura AZURE_STORAGE_CROQUIS_CONTAINER para guardar croquis en Azure.');
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

    private function contentDisposition(string $downloadName, string $disposition): string
    {
        $safeName = str_replace(['"', '\\'], '', $downloadName) ?: 'croquis.png';
        $disposition = $disposition === 'attachment' ? 'attachment' : 'inline';

        return $disposition . '; filename="' . $safeName . '"';
    }

    private function mimeType(string $path): string
    {
        return File::mimeType($path) ?: 'image/png';
    }

    private function isNotFound(\Throwable $e): bool
    {
        $errorCode = method_exists($e, 'getErrorCode') ? (string) $e->getErrorCode() : '';

        return (int) $e->getCode() === 404
            || in_array($errorCode, ['BlobNotFound', 'ContainerNotFound'], true);
    }
}
