<?php

namespace App\Http\Controllers;

use App\Models\Croquis;
use App\Models\Hechos;
use App\Services\Croquis\CroquisArchivoStorage;
use App\Services\CroquisPreviewService;
use Illuminate\Http\Request;

class CroquisController extends Controller
{
    private $previewService;
    private $archivoStorage;

    public function __construct(CroquisPreviewService $previewService, CroquisArchivoStorage $archivoStorage)
    {
        $this->previewService = $previewService;
        $this->archivoStorage = $archivoStorage;
    }

    public function show(Hechos $hecho)
    {
        $croquis = Croquis::where('hecho_id', $hecho->id)
            ->latest('id')
            ->first();

        return view('croquis.show', compact('hecho', 'croquis'));
    }

    public function store(Request $request, Hechos $hecho)
    {
        $request->validate([
            'json_dibujo' => ['nullable', 'string'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'plantilla' => ['nullable', 'string', 'max:255'],
            'orientacion' => ['nullable', 'string', 'max:255'],
            'escala' => ['nullable', 'string', 'max:255'],
            'imagen_preview' => ['nullable', 'string'],
            'pdf_path' => ['nullable', 'string'],
        ]);

        $croquisActual = Croquis::where('hecho_id', $hecho->id)->first();
        $previewPath = $this->guardarPreview($request->imagen_preview, $hecho, optional($croquisActual)->imagen_preview);

        $croquis = Croquis::updateOrCreate(
            ['hecho_id' => $hecho->id],
            [
                'titulo' => $request->titulo,
                'plantilla' => $request->plantilla,
                'orientacion' => $request->orientacion,
                'escala' => $request->escala,
                'json_dibujo' => $request->json_dibujo,
                'imagen_preview' => $previewPath,
                'pdf_path' => $request->pdf_path,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        $this->previewService->ensure($croquis->fresh(), $hecho);

        return redirect()
            ->route('croquis.show', $hecho->id)
            ->with('success', 'Croquis guardado correctamente.');
    }

    public function update(Request $request, Hechos $hecho)
    {
        $request->validate([
            'json_dibujo' => ['nullable', 'string'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'plantilla' => ['nullable', 'string', 'max:255'],
            'orientacion' => ['nullable', 'string', 'max:255'],
            'escala' => ['nullable', 'string', 'max:255'],
            'imagen_preview' => ['nullable', 'string'],
            'pdf_path' => ['nullable', 'string'],
        ]);

        $croquis = Croquis::where('hecho_id', $hecho->id)->first();

        if (!$croquis) {
            return $this->store($request, $hecho);
        }

        $previewPath = $this->guardarPreview($request->imagen_preview, $hecho, $croquis->imagen_preview);

        $croquis->update([
            'titulo' => $request->titulo,
            'plantilla' => $request->plantilla,
            'orientacion' => $request->orientacion,
            'escala' => $request->escala,
            'json_dibujo' => $request->json_dibujo,
            'imagen_preview' => $previewPath,
            'pdf_path' => $request->pdf_path,
            'updated_by' => auth()->id(),
        ]);

        $this->previewService->ensure($croquis->fresh(), $hecho);

        return redirect()
            ->route('croquis.show', $hecho->id)
            ->with('success', 'Croquis actualizado correctamente.');
    }

    public function destroy(Hechos $hecho)
    {
        $croquis = Croquis::where('hecho_id', $hecho->id)->first();

        if ($croquis) {
            $this->archivoStorage->delete($croquis->imagen_preview);
            $this->archivoStorage->delete($croquis->pdf_path);
            $croquis->delete();
        }

        return redirect()
            ->route('croquis.show', $hecho->id)
            ->with('success', 'Croquis eliminado correctamente.');
    }

    public function preview(Hechos $hecho)
    {
        $croquis = Croquis::where('hecho_id', $hecho->id)
            ->latest('id')
            ->first();

        abort_unless($croquis && $croquis->imagen_preview, 404);

        return $this->archivoStorage->response(
            $croquis->imagen_preview,
            'hecho_' . $hecho->id . '_croquis.png'
        );
    }

    private function guardarPreview(?string $preview, Hechos $hecho, ?string $actual = null): ?string
    {
        if (!$preview) {
            return $actual;
        }

        if (!preg_match('/^data:image\/png;base64,/', $preview)) {
            return $actual;
        }

        $contenido = base64_decode(substr($preview, strpos($preview, ',') + 1), true);

        if ($contenido === false) {
            return $actual;
        }

        $path = 'previews/hecho_' . $hecho->id . '_croquis.png';
        $this->archivoStorage->putContent($contenido, $path, 'image/png');

        if ($actual && $this->archivoStorage->normalizePath($actual) !== $path) {
            $this->archivoStorage->delete($actual);
        }

        return $path;
    }
}
