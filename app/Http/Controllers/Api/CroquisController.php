<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Croquis;
use App\Models\Hechos;
use App\Services\CroquisPreviewService;
use App\Support\HechoAccess;
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
        if (!HechoAccess::canView(request()->user(), $hecho)) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permiso para consultar este hecho.',
            ], 403);
        }

        $croquis = Croquis::where('hecho_id', $hecho->id)
            ->latest('id')
            ->first();

        if (!$croquis) {
            return response()->json([
                'ok' => true,
                'message' => 'El hecho no tiene croquis registrado.',
                'data' => null,
            ], 200);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Croquis obtenido correctamente.',
            'data' => $this->transformCroquis($croquis, $hecho),
        ], 200);
    }

    public function store(Request $request, Hechos $hecho)
    {
        if (!HechoAccess::canEdit($request->user(), $hecho)) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permiso para editar este hecho.',
            ], 403);
        }

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

        $croquis = $croquis->fresh();
        $this->previewService->ensure($croquis, $hecho);
        $croquis = $croquis->fresh();

        return response()->json([
            'ok' => true,
            'message' => 'Croquis guardado correctamente.',
            'data' => $this->transformCroquis($croquis, $hecho),
        ], 200);
    }

    public function update(Request $request, Hechos $hecho)
    {
        if (!HechoAccess::canEdit($request->user(), $hecho)) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permiso para editar este hecho.',
            ], 403);
        }

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

        $croquis = $croquis->fresh();
        $this->previewService->ensure($croquis, $hecho);
        $croquis = $croquis->fresh();

        return response()->json([
            'ok' => true,
            'message' => 'Croquis actualizado correctamente.',
            'data' => $this->transformCroquis($croquis, $hecho),
        ], 200);
    }

    public function destroy(Hechos $hecho)
    {
        if (!HechoAccess::canEdit(request()->user(), $hecho)) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permiso para editar este hecho.',
            ], 403);
        }

        $croquis = Croquis::where('hecho_id', $hecho->id)->first();

        if (!$croquis) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró croquis para este hecho.',
            ], 404);
        }

        $croquis->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Croquis eliminado correctamente.',
        ], 200);
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

    private function transformCroquis(Croquis $croquis, Hechos $hecho): array
    {
        return [
            'id' => $croquis->id,
            'hecho_id' => $croquis->hecho_id,
            'titulo' => $croquis->titulo,
            'plantilla' => $croquis->plantilla,
            'orientacion' => $croquis->orientacion,
            'escala' => $croquis->escala,
            'json_dibujo' => $croquis->json_dibujo,
            'imagen_preview' => $croquis->imagen_preview,
            'imagen_preview_url' => $croquis->imagen_preview ? asset($croquis->imagen_preview) : null,
            'pdf_path' => $croquis->pdf_path,
            'pdf_url' => $croquis->pdf_path ? asset($croquis->pdf_path) : null,
            'created_by' => $croquis->created_by,
            'updated_by' => $croquis->updated_by,
            'created_at' => $croquis->created_at,
            'updated_at' => $croquis->updated_at,
            'hecho' => [
                'id' => $hecho->id,
                'folio_c5i' => $hecho->folio_c5i,
                'fecha' => $hecho->fecha,
                'hora' => $hecho->hora,
                'tipo_hecho' => $hecho->tipo_hecho,
                'situacion' => $hecho->situacion,
            ],
        ];
    }
}
