<?php

namespace Tests\Unit;

use App\Services\Documentos\DocumentoArchivoStorage;
use App\Services\Documentos\PdfCompressor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfCompressorTest extends TestCase
{
    private $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_conserva_la_version_comprimida_solo_cuando_es_pdf_y_pesa_menos(): void
    {
        $this->configureCompression();
        $input = $this->pdfFile(str_repeat('contenido-original-', 500));

        $compressor = new class extends PdfCompressor {
            public $executions = 0;

            protected function resolveBinary(): ?string
            {
                return 'ghostscript-de-prueba';
            }

            protected function execute(string $binary, string $inputPath, string $outputPath): bool
            {
                $this->executions++;
                file_put_contents($outputPath, "%PDF-1.7\ncomprimido\n%%EOF");
                return true;
            }
        };

        $output = $compressor->compress($input);

        $this->assertNotNull($output);
        $this->temporaryFiles[] = $output;
        $this->assertSame(1, $compressor->executions);
        $this->assertLessThan(filesize($input), filesize($output));
        $this->assertSame('%PDF-', file_get_contents($output, false, null, 0, 5));
    }

    public function test_descarta_el_resultado_si_no_reduce_el_tamano(): void
    {
        $this->configureCompression();
        $input = $this->pdfFile("%PDF-1.7\npequeno\n%%EOF");

        $compressor = new class extends PdfCompressor {
            public $outputPath;

            protected function resolveBinary(): ?string
            {
                return 'ghostscript-de-prueba';
            }

            protected function execute(string $binary, string $inputPath, string $outputPath): bool
            {
                $this->outputPath = $outputPath;
                file_put_contents($outputPath, "%PDF-1.7\n" . str_repeat('mas-grande', 20) . "\n%%EOF");
                return true;
            }
        };

        $this->assertNull($compressor->compress($input));
        $this->assertFileDoesNotExist($compressor->outputPath);
    }

    public function test_no_modifica_pdf_con_firma_digital(): void
    {
        $this->configureCompression();
        config()->set('pdf_compression.skip_signed', true);
        $input = $this->pdfFile("%PDF-1.7\n/ByteRange [0 10 20 30]\n%%EOF");

        $compressor = new class extends PdfCompressor {
            public $executions = 0;

            protected function resolveBinary(): ?string
            {
                return 'ghostscript-de-prueba';
            }

            protected function execute(string $binary, string $inputPath, string $outputPath): bool
            {
                $this->executions++;
                return true;
            }
        };

        $this->assertNull($compressor->compress($input));
        $this->assertSame(0, $compressor->executions);
    }

    public function test_almacenamiento_guarda_el_pdf_comprimido_y_limpia_el_temporal(): void
    {
        Storage::fake('public');
        config()->set('services.azure_storage.documentos_enabled', false);

        $input = $this->pdfFile("%PDF-1.7\noriginal\n%%EOF");
        $compressed = $this->pdfFile("%PDF-1.7\noptimizado\n%%EOF");
        $compressor = $this->createMock(PdfCompressor::class);
        $compressor->expects($this->once())
            ->method('compress')
            ->with($input)
            ->willReturn($compressed);

        $storage = new DocumentoArchivoStorage($compressor);
        $upload = new UploadedFile($input, 'dictamen.pdf', 'application/pdf', null, true);
        $path = $storage->putUploadedPdf($upload, 'dictamenes');

        Storage::disk('public')->assertExists($path);
        $this->assertSame("%PDF-1.7\noptimizado\n%%EOF", Storage::disk('public')->get($path));
        $this->assertFileDoesNotExist($compressed);
    }

    private function configureCompression(): void
    {
        config()->set('pdf_compression.enabled', true);
        config()->set('pdf_compression.required', true);
        config()->set('pdf_compression.min_bytes', 1);
        config()->set('pdf_compression.min_savings_percent', 1);
        config()->set('pdf_compression.skip_signed', false);
    }

    private function pdfFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pdf_test_');
        file_put_contents($path, $content);
        $this->temporaryFiles[] = $path;

        return $path;
    }
}
