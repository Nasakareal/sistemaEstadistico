<?php

namespace App\Services\Oficios;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OficioArchivoStorage
{
    private $client;

    public function putUploadedFile(UploadedFile $file, string $directory): string
    {
        $path = $this->makePath($file, $directory);

        if ($this->usesAzure()) {
            $this->putAzure($path, $file);
            return $path;
        }

        if (!Storage::disk('public')->putFileAs(dirname($path), $file, basename($path))) {
            throw new RuntimeException('No se pudo guardar el archivo del oficio en storage público.');
        }

        return $path;
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

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
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

        abort_unless(Storage::disk('public')->exists($path), 404);

        $headers = [
            'Content-Type' => Storage::disk('public')->mimeType($path) ?: 'application/octet-stream',
        ];

        return Storage::disk('public')->response($path, $downloadName ?: basename($path), $headers, $disposition);
    }

    private function putAzure(string $path, UploadedFile $file): void
    {
        $stream = fopen($file->getRealPath(), 'rb');

        if ($stream === false) {
            throw new RuntimeException('No se pudo abrir el archivo temporal del oficio.');
        }

        try {
            $optionsClass = '\\MicrosoftAzure\\Storage\\Blob\\Models\\CreateBlockBlobOptions';
            $options = new $optionsClass();
            $options->setContentType($file->getMimeType() ?: 'application/octet-stream');

            $this->client()->createBlockBlob($this->container(), $path, $stream, $options);
        } finally {
            fclose($stream);
        }
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
            throw new RuntimeException('Instala microsoft/azure-storage-blob para guardar oficios en Azure.');
        }

        $accountName = trim((string) config('services.azure_storage.account_name'));
        $accountKey = trim((string) config('services.azure_storage.account_key'));

        if ($accountName === '' || $accountKey === '' || Str::startsWith($accountKey, 'PEGA_AQUI')) {
            throw new RuntimeException('Configura AZURE_STORAGE_NAME y AZURE_STORAGE_KEY para guardar oficios en Azure.');
        }

        $connectionString = $this->connectionString($accountName, $accountKey);

        $this->client = $clientClass::createBlobService($connectionString);

        return $this->client;
    }

    private function usesAzure(): bool
    {
        return (bool) config('services.azure_storage.oficios_enabled', false);
    }

    private function container(): string
    {
        $container = trim((string) config('services.azure_storage.oficios_container', 'oficios'));

        if ($container === '') {
            throw new RuntimeException('Configura AZURE_STORAGE_OFICIOS_CONTAINER para guardar oficios en Azure.');
        }

        return $container;
    }

    private function makePath(UploadedFile $file, string $directory): string
    {
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin'));
        $directory = trim($directory, '/');

        return $directory . '/' . now()->format('Y/m') . '/' . Str::random(40) . '.' . $extension;
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

    private function normalizePath(?string $path): string
    {
        return ltrim(str_replace('\\', '/', trim((string) $path)), '/');
    }

    private function contentDisposition(string $downloadName, string $disposition): string
    {
        $safeName = str_replace(['"', '\\'], '', $downloadName) ?: 'archivo';
        $disposition = $disposition === 'attachment' ? 'attachment' : 'inline';

        return $disposition . '; filename="' . $safeName . '"';
    }

    private function isNotFound(\Throwable $e): bool
    {
        $errorCode = method_exists($e, 'getErrorCode') ? (string) $e->getErrorCode() : '';

        return (int) $e->getCode() === 404
            || in_array($errorCode, ['BlobNotFound', 'ContainerNotFound'], true);
    }
}
