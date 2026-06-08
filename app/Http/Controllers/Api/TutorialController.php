<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tutorial;
use App\Models\TutorialCategoria;
use Illuminate\Http\Request;

class TutorialController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $unidadId = $user && $user->unidad_id ? (int) $user->unidad_id : null;

        $categorias = TutorialCategoria::query()
            ->where('activo', true)
            ->whereHas('tutoriales', function ($query) use ($unidadId) {
                $query
                    ->paraAppMovil()
                    ->visiblesParaUnidad($unidadId);
            })
            ->with(['tutoriales' => function ($query) use ($unidadId) {
                $query
                    ->paraAppMovil()
                    ->visiblesParaUnidad($unidadId)
                    ->orderBy('orden')
                    ->orderBy('titulo');
            }])
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'data' => $categorias->map(function (TutorialCategoria $categoria) {
                return [
                    'id' => $categoria->id,
                    'nombre' => $categoria->nombre,
                    'slug' => $categoria->slug,
                    'descripcion' => $categoria->descripcion,
                    'tutoriales' => $categoria->tutoriales->map(function (Tutorial $tutorial) {
                        return [
                            'id' => $tutorial->id,
                            'titulo' => $tutorial->titulo,
                            'descripcion' => $tutorial->descripcion,
                            'youtube_url' => $tutorial->youtube_url,
                            'youtube_video_id' => $tutorial->youtube_video_id,
                            'youtube_embed_url' => $tutorial->youtube_embed_url,
                            'youtube_thumbnail_url' => $tutorial->youtube_thumbnail_url,
                            'unidad_id' => $tutorial->unidad_id,
                            'orden' => $tutorial->orden,
                        ];
                    })->values(),
                ];
            })->values(),
        ]);
    }
}
