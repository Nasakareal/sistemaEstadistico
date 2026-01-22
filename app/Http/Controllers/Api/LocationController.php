<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLocation;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * POST /api/location
     * Guarda (upsert) la última ubicación del usuario autenticado.
     * OJO: si compartir_ubicacion está apagado por jefe/admin, se ignora.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ((int)($user->compartir_ubicacion ?? 0) !== 1) {
            return response()->json([
                'message' => 'Tu ubicación está desactivada por tu jefe o por administración. No se guardó tu ubicación.',
            ], 200);
        }

        $validated = $request->validate([
            'lat'         => 'required|numeric|between:-90,90',
            'lng'         => 'required|numeric|between:-180,180',
            'accuracy'    => 'nullable|numeric|min:0',
            'speed'       => 'nullable|numeric|min:0',
            'heading'     => 'nullable|numeric|between:0,360',
            'captured_at' => 'nullable|date',
        ]);

        $location = UserLocation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'lat'         => $validated['lat'],
                'lng'         => $validated['lng'],
                'accuracy'    => $validated['accuracy'] ?? null,
                'speed'       => $validated['speed'] ?? null,
                'heading'     => $validated['heading'] ?? null,
                'captured_at' => $validated['captured_at'] ?? now(),
            ]
        );

        return response()->json([
            'message' => 'Ubicación guardada',
            'data'    => $location,
        ], 201);
    }

    /**
     * GET /api/location/last
     * Regresa la última ubicación del usuario autenticado.
     */
    public function last(Request $request)
    {
        $user = $request->user();

        $location = UserLocation::where('user_id', $user->id)->first();

        return response()->json([
            'data' => $location
        ]);
    }

    /**
     * GET /api/users/{user}/location/last
     * Regresa la última ubicación de un usuario específico.
     *
     * Protección mínima:
     * - Si NO tienes permiso "ver mapa", te limita a tu misma unidad y turno.
     * - Si sí tienes permiso, lo deja pasar (Admin/Coordinador/etc).
     */
    public function lastByUser(Request $request, User $user)
    {
        $actor = $request->user();

        if (!$actor->can('ver mapa')) {
            if ($actor->unidad_id && $user->unidad_id !== $actor->unidad_id) {
                abort(403, 'No autorizado.');
            }
            if ($actor->turno_id && $user->turno_id !== $actor->turno_id) {
                abort(403, 'No autorizado.');
            }
        }

        $location = UserLocation::where('user_id', $user->id)->first();

        return response()->json([
            'data' => $location
        ]);
    }

    /**
     * GET /api/locations
     * (Tú ya tienes la ruta apuntando a index, pero no estaba el método en tu controller original)
     * Si no lo usas aún, puedes borrar la ruta o dejar esto listo.
     *
     * Regresa todas las ubicaciones visibles para el actor:
     * - Si puede "ver mapa": regresa todas
     * - Si no: solo su unidad + turno
     */
    public function index(Request $request)
    {
        $actor = $request->user();

        $query = UserLocation::query()
            ->select([
                'user_locations.id',
                'user_locations.user_id',
                'user_locations.lat',
                'user_locations.lng',
                'user_locations.accuracy',
                'user_locations.speed',
                'user_locations.heading',
                'user_locations.captured_at',
            ])
            ->join('users', 'users.id', '=', 'user_locations.user_id');

        if (!$actor->can('ver mapa')) {
            if ($actor->unidad_id) {
                $query->where('users.unidad_id', $actor->unidad_id);
            }
            if ($actor->turno_id) {
                $query->where('users.turno_id', $actor->turno_id);
            }
        }

        $data = $query->orderByDesc('user_locations.captured_at')->get();

        return response()->json([
            'data' => $data,
        ]);
    }
}
