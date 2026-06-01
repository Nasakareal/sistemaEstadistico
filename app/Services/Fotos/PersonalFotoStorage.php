<?php

namespace App\Services\Fotos;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonalFotoStorage
{
    private $client;

    public function putUploadedFile(UploadedFile $file): string
    {
        $path = $this->makePath($file);

        if ($this->usesAzure()) {
            $stream = fopen($file->getRealPath(), 'rb');

            if ($stream === false) {
                throw new RuntimeException('No se pudo abrir el archivo temporal de la foto de personal.');
            }

            try {
                $this->putAzureStream($path, $stream, $file->getMimeType() ?: 'application/octet-stream');
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            return $path;
        }

        $localPath = 'personals/fotos/' . basename($path);

        if (!Storage::disk('local')->putFileAs(dirname($localPath), $file, basename($localPath))) {
            throw new RuntimeException('No se pudo guardar la foto de personal en almacenamiento privado.');
        }

        return $localPath;
    }

    public function migrateLocalFile(string $sourcePath, ?string $targetPath = null, bool $deleteSource = true): array
    {
        $sourcePath = $this->normalizeSourcePath($sourcePath);
        $targetPath = $this->normalizePath($targetPath ?: $sourcePath);

        if ($sourcePath === '' || $targetPath === '') {
            return ['status' => 'empty', 'path' => $targetPath];
        }

        $source = $this->findLocalSource($sourcePath);

        if (!$source) {
            return [
                'status' => $this->exists($targetPath) ? 'already_migrated' : 'missing_source',
                'path' => $targetPath,
            ];
        }

        if ($this->usesAzure()) {
            $stream = fopen($source['absolute'], 'rb');

            if ($stream === false) {
                throw new RuntimeException('No se pudo abrir la foto local: ' . $sourcePath);
            }

            try {
                $this->putAzureStream($targetPath, $stream, $this->mimeType($source['disk'], $source['path']));
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if ($deleteSource) {
                Storage::disk($source['disk'])->delete($source['path']);
            }

            return ['status' => 'migrated', 'path' => $targetPath];
        }

        return ['status' => 'local_noop', 'path' => $sourcePath];
    }

    public function response(?string $path, ?string $mimeType = null, ?string $downloadName = null, string $disposition = 'inline')
    {
        $path = $this->normalizePath($path);

        abort_if($path === '', 404);

        if ($this->usesAzure()) {
            try {
                return $this->azureResponse($path, $downloadName ?: basename($path), $disposition, $mimeType);
            } catch (\Throwable $e) {
                if (!$this->isNotFound($e)) {
                    throw $e;
                }
            }
        }

        $source = $this->findLocalSource($path);
        abort_unless($source, 404);

        return Storage::disk($source['disk'])->response(
            $source['path'],
            $downloadName ?: basename($source['path']),
            [
                'Content-Type' => $mimeType ?: $this->mimeType($source['disk'], $source['path']),
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ],
            $disposition
        );
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

        $this->deleteLocal($path);
    }

    public function deleteLocal(?string $path): int
    {
        $deleted = 0;

        foreach ($this->localCandidates($path) as $candidate) {
            if (Storage::disk($candidate['disk'])->exists($candidate['path'])) {
                Storage::disk($candidate['disk'])->delete($candidate['path']);
                $deleted++;
            }
        }

        return $deleted;
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

        return (bool) $this->findLocalSource($path);
    }

    public function normalizePath(?string $path): string
    {
        $path = $this->normalizeSourcePath($path);

        if (Str::startsWith($path, 'personals/fotos/')) {
            $path = 'personal/' . substr($path, strlen('personals/fotos/'));
        }

        if (Str::startsWith($path, 'fotos/personal/')) {
            $path = substr($path, strlen('fotos/'));
        }

        return ltrim($path, '/');
    }

    public function normalizeSourcePath(?string $path): string
    {
        $path = str_replace('\\', '/', trim((string) $path));
        $path = preg_replace('#^https?://[^/]+/storage/#i', '', (string) $path);
        $path = preg_replace('#^/?storage/#', '', (string) $path);
        $path = preg_replace('#^/?public/#', '', (string) $path);

        return ltrim((string) $path, '/');
    }

    public function localCandidates(?string $path): array
    {
        $source = $this->normalizeSourcePath($path);
        $normalized = $this->normalizePath($path);
        $basename = basename($normalized);

        $candidates = [
            ['disk' => 'local', 'path' => $source],
            ['disk' => 'public', 'path' => $source],
            ['disk' => 'local', 'path' => $normalized],
            ['disk' => 'public', 'path' => $normalized],
        ];

        if ($basename !== '' && $basename !== '.') {
            $candidates[] = ['disk' => 'local', 'path' => 'personals/fotos/' . $basename];
            $candidates[] = ['disk' => 'public', 'path' => 'personals/fotos/' . $basename];
        }

        $seen = [];

        return array_values(array_filter($candidates, function ($candidate) use (&$seen) {
            $key = $candidate['disk'] . ':' . $candidate['path'];

            if ($candidate['path'] === '' || isset($seen[$key])) {
                return false;
            }

            $seen[$key] = true;
            return true;
        }));
    }

    public function usesAzure(): bool
    {
        return (bool) config('services.azure_storage.fotos_enabled', false);
    }

    private function makePath(UploadedFile $file): string
    {
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin'));

        return 'personal/' . now()->format('Y/m') . '/' . Str::random(40) . '.' . $extension;
    }

    private function findLocalSource(?string $path): ?array
    {
        foreach ($this->localCandidates($path) as $candidate) {
            $disk = Storage::disk($candidate['disk']);

            if ($disk->exists($candidate['path'])) {
                return [
                    'disk' => $candidate['disk'],
                    'path' => $candidate['path'],
                    'absolute' => $disk->path($candidate['path']),
                ];
            }
        }

        return null;
    }

    private function putAzureStream(string $path, $stream, string $contentType): void
    {
        $optionsClass = '\\MicrosoftAzure\\Storage\\Blob\\Models\\CreateBlockBlobOptions';
        $options = new $optionsClass();
        $options->setContentType($contentType);

        $this->client()->createBlockBlob($this->container(), $path, $stream, $options);
    }

    private function azureResponse(string $path, string $downloadName, string $disposition, ?string $mimeType): StreamedResponse
    {
        $blob = $this->client()->getBlob($this->container(), $path);
        $properties = $blob->getProperties();
        $stream = $blob->getContentStream();

        $headers = [
            'Content-Type' => $mimeType ?: ($properties && $properties->getContentType()
                ? $properties->getContentType()
                : 'application/octet-stream'),
            'Content-Disposition' => $this->contentDisposition($downloadName, $disposition),
            'Cache-Control' => 'private, no-store, max-age=0',
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

    private function client()
    {
        if ($this->client) {
            return $this->client;
        }

        $clientClass = '\\MicrosoftAzure\\Storage\\Blob\\BlobRestProxy';

        if (!class_exists($clientClass)) {
            throw new RuntimeException('Instala microsoft/azure-storage-blob para guardar fotos en Azure.');
        }

        $accountName = trim((string) config('services.azure_storage.account_name'));
        $accountKey = trim((string) config('services.azure_storage.account_key'));

        if ($accountName === '' || $accountKey === '' || Str::startsWith($accountKey, 'PEGA_AQUI')) {
            throw new RuntimeException('Configura AZURE_STORAGE_NAME y AZURE_STORAGE_KEY para guardar fotos en Azure.');
        }

        $this->client = $clientClass::createBlobService($this->connectionString($accountName, $accountKey));

        return $this->client;
    }

    private function container(): string
    {
        $container = trim((string) config('services.azure_storage.fotos_container', 'fotos'));

        if ($container === '') {
            throw new RuntimeException('Configura AZURE_STORAGE_FOTOS_CONTAINER para guardar fotos en Azure.');
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
        $safeName = str_replace(['"', '\\'], '', $downloadName) ?: 'foto';
        $disposition = $disposition === 'attachment' ? 'attachment' : 'inline';

        return $disposition . '; filename="' . $safeName . '"';
    }

    private function mimeType(string $disk, string $path): string
    {
        return Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';
    }

    private function isNotFound(\Throwable $e): bool
    {
        $errorCode = method_exists($e, 'getErrorCode') ? (string) $e->getErrorCode() : '';

        return (int) $e->getCode() === 404
            || in_array($errorCode, ['BlobNotFound', 'ContainerNotFound'], true);
    }
}
