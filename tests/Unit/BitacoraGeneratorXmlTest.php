<?php

namespace Tests\Unit;

use App\Services\BitacoraGenerator;
use DOMDocument;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;
use ZipArchive;

class BitacoraGeneratorXmlTest extends TestCase
{
    public function test_escapa_caracteres_especiales_en_el_documento_generado(): void
    {
        if (!class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive es necesario para inspeccionar el DOCX.');
        }

        $generator = new TestableBitacoraGenerator();
        $phpWord = $generator->newPhpWordPublic();
        $phpWord->addSection()->addText('UNIDAD C102& APOYO <NORTE>');

        $path = tempnam(sys_get_temp_dir(), 'bitacora_xml_') . '.docx';

        try {
            IOFactory::createWriter($phpWord, 'Word2007')->save($path);

            $zip = new ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            $this->assertIsString($xml);
            $this->assertStringContainsString('C102&amp; APOYO &lt;NORTE&gt;', $xml);

            $document = new DOMDocument();
            $this->assertTrue($document->loadXML($xml));
        } finally {
            @unlink($path);
        }
    }

    public function test_normaliza_la_unidad_capturada_como_c102_ampersand(): void
    {
        $generator = new TestableBitacoraGenerator();

        $this->assertSame('C-1028', $generator->normalizeUnidadPublic('C102&'));
        $this->assertSame('C-1028', $generator->normalizeUnidadPublic('c1028'));
        $this->assertSame('22-2601', $generator->normalizeUnidadPublic('22-2601'));
    }
}

class TestableBitacoraGenerator extends BitacoraGenerator
{
    public function newPhpWordPublic(): PhpWord
    {
        return $this->newPhpWord();
    }

    public function normalizeUnidadPublic($value): string
    {
        return $this->normalizeUnidad($value);
    }
}
