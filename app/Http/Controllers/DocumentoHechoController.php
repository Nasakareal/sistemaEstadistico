<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hechos;
use App\Services\Croquis\CroquisArchivoStorage;
use App\Services\CroquisPreviewService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class DocumentoHechoController extends Controller
{
    private $croquisPreviewService;
    private $croquisStorage;

    public function __construct(CroquisPreviewService $croquisPreviewService, CroquisArchivoStorage $croquisStorage)
    {
        $this->croquisPreviewService = $croquisPreviewService;
        $this->croquisStorage = $croquisStorage;
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
            'faviconSrc' => $this->imageSource(public_path('logofondo.png')),
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

        $blobSource = $this->croquisStorage->dataUri($path);

        if ($blobSource) {
            return $blobSource;
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
