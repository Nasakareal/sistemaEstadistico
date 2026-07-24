<?php

namespace App\Services\Alcoholimetria;

use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class AlcoholimetriaMensualDocxGenerator
{
    private AlcoholimetriaMensualService $mensualService;

    public function __construct(AlcoholimetriaMensualService $mensualService)
    {
        $this->mensualService = $mensualService;
    }

    public function generar(Carbon $mes, ?string $destino = null): array
    {
        return $this->generarConResumen(
            $this->mensualService->resumen($mes),
            $destino
        );
    }

    public function generarConResumen(array $resumen, ?string $destino = null): array
    {
        $plantilla = $this->plantillaPath();

        if (!is_file($plantilla)) {
            throw new RuntimeException('No se encontró la plantilla mensual de alcoholimetría.');
        }

        $nombre = $this->nombreArchivo($resumen);
        $destino = $destino ?: storage_path('app/temp/' . $nombre);
        File::ensureDirectoryExists(dirname($destino));

        if (!copy($plantilla, $destino)) {
            throw new RuntimeException('No se pudo copiar la plantilla mensual de alcoholimetría.');
        }

        $this->reemplazarVariables($destino, $resumen['variables'] ?? []);

        return [
            'path' => $destino,
            'name' => basename($destino),
            'contents' => file_get_contents($destino),
            'sha256' => hash_file('sha256', $destino),
            'resumen' => $resumen,
        ];
    }

    private function reemplazarVariables(string $archivo, array $variables): void
    {
        $reemplazos = [];
        foreach ($variables as $variable => $valor) {
            $reemplazos['$' . $variable] = (string) $valor;
        }

        $zip = new ZipArchive();
        if ($zip->open($archivo) !== true) {
            throw new RuntimeException('No se pudo abrir el reporte mensual de alcoholimetría.');
        }

        try {
            $xml = $zip->getFromName('word/document.xml');
            if ($xml === false) {
                throw new RuntimeException('La plantilla no contiene word/document.xml.');
            }

            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = true;
            $dom->formatOutput = false;

            if (!$dom->loadXML($xml, LIBXML_NONET)) {
                throw new RuntimeException('El XML principal de la plantilla no es válido.');
            }

            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace(
                'w',
                'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
            );

            foreach ($xpath->query('//w:p') as $parrafo) {
                $textos = $xpath->query('.//w:t', $parrafo);
                if ($textos->length === 0) {
                    continue;
                }

                $textoCompleto = '';
                foreach ($textos as $texto) {
                    $textoCompleto .= $texto->textContent;
                }

                $textoNuevo = strtr($textoCompleto, $reemplazos);
                if ($textoNuevo === $textoCompleto) {
                    continue;
                }

                $textos->item(0)->nodeValue = $textoNuevo;
                for ($indice = 1; $indice < $textos->length; $indice++) {
                    $textos->item($indice)->nodeValue = '';
                }
            }

            $xmlFinal = $dom->saveXML();
            if (preg_match('/\$[A-Za-z][A-Za-z0-9_]*/', $xmlFinal, $pendiente)) {
                throw new RuntimeException(
                    'Quedó una variable sin reemplazar en la plantilla: ' . $pendiente[0]
                );
            }

            if (!$zip->addFromString('word/document.xml', $xmlFinal)) {
                throw new RuntimeException('No se pudo actualizar el contenido del reporte.');
            }
        } finally {
            $zip->close();
        }
    }

    private function plantillaPath(): string
    {
        $configurada = trim((string) config(
            'services.alcoholimetria_mensual.template_path',
            ''
        ));

        return $configurada !== ''
            ? $configurada
            : public_path('templates/alcohol_bernardo.docx');
    }

    private function nombreArchivo(array $resumen): string
    {
        $mes = Carbon::parse((string) ($resumen['mes'] ?? now()->startOfMonth()));
        $municipio = Str::upper(Str::ascii((string) ($resumen['municipio'] ?? 'MUNICIPIO')));
        $municipio = preg_replace('/[^A-Z0-9]+/', '_', $municipio);
        $mesTexto = Str::upper(Str::ascii($mes->locale('es')->translatedFormat('F')));

        return sprintf(
            'CONCENTRADO_ALCOHOLIMETRIA_%s_%s_%s.docx',
            trim($municipio, '_'),
            $mesTexto,
            $mes->format('Y')
        );
    }
}
