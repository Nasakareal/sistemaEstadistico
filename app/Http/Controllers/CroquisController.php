<?php

namespace App\Http\Controllers;

use App\Models\Croquis;
use App\Models\Hechos;
use App\Services\CroquisPreviewService;
use Illuminate\Http\Request;

class CroquisController extends Controller
{
    private $previewService;

    public function __construct(CroquisPreviewService $previewService)
    {
        $this->previewService = $previewService;
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
            $croquis->delete();
        }

        return redirect()
            ->route('croquis.show', $hecho->id)
            ->with('success', 'Croquis eliminado correctamente.');
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

        $directorioRelativo = 'img/croquis/previews';
        $directorio = public_path($directorioRelativo);

        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        $nombreArchivo = 'hecho_' . $hecho->id . '_croquis.png';
        $ruta = $directorio . DIRECTORY_SEPARATOR . $nombreArchivo;

        file_put_contents($ruta, $contenido);

        return $directorioRelativo . '/' . $nombreArchivo;
    }
}
