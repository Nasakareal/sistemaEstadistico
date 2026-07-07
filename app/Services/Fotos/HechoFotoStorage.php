<?php

namespace App\Services\Fotos;

use App\Models\Hechos;
use App\Models\Vehiculo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HechoFotoStorage
{
    private $client;

    public function putUploadedFile(UploadedFile $file, Hechos $hecho, string $kind, ?Vehiculo $vehiculo = null): string
    {
        $path = $this->makePath($file, $hecho, $kind, $vehiculo);

        if ($this->usesAzure()) {
            $stream = fopen($file->getRealPath(), 'rb');

            if ($stream === false) {
                throw new RuntimeException('No se pudo abrir el archivo temporal de la foto del hecho.');
            }

            try {
                $this->putStream($path, $stream, $file->getMimeType() ?: 'application/octet-stream');
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            return $path;
        }

        if (!Storage::disk('public')->putFileAs(dirname($path), $file, basename($path))) {
            throw new RuntimeException('No se pudo guardar la foto del hecho en almacenamiento local.');
        }

        return $path;
    }

    public function putPublicFile(string $sourcePath, ?string $targetPath = null): array
    {
        $sourcePath = $this->normalizeSourcePath($sourcePath);
        $targetPath = $this->normalizePath($targetPath ?: $sourcePath);

        if ($sourcePath === '' || $targetPath === '') {
            return ['status' => 'empty', 'path' => $targetPath];
        }

        if ($this->usesAzure() && $this->targetBlobExists($targetPath)) {
            return ['status' => 'already_exists', 'path' => $targetPath];
        }

        $source = $this->findLocalSource($sourcePath);

        if (!$source) {
            if ($this->usesAzure()) {
                $legacyBlob = $this->getBlobFromContainer($this->legacyContainer(), $targetPath);

                if ($legacyBlob) {
                    $stream = $legacyBlob->getContentStream();

                    try {
                        $properties = $legacyBlob->getProperties();
                        $this->putStream(
                            $targetPath,
                            $stream,
                            $properties && $properties->getContentType()
                                ? $properties->getContentType()
                                : 'application/octet-stream'
                        );
                    } finally {
                        if (is_resource($stream)) {
                            fclose($stream);
                        }
                    }

                    return ['status' => 'copied', 'path' => $targetPath];
                }
            }

            return [
                'status' => $this->usesAzure() && $this->targetBlobExists($targetPath) ? 'already_exists' : 'missing_source',
                'path' => $targetPath,
            ];
        }

        if ($this->usesAzure()) {
            $stream = fopen($source['absolute'], 'rb');

            if ($stream === false) {
                throw new RuntimeException('No se pudo abrir la foto local: ' . $sourcePath);
            }

            try {
                $this->putStream($targetPath, $stream, $this->mimeType($source['disk'], $source['path']));
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            return ['status' => 'copied', 'path' => $targetPath];
        }

        return ['status' => 'local_noop', 'path' => $sourcePath];
    }

    public function response(?string $path, ?string $downloadName = null)
    {
        $path = $this->normalizePath($path);

        abort_if($path === '', 404);

        if ($this->usesAzure()) {
            try {
                return $this->azureResponse($path, $downloadName ?: basename($path));
            } catch (\Throwable $e) {
                if (!$this->isNotFound($e)) {
                    throw $e;
                }
            }

            try {
                return $this->azureResponse($path, $downloadName ?: basename($path), $this->legacyContainer());
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
                'Content-Type' => $this->mimeType($source['disk'], $source['path']),
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline'
        );
    }

    public function url(?string $path, int $minutes = 120): ?string
    {
        $path = $this->normalizePath($path);

        if ($path === '') {
            return null;
        }

        return URL::temporarySignedRoute(
            'hechos.fotos.signed',
            now()->addMinutes($minutes),
            ['path' => $path]
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

    public function localExistingCount(?string $path): int
    {
        $count = 0;

        foreach ($this->localCandidates($path) as $candidate) {
            if (Storage::disk($candidate['disk'])->exists($candidate['path'])) {
                $count++;
            }
        }

        return $count;
    }

    public function temporaryLocalPath(?string $path): ?string
    {
        $path = $this->normalizePath($path);

        if ($path === '') {
            return null;
        }

        $source = $this->findLocalSource($path);

        if ($source) {
            return $source['absolute'];
        }

        if (!$this->usesAzure()) {
            return null;
        }

        $blob = $this->getBlobFromContainer($this->container(), $path)
            ?: $this->getBlobFromContainer($this->legacyContainer(), $path);

        if (!$blob) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: 'img';
        $extension = preg_replace('/[^a-z0-9]+/', '', $extension) ?: 'img';
        $localPath = storage_path('app/temp/' . uniqid('hecho_foto_', true) . '.' . $extension);
        File::ensureDirectoryExists(dirname($localPath));

        $sourceStream = $blob->getContentStream();
        $targetStream = fopen($localPath, 'wb');

        if ($targetStream === false) {
            if (is_resource($sourceStream)) {
                fclose($sourceStream);
            }

            return null;
        }

        try {
            stream_copy_to_stream($sourceStream, $targetStream);
        } finally {
            if (is_resource($sourceStream)) {
                fclose($sourceStream);
            }

            fclose($targetStream);
        }

        return $localPath;
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

    public function sourceExists(?string $path): bool
    {
        $path = $this->normalizePath($path);

        if ($path === '') {
            return false;
        }

        if ($this->findLocalSource($path)) {
            return true;
        }

        return $this->usesAzure()
            && $this->blobExists($this->legacyContainer(), $path);
    }

    public function targetBlobExists(?string $path): bool
    {
        $path = $this->normalizePath($path);

        return $this->usesAzure()
            && $this->blobExists($this->container(), $path);
    }

    public function usesAzure(): bool
    {
        return (bool) config('services.azure_storage.hechos_fotos_enabled', false);
    }

    public function normalizePath(?string $path): string
    {
        $path = $this->normalizeSourcePath($path);

        if (Str::startsWith($path, 'hechos-fotos/')) {
            $path = substr($path, strlen('hechos-fotos/'));
        }

        if (Str::startsWith($path, 'fotos/hechos/')) {
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
        $path = preg_replace('#^https?://[^/]+/hechos-fotos/#i', '', (string) $path);
        $path = preg_replace('#^https?://[^/]+/fotos/#i', '', (string) $path);

        return ltrim((string) $path, '/');
    }

    public function localCandidates(?string $path): array
    {
        $source = $this->normalizeSourcePath($path);
        $normalized = $this->normalizePath($path);

        $candidates = [
            ['disk' => 'public', 'path' => $source],
            ['disk' => 'local', 'path' => $source],
            ['disk' => 'public', 'path' => $normalized],
            ['disk' => 'local', 'path' => $normalized],
        ];

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

    private function makePath(UploadedFile $file, Hechos $hecho, string $kind, ?Vehiculo $vehiculo): string
    {
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin'));
        $extension = preg_replace('/[^a-z0-9]+/', '', $extension) ?: 'bin';
        $kind = $this->sanitizeSegment($kind ?: 'foto');
        $year = $this->yearForHecho($hecho);
        $fileName = $kind . '_' . now('America/Mexico_City')->format('Ymd_His') . '_' . Str::random(12) . '.' . $extension;

        if ($vehiculo) {
            return 'hechos/' . $year . '/hecho_' . $hecho->id . '/vehiculos/vehiculo_' . $vehiculo->id . '/' . $fileName;
        }

        return 'hechos/' . $year . '/hecho_' . $hecho->id . '/' . $fileName;
    }

    private function putStream(string $path, $stream, string $contentType): void
    {
        $path = $this->normalizePath($path);

        if ($path === '') {
            throw new RuntimeException('Ruta destino de Blob vacia.');
        }

        $optionsClass = '\\MicrosoftAzure\\Storage\\Blob\\Models\\CreateBlockBlobOptions';
        $options = new $optionsClass();
        $options->setContentType($contentType ?: 'application/octet-stream');

        $this->client()->createBlockBlob($this->container(), $path, $stream, $options);
    }

    private function azureResponse(string $path, string $downloadName, ?string $container = null): StreamedResponse
    {
        $blob = $this->client()->getBlob($container ?: $this->container(), $path);
        $properties = $blob->getProperties();
        $stream = $blob->getContentStream();

        $headers = [
            'Content-Type' => $properties && $properties->getContentType()
                ? $properties->getContentType()
                : 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $this->safeDownloadName($downloadName) . '"',
            'Cache-Control' => 'private, max-age=3600',
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

    private function client()
    {
        if ($this->client) {
            return $this->client;
        }

        $clientClass = '\\MicrosoftAzure\\Storage\\Blob\\BlobRestProxy';

        if (!class_exists($clientClass)) {
            throw new RuntimeException('Instala microsoft/azure-storage-blob para guardar fotos de hechos en Azure.');
        }

        $accountName = trim((string) config('services.azure_storage.account_name'));
        $accountKey = trim((string) config('services.azure_storage.account_key'));

        if ($accountName === '' || $accountKey === '' || Str::startsWith($accountKey, 'PEGA_AQUI')) {
            throw new RuntimeException('Configura AZURE_STORAGE_NAME y AZURE_STORAGE_KEY para guardar fotos de hechos en Azure.');
        }

        $this->client = $clientClass::createBlobService($this->connectionString($accountName, $accountKey));

        return $this->client;
    }

    private function container(): string
    {
        $container = trim((string) config('services.azure_storage.hechos_fotos_container', 'hechos-fotos'));

        if ($container === '') {
            throw new RuntimeException('Configura AZURE_STORAGE_HECHOS_FOTOS_CONTAINER para guardar fotos de hechos en Azure.');
        }

        return $container;
    }

    private function legacyContainer(): ?string
    {
        $container = trim((string) config('services.azure_storage.fotos_container', 'fotos'));

        if ($container === '' || $container === $this->container()) {
            return null;
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

    private function yearForHecho(Hechos $hecho): string
    {
        try {
            return $hecho->fecha
                ? \Carbon\Carbon::parse($hecho->fecha)->format('Y')
                : now('America/Mexico_City')->format('Y');
        } catch (\Throwable $e) {
            return now('America/Mexico_City')->format('Y');
        }
    }

    private function sanitizeSegment(string $segment): string
    {
        $segment = preg_replace('/[^A-Za-z0-9_-]+/', '_', $segment);
        $segment = trim((string) $segment, '_-');

        return $segment !== '' ? substr($segment, 0, 80) : 'foto';
    }

    private function safeDownloadName(string $name): string
    {
        $name = str_replace(['"', '\\'], '', $name);

        return $name !== '' ? $name : 'foto.jpg';
    }

    private function mimeType(string $disk, string $path): string
    {
        return Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';
    }

    private function getBlobFromContainer(?string $container, string $path)
    {
        if (!$container || $path === '') {
            return null;
        }

        try {
            return $this->client()->getBlob($container, $path);
        } catch (\Throwable $e) {
            if (!$this->isNotFound($e)) {
                throw $e;
            }
        }

        return null;
    }

    private function blobExists(?string $container, string $path): bool
    {
        if (!$container || $path === '') {
            return false;
        }

        try {
            $this->client()->getBlobProperties($container, $path);
            return true;
        } catch (\Throwable $e) {
            if (!$this->isNotFound($e)) {
                throw $e;
            }
        }

        return false;
    }

    private function isNotFound(\Throwable $e): bool
    {
        $errorCode = method_exists($e, 'getErrorCode') ? (string) $e->getErrorCode() : '';

        return (int) $e->getCode() === 404
            || in_array($errorCode, ['BlobNotFound', 'ContainerNotFound'], true);
    }
}
