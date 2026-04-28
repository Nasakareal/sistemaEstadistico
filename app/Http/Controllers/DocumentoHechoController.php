<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hechos;
use App\Services\CroquisPreviewService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class DocumentoHechoController extends Controller
{
    private $croquisPreviewService;

    public function __construct(CroquisPreviewService $croquisPreviewService)
    {
        $this->croquisPreviewService = $croquisPreviewService;
    }

    public function descargarDocx($id)
    {
        // Obtener el hecho con los vehículos, conductores y lesionados relacionados
        $hecho = Hechos::with(['vehiculos.conductores', 'lesionados', 'croquis'])->findOrFail($id);

        if ($hecho->croquis) {
            $this->croquisPreviewService->ensure($hecho->croquis, $hecho);
            $hecho->load('croquis');
        }

        $pdf = Pdf::loadView('hechos.reporte_docx', [
            'hecho' => $hecho,
            'pdfMode' => true,
            'faviconSrc' => $this->imageSource(public_path('ssp.jpg')),
            'croquisPreviewSrc' => $this->croquisPreviewSource(optional($hecho->croquis)->imagen_preview),
        ])->setPaper('legal', 'portrait')
            ->setOptions([
                'dpi' => 96,
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => true,
                'chroot' => base_path(),
            ]);

        return $pdf->download('hecho_' . $hecho->folio_c5i . '.pdf');
    }

    private function croquisPreviewSource(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['data:image', 'http://', 'https://'])) {
            return $path;
        }

        return $this->imageSource(public_path(ltrim($path, '/')));
    }

    private function imageSource(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        return $path;
    }
}
