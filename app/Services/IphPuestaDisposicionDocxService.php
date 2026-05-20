<?php

namespace App\Services;

use App\Models\Hechos;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class IphPuestaDisposicionDocxService
{
    public function generar(Hechos $hecho, array $mapeo): array
    {
        $html = view('hechos.iph_puesta_disposicion.print', [
            'hecho' => $hecho,
            'mapeo' => $mapeo,
            'wordMode' => true,
        ])->render();

        $html = $this->prepararHtmlParaWord($html);

        $directorio = storage_path('app/temp');
        File::ensureDirectoryExists($directorio);

        $path = $directorio . DIRECTORY_SEPARATOR . uniqid('iph_puesta_disposicion_', true) . '.docx';
        $this->crearDocxDesdeHtml($html, $path);

        $folio = Str::slug((string) ($hecho->folio_c5i ?: $hecho->id), '_') ?: (string) $hecho->id;

        return [$path, "iph_puesta_disposicion_{$folio}.docx"];
    }

    private function prepararHtmlParaWord(string $html): string
    {
        $html = preg_replace_callback(
            '/(<img\b[^>]*\bsrc=["\'])([^"\']+)(["\'][^>]*>)/i',
            function (array $matches): string {
                return $matches[1] . $this->srcParaWord($matches[2]) . $matches[3];
            },
            $html
        );

        $html = preg_replace('/<\s*(\/?)\s*(main|section|header|footer|article|nav)(\b[^>]*)>/i', '<$1div$3>', $html);

        $wordCss = <<<'CSS'
<style>
    @page { size: 8.5in 14in; margin: .39in; }
    @page custody-letter { size: 8.5in 11in; margin: .39in; }
    .toolbar { display: none !important; }
    .iph-front,
    .iph-continuation,
    .iph-narrative-page,
    .iph-vehicle-page,
    .custody-page,
    .custody-delivery-page { page: custody-letter; }
</style>
CSS;

        $html = preg_replace('/<\/head>/i', $wordCss . '</head>', $html, 1);

        return preg_replace(
            '/<html([^>]*)>/i',
            '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns:m="http://schemas.microsoft.com/office/2004/12/omml"$1>',
            $html,
            1
        );
    }

    private function srcParaWord(string $src): string
    {
        if (preg_match('/^data:image\//i', $src)) {
            return $src;
        }

        $path = $this->resolverPublicPath($src);

        if (!$path || !is_file($path)) {
            return $src;
        }

        $mime = $this->mimeType($path);
        $data = base64_encode((string) file_get_contents($path));

        return "data:{$mime};base64,{$data}";
    }

    private function resolverPublicPath(string $src): ?string
    {
        $src = trim($src);

        if ($src === '') {
            return null;
        }

        if (is_file($src)) {
            return $src;
        }

        $path = parse_url($src, PHP_URL_PATH) ?: $src;
        $path = urldecode(str_replace('\\', '/', $path));
        $path = ltrim($path, '/');

        $candidatos = [
            public_path($path),
            public_path('storage/' . $path),
        ];

        foreach (['storage/', 'img/'] as $marca) {
            $pos = strpos($path, $marca);

            if ($pos !== false) {
                $candidatos[] = public_path(substr($path, $pos));
            }
        }

        foreach ($candidatos as $candidato) {
            if (is_file($candidato)) {
                return $candidato;
            }
        }

        return null;
    }

    private function mimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'jpg' || $extension === 'jpeg') {
            return 'image/jpeg';
        }

        if ($extension === 'gif') {
            return 'image/gif';
        }

        if ($extension === 'webp') {
            return 'image/webp';
        }

        if ($extension === 'svg') {
            return 'image/svg+xml';
        }

        return 'image/png';
    }

    private function crearDocxDesdeHtml(string $html, string $path): void
    {
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo DOCX.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->packageRelsXml());
        $zip->addFromString('word/document.xml', $this->documentXml());
        $zip->addFromString('word/_rels/document.xml.rels', $this->documentRelsXml());
        $zip->addFromString('word/afchunk.html', $html);
        $zip->close();
    }

    private function contentTypesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Default Extension="html" ContentType="text/html"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
XML;
    }

    private function packageRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML;
    }

    private function documentXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <w:body>
        <w:altChunk r:id="rId1"/>
        <w:sectPr>
            <w:pgSz w:w="12240" w:h="20160"/>
            <w:pgMar w:top="567" w:right="567" w:bottom="567" w:left="567" w:header="0" w:footer="0" w:gutter="0"/>
        </w:sectPr>
    </w:body>
</w:document>
XML;
    }

    private function documentRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/aFChunk" Target="afchunk.html"/>
</Relationships>
XML;
    }
}
