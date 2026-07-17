<?php

namespace App\Services\Documentos;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class PdfCompressor
{
    /**
     * Devuelve la ruta de un PDF temporal comprimido o null cuando debe usarse
     * el archivo original. Quien recibe una ruta es responsable de borrarla.
     */
    public function compress(string $inputPath): ?string
    {
        if (!(bool) config('pdf_compression.enabled', true)) {
            return null;
        }

        $inputSize = is_file($inputPath) ? filesize($inputPath) : false;

        if ($inputSize === false || $inputSize <= 0) {
            return $this->failure('No se pudo leer el PDF original para comprimirlo.');
        }

        if ($inputSize < max(0, (int) config('pdf_compression.min_bytes', 1048576))) {
            return null;
        }

        if ((bool) config('pdf_compression.skip_signed', true) && $this->containsDigitalSignature($inputPath)) {
            $this->writeLog('info', 'Compresion PDF omitida para preservar una firma digital.', [
                'bytes' => $inputSize,
            ]);

            return null;
        }

        $binary = $this->resolveBinary();

        if ($binary === null) {
            return $this->failure(
                'No se encontro Ghostscript. Configura PDF_COMPRESSION_BINARY para comprimir los PDF.'
            );
        }

        $outputPath = $this->temporaryPdfPath();

        try {
            if (!$this->execute($binary, $inputPath, $outputPath)) {
                $this->deleteTemporary($outputPath);
                return $this->failure('Ghostscript no pudo comprimir el PDF; se conservara el original.');
            }

            $outputSize = is_file($outputPath) ? filesize($outputPath) : false;

            if ($outputSize === false || $outputSize <= 0 || !$this->hasPdfHeader($outputPath)) {
                $this->deleteTemporary($outputPath);
                return $this->failure('El resultado de la compresion no es un PDF valido.');
            }

            $savingsPercent = (($inputSize - $outputSize) / $inputSize) * 100;
            $minimumSavings = max(0, (float) config('pdf_compression.min_savings_percent', 5));

            if ($outputSize >= $inputSize || $savingsPercent < $minimumSavings) {
                $this->deleteTemporary($outputPath);
                return null;
            }

            $this->writeLog('info', 'PDF comprimido automaticamente.', [
                'original_bytes' => $inputSize,
                'compressed_bytes' => $outputSize,
                'savings_percent' => round($savingsPercent, 2),
            ]);

            return $outputPath;
        } catch (\Throwable $e) {
            $this->deleteTemporary($outputPath);

            return $this->failure('Error al comprimir el PDF: ' . $e->getMessage(), $e);
        }
    }

    protected function execute(string $binary, string $inputPath, string $outputPath): bool
    {
        $preset = strtolower(trim((string) config('pdf_compression.preset', 'ebook')));

        if (!in_array($preset, ['screen', 'ebook', 'printer', 'prepress', 'default'], true)) {
            $preset = 'ebook';
        }

        $process = new Process([
            $binary,
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.7',
            '-dPDFSETTINGS=/' . $preset,
            '-dNOPAUSE',
            '-dQUIET',
            '-dBATCH',
            '-dSAFER',
            '-dDetectDuplicateImages=true',
            '-dCompressFonts=true',
            '-dSubsetFonts=true',
            '-sOutputFile=' . $outputPath,
            $inputPath,
        ]);
        $process->setTimeout(max(10, (int) config('pdf_compression.timeout', 180)));
        $process->run();

        if (!$process->isSuccessful()) {
            $this->writeLog('warning', 'Ghostscript devolvio un error al comprimir un PDF.', [
                'exit_code' => $process->getExitCode(),
                'error' => mb_substr(trim($process->getErrorOutput()), 0, 1000),
            ]);

            return false;
        }

        return true;
    }

    protected function resolveBinary(): ?string
    {
        $configured = trim((string) config('pdf_compression.binary', ''));
        $finder = new ExecutableFinder();

        if ($configured !== '') {
            if (is_file($configured)) {
                return $configured;
            }

            return $finder->find($configured);
        }

        foreach (PHP_OS_FAMILY === 'Windows' ? ['gswin64c', 'gswin32c', 'gs'] : ['gs'] as $candidate) {
            $found = $finder->find($candidate);

            if ($found !== null) {
                return $found;
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $matches = [];

            foreach (array_unique(array_filter([
                getenv('ProgramW6432'),
                getenv('ProgramFiles'),
                getenv('ProgramFiles(x86)'),
            ])) as $programFiles) {
                $matches = array_merge(
                    $matches,
                    glob(rtrim($programFiles, '\\/') . '/gs/gs*/bin/gswin64c.exe') ?: [],
                    glob(rtrim($programFiles, '\\/') . '/gs/gs*/bin/gswin32c.exe') ?: []
                );
            }

            if ($matches !== []) {
                natsort($matches);
                return (string) end($matches);
            }
        }

        return null;
    }

    private function temporaryPdfPath(): string
    {
        $directory = storage_path('app/tmp/pdfs');

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            $directory = sys_get_temp_dir();
        }

        if (!is_writable($directory)) {
            $directory = sys_get_temp_dir();
        }

        $temporary = @tempnam($directory, 'compress_');

        if ($temporary === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal para comprimir el PDF.');
        }

        $outputPath = $temporary . '.pdf';
        @unlink($temporary);

        return $outputPath;
    }

    private function containsDigitalSignature(string $path): bool
    {
        $stream = @fopen($path, 'rb');

        if ($stream === false) {
            return false;
        }

        $needle = '/ByteRange';
        $carry = '';

        try {
            while (!feof($stream)) {
                $chunk = fread($stream, 65536);

                if ($chunk === false) {
                    break;
                }

                $content = $carry . $chunk;

                if (strpos($content, $needle) !== false) {
                    return true;
                }

                $carry = substr($content, -(strlen($needle) - 1));
            }
        } finally {
            fclose($stream);
        }

        return false;
    }

    private function hasPdfHeader(string $path): bool
    {
        $stream = @fopen($path, 'rb');

        if ($stream === false) {
            return false;
        }

        try {
            return fread($stream, 5) === '%PDF-';
        } finally {
            fclose($stream);
        }
    }

    private function failure(string $message, ?\Throwable $exception = null): ?string
    {
        $this->writeLog('warning', $message, $exception ? ['exception' => $exception] : []);

        if ((bool) config('pdf_compression.required', false)) {
            throw new RuntimeException($message, 0, $exception);
        }

        return null;
    }

    private function deleteTemporary(?string $path): void
    {
        if ($path && is_file($path)) {
            @unlink($path);
        }
    }

    private function writeLog(string $level, string $message, array $context = []): void
    {
        try {
            Log::log($level, $message, $context);
        } catch (\Throwable $e) {
            // La telemetria no debe impedir que el documento se almacene.
        }
    }
}
